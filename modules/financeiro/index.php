<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Financeiro';

$tipo = input('tipo'); // receita/despesa
$status = input('status');
$mes = input('mes', date('Y-m'));
$ini = $mes . '-01';
$fim = date('Y-m-t', strtotime($ini));

$where = "WHERE c.empresa_id = ? AND c.data_vencimento BETWEEN ? AND ?";
$params = [active_company_id(), $ini, $fim];
if ($tipo) { $where .= " AND c.tipo = ?"; $params[] = $tipo; }
if ($status) { $where .= " AND c.status = ?"; $params[] = $status; }

// Auto-atualizar atrasados
db()->query("UPDATE contas_financeiras SET status='atrasado' WHERE status='pendente' AND data_vencimento < CURDATE()");

$stmt = db()->prepare("SELECT c.*, cat.nome AS categoria_nome, cat.cor AS categoria_cor, p.nome AS paciente_nome
  FROM contas_financeiras c
  LEFT JOIN categorias_financeiras cat ON cat.id = c.categoria_id
  LEFT JOIN pacientes p ON p.id = c.paciente_id
  $where ORDER BY c.data_vencimento DESC LIMIT 300");
$stmt->execute($params);
$contas = $stmt->fetchAll();

// Totais
$tot = db()->prepare("SELECT
  SUM(CASE WHEN tipo='receita' AND status='pago' THEN valor ELSE 0 END) AS recebido,
  SUM(CASE WHEN tipo='receita' AND status IN ('pendente','atrasado') THEN valor ELSE 0 END) AS a_receber,
  SUM(CASE WHEN tipo='despesa' AND status='pago' THEN valor ELSE 0 END) AS pago,
  SUM(CASE WHEN tipo='despesa' AND status IN ('pendente','atrasado') THEN valor ELSE 0 END) AS a_pagar
  FROM contas_financeiras WHERE empresa_id=? AND data_vencimento BETWEEN ? AND ?");
$tot->execute([active_company_id(), $ini, $fim]);
$t = $tot->fetch();
$saldo = ($t['recebido'] ?? 0) - ($t['pago'] ?? 0);

$badge = ['pendente'=>'badge-warning','pago'=>'badge-success','atrasado'=>'badge-danger','cancelado'=>'badge-secondary'];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex flex-wrap justify-between items-center gap-3 mb-5">
  <div>
    <h1 class="text-2xl font-bold"><i class="bi bi-cash-stack text-primary"></i> Financeiro</h1>
    <p class="text-sm text-slate-500">Contas a receber e a pagar</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="relatorio_mensal.php?mes=<?= e($mes) ?>" class="btn bg-sky-600 text-white"><i class="bi bi-calendar2-range"></i> Relatório mensal</a>
    <a href="categorias.php" class="btn bg-slate-100 text-slate-700"><i class="bi bi-tags"></i> Categorias</a>
    <a href="nova.php?tipo=despesa" class="btn bg-red-500 text-white"><i class="bi bi-dash-circle"></i> Despesa</a>
    <a href="nova.php?tipo=receita" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Receita</a>
  </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
  <div class="card p-4">
    <p class="text-xs text-slate-500 uppercase">Recebido no mês</p>
    <p class="text-2xl font-bold text-green-600"><?= money($t['recebido'] ?? 0) ?></p>
  </div>
  <div class="card p-4">
    <p class="text-xs text-slate-500 uppercase">A receber</p>
    <p class="text-2xl font-bold text-amber-600"><?= money($t['a_receber'] ?? 0) ?></p>
  </div>
  <div class="card p-4">
    <p class="text-xs text-slate-500 uppercase">Pago</p>
    <p class="text-2xl font-bold text-red-600"><?= money($t['pago'] ?? 0) ?></p>
  </div>
  <div class="card p-4">
    <p class="text-xs text-slate-500 uppercase">Saldo</p>
    <p class="text-2xl font-bold <?= $saldo>=0?'text-green-600':'text-red-600' ?>"><?= money($saldo) ?></p>
  </div>
</div>

<form method="GET" class="card p-3 mb-4 flex flex-wrap gap-2 items-end">
  <div><label class="text-xs">Mês</label><input type="month" name="mes" value="<?= e($mes) ?>" class="form-input"></div>
  <div><label class="text-xs">Tipo</label><select name="tipo" class="form-select"><option value="">Todos</option><option value="receita" <?= $tipo==='receita'?'selected':'' ?>>Receita</option><option value="despesa" <?= $tipo==='despesa'?'selected':'' ?>>Despesa</option></select></div>
  <div><label class="text-xs">Status</label><select name="status" class="form-select"><option value="">Todos</option><?php foreach(['pendente','pago','atrasado','cancelado'] as $s): ?><option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
  <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
</form>

<div class="card overflow-hidden">
<table class="table-modern">
<thead><tr><th>Vencimento</th><th>Descrição</th><th>Categoria</th><th>Paciente</th><th>Valor</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php if (!$contas): ?><tr><td colspan="7" class="text-center py-10 text-slate-400">Sem lançamentos.</td></tr>
<?php else: foreach($contas as $c): ?>
<tr>
  <td><?= format_date($c['data_vencimento']) ?><?php if($c['parcela_total']>1): ?><div class="text-xs text-slate-500"><?= $c['parcela_numero'] ?>/<?= $c['parcela_total'] ?></div><?php endif; ?></td>
  <td><div class="font-medium"><?= e($c['descricao']) ?></div></td>
  <td><span class="badge" style="background:<?= e($c['categoria_cor'] ?? '#cbd5e1') ?>;color:white"><?= e($c['categoria_nome'] ?? '—') ?></span></td>
  <td><?= e($c['paciente_nome'] ?? '—') ?></td>
  <td class="<?= $c['tipo']==='receita'?'text-green-600':'text-red-600' ?> font-semibold"><?= ($c['tipo']==='receita'?'+ ':'- ').money($c['valor']) ?></td>
  <td><span class="badge <?= $badge[$c['status']] ?? '' ?>"><?= e($c['status']) ?></span></td>
  <td class="text-right whitespace-nowrap">
    <?php if ($c['status']!=='pago'): ?>
      <a href="pagar.php?id=<?= $c['id'] ?>" data-confirm="Marcar como pago?" class="text-green-600 mx-1" title="Pagar"><i class="bi bi-check-circle"></i></a>
    <?php endif; ?>
    <a href="editar.php?id=<?= $c['id'] ?>" class="text-amber-600 mx-1"><i class="bi bi-pencil"></i></a>
    <a href="excluir.php?id=<?= $c['id'] ?>" data-confirm="Excluir?" class="text-red-600 mx-1"><i class="bi bi-trash"></i></a>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
