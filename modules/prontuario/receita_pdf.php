<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$id = (int)input('id');
$stmt = db()->prepare("SELECT r.*, p.nome AS pn, p.cpf_cnpj, p.data_nascimento, p.logradouro, p.numero AS pnum, p.bairro, p.cidade AS pcid, p.uf AS puf, u.nome AS prof, u.registro_profissional, u.especialidade FROM receitas r JOIN pacientes p ON p.id=r.paciente_id JOIN usuarios u ON u.id=r.profissional_id WHERE r.id=? AND p.empresa_id=?");
$stmt->execute([$id, active_company_id()]);
$r = $stmt->fetch();
if (!$r) die('Receita não encontrada.');

$empresa = current_empresa();
$tipo_label = ['simples'=>'Receita Simples','controlada'=>'Receita Controlada (Tipo B)','atestado'=>'Atestado Médico'][$r['tipo']] ?? 'Documento';
?>
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><title><?= $tipo_label ?></title>
<style>
body{font-family:'Georgia',serif;max-width:780px;margin:30px auto;padding:30px;color:#222;}
.header{text-align:center;border-bottom:3px solid #0ea5e9;padding-bottom:15px;margin-bottom:30px;}
.header h1{margin:0;color:#0ea5e9;}
.header p{margin:3px 0;font-size:13px;color:#555;}
.titulo{text-align:center;text-transform:uppercase;letter-spacing:3px;font-size:22px;margin:30px 0;}
.paciente-info{background:#f8fafc;padding:15px;border-radius:8px;margin-bottom:25px;font-size:14px;}
.conteudo{font-size:15px;line-height:2;white-space:pre-wrap;padding:0 10px;}
.footer{margin-top:40px;text-align:center;}
.assinatura-box{margin-top:50px;display:flex;justify-content:space-between;align-items:flex-end;}
.ass-img{text-align:center;}
.ass-img img{max-height:80px;}
.linha{border-top:1px solid #333;width:280px;margin:0 auto;padding-top:5px;}
.qr-box{text-align:right;}
.qr-box img{width:90px;height:90px;}
.hash{font-family:monospace;font-size:9px;color:#94a3b8;word-break:break-all;text-align:center;margin-top:5px;}
.validacao{text-align:center;background:#f0f9ff;padding:10px;border:1px solid #bae6fd;border-radius:8px;font-size:11px;margin-top:30px;}
@media print{.no-print{display:none;}body{margin:0;}}
</style></head><body>

<div class="no-print" style="text-align:right;margin-bottom:10px;">
  <button onclick="window.print()" style="padding:8px 16px;background:#0ea5e9;color:white;border:0;border-radius:6px;cursor:pointer;">🖨 Imprimir</button>
</div>

<div class="header">
  <h1><?= e($empresa['nome_fantasia'] ?: $empresa['razao_social']) ?></h1>
  <p><?= e($empresa['endereco_logradouro']) ?>, <?= e($empresa['endereco_numero']) ?> - <?= e($empresa['endereco_cidade']) ?>/<?= e($empresa['endereco_uf']) ?></p>
  <p>CNPJ: <?= format_cpf_cnpj($empresa['cnpj']) ?> · Tel: <?= e($empresa['telefone']) ?></p>
</div>

<div class="titulo"><?= $tipo_label ?></div>

<div class="paciente-info">
  <strong>Paciente:</strong> <?= e($r['pn']) ?><br>
  <strong>CPF:</strong> <?= format_cpf_cnpj($r['cpf_cnpj']) ?>
  <?php if ($r['data_nascimento']): ?> · <strong>Nascimento:</strong> <?= format_date($r['data_nascimento']) ?><?php endif; ?><br>
  <?php if ($r['cid']): ?><strong>CID:</strong> <?= e($r['cid']) ?><br><?php endif; ?>
  <?php if ($r['dias_afastamento']): ?><strong>Dias de afastamento:</strong> <?= e($r['dias_afastamento']) ?><br><?php endif; ?>
</div>

<div class="conteudo"><?= e($r['conteudo']) ?></div>

<div class="assinatura-box">
  <div>
    <p><?= e($empresa['endereco_cidade']) ?>, <?= format_date($r['data_emissao']) ?></p>
  </div>
  <?php if($r['qr_code']): ?>
  <div class="qr-box">
    <img src="<?= e($r['qr_code']) ?>" alt="QR Verificação">
    <div style="font-size:9px;color:#64748b;">Verificação digital</div>
  </div>
  <?php endif; ?>
</div>

<div class="ass-img" style="margin-top:30px;">
  <?php if($r['assinatura_digital']): ?>
    <img src="<?= e($r['assinatura_digital']) ?>" alt="Assinatura">
  <?php endif; ?>
  <div class="linha"></div>
  <p><strong>Dr(a). <?= e($r['prof']) ?></strong></p>
  <p><?= e($r['especialidade']) ?> — <?= e($r['registro_profissional']) ?></p>
</div>

<?php if($r['hash_documento']): ?>
<div class="validacao">
  🔐 <strong>Documento assinado digitalmente</strong><br>
  Hash SHA-256: <span class="hash"><?= e($r['hash_documento']) ?></span><br>
  <small>Para validar, escaneie o QR Code ou acesse <?= APP_URL ?>/validar.php?h=<?= substr($r['hash_documento'],0,16) ?></small>
</div>
<?php endif; ?>

<p style="text-align:center;font-size:10px;color:#666;margin-top:20px;">
  Documento eletrônico — emitido por <?= APP_NAME ?> em <?= format_date($r['data_emissao'], true) ?>
</p>

</body></html>
