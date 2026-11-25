<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Relatório Financeiro Mensal';

$mes = input('mes', date('Y-m'));
$report = monthly_financial_report(active_company_id(), $mes);
$totals = $report['totals'] ?? [];
$receitas = (float) ($totals['receitas'] ?? 0);
$despesas = (float) ($totals['despesas'] ?? 0);
$saldo = $receitas - $despesas;
$receitasAberto = (float) ($totals['receitas_em_aberto'] ?? 0);
$despesasAberto = (float) ($totals['despesas_em_aberto'] ?? 0);
$timeline = $report['timeline'] ?? [];
$categories = $report['categories'] ?? [];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
  <div>
    <a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar para Financeiro</a>
    <h1 class="text-2xl font-bold text-slate-800 mt-1"><i class="bi bi-calendar2-range text-primary"></i> Relatório Financeiro Mensal</h1>
    <p class="text-sm text-slate-500">Resumo de fluxo, categorias e evolução diária do mês selecionado.</p>
  </div>
  <form method="GET" class="flex gap-2 items-end">
    <div>
      <label class="text-xs text-slate-500">Mês</label>
      <input type="month" name="mes" value="<?= e($mes) ?>" class="form-input">
    </div>
    <button class="btn btn-primary"><i class="bi bi-funnel"></i> Atualizar</button>
  </form>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
  <div class="card p-4"><p class="text-xs uppercase text-slate-500">Receitas pagas</p><p class="text-2xl font-bold text-green-600"><?= money($receitas) ?></p></div>
  <div class="card p-4"><p class="text-xs uppercase text-slate-500">Despesas pagas</p><p class="text-2xl font-bold text-red-600"><?= money($despesas) ?></p></div>
  <div class="card p-4"><p class="text-xs uppercase text-slate-500">Saldo do mês</p><p class="text-2xl font-bold <?= $saldo >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>"><?= money($saldo) ?></p></div>
  <div class="card p-4"><p class="text-xs uppercase text-slate-500">Margem</p><p class="text-2xl font-bold"><?= $receitas > 0 ? number_format(($saldo * 100) / $receitas, 1, ',', '.') : '0,0' ?>%</p></div>
  <div class="card p-4"><p class="text-xs uppercase text-slate-500">Receitas em aberto</p><p class="text-xl font-bold text-amber-600"><?= money($receitasAberto) ?></p></div>
  <div class="card p-4"><p class="text-xs uppercase text-slate-500">Despesas em aberto</p><p class="text-xl font-bold text-orange-600"><?= money($despesasAberto) ?></p></div>
  <div class="card p-4"><p class="text-xs uppercase text-slate-500">Lanç. de receita</p><p class="text-xl font-bold"><?= (int) ($totals['lancamentos_receita'] ?? 0) ?></p></div>
  <div class="card p-4"><p class="text-xs uppercase text-slate-500">Lanç. de despesa</p><p class="text-xl font-bold"><?= (int) ($totals['lancamentos_despesa'] ?? 0) ?></p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
  <div class="card p-5">
    <h3 class="font-bold mb-3">Evolução diária</h3>
    <canvas id="graficoMensal" height="210"></canvas>
  </div>
  <div class="card p-5">
    <h3 class="font-bold mb-3">Categorias do mês</h3>
    <div class="overflow-auto">
      <table class="table-modern">
        <thead><tr><th>Tipo</th><th>Categoria</th><th>Total</th></tr></thead>
        <tbody>
        <?php if (!$categories): ?>
          <tr><td colspan="3" class="text-center py-8 text-slate-400">Sem movimentações no período.</td></tr>
        <?php else: foreach ($categories as $cat): ?>
          <tr>
            <td><span class="badge <?= ($cat['tipo'] ?? '') === 'receita' ? 'badge-success' : 'badge-danger' ?>"><?= e($cat['tipo']) ?></span></td>
            <td><?= e($cat['categoria']) ?></td>
            <td class="font-semibold <?= ($cat['tipo'] ?? '') === 'receita' ? 'text-green-600' : 'text-red-600' ?>"><?= money($cat['total']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
const timelineMensal = <?= json_encode($timeline, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
new Chart(document.getElementById('graficoMensal'), {
  type: 'line',
  data: {
    labels: timelineMensal.map(i => i.dia),
    datasets: [
      {label: 'Receitas', data: timelineMensal.map(i => i.receitas), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)', fill: true, tension: .3},
      {label: 'Despesas', data: timelineMensal.map(i => i.despesas), borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.08)', fill: true, tension: .3}
    ]
  },
  options: {responsive: true, plugins: {legend: {position: 'top'}}}
});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
