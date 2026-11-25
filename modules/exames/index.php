<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Solicitações de Exame';

$stmt = db()->prepare("SELECT s.*, p.nome AS pn, u.nome AS prof FROM solicitacoes_exame s
  JOIN pacientes p ON p.id=s.paciente_id JOIN usuarios u ON u.id=s.profissional_id
  WHERE p.empresa_id=? ORDER BY s.data_solicitacao DESC LIMIT 200");
$stmt->execute([active_company_id()]);
$sols = $stmt->fetchAll();

$badge = ['solicitado'=>'badge-info','realizado'=>'badge-warning','recebido'=>'badge-success','cancelado'=>'badge-secondary'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex justify-between flex-wrap gap-3 mb-5">
  <div>
    <h1 class="text-2xl font-bold"><i class="bi bi-clipboard2-pulse text-primary"></i> Solicitações de Exame</h1>
    <p class="text-sm text-slate-500">Pedidos padronizados com modelos pré-prontos</p>
  </div>
  <div class="flex gap-2">
    <a href="modelos.php" class="btn bg-slate-100 text-slate-700"><i class="bi bi-bookmark"></i> Modelos</a>
    <a href="nova.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nova Solicitação</a>
  </div>
</div>

<div class="card overflow-hidden">
<table class="table-modern">
<thead><tr><th>Data</th><th>Paciente</th><th>Profissional</th><th>Urgência</th><th>CID</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php if(!$sols): ?><tr><td colspan="7" class="text-center py-10 text-slate-400">Sem solicitações.</td></tr>
<?php else: foreach($sols as $s): ?>
<tr>
  <td><?= format_date($s['data_solicitacao']) ?></td>
  <td><a href="../pacientes/ver.php?id=<?= $s['paciente_id'] ?>" class="hover:underline"><?= e($s['pn']) ?></a></td>
  <td>Dr(a). <?= e($s['prof']) ?></td>
  <td><span class="badge badge-<?= $s['urgencia']==='emergencia'?'danger':($s['urgencia']==='urgente'?'warning':'info') ?>"><?= e($s['urgencia']) ?></span></td>
  <td class="text-xs"><?= e($s['cid_codigo']) ?></td>
  <td><span class="badge <?= $badge[$s['status']] ?? '' ?>"><?= e($s['status']) ?></span></td>
  <td class="text-right whitespace-nowrap">
    <a href="pdf.php?id=<?= $s['id'] ?>" target="_blank" class="text-purple-600 mx-1" title="Imprimir"><i class="bi bi-printer"></i></a>
    <a href="ver.php?id=<?= $s['id'] ?>" class="text-sky-600 mx-1"><i class="bi bi-eye"></i></a>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
