<?php
/** Pinned core wpdb/mysqli fence. Never reconnect or replay SQL inside an owned transaction. */
namespace AlexCuadra\ProjectClassificationProposalLab;
require_once __DIR__.'/wordpress-source.php';

function fenced_transaction($db, callable $work) {
    wordpress_lab_guard();
    if ($db!==($GLOBALS['wpdb']??null) || get_class($db)!=='wpdb' || get_bloginfo('version')!=='7.1.3') { reject('connection_profile'); }
    $property=new \ReflectionProperty(\wpdb::class,'dbh');$handle=$property->getValue($db);
    if (!$handle instanceof \mysqli) { reject('connection_profile'); }
    $report=(new \mysqli_driver)->report_mode;$started=false;$committed=false;$committing=false;$guard=null;
    $connection=null;
    $state=static function()use($handle){
        \mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        try{
            $result=\mysqli_query($handle,'SELECT CONNECTION_ID(),@@in_transaction');$row=\mysqli_fetch_row($result);\mysqli_free_result($result);
        }catch(\Error $e){reject('connection_lost');}
        if(!$row){reject('connection_lost');}return [(int)$row[0],(int)$row[1]];
    };
    $guard=static function($sql)use($db,$property,$handle,$state,&$connection,&$guard){
        global $wp_filter;
        \mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        $entries=$wp_filter['query']->callbacks[PHP_INT_MAX]??[];$last=end($entries);
        if(!$last||$last['function']!==$guard){reject('connection_guard_changed');}
        if($db!==($GLOBALS['wpdb']??null)||$property->getValue($db)!==$handle){reject('connection_lost');}
        [$id,$active]=$state();if($id!==$connection||$active!==1){reject('connection_lost');}
        // Disallow explicit/implicit commit, SET and DDL from callbacks in this scope.
        if(!preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE|REPLACE|SHOW|DESCRIBE|EXPLAIN)\b/i',$sql)){reject('transaction_control');}
        return $sql;
    };
    try{
        [$connection,$active]=$state();if($active!==0){reject('outer_transaction');}
        \mysqli_query($handle,'START TRANSACTION');$started=true;
        add_filter('query',$guard,PHP_INT_MAX,1);
        $value=$work();$guard('SELECT 1');
        $committing=true;
        if(\mysqli_query($handle,'COMMIT')!==true){reject('commit_uncertain');}
        $committed=true;return $value;
    }catch(\mysqli_sql_exception $e){
        if(in_array($e->getCode(),[2006,2013,2055],true)){reject($committing?'commit_uncertain':'connection_lost');}
        reject('storage_failure');
    }finally{
        remove_filter('query',$guard,PHP_INT_MAX);
        try{
            if($started&&!$committed){
                \mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
                try{\mysqli_query($handle,'ROLLBACK');}catch(\Throwable $ignored){}
            }
        }finally{\mysqli_report($report);}
    }
}
