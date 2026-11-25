<?php
$page_title = 'NFSe Emitidas';
require_once __DIR__ . '/../../includes/header.php';

$status_filter = input('status');
$where = "WHERE n.empresa_id = ?";
$params = [active_company_id()];
if ($status_filter) { $where .= " AND n.status = ?"; $params[] = $status_filter; }

$stmt = db()->prepare("SELECT n.*, p.nome AS paciente_nome FROM nfse n JOIN pacientes p ON p.id=n.paciente_id $where ORDER BY n.data_emissao DESC LIMIT 300");
$stmt->execute($params);
$nfses = $stmt->fetchAll();

$badge = ['rascunho'=>'badge-secondary','enviada'=>'badge-info','autorizada'=>'badge-success','cancelada'=>'badge-warning','rejeitada'=>'badge-danger','erro'=>'badge-danger'];
?>
<div class="flex flex-wrap justify-between items-center gap-3 mb-5">
  <div>
    <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-receipt text-primary"></i> NFSe</h1>
    <p class="text-sm text-slate-500">Notas fiscais de serviço eletrônicas</p>
  </div>
  <div class="flex gap-2">
    <a href="../empresa/index.php#nfse" class="btn bg-slate-100 text-slate-700"><i class="bi bi-gear"></i> Config. NFSe Nacional</a>
    <a href="novo.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Emitir NFSe</a>
  </div>
</div>

<div class="card p-3 mb-4 flex flex-wrap gap-2">
  <a href="index.php" class="px-3 py-1.5 text-sm rounded-lg <?= !$status_filter?'bg-primary text-white':'bg-slate-100' ?>">Todas</a>
  <?php foreach(['rascunho','enviada','autorizada','cancelada','rejeitada'] as $s): ?>
    <a href="?status=<?= $s ?>" class="px-3 py-1.5 text-sm rounded-lg <?= $status_filter===$s?'bg-primary text-white':'bg-slate-100' ?>"><?= ucfirst($s) ?></a>
  <?php endforeach; ?>
</div>

<div class="card overflow-hidden">
  <table class="table-modern">
    <thead><tr><th>RPS / NFSe</th><th>Data</th><th>Paciente</th><th>Valor</th><th>ISS</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (!$nfses): ?><tr><td colspan="7" class="text-center py-10 text-slate-400">Nenhuma NFSe emitida.</td></tr>
      <?php else: foreach($nfses as $n): ?>
      <tr>
        <td>
          <div class="font-medium">RPS <?= e($n['numero_rps']) ?>/<?= e($n['serie_rps']) ?></div>
          <?php if ($n['numero_nfse']): ?><div class="text-xs text-slate-500">NFSe: <?= e($n['numero_nfse']) ?></div><?php endif; ?>
        </td>
        <td><?= format_date($n['data_emissao']) ?></td>
        <td><a href="../pacientes/ver.php?id=<?= $n['paciente_id'] ?>" class="hover:underline"><?= e($n['paciente_nome']) ?></a></td>
        <td><?= money($n['valor_servico']) ?></td>
        <td><?= money($n['valor_iss']) ?></td>
        <td><span class="badge <?= $badge[$n['status']] ?? 'badge-secondary' ?>"><?= e($n['status']) ?></span></td>
        <td class="text-right whitespace-nowrap">
          <a href="ver.php?id=<?= $n['id'] ?>" class="text-sky-600 mx-1" title="Ver"><i class="bi bi-eye"></i></a>
          <?php if ($n['status']==='rascunho'): ?>
            <a href="enviar.php?id=<?= $n['id'] ?>" data-confirm="Enviar NFSe para a plataforma nacional?" class="text-green-600 mx-1" title="Enviar"><i class="bi bi-send"></i></a>
          <?php endif; ?>
          <?php if ($n['status']==='autorizada'): ?>
            <a href="pdf.php?id=<?= $n['id'] ?>" target="_blank" class="text-purple-600 mx-1" title="Imprimir"><i class="bi bi-printer"></i></a>
            <a href="cancelar.php?id=<?= $n['id'] ?>" data-confirm="Cancelar esta NFSe?" class="text-red-600 mx-1" title="Cancelar"><i class="bi bi-x-circle"></i></a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
