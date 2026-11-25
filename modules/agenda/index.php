<?php
$page_title = 'Agenda';
require_once __DIR__ . '/../../includes/header.php';

$view = input('view', 'mes');
$ref = input('data', date('Y-m-d'));
$refTs = strtotime($ref);
$eid = active_company_id();
$hasFila = db_table_exists('agenda_fila_espera');
$hasConfirmacao = db_column_exists('agendamentos', 'confirmacao_status');
$hasUltimoLembrete = db_column_exists('agendamentos', 'ultimo_lembrete_em');

if ($view === 'mes') {
    $ini = date('Y-m-01', $refTs);
    $fim = date('Y-m-t', $refTs);
} elseif ($view === 'semana') {
    $dow = date('w', $refTs);
    $ini = date('Y-m-d', strtotime("-{$dow} days", $refTs));
    $fim = date('Y-m-d', strtotime('+' . (6 - $dow) . ' days', $refTs));
} else {
    $ini = $fim = $ref;
}

$stmt = db()->prepare("SELECT a.*, p.nome AS paciente_nome, u.nome AS prof_nome, pr.nome AS proc_nome
    FROM agendamentos a
    JOIN pacientes p ON p.id = a.paciente_id
    JOIN usuarios u ON u.id = a.profissional_id
    LEFT JOIN procedimentos pr ON pr.id = a.procedimento_id
    WHERE a.empresa_id = ? AND DATE(a.data_hora_inicio) BETWEEN ? AND ?
    ORDER BY a.data_hora_inicio");
$stmt->execute([$eid, $ini, $fim]);
$ags = $stmt->fetchAll() ?: [];

$porData = [];
foreach ($ags as $agendamento) {
    $dia = date('Y-m-d', strtotime((string) $agendamento['data_hora_inicio']));
    $porData[$dia][] = $agendamento;
}

$statusBadge = [
    'agendado' => 'badge-info',
    'confirmado' => 'badge-success',
    'realizado' => 'badge-secondary',
    'faltou' => 'badge-warning',
    'cancelado' => 'badge-danger',
];

$todayStart = date('Y-m-d 00:00:00');
$todayEnd = date('Y-m-d 23:59:59');
$kpiHoje = db()->prepare("SELECT
    SUM(CASE WHEN status IN ('agendado','confirmado') THEN 1 ELSE 0 END) AS proximos,
    SUM(CASE WHEN status='realizado' THEN 1 ELSE 0 END) AS realizados,
    SUM(CASE WHEN status='faltou' THEN 1 ELSE 0 END) AS faltas
    FROM agendamentos WHERE empresa_id=? AND data_hora_inicio BETWEEN ? AND ?");
$kpiHoje->execute([$eid, $todayStart, $todayEnd]);
$kpiHoje = $kpiHoje->fetch() ?: ['proximos' => 0, 'realizados' => 0, 'faltas' => 0];

$confirmacoesPendentes = 0;
if ($hasConfirmacao) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM agendamentos WHERE empresa_id=? AND status IN ('agendado','confirmado') AND data_hora_inicio >= NOW() AND COALESCE(confirmacao_status,'pendente')='pendente'");
    $stmt->execute([$eid]);
    $confirmacoesPendentes = (int) $stmt->fetchColumn();
}

$lembretesPendentes = 0;
$reminderSql = "SELECT COUNT(*) FROM agendamentos WHERE empresa_id=? AND status IN ('agendado','confirmado') AND data_hora_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)";
if ($hasUltimoLembrete) {
    $reminderSql .= " AND ultimo_lembrete_em IS NULL";
}
$stmt = db()->prepare($reminderSql);
$stmt->execute([$eid]);
$lembretesPendentes = (int) $stmt->fetchColumn();

$filaAtiva = 0;
if ($hasFila) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM agenda_fila_espera WHERE empresa_id=? AND status IN ('ativo','contatado')");
    $stmt->execute([$eid]);
    $filaAtiva = (int) $stmt->fetchColumn();
}
?>
<div class="space-y-5">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-calendar-week-fill text-primary"></i> Agenda Inteligente</h1>
      <p class="text-sm text-slate-500">Confirmação, lembretes, fila de encaixe e reaproveitamento de cancelamentos.</p>
    </div>
    <div class="flex gap-2 flex-wrap">
      <?php if ($hasFila): ?>
        <a href="fila.php" class="btn bg-violet-600 text-white"><i class="bi bi-hourglass-split"></i> Fila de Encaixe</a>
      <?php endif; ?>
      <a href="bloqueios.php" class="btn bg-slate-100 text-slate-700"><i class="bi bi-lock"></i> Bloqueios</a>
      <?php if (in_array($_SESSION['perfil'] ?? '', ['admin','recepcao','desenvolvedor'], true)): ?>
      <a href="solicitacoes.php" class="btn bg-slate-100 text-slate-700">Solicitações do portal</a>
      <?php endif; ?>
      <a href="novo.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nova Consulta</a>
    </div>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
    <div class="card p-4"><div class="text-xs uppercase text-slate-500">Hoje</div><div class="text-2xl font-bold text-sky-600"><?= (int) ($kpiHoje['proximos'] ?? 0) ?></div><div class="text-xs text-slate-400">Consultas agendadas/confirmadas</div></div>
    <div class="card p-4"><div class="text-xs uppercase text-slate-500">Realizados hoje</div><div class="text-2xl font-bold text-emerald-600"><?= (int) ($kpiHoje['realizados'] ?? 0) ?></div><div class="text-xs text-slate-400">Atendimentos concluídos</div></div>
    <div class="card p-4"><div class="text-xs uppercase text-slate-500">Faltas hoje</div><div class="text-2xl font-bold text-amber-600"><?= (int) ($kpiHoje['faltas'] ?? 0) ?></div><div class="text-xs text-slate-400">No-show do dia</div></div>
    <div class="card p-4"><div class="text-xs uppercase text-slate-500">Confirmações pendentes</div><div class="text-2xl font-bold text-indigo-600"><?= $confirmacoesPendentes ?></div><div class="text-xs text-slate-400">Consultas futuras aguardando retorno</div></div>
    <div class="card p-4"><div class="text-xs uppercase text-slate-500">Fila ativa / lembretes</div><div class="text-2xl font-bold text-violet-600"><?= $filaAtiva ?></div><div class="text-xs text-slate-400"><?= $lembretesPendentes ?> lembretes nas próximas 24h</div></div>
  </div>

  <div class="card p-4">
    <form method="GET" class="flex flex-wrap items-center gap-2 justify-between">
      <div class="flex flex-wrap items-center gap-2">
        <div class="inline-flex rounded-lg overflow-hidden border">
          <a href="?view=dia&data=<?= $ref ?>" class="px-3 py-1.5 text-sm <?= $view === 'dia' ? 'bg-primary text-white' : 'bg-white' ?>">Dia</a>
          <a href="?view=semana&data=<?= $ref ?>" class="px-3 py-1.5 text-sm <?= $view === 'semana' ? 'bg-primary text-white' : 'bg-white' ?>">Semana</a>
          <a href="?view=mes&data=<?= $ref ?>" class="px-3 py-1.5 text-sm <?= $view === 'mes' ? 'bg-primary text-white' : 'bg-white' ?>">Mês</a>
        </div>
        <input type="date" name="data" value="<?= e($ref) ?>" class="form-input w-auto" onchange="this.form.submit()">
        <input type="hidden" name="view" value="<?= e($view) ?>">
        <a href="?view=<?= e($view) ?>&data=<?= date('Y-m-d') ?>" class="text-sm text-primary">Hoje</a>
      </div>
      <div class="text-xs text-slate-500">Período: <?= format_date($ini) ?><?= $ini !== $fim ? ' até ' . format_date($fim) : '' ?></div>
    </form>
  </div>

  <?php if ($view === 'mes'): ?>
    <?php
    $primeiro = strtotime(date('Y-m-01', $refTs));
    $dowInicial = date('w', $primeiro);
    $celula = strtotime("-{$dowInicial} days", $primeiro);
    ?>
    <div class="card p-4">
      <div class="flex justify-between items-center mb-3">
        <a href="?view=mes&data=<?= date('Y-m-d', strtotime('-1 month', $refTs)) ?>" class="text-slate-500 hover:text-primary"><i class="bi bi-chevron-left"></i></a>
        <h2 class="font-bold text-slate-700"><?= e(format_month_year_ptbr($refTs)) ?></h2>
        <a href="?view=mes&data=<?= date('Y-m-d', strtotime('+1 month', $refTs)) ?>" class="text-slate-500 hover:text-primary"><i class="bi bi-chevron-right"></i></a>
      </div>
      <div class="calendar-grid">
        <?php foreach (['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $diaSemana): ?>
          <div class="text-xs text-center font-semibold text-slate-500 py-2"><?= $diaSemana ?></div>
        <?php endforeach; ?>
        <?php for ($i = 0; $i < 42; $i++):
          $dia = date('Y-m-d', $celula);
          $isOther = date('n', $celula) !== date('n', $refTs);
          $isToday = $dia === date('Y-m-d');
          $eventos = $porData[$dia] ?? [];
        ?>
          <div class="calendar-day <?= $isOther ? 'other-month' : '' ?> <?= $isToday ? 'today' : '' ?>">
            <div class="flex justify-between items-center">
              <span class="font-semibold text-xs <?= $isToday ? 'text-amber-700' : 'text-slate-700' ?>"><?= date('d', $celula) ?></span>
              <a href="novo.php?data=<?= $dia ?>" class="text-xs text-slate-400 hover:text-primary">+</a>
            </div>
            <?php foreach (array_slice($eventos, 0, 3) as $evento): ?>
              <a href="ver.php?id=<?= (int) $evento['id'] ?>" class="calendar-event" title="<?= e($evento['paciente_nome']) ?>">
                <?= date('H:i', strtotime((string) $evento['data_hora_inicio'])) ?> <?= e(mb_substr((string) $evento['paciente_nome'], 0, 12)) ?>
              </a>
            <?php endforeach; ?>
            <?php if (count($eventos) > 3): ?>
              <div class="text-xs text-slate-500 mt-1">+<?= count($eventos) - 3 ?> mais</div>
            <?php endif; ?>
          </div>
        <?php $celula = strtotime('+1 day', $celula); endfor; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="card overflow-hidden">
      <table class="table-modern">
        <thead><tr><th>Data/Hora</th><th>Paciente</th><th>Profissional</th><th>Procedimento</th><th>Valor</th><th>Status</th><th>Confirmação</th><th></th></tr></thead>
        <tbody>
          <?php if (!$ags): ?>
            <tr><td colspan="8" class="text-center py-10 text-slate-400">Nenhuma consulta neste período.</td></tr>
          <?php else: foreach ($ags as $agendamento): ?>
            <tr>
              <td><?= format_date($agendamento['data_hora_inicio'], true) ?></td>
              <td><a href="../pacientes/ver.php?id=<?= (int) $agendamento['paciente_id'] ?>" class="hover:underline font-medium"><?= e($agendamento['paciente_nome']) ?></a></td>
              <td><?= e($agendamento['prof_nome']) ?></td>
              <td><?= e($agendamento['proc_nome']) ?></td>
              <td><?= money($agendamento['valor']) ?></td>
              <td><span class="badge <?= $statusBadge[$agendamento['status']] ?? 'badge-secondary' ?>"><?= e($agendamento['status']) ?></span></td>
              <td>
                <?php if ($hasConfirmacao): ?>
                  <span class="badge <?= status_badge_class($agendamento['confirmacao_status'] ?? 'pendente') ?>"><?= e($agendamento['confirmacao_status'] ?? 'pendente') ?></span>
                <?php else: ?>
                  <span class="text-xs text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="text-right whitespace-nowrap">
                <a href="ver.php?id=<?= (int) $agendamento['id'] ?>" class="text-sky-600 mx-1"><i class="bi bi-eye"></i></a>
                <a href="editar.php?id=<?= (int) $agendamento['id'] ?>" class="text-amber-600 mx-1"><i class="bi bi-pencil"></i></a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
