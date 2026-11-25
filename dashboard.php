<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$eid = active_company_id();

$total_pac = db()->prepare("SELECT COUNT(*) FROM pacientes WHERE empresa_id=? AND ativo=1");
$total_pac->execute([$eid]); $total_pac = $total_pac->fetchColumn();

$total_ag_hoje = db()->prepare("SELECT COUNT(*) FROM agendamentos WHERE empresa_id=? AND DATE(data_hora_inicio)=CURDATE()");
$total_ag_hoje->execute([$eid]); $total_ag_hoje = $total_ag_hoje->fetchColumn();

$total_ag_mes = db()->prepare("SELECT COUNT(*) FROM agendamentos WHERE empresa_id=? AND MONTH(data_hora_inicio)=MONTH(CURDATE()) AND YEAR(data_hora_inicio)=YEAR(CURDATE())");
$total_ag_mes->execute([$eid]); $total_ag_mes = $total_ag_mes->fetchColumn();

$rec_mes = 0;
if (db_table_exists('contas_financeiras')) {
    try {
        $stmtReceita = db()->prepare("SELECT COALESCE(SUM(valor),0) FROM contas_financeiras WHERE empresa_id=? AND tipo='receita' AND status='pago' AND MONTH(data_pagamento)=MONTH(CURDATE()) AND YEAR(data_pagamento)=YEAR(CURDATE())");
        $stmtReceita->execute([$eid]);
        $rec_mes = (float) $stmtReceita->fetchColumn();
    } catch (Exception $e) {
        $rec_mes = 0;
    }
}

$proximos = db()->prepare("
  SELECT a.*, p.nome as paciente_nome, u.nome as profissional_nome
  FROM agendamentos a JOIN pacientes p ON p.id=a.paciente_id JOIN usuarios u ON u.id=a.profissional_id
  WHERE a.empresa_id=? AND a.data_hora_inicio >= NOW() AND a.status IN ('agendado','confirmado')
  ORDER BY a.data_hora_inicio ASC LIMIT 6");
$proximos->execute([$eid]); $proximos = $proximos->fetchAll();

$temaAtual = get_theme();
$specialtyMeta = specialty_theme_meta($temaAtual);
$nomeSaudacao = trim((string) ($user['nome'] ?? ($_SESSION['nome'] ?? '')));
$primeiroNome = $nomeSaudacao !== '' ? explode(' ', $nomeSaudacao)[0] : 'Usuário';
$specialtyStats = is_custom_specialty_theme($temaAtual) ? specialty_module_stats($temaAtual) : ['records_month' => 0, 'patients_month' => 0, 'required_fields' => 0];
$specialtyResources = is_custom_specialty_theme($temaAtual) ? specialty_theme_resources($temaAtual) : [];
$psicoPlanosAbertos = 0;
$psicoEscalasMes = 0;
$temProtocolos = db_table_exists('protocolos_clinicos');
$temModelos = db_table_exists('modelos_clinicos');
$temCuidados = db_table_exists('planos_cuidado') && db_table_exists('paciente_alertas');
$protocolosAtivos = 0;
$modelosAtivos = 0;
$alertasAtivos = 0;
$alertasCriticos = 0;
$planosAbertos = 0;
$dashboardTemaFiltro = in_array($temaAtual, ['psicologia','psiquiatria','pediatria','ginecologia_obstetricia','ortopedia_traumatologia','cardiologia','oftalmologia','neurologia'], true) ? $temaAtual : '';

if ($temaAtual === 'psicologia') {
    try {
        $stmt = db()->prepare("SELECT COUNT(*) FROM psicologia_planos WHERE empresa_id=? AND status IN ('ativo','em_acompanhamento')");
        $stmt->execute([$eid]);
        $psicoPlanosAbertos = (int) $stmt->fetchColumn();

        $stmt = db()->prepare("SELECT COUNT(*) FROM psicologia_escalas WHERE empresa_id=? AND MONTH(data_aplicacao)=MONTH(CURDATE()) AND YEAR(data_aplicacao)=YEAR(CURDATE())");
        $stmt->execute([$eid]);
        $psicoEscalasMes = (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        $psicoPlanosAbertos = 0;
        $psicoEscalasMes = 0;
    }
}

if ($temProtocolos) {
    $sql = "SELECT COUNT(*) FROM protocolos_clinicos WHERE empresa_id=? AND ativo=1";
    $params = [$eid];
    if ($dashboardTemaFiltro !== '') {
        $sql .= " AND tema=?";
        $params[] = $dashboardTemaFiltro;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $protocolosAtivos = (int) $stmt->fetchColumn();
}

if ($temModelos) {
    $sql = "SELECT COUNT(*) FROM modelos_clinicos WHERE empresa_id=? AND ativo=1";
    $params = [$eid];
    if ($dashboardTemaFiltro !== '') {
        $sql .= " AND tema=?";
        $params[] = $dashboardTemaFiltro;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $modelosAtivos = (int) $stmt->fetchColumn();
}

if ($temCuidados) {
    $sqlAlertas = "SELECT COUNT(*) FROM paciente_alertas WHERE empresa_id=? AND ativo=1";
    $paramsAlertas = [$eid];
    if ($dashboardTemaFiltro !== '') {
        $sqlAlertas .= " AND tema=?";
        $paramsAlertas[] = $dashboardTemaFiltro;
    }
    $stmt = db()->prepare($sqlAlertas);
    $stmt->execute($paramsAlertas);
    $alertasAtivos = (int) $stmt->fetchColumn();

    $sqlCriticos = $sqlAlertas . " AND prioridade='critica'";
    $stmt = db()->prepare($sqlCriticos);
    $stmt->execute($paramsAlertas);
    $alertasCriticos = (int) $stmt->fetchColumn();

    $sqlPlanos = "SELECT COUNT(*) FROM planos_cuidado WHERE empresa_id=? AND status IN ('ativo','em_acompanhamento')";
    $paramsPlanos = [$eid];
    if ($dashboardTemaFiltro !== '') {
        $sqlPlanos .= " AND tema=?";
        $paramsPlanos[] = $dashboardTemaFiltro;
    }
    $stmt = db()->prepare($sqlPlanos);
    $stmt->execute($paramsPlanos);
    $planosAbertos = (int) $stmt->fetchColumn();
}
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-800">Olá, <?= e($primeiroNome) ?>! 👋</h1>
  <p class="text-slate-500 text-sm">Resumo do seu consultório · <?= date('d/m/Y') ?></p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="card p-5">
    <div class="flex items-center justify-between">
      <div><p class="text-xs uppercase text-slate-500">Pacientes</p><p class="text-3xl font-bold"><?= $total_pac ?></p></div>
      <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl"><i class="bi bi-people-fill"></i></div>
    </div>
  </div>
  <div class="card p-5">
    <div class="flex items-center justify-between">
      <div><p class="text-xs uppercase text-slate-500">Consultas Hoje</p><p class="text-3xl font-bold"><?= $total_ag_hoje ?></p></div>
      <div class="w-12 h-12 rounded-xl bg-green-100 text-green-600 flex items-center justify-center text-2xl"><i class="bi bi-calendar-check-fill"></i></div>
    </div>
  </div>
  <div class="card p-5">
    <div class="flex items-center justify-between">
      <div><p class="text-xs uppercase text-slate-500">Atendim. Mês</p><p class="text-3xl font-bold"><?= $total_ag_mes ?></p></div>
      <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-2xl"><i class="bi bi-graph-up-arrow"></i></div>
    </div>
  </div>
  <div class="card p-5">
    <div class="flex items-center justify-between">
      <div><p class="text-xs uppercase text-slate-500">Receita Mês</p><p class="text-xl font-bold"><?= money($rec_mes) ?></p></div>
      <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl"><i class="bi bi-cash-coin"></i></div>
    </div>
    <a href="<?= APP_URL ?>/modules/relatorios/index.php" class="text-xs text-primary mt-2 inline-block">Ver relatórios →</a>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 card p-5">
    <h2 class="font-bold mb-4"><i class="bi bi-calendar-event mr-1"></i> Próximas Consultas</h2>
    <?php if (empty($proximos)): ?>
      <p class="text-center text-slate-400 py-8">Nenhuma consulta agendada.</p>
    <?php else: ?>
    <div class="space-y-2">
      <?php foreach($proximos as $a): ?>
      <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50">
        <div class="text-center w-14">
          <div class="text-xs uppercase text-slate-500"><?= date('M', strtotime($a['data_hora_inicio'])) ?></div>
          <div class="text-2xl font-bold text-primary"><?= date('d', strtotime($a['data_hora_inicio'])) ?></div>
        </div>
        <div class="flex-1">
          <div class="font-medium"><?= e($a['paciente_nome']) ?></div>
          <div class="text-xs text-slate-500"><i class="bi bi-clock"></i> <?= date('H:i', strtotime($a['data_hora_inicio'])) ?> — Dr(a). <?= e($a['profissional_nome']) ?></div>
        </div>
        <span class="badge badge-<?= $a['status']==='confirmado'?'success':'info' ?>"><?= e($a['status']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="card p-5">
    <h2 class="font-bold mb-4"><i class="bi bi-lightning-charge"></i> Acesso Rápido</h2>
    <div class="grid grid-cols-2 gap-3">
      <a href="<?= APP_URL ?>/modules/pacientes/novo.php" class="p-4 rounded-xl border hover:border-primary hover:bg-blue-50 text-center"><i class="bi bi-person-plus text-3xl text-primary"></i><p class="text-xs mt-2">Novo Paciente</p></a>
      <a href="<?= APP_URL ?>/modules/agenda/novo.php" class="p-4 rounded-xl border hover:border-primary hover:bg-blue-50 text-center"><i class="bi bi-calendar-plus text-3xl text-primary"></i><p class="text-xs mt-2">Agendar</p></a>
      <a href="<?= APP_URL ?>/modules/financeiro/nova.php?tipo=receita" class="p-4 rounded-xl border hover:border-primary hover:bg-blue-50 text-center"><i class="bi bi-cash text-3xl text-primary"></i><p class="text-xs mt-2">Receita</p></a>
      <a href="<?= APP_URL ?>/modules/nfse/novo.php" class="p-4 rounded-xl border hover:border-primary hover:bg-blue-50 text-center"><i class="bi bi-receipt text-3xl text-primary"></i><p class="text-xs mt-2">NFSe</p></a>
      <?php if ($temaAtual==='dentista'): ?>
        <a href="<?= APP_URL ?>/modules/planos/novo.php" class="p-4 rounded-xl border hover:border-primary hover:bg-blue-50 text-center"><i class="bi bi-clipboard2-check text-3xl text-primary"></i><p class="text-xs mt-2">Plano Trat.</p></a>
        <a href="<?= APP_URL ?>/modules/prontuario/odontograma.php" class="p-4 rounded-xl border hover:border-primary hover:bg-blue-50 text-center"><i class="bi bi-emoji-smile text-3xl text-primary"></i><p class="text-xs mt-2">Odontograma</p></a>
      <?php elseif ($temaAtual==='psicologia'): ?>
        <a href="<?= APP_URL ?>/modules/psicologia/escalas.php" class="p-4 rounded-xl border hover:border-primary hover:bg-violet-50 text-center"><i class="bi bi-clipboard-data text-3xl text-primary"></i><p class="text-xs mt-2">Escalas</p></a>
        <a href="<?= APP_URL ?>/modules/psicologia/planos.php" class="p-4 rounded-xl border hover:border-primary hover:bg-violet-50 text-center"><i class="bi bi-bullseye text-3xl text-primary"></i><p class="text-xs mt-2">Plano Terap.</p></a>
      <?php elseif (is_custom_specialty_theme($temaAtual) && count($specialtyResources) >= 2): ?>
        <?php foreach (array_slice($specialtyResources, 0, 2) as $resource): ?>
          <a href="<?= e($resource['url']) ?>" class="p-4 rounded-xl border hover:border-primary hover:bg-blue-50 text-center"><i class="bi <?= e($resource['icon']) ?> text-3xl text-primary"></i><p class="text-xs mt-2"><?= e($resource['label']) ?></p></a>
        <?php endforeach; ?>
      <?php else: ?>
        <a href="<?= APP_URL ?>/modules/exames/nova.php" class="p-4 rounded-xl border hover:border-primary hover:bg-blue-50 text-center"><i class="bi bi-clipboard2-pulse text-3xl text-primary"></i><p class="text-xs mt-2">Exames</p></a>
        <a href="<?= APP_URL ?>/modules/telemedicina/nova.php" class="p-4 rounded-xl border hover:border-primary hover:bg-blue-50 text-center"><i class="bi bi-camera-video text-3xl text-primary"></i><p class="text-xs mt-2">Telemedicina</p></a>
      <?php endif; ?>
    </div>
    <button id="pwa-install-btn" class="hidden mt-4 w-full btn btn-primary text-sm"><i class="bi bi-download"></i> Instalar App</button>

    <?php if ($temaAtual === 'psicologia'): ?>
    <div class="mt-4 rounded-xl border border-violet-200 bg-gradient-to-r from-violet-50 to-fuchsia-50 p-4">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-violet-900"><i class="bi bi-flower1"></i> Painel de Psicologia</h3>
          <p class="text-xs text-violet-700 mt-1">Recursos separados para acompanhamento terapêutico, escalas clínicas e teleatendimento.</p>
        </div>
        <a href="<?= APP_URL ?>/modules/psicologia/index.php" class="text-xs font-semibold text-violet-700 hover:underline">Abrir módulo</a>
      </div>
      <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
        <div class="rounded-lg bg-white/80 p-3 border border-violet-100">
          <div class="text-slate-500 text-xs uppercase">Planos Ativos</div>
          <div class="text-2xl font-bold text-violet-700"><?= $psicoPlanosAbertos ?></div>
        </div>
        <div class="rounded-lg bg-white/80 p-3 border border-violet-100">
          <div class="text-slate-500 text-xs uppercase">Escalas no mês</div>
          <div class="text-2xl font-bold text-fuchsia-700"><?= $psicoEscalasMes ?></div>
        </div>
      </div>
    </div>
    <?php elseif (is_custom_specialty_theme($temaAtual)): ?>
    <div class="mt-4 rounded-xl border border-primary/20 bg-gradient-to-r from-white to-slate-50 p-4">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-slate-900"><span class="mr-1"><?= e($specialtyMeta['icone']) ?></span> <?= e($specialtyMeta['nome']) ?></h3>
          <p class="text-xs text-slate-600 mt-1"><?= e($specialtyMeta['descricao']) ?></p>
        </div>
        <a href="<?= specialty_module_url($temaAtual) ?>" class="text-xs font-semibold text-primary hover:underline">Abrir módulo</a>
      </div>
      <div class="grid grid-cols-3 gap-3 mt-4 text-sm">
        <div class="rounded-lg bg-white p-3 border border-slate-100">
          <div class="text-slate-500 text-xs uppercase">Registros no mês</div>
          <div class="text-2xl font-bold text-slate-800"><?= (int) $specialtyStats['records_month'] ?></div>
        </div>
        <div class="rounded-lg bg-white p-3 border border-slate-100">
          <div class="text-slate-500 text-xs uppercase">Pacientes no mês</div>
          <div class="text-2xl font-bold text-slate-800"><?= (int) $specialtyStats['patients_month'] ?></div>
        </div>
        <div class="rounded-lg bg-white p-3 border border-slate-100">
          <div class="text-slate-500 text-xs uppercase">Campos obrigatórios</div>
          <div class="text-2xl font-bold text-slate-800"><?= (int) $specialtyStats['required_fields'] ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($temProtocolos || $temModelos || $temCuidados): ?>
    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="font-bold text-slate-900"><i class="bi bi-journal-richtext mr-1"></i> Governança clínica</h3>
          <p class="text-xs text-slate-600 mt-1">Biblioteca assistencial, alertas e planos de seguimento<?= $dashboardTemaFiltro !== '' ? ' para o tema atual.' : ' da clínica.' ?></p>
        </div>
        <div class="flex gap-2 text-xs flex-wrap justify-end">
          <?php if ($temProtocolos): ?><a href="<?= APP_URL ?>/modules/protocolos/index.php<?= $dashboardTemaFiltro !== '' ? '?tema=' . urlencode($dashboardTemaFiltro) : '' ?>" class="text-primary hover:underline">Protocolos</a><?php endif; ?>
          <?php if ($temModelos): ?><a href="<?= APP_URL ?>/modules/modelos_clinicos/index.php<?= $dashboardTemaFiltro !== '' ? '?tema=' . urlencode($dashboardTemaFiltro) : '' ?>" class="text-primary hover:underline">Modelos</a><?php endif; ?>
          <?php if ($temCuidados): ?><a href="<?= APP_URL ?>/modules/cuidados/index.php<?= $dashboardTemaFiltro !== '' ? '?tema=' . urlencode($dashboardTemaFiltro) : '' ?>" class="text-primary hover:underline">Cuidados</a><?php endif; ?>
        </div>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 text-sm">
        <div class="rounded-lg bg-slate-50 p-3 border border-slate-100">
          <div class="text-slate-500 text-xs uppercase">Protocolos</div>
          <div class="text-2xl font-bold text-slate-800"><?= $protocolosAtivos ?></div>
        </div>
        <div class="rounded-lg bg-slate-50 p-3 border border-slate-100">
          <div class="text-slate-500 text-xs uppercase">Modelos</div>
          <div class="text-2xl font-bold text-slate-800"><?= $modelosAtivos ?></div>
        </div>
        <div class="rounded-lg bg-slate-50 p-3 border border-slate-100">
          <div class="text-slate-500 text-xs uppercase">Alertas ativos</div>
          <div class="text-2xl font-bold text-slate-800"><?= $alertasAtivos ?></div>
          <?php if ($alertasCriticos > 0): ?><div class="text-[11px] text-rose-600 mt-1"><?= $alertasCriticos ?> crítico(s)</div><?php endif; ?>
        </div>
        <div class="rounded-lg bg-slate-50 p-3 border border-slate-100">
          <div class="text-slate-500 text-xs uppercase">Planos abertos</div>
          <div class="text-2xl font-bold text-slate-800"><?= $planosAbertos ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
