<?php
// Read-only reporting. Keep all values parameterized; identifiers come from this allowlist.
function rc_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function rc_period($input){
 $year=isset($input['ano'])&&is_scalar($input['ano'])?(int)$input['ano']:(int)date('Y');
 if($year<2000||$year>2100)$year=(int)date('Y');
 $month=isset($input['mes'])&&is_string($input['mes'])?$input['mes']:date('m');
 if($month==='%'||$month==='all')$month='all';
 elseif(!ctype_digit($month)||(int)$month<1||(int)$month>12)$month=date('m');
 else $month=sprintf('%02d',(int)$month);
 $from=sprintf('%04d-%02d-01',$year,$month==='all'?1:(int)$month);
 $until=(new DateTimeImmutable($from))->modify($month==='all'?'+1 year':'+1 month')->format('Y-m-d');
 return array('year'=>$year,'month'=>$month,'from'=>$from,'until'=>$until,'loglike'=>$month==='all'?'%/'.$year.'%':'%/'.$month.'/'.$year.'%');
}
function rc_query($db,$sql,$params=array()){
 $q=$db->prepare($sql);if(!$q)throw new RuntimeException('Consulta indisponível');
 if($params){$types=str_repeat('s',count($params));$refs=array($types);foreach($params as $i=>$value){$params[$i]=(string)$value;$refs[]=&$params[$i];}call_user_func_array(array($q,'bind_param'),$refs);}
 if(!$q->execute())throw new RuntimeException('Consulta indisponível');
 $result=$q->get_result();$rows=$result->fetch_all(MYSQLI_ASSOC);$q->close();return $rows;
}
function rc_kind($kind){return in_array($kind,array('instalacao','reativacao','cancelamento','adicional'),true)?$kind:'todos';}
function rc_sort_events($a,$b){return strcmp($b['date'],$a['date']);}
function rc_money($v){return 'R$ '.number_format((float)$v,2,',','.');}
function rc_date($v){$stamp=strtotime($v);return $stamp?date('d/m/Y H:i',$stamp):'—';}
function rc_event($row,$kind,$date,$detail=''){
 return array('name'=>$row['nome'],'login'=>$row['login'],'active'=>($row['cli_ativado']??'s')==='s','uuid'=>$row['uuid_cliente'],'kind'=>$kind,'date'=>$date,'segment'=>isset($row['segment'])&&trim((string)$row['segment'])!==''?$row['segment']:'Não informado','detail'=>$detail);
}

// Render locally: charts do not depend on a CDN or JavaScript initialization.
function rc_pie($values,$title,$details=array()){
 arsort($values);$total=array_sum($values);
 if(!$total)return '<p class="rc-empty">Nenhum cliente nesse filtro.</p>';
 $colors=array('#1768e0','#059669','#7c3aed','#e84b68','#0891b2','#ea9a17','#526680','#db60ab','#5daa3c','#7050b6','#188c92','#c15c29');
 $svg='<svg class="rc-pie" viewBox="0 0 240 240" role="img" aria-label="'.rc_h($title).'"><title>'.rc_h($title).'</title>';
 $legend='<ul class="rc-pie-legend">';$angle=-M_PI/2;$i=0;
 foreach($values as $label=>$count){
  $color=$colors[$i++%count($colors)];$next=$angle+2*M_PI*$count/$total;$pct=100*$count/$total;
  $tooltip=rc_h($label.' · '.$count.' cliente(s) · '.number_format($pct,1,',','.').'%');
  $payload=rc_h(json_encode(array('label'=>$label,'count'=>$count,'rows'=>$details[$label]??array()),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
  $attr=' data-rc-pie="'.$payload.'"';
  $svg.='<a href="#rc-plan-tables"'.$attr.' aria-label="'.$tooltip.'">';
  if($count==$total)$svg.='<circle cx="120" cy="120" r="110" fill="'.$color.'"><title>'.$tooltip.'</title></circle>';
  else{
   $x1=120+110*cos($angle);$y1=120+110*sin($angle);$x2=120+110*cos($next);$y2=120+110*sin($next);
   $d=sprintf('M120 120 L%.5F %.5F A110 110 0 %d 1 %.5F %.5F Z',$x1,$y1,$pct>50?1:0,$x2,$y2);
   $svg.='<path d="'.$d.'" fill="'.$color.'" stroke="white" stroke-width="1"><title>'.$tooltip.'</title></path>';
  }
  $svg.='</a>';
  $linkRows=$details[$label]??array();$direct=count($linkRows)===1&&$title==='Clientes por plano';
  $href=$direct?$linkRows[0]['url']:'#rc-plan-tables';
  $legend.='<li><a href="'.rc_h($href).'"'.($direct?' data-rc-direct="true"':'').$attr.'><i style="background:'.$color.'"></i><span>'.rc_h($label).'</span><b>'.$count.' <small>'.number_format($pct,1,',','.').'%</small></b></a></li>';$angle=$next;
 }
 return '<div class="rc-pie-content">'.$svg.'</svg>'.$legend.'</ul></div><p class="rc-selection-note rc-muted" data-rc-selection aria-live="polite">Clique ou arraste nas fatias para filtrar um ou vários planos. Clique no nome do plano para abrir a Busca.</p>';
}

function rc_plan_clients_url($plan,$state){
 return '/admin/addons/busca_inteligente/index.php?'.http_build_query(array('busca'=>$plan.($state==='desativados'?'+desativado':''),'organizar'=>'c.nome','filtro'=>'','num_registros'=>25,'pagina'=>1),'','&',PHP_QUERY_RFC3986);
}

function rc_client_url($login,$active){
 $params=$active?array('busca'=>$login):array('busca'=>'desativado','disabled_q'=>$login);
 return '/admin/addons/busca_inteligente/index.php?'.http_build_query($params,'','&',PHP_QUERY_RFC3986);
}
