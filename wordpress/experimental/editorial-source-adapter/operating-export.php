<?php
/** Private owner archive, not a database backup/import or retention policy. */
namespace AlexCuadra\EditorialSourceExperiment;
function operating_export_path($path){
    if(!is_string($path)||!preg_match('~^/.+/[a-zA-Z0-9_-]+\.ndjson$~D',$path)){throw new \RuntimeException('contract_export_path');}
    $parent=dirname($path);$real=realpath($parent);$web=rtrim(realpath(ABSPATH),'/');
    if($real!==$parent||($real===$web||str_starts_with($real,$web.'/'))||((fileperms($real)&0777)!==0700)||is_link($parent)){throw new \RuntimeException('contract_export_path');}
    if(file_exists($path)||is_link($path)){throw new \RuntimeException('contract_export_exists');}
    return $path;
}
function verify_operating_export($path){
    $before=lstat($path);if(!$before||is_link($path)||($before['mode']&0777)!==0600||($before['mode']&0170000)!==0100000||$before['size']>33554432){throw new \RuntimeException('contract_export_invalid');}
    $f=fopen($path,'rb');if(!$f){throw new \RuntimeException('contract_export_invalid');}$stat=fstat($f);
    try{
        if($stat['ino']!==$before['ino']||$stat['dev']!==$before['dev']){throw new \RuntimeException('contract_export_invalid');}
        $hash=hash_init('sha256');$header=null;$footer=null;$counts=array_fill_keys(['reviews','requests','apps','approvals','events','effects'],0);$bytes=0;$records=0;
        while(($line=fgets($f,2097153))!==false){
            if(!str_ends_with($line,"\n")||strlen($line)>2097152||$footer!==null){throw new \RuntimeException('contract_export_invalid');}
            $d=json_decode($line,true,32,JSON_THROW_ON_ERROR);
            if($header===null){
                if(!operating_keys($d,['format','owner_id','schema_hash','counts'])||$d['format']!=='operating-private-export-v1'||!operating_positive($d['owner_id'])||!operating_hex($d['schema_hash'])||!operating_keys($d['counts'],array_keys($counts))){throw new \RuntimeException('contract_export_invalid');}
                foreach($d['counts'] as $n){if(!is_int($n)||$n<0||$n>128){throw new \RuntimeException('contract_export_invalid');}}$header=$d;
            }elseif(($d['format']??null)==='operating-private-export-footer-v1'){
                if(!operating_keys($d,['format','records','data_bytes','data_sha256'])||$d['records']!==$records||$d['data_bytes']!==$bytes||$d['data_sha256']!==hash_final($hash)||$counts!==$header['counts']){throw new \RuntimeException('contract_export_invalid');}$footer=$d;continue;
            }else{
                if(!operating_keys($d,['table','row'])||!is_string($d['table'])||!array_key_exists($d['table'],$counts)||!is_array($d['row'])||++$records>128){throw new \RuntimeException('contract_export_invalid');}$counts[$d['table']]++;
            }
            hash_update($hash,$line);$bytes+=strlen($line);
        }
        if(!feof($f)||$footer===null){throw new \RuntimeException('contract_export_invalid');}
        return ['format'=>'operating-private-export-verification-v1','verified'=>true,'records'=>$records,'bytes'=>$stat['size'],'sha256'=>hash_file('sha256',$path),'counts'=>$counts,'restoration_verified'=>false];
    }finally{fclose($f);}
}
function publish_operating_export($temporary,$destination){
    $verified=verify_operating_export($temporary);operating_export_path($destination);
    $oldMask=umask(0077);$input=null;$output=null;
    try{
        // Exclusive creation also rejects a file/symlink created after path validation.
        // Readers must verify the footer/digest: visibility of this copy is not atomic.
        $output=@fopen($destination,'xb');if(!$output){throw new \RuntimeException('contract_export_exists');}
        $input=fopen($temporary,'rb');if(!$input){throw new \RuntimeException('contract_export_io');}
        if(stream_copy_to_stream($input,$output,33554433)!==$verified['bytes']||!fflush($output)||!fsync($output)){throw new \RuntimeException('contract_export_io');}
        fclose($output);$output=null;clearstatcache(true,$destination);
        $published=verify_operating_export($destination);
        if($published!==$verified){throw new \RuntimeException('contract_export_inconsistent');}
        return $published;
    }finally{
        if(is_resource($input)){fclose($input);}if(is_resource($output)){fclose($output);}umask($oldMask);
        // On failure keep both paths for operator inspection; never overwrite/retry them.
    }
}
function export_operating_owner($db,$owner,$destination){
    operating_export_path($destination);$oldMask=umask(0077);$temporary=$destination.'.partial-'.bin2hex(random_bytes(8));$f=null;
    try{
        $f=fopen($temporary,'xb');if(!$f){throw new \RuntimeException('contract_export_io');}chmod($temporary,0600);
        $audit=audit_operating_owner($db,$owner,false);$p=$db->prefix.'ac_exp_operating_';
        $counts=['reviews'=>$audit['reviews'],'requests'=>$audit['requests'],'apps'=>$audit['applications'],'approvals'=>$audit['approvals'],'events'=>$audit['events'],'effects'=>$audit['events']];
        $header=['format'=>'operating-private-export-v1','owner_id'=>$owner,'schema_hash'=>operating_storage_marker($db,'ready')['schema_hash'],'counts'=>$counts];
        $hash=hash_init('sha256');$bytes=0;$records=0;
        $write=static function($d,$hashed=true)use($f,$hash,&$bytes){$line=json_encode($d,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";if(strlen($line)>2097152||$bytes+strlen($line)>33554432){throw new \RuntimeException('contract_export_limit');}$left=$line;while($left!==''){$n=fwrite($f,$left);if($n===false||$n===0){throw new \RuntimeException('contract_export_io');}$left=substr($left,$n);}if($hashed){hash_update($hash,$line);}$bytes+=strlen($line);};
        $write($header);
        $queries=['reviews'=>"SELECT * FROM {$p}reviews WHERE owner_id=%d ORDER BY review_id",'requests'=>"SELECT * FROM {$p}requests WHERE owner_id=%d ORDER BY request_id",'apps'=>"SELECT * FROM {$p}apps WHERE owner_id=%d ORDER BY proposal_id",
            'approvals'=>"SELECT a.* FROM {$p}approvals a JOIN {$p}reviews r ON r.review_id=a.review_id WHERE r.owner_id=%d ORDER BY a.review_id",
            'events'=>"SELECT e.* FROM {$db->prefix}ac_exp_source_events e JOIN {$p}apps a ON e.event_id=a.event_id OR e.event_id=a.recovery_event WHERE a.owner_id=%d ORDER BY e.event_id",
            'effects'=>"SELECT f.* FROM {$db->prefix}ac_exp_source_effects f JOIN {$p}apps a ON f.event_id=a.event_id OR f.event_id=a.recovery_event WHERE a.owner_id=%d ORDER BY f.event_id"];
        foreach($queries as $table=>$sql){$rows=$db->get_results($db->prepare($sql,$owner),ARRAY_A);if($db->last_error||count($rows??[])!==$counts[$table]){throw new \RuntimeException('contract_export_inconsistent');}foreach($rows as $row){$write(['table'=>$table,'row'=>$row]);$records++;}}
        $write(['format'=>'operating-private-export-footer-v1','records'=>$records,'data_bytes'=>$bytes,'data_sha256'=>hash_final($hash)],false);
        if(!fflush($f)||!fsync($f)){throw new \RuntimeException('contract_export_io');}fclose($f);$f=null;
        $verified=publish_operating_export($temporary,$destination);
        // Remove only our staging copy after independent verification of the destination.
        unlink($temporary);return $verified;
    }finally{if(is_resource($f)){fclose($f);}umask($oldMask);}
}
