<?php
function off_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function off_arg($name, $default = '') { return isset($_GET[$name]) && is_string($_GET[$name]) ? trim($_GET[$name]) : $default; }
$off_periods = array('todos'=>'Todo o período','hoje'=>'Desconectou hoje','ultimas'=>'Nas últimas X horas','mais'=>'Offline há X horas ou mais','ultimos_dias'=>'Nos últimos X dias','mais_dias'=>'Offline há X dias ou mais','intervalo'=>'Período personalizado');
$off_period = off_arg('periodo', 'todos');
if (!isset($off_periods[$off_period])) $off_period = 'todos';
$off_days = off_arg('dias','1'); $off_hours = off_arg('horas','6'); $off_start = off_arg('inicio'); $off_end = off_arg('fim'); $off_error = '';
function off_period_sql($link) {
 global $off_days, $off_period, $off_hours, $off_start, $off_end, $off_error;
 if ($off_period === 'hoje') return ' AND c.acctstoptime >= CURDATE()';
 if ($off_period === 'ultimas' || $off_period === 'mais') {
  if (!is_numeric($off_hours) || (float)$off_hours <= 0 || (float)$off_hours > 876000 || (float)$off_hours * 3600 < 1) { $off_error = 'Informe uma quantidade de horas válida, maior que zero e até 876000.'; return ' AND 1=0'; }
  $seconds = (int)round((float)$off_hours * 3600);
  return ' AND c.acctstoptime '.($off_period === 'ultimas' ? '>=' : '<=').' DATE_SUB(NOW(), INTERVAL '.$seconds.' SECOND)';
 }
 if ($off_period === 'ultimos_dias' || $off_period === 'mais_dias') {
  if (!is_numeric($off_days) || (float)$off_days < 0.01 || (float)$off_days > 36500) { $off_error = 'Informe um período entre 0,01 e 36500 dias.'; return ' AND 1=0'; }
  $seconds = (int)round((float)$off_days * 86400);
  return ' AND c.acctstoptime '.($off_period === 'ultimos_dias' ? '>=' : '<=').' DATE_SUB(NOW(), INTERVAL '.$seconds.' SECOND)';
 }
 if ($off_period === 'intervalo') {
  $parts = array();
  foreach (array('inicio'=>$off_start,'fim'=>$off_end) as $key=>$value) {
   $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
   if (!$date || $date->format('Y-m-d\TH:i') !== $value) { $off_error = 'Preencha o início e o fim com datas e horários válidos.'; return ' AND 1=0'; }
   $parts[] = 'c.acctstoptime '.($key === 'inicio' ? '>=' : '<=')." '".$date->format('Y-m-d H:i:').($key === 'inicio' ? '00' : '59')."'";
  }
  if ($off_start > $off_end) { $off_error = 'O início deve ser anterior ao fim do período.'; return ' AND 1=0'; }
  return ' AND '.implode(' AND ', $parts);
 }
 return '';
}
function off_filter_form() {
 global $off_days,$off_periods,$off_period,$off_hours,$off_start,$off_end;
 ?>
 <div class="off-period-fields no_print"><label>Desconexão<select name="periodo" id="off-period"><?php foreach($off_periods as $key=>$label): ?><option value="<?= $key ?>" <?= $key === $off_period ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
 <label data-off-hours>Quantidade de horas<input name="horas" type="number" min="0.01" max="876000" step="any" value="<?= off_h($off_hours) ?>"></label>
 <label data-off-days>Quantidade de dias<input name="dias" type="number" min="0.01" max="36500" step="any" value="<?= off_h($off_days) ?>"></label>
 <label data-off-range>De<input name="inicio" type="datetime-local" value="<?= off_h($off_start) ?>"></label><label data-off-range>Até<input name="fim" type="datetime-local" value="<?= off_h($off_end) ?>"></label><button type="submit" class="off-apply">Aplicar filtros</button><a href="index.php">Limpar</a></div>
 <p class="off-help no_print">Hoje: desde a meia-noite do servidor. Últimas X horas: quedas recentes. X horas ou mais: desconexões mais antigas. Cada dia equivale a 24 horas. Digite nome, rua, bairro ou cidade para buscar automaticamente.</p>
 <?php
}

function off_card_url($status) {
 $query = array();
 foreach (array('busca','organizar','periodo','horas','dias','inicio','fim','reconexoes','chamados') as $key) if (isset($_GET[$key]) && is_string($_GET[$key])) $query[$key] = $_GET[$key];
 $query['status'] = $status;
 return 'index.php?'.http_build_query($query);
}

function off_attention_icon() { return ''; }
function off_metric_matches($value, $filter) {
 if ($filter === 'todos') return true;
 if ($value === null) return false;
 return $filter === 'com' ? $value > 0 : $value === 0;
}
function off_metric_order($rows, $metric) {
 usort($rows, function($a,$b) use($metric) {
  $left = $a[$metric] ?? -1; $right = $b[$metric] ?? -1;
  if ($left !== $right) return $right <=> $left;
  $time = strcmp($a['acctstoptime'],$b['acctstoptime']);
  return $time !== 0 ? $time : strcmp($a['username'],$b['username']);
 });
 return $rows;
}
