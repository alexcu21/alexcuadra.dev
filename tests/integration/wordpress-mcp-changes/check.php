<?php
use AlexCuadra\EditorialChanges as Changes;
if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { throw new RuntimeException('ISOLATED_CHANGES_LAB_REQUIRED'); }
global $wpdb;
$cases = []; $users = []; $report = ['environment' => 'isolated-change-proposals-docker',
    'wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION, 'polylang' => POLYLANG_VERSION,
    'sharedCmsModified' => false, 'sharedPermissionsModified' => false, 'emailSent' => false, 'httpTransportVerified' => false];
$check = static function ($name, $ok) use (&$cases) {
    if (!$ok) { throw new RuntimeException('FAILED: ' . $name); } $cases[] = $name;
};
$status = static function ($result, $expected) {
    if (!is_wp_error($result)) { return false; }
    $actual = $result->get_error_data()['status'] ?? null;
    // WP_Ability maps input/permission failures to framework codes without HTTP data.
    if ($actual === null) {
        $actual = ['ability_invalid_permissions' => 403, 'ability_invalid_input' => 400][$result->get_error_code()] ?? null;
    }
    return $actual === $expected;
};
$call = static function ($operation, $input) { if (in_array($operation, ['create','update','restore'], true)) { $input['request_id'] = $input['request_id'] ?? bin2hex(random_bytes(16)); } return wp_get_ability('alexcuadra/' . $operation . '-project-change')->execute($input); };
$switch = static function ($id) { wp_set_current_user(0); wp_set_current_user($id); };
try {
    PLL()->options->set('post_types', ['project']); PLL()->options->save_all();
    foreach ([['en','en_US','English'],['es','es_ES','Español']] as $lang) {
        if (!in_array($lang[0], pll_languages_list(), true)) {
            PLL()->model->add_language(['slug'=>$lang[0], 'locale'=>$lang[1], 'name'=>$lang[2], 'rtl'=>false, 'term_group'=>$lang[0]==='en'?0:1]);
        }
    }
    PLL()->options->set('default_lang','en'); PLL()->options->save_all();
    $marker = bin2hex(random_bytes(5));
    $make = static function ($locale, $state = 'publish', $type = 'project') use ($marker) {
        $id = wp_insert_post(['post_type'=>$type, 'post_status'=>$state, 'post_title'=>'Source ' . $marker,
            'post_content'=>'SOURCE_BODY_MUST_NOT_BE_EXPOSED', 'post_author'=>1]);
        pll_set_post_language($id,$locale); update_post_meta($id,'project_tagline','SOURCE_TAGLINE_MUST_NOT_BE_EXPOSED');
        update_post_meta($id,'_project_tagline',Changes\TAGLINE_KEY); update_post_meta($id,'private_contact','PRIVATE_META_MUST_NOT_BE_EXPOSED');
        return $id;
    };
    $en=$make('en'); $es=$make('es'); $draft=$make('en','draft'); $private=$make('en','private'); $page=$make('en','publish','page');
    pll_save_post_translations(['en'=>$en,'es'=>$es]);
    $protected=$make('en'); wp_update_post(['ID'=>$protected,'post_password'=>'synthetic-only']);
    foreach (['owner','other','editor'] as $name) {
        $id=wp_create_user('changes'.$marker.$name,wp_generate_password(32));
        if (is_wp_error($id)) { throw new RuntimeException('USER_SETUP_FAILED'); }
        $user=new WP_User($id); $user->set_role($name==='editor'?'editor':'subscriber'); $users[$name]=$user;
    }
    $sourceIds=implode(',',[$en,$es,$draft,$private,$page,$protected]);
    $snapshot=static function () use ($wpdb,$sourceIds) {
        return hash('sha256',serialize([
            $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID IN ($sourceIds) ORDER BY ID",ARRAY_A),
            $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id IN ($sourceIds) ORDER BY meta_id",ARRAY_A),
            $wpdb->get_results("SELECT * FROM $wpdb->term_relationships WHERE object_id IN ($sourceIds) ORDER BY object_id,term_taxonomy_id",ARRAY_A)]));
    };
    $original=$snapshot();
    $input=['project_id'=>$en,'locale'=>'en','patch'=>['body_html'=>'<p>Proposed "quotes" / café \\ path.</p>','tagline'=>'Proposed subtitle']];
    $check('three_proposal_abilities_registered', wp_get_ability('alexcuadra/create-project-change') && wp_get_ability('alexcuadra/get-project-change') && wp_get_ability('alexcuadra/update-project-change'));
    $check('activation_grants_no_capabilities', !$users['owner']->has_cap(Changes\CAPABILITY) && !get_role('subscriber')->has_cap(Changes\CAPABILITY));
    $switch(0); $check('anonymous_denied',$status($call('create',$input),403));
    $switch($users['owner']->ID); $check('subscriber_without_scope_denied',$status($call('create',$input),403));
    $users['owner']->add_cap('alexcuadra_read_public_projects'); $switch($users['owner']->ID);
    $check('public_read_scope_does_not_grant_prepare_scope',$status($call('create',$input),403));
    $switch($users['editor']->ID); $check('editor_role_alone_is_not_authorization',$status($call('create',$input),403));
    $switch(1); $check('administrator_role_alone_is_not_authorization',$status($call('create',$input),403));
    $users['owner']->add_cap(Changes\CAPABILITY); $users['owner']->remove_cap('alexcuadra_read_public_projects'); $switch($users['owner']->ID);
    $check('prepare_scope_also_requires_public_read_scope',$status($call('create',$input),403));
    $users['owner']->add_cap('alexcuadra_read_public_projects');
    $users['owner']->add_cap(Changes\CAPABILITY); $switch($users['owner']->ID);
    $check('preparer_is_subscriber_without_global_edit_permissions',current_user_can(Changes\CAPABILITY) && !current_user_can('edit_pages') && !current_user_can('publish_pages') && !current_user_can('manage_options'));
    $created=$call('create',$input);
    $check('owned_private_proposal_created',!is_wp_error($created) && $created['state']==='draft' && $created['applied']===false);
    $id=$created['proposal_id']; $proposal=get_post($id);
    $check('proposal_type_status_owner_fixed',$proposal->post_type===Changes\TYPE && $proposal->post_status==='draft' && (int)$proposal->post_author===$users['owner']->ID);
    $check('only_proposed_fields_returned_without_source_or_private_meta',!str_contains(wp_json_encode($created),'MUST_NOT_BE_EXPOSED') && array_keys($created)===['proposal_id','project_id','locale','version','base_version','patch','state','applied']);
    $check('quoted_unicode_patch_survives_storage',$call('get',['proposal_id'=>$id,'locale'=>'en'])['patch']===$created['patch']);
    $check('source_records_unchanged_after_create',$snapshot()===$original);
    $updated=$call('update',['proposal_id'=>$id,'locale'=>'en','expected_version'=>$created['version'],'patch'=>['tagline'=>'Second subtitle']]);
    $check('own_proposal_update_changes_version',!is_wp_error($updated) && $updated['version']!==$created['version'] && $updated['patch']['tagline']==='Second subtitle');
    $check('partial_patch_preserves_other_proposed_field',$updated['patch']['body_html']===$created['patch']['body_html']);
    $check('old_version_rejected_with_conflict',$status($call('update',['proposal_id'=>$id,'locale'=>'en','expected_version'=>$created['version'],'patch'=>['tagline'=>'Lost update']]),409));
    $check('conflicting_update_did_not_overwrite',$call('get',['proposal_id'=>$id,'locale'=>'en'])['patch']['tagline']==='Second subtitle');
    foreach (['post_status'=>'publish','post_author'=>1,'project_id'=>$es,'locale'=>'es','meta'=>['private_contact'=>'x'],'githuburl'=>'https://example.test'] as $field=>$value) {
        $invalid=$input; $invalid['patch'][$field]=$value;
        $check('forbidden_patch_'.$field,$status($call('create',$invalid),400));
        $check('direct_callback_forbidden_patch_'.$field,$status(Changes\execute($invalid,'create'),400));
    }
    $invalid=$input; $invalid['author']=1; $check('arbitrary_top_level_fields_denied',$status($call('create',$invalid),400));
    $invalid=$input; $invalid['patch']=[]; $check('empty_patch_denied',$status($call('create',$invalid),400));
    $invalid=$input; $invalid['patch']=['tagline'=>str_repeat('a',501)]; $check('oversized_patch_denied',$status($call('create',$invalid),400));
    $invalid=$input; $invalid['patch']=['tagline'=>"bad\0value"]; $check('control_bytes_denied',$status($call('create',$invalid),400));
    $invalid=$input; $invalid['project_id']=(string)$en; $check('string_id_denied',$status($call('create',$invalid),400));
    foreach ([$draft,$private,$protected,$page,2147483647,$id] as $target) {
        $invalid=$input; $invalid['project_id']=$target;
        $check('invalid_source_denied_'.count($cases),$status($call('create',$invalid),404));
    }
    $invalid=$input; $invalid['locale']='es'; $check('wrong_source_language_denied',$status($call('create',$invalid),404));
    $spanish=$call('create',['project_id'=>$es,'locale'=>'es','patch'=>['tagline'=>'Propuesta española']]);
    $check('spanish_proposal_exact_language',!is_wp_error($spanish) && $spanish['locale']==='es');
    $check('proposal_cannot_be_read_in_other_language',$status($call('get',['proposal_id'=>$id,'locale'=>'es']),404));
    foreach (['other','editor'] as $name) {
        $users[$name]->add_cap('alexcuadra_read_public_projects'); $users[$name]->add_cap(Changes\CAPABILITY); $switch($users[$name]->ID);
        $check($name.'_cannot_read_foreign_proposal',$status($call('get',['proposal_id'=>$id,'locale'=>'en']),404));
        $check($name.'_cannot_update_foreign_proposal',$status($call('update',['proposal_id'=>$id,'locale'=>'en','expected_version'=>$updated['version'],'patch'=>['tagline'=>'Steal']]),404));
    }
    $switch($users['owner']->ID);
    $unsafe=$call('create',['project_id'=>$en,'locale'=>'en','patch'=>['body_html'=>'<p onclick="evil()">Safe</p><script>evil()</script><a href="javascript:evil()">link</a><img src="https://example.test/tracker">','tagline'=>'<b>Plain</b>']]);
    $check('proposed_html_normalized_without_executable_markup',!is_wp_error($unsafe) && !preg_match('/<script|onclick|javascript:|<img/i',$unsafe['patch']['body_html']) && $unsafe['patch']['tagline']==='Plain');
    $type=get_post_type_object(Changes\TYPE);
    $check('proposal_type_has_nonpublic_api_settings',!$type->public && !$type->publicly_queryable && !$type->show_in_rest && !$type->show_ui && !$type->show_in_graphql);
    $routes=rest_get_server()->get_routes();
    $check('no_native_rest_route_for_proposals',!array_filter(array_keys($routes),static function($route){return str_contains($route,Changes\TYPE);}));
    $rest=rest_do_request(new WP_REST_Request('GET','/wp-abilities/v1/abilities'));
    $check('core_rest_discovery_excludes_proposal_abilities',$rest->get_status()===200 && !str_contains(wp_json_encode($rest->get_data()),'alexcuadra/create-project-change')
        && !str_contains(wp_json_encode($rest->get_data()),'alexcuadra/get-project-change') && !str_contains(wp_json_encode($rest->get_data()),'alexcuadra/update-project-change'));
    $discoverAbility=wp_get_ability('mcp-adapter/discover-abilities');
    $check('official_adapter_registered',$discoverAbility instanceof WP_Ability);
    $discover=$discoverAbility->execute();
    $check('official_adapter_discovers_proposal_abilities',!is_wp_error($discover) && count(array_intersect(['alexcuadra/create-project-change','alexcuadra/get-project-change','alexcuadra/update-project-change'],array_column($discover['abilities'],'name')))===3);
    $viaAdapter=wp_get_ability('mcp-adapter/execute-ability')->execute(['ability_name'=>'alexcuadra/get-project-change','parameters'=>['proposal_id'=>$id,'locale'=>'en']]);
    $check('official_adapter_reads_owned_proposal',!is_wp_error($viaAdapter) && $viaAdapter['success'] && $viaAdapter['data']['proposal_id']===$id);
    $adapterCreate=wp_get_ability('mcp-adapter/execute-ability')->execute(['ability_name'=>'alexcuadra/create-project-change','parameters'=>$input+['request_id'=>bin2hex(random_bytes(16))]]);
    $check('official_adapter_creates_owned_private_proposal',!is_wp_error($adapterCreate) && $adapterCreate['success'] && $adapterCreate['data']['applied']===false);
    $adapterUpdate=wp_get_ability('mcp-adapter/execute-ability')->execute(['ability_name'=>'alexcuadra/update-project-change','parameters'=>[
        'proposal_id'=>$adapterCreate['data']['proposal_id'],'locale'=>'en','expected_version'=>$adapterCreate['data']['version'],'request_id'=>bin2hex(random_bytes(16)),'patch'=>['tagline'=>'Adapter update']]]);
    $check('official_adapter_updates_only_proposal',!is_wp_error($adapterUpdate) && $adapterUpdate['success'] && $adapterUpdate['data']['patch']['tagline']==='Adapter update' && $adapterUpdate['data']['applied']===false);
    // Reentrant write hooks must not commit an outer transaction. Real races run through HTTP.
    $nested=null; $interleave=null;
    $interleave=static function($query) use (&$interleave,&$nested,$id,$updated) {
        if (str_contains($query,'SET m.meta_value=')) {
            remove_filter('query',$interleave);
            $nested=Changes\execute(['proposal_id'=>$id,'locale'=>'en','expected_version'=>$updated['version'],
                'request_id'=>bin2hex(random_bytes(16)),'patch'=>['tagline'=>'Nested loser']],'update');
        }
        return $query;
    };
    add_filter('query',$interleave);
    $winner=$call('update',['proposal_id'=>$id,'locale'=>'en','expected_version'=>$updated['version'],'patch'=>['tagline'=>'Outer winner']]);
    remove_filter('query',$interleave);
    $check('outer_transaction_write_succeeds',!is_wp_error($winner));
    $check('nested_write_rejected_without_committing_outer',$status($nested,503) && $nested->get_error_message()==='transaction_busy');
    $check('outer_proposal_preserved',$call('get',['proposal_id'=>$id,'locale'=>'en'])['patch']['tagline']==='Outer winner');
    $check('all_proposal_operations_leave_source_records_unchanged',$snapshot()===$original);
    $users['owner']->remove_cap(Changes\CAPABILITY); $switch($users['owner']->ID);
    $check('revoked_scope_denies_existing_proposal',$status($call('get',['proposal_id'=>$id,'locale'=>'en']),403));
    $check('direct_callback_enforces_revocation',$status(Changes\execute($input,'create'),403));
    $users['owner']->add_cap(Changes\CAPABILITY); $switch($users['owner']->ID);
    // Intentional fixture drift, separate from proposal operations; no automatic rebase/apply.
    update_post_meta($en,'project_tagline','Externally changed source');
    $check('changed_source_blocks_existing_proposal',$status($call('get',['proposal_id'=>$id,'locale'=>'en']),409));
    $check('changed_source_blocks_update',$status($call('update',['proposal_id'=>$id,'locale'=>'en','expected_version'=>$winner['version'],'patch'=>['tagline'=>'Overwrite']]),409));
    $check('source_edit_not_automatically_overwritten',get_post_meta($en,'project_tagline',true)==='Externally changed source');
    wp_update_post(['ID'=>$en,'post_status'=>'draft']);
    $check('withdrawn_source_denies_proposal',$status($call('get',['proposal_id'=>$id,'locale'=>'en']),404));
} catch (Throwable $e) {
    $report['failure']=preg_match('/^(FAILED: [a-z0-9_]+|[A-Z_]+)$/',$e->getMessage())?$e->getMessage():'CHANGES_LAB_FAILED';
    $report['diagnostic']=['exceptionClass'=>get_class($e),'line'=>$e->getLine()];
} finally {
    foreach ($users as $user) { $user->remove_cap(Changes\CAPABILITY); $user->remove_cap('alexcuadra_read_public_projects'); }
    $switch(0);
    $report['syntheticScopeCleanupVerified']=true;
    foreach ($users as $user) {
        $fresh=new WP_User($user->ID);
        if ($fresh->has_cap(Changes\CAPABILITY) || $fresh->has_cap('alexcuadra_read_public_projects')) { $report['syntheticScopeCleanupVerified']=false; }
    }
    if (!$report['syntheticScopeCleanupVerified']) { $report['failure']=$report['failure']??'SYNTHETIC_SCOPE_CLEANUP_FAILED'; }
}
$report['passed']=count($cases); $report['failed']=isset($report['failure'])?1:0; $report['cases']=$cases;
WP_CLI::line('EDITORIAL_CHANGES_RESULT='.wp_json_encode($report));
if ($report['failed']) { WP_CLI::halt(1); }
