<?php
namespace AlexCuadra\EditorialSourceExperiment;
function operating_storage_definitions($db){
    $reviews=$db->prefix.'ac_exp_operating_reviews';$approvals=$db->prefix.'ac_exp_operating_approvals';$requests=$db->prefix.'ac_exp_operating_requests';$apps=$db->prefix.'ac_exp_operating_apps';
    return (new MariaDbOperation($db))->definitions()+[
        'reviews'=>"CREATE TABLE $reviews (review_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,owner_id BIGINT UNSIGNED NOT NULL,proposal_id BIGINT UNSIGNED NOT NULL,document LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,review_hash VARBINARY(64) NOT NULL,KEY owner_id(owner_id)) ENGINE=InnoDB",
        'approvals'=>"CREATE TABLE $approvals (review_id BIGINT UNSIGNED PRIMARY KEY,actor_id BIGINT UNSIGNED NOT NULL,document LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,consumed_event VARBINARY(64) NULL) ENGINE=InnoDB",
        'requests'=>"CREATE TABLE $requests (owner_id BIGINT UNSIGNED NOT NULL,request_id VARBINARY(32) NOT NULL,request_hash VARBINARY(64) NOT NULL,response LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,PRIMARY KEY(owner_id,request_id)) ENGINE=InnoDB",
        'apps'=>"CREATE TABLE $apps (proposal_id BIGINT UNSIGNED PRIMARY KEY,owner_id BIGINT UNSIGNED NOT NULL,review_id BIGINT UNSIGNED NOT NULL,event_id VARBINARY(64) NOT NULL,source_after VARBINARY(64) NOT NULL,reserved_bytes INT UNSIGNED NOT NULL,reserved_reviews INT UNSIGNED NOT NULL,reserved_requests INT UNSIGNED NOT NULL,recovery_review BIGINT UNSIGNED NULL,recovery_event VARBINARY(64) NULL,KEY owner_id(owner_id)) ENGINE=InnoDB",
    ];
}
function migration_table_name($db,$name){return $db->prefix.(str_starts_with($name,'source_')?'ac_exp_'.$name:'ac_exp_operating_'.$name);}
function migration_table_schema($db,$journal,$name){if(str_starts_with($name,'source_')){$journal->schema(migration_table_name($db,$name));}else{operating_storage_schema($db,$name);}}
function operating_storage_marker($db,$phase){return ['format'=>'operating-storage-v2','phase'=>$phase,'schema_hash'=>hash('sha256',json_encode(operating_storage_definitions($db),JSON_THROW_ON_ERROR))];}
function require_operating_storage_ready($db){
    $value=$db->get_var("SELECT option_value FROM {$db->options} WHERE option_name='ac_exp_operating_storage'");
    if($db->last_error||$value!==json_encode(operating_storage_marker($db,'ready'),JSON_THROW_ON_ERROR)){throw new \RuntimeException('contract_storage_not_ready');}
}
/** Only CLI operator. DDL is resumable, never described as transactional. */
function migrate_operating_storage($db,MariaDbOperation $journal,?callable $checkpoint=null){
    return with_storage_barrier($db,function()use($db,$journal,$checkpoint){return migrate_locked_operating_storage($db,$journal,$checkpoint);});
}
function migrate_locked_operating_storage($db,MariaDbOperation $journal,?callable $checkpoint=null){
    local_wp_guard();
    if(!defined('WP_CLI')||!WP_CLI||!current_user_can('manage_options')){throw new \RuntimeException('contract_operator_required');}
    if($db!==($GLOBALS['wpdb']??null)||get_class($db)!=='wpdb'||!preg_match('/^[a-zA-Z0-9_]+$/D',$db->prefix)){throw new \RuntimeException('contract_dependencies');}
    $handle=(new \ReflectionProperty(\wpdb::class,'dbh'))->getValue($db);$mode=(new \mysqli_driver)->report_mode;$locked=false;
    $lock='ac_op_migrate_'.substr(hash('sha256',DB_NAME.'|'.$db->prefix),0,32);
    $query=static function($sql)use($handle,$db){
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        if((new \ReflectionProperty(\wpdb::class,'dbh'))->getValue($db)!==$handle){throw new \RuntimeException('contract_migration_connection_lost');}
        return mysqli_query($handle,$sql);
    };
    $scalar=static function($sql)use($query){$r=$query($sql);$row=mysqli_fetch_row($r);mysqli_free_result($r);return $row[0]??null;};
    try{
        if((int)$scalar('SELECT @@in_transaction')!==0){throw new \RuntimeException('contract_outer_transaction');}
        if((string)$scalar("SELECT GET_LOCK('$lock',0)")!=='1'){throw new \RuntimeException('contract_migration_busy');}$locked=true;
        $value=$scalar("SELECT option_value FROM {$db->options} WHERE option_name='ac_exp_operating_storage'");
        $preparing=json_encode(operating_storage_marker($db,'preparing'),JSON_THROW_ON_ERROR);$ready=json_encode(operating_storage_marker($db,'ready'),JSON_THROW_ON_ERROR);
        if($value!==null&&$value!==$preparing&&$value!==$ready){throw new \RuntimeException('contract_migration_marker_changed');}
        $definitions=operating_storage_definitions($db);$exists=[];
        // Validate every existing table before any DDL or marker write.
        foreach($definitions as $name=>$ddl){$table=migration_table_name($db,$name);
            $engine=$scalar($db->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));
            $exists[$name]=$engine!==null;
            if($engine!==null){migration_table_schema($db,$journal,$name);if($engine!=='InnoDB'){throw new \RuntimeException('contract_schema_changed');}
                if($value!==$ready&&(string)$scalar("SELECT COUNT(*) FROM $table")!=='0'){throw new \RuntimeException('contract_migration_untracked_data');}}
            elseif($value===$ready){throw new \RuntimeException('contract_schema_changed');}
        }
        if($value===$ready){return operating_storage_marker($db,'ready');}
        $query($db->prepare("INSERT INTO {$db->options} (option_name,option_value,autoload) VALUES ('ac_exp_operating_storage',%s,'off') ON DUPLICATE KEY UPDATE option_value=VALUES(option_value),autoload='off'",$preparing));
        foreach($definitions as $name=>$ddl){if(!$exists[$name]){$query($ddl);}migration_table_schema($db,$journal,$name);if($checkpoint!==null){$checkpoint($name);}}
        operating_storage_schema($db);$journal->schema();
        $query($db->prepare("UPDATE {$db->options} SET option_value=%s WHERE option_name='ac_exp_operating_storage' AND option_value=%s",$ready,$preparing));
        if(mysqli_affected_rows($handle)!==1){throw new \RuntimeException('contract_migration_marker_changed');}
        return operating_storage_marker($db,'ready');
    }finally{
        try{if($locked){try{$query("DO RELEASE_LOCK('$lock')");}catch(\Throwable $ignored){}}}finally{mysqli_report($mode);wp_cache_delete('ac_exp_operating_storage','options');wp_cache_delete('notoptions','options');wp_cache_delete('alloptions','options');}
    }
}
