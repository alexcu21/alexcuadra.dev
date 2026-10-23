<?php
/** Local operational-contract integration. No plugin hooks, abilities or live migration. */
namespace AlexCuadra\EditorialSourceExperiment;
use AlexCuadra\EditorialProposals as P;
require_once __DIR__.'/operating-storage.php';

final class OperatingContract {
    const APPLY='alexcuadra_apply_project_changes';
    const RECOVER='alexcuadra_recover_project_changes';
    const APPROVE='alexcuadra_approve_public_project_changes';
    const MAX_REVIEWS=8;
    const MAX_REQUESTS=64;
    const MAX_BYTES=8388608; // Separate from the unchanged rc.2 proposal budget.
    const MAX_DOCUMENT=524288;
    const MAX_RECEIPT=4096;
    const RECOVERY_BYTES=573440;
    private $db; private $journal; private $snapshot; private $schema; private $clear;
    private $reviews; private $approvals; private $requests; private $apps;
    public function __construct($db,MariaDbOperation $journal,callable $snapshot,callable $schema,callable $clear) {
        local_wp_guard();
        if($db!==($GLOBALS['wpdb']??null)||!function_exists('AlexCuadra\\EditorialProposals\\permission')||!P\storage_schema_valid()){throw new \RuntimeException('contract_dependencies');}
        $this->db=$db;$this->journal=$journal;$this->snapshot=$snapshot;$this->schema=$schema;$this->clear=$clear;
        foreach(['reviews','approvals','requests','apps'] as $name){$this->$name=$db->prefix.'ac_exp_operating_'.$name;}
    }
    private function fail($code){throw new \RuntimeException($code);}
    private function sql($sql){$n=$this->db->query($sql);if($n===false){$this->fail('contract_sql_failed');}return $n;}
    private function row($sql){$r=$this->db->get_row($sql,ARRAY_A);if($this->db->last_error){$this->fail('contract_sql_failed');}return $r;}
    private function now(){
        $value=$this->db->get_var('SELECT UNIX_TIMESTAMP()');
        if($value===null||$this->db->last_error||!ctype_digit((string)$value)){$this->fail('contract_sql_failed');}
        return (int)$value;
    }
    private function outside(){
        $active=$this->db->get_var('SELECT @@in_transaction');
        if($active===null||$this->db->last_error||(int)$active!==0){$this->fail('contract_outer_transaction');}
    }
    private function scope($operation='apply'){local_wp_guard();if(is_wp_error(P\permission())||!current_user_can($operation==='recover'?self::RECOVER:self::APPLY)){$this->fail('contract_forbidden');}}
    private function readScope(){local_wp_guard();if(is_wp_error(P\permission())||(!current_user_can(self::APPLY)&&!current_user_can(self::RECOVER))){$this->fail('contract_forbidden');}}
    private function input($input,$keys){
        if(array_diff(array_keys($input),$keys)||array_diff($keys,array_keys($input))||!in_array($input['locale']??null,['en','es'],true)){$this->fail('contract_invalid_input');}
        foreach($input as $key=>$value){
            if(in_array($key,['proposal_id','review_id'],true)){if(!is_int($value)||$value<1){$this->fail('contract_invalid_input');}}
            elseif($key!=='locale'&&(!is_string($value)||!preg_match('/^[a-f0-9]{'.($key==='request_id'?32:64).'}$/D',$value))){$this->fail('contract_invalid_input');}
        }
    }
    private function owned($id,$owner){
        $r=$this->row($this->db->prepare("SELECT ID FROM {$this->db->posts} WHERE ID=%d AND post_author=%d AND post_type=%s AND post_status='draft' AND post_password=''",$id,$owner,P\TYPE));
        if(!$r){$this->fail('contract_not_found');}
    }
    private function ownerLock($owner){
        $table=$this->db->prefix.'ac_proposal_owners';
        $row=$this->row($this->db->prepare("SELECT owner_id FROM $table WHERE owner_id=%d FOR UPDATE",$owner));
        if(!$row){$this->fail('contract_owner_not_ready');}
    }
    private function tables(){
        operating_storage_schema($this->db);
        foreach([$this->reviews,$this->approvals,$this->requests,$this->apps,$this->db->prefix.'ac_proposal_owners',$this->db->prefix.'ac_proposal_requests'] as $table){
            $engine=$this->db->get_var($this->db->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));
            if($this->db->last_error||$engine!=='InnoDB'){$this->fail('contract_transactional_storage_required');}
        }
    }
    public function install(){
        local_wp_guard();if(!defined('WP_CLI')||!WP_CLI||!current_user_can('manage_options')){$this->fail('contract_operator_required');}$this->outside();
        $this->sql("CREATE TABLE $this->reviews (review_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,owner_id BIGINT UNSIGNED NOT NULL,proposal_id BIGINT UNSIGNED NOT NULL,document LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,review_hash VARBINARY(64) NOT NULL,KEY owner_id(owner_id)) ENGINE=InnoDB");
        $this->sql("CREATE TABLE $this->approvals (review_id BIGINT UNSIGNED PRIMARY KEY,actor_id BIGINT UNSIGNED NOT NULL,document LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,consumed_event VARBINARY(64) NULL) ENGINE=InnoDB");
        $this->sql("CREATE TABLE $this->requests (owner_id BIGINT UNSIGNED NOT NULL,request_id VARBINARY(32) NOT NULL,request_hash VARBINARY(64) NOT NULL,response LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,PRIMARY KEY(owner_id,request_id)) ENGINE=InnoDB");
        $this->sql("CREATE TABLE $this->apps (proposal_id BIGINT UNSIGNED PRIMARY KEY,owner_id BIGINT UNSIGNED NOT NULL,review_id BIGINT UNSIGNED NOT NULL,event_id VARBINARY(64) NOT NULL,source_after VARBINARY(64) NOT NULL,reserved_bytes INT UNSIGNED NOT NULL,reserved_reviews INT UNSIGNED NOT NULL,reserved_requests INT UNSIGNED NOT NULL,recovery_review BIGINT UNSIGNED NULL,recovery_event VARBINARY(64) NULL,KEY owner_id(owner_id)) ENGINE=InnoDB");
    }
    private function transaction($owner,callable $fn){
        $this->outside();
        $this->tables();return owned_transaction($this->db,function()use($owner,$fn){$this->ownerLock($owner);return $fn();});
    }
    private function hash($kind,$input){ksort($input);return hash('sha256',json_encode([$kind,$input],JSON_THROW_ON_ERROR));}
    private function replay($owner,$input,$hash,$kind){
        $r=$this->row($this->db->prepare("SELECT * FROM $this->requests WHERE owner_id=%d AND request_id=%s",$owner,$input['request_id']));
        if(!$r){return null;}if(strlen($r['response'])>self::MAX_RECEIPT){$this->fail('contract_receipt_full');}if(!hash_equals($r['request_hash'],$hash)){$this->fail('contract_request_reused');}
        return decode_operating_receipt($r['response'],$kind,$input);
    }
    private function save($owner,$input,$hash,array $response){
        $encoded=json_encode($response,JSON_THROW_ON_ERROR);if(strlen($encoded)>self::MAX_RECEIPT){$this->fail('contract_receipt_full');}
        $this->sql($this->db->prepare("INSERT INTO $this->requests VALUES (%d,%s,%s,%s)",$owner,$input['request_id'],$hash,$encoded));
    }
    public function usage($owner){
        $review=$this->row($this->db->prepare("SELECT COUNT(*) AS n,COALESCE(SUM(OCTET_LENGTH(document)+192),0) AS bytes FROM $this->reviews WHERE owner_id=%d",$owner));
        $requests=$this->row($this->db->prepare("SELECT COUNT(*) AS n,COALESCE(SUM(OCTET_LENGTH(response)+128),0) AS bytes FROM $this->requests WHERE owner_id=%d",$owner));
        $apps=$this->row($this->db->prepare("SELECT COALESCE(SUM(reserved_bytes+8192+256),0) AS bytes,COALESCE(SUM(reserved_reviews),0) AS reviews,COALESCE(SUM(reserved_requests),0) AS requests FROM $this->apps WHERE owner_id=%d",$owner));
        return ['reviews'=>(int)$review['n']+(int)$apps['reviews'],'requests'=>(int)$requests['n']+(int)$apps['requests'],
            'bytes'=>(int)$review['bytes']+(int)$review['n']*4096+(int)$requests['bytes']+(int)$apps['bytes']];
    }
    private function budget($owner,$reviews=0,$requests=0,$bytes=0){
        $u=$this->usage($owner);if($u['reviews']+$reviews>self::MAX_REVIEWS||$u['requests']+$requests>self::MAX_REQUESTS||$u['bytes']+$bytes>self::MAX_BYTES){$this->fail('contract_owner_quota');}
    }
    private function proposal($id,$locale,$version,$base){
        $owner=get_current_user_id();$this->owned($id,$owner);
        $this->row($this->db->prepare("SELECT ID FROM {$this->db->posts} WHERE ID=%d FOR UPDATE",$id));
        clean_post_cache($id);wp_cache_delete($id,'post_meta');
        $p=P\load_owned($id,$locale);if(is_wp_error($p)){$this->fail('contract_proposal_changed');}
        if($p['payload']['version']!==$version||$p['payload']['base_version']!==$base){$this->fail('contract_proposal_changed');}
        return $p['payload'];
    }
    private function review($id,$owner,$locale,$hash=null){
        $r=$this->row($this->db->prepare("SELECT * FROM $this->reviews WHERE review_id=%d AND owner_id=%d FOR UPDATE",$id,$owner));
        if(!$r){$this->fail('contract_not_found');}if(strlen($r['document'])>self::MAX_DOCUMENT){$this->fail('contract_review_corrupt');}$d=decode_operating_review($r['document']);
        if(!is_array($d)||hash('sha256',$r['document'])!==$r['review_hash']||$d['owner_id']!==$owner||$d['proposal_id']!==(int)$r['proposal_id']||$d['format']!=='operating-review-v1'){$this->fail('contract_review_corrupt');}
        if($d['locale']!==$locale){$this->fail('contract_not_found');}
        if($hash!==null&&!hash_equals($r['review_hash'],$hash)){$this->fail('contract_review_changed');}
        $this->owned($d['proposal_id'],$owner);return ['document'=>$d,'hash'=>$r['review_hash']];
    }
    private function current(array $d){
        $this->scope($d['operation']);$p=null;
        if($d['operation']==='recover'){
            $this->owned($d['proposal_id'],get_current_user_id());
            $app=$this->row($this->db->prepare("SELECT * FROM $this->apps WHERE proposal_id=%d AND owner_id=%d FOR UPDATE",$d['proposal_id'],get_current_user_id()));if($app){validate_operating_application($app);}
            if(!$app||$app['recovery_event']!==null||$app['event_id']!==$d['application_event']||$app['source_after']!==$d['source_version']||(int)$app['recovery_review']!==$d['review_id']){$this->fail('contract_application_changed');}
        }else{$p=$this->proposal($d['proposal_id'],$d['locale'],$d['proposal_version'],$d['base_version']);}
        if($d['expires_at']<=$this->now()){$this->fail('contract_review_expired');}
        if(($this->schema)()!==$d['schema_version']){$this->fail('contract_schema_changed');}
        $s=($this->snapshot)($d['project_id'],$d['locale'],array_fill_keys(array_keys($d['after']),''),true);
        if($s['version']!==$d['source_version']||$s['before']!==$d['before']){$this->fail('contract_source_changed');}
        return $p;
    }
    public function prepare(array $input){
        $this->scope();$this->input($input,['proposal_id','locale','expected_proposal_version','expected_base_version','request_id']);
        $owner=get_current_user_id();$hash=$this->hash('prepare',$input);
        return $this->transaction($owner,function()use($input,$owner,$hash){
            $prior=$this->replay($owner,$input,$hash,'prepare');if($prior!==null){$this->owned($input['proposal_id'],$owner);return $prior;}
            $p=$this->proposal($input['proposal_id'],$input['locale'],$input['expected_proposal_version'],$input['expected_base_version']);
            if($this->row($this->db->prepare("SELECT proposal_id FROM $this->apps WHERE proposal_id=%d",$input['proposal_id']))){$this->fail('contract_proposal_already_applied');}
            $fields=array_fill_keys(array_keys($p['patch']),'');$s=($this->snapshot)($p['project_id'],$p['locale'],$fields,true);$after=$s['before'];
            foreach($p['patch'] as $key=>$value){if($key==='body_html'){$after[$key]=$value;}else{$after[$key]=['project_tagline'=>['present'=>true,'value'=>$value],'_project_tagline'=>['present'=>true,'value'=>P\TAGLINE_KEY]];}}
            validate_fields($s['before']);validate_fields($after);
            $created=$this->now();
            $d=['format'=>'operating-review-v1','owner_id'=>$owner,'proposal_id'=>$input['proposal_id'],'project_id'=>$p['project_id'],'locale'=>$p['locale'],
                'operation'=>'apply','proposal_version'=>$p['version'],'base_version'=>$p['base_version'],'source_version'=>$s['version'],'schema_version'=>($this->schema)(),
                'before'=>$s['before'],'after'=>$after,'created_at'=>$created,'expires_at'=>$created+600];
            $encoded=json_encode($d,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if(strlen($encoded)>self::MAX_DOCUMENT){$this->fail('contract_review_full');}
            $this->budget($owner,1,1,strlen($encoded)+192+4096+self::MAX_RECEIPT+128);
            $reviewHash=hash('sha256',$encoded);$this->sql($this->db->prepare("INSERT INTO $this->reviews (owner_id,proposal_id,document,review_hash) VALUES (%d,%d,%s,%s)",$owner,$input['proposal_id'],$encoded,$reviewHash));$id=(int)$this->db->insert_id;
            $dto=['format'=>'operating-review-receipt-v1','review_id'=>$id,'proposal_id'=>$input['proposal_id'],'project_id'=>$p['project_id'],'locale'=>$p['locale'],'review_hash'=>$reviewHash,'expires_at'=>$d['expires_at']];
            $this->save($owner,$input,$hash,$dto);$this->budget($owner);return $dto;
        });
    }
    public function inspect($reviewId,$locale){
        local_wp_guard();$this->tables();$this->input(['review_id'=>$reviewId,'locale'=>$locale],['review_id','locale']);$owner=get_current_user_id();
        if(defined('WP_CLI')&&WP_CLI&&current_user_can('manage_options')&&current_user_can(self::APPROVE)){
            $r=$this->row($this->db->prepare("SELECT owner_id FROM $this->reviews WHERE review_id=%d",$reviewId));if(!$r){$this->fail('contract_not_found');}$owner=(int)$r['owner_id'];
        }else{$this->readScope();}
        $r=$this->review($reviewId,$owner,$locale);
        return ['format'=>'operating-review-inspection-v1','review_hash'=>$r['hash'],'document'=>$r['document']];
    }
    /** Operator-only CLI: never a registered ability/REST/GraphQL callback. */
    public function approve($reviewId,$locale,$expectedHash){
        local_wp_guard();$actor=get_current_user_id();
        if(!defined('WP_CLI')||!WP_CLI||!current_user_can('manage_options')||!current_user_can(self::APPROVE)){$this->fail('contract_operator_required');}
        $this->input(['review_id'=>$reviewId,'locale'=>$locale,'expected_review_hash'=>$expectedHash],['review_id','locale','expected_review_hash']);
        $r=$this->row($this->db->prepare("SELECT owner_id FROM $this->reviews WHERE review_id=%d",$reviewId));if(!$r){$this->fail('contract_not_found');}$owner=(int)$r['owner_id'];
        if($owner===$actor){$this->fail('contract_independent_approval_required');}
        try{
            wp_set_current_user($owner);
            return $this->transaction($owner,function()use($reviewId,$locale,$expectedHash,$actor,$owner){
                $review=$this->review($reviewId,$owner,$locale,$expectedHash);$d=$review['document'];$d['review_id']=$reviewId;$this->current($d);
                $prior=$this->row($this->db->prepare("SELECT * FROM $this->approvals WHERE review_id=%d FOR UPDATE",$reviewId));
                if($prior){decode_operating_approval($prior,$d,$reviewId,$owner,$expectedHash);if($prior['consumed_event']!==null){$this->fail('contract_approval_consumed');}return ['format'=>'operating-approval-receipt-v1','review_id'=>$reviewId,'review_hash'=>$expectedHash,'actor_id'=>(int)$prior['actor_id']];}
                $approvedAt=$this->now();if($approvedAt>=$d['expires_at']){$this->fail('contract_review_expired');}
                $approval=['format'=>'operating-approval-v1','review_id'=>$reviewId,'review_hash'=>$expectedHash,'executor_id'=>$owner,'actor_id'=>$actor,'approved_at'=>$approvedAt];
                $encoded=json_encode($approval,JSON_THROW_ON_ERROR);if(strlen($encoded)+192>4096){$this->fail('contract_approval_full');}
                $this->sql($this->db->prepare("INSERT INTO $this->approvals (review_id,actor_id,document,consumed_event) VALUES (%d,%d,%s,NULL)",$reviewId,$actor,$encoded));$this->budget($owner);
                return ['format'=>'operating-approval-receipt-v1','review_id'=>$reviewId,'review_hash'=>$expectedHash,'actor_id'=>$actor];
            });
        }finally{wp_set_current_user($actor);}
    }
    public function apply(array $input){
        $this->scope();$this->input($input,['review_id','locale','expected_review_hash','request_id']);$this->outside();$this->tables();$owner=get_current_user_id();$hash=$this->hash('apply',$input);
        $review=$this->review($input['review_id'],$owner,$input['locale'],$input['expected_review_hash']);$d=$review['document'];if($d['operation']!=='apply'){$this->fail('contract_operation_mismatch');}
        $prior=$this->replay($owner,$input,$hash,'apply');if($prior!==null){return $prior;}
        $event=['event_id'=>hash('sha256','operating|'.$owner.'|'.$input['request_id']),'event_hash'=>$hash,'project_id'=>$d['project_id'],'operation'=>'apply'];
        return $this->journal->record($event,function()use($owner,$input,$hash,$event){
            $this->ownerLock($owner);$this->scope();$review=$this->review($input['review_id'],$owner,$input['locale'],$input['expected_review_hash']);$d=$review['document'];$this->current($d);
            $approval=$this->row($this->db->prepare("SELECT * FROM $this->approvals WHERE review_id=%d FOR UPDATE",$input['review_id']));
            if(!$approval){$this->fail('contract_approval_required');}
            $a=decode_operating_approval($approval,$d,$input['review_id'],$owner,$input['expected_review_hash']);
            if($a['review_hash']!==$input['expected_review_hash']||$a['executor_id']!==$owner||$a['actor_id']===$owner||$a['actor_id']!==(int)$approval['actor_id']||$a['approved_at']<$d['created_at']||$a['approved_at']>=$d['expires_at']){$this->fail('contract_approval_invalid');}
            if($approval['consumed_event']!==null){$this->fail('contract_approval_consumed');}
            if($this->row($this->db->prepare("SELECT proposal_id FROM $this->apps WHERE proposal_id=%d FOR UPDATE",$d['proposal_id']))){$this->fail('contract_proposal_already_applied');}
            // Reserve one recovery review, two requests and its bounded payload/event.
            $this->budget($owner,1,3,self::RECOVERY_BYTES+8192+256+self::MAX_RECEIPT+128);
            $this->sql($this->db->prepare("INSERT INTO $this->apps VALUES (%d,%d,%d,%s,'',%d,1,2,NULL,NULL)",$d['proposal_id'],$owner,$input['review_id'],$event['event_id'],self::RECOVERY_BYTES));
            $after=write_source($d,$this->snapshot,$this->schema,$this->clear);
            $this->sql($this->db->prepare("UPDATE $this->apps SET source_after=%s WHERE proposal_id=%d",$after['version'],$d['proposal_id']));
            if($this->sql($this->db->prepare("UPDATE $this->approvals SET consumed_event=%s WHERE review_id=%d AND consumed_event IS NULL AND UNIX_TIMESTAMP()<%d",$event['event_id'],$input['review_id'],$d['expires_at']))!==1){$this->fail($this->now()>=$d['expires_at']?'contract_review_expired':'contract_approval_changed');}
            $dto=['format'=>'operating-application-receipt-v1','event_id'=>$event['event_id'],'review_id'=>$input['review_id'],'proposal_id'=>$d['proposal_id'],'project_id'=>$d['project_id'],'locale'=>$d['locale'],
                'content_state'=>'applied','source_version'=>$after['version'],'effect_state_at_commit'=>'pending'];
            $this->save($owner,$input,$hash,$dto);$this->budget($owner);return $dto;
        });
    }
    /** Frozen inverse of one committed application; never a forced overwrite. */
    public function prepareRecovery(array $input){
        $this->scope('recover');$this->input($input,['proposal_id','locale','expected_source_version','request_id']);
        $owner=get_current_user_id();$hash=$this->hash('prepare_recovery',$input);
        return $this->transaction($owner,function()use($input,$owner,$hash){
            $this->owned($input['proposal_id'],$owner);$prior=$this->replay($owner,$input,$hash,'prepare_recovery');if($prior!==null){return $prior;}
            $app=$this->row($this->db->prepare("SELECT * FROM $this->apps WHERE proposal_id=%d AND owner_id=%d FOR UPDATE",$input['proposal_id'],$owner));if($app){validate_operating_application($app);}
            if(!$app||$app['recovery_event']!==null){$this->fail('contract_application_changed');}
            if($app['source_after']!==$input['expected_source_version']){$this->fail('contract_source_changed');}
            $original=$this->review((int)$app['review_id'],$owner,$input['locale']);$d=$original['document'];
            if($app['recovery_review']!==null){
                $previous=$this->review((int)$app['recovery_review'],$owner,$input['locale']);
                if($previous['document']['expires_at']>$this->now()){$this->fail('contract_recovery_review_exists');}
                // Expired reviews remain immutable and charged; renewal needs real free capacity.
                $this->budget($owner,1,1,self::MAX_DOCUMENT+192+4096+self::MAX_RECEIPT+128);
            }
            $s=($this->snapshot)($d['project_id'],$d['locale'],array_fill_keys(array_keys($d['after']),''),true);
            if($s['version']!==$app['source_after']||$s['before']!==$d['after']){$this->fail('contract_source_changed');}
            if(($this->schema)()!==$d['schema_version']){$this->fail('contract_schema_changed');}
            $created=$this->now();
            $rd=['format'=>'operating-review-v1','owner_id'=>$owner,'proposal_id'=>$d['proposal_id'],'project_id'=>$d['project_id'],'locale'=>$d['locale'],
                'operation'=>'recover','application_event'=>$app['event_id'],'application_review_hash'=>$original['hash'],
                'source_version'=>$s['version'],'schema_version'=>$d['schema_version'],'before'=>$s['before'],'after'=>$d['before'],
                'created_at'=>$created,'expires_at'=>$created+600];
            $encoded=json_encode($rd,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            if(strlen($encoded)>self::MAX_DOCUMENT){$this->fail('contract_review_full');}
            // review_id is stored in the row and approval binding, not recursively hashed.
            $rh=hash('sha256',$encoded);$this->sql($this->db->prepare("INSERT INTO $this->reviews (owner_id,proposal_id,document,review_hash) VALUES (%d,%d,%s,%s)",$owner,$d['proposal_id'],$encoded,$rh));$id=(int)$this->db->insert_id;
            $this->sql($this->db->prepare("UPDATE $this->apps SET recovery_review=%d,reserved_reviews=0,reserved_requests=1,reserved_bytes=%d WHERE proposal_id=%d",$id,8192+self::MAX_RECEIPT+128,$d['proposal_id']));
            $dto=['format'=>'operating-review-receipt-v1','review_id'=>$id,'proposal_id'=>$d['proposal_id'],'project_id'=>$d['project_id'],'locale'=>$d['locale'],'review_hash'=>$rh,'expires_at'=>$rd['expires_at']];
            $this->save($owner,$input,$hash,$dto);$this->budget($owner);return $dto;
        });
    }
    public function recover(array $input){
        $this->scope('recover');$this->input($input,['review_id','locale','expected_review_hash','request_id']);$this->outside();$this->tables();
        $owner=get_current_user_id();$hash=$this->hash('recover',$input);$r=$this->review($input['review_id'],$owner,$input['locale'],$input['expected_review_hash']);$d=$r['document'];
        if($d['operation']!=='recover'){$this->fail('contract_operation_mismatch');}
        $prior=$this->replay($owner,$input,$hash,'recover');if($prior!==null){return $prior;}
        $event=['event_id'=>hash('sha256','operating|'.$owner.'|'.$input['request_id']),'event_hash'=>$hash,'project_id'=>$d['project_id'],'operation'=>'recover'];
        return $this->journal->record($event,function()use($owner,$input,$hash,$event){
            $this->ownerLock($owner);$r=$this->review($input['review_id'],$owner,$input['locale'],$input['expected_review_hash']);$d=$r['document'];$d['review_id']=$input['review_id'];$this->current($d);
            $approval=$this->row($this->db->prepare("SELECT * FROM $this->approvals WHERE review_id=%d FOR UPDATE",$input['review_id']));
            if(!$approval){$this->fail('contract_approval_required');}$a=decode_operating_approval($approval,$d,$input['review_id'],$owner,$input['expected_review_hash']);
            if($a['review_hash']!==$input['expected_review_hash']||$a['executor_id']!==$owner||$a['actor_id']===$owner||$a['actor_id']!==(int)$approval['actor_id']||$a['approved_at']<$d['created_at']||$a['approved_at']>=$d['expires_at']){$this->fail('contract_approval_invalid');}
            if($approval['consumed_event']!==null){$this->fail('contract_approval_consumed');}
            $after=write_source($d,$this->snapshot,$this->schema,$this->clear);
            if($this->sql($this->db->prepare("UPDATE $this->approvals SET consumed_event=%s WHERE review_id=%d AND consumed_event IS NULL AND UNIX_TIMESTAMP()<%d",$event['event_id'],$input['review_id'],$d['expires_at']))!==1){$this->fail($this->now()>=$d['expires_at']?'contract_review_expired':'contract_approval_changed');}
            if($this->sql($this->db->prepare("UPDATE $this->apps SET recovery_event=%s,reserved_reviews=0,reserved_requests=0,reserved_bytes=8192 WHERE proposal_id=%d AND recovery_review=%d AND recovery_event IS NULL",$event['event_id'],$d['proposal_id'],$input['review_id']))!==1){$this->fail('contract_application_changed');}
            $dto=['format'=>'operating-recovery-receipt-v1','event_id'=>$event['event_id'],'application_event'=>$d['application_event'],'review_id'=>$input['review_id'],'proposal_id'=>$d['proposal_id'],'project_id'=>$d['project_id'],'locale'=>$d['locale'],
                'content_state'=>'recovered','source_version'=>$after['version'],'effect_state_at_commit'=>'pending'];
            $this->save($owner,$input,$hash,$dto);$this->budget($owner);return $dto;
        });
    }
    public function effectStatus($proposalId,$locale){
        $this->readScope();$this->tables();$this->input(['proposal_id'=>$proposalId,'locale'=>$locale],['proposal_id','locale']);$owner=get_current_user_id();$this->owned($proposalId,$owner);
        $app=$this->row($this->db->prepare("SELECT * FROM $this->apps WHERE proposal_id=%d AND owner_id=%d",$proposalId,$owner));if($app){validate_operating_application($app);}if(!$app){$this->fail('contract_not_found');}
        $review=$this->review((int)$app['review_id'],$owner,$locale);$eventId=$app['recovery_event']??$app['event_id'];$effect=$this->journal->effect($eventId);if(!$effect){$this->fail('contract_effect_unavailable');}
        return ['format'=>'operating-effect-status-v1','event_id'=>$eventId,'proposal_id'=>$proposalId,'locale'=>$locale,'effect_state'=>$effect['state'],'attempts'=>(int)$effect['attempts']];
    }
}
