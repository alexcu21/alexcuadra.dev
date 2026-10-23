<?php
/** Explicit registration only; no hooks or abilities are installed on load. */
namespace AlexCuadra\EditorialSourceExperiment;
use AlexCuadra\EditorialProposals as P;
function operating_ability_specs(){
    $id=['type'=>'integer','minimum'=>1];$locale=['type'=>'string','enum'=>['en','es']];$hash=['type'=>'string','pattern'=>'^[a-f0-9]{64}$'];$key=['type'=>'string','pattern'=>'^[a-f0-9]{32}$'];
    $object=static function($p){return ['type'=>'object','additionalProperties'=>false,'properties'=>$p,'required'=>array_keys($p)];};
    $format=static function($v){return ['type'=>'string','enum'=>[$v]];};
    $review=$object(['format'=>$format('operating-review-receipt-v1'),'review_id'=>$id,'proposal_id'=>$id,'project_id'=>$id,'locale'=>$locale,'review_hash'=>$hash,'expires_at'=>$id]);
    $application=$object(['format'=>$format('operating-application-receipt-v1'),'event_id'=>$hash,'review_id'=>$id,'proposal_id'=>$id,'project_id'=>$id,'locale'=>$locale,'content_state'=>$format('applied'),'source_version'=>$hash,'effect_state_at_commit'=>$format('pending')]);
    $recovery=$application;$recovery['properties']['format']=$format('operating-recovery-receipt-v1');$recovery['properties']['content_state']=$format('recovered');$recovery['properties']['application_event']=$hash;$recovery['required'][]='application_event';
    $meta=$object(['present'=>['type'=>'boolean'],'value'=>['type'=>['string','null']]]);
    $fields=['type'=>'object','additionalProperties'=>false,'minProperties'=>1,'properties'=>['body_html'=>['type'=>'string','maxLength'=>40000],'tagline'=>$object(['project_tagline'=>$meta,'_project_tagline'=>$meta])]];
    $base=['format'=>$format('operating-review-v1'),'owner_id'=>$id,'proposal_id'=>$id,'project_id'=>$id,'locale'=>$locale,'source_version'=>$hash,'schema_version'=>$hash,'before'=>$fields,'after'=>$fields,'created_at'=>$id,'expires_at'=>$id];
    $inspect=$object(['format'=>$format('operating-review-inspection-v1'),'review_hash'=>$hash,'document'=>['oneOf'=>[$object($base+['operation'=>$format('apply'),'proposal_version'=>$hash,'base_version'=>$hash]),$object($base+['operation'=>$format('recover'),'application_event'=>$hash,'application_review_hash'=>$hash])]]]);
    $applyInput=['review_id'=>$id,'locale'=>$locale,'expected_review_hash'=>$hash,'request_id'=>$key];
    return [
        'prepare-project-application'=>['method'=>'prepare','scope'=>'apply','input'=>$object(['proposal_id'=>$id,'locale'=>$locale,'expected_proposal_version'=>$hash,'expected_base_version'=>$hash,'request_id'=>$key]),'output'=>$review],
        'inspect-project-review'=>['method'=>'inspect','scope'=>'read','input'=>$object(['review_id'=>$id,'locale'=>$locale]),'output'=>$inspect],
        'apply-project-review'=>['method'=>'apply','scope'=>'apply','input'=>$object($applyInput),'output'=>$application],
        'prepare-project-recovery'=>['method'=>'prepareRecovery','scope'=>'recover','input'=>$object(['proposal_id'=>$id,'locale'=>$locale,'expected_source_version'=>$hash,'request_id'=>$key]),'output'=>$review],
        'recover-project-review'=>['method'=>'recover','scope'=>'recover','input'=>$object($applyInput),'output'=>$recovery],
        'get-project-effect-state'=>['method'=>'effectStatus','scope'=>'read','input'=>$object(['proposal_id'=>$id,'locale'=>$locale]),'output'=>$object(['format'=>$format('operating-effect-status-v1'),'event_id'=>$hash,'proposal_id'=>$id,'locale'=>$locale,'effect_state'=>['type'=>'string','enum'=>['pending','running','retry','requested','attention']],'attempts'=>['type'=>'integer','minimum'=>0,'maximum'=>8]])],
    ];
}
function operating_ability_permission($scope){
    try{local_wp_guard();$ok=!is_wp_error(P\permission())&&($scope==='read'?(current_user_can(OperatingContract::APPLY)||current_user_can(OperatingContract::RECOVER)):current_user_can($scope==='apply'?OperatingContract::APPLY:OperatingContract::RECOVER));}
    catch(\Throwable $e){$ok=false;}
    return $ok?true:new \WP_Error('editorial_forbidden','Editorial permission required.',['status'=>403]);
}
function operating_ability_error(\Throwable $e){
    $code=$e->getMessage();$known=['contract_not_found'=>404,'contract_forbidden'=>403,'contract_invalid_input'=>400,'contract_storage_busy'=>503,'contract_owner_quota'=>429,'contract_storage_not_ready'=>503,'contract_review_expired'=>409,'contract_source_changed'=>409,'contract_proposal_changed'=>409,'contract_review_changed'=>409,'contract_request_reused'=>409,'contract_approval_required'=>409,'contract_approval_consumed'=>409,'contract_application_changed'=>409,'contract_operation_mismatch'=>409,'contract_recovery_review_exists'=>409,'source_changed'=>409];
    if(!isset($known[$code])){return new \WP_Error('editorial_operation_failed','Editorial operation could not be completed.',['status'=>500]);}
    return new \WP_Error($code,'Editorial operation rejected.',['status'=>$known[$code]]);
}
function register_operating_abilities(callable $factory){
    local_wp_guard();
    foreach(operating_ability_specs() as $name=>$spec){
        wp_register_ability('alexcuadra/'.$name,['label'=>$name,'description'=>'Owned editorial review or approved public application/recovery. Approval is available only to an independent CLI operator.','category'=>'alexcuadra-operating',
            'permission_callback'=>static function()use($spec){return operating_ability_permission($spec['scope']);},'input_schema'=>$spec['input'],'output_schema'=>$spec['output'],
            'execute_callback'=>static function($input)use($factory,$spec){try{$c=$factory();$m=$spec['method'];if($m==='inspect'){return $c->inspect($input['review_id'],$input['locale']);}if($m==='effectStatus'){return $c->effectStatus($input['proposal_id'],$input['locale']);}return $c->$m($input);}catch(\Throwable $e){return operating_ability_error($e);}},
            'meta'=>['public'=>false,'show_in_rest'=>false,'mcp'=>['public'=>true,'type'=>'tool'],'annotations'=>['readonly'=>$spec['scope']==='read','destructive'=>in_array($spec['method'],['apply','recover'],true),'idempotent'=>true]]]);
    }
}
