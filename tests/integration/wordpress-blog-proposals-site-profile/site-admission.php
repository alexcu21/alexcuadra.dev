<?php
/** Additional admission for a non-distributable, isolated site-profile experiment. */
namespace AlexCuadra\EditorialBlogProposals;
function profile_report(): array {
    $report=minimal_profile_report();
    $expected=json_decode(file_get_contents(__DIR__.'/site-profile.json'),true,32,JSON_THROW_ON_ERROR);
    $self='alexcuadra-editorial-blog-proposals/alexcuadra-editorial-blog-proposals.php';
    $active=$report['activePlugins'];$selfVersion=$active[$self]??null;unset($active[$self]);
    ksort($active);$wanted=$expected['activePlugins'];ksort($wanted);
    $mains=true;foreach($expected['mainSha256']as$file=>$hash){$path=WP_PLUGIN_DIR.'/'.$file;$mains=$mains&&is_file($path)&&hash_file('sha256',$path)===$hash;}
    if($selfVersion!==null){$mains=$mains&&$selfVersion==='0.1.0-lab.site.1'&&hash_file('sha256',WP_PLUGIN_DIR.'/'.$self)===$expected['selfMainSha256'];}
    $mu=$report['muPluginSha256'];$wantedMu=$expected['muPluginSha256'];$wantedMu['000-blog-site-guard.php']=$expected['fixtureMuSha256'];ksort($mu);ksort($wantedMu);
    $theme=$expected['theme'];$themeInfo=['stylesheet'=>$theme['stylesheet'],'template'=>$theme['template'],'version'=>$theme['version'],'styleSha256'=>$theme['styleSha256'],'functionsSha256'=>$theme['functionsSha256']];
    $report['checks']['testedActivePlugins']=$active===$wanted&&$mains;
    $report['checks']['testedMuPlugins']=$mu===$wantedMu;
    $report['checks']['testedTheme']=$report['theme']===$themeInfo;
    // Arbitrary callbacks are admitted solely for deliberate fault injection in this LAB gate.
    $report['checks']['queryFilters']=site_admitted();
    $report['checks']['isolatedSiteLab']=site_admitted();
    $report['ready']=!in_array(false,$report['checks'],true);
    $report['siteProfileLaboratory']=true;$report['productionAdmission']=false;
    return $report;
}
