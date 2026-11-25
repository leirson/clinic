<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$id = (int)input('id');
try { clinic_company_record('planos_tratamento', $id); }
catch (InvalidArgumentException $e) { http_response_code(404); exit('Plano não encontrado.'); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    $acao = input('acao');
    if ($acao === 'aprovar') {
        db()->prepare("UPDATE planos_tratamento SET status='aprovado', aprovado_em=NOW(), aprovado_assinatura=? WHERE id=? AND empresa_id=?")
            ->execute([input('assinatura'), $id, active_company_id()]);
        flash('success','Plano aprovado e assinado!');
    } elseif ($acao === 'status') {
        db()->prepare("UPDATE planos_tratamento SET status=? WHERE id=? AND empresa_id=?")
            ->execute([input('status'), $id, active_company_id()]);
        flash('success','Status atualizado.');
    } elseif ($acao === 'concluir_item') {
        $iid = (int)input('item_id');
        db()->prepare("UPDATE plano_tratamento_itens SET status='concluido', executado_em=CURDATE(), executado_por=? WHERE id=? AND plano_id=?")
            ->execute([$_SESSION['user_id'], $iid, $id]);
        flash('success','Item concluído.');
    }
    redirect('ver.php?id='.$id);
}

$stmt = db()->prepare("SELECT pt.*, p.nome AS paciente_nome, p.cpf_cnpj, u.nome AS prof_nome
  FROM planos_tratamento pt JOIN pacientes p ON p.id=pt.paciente_id JOIN usuarios u ON u.id=pt.profissional_id
  WHERE pt.id=? AND pt.empresa_id=?");
$stmt->execute([$id, active_company_id()]);
$plano = $stmt->fetch();
if (!$plano) { flash('error','Plano não encontrado'); redirect('index.php'); }

$itens = db()->prepare("SELECT * FROM plano_tratamento_itens WHERE plano_id=? ORDER BY ordem");
$itens->execute([$id]); $itens = $itens->fetchAll();

$badge = ['orcamento'=>'badge-secondary','aprovado'=>'badge-info','em_execucao'=>'badge-warning','concluido'=>'badge-success','cancelado'=>'badge-danger'];
$page_title = 'Plano #'.$id;
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5 flex justify-between flex-wrap gap-2">
  <div>
    <a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a>
    <h1 class="text-2xl font-bold"><?= e($plano['titulo']) ?></h1>
    <p class="text-sm text-slate-500"><span class="badge <?= $badge[$plano['status']] ?? '' ?>"><?= e(str_replace('_',' ',$plano['status'])) ?></span> · <?= e($plano['paciente_nome']) ?></p>
  </div>
  <div class="flex gap-2">
    <a href="pdf.php?id=<?= $id ?>" target="_blank" class="btn bg-purple-600 text-white"><i class="bi bi-printer"></i> PDF</a>
    <?php if ($plano['status']==='orcamento'): ?>
      <button onclick="document.getElementById('modalAprovar').classList.remove('hidden')" class="btn bg-green-600 text-white"><i class="bi bi-check2-circle"></i> Aprovar</button>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
  <div class="lg:col-span-2 card p-5">
    <h3 class="font-bold mb-3">Itens do Tratamento</h3>
    <table class="table-modern text-sm">
      <thead><tr><th>#</th><th>Procedimento</th><th>Dente</th><th>Valor</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach($itens as $i => $it): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= e($it['descricao']) ?><?php if($it['face']): ?><div class="text-xs text-slate-500">Face: <?= e($it['face']) ?></div><?php endif; ?></td>
          <td><?= e($it['dente']) ?></td>
          <td><?= money($it['valor_total']) ?></td>
          <td>
            <?php if($it['status']==='concluido'): ?>
              <span class="badge badge-success">Concluído</span>
              <div class="text-xs"><?= format_date($it['executado_em']) ?></div>
            <?php else: ?>
              <span class="badge badge-warning">Pendente</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($plano['status']==='aprovado' || $plano['status']==='em_execucao'): ?>
            <?php if ($it['status']!=='concluido'): ?>
            <form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="acao" value="concluir_item"><input type="hidden" name="item_id" value="<?= $it['id'] ?>"><button class="text-green-600" title="Marcar concluído"><i class="bi bi-check-lg"></i></button></form>
            <?php endif; endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card p-5">
    <h3 class="font-bold mb-3">Resumo Financeiro</h3>
    <dl class="text-sm space-y-2">
      <div class="flex justify-between"><dt>Subtotal:</dt><dd><?= money($plano['valor_total']) ?></dd></div>
      <div class="flex justify-between text-red-600"><dt>Desconto:</dt><dd>- <?= money($plano['desconto']) ?></dd></div>
      <div class="flex justify-between font-bold text-lg pt-2 border-t"><dt>Total:</dt><dd class="text-primary"><?= money($plano['valor_final']) ?></dd></div>
      <?php if ($plano['parcelas']>1): ?>
      <div class="flex justify-between text-xs"><dt>Parcelas:</dt><dd><?= $plano['parcelas'] ?>x de <?= money($plano['valor_final']/$plano['parcelas']) ?></dd></div>
      <?php endif; ?>
    </dl>
    <div class="border-t mt-3 pt-3">
      <p class="text-xs text-slate-500">Profissional:</p>
      <p class="text-sm font-medium">Dr(a). <?= e($plano['prof_nome']) ?></p>
    </div>
    <?php if ($plano['validade']): ?>
    <div class="border-t mt-3 pt-3">
      <p class="text-xs text-slate-500">Validade do orçamento:</p>
      <p class="text-sm"><?= format_date($plano['validade']) ?></p>
    </div>
    <?php endif; ?>
    <?php if ($plano['aprovado_em']): ?>
    <div class="border-t mt-3 pt-3 bg-green-50 -mx-5 -mb-5 p-4 rounded-b-lg">
      <p class="text-xs text-green-700"><i class="bi bi-check-circle-fill"></i> Aprovado em <?= format_date($plano['aprovado_em'], true) ?></p>
      <?php if ($plano['aprovado_assinatura']): ?>
        <img src="<?= e($plano['aprovado_assinatura']) ?>" class="mt-2 max-h-20 bg-white rounded border">
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Aprovação -->
<div id="modalAprovar" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-xl p-6 w-full max-w-md">
    <h3 class="font-bold text-lg mb-2">Aprovação do Paciente</h3>
    <p class="text-sm text-slate-600 mb-3">Solicite que o paciente assine no campo abaixo (touch ou mouse):</p>
    <form method="POST" onsubmit="document.getElementById('assinaturaImg').value = sigCanvas.toDataURL();">
      <?= csrf_field() ?>
      <input type="hidden" name="acao" value="aprovar">
      <input type="hidden" name="assinatura" id="assinaturaImg">
      <canvas id="sigCanvas" width="400" height="150" class="border-2 border-dashed border-slate-300 rounded w-full touch-none"></canvas>
      <button type="button" onclick="limparAss()" class="text-xs text-red-600 mt-1">Limpar</button>
      <div class="flex gap-2 justify-end mt-4">
        <button type="button" onclick="document.getElementById('modalAprovar').classList.add('hidden')" class="btn bg-slate-200">Cancelar</button>
        <button class="btn bg-green-600 text-white">Confirmar Aprovação</button>
      </div>
    </form>
  </div>
</div>

<script>
const sigCanvas = document.getElementById('sigCanvas');
const ctx = sigCanvas.getContext('2d');
ctx.lineWidth = 2; ctx.strokeStyle = '#0ea5e9'; ctx.lineCap = 'round';
let drawing = false;
function pos(e) {
  const r = sigCanvas.getBoundingClientRect();
  const t = e.touches ? e.touches[0] : e;
  return {x: t.clientX - r.left, y: t.clientY - r.top};
}
sigCanvas.addEventListener('mousedown', e => { drawing = true; const p=pos(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); });
sigCanvas.addEventListener('mousemove', e => { if (drawing) { const p=pos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); }});
sigCanvas.addEventListener('mouseup', () => drawing = false);
sigCanvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; const p=pos(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); });
sigCanvas.addEventListener('touchmove', e => { e.preventDefault(); if (drawing) { const p=pos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); }});
sigCanvas.addEventListener('touchend', () => drawing = false);
function limparAss() { ctx.clearRect(0,0,sigCanvas.width,sigCanvas.height); }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
