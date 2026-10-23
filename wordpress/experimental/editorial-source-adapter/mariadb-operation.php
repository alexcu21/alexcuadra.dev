<?php
/** Experimental WP/MariaDB operation. Never registered as an ability. */
namespace AlexCuadra\EditorialSourceExperiment;
require_once __DIR__.'/journal-records.php';

function local_origin_admitted() {
    if(home_url()==='http://wordpress'){return true;}
    return getenv('AC_SOURCE_EFFECTS_FULL')==='verified-backup-v1' && getenv('AC_SOURCE_EFFECTS_NETWORK')==='internal-only'
        && defined('DB_HOST') && DB_HOST==='db' && defined('DB_NAME') && DB_NAME==='wordpress'
        && home_url()==='https://content.alexcuadra.dev' && site_url()===home_url();
}
function local_wp_guard() {
    global $wp_object_cache;
    if (!defined('AC_EDITORIAL_SOURCE_EXPERIMENT') || AC_EDITORIAL_SOURCE_EXPERIMENT !== true
        || wp_get_environment_type() !== 'local' || !local_origin_admitted() || get_bloginfo('version') !== '7.1.3'
        || !in_array(wp_using_ext_object_cache(), [false,null], true)
        || file_exists(WP_CONTENT_DIR.'/object-cache.php') || !is_object($wp_object_cache)
        || get_class($wp_object_cache)!=='WP_Object_Cache'
        || realpath((new \ReflectionClass($wp_object_cache))->getFileName())!==realpath(ABSPATH.WPINC.'/class-wp-object-cache.php')) { throw new \RuntimeException('local_wp_experiment_only'); }
}
function deferred_publish($projectId, callable $write) {
    global $wp_filter;
    static $busy = false;
    local_wp_guard();
    if ($busy || doing_action('transition_post_status')) { throw new \RuntimeException('nested_effect_scope'); }
    $hook = $wp_filter['transition_post_status'] ?? null; $found = [];
    foreach ($hook->callbacks ?? [] as $priority => $entries) {
        foreach ($entries as $key => $entry) {
            $fn = $entry['function'];
            if (is_array($fn) && is_object($fn[0]) && get_class($fn[0]) === 'LiteSpeed\\Purge' && $fn[1] === 'purge_publish') {
                $found[] = [$priority,$key,$entry];
            }
        }
    }
    if (count($found) !== 1 || $found[0][0] !== 10 || $found[0][2]['accepted_args'] !== 3) { throw new \RuntimeException('purge_callback_changed'); }
    [$priority,$key,$entry] = $found[0];
    $file = (new \ReflectionMethod($entry['function'][0], 'purge_publish'))->getFileName();
    if (hash_file('sha256', $file) !== 'b9a53d0221687eafa935245b592138f989c143036b56bce00e633ff8fe5bc5a6') { throw new \RuntimeException('purge_code_changed'); }
    $wrapper = static function ($new,$old,$post) use ($entry,$projectId) {
        if ((int)$post->ID === $projectId && ($new === 'publish' || $old === 'publish')) { return; }
        return ($entry['function'])($new,$old,$post);
    };
    $busy = true;
    // Preserve WP_Hook slot/order/accepted_args. This internal representation is
    // pinned by the WP version tested; it needs revalidation on a future release.
    $hook->callbacks[$priority][$key]['function'] = $wrapper;
    try { return $write(); }
    finally {
        $drift = ($wp_filter['transition_post_status'] ?? null) !== $hook
            || ($hook->callbacks[$priority][$key]['function'] ?? null) !== $wrapper
            || ($hook->callbacks[$priority][$key]['accepted_args'] ?? null) !== 3;
        $hook->callbacks[$priority][$key] = $entry; $busy = false;
        if ($drift) { throw new \RuntimeException('purge_scope_changed'); }
    }
}

/** Pinned core wpdb + mysqli only. Never let core replay a query on a new session. */
function owned_transaction($db, callable $work) {
    local_wp_guard();
    if($db!==($GLOBALS['wpdb']??null)||get_class($db)!=='wpdb'){throw new \RuntimeException('source_connection_mismatch');}
    $handle=(new \ReflectionProperty(\wpdb::class,'dbh'))->getValue($db);
    if(!$handle instanceof \mysqli){throw new \RuntimeException('source_mysqli_required');}
    $mode=(new \mysqli_driver)->report_mode;$started=false;$committed=false;$guard=null;
    $assert=static function()use($db,$handle){
        \mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        if($db!==($GLOBALS['wpdb']??null)||(new \ReflectionProperty(\wpdb::class,'dbh'))->getValue($db)!==$handle){throw new \RuntimeException('source_transaction_lost');}
        $result=\mysqli_query($handle,'SELECT @@in_transaction');$row=\mysqli_fetch_row($result);\mysqli_free_result($result);
        if(!$row||(int)$row[0]!==1){throw new \RuntimeException('source_transaction_lost');}
    };
    $guard=static function($sql)use($assert,&$guard){
        global $wp_filter;
        // Other connections can reset PHP's process-wide mysqli reporting mode.
        // Reassert before every wpdb query, and refuse a later filter in this slot.
        \mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        $entries=$wp_filter['query']->callbacks[PHP_INT_MAX]??[];$last=end($entries);
        if(!$last||$last['function']!==$guard){throw new \RuntimeException('source_query_guard_changed');}
        $assert();
        // Closed SQL verb admission excludes implicit/explicit commit statements.
        // SET timestamp is solely the local expiry-test clock; no other SET is admitted.
        if(!preg_match('/^\s*(?:SELECT|INSERT|UPDATE|DELETE|REPLACE|SHOW|DESCRIBE|EXPLAIN)\b/i',$sql)
            && !preg_match('/^\s*SET\s+timestamp\s*=/i',$sql)){throw new \RuntimeException('source_transaction_control_denied');}
        return $sql;
    };
    try{
        \mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        $r=\mysqli_query($handle,'SELECT @@in_transaction');$row=\mysqli_fetch_row($r);\mysqli_free_result($r);
        if(!$row||(int)$row[0]!==0){throw new \RuntimeException('outer_transaction');}
        \mysqli_query($handle,'START TRANSACTION');$started=true;
        add_filter('query',$guard,PHP_INT_MAX,1);
        $value=$work();$guard('SELECT 1');
        if(\mysqli_query($handle,'COMMIT')!==true){throw new \RuntimeException('source_transaction_lost');}
        $committed=true;return $value;
    }catch(\mysqli_sql_exception $e){
        if(in_array($e->getCode(),[2006,2013,2055],true)){throw new \RuntimeException('source_transaction_lost',0,$e);}
        throw $e;
    }finally{
        remove_filter('query',$guard,PHP_INT_MAX);
        try{
            if($started&&!$committed){
                \mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
                // Raw original handle only: rollback must never reconnect.
                try{\mysqli_query($handle,'ROLLBACK');}catch(\Throwable $ignored){}
            }
        }finally{\mysqli_report($mode);}
    }
}

final class MariaDbOperation {
    private $db;
    private $events;
    private $effects;
    private $invalidate;
    public function __construct($db, ?callable $invalidate=null) {
        local_wp_guard(); $this->db=$db; $this->invalidate=$invalidate;
        $this->events=$db->prefix.'ac_exp_source_events'; $this->effects=$db->prefix.'ac_exp_source_effects';
        if (!preg_match('/^[a-zA-Z0-9_]+$/D',$db->prefix)) { throw new \RuntimeException('invalid_prefix'); }
    }
    private function query($sql) {
        try{$n=$this->db->query($sql);}catch(\mysqli_sql_exception $e){if(in_array($e->getCode(),[2006,2013,2055],true)){throw $e;}throw new \RuntimeException('experiment_sql_failed',0,$e);} if ($n===false) { throw new \RuntimeException('experiment_sql_failed'); } return $n;
    }
    private function outside() {
        local_wp_guard();
        $tx=$this->db->get_var('SELECT @@in_transaction');
        if ($tx===null || $this->db->last_error || (int)$tx!==0) { throw new \RuntimeException('outer_transaction'); }
    }
    public function install() {
        $this->outside();
        $this->query("CREATE TABLE $this->events (event_id VARBINARY(64) NOT NULL PRIMARY KEY,event_hash VARBINARY(64) NOT NULL,
            project_id BIGINT UNSIGNED NOT NULL,operation VARBINARY(8) NOT NULL,receipt LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL) ENGINE=InnoDB");
        $this->query("CREATE TABLE $this->effects (event_id VARBINARY(64) NOT NULL PRIMARY KEY,project_id BIGINT UNSIGNED NOT NULL,
            state VARBINARY(16) NOT NULL,attempts INT UNSIGNED NOT NULL DEFAULT 0,available_at BIGINT UNSIGNED NOT NULL,
            lease_token VARBINARY(32) NULL,lease_until BIGINT UNSIGNED NULL,last_outcome VARBINARY(16) NULL) ENGINE=InnoDB");
    }
    public function schema() {
        $expected=[
            $this->events=>[['event_id','varbinary(64)','NO','PRI',null],['event_hash','varbinary(64)','NO','',null],['project_id','bigint unsigned','NO','',null],['operation','varbinary(8)','NO','',null],['receipt','longtext','NO','',null]],
            $this->effects=>[['event_id','varbinary(64)','NO','PRI',null],['project_id','bigint unsigned','NO','',null],['state','varbinary(16)','NO','',null],['attempts','int unsigned','NO','','0'],['available_at','bigint unsigned','NO','',null],['lease_token','varbinary(32)','YES','',null],['lease_until','bigint unsigned','YES','',null],['last_outcome','varbinary(16)','YES','',null]]];
        foreach($expected as $table=>$columns){
            $rows=$this->db->get_results("SHOW FULL COLUMNS FROM $table",ARRAY_A);
            $actual=[];
            foreach($rows??[] as $row){
                $type=preg_replace('/^(bigint|int)\(\d+\)/','$1',strtolower($row['Type']));
                $actual[]=[$row['Field'],$type,$row['Null'],$row['Key'],$row['Default']];
                if($row['Extra']!=='' || ($row['Field']==='receipt' && $row['Collation']!=='utf8mb4_bin')){throw new \RuntimeException('experiment_schema_changed');}
            }
            $indexes=$this->db->get_results("SHOW INDEX FROM $table",ARRAY_A);
            if($actual!==$columns || count($indexes??[])!==1 || $indexes[0]['Key_name']!=='PRIMARY' || $indexes[0]['Column_name']!=='event_id') {throw new \RuntimeException('experiment_schema_changed');}
            $triggers=$this->db->get_var($this->db->prepare('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE=%s',$table));
            $constraints=$this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s AND CONSTRAINT_TYPE IN ('CHECK','FOREIGN KEY')",$table));
            if($triggers===null || (int)$triggers!==0 || $constraints===null || (int)$constraints!==0){throw new \RuntimeException('experiment_schema_changed');}
        }
        foreach([$this->events,$this->effects,$this->db->posts,$this->db->postmeta,$this->db->options,$this->db->terms,$this->db->term_taxonomy,$this->db->term_relationships] as $table){
            $engine=$this->db->get_var($this->db->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));
            if($engine!=='InnoDB'){throw new \RuntimeException('transactional_tables_required');}
        }
    }
    public function record(array $event, callable $write) {
        if ($this->db !== ($GLOBALS['wpdb'] ?? null)) { throw new \RuntimeException('source_connection_mismatch'); }
        $this->outside(); $this->schema();
        foreach(['event_id','event_hash'] as $key){if(!is_string($event[$key]??null)||!preg_match('/^[a-f0-9]{64}$/D',$event[$key])){throw new \RuntimeException('invalid_event');}}
        if(!is_int($event['project_id']??null)||$event['project_id']<1||!in_array($event['operation']??null,['apply','recover'],true)){throw new \RuntimeException('invalid_event');}
        $id=$event['project_id'];
        try {return owned_transaction($this->db,function()use($event,$write,$id){
            $prior=$this->db->get_row($this->db->prepare("SELECT * FROM $this->events WHERE event_id=%s FOR UPDATE",$event['event_id']),ARRAY_A);
            if($this->db->last_error){throw new \RuntimeException('experiment_sql_failed');}
            if($prior){
                if($prior['event_hash']!==$event['event_hash']||(int)$prior['project_id']!==$id||$prior['operation']!==$event['operation']){throw new \RuntimeException('event_reused');}
                return decode_source_event($prior);
            }
            $this->query($this->db->prepare("INSERT INTO $this->events VALUES (%s,%s,%d,%s,'')",$event['event_id'],$event['event_hash'],$id,$event['operation']));
            $receipt=deferred_publish($id,$write);
            if(!is_array($receipt)){throw new \RuntimeException('invalid_receipt');}
            $encoded=json_encode($receipt,JSON_THROW_ON_ERROR);
            if(strlen($encoded)>65536){throw new \RuntimeException('receipt_too_large');}
            $this->query($this->db->prepare("UPDATE $this->events SET receipt=%s WHERE event_id=%s",$encoded,$event['event_id']));
            $this->query($this->db->prepare("INSERT INTO $this->effects (event_id,project_id,state,available_at) VALUES (%s,%d,'pending',UNIX_TIMESTAMP())",$event['event_id'],$id));
            return $receipt;
        });} finally {
            clean_post_cache($id); wp_cache_delete($id,'post_meta');
            if(function_exists('acf_flush_value_cache')){acf_flush_value_cache($id,'project_tagline');}
            if($this->invalidate!==null){($this->invalidate)($id);}
        }
    }
    public function entry($eventId) {
        $this->outside();$this->schema();
        if(!operating_hex($eventId)){throw new \RuntimeException('invalid_event');}
        $r=$this->db->get_row($this->db->prepare("SELECT * FROM $this->events WHERE event_id=%s",$eventId),ARRAY_A);
        if($this->db->last_error){throw new \RuntimeException('experiment_sql_failed');}
        if($r){decode_source_event($r);}return $r;
    }
    public function effect($eventId) {
        $this->outside();$this->schema();
        if(!operating_hex($eventId)){throw new \RuntimeException('invalid_event');}
        $r=$this->db->get_row($this->db->prepare("SELECT * FROM $this->effects WHERE event_id=%s",$eventId),ARRAY_A);
        if($this->db->last_error){throw new \RuntimeException('experiment_sql_failed');}
        return $r?validate_source_effect($r):null;
    }
    /** Worker on a separate request/connection. API invocation is not cache delivery proof. */
    public function consume($eventId, callable $purge) {
        $this->outside(); $this->schema();
        $before=$this->effect($eventId);$entry=$this->entry($eventId);
        if(!$before||!$entry){return 'not_claimed';}
        if($before['project_id']!==$entry['project_id']){throw new \RuntimeException('source_event_relation_changed');}
        $token=bin2hex(random_bytes(16));
        $n=$this->query($this->db->prepare("UPDATE $this->effects f SET state='running',attempts=attempts+1,lease_token=%s,lease_until=UNIX_TIMESTAMP()+30
            WHERE event_id=%s AND attempts<8 AND EXISTS (SELECT 1 FROM $this->events e WHERE e.event_id=f.event_id AND e.project_id=f.project_id AND e.receipt<>'')
            AND ((state IN ('pending','retry') AND available_at<=UNIX_TIMESTAMP()) OR (state='running' AND lease_until<=UNIX_TIMESTAMP()))",$token,$eventId));
        if($n!==1){
            $this->query($this->db->prepare("UPDATE $this->effects SET state='attention',lease_token=NULL,lease_until=NULL,last_outcome='attempt_limit'
                WHERE event_id=%s AND attempts>=8 AND ((state='running' AND lease_until<=UNIX_TIMESTAMP()) OR (state='retry' AND available_at<=UNIX_TIMESTAMP()))",$eventId));
            return 'not_claimed';
        }
        $row=$this->effect($eventId);
        if(!$row || $row['lease_token']!==$token){return 'lease_lost';}
        try{$outcome=$purge((int)$row['project_id']);if(!in_array($outcome,['requested','retry','uncertain'],true)){$outcome='uncertain';}}
        catch(\Throwable $e){$outcome='uncertain';}
        $state=$outcome==='requested'?'requested':((int)$row['attempts']>=8?'attention':'retry');
        $delay=min(300,2**min(8,(int)$row['attempts']));
        $n=$this->query($this->db->prepare("UPDATE $this->effects SET state=%s,last_outcome=%s,available_at=UNIX_TIMESTAMP()+%d,lease_token=NULL,lease_until=NULL
            WHERE event_id=%s AND state='running' AND lease_token=%s AND lease_until>UNIX_TIMESTAMP()",$state,$outcome,$delay,$eventId,$token));
        return $n===1?$state:'lease_lost';
    }
}
