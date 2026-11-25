<?php
// One native stock allocation per physical unit. No schema changes to vendor tables.
function mka_equipment_schema($db) {
    foreach (array(
        "CREATE TABLE IF NOT EXISTS suite_equipment_clients(uuid_cliente VARCHAR(64) NOT NULL PRIMARY KEY,revision INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS suite_equipment_items(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,uuid_cliente VARCHAR(64) NOT NULL,idprod INT NOT NULL,allocation_id INT NOT NULL,serial VARCHAR(255) NOT NULL DEFAULT '',comodato VARCHAR(3) NOT NULL DEFAULT 'nao',position INT NOT NULL DEFAULT 0,UNIQUE KEY native_allocation(allocation_id),KEY client_items(uuid_cliente,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS suite_equipment_operations(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,uuid_cliente VARCHAR(64) NOT NULL,operator VARCHAR(64) NOT NULL,request_key VARCHAR(64) NOT NULL,payload_hash CHAR(64) NOT NULL,revision INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY once_only(uuid_cliente,request_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ) as $sql) if (!$db->query($sql)) throw new RuntimeException('Não foi possível preparar os equipamentos.');
}
function mka_equipment_query($db,$sql,$types='',$values=array()) {
    $q=$db->prepare($sql);
    if (!$q) throw new RuntimeException('Consulta de equipamentos indisponível.',(int)$db->errno);
    if ($types!=='') { $args=array($types); foreach($values as $i=>$v) $args[]=&$values[$i]; call_user_func_array(array($q,'bind_param'),$args); }
    if (!$q->execute()) throw new RuntimeException('Não foi possível gravar os equipamentos.',(int)$q->errno);
    return $q;
}
function mka_equipment_rows($db,$uuid,$login,$lock=false) {
    // Keep broken native links visible, so a concurrent native removal is never credited twice.
    return mka_equipment_query($db,"SELECT e.id,e.idprod,e.allocation_id,e.serial,e.comodato,e.position,p.nome,
        a.id native_id,a.idprod native_product_id,a.qtdcli,a.datains,a.cliente FROM suite_equipment_items e
        LEFT JOIN sis_produto p ON p.id=e.idprod LEFT JOIN sis_prodcliente a ON a.id=e.allocation_id
        WHERE e.uuid_cliente=? ORDER BY e.position,e.id".($lock?' FOR UPDATE':''),'s',array($uuid))->get_result()->fetch_all(MYSQLI_ASSOC);
}
function mka_equipment_normalize($rows) {
    if (!is_array($rows) || count($rows)>100) throw new InvalidArgumentException('Use no máximo 100 equipamentos por cliente.');
    $result=array();$ids=array();
    foreach($rows as $row) {
        if (!is_array($row) || !isset($row['product_id']) || !is_scalar($row['product_id']) || !ctype_digit((string)$row['product_id']) || (int)$row['product_id']<1) throw new InvalidArgumentException('Selecione o produto de cada linha.');
        $id=isset($row['id'])?$row['id']:0;
        if (!is_scalar($id) || !ctype_digit((string)$id) || (int)$id<0) throw new InvalidArgumentException('Linha inválida.');
        $id=(int)$id;
        if ($id && isset($ids[$id])) throw new InvalidArgumentException('Linha de equipamento repetida.');
        if ($id) $ids[$id]=true;
        $serial=isset($row['serial'])?$row['serial']:'';
        if (!is_string($serial) || strlen($serial)>255 || preg_match('/[\x00-\x1f\x7f]/',$serial)) throw new InvalidArgumentException('Informe um MAC/serial válido de até 255 bytes.');
        $comodato=isset($row['comodato'])?$row['comodato']:'nao';
        if (!in_array($comodato,array('sim','nao'),true)) throw new InvalidArgumentException('Comodato inválido.');
        $result[]=array('id'=>$id,'product_id'=>(int)$row['product_id'],'serial'=>trim($serial),'comodato'=>$comodato);
    }
    return $result;
}
function mka_equipment_valid_allocation($row,$login) {
    return $row['native_id']!==null && (int)$row['native_product_id']===(int)$row['idprod'] && (string)$row['cliente']===(string)$login && (int)$row['qtdcli']===1;
}
function mka_equipment_save($db,$uuid,$login,$operator,$revision,$key,$input) {
    for($attempt=0;$attempt<3;$attempt++) {
        try {return mka_equipment_save_attempt($db,$uuid,$login,$operator,$revision,$key,$input);}
        catch(Throwable $e) {
            if(!in_array((int)$e->getCode(),array(1205,1213),true))throw $e;
            if($attempt===2)throw new UnexpectedValueException('Outra movimentação está usando o estoque. Atualize o saldo e tente novamente.',0,$e);
            usleep(50000*($attempt+1));
        }
    }
}
function mka_equipment_save_attempt($db,$uuid,$login,$operator,$revision,$key,$input) {
    if(strlen($login)>32 || strlen($operator)>32)throw new InvalidArgumentException('Login maior que o suportado pelo estoque nativo.');
    if (!preg_match('/^[a-f0-9]{32,64}$/D',$key)) throw new InvalidArgumentException('Identificador de gravação inválido.');
    $rows=mka_equipment_normalize($input);
    $hash=hash('sha256',json_encode(array($revision,$rows)));
    if(!$db->begin_transaction())throw new RuntimeException('Não foi possível iniciar a movimentação.');
    try {
        $client=mka_equipment_query($db,'SELECT login FROM sis_cliente WHERE uuid_cliente=? FOR UPDATE','s',array($uuid))->get_result()->fetch_assoc();
        if(!$client || (string)$client['login']!==(string)$login)throw new UnexpectedValueException('O cadastro do cliente mudou. Reabra a edição.');
        mka_equipment_query($db,'INSERT IGNORE INTO suite_equipment_clients(uuid_cliente) VALUES(?)','s',array($uuid));
        $state=mka_equipment_query($db,'SELECT revision FROM suite_equipment_clients WHERE uuid_cliente=? FOR UPDATE','s',array($uuid))->get_result()->fetch_assoc();
        $old=mka_equipment_query($db,'SELECT payload_hash,revision FROM suite_equipment_operations WHERE uuid_cliente=? AND request_key=?','ss',array($uuid,$key))->get_result()->fetch_assoc();
        if ($old) {
            if (!hash_equals($old['payload_hash'],$hash)) throw new UnexpectedValueException('Esta gravação já foi usada com outros dados. Recarregue os equipamentos.');
            if(!$db->commit())throw new RuntimeException('Não foi possível confirmar a gravação.');return (int)$old['revision'];
        }
        if ((int)$state['revision']!==(int)$revision) throw new UnexpectedValueException('Outro operador alterou os equipamentos. Recarregue a lista antes de salvar.');
        $current=mka_equipment_rows($db,$uuid,$login,true);$byId=array();$products=array();$kept=array();
        foreach($current as $row) {$byId[(int)$row['id']]=$row;$products[(int)$row['idprod']]=true;}
        foreach($rows as $row) {
            $products[$row['product_id']]=true;
            if ($row['id']) {
                if (!isset($byId[$row['id']])) throw new UnexpectedValueException('Uma linha não pertence a este cliente. Recarregue a lista.');
                $kept[$row['id']]=true;
                if (!mka_equipment_valid_allocation($byId[$row['id']],$login)) throw new UnexpectedValueException('Um vínculo foi alterado pelo estoque nativo. Remova a linha desatualizada, salve e adicione novamente.');
            }
        }
        // Consistent product order prevents cross-client deadlocks on stock rows.
        $productIds=array_keys($products);sort($productIds,SORT_NUMERIC);$stock=array();$delta=array();
        foreach($productIds as $id) {
            $p=mka_equipment_query($db,'SELECT id,nome,ativo FROM sis_produto WHERE id=? FOR UPDATE','i',array($id))->get_result()->fetch_assoc();
            $s=mka_equipment_query($db,'SELECT id,estoque FROM sis_estoque WHERE idprod=? ORDER BY id FOR UPDATE','i',array($id))->get_result()->fetch_all(MYSQLI_ASSOC);
            if (!$p || count($s)!==1 || $s[0]['estoque']===null) throw new UnexpectedValueException('Produto sem um saldo de estoque único. Revise o cadastro do produto.');
            $stock[$id]=array('row'=>$s[0],'product'=>$p);$delta[$id]=0;
        }
        foreach($current as $row) {
            $next=null;foreach($rows as $candidate)if($candidate['id']===(int)$row['id']){$next=$candidate;break;}
            if ((!$next || $next['product_id']!==(int)$row['idprod']) && mka_equipment_valid_allocation($row,$login)) $delta[(int)$row['idprod']]++;
        }
        foreach($rows as $row) {
            if (!$row['id'] || $row['product_id']!==(int)$byId[$row['id']]['idprod']) {
                if ($stock[$row['product_id']]['product']['ativo']!=='sim') throw new UnexpectedValueException('O produto selecionado está inativo.');
                $delta[$row['product_id']]--;
            }
        }
        foreach($delta as $id=>$change) {
            if ((int)$stock[$id]['row']['estoque']+$change<0) throw new UnexpectedValueException('Estoque insuficiente para '.$stock[$id]['product']['nome'].'. Nenhuma unidade foi movimentada.');
            if (!$change) continue;
            $q=mka_equipment_query($db,'UPDATE sis_estoque SET estoque=estoque+? WHERE id=? AND estoque+?>=0','iii',array($change,(int)$stock[$id]['row']['id'],$change));
            if ($q->affected_rows!==1) throw new UnexpectedValueException('O saldo mudou durante a gravação. Atualize e tente novamente.');
        }
        // Remove only suite-owned native allocations; never touch unrelated products.
        foreach($current as $row) {
            $next=null;foreach($rows as $candidate)if($candidate['id']===(int)$row['id']){$next=$candidate;break;}
            if (!$next || $next['product_id']!==(int)$row['idprod']) {
                if (mka_equipment_valid_allocation($row,$login)) {
                    $q=mka_equipment_query($db,'DELETE FROM sis_prodcliente WHERE id=? AND idprod=? AND cliente=? AND qtdcli=1','iis',array((int)$row['allocation_id'],(int)$row['idprod'],$login));
                    if($q->affected_rows!==1)throw new UnexpectedValueException('O vínculo nativo mudou. Atualize a lista.');
                    mka_equipment_history($db,(int)$row['idprod'],$operator,'1 unidade devolvida pelo cliente '.$login);
                } elseif ($row['native_id']!==null) throw new UnexpectedValueException('Vínculo nativo inconsistente. Revise o estoque antes de remover.');
                mka_equipment_query($db,'DELETE FROM suite_equipment_items WHERE id=? AND uuid_cliente=?','is',array((int)$row['id'],$uuid));
            }
        }
        foreach($rows as $position=>$row) {
            if (!$row['id'] || $row['product_id']!==(int)$byId[$row['id']]['idprod']) {
                mka_equipment_query($db,'INSERT INTO sis_prodcliente(idprod,cliente,qtdcli,usuario,datains) VALUES(?,?,1,?,NOW())','iss',array($row['product_id'],$login,$operator));$allocation=(int)$db->insert_id;
                mka_equipment_query($db,'INSERT INTO suite_equipment_items(uuid_cliente,idprod,allocation_id,serial,comodato,position) VALUES(?,?,?,?,?,?)','siissi',array($uuid,$row['product_id'],$allocation,$row['serial'],$row['comodato'],$position));
                mka_equipment_history($db,$row['product_id'],$operator,'1 unidade entregue ao cliente '.$login);
            } else mka_equipment_query($db,'UPDATE suite_equipment_items SET serial=?,comodato=?,position=? WHERE id=? AND uuid_cliente=?','ssiis',array($row['serial'],$row['comodato'],$position,$row['id'],$uuid));
        }
        $newRevision=(int)$state['revision']+1;
        mka_equipment_query($db,'UPDATE suite_equipment_clients SET revision=? WHERE uuid_cliente=?','is',array($newRevision,$uuid));
        mka_equipment_query($db,'INSERT INTO suite_equipment_operations(uuid_cliente,operator,request_key,payload_hash,revision) VALUES(?,?,?,?,?)','ssssi',array($uuid,$operator,$key,$hash,$newRevision));
        if(!$db->commit())throw new RuntimeException('Não foi possível confirmar a gravação.');return $newRevision;
    } catch(Throwable $e) {$db->rollback();throw $e;}
}
function mka_equipment_history($db,$id,$operator,$message) {
    mka_equipment_query($db,'INSERT INTO sis_prodhistorico(idprod,usuario,historico,data) VALUES(?,?,?,NOW())','iss',array($id,$operator,$message));
}
function mka_equipment_state($db,$client) {
    $uuid=$client['uuid_cliente'];$login=$client['login'];
    $q=mka_equipment_query($db,'SELECT revision FROM suite_equipment_clients WHERE uuid_cliente=?','s',array($uuid))->get_result()->fetch_assoc();
    $rows=mka_equipment_rows($db,$uuid,$login);$items=array();
    foreach($rows as $r)$items[]=array('id'=>(int)$r['id'],'product_id'=>(int)$r['idprod'],'name'=>(string)$r['nome'],'serial'=>$r['serial'],'comodato'=>$r['comodato'],'inserted_at'=>$r['datains'],'stale'=>!mka_equipment_valid_allocation($r,$login));
    $products=$db->query("SELECT p.id,p.nome,p.ativo,COUNT(s.id) stock_rows,MIN(s.estoque) available FROM sis_produto p LEFT JOIN sis_estoque s ON s.idprod=p.id GROUP BY p.id,p.nome,p.ativo ORDER BY p.nome,p.id")->fetch_all(MYSQLI_ASSOC);
    $catalog=array();foreach($products as $p)$catalog[]=array('id'=>(int)$p['id'],'name'=>$p['nome'],'active'=>$p['ativo']==='sim','available'=>(int)$p['stock_rows']===1 && $p['available']!==null?(int)$p['available']:null);
    // Keep native products visible without importing them or debiting stock again.
    $other=mka_equipment_query($db,"SELECT a.id,a.idprod product_id,p.nome name,a.qtdcli quantity,a.datains inserted_at FROM sis_prodcliente a LEFT JOIN sis_produto p ON p.id=a.idprod WHERE a.cliente=? AND NOT EXISTS (SELECT 1 FROM suite_equipment_items e WHERE e.allocation_id=a.id AND e.uuid_cliente=?) ORDER BY a.datains,a.id",'ss',array($login,$uuid))->get_result()->fetch_all(MYSQLI_ASSOC);
    return array('items'=>$items,'other_products'=>$other,'products'=>$catalog,'revision'=>$q?(int)$q['revision']:0);
}
function mka_equipment_contract_html($db,$login) {
    $q=mka_equipment_query($db,'SELECT uuid_cliente,equipamento,mac_serial,comodato FROM sis_cliente WHERE login=? LIMIT 1','s',array($login));$client=$q->get_result()->fetch_assoc();
    if(!$client)return '';
    $items=array();foreach(mka_equipment_rows($db,$client['uuid_cliente'],$login) as $r)if(mka_equipment_valid_allocation($r,$login))$items[]=array('name'=>$r['nome'],'serial'=>$r['serial'],'comodato'=>$r['comodato']);
    $legacy=trim((string)$client['equipamento']);$matched=false;
    $fold=function($value){$value=trim((string)$value);if(class_exists('Normalizer',false))$value=Normalizer::normalize($value,Normalizer::FORM_KC);$value=function_exists('mb_strtolower')?mb_strtolower($value,'UTF-8'):strtolower($value);return preg_replace('/[_\s-]+/u',' ',$value);};
    foreach($items as $item)if($fold($item['name'])===$fold($legacy) && (string)$item['serial']===(string)$client['mac_serial']){$matched=true;break;}
    if($legacy!=='' && strtolower($legacy)!=='nenhum' && !$matched)array_unshift($items,array('name'=>$client['equipamento'],'serial'=>$client['mac_serial'],'comodato'=>$client['comodato']));
    return mka_equipment_contract_table($items);
}
function mka_equipment_contract_table($items) {
    if(!$items)return '<p>Nenhum equipamento vinculado.</p>';
    $html='<table style="width:100%;border-collapse:collapse"><thead><tr><th>Equipamento</th><th>Quantidade</th><th>MAC/Serial</th><th>Comodato</th></tr></thead><tbody>';
    foreach($items as $r){$html.='<tr>';foreach(array($r['name'],'1',$r['serial'],$r['comodato']==='sim'?'Sim':'Não')as$v)$html.='<td>'.htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8').'</td>';$html.='</tr>';}
    return $html.'</tbody></table>';
}
function mka_equipment_contract_replace($db,$login,$html) {
    if(strpos($html,'%equipamentos%')===false)return $html;
    $charset=$db->character_set_name();$db->set_charset('utf8mb4');
    try {$table=mka_equipment_contract_html($db,$login);}finally{$db->set_charset($charset);}
    // Vendor contract entries emit ISO-8859-1. Their legacy template bytes stay unchanged.
    if(function_exists('iconv'))$table=iconv('UTF-8','ISO-8859-1//TRANSLIT',$table);
    return str_replace('%equipamentos%',$table,$html);
}

