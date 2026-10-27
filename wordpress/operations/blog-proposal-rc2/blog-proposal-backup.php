<?php
/** Operator CLI only. No plugin/ability, no automatic grants or publication. */
namespace AlexCuadra\BlogProposalRecovery;
if(!defined('WP_CLI')||!WP_CLI){throw new \RuntimeException('WP_CLI_REQUIRED');}
require_once __DIR__.'/recovery.php';
try{
 $input=$args??[];$mode=$input[0]??'';
 if($mode==='export'&&count($input)===3){$result=export_backup($input[1],$input[2]);}
 elseif($mode==='verify'&&count($input)===3){
  paused_operator('writers-stopped');$checked=verify_backup($input[1],$input[2]);$result=['counts'=>$checked['manifest']['counts'],'integrityVerified'=>true,'restorationVerified'=>false,'databaseWritesPerformed'=>false];
 }elseif($mode==='restore-empty'&&count($input)===4){$result=restore_empty($input[1],$input[2],$input[3]);}
 else{fail('ARGUMENTS');}
 \WP_CLI::line('BLOG_PROPOSAL_BACKUP_RESULT='.json_encode(['format'=>'blog-proposal-backup-operator-v1','mode'=>$mode,'ready'=>true]+$result,JSON_THROW_ON_ERROR));
}catch(\Throwable $e){
 // No stack, SQL, paths, message bodies or exception arguments in operator stdout.
 $code=$e instanceof \DomainException&&preg_match('/^BLOG_RECOVERY_[A-Z_]+$/D',$e->getMessage())?$e->getMessage():'BLOG_RECOVERY_FAILED';
 \WP_CLI::line('BLOG_PROPOSAL_BACKUP_RESULT='.json_encode(['format'=>'blog-proposal-backup-operator-v1','mode'=>$mode??'','ready'=>false,'code'=>$code,'automaticRetryAllowed'=>false],JSON_THROW_ON_ERROR));
 \WP_CLI::halt(1);
}
