<?php
$page_title = 'NFSe';
require_once __DIR__ . '/../../includes/header.php';

$id = (int)input('id');
$stmt = db()->prepare("SELECT n.*, p.nome AS paciente_nome, p.cpf_cnpj FROM nfse n JOIN pacientes p ON p.id=n.paciente_id WHERE n.id=? AND n.empresa_id=?");
$stmt->execute([$id, active_company_id()]);
$n = $stmt->fetch();
if (!$n) { flash('error','Não encontrada'); redirect('index.php'); }

$badge = ['rascunho'=>'badge-secondary','enviada'=>'badge-info','autorizada'=>'badge-success','cancelada'=>'badge-warning','rejeitada'=>'badge-danger','erro'=>'badge-danger'];
?>
<div class="mb-5 flex justify-between flex-wrap gap-2">
  <div>
    <a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a>
    <h1 class="text-2xl font-bold">NFSe — RPS #<?= e($n['numero_rps']) ?>/<?= e($n['serie_rps']) ?></h1>
    <span class="badge <?= $badge[$n['status']] ?? 'badge-secondary' ?>"><?= e(strtoupper($n['status'])) ?></span>
  </div>
  <div class="flex gap-2">
    <?php if ($n['status']==='rascunho'): ?>
      <a href="enviar.php?id=<?= $id ?>" data-confirm="Enviar para a plataforma NFSe Nacional?" class="btn btn-primary"><i class="bi bi-send"></i> Enviar NFSe Nacional</a>
    <?php endif; ?>
    <?php if ($n['status']==='autorizada'): ?>
      <a href="pdf.php?id=<?= $id ?>" target="_blank" class="btn bg-purple-600 text-white"><i class="bi bi-printer"></i> Imprimir/PDF</a>
      <a href="cancelar.php?id=<?= $id ?>" data-confirm="Cancelar NFSe?" class="btn bg-red-600 text-white"><i class="bi bi-x-circle"></i> Cancelar</a>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
  <div class="lg:col-span-2 card p-5">
    <h3 class="font-bold mb-3"><i class="bi bi-info-circle"></i> Dados da NFSe</h3>
    <dl class="grid grid-cols-2 gap-3 text-sm">
      <div><dt class="text-slate-500">Paciente:</dt><dd class="font-medium"><?= e($n['paciente_nome']) ?></dd></div>
      <div><dt class="text-slate-500">CPF/CNPJ:</dt><dd><?= format_cpf_cnpj($n['cpf_cnpj']) ?></dd></div>
      <div><dt class="text-slate-500">Emissão:</dt><dd><?= format_date($n['data_emissao'], true) ?></dd></div>
      <div><dt class="text-slate-500">Competência:</dt><dd><?= format_date($n['data_competencia']) ?></dd></div>
      <div><dt class="text-slate-500">Valor Serviços:</dt><dd class="font-semibold"><?= money($n['valor_servico']) ?></dd></div>
      <div><dt class="text-slate-500">Deduções:</dt><dd><?= money($n['valor_deducoes']) ?></dd></div>
      <div><dt class="text-slate-500">Alíquota ISS:</dt><dd><?= number_format($n['aliquota_iss'],2) ?>%</dd></div>
      <div><dt class="text-slate-500">Valor ISS:</dt><dd><?= money($n['valor_iss']) ?></dd></div>
      <div><dt class="text-slate-500">Item LC 116:</dt><dd><?= e($n['item_lista_servico']) ?></dd></div>
      <div><dt class="text-slate-500">Cód. Trib.:</dt><dd><?= e($n['codigo_tributacao_municipio']) ?></dd></div>
      <div class="col-span-2"><dt class="text-slate-500">Discriminação:</dt><dd class="whitespace-pre-wrap"><?= e($n['discriminacao']) ?></dd></div>
    </dl>
  </div>
  <div class="card p-5">
    <h3 class="font-bold mb-3"><i class="bi bi-shield-check"></i> Retorno NFSe Nacional</h3>
    <dl class="text-sm space-y-2">
      <div><dt class="text-slate-500">Nº NFSe:</dt><dd class="font-semibold"><?= e($n['numero_nfse'] ?: '—') ?></dd></div>
      <div><dt class="text-slate-500">Protocolo:</dt><dd><?= e($n['protocolo'] ?: '—') ?></dd></div>
      <div><dt class="text-slate-500">Cód. Verificação:</dt><dd class="font-mono"><?= e($n['codigo_verificacao'] ?: '—') ?></dd></div>
      <div><dt class="text-slate-500">Mensagem:</dt><dd class="text-xs"><?= e($n['mensagem_retorno'] ?: '—') ?></dd></div>
    </dl>
    <?php if ($n['xml_envio'] || $n['xml_retorno']): ?>
    <details class="mt-4 text-xs">
      <summary class="cursor-pointer text-primary">Ver XML técnico</summary>
      <?php if ($n['xml_envio']): ?>
      <p class="mt-2 font-semibold">Envio:</p>
      <pre class="bg-slate-50 p-2 rounded overflow-auto max-h-40 text-[10px]"><?= e($n['xml_envio']) ?></pre>
      <?php endif; ?>
      <?php if ($n['xml_retorno']): ?>
      <p class="mt-2 font-semibold">Retorno:</p>
      <pre class="bg-slate-50 p-2 rounded overflow-auto max-h-40 text-[10px]"><?= e($n['xml_retorno']) ?></pre>
      <?php endif; ?>
    </details>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
