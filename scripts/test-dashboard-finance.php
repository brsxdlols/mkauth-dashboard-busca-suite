<?php
// Temporary fixtures shadow native financial tables only in this connection.
if(PHP_SAPI!=='cli')exit(1);
error_reporting(E_ERROR);$conn=new mysqli(getenv('MYSQL_HOST')?:'127.0.0.1',getenv('MYSQL_USER')?:'root',getenv('MYSQL_PASS')?:'',getenv('MYSQL_DB')?:'mkradius');
$conn->query("SET SESSION sql_mode=''");
function finance_check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
function fixture_sql($conn,$sql){if(!$conn->query($sql))throw new RuntimeException('Fixture query failed.');}
try{
 fixture_sql($conn,'CREATE TEMPORARY TABLE sis_cliente(login VARCHAR(64),cli_ativado CHAR(1))');
 fixture_sql($conn,'CREATE TEMPORARY TABLE sis_lanc(login VARCHAR(64),valor DECIMAL(12,2),status VARCHAR(20),deltitulo INT,datavenc DATETIME,datapag DATETIME,valorpag DECIMAL(12,2))');
 fixture_sql($conn,'CREATE TEMPORARY TABLE sis_caixa(data DATETIME,entrada DECIMAL(12,2),saida DECIMAL(12,2),historico VARCHAR(64))');
 fixture_sql($conn,'CREATE TEMPORARY TABLE sis_contaspagar(vencimento DATETIME,valor DECIMAL(12,2),status VARCHAR(20))');
 $previous=date('Y-m',strtotime('-1 month'));$current=date('Y-m');$past=date('Y-m',strtotime('-2 month'));$future=date('Y-m',strtotime('+1 month'));
 fixture_sql($conn,"INSERT INTO sis_cliente VALUES('a','s'),('b','n')");
 fixture_sql($conn,"INSERT INTO sis_lanc VALUES('a',45,'aberto',0,'$previous-00',NULL,0),('a',100,'pago',0,'$current-02','$current-02',100),('a',200,'aberto',0,'$current-03',NULL,0),('b',500,'pago',0,'$current-04','$current-04',500),(NULL,50,'pago',0,'$current-05','$current-05',50),('a',900,'aberto',1,'$current-05',NULL,0)");
 fixture_sql($conn,"INSERT INTO sis_caixa VALUES('$past-01',100,0,'normal'),('$previous-00',10,0,'normal'),('$previous-01',1000,200,'normal'),('$current-01',500,0,'emprestimo teste'),('$current-02',700,200,'normal'),('$current-03',100,30,'estorno teste'),('$future-01',999,0,'normal')");
 fixture_sql($conn,"INSERT INTO sis_contaspagar VALUES('$previous-00',30,'aberto'),('$current-02',80,'aberto'),('$current-02',40,'liquidado')");
 $per_meses=2;$limite_ticket=1000;$grupos='';ob_start();require __DIR__.'/../addons/dashboard/graf_periodo.php';ob_end_clean();
 finance_check($previsao_mes_[$previous]==45 && $previsao_mes_[$current]==300,'active-client invoices and deleted/inactive exclusions');
 finance_check($a_receber_mes_[$current]==200 && $contas_a_pagar_[$previous]==30 && $contas_a_pagar_[$current]==80,'unpaid amounts and native zero-day dates');
 finance_check($cont_ticket_[$current]==2 && $ticket_medio_[$current]==='335.00','payment count ignores null logins and preserves global ticket calculation');
 finance_check($tot_entrada_[$current]==1170 && $tot_saida_[$current]==100 && $total_emprestimo_[$current]==500,'original refund and loan arithmetic');
 finance_check($saldo_conta_[$previous]==910 && $saldo_conta_[$current]==1980,'historical balance includes opening amounts and excludes future months');
}finally{$conn->close();}
