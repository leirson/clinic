<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

if (get_theme() !== 'psicologia' && !is_developer()) {
    flash('warning', 'O módulo de Psicologia é exclusivo para o tema Psicologia.');
    redirect(APP_URL . '/dashboard.php');
}

$page_title = 'Psicologia';
$eid = active_company_id();

$stats = [
    'planos_ativos' => 0,
    'escalas_mes' => 0,
    'teleatendimentos_mes' => 0,
    'evolucoes_mes' => 0,
];
$planos = [];
$escalas = [];

try {
    $stmt = db()->prepare("SELECT COUNT(*) FROM psicologia_planos WHERE empresa_id=? AND status IN ('ativo','em_acompanhamento')");
    $stmt->execute([$eid]);
    $stats['planos_ativos'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(*) FROM psicologia_escalas WHERE empresa_id=? AND MONTH(data_aplicacao)=MONTH(CURDATE()) AND YEAR(data_aplicacao)=YEAR(CURDATE())");
    $stmt->execute([$eid]);
    $stats['escalas_mes'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(*) FROM agendamentos WHERE empresa_id=? AND modalidade='telemedicina' AND MONTH(data_hora_inicio)=MONTH(CURDATE()) AND YEAR(data_hora_inicio)=YEAR(CURDATE())");
    $stmt->execute([$eid]);
    $stats['teleatendimentos_mes'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(*) FROM prontuarios p JOIN pacientes pa ON pa.id = p.paciente_id WHERE pa.empresa_id=? AND MONTH(p.data_atendimento)=MONTH(CURDATE()) AND YEAR(p.data_atendimento)=YEAR(CURDATE())");
    $stmt->execute([$eid]);
    $stats['evolucoes_mes'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT pp.*, pa.nome AS paciente_nome, u.nome AS profissional_nome
        FROM psicologia_planos pp
        JOIN pacientes pa ON pa.id = pp.paciente_id
        JOIN usuarios u ON u.id = pp.profissional_id
        WHERE pp.empresa_id=?
        ORDER BY pp.atualizado_em DESC, pp.id DESC
        LIMIT 6");
    $stmt->execute([$eid]);
    $planos = $stmt->fetchAll();

    $stmt = db()->prepare("SELECT pe.*, pa.nome AS paciente_nome, u.nome AS profissional_nome
        FROM psicologia_escalas pe
        JOIN pacientes pa ON pa.id = pe.paciente_id
        JOIN usuarios u ON u.id = pe.profissional_id
        WHERE pe.empresa_id=?
        ORDER BY pe.data_aplicacao DESC, pe.id DESC
        LIMIT 6");
    $stmt->execute([$eid]);
    $escalas = $stmt->fetchAll();
} catch (Exception $e) {
    flash('warning', 'O módulo de Psicologia precisa da migração SQL v6 para funcionar completamente.');
}

$statusBadge = [
    'ativo' => 'badge-success',
    'em_acompanhamento' => 'badge-info',
    'pausado' => 'badge-warning',
    'concluido' => 'badge-secondary',
];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div>
    <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-flower1 text-primary"></i> Módulo de Psicologia</h1>
    <p class="text-sm text-slate-500">Identidade visual exclusiva, recursos terapêuticos separados e acompanhamento por especialidade.</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="<?= APP_URL ?>/modules/psicologia/escalas.php" class="btn bg-violet-600 text-white"><i class="bi bi-clipboard-data"></i> Escalas Clínicas</a>
    <a href="<?= APP_URL ?>/modules/psicologia/planos.php" class="btn bg-fuchsia-600 text-white"><i class="bi bi-bullseye"></i> Plano Terapêutico</a>
    <a href="<?= APP_URL ?>/modules/telemedicina/index.php" class="btn btn-primary"><i class="bi bi-camera-video"></i> Teleatendimento</a>
  </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="card p-5">
    <div class="flex items-center justify-between">
      <div><p class="text-xs uppercase text-slate-500">Planos Ativos</p><p class="text-3xl font-bold text-violet-700"><?= $stats['planos_ativos'] ?></p></div>
      <div class="w-12 h-12 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center text-2xl"><i class="bi bi-bullseye"></i></div>
    </div>
  </div>
  <div class="card p-5">
    <div class="flex items-center justify-between">
      <div><p class="text-xs uppercase text-slate-500">Escalas no mês</p><p class="text-3xl font-bold text-fuchsia-700"><?= $stats['escalas_mes'] ?></p></div>
      <div class="w-12 h-12 rounded-xl bg-fuchsia-100 text-fuchsia-700 flex items-center justify-center text-2xl"><i class="bi bi-clipboard-data"></i></div>
    </div>
  </div>
  <div class="card p-5">
    <div class="flex items-center justify-between">
      <div><p class="text-xs uppercase text-slate-500">Teleatendimentos</p><p class="text-3xl font-bold text-indigo-700"><?= $stats['teleatendimentos_mes'] ?></p></div>
      <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl"><i class="bi bi-camera-video"></i></div>
    </div>
  </div>
  <div class="card p-5">
    <div class="flex items-center justify-between">
      <div><p class="text-xs uppercase text-slate-500">Evoluções no mês</p><p class="text-3xl font-bold text-pink-700"><?= $stats['evolucoes_mes'] ?></p></div>
      <div class="w-12 h-12 rounded-xl bg-pink-100 text-pink-700 flex items-center justify-center text-2xl"><i class="bi bi-journal-richtext"></i></div>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
  <div class="card p-6 bg-gradient-to-r from-violet-50 to-fuchsia-50 border border-violet-100">
    <h2 class="font-bold text-violet-900 mb-2"><i class="bi bi-stars"></i> Recursos exclusivos</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm mt-4">
      <a href="<?= APP_URL ?>/modules/prontuario/index.php" class="rounded-xl bg-white p-4 border border-violet-100 hover:border-violet-300 transition">
        <div class="font-semibold text-slate-800">Evolução terapêutica</div>
        <p class="text-slate-500 text-xs mt-1">Use o prontuário como diário clínico e evolução de sessões.</p>
      </a>
      <a href="<?= APP_URL ?>/modules/psicologia/escalas.php" class="rounded-xl bg-white p-4 border border-violet-100 hover:border-violet-300 transition">
        <div class="font-semibold text-slate-800">Escalas clínicas</div>
        <p class="text-slate-500 text-xs mt-1">Registre BAI, BDI, GAD-7, PHQ-9 e outros instrumentos.</p>
      </a>
      <a href="<?= APP_URL ?>/modules/psicologia/planos.php" class="rounded-xl bg-white p-4 border border-violet-100 hover:border-violet-300 transition">
        <div class="font-semibold text-slate-800">Plano terapêutico</div>
        <p class="text-slate-500 text-xs mt-1">Defina objetivos, abordagem, metas e reavaliações.</p>
      </a>
      <a href="<?= APP_URL ?>/modules/telemedicina/index.php" class="rounded-xl bg-white p-4 border border-violet-100 hover:border-violet-300 transition">
        <div class="font-semibold text-slate-800">Teleatendimento</div>
        <p class="text-slate-500 text-xs mt-1">Sessões online com a mesma base do ClinicApp.</p>
      </a>
    </div>
  </div>

  <div class="card p-6">
    <h2 class="font-bold mb-4"><i class="bi bi-lightning-charge"></i> Ativação por especialidade</h2>
    <div class="space-y-3 text-sm text-slate-600">
      <div class="rounded-lg bg-slate-50 p-4 border">Usuários com especialidade contendo <strong>Psicologia</strong>, <strong>Psicoterapia</strong>, <strong>Psicanálise</strong> ou <strong>Neuropsicologia</strong> passam a receber automaticamente o tema exclusivo quando o tema preferido ainda estiver no padrão médico.</div>
      <div class="rounded-lg bg-slate-50 p-4 border">A empresa também pode definir <strong>Psicologia</strong> como tema padrão em <strong>Empresa</strong> ou no módulo de <strong>Licenças</strong>.</div>
      <div class="rounded-lg bg-slate-50 p-4 border">As configurações visuais e os menus ficam separados para preservar os recursos específicos da especialidade.</div>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
  <div class="card overflow-hidden">
    <div class="px-6 py-4 border-b flex items-center justify-between">
      <h2 class="font-bold"><i class="bi bi-bullseye text-primary"></i> Planos terapêuticos recentes</h2>
      <a href="<?= APP_URL ?>/modules/psicologia/planos.php" class="text-sm text-primary hover:underline">Ver todos</a>
    </div>
    <div class="p-4 space-y-3">
      <?php if (!$planos): ?>
        <p class="text-sm text-slate-400">Nenhum plano terapêutico cadastrado.</p>
      <?php else: foreach ($planos as $pl): ?>
        <div class="rounded-xl border p-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="font-semibold text-slate-800"><?= e($pl['paciente_nome']) ?></div>
              <div class="text-xs text-slate-500">Profissional: <?= e($pl['profissional_nome']) ?> · <?= e($pl['abordagem']) ?></div>
            </div>
            <span class="badge <?= $statusBadge[$pl['status']] ?? 'badge-secondary' ?>"><?= e(str_replace('_', ' ', $pl['status'])) ?></span>
          </div>
          <div class="text-sm text-slate-600 mt-2"><?= e($pl['objetivo_principal']) ?></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="card overflow-hidden">
    <div class="px-6 py-4 border-b flex items-center justify-between">
      <h2 class="font-bold"><i class="bi bi-clipboard-data text-primary"></i> Escalas clínicas recentes</h2>
      <a href="<?= APP_URL ?>/modules/psicologia/escalas.php" class="text-sm text-primary hover:underline">Ver todas</a>
    </div>
    <div class="p-4 space-y-3">
      <?php if (!$escalas): ?>
        <p class="text-sm text-slate-400">Nenhuma escala registrada.</p>
      <?php else: foreach ($escalas as $esc): ?>
        <div class="rounded-xl border p-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="font-semibold text-slate-800"><?= e($esc['nome_escala']) ?> · <?= e($esc['paciente_nome']) ?></div>
              <div class="text-xs text-slate-500">Aplicada em <?= format_date($esc['data_aplicacao']) ?> · <?= e($esc['profissional_nome']) ?></div>
            </div>
            <div class="text-right">
              <div class="text-xs text-slate-500">Score</div>
              <div class="font-bold text-violet-700"><?= e($esc['score_total']) ?></div>
            </div>
          </div>
          <div class="text-sm text-slate-600 mt-2"><?= e($esc['classificacao']) ?></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>