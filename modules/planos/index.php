<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Planos de Tratamento';

$status = input('status');
$where = "WHERE pt.empresa_id = ?";
$params = [active_company_id()];
if ($status) { $where .= " AND pt.status = ?"; $params[] = $status; }

$stmt = db()->prepare("SELECT pt.*, p.nome AS paciente_nome, u.nome AS prof_nome,
  (SELECT COUNT(*) FROM plano_tratamento_itens WHERE plano_id=pt.id) AS total_itens,
  (SELECT COUNT(*) FROM plano_tratamento_itens WHERE plano_id=pt.id AND status='concluido') AS itens_concluidos
  FROM planos_tratamento pt
  JOIN pacientes p ON p.id = pt.paciente_id
  JOIN usuarios u ON u.id = pt.profissional_id
  $where ORDER BY pt.criado_em DESC LIMIT 200");
$stmt->execute($params);
$planos = $stmt->fetchAll();

$badge = ['orcamento'=>'badge-secondary','aprovado'=>'badge-info','em_execucao'=>'badge-warning','concluido'=>'badge-success','cancelado'=>'badge-danger'];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex flex-wrap justify-between items-center gap-3 mb-5">
  <div>
    <h1 class="text-2xl font-bold"><i class="bi bi-clipboard2-check text-primary"></i> Planos de Tratamento</h1>
    <p class="text-sm text-slate-500">Orçamentos e planejamento odontológico</p>
  </div>
  <a href="novo.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Novo Plano</a>
</div>

<div class="card p-3 mb-4 flex flex-wrap gap-2">
  <a href="index.php" class="px-3 py-1.5 text-sm rounded-lg <?= !$status?'bg-primary text-white':'bg-slate-100' ?>">Todos</a>
  <?php foreach(['orcamento','aprovado','em_execucao','concluido','cancelado'] as $s): ?>
    <a href="?status=<?= $s ?>" class="px-3 py-1.5 text-sm rounded-lg <?= $status===$s?'bg-primary text-white':'bg-slate-100' ?>"><?= ucfirst(str_replace('_',' ',$s)) ?></a>
  <?php endforeach; ?>
</div>

<div class="card overflow-hidden">
<table class="table-modern">
<thead><tr><th>Plano</th><th>Paciente</th><th>Valor Final</th><th>Progresso</th><th>Status</th><th>Criado em</th><th></th></tr></thead>
<tbody>
<?php if (!$planos): ?><tr><td colspan="7" class="text-center py-10 text-slate-400">Nenhum plano.</td></tr>
<?php else: foreach($planos as $p): $perc = $p['total_itens']>0 ? round($p['itens_concluidos']*100/$p['total_itens']) : 0; ?>
<tr>
  <td><div class="font-medium"><?= e($p['titulo']) ?></div><div class="text-xs text-slate-500">Dr(a). <?= e($p['prof_nome']) ?></div></td>
  <td><a href="../pacientes/ver.php?id=<?= $p['paciente_id'] ?>" class="hover:underline"><?= e($p['paciente_nome']) ?></a></td>
  <td class="font-semibold"><?= money($p['valor_final']) ?><?php if($p['desconto']>0): ?><div class="text-xs text-slate-400 line-through"><?= money($p['valor_total']) ?></div><?php endif; ?></td>
  <td>
    <div class="text-xs"><?= $p['itens_concluidos'] ?>/<?= $p['total_itens'] ?> itens (<?= $perc ?>%)</div>
    <div class="w-full bg-slate-200 rounded-full h-2 mt-1"><div class="bg-primary h-2 rounded-full" style="width:<?= $perc ?>%"></div></div>
  </td>
  <td><span class="badge <?= $badge[$p['status']] ?? '' ?>"><?= e(str_replace('_',' ',$p['status'])) ?></span></td>
  <td class="text-xs"><?= format_date($p['criado_em']) ?></td>
  <td class="text-right whitespace-nowrap">
    <a href="ver.php?id=<?= $p['id'] ?>" class="text-sky-600 mx-1"><i class="bi bi-eye"></i></a>
    <a href="pdf.php?id=<?= $p['id'] ?>" target="_blank" class="text-purple-600 mx-1"><i class="bi bi-printer"></i></a>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
