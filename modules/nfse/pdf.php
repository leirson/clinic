<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$id = (int)input('id');
$stmt = db()->prepare("SELECT n.*, p.nome AS pn, p.cpf_cnpj AS pcpf, p.logradouro, p.numero AS pnum, p.bairro, p.cidade AS pcid, p.uf AS puf, p.cep AS pcep FROM nfse n JOIN pacientes p ON p.id=n.paciente_id WHERE n.id=? AND n.empresa_id=?");
$stmt->execute([$id, active_company_id()]);
$n = $stmt->fetch();
if (!$n) die('NFSe não encontrada');
$emp = current_empresa();
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>NFSe <?= e($n['numero_nfse']) ?></title>
<style>
body{font-family:Arial,sans-serif;max-width:780px;margin:20px auto;padding:20px;color:#222;font-size:12px;}
.header{border:2px solid #1e40af;padding:15px;border-radius:6px;}
.header h1{margin:0;color:#1e40af;font-size:18px;}
.box{border:1px solid #cbd5e1;padding:10px;margin-top:10px;border-radius:6px;}
.box h3{margin:0 0 8px 0;font-size:13px;color:#1e40af;border-bottom:1px solid #cbd5e1;padding-bottom:4px;}
table{width:100%;border-collapse:collapse;}
td{padding:4px 8px;}
.label{font-weight:bold;color:#555;}
.total{font-size:16px;font-weight:bold;color:#1e40af;}
@media print{.no-print{display:none;}body{margin:0;}}
</style></head><body>

<div class="no-print" style="text-align:right;margin-bottom:10px;">
  <button onclick="window.print()" style="padding:8px 16px;background:#0ea5e9;color:white;border:0;border-radius:6px;">🖨 Imprimir</button>
</div>

<div class="header">
  <table><tr>
    <td><h1>NOTA FISCAL DE SERVIÇO ELETRÔNICA</h1>
        <p style="margin:5px 0 0;font-size:11px;">Nº <?= e($n['numero_nfse'] ?: 'RPS '.$n['numero_rps']) ?> · Emissão: <?= format_date($n['data_emissao'], true) ?></p></td>
    <td style="text-align:right;font-size:10px;">
      Código de Verificação:<br><strong style="font-family:monospace;"><?= e($n['codigo_verificacao'] ?: '—') ?></strong>
    </td>
  </tr></table>
</div>

<div class="box">
  <h3>PRESTADOR DE SERVIÇOS</h3>
  <table>
    <tr><td class="label" style="width:25%">Razão Social:</td><td><?= e($emp['razao_social']) ?></td></tr>
    <tr><td class="label">CNPJ:</td><td><?= format_cpf_cnpj($emp['cnpj']) ?> · IM: <?= e($emp['inscricao_municipal']) ?></td></tr>
    <tr><td class="label">Endereço:</td><td><?= e($emp['endereco_logradouro']) ?>, <?= e($emp['endereco_numero']) ?> - <?= e($emp['endereco_bairro']) ?>, <?= e($emp['endereco_cidade']) ?>/<?= e($emp['endereco_uf']) ?></td></tr>
  </table>
</div>

<div class="box">
  <h3>TOMADOR DE SERVIÇOS</h3>
  <table>
    <tr><td class="label" style="width:25%">Nome:</td><td><?= e($n['pn']) ?></td></tr>
    <tr><td class="label">CPF/CNPJ:</td><td><?= format_cpf_cnpj($n['pcpf']) ?></td></tr>
    <tr><td class="label">Endereço:</td><td><?= e($n['logradouro']) ?>, <?= e($n['pnum']) ?> - <?= e($n['bairro']) ?>, <?= e($n['pcid']) ?>/<?= e($n['puf']) ?> · CEP: <?= e($n['pcep']) ?></td></tr>
  </table>
</div>

<div class="box">
  <h3>DISCRIMINAÇÃO DOS SERVIÇOS</h3>
  <p style="white-space:pre-wrap;line-height:1.6;"><?= e($n['discriminacao']) ?></p>
  <p style="font-size:10px;color:#666;">Item Lista Serviço: <?= e($n['item_lista_servico']) ?> · Cód. Tributação: <?= e($n['codigo_tributacao_municipio']) ?></p>
</div>

<div class="box">
  <h3>VALORES</h3>
  <table>
    <tr><td class="label">Valor dos Serviços:</td><td><?= money($n['valor_servico']) ?></td>
        <td class="label">Deduções:</td><td><?= money($n['valor_deducoes']) ?></td></tr>
    <tr><td class="label">Alíquota ISS:</td><td><?= number_format($n['aliquota_iss'],2) ?>%</td>
        <td class="label">Valor ISS:</td><td><?= money($n['valor_iss']) ?></td></tr>
    <tr><td class="label">ISS Retido:</td><td><?= $n['iss_retido']?'Sim':'Não' ?></td>
        <td class="label total">VALOR LÍQUIDO:</td><td class="total"><?= money($n['valor_liquido']) ?></td></tr>
  </table>
</div>

<p style="text-align:center;font-size:10px;color:#666;margin-top:20px;">
  Documento emitido eletronicamente em <?= format_date($n['data_emissao'], true) ?> via <?= APP_NAME ?>
</p>
</body></html>
