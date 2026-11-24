<?php
// Offline example: no database connection, customer records or inventory movements.
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../addons/client_equipment/model.php';
$items=array(
 array('name'=>'Roteador Intelbras','serial'=>'EXEMPLO-RT-001','comodato'=>'sim'),
 array('name'=>'Roteador Intelbras','serial'=>'EXEMPLO-RT-002','comodato'=>'nao'),
 array('name'=>'ONU Fiberhome','serial'=>'EXEMPLO-ONU-001','comodato'=>'sim'),
 array('name'=>'Câmera IP','serial'=>'EXEMPLO-CAM-001','comodato'=>'sim'),
 array('name'=>'Câmera IP','serial'=>'EXEMPLO-CAM-002','comodato'=>'nao'),
 array('name'=>'Câmera IP','serial'=>'EXEMPLO-CAM-003','comodato'=>'nao')
);
?><!doctype html>
<html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Exemplo de equipamentos no contrato</title>
<style>body{margin:0;background:#eee;font:15px/1.5 Arial,sans-serif;color:#222}main{box-sizing:border-box;width:960px;margin:32px auto;padding:42px 48px;background:white;border:1px solid #ddd}h1{margin:0 0 8px;font-size:23px}h2{font-size:17px;margin:30px 0 12px}.example{color:#666;font-size:13px}table{text-align:left;font-size:14px}th,td{text-align:left;padding:12px 10px;border:1px solid #bbb}th{background:#f2f2f2}td:nth-child(2){text-align:center;width:92px}td:last-child{width:90px}footer{margin-top:22px;font-size:13px;color:#555}</style>
<main><div class="example">PRÉVIA · DADOS FICTÍCIOS</div><h1>Equipamentos no contrato</h1><p>Resultado da variável <strong>%equipamentos%</strong></p><h2>Equipamentos vinculados ao cliente</h2>
<?= mka_equipment_contract_table($items) ?>
<footer>6 unidades: 2 roteadores, 1 ONU e 3 câmeras.<br>Cada unidade ocupa uma linha. O comodato e o MAC/serial seguem o cadastro de cada equipamento.</footer></main></html>
