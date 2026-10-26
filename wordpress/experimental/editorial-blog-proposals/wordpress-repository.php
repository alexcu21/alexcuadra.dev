<?php
/** wpdb/InnoDB repository of private plans; no source writer, hooks or MCP tools. */
namespace AlexCuadra\EditorialBlogProposalLab;
require_once __DIR__.'/wordpress-source.php';
require_once __DIR__.'/connection-fence.php';

final class WordPressRepository {
    private $db;
    private string $prefix;
    public function __construct($db) {
        wordpress_lab_guard();
        if ($db!==($GLOBALS['wpdb']??null) || !preg_match('/^[a-zA-Z0-9_]+$/D',$db->prefix)) { reject('connection'); }
        $this->db=$db; $this->prefix=$db->prefix.'ac_blog_lab_';
    }
    private function query(string $sql,array $values=[],bool $rows=false) {
        $previous=$this->db->suppress_errors(true);
        try {
            $prepared=$values?$this->db->prepare($sql,...$values):$sql;
            $result=$rows?$this->db->get_results($prepared,ARRAY_A):$this->db->query($prepared);
            if ($result===false || $this->db->last_error) { reject('storage_failure'); }
            return $result;
        } finally { $this->db->suppress_errors($previous); }
    }
    private function row(string $sql,array $values=[]): ?array { return $this->query($sql,$values,true)[0]??null; }
    private function outside(): void {
        if ((int)($this->row('SELECT @@in_transaction AS n')['n']??1)!==0) { reject('outer_transaction'); }
    }
    private function tablesReady(): void {
        foreach (['owners','proposals','revisions','requests'] as $suffix) {
            $row=$this->row('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',[$this->prefix.$suffix]);
            if (($row['ENGINE']??null)!=='InnoDB') { reject('storage_not_ready'); }
        }
    }
    /** DDL is explicit local CLI/operator only; MariaDB DDL is not transactional. */
    public function install(): void {
        wordpress_lab_guard(); $this->outside();
        if (!defined('WP_CLI') || !WP_CLI || !current_user_can('manage_options')) { reject('operator'); }
        $p=$this->prefix;
        foreach (['owners','proposals','revisions','requests'] as $suffix) {
            if ($this->row('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',[$p.$suffix])) { reject('storage_not_empty'); }
        }
        $tail=' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin';
        $this->query("CREATE TABLE {$p}owners(owner_id BIGINT UNSIGNED PRIMARY KEY)$tail");
        $this->query("CREATE TABLE {$p}proposals(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,owner_id BIGINT UNSIGNED NOT NULL,version INT UNSIGNED NOT NULL,
            UNIQUE KEY owned(id,owner_id),FOREIGN KEY(owner_id) REFERENCES {$p}owners(owner_id))$tail");
        $this->query("CREATE TABLE {$p}revisions(proposal_id BIGINT UNSIGNED NOT NULL,owner_id BIGINT UNSIGNED NOT NULL,version INT UNSIGNED NOT NULL,
            document MEDIUMTEXT NOT NULL,document_hash CHAR(64) NOT NULL,
            PRIMARY KEY(proposal_id,version),UNIQUE KEY owned_version(proposal_id,owner_id,version),KEY owner(owner_id),
            FOREIGN KEY(proposal_id,owner_id) REFERENCES {$p}proposals(id,owner_id))$tail");
        $this->query("CREATE TABLE {$p}requests(owner_id BIGINT UNSIGNED NOT NULL,request_id CHAR(32) NOT NULL,
            request_hash CHAR(64) NOT NULL,proposal_id BIGINT UNSIGNED NOT NULL,version INT UNSIGNED NOT NULL,
            PRIMARY KEY(owner_id,request_id),FOREIGN KEY(proposal_id,owner_id,version) REFERENCES {$p}revisions(proposal_id,owner_id,version))$tail");
        $this->tablesReady();
    }
    /** Explicit owner row only; never grants scope or changes a role. */
    public function enroll(int $owner): void {
        wordpress_lab_guard(); $this->outside();
        if (!defined('WP_CLI') || !WP_CLI || !current_user_can('manage_options') || !positive($owner) || !get_userdata($owner)) { reject('operator'); }
        $this->tablesReady();
        if (!$this->row("SELECT owner_id FROM {$this->prefix}owners WHERE owner_id=%d",[$owner])) {
            $this->query("INSERT INTO {$this->prefix}owners(owner_id) VALUES (%d)",[$owner]);
        }
    }
    private function transaction(int $owner,callable $fn) {
        $this->outside();$this->tablesReady();
        return fenced_transaction($this->db,function()use($owner,$fn){
            if (!$this->row("SELECT owner_id FROM {$this->prefix}owners WHERE owner_id=%d FOR UPDATE",[$owner])) { reject('owner_not_ready'); }
            return $fn();
        });
    }
    private function head(int $owner,int $id): int {
        $r=$this->row("SELECT version FROM {$this->prefix}proposals WHERE owner_id=%d AND id=%d",[$owner,$id]);
        if (!$r) { reject('not_found'); }return (int)$r['version'];
    }
    private function document(int $owner,int $id,int $version): array {
        $r=$this->row("SELECT document,document_hash FROM {$this->prefix}revisions WHERE owner_id=%d AND proposal_id=%d AND version=%d",[$owner,$id,$version]);
        if (!$r) { reject('not_found'); }
        if (strlen($r['document'])>786432) { reject('corrupt_document'); }
        try {$doc=json_decode($r['document'],true,32,JSON_THROW_ON_ERROR);}catch(\JsonException $e){reject('corrupt_document');}
        if (!is_array($doc) || !is_string($doc['document_hash']??null)) { reject('corrupt_document'); }
        $hash=$doc['document_hash'];unset($doc['document_hash']);
        if (!hash_equals($r['document_hash'],$hash) || !hash_equals(digest($doc),$hash) || ($doc['proposal_id']??null)!==$id
            || ($doc['owner_id']??null)!==$owner || ($doc['version']??null)!==$version) { reject('corrupt_document'); }
        $doc['document_hash']=$hash;return ['proposal_id'=>$id,'version'=>$version,'document'=>$doc];
    }
    private function quota(int $owner,int $bytes,bool $new,int $version): void {
        $p=$this->prefix;
        $heads=(int)$this->row("SELECT COUNT(*) AS n FROM {$p}proposals WHERE owner_id=%d",[$owner])['n'];
        $requests=(int)$this->row("SELECT COUNT(*) AS n FROM {$p}requests WHERE owner_id=%d",[$owner])['n'];
        $usage=$this->row("SELECT COUNT(*) AS n,COALESCE(SUM(OCTET_LENGTH(document)),0) AS bytes FROM {$p}revisions WHERE owner_id=%d",[$owner]);
        if ($heads+(int)$new>16 || $requests+1>128 || (int)$usage['n']+1>64 || $version>16 || $bytes>786432
            || (int)$usage['bytes']+$bytes+($requests+1)*256+($heads+(int)$new)*128>8388608) { reject('quota'); }
    }
    private function save(array $request,?int $id,?int $expected): array {
        $actor=wordpress_actor();$request=normalize_request($request);$owner=$actor['id'];$p=$this->prefix;
        $hash=digest(['kind'=>$id===null?'create':'update','owner_id'=>$owner,'proposal_id'=>$id,'expected_version'=>$expected,'request'=>$request]);
        return $this->transaction($owner,function()use($actor,$request,$id,$expected,$owner,$p,$hash){
            $r=$this->row("SELECT request_hash,proposal_id,version FROM {$p}requests WHERE owner_id=%d AND request_id=%s",[$owner,$request['request_id']]);
            if ($r) {
                if (!hash_equals($r['request_hash'],$hash)) { reject('request_reused'); }
                return $this->document($owner,(int)$r['proposal_id'],(int)$r['version']);
            }
            $version=1;
            if ($id!==null) {
                $current=$this->head($owner,$id);if($current!==$expected){reject('version_conflict');}
                $old=$this->document($owner,$id,$current)['document'];
                foreach(['source_id','locale','operation'] as $key){if($old[$key]!==$request[$key]){reject('identity_change');}}
                $version=$current+1;
            }
            $projection=wordpress_projection($request['source_id']);
            $doc=prepare($actor,$projection['source'],$projection['terms'],$request);
            $doc['version']=$version;$doc['proposal_id']=$id??PHP_INT_MAX;unset($doc['document_hash']);$doc['document_hash']=digest($doc);
            $json=json_encode($doc,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $this->quota($owner,strlen($json),$id===null,$version);
            if ($id===null) {
                $this->query("INSERT INTO {$p}proposals(owner_id,version) VALUES (%d,1)",[$owner]);$id=(int)$this->db->insert_id;
                $doc['proposal_id']=$id;unset($doc['document_hash']);$doc['document_hash']=digest($doc);
                $json=json_encode($doc,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            } else {
                if ($this->query("UPDATE {$p}proposals SET version=%d WHERE id=%d AND owner_id=%d AND version=%d",[$version,$id,$owner,$expected])!==1) { reject('version_conflict'); }
            }
            $this->query("INSERT INTO {$p}revisions(proposal_id,owner_id,version,document,document_hash) VALUES (%d,%d,%d,%s,%s)",[$id,$owner,$version,$json,$doc['document_hash']]);
            $this->query("INSERT INTO {$p}requests(owner_id,request_id,request_hash,proposal_id,version) VALUES (%d,%s,%s,%d,%d)",[$owner,$request['request_id'],$hash,$id,$version]);
            return $this->document($owner,$id,$version);
        });
    }
    public function create(array $request): array { return $this->save($request,null,null); }
    public function update(int $id,int $version,array $request): array {
        wordpress_actor();if(!positive($id)||!positive($version)){reject('input');}return $this->save($request,$id,$version);
    }
    public function get(int $id,?int $version=null): array {
        $actor=wordpress_actor();if(!positive($id)||($version!==null&&!positive($version))){reject('input');}
        return $this->transaction($actor['id'],function()use($actor,$id,$version){$head=$this->head($actor['id'],$id);return $this->document($actor['id'],$id,$version??$head);});
    }
    public function listing(int $after=0,int $limit=10): array {
        $actor=wordpress_actor();if($after<0||$limit<1||$limit>20){reject('input');}
        return $this->transaction($actor['id'],function()use($actor,$after,$limit){
            $rows=$this->query("SELECT id,version FROM {$this->prefix}proposals WHERE owner_id=%d AND id>%d ORDER BY id LIMIT %d",[$actor['id'],$after,$limit+1],true);
            $more=count($rows)>$limit;$rows=array_slice($rows,0,$limit);
            return ['items'=>array_map(fn($r)=>['proposal_id'=>(int)$r['id'],'version'=>(int)$r['version']],$rows),'next_after'=>$more?(int)$rows[count($rows)-1]['id']:null];
        });
    }
}
