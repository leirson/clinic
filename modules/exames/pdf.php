<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$id = (int)input('id');
$stmt = db()->prepare("SELECT s.*, p.nome AS pn, p.cpf_cnpj, p.data_nascimento, u.nome AS prof, u.registro_profissional, u.especialidade FROM solicitacoes_exame s JOIN pacientes p ON p.id=s.paciente_id JOIN usuarios u ON u.id=s.profissional_id WHERE s.id=? AND p.empresa_id=?");
$stmt->execute([$id, active_company_id()]);
$s = $stmt->fetch();
if (!$s) die('Não encontrado');
$emp = current_empresa();
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Solicitação de Exames</title>
<style>
body{font-family:Georgia,serif;max-width:780px;margin:30px auto;padding:30px;}
.header{text-align:center;border-bottom:3px solid #0ea5e9;padding-bottom:15px;margin-bottom:25px;}
.header h1{margin:0;color:#0ea5e9;}
.titulo{text-align:center;text-transform:uppercase;letter-spacing:3px;font-size:20px;margin:20px 0;color:#1e40af;}
.box{background:#f8fafc;padding:12px;border-radius:8px;margin-bottom:15px;font-size:13px;}
.exames{font-size:15px;line-height:2;padding:15px;border:1px solid #cbd5e1;border-radius:8px;white-space:pre-wrap;}
.urgencia{display:inline-block;padding:4px 12px;border-radius:20px;font-weight:bold;text-transform:uppercase;font-size:11px;}
.urg-rotina{background:#dbeafe;color:#1e40af;}
.urg-urgente{background:#fef3c7;color:#92400e;}
.urg-emergencia{background:#fee2e2;color:#991b1b;}
.assinatura{margin-top:60px;text-align:center;}
.linha{border-top:1px solid #333;width:300px;margin:0 auto;}
@media print{.no-print{display:none}body{margin:0}}
</style></head><body>
<div class="no-print" style="text-align:right;margin-bottom:10px;">
  <button onclick="window.print()" style="padding:8px 16px;background:#0ea5e9;color:white;border:0;border-radius:6px;">🖨 Imprimir</button>
</div>
<div class="header">
  <h1><?= e($emp['nome_fantasia'] ?: $emp['razao_social']) ?></h1>
  <p style="margin:3px 0;font-size:12px;"><?= e($emp['endereco_logradouro']) ?>, <?= e($emp['endereco_numero']) ?> · CNPJ: <?= format_cpf_cnpj($emp['cnpj']) ?> · Tel: <?= e($emp['telefone']) ?></p>
</div>

<div class="titulo">Solicitação de Exames</div>

<div class="box">
  <strong>Paciente:</strong> <?= e($s['pn']) ?><br>
  <strong>CPF:</strong> <?= format_cpf_cnpj($s['cpf_cnpj']) ?>
  <?php if($s['data_nascimento']): ?> · <strong>Nascimento:</strong> <?= format_date($s['data_nascimento']) ?><?php endif; ?><br>
  <strong>Data:</strong> <?= format_date($s['data_solicitacao']) ?>
  · <strong>Urgência:</strong> <span class="urgencia urg-<?= e($s['urgencia']) ?>"><?= e($s['urgencia']) ?></span>
  <?php if($s['cid_codigo']): ?><br><strong>CID-10:</strong> <?= e($s['cid_codigo']) ?><?php endif; ?>
</div>

<?php if($s['indicacao_clinica']): ?>
<div class="box"><strong>Indicação Clínica:</strong><br><?= nl2br(e($s['indicacao_clinica'])) ?></div>
<?php endif; ?>

<p style="font-weight:bold;margin-bottom:10px;">Solicito os seguintes exames:</p>
<div class="exames"><?= e($s['exames']) ?></div>

<?php if($s['observacoes']): ?>
<div class="box" style="margin-top:15px;"><strong>Observações:</strong><br><?= nl2br(e($s['observacoes'])) ?></div>
<?php endif; ?>

<div class="assinatura">
  <p><?= e($emp['endereco_cidade']) ?>, <?= format_date($s['data_solicitacao']) ?></p>
  <div style="margin-top:60px;" class="linha"></div>
  <p><strong>Dr(a). <?= e($s['prof']) ?></strong></p>
  <p><?= e($s['especialidade']) ?> · <?= e($s['registro_profissional']) ?></p>
</div>
</body></html>
