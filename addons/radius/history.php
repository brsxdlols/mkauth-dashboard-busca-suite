<?php
// Bounded, on-demand history lookup. Never called by the periodic refresh.
function radius_latest_history($login) {
    $base=radius_log_path(); $files=array($base);
    for($i=1;$i<=14;$i++) foreach(array($base.'.'.$i,$base.'.'.$i.'.gz') as $p) if(is_readable($p)) $files[]=$p;
    $start=microtime(true);$bytes=0;$searched=0;
    foreach($files as $file) {
        $gzip=substr($file,-3)==='.gz';
        $h=$gzip?@gzopen($file,'rb'):@fopen($file,'rb');if(!$h)continue;
        $last=null;$complete=true;
        while(!($gzip?gzeof($h):feof($h))) {
            if($bytes>=67108864 || microtime(true)-$start>2){$complete=false;break;}
            $line=$gzip?gzgets($h,65536):fgets($h,65536);if($line===false)break;
            $bytes+=strlen($line);
            if(radius_extract_login($line)===$login) $last=trim($line);
        }
        $gzip?gzclose($h):fclose($h);$searched++;
        if(!$complete)return array('row'=>null,'note'=>'Busca histórica atingiu o limite seguro; último registro não confirmado.');
        if($last!==null){
            $last=preg_replace('/\[([^\/\]]+)\/[^\]]*\]/','[$1/***]',$last);
            $last=preg_replace('/((?:User-Password|Cleartext-Password|CHAP-Password)\s*[:=]\s*)("[^"]*"|\S+)/i','$1***',$last);
            $type=radius_log_type($last);$labels=radius_type_labels();
            return array('row'=>array('type'=>$type,'label'=>$labels[$type],'line'=>$last,'login'=>$login),'note'=>'Último registro localizado no histórico disponível.');
        }
    }
    return array('row'=>null,'note'=>'Nenhum registro deste login nos '.$searched.' arquivos disponíveis (atual e até 14 rotações). O log pode ter sido removido pela retenção.');
}
