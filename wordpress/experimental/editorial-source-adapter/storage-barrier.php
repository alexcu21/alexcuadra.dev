<?php
namespace AlexCuadra\EditorialSourceExperiment;
function storage_barrier_name($db){return 'ac_storage_'.substr(hash('sha256',DB_NAME.'|'.$db->prefix),0,32);}
function with_storage_barrier($db,callable $fn){
    local_wp_guard();$handle=(new \ReflectionProperty(\wpdb::class,'dbh'))->getValue($db);$mode=(new \mysqli_driver)->report_mode;$locked=false;$name=storage_barrier_name($db);
    try{
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$r=mysqli_query($handle,"SELECT GET_LOCK('$name',0)");$row=mysqli_fetch_row($r);mysqli_free_result($r);
        if(!$row||(string)$row[0]!=='1'){throw new \RuntimeException('contract_storage_busy');}$locked=true;
        $result=$fn();
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$r=mysqli_query($handle,'SELECT CONNECTION_ID()');mysqli_free_result($r);
        if((new \ReflectionProperty(\wpdb::class,'dbh'))->getValue($db)!==$handle){throw new \RuntimeException('source_transaction_lost');}
        return $result;
    }finally{
        try{if($locked){try{mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);mysqli_query($handle,"DO RELEASE_LOCK('$name')");}catch(\Throwable $ignored){}}}finally{mysqli_report($mode);}
    }
}
