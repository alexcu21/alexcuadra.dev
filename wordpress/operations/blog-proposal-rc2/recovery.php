<?php
/** Operator-only recovery library. Four own tables; never restores WordPress source/configuration. */
namespace AlexCuadra\BlogProposalRecovery;
use AlexCuadra\EditorialBlogProposals as B;
ini_set('zend.exception_ignore_args','1');
const PACKAGE_SHA='7e5bc2b3213a8497dc47426b4565bbf528400d69f4e4f3e57f40f6119d4d6def';
const COLUMNS=[
 'owners'=>['owner_id'], 'proposals'=>['id','owner_id','version'],
 'revisions'=>['proposal_id','owner_id','version','document','document_hash'],
 'requests'=>['owner_id','request_id','request_hash','proposal_id','version'],
];
const ORDERS=['owners'=>'owner_id','proposals'=>'id','revisions'=>'proposal_id,version','requests'=>'owner_id,request_id'];
const LIMITS=['owners'=>1,'proposals'=>16,'revisions'=>64,'requests'=>128];
const MAX_BYTES=16777216;
function fail(string $code): never {throw new \DomainException('BLOG_RECOVERY_'.$code);}
function exact(array $value,array $keys): void {$actual=array_keys($value);sort($actual);sort($keys);if($actual!==$keys){fail('FIELDS');}}
function encode(array $value): string {return json_encode($value,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
function integer($value,int $max=PHP_INT_MAX): int {
 if(!is_string($value)||!preg_match('/^[1-9][0-9]*$/D',$value)||(string)(int)$value!==$value||(int)$value>$max){fail('INTEGER');}return (int)$value;
}
function sha($value): void {if(!is_string($value)||!preg_match('/^[a-f0-9]{64}$/D',$value)){fail('HASH');}}
function paused_operator(string $declaration): void {
 if(!defined('WP_CLI')||!WP_CLI||$declaration!=='writers-stopped'){fail('PAUSE_DECLARATION');}
 if(!class_exists(B\WordPressRepository::class)||get_current_user_id()!==1||!current_user_can('manage_options')){fail('OPERATOR');}
 B\wordpress_guard();
 $root=WP_PLUGIN_DIR.'/alexcuadra-editorial-blog-proposals';
 $hashes=json_decode('{"README.md":"606c7dff0d1e4e684342719800b4fe2a9bfa8b143d3cc936afa2607ec0079369","abilities.php":"46c4ef85ae4f336e7b62075ea34da9cfeba7d69e48eb366d19e1f364050009c2","admission.php":"7043443da1d1ea07208cd52dbafbd98f1458e425b19a00546d3219ac90805eb4","alexcuadra-editorial-blog-proposals.php":"62346abeeb919130faea84b8db88876140125cb837eaa06e0062a0fcf423c42a","connection-fence.php":"c836eb135f411cc2e59d54eb126bd5d2e4a91ba184ca47018c3728e4ae1b5be5","planner.php":"a47a8ce035a391fa4e0ba7de7d11a0db0b2b8d241aa7d3d9b5d063d55b44ae8f","wordpress-repository.php":"14756411443d31ad89409a8a0963b72d38492d2e544e4b8b523ca3592bbf7204","wordpress-source.php":"10a7efb3af20870fbd3cec53550d663b1253b397c8cffa8776a7cd39071a94bd"}',true,32,JSON_THROW_ON_ERROR);
 $names=array_values(array_diff(scandir($root),['.','..']));sort($names);$wanted=array_keys($hashes);sort($wanted);
 if(is_link($root)||$names!==$wanted){fail('PACKAGE');}
 foreach($hashes as$name=>$hash){$path=$root.'/'.$name;if(is_link($path)||!is_file($path)||hash_file('sha256',$path)!==$hash){fail('PACKAGE');}}
 $user=get_userdata(2);$caps=$user?array_keys(array_filter($user->allcaps)):[];sort($caps);
 $base=['alexcuadra_apply_project_changes','alexcuadra_prepare_project_changes','alexcuadra_read_public_posts','alexcuadra_read_public_projects','alexcuadra_recover_project_changes','level_0','read','subscriber'];sort($base);
 if(!$user||$user->roles!==['subscriber']||$caps!==$base||$user->has_cap(B\PREPARE_SCOPE)){fail('SCOPES_NOT_PAUSED');}
 if(get_users(['capability'=>'alexcuadra_prepare_post_changes','number'=>1,'fields'=>'ID'])){fail('SCOPES_NOT_PAUSED');}
 foreach(wp_roles()->roles as$role){if(!empty($role['capabilities']['alexcuadra_prepare_post_changes'])){fail('SCOPES_NOT_PAUSED');}}
}
/** Canonical connection, direct mysqli reads: no wpdb automatic reconnection in the snapshot. */
function handle(): \mysqli {
 global $wpdb;
 if(get_class($wpdb)!=='wpdb'){fail('CONNECTION');}
 $h=(new \ReflectionProperty(\wpdb::class,'dbh'))->getValue($wpdb);
 if(!$h instanceof \mysqli){fail('CONNECTION');}return $h;
}
function rows(\mysqli $h,string $prefix): array {
 if(!preg_match('/^[a-zA-Z0-9_]+$/D',$prefix)){fail('PREFIX');}
 $all=[];
 foreach(COLUMNS as$s=>$cols){
  $q='SELECT `'.implode('`,`',$cols).'` FROM `'.$prefix.'ac_blog_proposal_'.$s.'` ORDER BY '.ORDERS[$s].' LIMIT '.(LIMITS[$s]+1);
  $result=\mysqli_query($h,$q);$data=\mysqli_fetch_all($result,MYSQLI_ASSOC);\mysqli_free_result($result);
  if(count($data)>LIMITS[$s]){fail('QUOTA');}
  $all[$s]=array_map(static fn($row)=>array_map(static fn($v)=>(string)$v,$row),$data);
 }
 return $all;
}
function validate_rows(array $tables): array {
 exact($tables,array_keys(COLUMNS));$counts=[];$heads=[];$versions=[];$documents=[];$receipts=[];$bytes=0;
 foreach(COLUMNS as$s=>$cols){if(!is_array($tables[$s])||!array_is_list($tables[$s])||count($tables[$s])>LIMITS[$s]){fail('QUOTA');}
  $counts[$s]=count($tables[$s]);foreach($tables[$s]as$row){if(!is_array($row)){fail('ROW');}exact($row,$cols);foreach($row as$v){if(!is_string($v)){fail('ROW');}}if($row['owner_id']!=='2'){fail('OWNER');}}}
 if($tables['owners']!==[['owner_id'=>'2']]){fail('OWNER');}
 foreach($tables['proposals']as$row){$id=integer($row['id']);$v=integer($row['version'],16);if(isset($heads[$id])){fail('DUPLICATE');}$heads[$id]=$v;}
 foreach($tables['revisions']as$row){
  $id=integer($row['proposal_id']);$v=integer($row['version'],16);$key=$id.':'.$v;
  if(!isset($heads[$id])||isset($documents[$key])||$v>$heads[$id]||strlen($row['document'])>786432){fail('REVISION');}
  sha($row['document_hash']);$doc=json_decode($row['document'],true,32,JSON_THROW_ON_ERROR);
  $review=['proposal_id'=>$id,'version'=>$v,'document'=>$doc];
  if(is_wp_error(rest_validate_value_from_schema($review,B\contracts()['get-post-proposal']['output']))){fail('DOCUMENT');}
  $hash=$doc['document_hash'];unset($doc['document_hash']);
  if($doc['owner_id']!==2||$doc['proposal_id']!==$id||$doc['version']!==$v||!hash_equals($hash,$row['document_hash'])||!hash_equals(B\digest($doc),$hash)){fail('DOCUMENT');}
  $documents[$key]=$doc;$versions[$id][]=$v;$bytes+=strlen($row['document']);
 }
 foreach($heads as$id=>$v){$actual=$versions[$id]??[];sort($actual);if($actual!==range(1,$v)){fail('REVISION_GAP');}}
 foreach($tables['requests']as$row){
  $id=integer($row['proposal_id']);$v=integer($row['version'],16);$key=$id.':'.$v;sha($row['request_hash']);
  if(!preg_match('/^[a-f0-9]{32}$/D',$row['request_id'])||isset($receipts[$key])||!isset($documents[$key])||$documents[$key]['request_id']!==$row['request_id']){fail('RECEIPT');}
  $receipts[$key]=true;
 }
 if(count($receipts)!==count($documents)||count(array_unique(array_column($tables['requests'],'request_id')))!==count($receipts)){fail('RECEIPT');}
 if($bytes+$counts['requests']*256+$counts['proposals']*128>8388608){fail('QUOTA');}
 return $counts;
}
function private_directory(string $directory): string {
 $real=realpath($directory);$public=realpath(dirname(rtrim(ABSPATH,'/')));
 if(!$real||$real!==rtrim($directory,'/')||is_link($directory)||!is_dir($real)||((fileperms($real)&0777)!==0700)
   ||$real===$public||str_starts_with($real,$public.'/')){fail('PRIVATE_DIRECTORY');}
 return $real;
}
function write_private(string $path,string $value): void {
 $old=umask(0077);try{$f=@fopen($path,'xb');}finally{umask($old);}
 if(!$f){fail('FILE_CREATE');}
 try{if(!@chmod($path,0600)){fail('FILE_MODE');}$n=0;while($n<strlen($value)){$w=@fwrite($f,substr($value,$n));if(!$w){fail('FILE_WRITE');}$n+=$w;}if(!@fflush($f)||(function_exists('fsync')&&!@fsync($f))){fail('FILE_FLUSH');}}finally{fclose($f);}
}
function read_private(string $directory,string $name,int $limit): string {
 $file=$directory.'/'.$name;
 if(is_link($file)||!is_file($file)||(fileperms($file)&0777)!==0600||filesize($file)>$limit){fail('PRIVATE_FILE');}
 $f=@fopen($file,'rb');if(!$f){fail('FILE_READ');}
 try{$data=@stream_get_contents($f,$limit+1);if($data===false||strlen($data)>$limit){fail('FILE_READ');}return $data;}finally{fclose($f);}
}
function export_backup(string $base,string $declaration): array {
 paused_operator($declaration);global $wpdb;$base=private_directory($base);
 if(!B\schema_report($wpdb)['ready']){fail('SCHEMA');}
 $h=handle();$report=(new \mysqli_driver)->report_mode;$started=false;
 try{
  \mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
  $state=\mysqli_fetch_row(\mysqli_query($h,'SELECT CONNECTION_ID(),@@in_transaction,@@character_set_connection'));
  if((int)$state[1]!==0||$state[2]!=='utf8mb4'){fail('CONNECTION');}
  \mysqli_query($h,'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
  \mysqli_query($h,'START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');$started=true;
  $tables=rows($h,$wpdb->prefix);$counts=validate_rows($tables);
  $end=\mysqli_fetch_row(\mysqli_query($h,'SELECT CONNECTION_ID(),@@in_transaction'));
  if($state[0]!==$end[0]||(int)$end[1]!==1||handle()!==$h){fail('CONNECTION');}
  \mysqli_query($h,'ROLLBACK');$started=false;
 }finally{
  if($started){try{\mysqli_query($h,'ROLLBACK');}catch(\Throwable $ignored){}}
  \mysqli_report($report);
 }
 paused_operator($declaration);if(!B\schema_report($wpdb)['ready']){fail('SCHEMA');}
 $payload=encode(['format'=>'blog-proposal-private-tables-v1','tables'=>$tables]);if(strlen($payload)>MAX_BYTES){fail('QUOTA');}
 $name='backup-'.gmdate('Ymd\THis\Z').'-'.bin2hex(random_bytes(8));$dir=$base.'/'.$name;
 if(!@mkdir($dir,0700)){fail('DIRECTORY_CREATE');}if(!@chmod($dir,0700)){fail('DIRECTORY_MODE');}
 write_private($dir.'/tables.json',$payload);
 $manifest=['format'=>'blog-proposal-recovery-v1','site'=>'https://content.alexcuadra.dev','packageSha256'=>PACKAGE_SHA,'ownerId'=>2,
  'createdAtUtc'=>gmdate('Y-m-d\TH:i:s\Z'),'tablesBytes'=>strlen($payload),'tablesSha256'=>hash('sha256',$payload),'counts'=>$counts];
 // Manifest is written last: an interrupted directory is not a complete backup.
 write_private($dir.'/manifest.json',encode($manifest));
 $sha=hash_file('sha256',$dir.'/manifest.json');verify_backup($dir,$sha);
 return ['backupDirectory'=>$name,'manifestSha256'=>$sha,'counts'=>$counts,'databaseWritesPerformed'=>false,'restorationVerified'=>false];
}
function verify_backup(string $directory,string $expectedManifestSha): array {
 sha($expectedManifestSha);$dir=private_directory($directory);
 $names=array_values(array_diff(scandir($dir),['.','..']));sort($names);if($names!==['manifest.json','tables.json']){fail('FILE_SET');}
 $raw=read_private($dir,'manifest.json',16384);if(!hash_equals(hash('sha256',$raw),$expectedManifestSha)){fail('MANIFEST_HASH');}
 $m=json_decode($raw,true,32,JSON_THROW_ON_ERROR);if(!is_array($m)){fail('MANIFEST');}
 exact($m,['format','site','packageSha256','ownerId','createdAtUtc','tablesBytes','tablesSha256','counts']);
 if($m['format']!=='blog-proposal-recovery-v1'||$m['site']!=='https://content.alexcuadra.dev'||$m['packageSha256']!==PACKAGE_SHA||$m['ownerId']!==2
   ||!is_int($m['tablesBytes'])||$m['tablesBytes']>MAX_BYTES||!is_string($m['createdAtUtc'])||!preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ$/D',$m['createdAtUtc'])){fail('MANIFEST');}
 sha($m['tablesSha256']);$payload=read_private($dir,'tables.json',MAX_BYTES);
 if(strlen($payload)!==$m['tablesBytes']||!hash_equals(hash('sha256',$payload),$m['tablesSha256'])){fail('PAYLOAD_HASH');}
 $data=json_decode($payload,true,32,JSON_THROW_ON_ERROR);if(!is_array($data)){fail('PAYLOAD');}exact($data,['format','tables']);
 if($data['format']!=='blog-proposal-private-tables-v1'||!is_array($data['tables'])){fail('PAYLOAD');}
 $counts=validate_rows($data['tables']);if($counts!==$m['counts']){fail('COUNTS');}
 return ['manifest'=>$m,'tables'=>$data['tables']];
}
/** Only an entirely absent set may be restored. No DROP, ALTER, deletion or upsert. */
function restore_empty(string $directory,string $expectedManifestSha,string $declaration): array {
 paused_operator($declaration);$backup=verify_backup($directory,$expectedManifestSha);global $wpdb;
 if(B\schema_report($wpdb)['present']!==0){fail('RESTORE_TARGET_NOT_EMPTY');}
 $repo=new B\WordPressRepository($wpdb);$repo->install();
 $previous=$wpdb->suppress_errors(true);
 try{B\fenced_transaction($wpdb,static function()use($wpdb,$backup){
  foreach(COLUMNS as$s=>$cols){foreach($backup['tables'][$s]as$row){if($wpdb->insert($wpdb->prefix.'ac_blog_proposal_'.$s,$row)===false){fail('RESTORE_INSERT');}}}
  if(rows(handle(),$wpdb->prefix)!==$backup['tables']){fail('RESTORE_COMPARE');}
 });}finally{$wpdb->suppress_errors($previous);}
 if(!B\schema_report($wpdb)['ready']){fail('RESTORE_SCHEMA');}
 return ['counts'=>$backup['manifest']['counts'],'privateRowsRestored'=>true,'sourceTablesRestored'=>false,'permissionsRestored'=>false];
}
