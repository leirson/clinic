<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

if (get_theme() !== 'psicologia' && !is_developer()) {
    flash('warning', 'Ative o tema Psicologia para acessar o plano terapêutico.');
    redirect(APP_URL . '/dashboard.php');
}

$page_title = 'Plano Terapêutico';
$eid = active_company_id();
$pacienteFiltro = (int) input('paciente_id');
$tableReady = db_table_exists('psicologia_planos');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    try {
        if (!$tableReady) {
            throw new Exception('Tabela psicologia_planos não encontrada. Execute a migração v7 do prontuário psicológico.');
        }

        $pacienteId = (int) input('paciente_id');
        $profissionalId = (int) input('profissional_id', $_SESSION['user_id']);
        clinic_validate_clinical_links($pacienteId, (int) input('prontuario_id'));
        clinic_company_record('usuarios', $profissionalId);
        $objetivo = trim((string) input('objetivo_principal'));
        $demandaFocal = trim((string) input('demanda_focal'));

        if ($pacienteId <= 0 || $profissionalId <= 0) {
            throw new Exception('Paciente e profissional são obrigatórios.');
        }
        if ($objetivo === '' && $demandaFocal === '') {
            throw new Exception('Informe pelo menos a demanda focal ou o objetivo principal.');
        }

        $planId = psychology_save_plan([
            'id' => (int) input('id'),
            'empresa_id' => $eid,
            'prontuario_id' => (int) input('prontuario_id') ?: null,
            'paciente_id' => $pacienteId,
            'profissional_id' => $profissionalId,
            'abordagem' => input('abordagem'),
            'demanda_focal' => $demandaFocal,
            'objetivo_principal' => $objetivo,
            'metas' => input('metas'),
            'metas_curto_prazo' => input('metas_curto_prazo'),
            'metas_medio_prazo' => input('metas_medio_prazo'),
            'metas_longo_prazo' => input('metas_longo_prazo'),
            'estrategias_intervencao' => input('estrategias_intervencao'),
            'indicadores_evolucao' => input('indicadores_evolucao'),
            'criterios_alta' => input('criterios_alta'),
            'risco_monitorado' => input('risco_monitorado'),
            'frequencia_sugerida' => input('frequencia_sugerida'),
            'status' => input('status', 'ativo'),
            'data_inicio' => input('data_inicio') ?: date('Y-m-d'),
            'data_reavaliacao' => input('data_reavaliacao') ?: null,
            'observacoes' => input('observacoes'),
        ]);

        if ($planId <= 0) {
            throw new Exception('Não foi possível salvar o plano terapêutico.');
        }

        log_action('create', 'psicologia_planos', $planId, 'Plano terapêutico salvo/atualizado.');
        flash('success', 'Plano terapêutico salvo com sucesso.');
        redirect(APP_URL . '/modules/psicologia/planos.php?paciente_id=' . $pacienteId);
    } catch (Exception $e) {
        flash('error', 'Erro: ' . $e->getMessage());
    }
}

$pacientesStmt = db()->prepare("SELECT id, nome FROM pacientes WHERE empresa_id=? AND ativo=1 ORDER BY nome ASC");
$pacientesStmt->execute([$eid]);
$pacientes = $pacientesStmt->fetchAll();

$profStmt = db()->prepare("SELECT id, nome FROM usuarios WHERE empresa_id=? AND ativo=1 AND perfil IN ('admin','profissional') ORDER BY nome ASC");
$profStmt->execute([$eid]);
$profissionais = $profStmt->fetchAll();

$planos = [];
if ($tableReady) {
    $where = 'WHERE pp.empresa_id = ?';
    $params = [$eid];
    if ($pacienteFiltro > 0) {
        $where .= ' AND pp.paciente_id = ?';
        $params[] = $pacienteFiltro;
    }

    $stmt = db()->prepare("SELECT pp.*, pa.nome AS paciente_nome, u.nome AS profissional_nome
        FROM psicologia_planos pp
        JOIN pacientes pa ON pa.id = pp.paciente_id
        JOIN usuarios u ON u.id = pp.profissional_id
        $where
        ORDER BY pp.atualizado_em DESC, pp.id DESC
        LIMIT 100");
    $stmt->execute($params);
    $planos = $stmt->fetchAll();
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
    <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-bullseye text-primary"></i> Plano Terapêutico</h1>
    <p class="text-sm text-slate-500">Objetivos, metas, critérios de evolução e monitoramento vinculados ao acompanhamento psicológico.</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="<?= APP_URL ?>/modules/psicologia/index.php" class="btn bg-slate-100 text-slate-700"><i class="bi bi-arrow-left"></i> Psicologia</a>
    <a href="<?= APP_URL ?>/modules/psicologia/escalas.php" class="btn bg-violet-600 text-white"><i class="bi bi-clipboard-data"></i> Escalas Clínicas</a>
  </div>
</div>

<?php if (!$tableReady): ?>
  <div class="card p-5 border-l-4 border-amber-400 bg-amber-50 text-amber-900 mb-6">
    <div class="font-semibold mb-1">Migração pendente</div>
    <div class="text-sm">A estrutura avançada do plano terapêutico ainda não está disponível neste banco. Execute o script <code>sql/upgrade_v7_prontuario_psicologia.sql</code>.</div>
  </div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="xl:col-span-1">
    <form method="POST" class="card p-6 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) input('id') ?>">
      <div>
        <label class="text-sm font-medium">Paciente *</label>
        <select data-patient-search name="paciente_id" class="form-select" required>
          <option value="">Selecione</option>
          <?php foreach ($pacientes as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= ((int) input('paciente_id', $pacienteFiltro) === (int) $p['id']) ? 'selected' : '' ?>><?= e($p['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Profissional *</label>
        <select name="profissional_id" class="form-select" required>
          <?php foreach ($profissionais as $prof): ?>
            <option value="<?= (int) $prof['id'] ?>" <?= ((int) input('profissional_id', $_SESSION['user_id']) === (int) $prof['id']) ? 'selected' : '' ?>><?= e($prof['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium">Abordagem</label>
          <input name="abordagem" value="<?= e(input('abordagem')) ?>" class="form-input" placeholder="TCC, Psicanálise, ACT...">
        </div>
        <div>
          <label class="text-sm font-medium">Frequência sugerida</label>
          <input name="frequencia_sugerida" value="<?= e(input('frequencia_sugerida')) ?>" class="form-input" placeholder="Semanal, quinzenal...">
        </div>
      </div>
      <div>
        <label class="text-sm font-medium">Demanda focal</label>
        <textarea name="demanda_focal" rows="3" class="form-textarea" placeholder="Sintomas, temas ou contextos prioritários do acompanhamento..."><?= e(input('demanda_focal')) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Objetivo principal</label>
        <input name="objetivo_principal" value="<?= e(input('objetivo_principal')) ?>" class="form-input" placeholder="Ex: reduzir sintomas ansiosos e ampliar repertório emocional">
      </div>
      <div>
        <label class="text-sm font-medium">Síntese das metas terapêuticas</label>
        <textarea name="metas" rows="3" class="form-textarea" placeholder="Visão geral do plano terapêutico...\n"><?= e(input('metas')) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Metas de curto prazo</label>
        <textarea name="metas_curto_prazo" rows="3" class="form-textarea" placeholder="Estabilização, manejo inicial, organização de rotina...\n"><?= e(input('metas_curto_prazo')) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Metas de médio prazo</label>
        <textarea name="metas_medio_prazo" rows="3" class="form-textarea" placeholder="Elaboração de conflitos, treino de habilidades...\n"><?= e(input('metas_medio_prazo')) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Metas de longo prazo</label>
        <textarea name="metas_longo_prazo" rows="3" class="form-textarea" placeholder="Autonomia, manutenção de ganhos, prevenção de recaída...\n"><?= e(input('metas_longo_prazo')) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Estratégias / intervenções previstas</label>
        <textarea name="estrategias_intervencao" rows="3" class="form-textarea" placeholder="Psicoeducação, reestruturação cognitiva, trabalho com vínculos...\n"><?= e(input('estrategias_intervencao')) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Indicadores de evolução</label>
        <textarea name="indicadores_evolucao" rows="3" class="form-textarea" placeholder="Assiduidade, escalas, funcionalidade, autorrelato, sintomas...\n"><?= e(input('indicadores_evolucao')) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Critérios de alta / transição</label>
        <textarea name="criterios_alta" rows="3" class="form-textarea" placeholder="Quando espaçar, encaminhar, encerrar ou reavaliar...\n"><?= e(input('criterios_alta')) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Riscos / pontos de monitoramento</label>
        <textarea name="risco_monitorado" rows="3" class="form-textarea" placeholder="Crise, autoagressão, recaída, abandono, vulnerabilidades...\n"><?= e(input('risco_monitorado')) ?></textarea>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium">Status</label>
          <select name="status" class="form-select">
            <?php foreach (['ativo'=>'Ativo','em_acompanhamento'=>'Em acompanhamento','pausado'=>'Pausado','concluido'=>'Concluído'] as $k => $label): ?>
              <option value="<?= $k ?>" <?= input('status', 'ativo') === $k ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium">Data de reavaliação</label>
          <input type="date" name="data_reavaliacao" value="<?= e(input('data_reavaliacao')) ?>" class="form-input">
        </div>
      </div>
      <div>
        <label class="text-sm font-medium">Início</label>
        <input type="date" name="data_inicio" value="<?= e(input('data_inicio', date('Y-m-d'))) ?>" class="form-input">
      </div>
      <div>
        <label class="text-sm font-medium">Observações</label>
        <textarea name="observacoes" rows="4" class="form-textarea" placeholder="Acordos terapêuticos, pontos de atenção, alinhamentos com rede...\n"><?= e(input('observacoes')) ?></textarea>
      </div>
      <div class="flex justify-end">
        <button class="btn bg-fuchsia-600 text-white"><i class="bi bi-save"></i> Salvar Plano</button>
      </div>
    </form>
  </div>

  <div class="xl:col-span-2 space-y-6">
    <form method="GET" class="card p-4 flex flex-col md:flex-row gap-3 md:items-end">
      <div class="flex-1">
        <label class="text-xs uppercase text-slate-500">Filtrar paciente</label>
        <select data-patient-search name="paciente_id" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($pacientes as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= $pacienteFiltro === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
    </form>

    <div class="space-y-4">
      <?php if (!$planos): ?>
        <div class="card p-10 text-center text-slate-400">Nenhum plano terapêutico cadastrado.</div>
      <?php else: foreach ($planos as $pl): ?>
        <div class="card p-5">
          <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
              <div class="text-lg font-semibold text-slate-800"><?= e($pl['paciente_nome']) ?></div>
              <div class="text-sm text-slate-500">Profissional: <?= e($pl['profissional_nome']) ?><?= !empty($pl['abordagem']) ? ' · ' . e($pl['abordagem']) : '' ?></div>
              <?php if (!empty($pl['prontuario_id'])): ?>
                <div class="text-xs text-violet-600 mt-1"><a href="<?= APP_URL ?>/modules/prontuario/ver.php?id=<?= (int) $pl['prontuario_id'] ?>">Ver prontuário relacionado</a></div>
              <?php endif; ?>
            </div>
            <div class="flex items-center gap-2 flex-wrap justify-end">
              <span class="badge <?= $statusBadge[$pl['status']] ?? 'badge-secondary' ?>"><?= e(str_replace('_', ' ', $pl['status'])) ?></span>
              <span class="text-xs text-slate-500">Atualizado em <?= format_date($pl['atualizado_em'], true) ?></span>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <?php foreach([
                'demanda_focal' => 'Demanda focal',
                'objetivo_principal' => 'Objetivo principal',
                'metas' => 'Síntese das metas terapêuticas',
                'metas_curto_prazo' => 'Metas de curto prazo',
                'metas_medio_prazo' => 'Metas de médio prazo',
                'metas_longo_prazo' => 'Metas de longo prazo',
                'estrategias_intervencao' => 'Estratégias / intervenções previstas',
                'indicadores_evolucao' => 'Indicadores de evolução',
                'criterios_alta' => 'Critérios de alta / transição',
                'risco_monitorado' => 'Riscos / pontos de monitoramento',
                'frequencia_sugerida' => 'Frequência sugerida',
                'observacoes' => 'Observações'
            ] as $field => $label): if (!empty($pl[$field])): ?>
              <div class="<?= in_array($field, ['demanda_focal','objetivo_principal','metas','metas_curto_prazo','metas_medio_prazo','metas_longo_prazo','estrategias_intervencao','indicadores_evolucao','criterios_alta','risco_monitorado','observacoes'], true) ? 'md:col-span-2' : '' ?>">
                <div class="text-xs uppercase text-slate-500"><?= $label ?></div>
                <div class="text-slate-700 mt-1 whitespace-pre-line"><?= e($pl[$field]) ?></div>
              </div>
            <?php endif; endforeach; ?>
            <?php if (!empty($pl['data_reavaliacao'])): ?>
              <div>
                <div class="text-xs uppercase text-slate-500">Reavaliação</div>
                <div class="text-slate-700 mt-1"><?= format_date($pl['data_reavaliacao']) ?></div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
