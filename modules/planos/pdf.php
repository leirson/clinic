<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$id = (int)input('id');
$stmt = db()->prepare("SELECT pt.*, p.nome AS pn, p.cpf_cnpj, u.nome AS prof, u.registro_profissional FROM planos_tratamento pt JOIN pacientes p ON p.id=pt.paciente_id JOIN usuarios u ON u.id=pt.profissional_id WHERE pt.id=? AND pt.empresa_id=?");
$stmt->execute([$id, active_company_id()]);
$pl = $stmt->fetch();
if (!$pl) die('Plano não encontrado');
$itens = db()->prepare("SELECT * FROM plano_tratamento_itens WHERE plano_id=? ORDER BY ordem");
$itens->execute([$id]); $itens = $itens->fetchAll();
$emp = current_empresa();
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Plano de Tratamento #<?= $id ?></title>
<style>
body{font-family:Arial,sans-serif;max-width:780px;margin:20px auto;padding:25px;font-size:13px;}
.header{text-align:center;border-bottom:3px solid #0891b2;padding-bottom:15px;margin-bottom:20px;}
.header h1{margin:0;color:#0891b2;}
.box{border:1px solid #cbd5e1;padding:12px;border-radius:6px;margin-bottom:15px;}
.box h3{margin:0 0 8px 0;color:#0891b2;font-size:14px;}
table{width:100%;border-collapse:collapse;margin-top:5px;}
th{background:#f1f5f9;text-align:left;padding:8px;border-bottom:2px solid #cbd5e1;font-size:11px;}
td{padding:8px;border-bottom:1px solid #e2e8f0;}
.total{font-size:18px;font-weight:bold;color:#0891b2;}
.assinatura{margin-top:60px;display:flex;justify-content:space-around;text-align:center;}
.linha{border-top:1px solid #333;width:240px;margin:0 auto 5px;padding-top:5px;font-size:12px;}
@media print{.no-print{display:none}body{margin:0;}}
</style></head><body>
<div class="no-print" style="text-align:right;margin-bottom:10px;">
  <button onclick="window.print()" style="padding:8px 16px;background:#0891b2;color:white;border:0;border-radius:6px;">🖨 Imprimir</button>
</div>
<div class="header">
  <h1><?= e($emp['nome_fantasia'] ?: $emp['razao_social']) ?></h1>
  <p style="margin:3px 0;font-size:11px;"><?= e($emp['endereco_logradouro']) ?>, <?= e($emp['endereco_numero']) ?> · <?= e($emp['endereco_cidade']) ?>/<?= e($emp['endereco_uf']) ?> · CNPJ: <?= format_cpf_cnpj($emp['cnpj']) ?></p>
  <h2 style="margin:10px 0 0;color:#0891b2;">PLANO DE TRATAMENTO</h2>
</div>

<div class="box">
  <h3>PACIENTE</h3>
  <table><tr><td><strong>Nome:</strong> <?= e($pl['pn']) ?></td><td><strong>CPF:</strong> <?= format_cpf_cnpj($pl['cpf_cnpj']) ?></td></tr>
  <tr><td colspan="2"><strong>Profissional:</strong> Dr(a). <?= e($pl['prof']) ?> · <?= e($pl['registro_profissional']) ?></td></tr></table>
</div>

<div class="box">
  <h3>TRATAMENTO PROPOSTO: <?= e($pl['titulo']) ?></h3>
  <?php if($pl['descricao']): ?><p><?= nl2br(e($pl['descricao'])) ?></p><?php endif; ?>
</div>

<div class="box">
  <h3>ITENS</h3>
  <table>
    <tr><th>#</th><th>Procedimento</th><th>Dente</th><th>Face</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th></tr>
    <?php foreach($itens as $i => $it): ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td><?= e($it['descricao']) ?></td>
      <td><?= e($it['dente']) ?></td>
      <td><?= e($it['face']) ?></td>
      <td><?= $it['quantidade'] ?></td>
      <td><?= money($it['valor_unitario']) ?></td>
      <td><?= money($it['valor_total']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="box">
  <h3>VALORES</h3>
  <table>
    <tr><td>Subtotal:</td><td style="text-align:right;"><?= money($pl['valor_total']) ?></td></tr>
    <tr><td>Desconto:</td><td style="text-align:right;color:#dc2626;">- <?= money($pl['desconto']) ?></td></tr>
    <tr><td class="total">TOTAL:</td><td class="total" style="text-align:right;"><?= money($pl['valor_final']) ?></td></tr>
    <?php if($pl['parcelas']>1): ?>
    <tr><td colspan="2">Parcelamento: <strong><?= $pl['parcelas'] ?>x de <?= money($pl['valor_final']/$pl['parcelas']) ?></strong></td></tr>
    <?php endif; ?>
    <?php if($pl['validade']): ?>
    <tr><td colspan="2">Validade do orçamento: <strong><?= format_date($pl['validade']) ?></strong></td></tr>
    <?php endif; ?>
  </table>
</div>

<?php if($pl['observacoes']): ?>
<div class="box"><h3>OBSERVAÇÕES</h3><p style="white-space:pre-wrap"><?= e($pl['observacoes']) ?></p></div>
<?php endif; ?>

<div class="assinatura">
  <div>
    <?php if($pl['aprovado_assinatura']): ?>
      <img src="<?= e($pl['aprovado_assinatura']) ?>" style="max-height:60px;">
    <?php endif; ?>
    <div class="linha">Paciente</div>
    <small><?= e($pl['pn']) ?></small>
  </div>
  <div>
    <div class="linha" style="margin-top:60px">Profissional Responsável</div>
    <small>Dr(a). <?= e($pl['prof']) ?> · <?= e($pl['registro_profissional']) ?></small>
  </div>
</div>

<p style="text-align:center;margin-top:30px;font-size:10px;color:#666;">Emitido em <?= date('d/m/Y H:i') ?> · <?= APP_NAME ?></p>
</body></html>
