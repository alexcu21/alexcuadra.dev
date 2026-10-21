<?php
use AlexCuadra\EditorialChanges as Changes;
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { throw new RuntimeException('ISOLATED_CHANGES_HTTP_REQUIRED'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
global $wpdb;
$endpoint='http://wordpress/?rest_route=/mcp/mcp-adapter-default-server';
$plugin='alexcuadra-editorial-changes/alexcuadra-editorial-changes.php';
$table=$wpdb->prefix.'ac_change_http_races';
$cases=[]; $users=[]; $credentials=[]; $sessions=[]; $raceKeys=[]; $sequence=0; $lastDiagnostic=[];
$report=['environment'=>'isolated-change-proposals-http','wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'polylang'=>POLYLANG_VERSION,
    'httpTransportVerified'=>false,'independentHttpConcurrencyVerified'=>false,'sharedCmsModified'=>false,'sharedPermissionsModified'=>false,'hostPortsPublished'=>false,'emailSent'=>false];
$check=static function($name,$ok)use(&$cases){if(!$ok){throw new RuntimeException('FAILED: '.$name);} $cases[]=$name;};
$headers=static function($auth=null,$session=null){
    $h=['Content-Type'=>'application/json','Accept'=>'application/json, text/event-stream'];
    if($auth!==null){$h['Authorization']=$auth;}
    if($session!==null){$h['Mcp-Session-Id']=$session;$h['MCP-Protocol-Version']='2025-11-25';}
    return $h;
};
$request=static function($payload,$auth=null,$session=null,$method='POST',$url=null)use($endpoint,$headers,&$lastDiagnostic){
    $r=wp_remote_request($url??$endpoint,['method'=>$method,'headers'=>$headers($auth,$session),'body'=>$payload===null?null:wp_json_encode($payload),'timeout'=>25,'redirection'=>0]);
    if(is_wp_error($r)){throw new RuntimeException('HTTP_LAB_NETWORK_FAILED');}
    $json=json_decode(wp_remote_retrieve_body($r),true);$code=wp_remote_retrieve_response_code($r);
    $lastDiagnostic=['httpStatus'=>$code,'rpcErrorCode'=>$json['error']['code']??null];
    return ['status'=>$code,'json'=>$json,'session'=>wp_remote_retrieve_header($r,'mcp-session-id')];
};
$rpc=static function($method,$params,$auth=null,$session=null)use($request,&$sequence){return $request(['jsonrpc'=>'2.0','id'=>++$sequence,'method'=>$method,'params'=>$params],$auth,$session);};
$data=static function($response){
    $r=$response['json']['result']??[];if(isset($r['structuredContent'])){return $r['structuredContent'];}
    foreach($r['content']??[] as $item){if(($item['type']??null)==='text'){$v=json_decode($item['text'],true);if(is_array($v)){return $v;}}}
    return null;
};
$appError=static function($response,$needle)use($data){
    $d=$data($response);
    $failed=isset($response['json']['error'])||($response['json']['result']['isError']??false)===true||($d['success']??null)===false;
    return $failed && str_contains(wp_json_encode($response['json']),$needle);
};
$tool=static function($name,$arguments,$auth,$session)use($rpc){return $rpc('tools/call',['name'=>$name,'arguments'=>$arguments],$auth,$session);};
$execute=static function($op,$parameters,$auth,$session)use($tool){
    if(in_array($op,['create','update','restore'],true)){$parameters['request_id']=$parameters['request_id']??bin2hex(random_bytes(16));}
    $name=['history'=>'list-project-change-revisions','revision'=>'get-project-change-revision','restore'=>'restore-project-change-revision'][$op]??$op.'-project-change';
    return $tool('mcp-adapter-execute-ability',['ability_name'=>'alexcuadra/'.$name,'parameters'=>$parameters],$auth,$session);
};
$initialize=static function($auth,$owner)use($rpc,$request,&$sessions){
    $r=$rpc('initialize',['protocolVersion'=>'2025-11-25','capabilities'=>new stdClass(),'clientInfo'=>['name'=>'synthetic-change-http','version'=>'0.1.0']],$auth);
    $s=$r['session'];
    if($r['status']!==200||!is_string($s)||!preg_match('/^[a-f0-9-]{36}$/i',$s)){throw new RuntimeException('HTTP_INITIALIZE_FAILED');}
    $sessions[]=['id'=>$s,'owner'=>$owner];
    $n=$request(['jsonrpc'=>'2.0','method'=>'notifications/initialized'],$auth,$s);
    if(!in_array($n['status'],[200,202],true)){throw new RuntimeException('HTTP_NOTIFICATION_FAILED');}
    return $s;
};
$credential=static function($id)use(&$credentials){
    $r=WP_Application_Passwords::create_new_application_password($id,['name'=>'synthetic-change-http']);
    if(is_wp_error($r)){throw new RuntimeException('APPLICATION_PASSWORD_SETUP_FAILED');}
    $credentials[]=['user'=>$id,'uuid'=>$r[1]['uuid']];
    return 'Basic '.base64_encode(get_userdata($id)->user_login.':'.$r[0]);
};
try {
    $wpdb->query("CREATE TABLE IF NOT EXISTS $table (race_key VARCHAR(32) NOT NULL,worker_id BIGINT NOT NULL,arrived DOUBLE NOT NULL,released DOUBLE NOT NULL,PRIMARY KEY(race_key,worker_id)) ENGINE=InnoDB");
    if($wpdb->last_error){throw new RuntimeException('RACE_TABLE_SETUP_FAILED');}
    PLL()->options->set('post_types',['project']);PLL()->options->save_all();
    foreach([['en','en_US','English'],['es','es_ES','Español']]as $l){
        if(!in_array($l[0],pll_languages_list(),true)){PLL()->model->add_language(['slug'=>$l[0],'locale'=>$l[1],'name'=>$l[2],'rtl'=>false,'term_group'=>$l[0]==='en'?0:1]);}
    }
    PLL()->options->set('default_lang','en');PLL()->options->save_all();
    $make=static function($locale,$state='publish'){
        $id=wp_insert_post(['post_type'=>'project','post_status'=>$state,'post_title'=>'Synthetic HTTP source','post_author'=>1,'post_content'=>'SOURCE_HTTP_BODY_NOT_RETURNED'],true);
        if(is_wp_error($id)){throw new RuntimeException('SOURCE_SETUP_FAILED');}
        pll_set_post_language($id,$locale);update_post_meta($id,'project_tagline','SOURCE_HTTP_TAGLINE_NOT_RETURNED');
        update_post_meta($id,'_project_tagline',Changes\TAGLINE_KEY);update_post_meta($id,'private_contact','PRIVATE_HTTP_META_NOT_RETURNED');
        return $id;
    };
    $en=$make('en');$es=$make('es');$draft=$make('en','draft');pll_save_post_translations(['en'=>$en,'es'=>$es]);
    $ids=implode(',',[$en,$es,$draft]);
    $snapshot=static function()use($wpdb,$ids){return hash('sha256',serialize([
        $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID IN ($ids) ORDER BY ID",ARRAY_A),
        $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id IN ($ids) ORDER BY meta_id",ARRAY_A),
        $wpdb->get_results("SELECT * FROM $wpdb->term_relationships WHERE object_id IN ($ids) ORDER BY object_id,term_taxonomy_id",ARRAY_A)]));};
    $before=$snapshot();
    foreach(['owner','other']as $name){
        $id=wp_create_user('changehttp'.bin2hex(random_bytes(6)),wp_generate_password(32));
        if(is_wp_error($id)){throw new RuntimeException('USER_SETUP_FAILED');}
        $u=new WP_User($id);$u->set_role('subscriber');$u->add_cap('alexcuadra_read_public_projects');$users[$name]=$u;
    }
    $auth=$credential($users['owner']->ID);$otherAuth=$credential($users['other']->ID);
    $unauth=$rpc('initialize',['protocolVersion'=>'2025-11-25','capabilities'=>new stdClass(),'clientInfo'=>['name'=>'synthetic','version'=>'1']]);
    $check('anonymous_transport_denied',in_array($unauth['status'],[401,403],true));
    $a=$initialize($auth,'owner');$b=$initialize($auth,'owner');$other=$initialize($otherAuth,'other');
    $check('two_owner_sessions_and_other_user_initialize',$a!==$b&&$a!==$other&&$b!==$other);
    $input=['project_id'=>$en,'locale'=>'en','request_id'=>bin2hex(random_bytes(16)),'patch'=>['body_html'=>'<p>Proposed HTTP café \\ "quoted".</p>','tagline'=>'First proposal']];
    $check('authenticated_read_only_subscriber_cannot_prepare',$appError($execute('create',$input,$auth,$a),'forbidden'));
    foreach($users as $u){$u->add_cap(Changes\CAPABILITY);}
    $check('http_preparer_is_not_editor_or_administrator',!$users['owner']->has_cap('edit_pages')&&!$users['owner']->has_cap('publish_pages')&&!$users['owner']->has_cap('manage_options'));
    $listed=$data($tool('mcp-adapter-discover-abilities',new stdClass(),$auth,$a));
    $check('http_discovers_three_proposal_abilities',count(array_intersect(['alexcuadra/create-project-change','alexcuadra/get-project-change','alexcuadra/update-project-change'],array_column($listed['abilities']??[],'name')))===3);
    foreach(['create','get','update']as $op){
        $info=$data($tool('mcp-adapter-get-ability-info',['ability_name'=>'alexcuadra/'.$op.'-project-change'],$auth,$a));
        $check('http_schema_closed_'.$op,($info['input_schema']['additionalProperties']??null)===false);
    }
    $created=$data($execute('create',$input,$auth,$a));
    $check('http_creates_private_owned_proposal',($created['success']??false)&&$created['data']['state']==='draft'&&$created['data']['applied']===false);
    $proposal=$created['data'];$pid=$proposal['proposal_id'];
    $repeat=$data($execute('create',$input,$auth,$b));
    $check('http_create_retry_returns_same_proposal_and_version',($repeat['success']??false)&&$repeat['data']===$proposal);
    $reused=$input;$reused['patch']['tagline']='Reused key with another intent';
    $check('http_request_id_reuse_with_other_data_conflicts',$appError($execute('create',$reused,$auth,$a),'request_reused'));
    $missingKey=$tool('mcp-adapter-execute-ability',['ability_name'=>'alexcuadra/create-project-change','parameters'=>['project_id'=>$en,'locale'=>'en','patch'=>['tagline'=>'Missing request id']]],$auth,$a);
    $check('http_schema_requires_write_request_id',$appError($missingKey,'invalid input'));
    $check('http_returns_only_proposal_fields',array_keys($proposal)===['proposal_id','project_id','locale','version','base_version','patch','state','applied']);
    $check('http_never_returns_source_body_or_private_meta',!str_contains(wp_json_encode($created),'NOT_RETURNED'));
    $same=$data($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$b));
    $check('other_session_of_same_owner_reads_exact_patch',($same['success']??false)&&$same['data']['patch']===$proposal['patch']);
    $foreignSession=$rpc('tools/list',new stdClass(),$otherAuth,$a);
    $check('another_user_cannot_reuse_owner_session',isset($foreignSession['json']['error']));
    $check('another_user_cannot_read_proposal',$appError($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$otherAuth,$other),'not_found'));
    $check('another_user_cannot_update_proposal',$appError($execute('update',['proposal_id'=>$pid,'locale'=>'en','expected_version'=>$proposal['version'],'patch'=>['tagline'=>'Foreign overwrite']],$otherAuth,$other),'not_found'));
    $bad=$request(['title'=>'Unauthorized source edit'],$auth,null,'POST','http://wordpress/?rest_route=/wp/v2/project/'.$en);
    $check('ordinary_rest_source_edit_still_forbidden',$bad['status']===403&&($bad['json']['code']??null)==='rest_cannot_edit');
    $hidden=$request(null,null,null,'GET','http://wordpress/?rest_route=/wp/v2/'.Changes\TYPE.'/'.$pid);
    $check('proposal_has_no_public_rest_endpoint',$hidden['status']===404&&($hidden['json']['code']??null)==='rest_no_route');
    $updateInput=['proposal_id'=>$pid,'locale'=>'en','expected_version'=>$proposal['version'],'request_id'=>bin2hex(random_bytes(16)),'patch'=>['tagline'=>'Sequential update']];
    $updated=$data($execute('update',$updateInput,$auth,$b));
    $check('http_updates_proposal_with_fresh_version',($updated['success']??false)&&$updated['data']['version']!==$proposal['version']&&$updated['data']['patch']['tagline']==='Sequential update');
    $repeat=$data($execute('update',$updateInput,$auth,$a));
    $check('http_update_retry_returns_original_committed_version',($repeat['success']??false)&&$repeat['data']===$updated['data']);
    $check('http_rejects_stale_version',$appError($execute('update',['proposal_id'=>$pid,'locale'=>'en','expected_version'=>$proposal['version'],'patch'=>['tagline'=>'Stale overwrite']],$auth,$a),'proposal_changed'));
    $check('http_rejects_unknown_patch_fields',$appError($execute('create',['project_id'=>$en,'locale'=>'en','patch'=>['post_status'=>'publish']],$auth,$a),'invalid input'));
    $check('http_rejects_wrong_source_language',$appError($execute('create',['project_id'=>$en,'locale'=>'es','patch'=>['tagline'=>'Wrong language']],$auth,$a),'not_found'));
    $check('http_rejects_draft_source',$appError($execute('create',['project_id'=>$draft,'locale'=>'en','patch'=>['tagline'=>'Draft source']],$auth,$a),'not_found'));
    $spanish=$data($execute('create',['project_id'=>$es,'locale'=>'es','patch'=>['tagline'=>'Propuesta española']],$auth,$b));
    $check('http_spanish_proposal_uses_exact_language',($spanish['success']??false)&&$spanish['data']['locale']==='es');
    $historyNames=['alexcuadra/list-project-change-revisions','alexcuadra/get-project-change-revision','alexcuadra/restore-project-change-revision'];
    $check('http_discovers_three_history_abilities',count(array_intersect($historyNames,array_column($listed['abilities']??[],'name')))===3);
    foreach($historyNames as $index=>$abilityName){
        $info=$data($tool('mcp-adapter-get-ability-info',['ability_name'=>$abilityName],$auth,$a));
        $check('http_history_schema_closed_'.$index,($info['input_schema']['additionalProperties']??null)===false);
    }
    $historyInput=['proposal_id'=>$pid,'locale'=>'en','expected_version'=>$updated['data']['version'],'page'=>1,'per_page'=>1];
    $h=$data($execute('history',$historyInput,$auth,$a));
    $check('http_history_paginates_metadata_without_patch',($h['success']??false)&&$h['data']['total']===2&&$h['data']['next_page']===2&&!isset($h['data']['revisions'][0]['patch']));
    $h2=$data($execute('history',array_merge($historyInput,['page'=>2]),$auth,$b));
    $check('http_history_second_page_has_update_parent',($h2['success']??false)&&$h2['data']['revisions'][0]['parent_version']===$proposal['version']&&$h2['data']['next_page']===null);
    $revisionInput=['proposal_id'=>$pid,'locale'=>'en','revision_version'=>$proposal['version']];
    $r=$data($execute('revision',$revisionInput,$auth,$b));
    $check('http_old_revision_exact_snapshot',($r['success']??false)&&$r['data']['revision']['patch']===$proposal['patch']);
    foreach(['history'=>$historyInput,'revision'=>$revisionInput,'restore'=>$revisionInput+['expected_version'=>$updated['data']['version']]]as $op=>$parameters){
        $check('http_foreign_user_denied_'.$op,$appError($execute($op,$parameters,$otherAuth,$other),'not_found'));
    }
    // Distinct HTTP requests/sessions and PHP workers, held at their real CAS boundary.
    if(!extension_loaded('curl')){throw new RuntimeException('CURL_MULTI_REQUIRED');}
    $raceProofs=[];
    foreach(['first','second']as $round){
        $base=$data($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$a));
        $check('race_'.$round.'_base_read',($base['success']??false));
        $race=bin2hex(random_bytes(16));$raceKeys[]=$race;$requests=[];
        foreach([$a,$b]as $index=>$session){
            $isRestore=$round==='second'&&$index===1;
            $parameters=['proposal_id'=>$pid,'locale'=>'en','expected_version'=>$base['data']['version']];
            if($isRestore){$parameters['revision_version']=$proposal['version'];}else{$parameters['patch']=['tagline'=>'Race '.$round.' writer '.$index];}
            $parameters['request_id']=bin2hex(random_bytes(16));
            $args=['ability_name'=>'alexcuadra/'.($isRestore?'restore-project-change-revision':'update-project-change'),'parameters'=>$parameters];
            $h=$headers($auth,$session);$h['X-Ac-Changes-Race']=$race;
            $requests[$index]=['url'=>$endpoint,'type'=>'POST','headers'=>$h,'data'=>wp_json_encode(['jsonrpc'=>'2.0','id'=>++$sequence,'method'=>'tools/call','params'=>['name'=>'mcp-adapter-execute-ability','arguments'=>$args]])];
        }
        $responses=\WpOrg\Requests\Requests::request_multiple($requests,['timeout'=>25,'connect_timeout'=>5,'follow_redirects'=>false,'transport'=>new \WpOrg\Requests\Transport\Curl()]);
        $successes=[];$conflicts=0;
        foreach($responses as $index=>$response){
            if(!$response instanceof \WpOrg\Requests\Response){throw new RuntimeException('RACE_HTTP_NETWORK_FAILED');}
            $parsed=['status'=>$response->status_code,'json'=>json_decode($response->body,true)];$d=$data($parsed);
            if(($d['success']??false)===true){$successes[$index]=$d['data'];}
            elseif($appError($parsed,'proposal_changed')){$conflicts++;}
        }
        $rows=$wpdb->get_results($wpdb->prepare("SELECT worker_id,arrived,released FROM $table WHERE race_key=%s",$race),ARRAY_A);
        $workers=array_unique(array_column($rows,'worker_id'));
        $overlap=count($rows)===2&&count($workers)===2&&max(array_column($rows,'arrived'))<=min(array_column($rows,'released'));
        $check('race_'.$round.'_two_independent_workers_overlap',$overlap);
        $check('race_'.$round.'_one_success_one_application_conflict',count($successes)===1&&$conflicts===1);
        $winner=array_key_first($successes);$read=$data($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$b));
        $check('race_'.$round.'_winner_persisted_without_lost_update',($read['success']??false)&&$read['data']['version']===$successes[$winner]['version']&&$read['data']['patch']===$successes[$winner]['patch']);
        $check('race_'.$round.'_other_proposed_field_preserved',$read['data']['patch']['body_html']===$proposal['patch']['body_html']);
        $journal=$data($execute('history',['proposal_id'=>$pid,'locale'=>'en','expected_version'=>$read['data']['version'],'page'=>1,'per_page'=>10],$auth,$a));
        $entries=$journal['data']['revisions']??[];$last=end($entries);
        $check('race_'.$round.'_only_winner_appended_to_history',($journal['success']??false)&&count($entries)===($round==='first'?3:4)
            &&$last['version']===$read['data']['version']&&$last['parent_version']===$base['data']['version']);
        $raceProofs[]=['round'=>$round,'operations'=>$round==='first'?['update','update']:['update','restore'],'independentWorkers'=>count($workers),'overlapVerified'=>$overlap,'successfulWrites'=>count($successes),'applicationConflicts'=>$conflicts,'winner'=>$winner];
    }
    $report['raceProofs']=$raceProofs;$report['independentHttpConcurrencyVerified']=true;
    // Admission barrier is before the owner lock, observed by a separate autocommit connection.
    $parallel=static function($ops,$parameters,$credential,$pair,$label)use($endpoint,$headers,&$sequence,&$raceKeys,$data,$appError,$wpdb,$table,$check){
        $race=bin2hex(random_bytes(16));$raceKeys[]=$race;$requests=[];
        foreach($pair as $i=>$session){$h=$headers($credential,$session);$h['X-Ac-Changes-Race']=$race;
            $requests[$i]=['url'=>$endpoint,'type'=>'POST','headers'=>$h,'data'=>wp_json_encode(['jsonrpc'=>'2.0','id'=>++$sequence,'method'=>'tools/call',
                'params'=>['name'=>'mcp-adapter-execute-ability','arguments'=>['ability_name'=>'alexcuadra/'.$ops[$i].'-project-change','parameters'=>$parameters[$i]]]])];}
        $responses=\WpOrg\Requests\Requests::request_multiple($requests,['timeout'=>25,'connect_timeout'=>5,'follow_redirects'=>false,'transport'=>new \WpOrg\Requests\Transport\Curl()]);
        $success=[];$fail=[];
        foreach($responses as $i=>$response){if(!$response instanceof \WpOrg\Requests\Response){throw new RuntimeException('ADMISSION_HTTP_NETWORK_FAILED');}
            $parsed=['status'=>$response->status_code,'json'=>json_decode($response->body,true)];$d=$data($parsed);
            if(($d['success']??false)===true){$success[$i]=$d['data'];}else{
                $recognized=false;foreach(['request_reused','owner_quota','proposal_changed']as $marker){if($appError($parsed,$marker)){$fail[]=$marker;$recognized=true;break;}}
                if(!$recognized){throw new RuntimeException('UNEXPECTED_ADMISSION_ERROR');}
            }}
        $rows=$wpdb->get_results($wpdb->prepare("SELECT worker_id,arrived,released FROM $table WHERE race_key=%s",$race),ARRAY_A);
        $overlap=count($rows)===2&&count(array_unique(array_column($rows,'worker_id')))===2&&max(array_column($rows,'arrived'))<=min(array_column($rows,'released'));
        $check('admission_'.$label.'_independent_workers_overlap',$overlap);
        return [$success,$fail,['scenario'=>$label,'workers'=>count($rows),'overlapVerified'=>$overlap,'successfulResponses'=>count($success),'errors'=>$fail]];
    };
    $admissionProofs=[];
    $same=['project_id'=>$en,'locale'=>'en','request_id'=>bin2hex(random_bytes(16)),'patch'=>['tagline'=>'Identical parallel create']];
    $usage=Changes\owner_usage($users['owner']->ID);
    [$success,$fails,$proof]=$parallel(['create','create'],[$same,$same],$auth,[$a,$b],'duplicate_create');
    $check('http_parallel_identical_create_returns_two_equal_results',count($success)===2&&$fails===[]&&$success[0]===$success[1]);
    $after=Changes\owner_usage($users['owner']->ID);
    $check('http_parallel_identical_create_consumes_one_proposal_and_receipt',$after['proposals']===$usage['proposals']+1&&$after['requests']===$usage['requests']+1);
    $proof['committedProposals']=$after['proposals']-$usage['proposals'];$proof['committedReceipts']=$after['requests']-$usage['requests'];$admissionProofs[]=$proof;
    $dupe=$success[0];$sameUpdate=['proposal_id'=>$dupe['proposal_id'],'locale'=>'en','request_id'=>bin2hex(random_bytes(16)),
        'expected_version'=>$dupe['version'],'patch'=>['tagline'=>'Identical parallel update']];
    $usage=Changes\owner_usage($users['owner']->ID);
    [$success,$fails,$proof]=$parallel(['update','update'],[$sameUpdate,$sameUpdate],$auth,[$a,$b],'duplicate_update');
    $check('http_parallel_identical_update_returns_two_equal_results',count($success)===2&&$fails===[]&&$success[0]===$success[1]);
    $history=$data($execute('history',['proposal_id'=>$dupe['proposal_id'],'locale'=>'en','expected_version'=>$success[0]['version'],'page'=>1,'per_page'=>10],$auth,$a));
    $check('http_parallel_identical_update_appends_one_revision',($history['success']??false)&&$history['data']['total']===2&&Changes\owner_usage($users['owner']->ID)['requests']===$usage['requests']+1);
    $proof['committedRevisions']=1;$admissionProofs[]=$proof;
    $same['request_id']=bin2hex(random_bytes(16));$different=$same;$different['patch']['tagline']='Different parallel intent';
    [$success,$fails,$proof]=$parallel(['create','create'],[$same,$different],$auth,[$a,$b],'reused_key');
    $check('http_parallel_different_input_same_key_one_conflict',count($success)===1&&$fails===['request_reused']);$admissionProofs[]=$proof;
    // The second account has no proposals yet; fill seven slots, then race for the eighth.
    for($i=0;$i<Changes\MAX_OWNER_PROPOSALS-1;$i++){$filled=$data($execute('create',['project_id'=>$en,'locale'=>'en','patch'=>['tagline'=>'Quota fixture '.$i]],$otherAuth,$other));
        if(!($filled['success']??false)){throw new RuntimeException('HTTP_QUOTA_FILL_FAILED');}}
    $otherB=$initialize($otherAuth,'other');$left=$same;$left['request_id']=bin2hex(random_bytes(16));$right=$left;$right['request_id']=bin2hex(random_bytes(16));
    [$success,$fails,$proof]=$parallel(['create','create'],[$left,$right],$otherAuth,[$other,$otherB],'last_proposal_slot');
    $check('http_parallel_last_slot_admits_one_and_denies_one',count($success)===1&&$fails===['owner_quota']);
    $check('http_parallel_count_quota_never_exceeded',Changes\owner_usage($users['other']->ID)['proposals']===Changes\MAX_OWNER_PROPOSALS&&Changes\owner_usage($users['other']->ID)['requests']===Changes\MAX_OWNER_PROPOSALS);
    $admissionProofs[]=$proof;$report['admissionProofs']=$admissionProofs;
    $repeat=$data($execute('create',$input,$auth,$a));
    $check('http_old_create_retry_does_not_revert_current_head',($repeat['success']??false)&&$repeat['data']===$proposal);
    $check('http_history_old_pagination_head_rejected',$appError($execute('history',$historyInput,$auth,$a),'proposal_changed'));
    $head=$data($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$b));
    $restoreInput=$revisionInput+['expected_version'=>$head['data']['version']];
    $restoreInput['request_id']=bin2hex(random_bytes(16));
    $restored=$data($execute('restore',$restoreInput,$auth,$a));
    $check('http_restore_creates_new_version_with_original_patch',($restored['success']??false)&&$restored['data']['patch']===$proposal['patch']
        &&$restored['data']['version']!==$proposal['version']&&$restored['data']['version']!==$head['data']['version']);
    $repeat=$data($execute('restore',$restoreInput,$auth,$b));
    $check('http_restore_retry_returns_same_version',($repeat['success']??false)&&$repeat['data']===$restored['data']);
    $staleRestore=$restoreInput;$staleRestore['request_id']=bin2hex(random_bytes(16));
    $check('http_stale_restore_denied',$appError($execute('restore',$staleRestore,$auth,$b),'proposal_changed'));
    $historyInput=['proposal_id'=>$pid,'locale'=>'en','expected_version'=>$restored['data']['version'],'page'=>1,'per_page'=>10];
    $h=$data($execute('history',$historyInput,$auth,$b));$historyBeforeDeactivate=$h['data']??null;
    $check('http_restore_preserves_all_history_and_provenance',($h['success']??false)&&$h['data']['total']===5
        &&$h['data']['revisions'][4]['operation']==='restore'&&$h['data']['revisions'][4]['restored_from']===$proposal['version']);
    foreach(['a'=>$a,'b'=>$b]as $name=>$session){$alive=$rpc('tools/list',new stdClass(),$auth,$session);$check('owner_session_'.$name.'_survives_races',isset($alive['json']['result']['tools']));}
    $check('http_proposal_operations_do_not_change_source_records',$snapshot()===$before);
    update_post_meta($en,'project_tagline','Intentional synthetic source drift');
    $check('http_source_drift_blocks_old_proposal',$appError($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$a),'source_changed'));
    $check('http_source_drift_blocks_restore',$appError($execute('restore',$revisionInput+['expected_version'=>$restored['data']['version']],$auth,$a),'source_changed'));
    update_post_meta($en,'project_tagline','SOURCE_HTTP_TAGLINE_NOT_RETURNED');
    $check('restored_source_fixture_matches_original_hash',$snapshot()===$before);
    $users['owner']->remove_cap(Changes\CAPABILITY);
    $check('revoked_prepare_scope_blocks_existing_session',$appError($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$a),'forbidden'));
    $check('revoked_scope_blocks_history',$appError($execute('history',$historyInput,$auth,$b),'forbidden'));
    $users['owner']->add_cap(Changes\CAPABILITY);
    deactivate_plugins($plugin,true);
    $missing=$data($tool('mcp-adapter-discover-abilities',new stdClass(),$auth,$a));
    $check('deactivation_removes_proposal_definitions',isset($missing['abilities'])&&count(array_intersect(['alexcuadra/create-project-change','alexcuadra/get-project-change','alexcuadra/update-project-change'],array_column($missing['abilities'],'name')))===0);
    $check('deactivation_removes_history_definitions',isset($missing['abilities'])&&count(array_intersect($historyNames,array_column($missing['abilities'],'name')))===0);
    $old=$execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$a);
    $check('deactivated_proposal_ability_cannot_run',isset($old['json']['error'])||($old['json']['result']['isError']??false)===true||($data($old)['success']??null)===false);
    if(is_wp_error(activate_plugin($plugin,'',false,true))){throw new RuntimeException('PLUGIN_RESTORE_FAILED');}
    $again=$data($execute('get',['proposal_id'=>$pid,'locale'=>'en'],$auth,$a));
    $check('reactivation_preserves_private_proposal',($again['success']??false)&&$again['data']['proposal_id']===$pid);
    $h=$data($execute('history',$historyInput,$auth,$b));
    $check('reactivation_preserves_complete_history_across_requests',($h['success']??false)&&$h['data']===$historyBeforeDeactivate);
    $repeat=$data($execute('create',$input,$auth,$b));
    $check('reactivation_preserves_idempotency_receipt',($repeat['success']??false)&&$repeat['data']===$proposal);
    $closed=$request(null,$auth,$a,'DELETE');
    $check('delete_ends_one_owner_session',in_array($closed['status'],[200,204],true)&&!isset($closed['json']['error']));
    $dead=$rpc('tools/list',new stdClass(),$auth,$a);$alive=$rpc('tools/list',new stdClass(),$auth,$b);
    $check('closed_session_rejected_while_other_remains_valid',isset($dead['json']['error'])&&isset($alive['json']['result']['tools']));
    WP_Application_Passwords::delete_application_password($users['owner']->ID,$credentials[0]['uuid']);
    $revoked=$rpc('tools/list',new stdClass(),$auth,$b);
    $check('revoked_application_password_denies_existing_session',in_array($revoked['status'],[401,403],true));
    $check('sources_unchanged_at_end',$snapshot()===$before);
    $report['httpTransportVerified']=true;
} catch(Throwable $e){
    $report['failure']=preg_match('/^(FAILED: [a-z0-9_]+|[A-Z_]+)$/',$e->getMessage())?$e->getMessage():'CHANGES_HTTP_FAILED';
    $report['diagnostic']=['exceptionClass'=>get_class($e),'line'=>$e->getLine(),'lastHttp'=>$lastDiagnostic];
} finally {
    if(isset($en)){update_post_meta($en,'project_tagline','SOURCE_HTTP_TAGLINE_NOT_RETURNED');}
    if(!is_plugin_active($plugin)){activate_plugin($plugin,'',false,true);}
    foreach($users as $u){$u->remove_cap(Changes\CAPABILITY);$u->remove_cap('alexcuadra_read_public_projects');}
    foreach($users as $name=>$u){
        try{$cleanup=$credential($u->ID);foreach($sessions as $s){if($s['owner']===$name){$request(null,$cleanup,$s['id'],'DELETE');}}}
        catch(Throwable $ignored){$report['sessionCleanupError']=true;}
    }
    foreach($credentials as $c){WP_Application_Passwords::delete_application_password($c['user'],$c['uuid']);}
    foreach($raceKeys as $key){$wpdb->query($wpdb->prepare("DELETE FROM $table WHERE race_key=%s",$key));}
    $report['syntheticCredentialsRevoked']=true;$report['syntheticScopesRemoved']=true;
    foreach($users as $u){$fresh=new WP_User($u->ID);
        $report['syntheticCredentialsRevoked']=$report['syntheticCredentialsRevoked']&&WP_Application_Passwords::get_user_application_passwords($u->ID)===[];
        $report['syntheticScopesRemoved']=$report['syntheticScopesRemoved']&&!$fresh->has_cap(Changes\CAPABILITY)&&!$fresh->has_cap('alexcuadra_read_public_projects');}
    if(!$report['syntheticCredentialsRevoked']||!$report['syntheticScopesRemoved']||isset($report['sessionCleanupError'])||!is_plugin_active($plugin)){
        $report['failure']=$report['failure']??'SYNTHETIC_CLEANUP_FAILED';$report['httpTransportVerified']=false;
    }else{$cases[]='synthetic_credentials_removed';$cases[]='synthetic_scopes_removed';}
}
$report['passed']=count($cases);$report['failed']=isset($report['failure'])?1:0;$report['cases']=$cases;
WP_CLI::line('EDITORIAL_CHANGES_HTTP_RESULT='.wp_json_encode($report));
if($report['failed']){WP_CLI::halt(1);}
