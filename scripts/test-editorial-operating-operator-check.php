<?php
/** Synthetic control-flow checks only; no WordPress/database integration claim. */
namespace AlexCuadra\EditorialSourceExperiment {
    const OPERATING_VERSION='0.1.0-rc.3';
    const DEPLOY_INSTALL_SHA256='synthetic';
    class OperatingContract {const MAX_BYTES=8388608;}
    $actor=1;$mode='';$observations=0;$readonly=0;
    $wpdb=new class {
        public $prefix='wp_';public $last_error='';
        function get_var($sql){
            if(!preg_match('/^SELECT COUNT\(\*\) FROM wp_ac_exp_(\w+)$/D',$sql,$m)){throw new \RuntimeException('unexpected query');}
            return in_array($m[1],['operating_reviews','operating_requests'],true)?($GLOBALS['mode']==='count'?2:1):0;
        }
    };
    function deploy_install_require($ok,$code){if(!$ok){throw new \RuntimeException($code);}}
    function deploy_scope_context($empty){deploy_install_require(!$empty,'expected nonempty context');}
    function owned_transaction($db,$fn,$ro){deploy_install_require($ro,'expected READ ONLY audit');$GLOBALS['readonly']++;return $fn();}
    function audit_operating_owner($db,$owner,$locking){
        deploy_install_require($owner===2&&!$locking,'expected nonlocking owner audit');
        return ['owner_id'=>2,'reviews'=>1,'requests'=>1,'applications'=>0,'approvals'=>0,'events'=>0];
    }
    function deploy_scope_snapshot($config){$n=++$GLOBALS['observations'];return ['postmeta'=>$GLOBALS['mode']==='drift'&&$n>1?'changed':'same'];}
    function deploy_scope_caps(){return ['caps'=>'same'];}
    function deploy_scope_caps_hash($v){return $v;}
    function deploy_scope_permissions(){return ['allowed'=>6,'denied'=>12];}
    function get_current_user_id(){return $GLOBALS['actor'];}
    function wp_set_current_user($id){$GLOBALS['actor']=$id;}
    function add_filter($tag,$fn,$priority,$argc){$GLOBALS['read_filter']=$fn;}
    function remove_filter($tag,$fn,$priority){unset($GLOBALS['read_filter']);}
}
namespace AlexCuadra\EditorialOperatingPackage {
    function controller(){return new class {
        function usage($owner){return ['reviews'=>1,'requests'=>1,'bytes'=>6000];}
        function inspect($id,$locale){
            if(!isset($GLOBALS['read_filter'])){throw new \RuntimeException('missing read guard');}
            $d=['owner_id'=>2,'proposal_id'=>227,'project_id'=>146,'locale'=>'en','operation'=>'apply'];
            if($GLOBALS['mode']==='different'&&$GLOBALS['actor']===2){$d['extra']='unexpected';}
            return ['review_hash'=>$GLOBALS['mode']==='hash'?'wrong':'ecaf5b7835e98fb157e70e852aa718322e8df5f6d82c807040b1489f673e9738','document'=>$d];
        }
    };}
}
namespace AlexCuadra\EditorialSourceExperiment {
    require __DIR__.'/../wordpress/operations/editorial-operating-operator-check.php';
    $checks=0;
    foreach([''=>'','count'=>'OPERATOR_UNEXPECTED_STORAGE_COUNTS','hash'=>'OPERATOR_REVIEW_HASH','different'=>'OPERATOR_INSPECTION_MISMATCH','drift'=>'OPERATOR_OBSERVATION_CHANGED'] as $case=>$expected){
        $GLOBALS['mode']=$case;$GLOBALS['observations']=0;$GLOBALS['actor']=1;$GLOBALS['readonly']=0;
        try{
            $result=deploy_operator_check('synthetic-config');
            deploy_install_require($expected==='','expected rejection');
            deploy_install_require($result['operatorCliInspectionMatchesOwner']&&$GLOBALS['readonly']===2,'success invariants');
            deploy_install_require(!str_contains(json_encode($result),'document'),'private document leaked');
        }catch(\RuntimeException $e){deploy_install_require($expected!==''&&$e->getMessage()===$expected,'unexpected rejection: '.$e->getMessage());}
        deploy_install_require($GLOBALS['actor']===1&&!isset($GLOBALS['read_filter']),'actor/filter cleanup');$checks++;
    }
    foreach(['SELECT 1'=>true,'SHOW TABLES'=>true,'UPDATE wp_posts SET x=1'=>false,'INSERT INTO x VALUES (1)'=>false,'DELETE FROM x'=>false] as $sql=>$allow){
        try{deploy_operator_read_queries($sql);deploy_install_require($allow,'write admitted');}
        catch(\RuntimeException $e){deploy_install_require(!$allow&&$e->getMessage()==='OPERATOR_NON_READ_QUERY','unexpected guard rejection');}
        $checks++;
    }
    echo json_encode(['syntheticChecks'=>$checks,'passed'=>true,'liveWordPressTested'=>false]).PHP_EOL;
}
