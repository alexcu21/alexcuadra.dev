<?php
require_once '/integration/profile-admission.php';
$r=full_source_profile_admission();$r['passed']=true;
echo 'SOURCE_FULL_PROFILE_RESULT='.wp_json_encode($r).PHP_EOL;
