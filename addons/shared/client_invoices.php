<?php
function mka_client_open_invoices($db,$uuid,$scope,$page=1) {
    $q=$db->prepare("SELECT c.login,c.nome,c.conta FROM sis_cliente c WHERE c.uuid_cliente=? AND ($scope) LIMIT 1");
    $q->bind_param('s',$uuid);$q->execute();$client=$q->get_result()->fetch_assoc();
    if(!$client)throw new DomainException('Cliente não disponível para este usuário.');
    $q=$db->prepare("SELECT COUNT(*) FROM sis_lanc WHERE login=? AND status IN ('aberto','vencido') AND COALESCE(deltitulo,0)=0");
    $q->bind_param('s',$client['login']);$q->execute();$total=(int)$q->get_result()->fetch_row()[0];
    $size=5;$pages=max(1,(int)ceil($total/$size));$page=min(max(1,(int)$page),$pages);$offset=($page-1)*$size;
    $q=$db->prepare("SELECT l.id,l.uuid_lanc,l.recibo,l.datavenc,l.valor AS valor_original,(COALESCE((SELECT vt.valor FROM vtab_titulos vt WHERE vt.titulo=l.id LIMIT 1),l.valor)+COALESCE((SELECT SUM(CASE WHEN m.tipo='acrescimo' THEN m.valor WHEN m.tipo='desconto' THEN -m.valor ELSE 0 END) FROM sis_mlanc m WHERE m.idlanc=l.id AND COALESCE(m.deltitulo,0)=0),0)) AS valor,l.obs,(l.status='vencido' OR l.datavenc<CURDATE()) AS overdue,EXISTS(SELECT 1 FROM sis_qrpix pix WHERE pix.titulo=l.id AND TRIM(pix.qrcode)<>'') AS has_pix FROM sis_lanc l WHERE l.login=? AND l.status IN ('aberto','vencido') AND COALESCE(l.deltitulo,0)=0 ORDER BY l.datavenc ASC,l.id ASC LIMIT $size OFFSET $offset");
    $q->bind_param('s',$client['login']);$q->execute();
    return array('client'=>$client,'invoices'=>$q->get_result()->fetch_all(MYSQLI_ASSOC),'total'=>$total,'page'=>$page,'pages'=>$pages);
}

function mka_client_invoice_interest_methods(){
    $families=array(
        array(array('MKAC_TITULO','FMMMHAKMKATKTMAAHMKKAKUAHMKAUMUKH'),array('MKAC_MATH','FMUKMAHKAAMKUMAAUKHAUAKMAAHMAMKKH'),array('MKAC_MATH','FMHMUKAKMMMKTMAATMUAHMHAMAHAHAUKT'),array('MKAC_DATA','FAUMTKAAMKUMMKMMAAUKHATKMMKAHKUAU'),array('MKAC_FORMATACAO','FMKKAMAATATAMATMUMHMUMTMAMHAKMTAU')),
        array(array('MKAC_TITULO','FKTMTKTKTMMMUKTAKAHKUKUATAMAHMKKA'),array('MKAC_MATH','FAMAHAUKHMMMHMHMUKUMTMKAMKHAKMTAK'),array('MKAC_MATH','FMMKUMUMTAKMMMHAMAUKHAKMKKMKTMHKM'),array('MKAC_DATA','FMKATKUAKMTKTMKKUKUMTKTKHMTKAAMMK'),array('MKAC_FORMATACAO','FKMAHAMMAKHKHMUMUMHMKMAKHAMAKKMAM'))
    );
    foreach($families as $methods){$available=true;foreach($methods as $method)if(!is_callable($method)){$available=false;break;}if($available)return $methods;}
    throw new DomainException('O cálculo de juros não está disponível nesta versão do MK-AUTH. Consulte o recebimento nativo.');
}

// Call only the native read-only financial methods used by receber.hhvm.
function mka_client_invoice_interest($db,$uuid,$scope,$id) {
    $q=$db->prepare("SELECT l.id,l.datavenc,c.conta FROM sis_lanc l INNER JOIN sis_cliente c ON c.login=l.login WHERE c.uuid_cliente=? AND ($scope) AND l.id=? AND l.status IN ('aberto','vencido') AND COALESCE(l.deltitulo,0)=0 LIMIT 1");
    $q->bind_param('si',$uuid,$id);$q->execute();$invoice=$q->get_result()->fetch_assoc();
    if(!$invoice)throw new DomainException('Fatura não disponível para este cliente.');
    $account=(int)$invoice['conta'];
    $q=$db->prepare('SELECT multa,juros FROM sis_boleto WHERE id=? LIMIT 1');$q->bind_param('i',$account);$q->execute();$rates=$q->get_result()->fetch_assoc();
    if(!$rates||$rates['multa']===null||$rates['juros']===null)throw new DomainException('A conta do cliente não possui taxas disponíveis para calcular.');
    $methods=mka_client_invoice_interest_methods();
    $today=date('Y-m-d');$due=substr((string)$invoice['datavenc'],0,10);
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$due)||strtotime($due)===false)throw new DomainException('Vencimento inválido para o cálculo.');
    global $LOADMYSQL;$previous=$LOADMYSQL??null;$LOADMYSQL=$db;
    try {
        $base=(float)call_user_func($methods[0],(int)$invoice['id']);
        $fine=(float)call_user_func($methods[1],$account,$due,$base,$today);
        $interest=(float)call_user_func($methods[2],$account,$due,$base,$today);
        $days=$due<$today?(int)call_user_func($methods[3],$due,$today):0;
        $fineRate=(float)call_user_func($methods[4],$rates['multa']);$dailyRate=(float)call_user_func($methods[4],$rates['juros']);
    } finally {$LOADMYSQL=$previous;}
    foreach(array($base,$fine,$interest,$fineRate,$dailyRate) as $number)if(!is_finite($number)||$number<0)throw new DomainException('Valores inválidos na configuração do cálculo.');
    $total=round($base+$fine+$interest,2);$adjustment=round($total-round($base,2)-round($fine,2)-round($interest,2),2);
    return array('id'=>(int)$invoice['id'],'as_of'=>$today,'due_date'=>$due,'days_late'=>$days,'base'=>number_format($base,2,'.',''),'fine_rate'=>$fineRate,'daily_rate'=>$dailyRate,'fine'=>number_format($fine,2,'.',''),'interest'=>number_format($interest,2,'.',''),'rounding_adjustment'=>number_format($adjustment,2,'.',''),'total'=>number_format($total,2,'.',''));
}

function mka_client_invoice_pix($db,$uuid,$scope,$id,$render=true) {
    $q=$db->prepare("SELECT pix.qrcode FROM sis_qrpix pix INNER JOIN sis_lanc l ON pix.titulo=l.id INNER JOIN sis_cliente c ON c.login=l.login WHERE c.uuid_cliente=? AND ($scope) AND l.id=? AND l.status IN ('aberto','vencido') AND COALESCE(l.deltitulo,0)=0 LIMIT 1");
    $q->bind_param('si',$uuid,$id);$q->execute();$row=$q->get_result()->fetch_assoc();
    if(!$row||trim((string)$row['qrcode'])==='')throw new DomainException('PIX não disponível para esta fatura.');
    $code=trim($row['qrcode']);if(strlen($code)>8000)throw new DomainException('Código PIX inválido.');
    $image=null;
    if($render&&is_callable(array('MKAC_QRCODE','FMTMAMMMMKTKAMMMTAUKHAMKUMHMTMTKH'))){
        try{$candidate=MKAC_QRCODE::FMTMAMMMMKTKAMMMTAUKHAMKUMHMTMTKH($code);if(is_string($candidate)&&strlen($candidate)<2000000&&preg_match('~^data:image/png;base64,[A-Za-z0-9+/=]+$~D',$candidate))$image=$candidate;}catch(Throwable $error){error_log('Invoice PIX image unavailable');}
    }
    return array('code'=>$code,'image'=>$image);
}

function mka_client_invoice_pdf($db,$uuid,$scope,$id,$directory=null) {
    $q=$db->prepare("SELECT l.uuid_lanc FROM sis_lanc l INNER JOIN sis_cliente c ON c.login=l.login WHERE c.uuid_cliente=? AND ($scope) AND l.id=? AND l.status IN ('aberto','vencido') AND COALESCE(l.deltitulo,0)=0 LIMIT 1");
    $q->bind_param('si',$uuid,$id);$q->execute();$row=$q->get_result()->fetch_assoc();
    if(!$row||!preg_match('/^[a-zA-Z0-9-]{16,64}$/D',(string)$row['uuid_lanc']))throw new DomainException('Boleto não disponível para este cliente.');
    $directory=realpath($directory??(__DIR__.'/../../../print_pdf/boletos'));
    if(!$directory||!is_writable($directory)||!method_exists('MKAC_PDF','FMTMAMMMMKTKAMMMTAUKHAMKUMHMTMTKH'))throw new RuntimeException('Native PDF generator unavailable');
    $file=$directory.'/'.$row['uuid_lanc'].'.pdf';$lockPath=$file.'.suite.lock';
    if(is_link($file)||is_link($lockPath))throw new RuntimeException('Unsafe PDF path');
    $lock=fopen($lockPath,'c');if(!$lock)throw new RuntimeException('PDF lock unavailable');
    try {
        if(!flock($lock,LOCK_EX|LOCK_NB))throw new DomainException('PDF em preparação. Aguarde alguns segundos e tente novamente.');
        // Native generator uses integer title IDs and the installation's own PDF layout.
        if(!is_file($file)||filemtime($file)<time()-30){
            $job=new MKAC_PDF();$job->MKAKUKTAMKTAUKTAUKTAKAHAUMAAUMAKUMT='boleto';$job->MKAMMAUATAKMHMHMMMTAKMMAHAUKMKHKAAU=array('titulo'=>(int)$id);$job->MKAMAATAHATMUMHKUAKATMKMHKUKAMUKAKA=$file;$job->FMTMAMMMMKTKAMMMTAUKHAMKUMHMTMTKH();
        }
        $deadline=microtime(true)+15;
        do {clearstatcache(true,$file);if(is_file($file)&&filesize($file)>5){$start=file_get_contents($file,false,null,0,5);$tail=file_get_contents($file,false,null,max(0,filesize($file)-1024));if($start==='%PDF-'&&strpos($tail,'%%EOF')!==false)return $file;}usleep(200000);}while(microtime(true)<$deadline);
        throw new DomainException('PDF ainda em preparação. Aguarde alguns segundos e tente novamente.');
    }finally{flock($lock,LOCK_UN);fclose($lock);}
}
