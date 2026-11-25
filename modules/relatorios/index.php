<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Relatórios BI';

$ini = input('data_inicio', date('Y-m-d', strtotime('-6 months')));
$fim = input('data_fim', date('Y-m-d'));
$eid = active_company_id();
$hasRepasse = db_column_exists('usuarios', 'percentual_repasse');
$iniSql = $ini . ' 00:00:00';
$fimSql = $fim . ' 23:59:59';

$capacity = theoretical_schedule_capacity($ini, $fim, $eid);
$totalSlots = (int) ($capacity['total_slots'] ?? 0);
$slotsPorProfissional = max(1, (int) (($capacity['working_days'] ?? 0) * ($capacity['slots_per_day'] ?? 1)));

$fatStmt = db()->prepare("SELECT DATE_FORMAT(data_pagamento, '%Y-%m') AS mes, SUM(valor) AS total
  FROM contas_financeiras
  WHERE empresa_id=? AND tipo='receita' AND status='pago' AND data_pagamento BETWEEN ? AND ?
  GROUP BY mes ORDER BY mes");
$fatStmt->execute([$eid, $ini, $fim]);
$fat = $fatStmt->fetchAll() ?: [];

$despStmt = db()->prepare("SELECT DATE_FORMAT(data_pagamento, '%Y-%m') AS mes, SUM(valor) AS total
  FROM contas_financeiras
  WHERE empresa_id=? AND tipo='despesa' AND status='pago' AND data_pagamento BETWEEN ? AND ?
  GROUP BY mes ORDER BY mes");
$despStmt->execute([$eid, $ini, $fim]);
$desp = $despStmt->fetchAll() ?: [];

$procsStmt = db()->prepare("SELECT pr.nome, COUNT(*) AS qtd, SUM(a.valor) AS receita
  FROM agendamentos a
  JOIN procedimentos pr ON pr.id = a.procedimento_id
  WHERE a.empresa_id=? AND a.status='realizado' AND a.data_hora_inicio BETWEEN ? AND ?
  GROUP BY pr.id, pr.nome
  ORDER BY receita DESC, qtd DESC
  LIMIT 8");
$procsStmt->execute([$eid, $iniSql, $fimSql]);
$procs = $procsStmt->fetchAll() ?: [];

$statusStmt = db()->prepare("SELECT status, COUNT(*) AS qtd
  FROM agendamentos
  WHERE empresa_id=? AND data_hora_inicio BETWEEN ? AND ?
  GROUP BY status");
$statusStmt->execute([$eid, $iniSql, $fimSql]);
$status_ag = $statusStmt->fetchAll() ?: [];

$novosStmt = db()->prepare("SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes, COUNT(*) AS qtd
  FROM pacientes
  WHERE empresa_id=? AND criado_em BETWEEN ? AND ?
  GROUP BY mes ORDER BY mes");
$novosStmt->execute([$eid, $ini . ' 00:00:00', $fim . ' 23:59:59']);
$novos = $novosStmt->fetchAll() ?: [];

$especialidadesStmt = db()->prepare("SELECT
    COALESCE(NULLIF(u.especialidade, ''), REPLACE(COALESCE(u.tema_preferido, 'medico'), '_', ' '), 'geral') AS especialidade,
    COUNT(*) AS realizados,
    SUM(a.valor) AS receita
  FROM agendamentos a
  JOIN usuarios u ON u.id = a.profissional_id
  WHERE a.empresa_id=? AND a.status='realizado' AND a.data_hora_inicio BETWEEN ? AND ?
  GROUP BY especialidade
  ORDER BY receita DESC, realizados DESC");
$especialidadesStmt->execute([$eid, $iniSql, $fimSql]);
$especialidades = $especialidadesStmt->fetchAll() ?: [];

$repasseSelect = $hasRepasse ? 'COALESCE(u.percentual_repasse, 0)' : '0';
$profissionaisStmt = db()->prepare("SELECT
    u.id,
    u.nome,
    COALESCE(NULLIF(u.especialidade, ''), REPLACE(COALESCE(u.tema_preferido, 'medico'), '_', ' '), 'geral') AS especialidade,
    {$repasseSelect} AS percentual_repasse,
    SUM(CASE WHEN a.status='realizado' THEN 1 ELSE 0 END) AS realizados,
    SUM(CASE WHEN a.status='faltou' THEN 1 ELSE 0 END) AS faltas,
    SUM(CASE WHEN a.status IN ('agendado','confirmado','realizado','faltou') THEN 1 ELSE 0 END) AS ocupados,
    SUM(CASE WHEN a.status='realizado' THEN COALESCE(a.valor,0) ELSE 0 END) AS receita
  FROM usuarios u
  LEFT JOIN agendamentos a
    ON a.profissional_id = u.id
   AND a.empresa_id = u.empresa_id
   AND a.data_hora_inicio BETWEEN ? AND ?
  WHERE u.empresa_id=? AND u.ativo=1 AND u.perfil IN ('admin','profissional')
  GROUP BY u.id, u.nome, u.especialidade, u.tema_preferido" . ($hasRepasse ? ', u.percentual_repasse' : '') . "
  ORDER BY receita DESC, realizados DESC, u.nome");
$profissionaisStmt->execute([$iniSql, $fimSql, $eid]);
$ocup = $profissionaisStmt->fetchAll() ?: [];

$total_realizados = 0;
$total_faltou = 0;
$totalOcupados = 0;
$repasseProjetado = 0.0;
foreach ($ocup as &$profissional) {
    $profissional['realizados'] = (int) ($profissional['realizados'] ?? 0);
    $profissional['faltas'] = (int) ($profissional['faltas'] ?? 0);
    $profissional['ocupados'] = (int) ($profissional['ocupados'] ?? 0);
    $profissional['receita'] = (float) ($profissional['receita'] ?? 0);
    $profissional['percentual_repasse'] = (float) ($profissional['percentual_repasse'] ?? 0);
    $profissional['ticket_medio'] = $profissional['realizados'] > 0 ? ($profissional['receita'] / $profissional['realizados']) : 0;
    $profissional['ocupacao_pct'] = $slotsPorProfissional > 0 ? round(($profissional['ocupados'] * 100) / $slotsPorProfissional, 1) : 0;
    $profissional['repasse_valor'] = $profissional['receita'] * ($profissional['percentual_repasse'] / 100);

    $total_realizados += $profissional['realizados'];
    $total_faltou += $profissional['faltas'];
    $totalOcupados += $profissional['ocupados'];
    $repasseProjetado += $profissional['repasse_valor'];
}
unset($profissional);

$rec_total = array_sum(array_map(static fn($row) => (float) ($row['total'] ?? 0), $fat));
$desp_total = array_sum(array_map(static fn($row) => (float) ($row['total'] ?? 0), $desp));
$ticket_medio = $total_realizados > 0 ? $rec_total / $total_realizados : 0;
$taxa_noshow = ($total_realizados + $total_faltou) > 0 ? round($total_faltou * 100 / ($total_realizados + $total_faltou), 1) : 0;
$ocupacaoGeral = $totalSlots > 0 ? round($totalOcupados * 100 / $totalSlots, 1) : 0;
$receitaEspecialidades = array_sum(array_map(static fn($row) => (float) ($row['receita'] ?? 0), $especialidades));
$especialidadesAtivas = count(array_filter($especialidades, static fn($row) => (float) ($row['receita'] ?? 0) > 0));

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="space-y-5">
  <div>
    <h1 class="text-2xl font-bold"><i class="bi bi-bar-chart-fill text-primary"></i> Relatórios BI</h1>
    <p class="text-sm text-slate-500">MVP operacional e financeiro com ocupação, faltas, ticket médio, receita por especialidade e repasse.</p>
  </div>

  <form method="GET" class="card p-4 flex flex-wrap gap-3 items-end">
    <div>
      <label class="text-xs text-slate-500">De</label>
      <input type="date" name="data_inicio" value="<?= e($ini) ?>" class="form-input">
    </div>
    <div>
      <label class="text-xs text-slate-500">Até</label>
      <input type="date" name="data_fim" value="<?= e($fim) ?>" class="form-input">
    </div>
    <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
  </form>

  <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">Faturamento</p><p class="text-xl font-bold text-emerald-600"><?= money($rec_total) ?></p><p class="text-xs text-slate-400">Receitas pagas no período</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">Ocupação</p><p class="text-xl font-bold text-sky-600"><?= $ocupacaoGeral ?>%</p><p class="text-xs text-slate-400"><?= $totalOcupados ?> slots ocupados de <?= $totalSlots ?></p></div>
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">No-show</p><p class="text-xl font-bold text-amber-600"><?= $taxa_noshow ?>%</p><p class="text-xs text-slate-400"><?= $total_faltou ?> faltas registradas</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">Ticket Médio</p><p class="text-xl font-bold text-violet-600"><?= money($ticket_medio) ?></p><p class="text-xs text-slate-400"><?= $total_realizados ?> atendimentos realizados</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">Repasse projetado</p><p class="text-xl font-bold text-fuchsia-600"><?= money($repasseProjetado) ?></p><p class="text-xs text-slate-400">Com base nos percentuais dos profissionais</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">Especialidades ativas</p><p class="text-xl font-bold text-indigo-600"><?= $especialidadesAtivas ?></p><p class="text-xs text-slate-400"><?= money($receitaEspecialidades) ?> distribuídos por especialidade</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">Despesas</p><p class="text-xl font-bold text-rose-600"><?= money($desp_total) ?></p><p class="text-xs text-slate-400">Pagamentos realizados</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">Margem bruta</p><p class="text-xl font-bold <?= ($rec_total - $desp_total) >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $rec_total > 0 ? round((($rec_total - $desp_total) * 100) / $rec_total, 1) : 0 ?>%</p><p class="text-xs text-slate-400">Lucro bruto <?= money($rec_total - $desp_total) ?></p></div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
    <div class="card p-5">
      <h3 class="font-bold mb-3">Faturamento × Despesas</h3>
      <canvas id="g1" height="220"></canvas>
    </div>
    <div class="card p-5">
      <h3 class="font-bold mb-3">Status de agendamentos</h3>
      <canvas id="g2" height="220"></canvas>
    </div>
    <div class="card p-5">
      <h3 class="font-bold mb-3">Receita por especialidade</h3>
      <canvas id="g3" height="220"></canvas>
    </div>
    <div class="card p-5">
      <h3 class="font-bold mb-3">Repasse projetado por profissional</h3>
      <canvas id="g4" height="220"></canvas>
    </div>
    <div class="card p-5">
      <h3 class="font-bold mb-3">Top procedimentos por receita</h3>
      <canvas id="g5" height="220"></canvas>
    </div>
    <div class="card p-5">
      <h3 class="font-bold mb-3">Novos pacientes</h3>
      <canvas id="g6" height="220"></canvas>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="xl:col-span-2 card p-5">
      <div class="flex items-center justify-between gap-2 mb-4">
        <h3 class="font-bold text-slate-800">Ocupação e repasse por profissional</h3>
        <span class="text-xs text-slate-400">Capacidade teórica: <?= $slotsPorProfissional ?> slots/profissional</span>
      </div>
      <div class="overflow-x-auto">
        <table class="table-modern">
          <thead><tr><th>Profissional</th><th>Especialidade</th><th>Ocupação</th><th>Realizados</th><th>Faltas</th><th>Receita</th><th>Ticket</th><th>Repasse</th></tr></thead>
          <tbody>
            <?php if (!$ocup): ?>
              <tr><td colspan="8" class="text-center py-10 text-slate-400">Nenhum profissional encontrado.</td></tr>
            <?php else: foreach ($ocup as $row): ?>
              <tr>
                <td class="font-medium"><?= e($row['nome']) ?></td>
                <td><?= e(ucwords(str_replace('_', ' ', (string) $row['especialidade']))) ?></td>
                <td>
                  <div class="font-semibold text-sky-700"><?= number_format((float) $row['ocupacao_pct'], 1, ',', '.') ?>%</div>
                  <div class="text-xs text-slate-400"><?= (int) $row['ocupados'] ?> / <?= $slotsPorProfissional ?></div>
                </td>
                <td><?= (int) $row['realizados'] ?></td>
                <td><?= (int) $row['faltas'] ?></td>
                <td class="text-emerald-600 font-semibold"><?= money($row['receita']) ?></td>
                <td><?= money($row['ticket_medio']) ?></td>
                <td>
                  <div class="font-semibold text-fuchsia-700"><?= money($row['repasse_valor']) ?></div>
                  <div class="text-xs text-slate-400"><?= number_format((float) $row['percentual_repasse'], 2, ',', '.') ?>%</div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card p-5">
      <h3 class="font-bold text-slate-800 mb-4">Especialidades</h3>
      <div class="space-y-3">
        <?php if (!$especialidades): ?>
          <p class="text-sm text-slate-400">Sem especialidades com receita no período.</p>
        <?php else: foreach ($especialidades as $item):
          $pct = $receitaEspecialidades > 0 ? round(((float) $item['receita'] * 100) / $receitaEspecialidades, 1) : 0;
        ?>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-center justify-between gap-3">
              <div>
                <div class="font-semibold text-slate-800"><?= e(ucwords(str_replace('_', ' ', (string) $item['especialidade']))) ?></div>
                <div class="text-xs text-slate-500"><?= (int) $item['realizados'] ?> atendimentos realizados</div>
              </div>
              <div class="text-right">
                <div class="font-semibold text-emerald-600"><?= money($item['receita']) ?></div>
                <div class="text-xs text-slate-400"><?= number_format($pct, 1, ',', '.') ?>% da receita</div>
              </div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
const fat = <?= json_encode($fat, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const desp = <?= json_encode($desp, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const procs = <?= json_encode($procs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const statusAg = <?= json_encode($status_ag, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const novos = <?= json_encode($novos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const especialidades = <?= json_encode($especialidades, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const profissionais = <?= json_encode(array_map(static function ($row) {
    return [
        'nome' => (string) $row['nome'],
        'repasse_valor' => (float) $row['repasse_valor'],
        'ocupacao_pct' => (float) $row['ocupacao_pct'],
    ];
}, $ocup), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const meses = [...new Set([...fat.map(f => f.mes), ...desp.map(d => d.mes), ...novos.map(n => n.mes)])].sort();
const fatPorMes = meses.map(m => Number((fat.find(f => f.mes === m) || {}).total || 0));
const despPorMes = meses.map(m => Number((desp.find(d => d.mes === m) || {}).total || 0));
const novosPorMes = meses.map(m => Number((novos.find(n => n.mes === m) || {}).qtd || 0));

new Chart(document.getElementById('g1'), {
  type: 'bar',
  data: {labels: meses, datasets: [
    {label: 'Receitas', data: fatPorMes, backgroundColor: '#10b981'},
    {label: 'Despesas', data: despPorMes, backgroundColor: '#ef4444'}
  ]},
  options: {responsive: true, plugins: {legend: {position: 'top'}}}
});

new Chart(document.getElementById('g2'), {
  type: 'doughnut',
  data: {
    labels: statusAg.map(s => s.status),
    datasets: [{data: statusAg.map(s => Number(s.qtd || 0)), backgroundColor: ['#0ea5e9', '#10b981', '#64748b', '#f59e0b', '#ef4444']}]
  },
  options: {responsive: true}
});

new Chart(document.getElementById('g3'), {
  type: 'bar',
  data: {
    labels: especialidades.map(item => item.especialidade),
    datasets: [{label: 'Receita', data: especialidades.map(item => Number(item.receita || 0)), backgroundColor: '#6366f1'}]
  },
  options: {indexAxis: 'y', plugins: {legend: {display: false}}}
});

new Chart(document.getElementById('g4'), {
  type: 'bar',
  data: {
    labels: profissionais.map(item => item.nome),
    datasets: [{label: 'Repasse projetado', data: profissionais.map(item => Number(item.repasse_valor || 0)), backgroundColor: '#d946ef'}]
  },
  options: {plugins: {legend: {display: false}}}
});

new Chart(document.getElementById('g5'), {
  type: 'bar',
  data: {
    labels: procs.map(p => p.nome),
    datasets: [{label: 'Receita', data: procs.map(p => Number(p.receita || 0)), backgroundColor: '#0ea5e9'}]
  },
  options: {indexAxis: 'y', plugins: {legend: {display: false}}}
});

new Chart(document.getElementById('g6'), {
  type: 'line',
  data: {
    labels: meses,
    datasets: [{label: 'Novos pacientes', data: novosPorMes, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,.10)', fill: true, tension: .3}]
  },
  options: {plugins: {legend: {display: true}}}
});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>