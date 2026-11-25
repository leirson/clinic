<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$page_title = 'Cuidados e Alertas';
$eid = active_company_id();
$temaAtual = normalize_theme_choice(get_theme());
$temaMeta = specialty_theme_meta($temaAtual);
$tiposAlerta = [
    'clinico' => 'Clínico',
    'alergia' => 'Alergia',
    'medicacao' => 'Medicação',
    'retorno' => 'Retorno',
    'documental' => 'Documental',
    'risco' => 'Risco',
    'financeiro' => 'Financeiro',
    'outro' => 'Outro',
];
$prioridades = [
    'baixa' => 'Baixa',
    'moderada' => 'Moderada',
    'alta' => 'Alta',
    'critica' => 'Crítica',
];
$statusPlano = [
    'ativo' => 'Ativo',
    'em_acompanhamento' => 'Em acompanhamento',
    'pausado' => 'Pausado',
    'concluido' => 'Concluído',
    'cancelado' => 'Cancelado',
];

if (!db_table_exists('planos_cuidado') || !db_table_exists('paciente_alertas')) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="card p-6"><h1 class="text-2xl font-bold mb-3"><i class="bi bi-clipboard2-heart text-primary"></i> Cuidados e Alertas</h1><p class="text-sm text-slate-600">Execute o SQL atualizado do sistema para habilitar este módulo.</p></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    return;
}

$pacienteId = (int) input('paciente_id');
$editingPlanoId = (int) input('editar_plano');
$editingAlertaId = (int) input('editar_alerta');
$editingPlano = null;
$editingAlerta = null;

$pacs = db()->prepare("SELECT id, nome FROM pacientes WHERE empresa_id=? AND ativo=1 ORDER BY nome");
$pacs->execute([$eid]);
$pacientes = $pacs->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $acao = input('acao');

    if ($acao === 'salvar_alerta') {
        $id = (int) input('id');
        $tema = $temaAtual;
        $paciente = (int) input('paciente_id');
        $tipo = array_key_exists(input('tipo', 'clinico'), $tiposAlerta) ? input('tipo', 'clinico') : 'clinico';
        $prioridade = array_key_exists(input('prioridade', 'moderada'), $prioridades) ? input('prioridade', 'moderada') : 'moderada';
        $titulo = trim((string) input('titulo'));
        $descricao = trim((string) input('descricao'));
        $inicioEm = input('inicio_em', date('Y-m-d'));
        $fimEm = input('fim_em') ?: null;

        if ($paciente <= 0 || $titulo === '') {
            flash('error', 'Informe o paciente e o título do alerta.');
            redirect(APP_URL . '/modules/cuidados/index.php');
        }

        if ($id > 0) {
            db()->prepare("UPDATE paciente_alertas SET tema=?, paciente_id=?, tipo=?, prioridade=?, titulo=?, descricao=?, inicio_em=?, fim_em=?, atualizado_por=? WHERE id=? AND empresa_id=?")
                ->execute([$tema, $paciente, $tipo, $prioridade, $titulo, $descricao, $inicioEm, $fimEm, $_SESSION['user_id'], $id, $eid]);
            flash('success', 'Alerta atualizado.');
        } else {
            db()->prepare("INSERT INTO paciente_alertas (empresa_id, paciente_id, tema, tipo, prioridade, titulo, descricao, inicio_em, fim_em, criado_por, atualizado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$eid, $paciente, $tema, $tipo, $prioridade, $titulo, $descricao, $inicioEm, $fimEm, $_SESSION['user_id'], $_SESSION['user_id']]);
            flash('success', 'Alerta cadastrado.');
        }

        log_action('alerta_salvar', 'paciente_alertas', $id ?: db()->lastInsertId(), 'Cadastro/edição de alerta do paciente');
        redirect(APP_URL . '/modules/cuidados/index.php' . ($paciente ? '?paciente_id=' . $paciente : ''));
    }

    if ($acao === 'encerrar_alerta') {
        $id = (int) input('id');
        db()->prepare("UPDATE paciente_alertas SET ativo=0, fim_em=COALESCE(fim_em, CURDATE()), atualizado_por=? WHERE id=? AND empresa_id=?")
            ->execute([$_SESSION['user_id'], $id, $eid]);
        flash('success', 'Alerta encerrado.');
        log_action('alerta_encerrar', 'paciente_alertas', $id, 'Encerramento de alerta');
        redirect(APP_URL . '/modules/cuidados/index.php' . ($pacienteId ? '?paciente_id=' . $pacienteId : ''));
    }

    if ($acao === 'salvar_plano') {
        $id = (int) input('id');
        $tema = $temaAtual;
        $paciente = (int) input('paciente_id');
        $titulo = trim((string) input('titulo'));
        $objetivo = trim((string) input('objetivo'));
        $metas = trim((string) input('metas'));
        $conduta = trim((string) input('conduta'));
        $frequencia = trim((string) input('frequencia_retorno'));
        $proximaRevisao = input('proxima_revisao') ?: null;
        $status = array_key_exists(input('status', 'ativo'), $statusPlano) ? input('status', 'ativo') : 'ativo';
        $indicadores = trim((string) input('indicadores'));
        $orientacoes = trim((string) input('orientacoes'));

        if ($paciente <= 0 || $titulo === '') {
            flash('error', 'Informe o paciente e o título do plano de cuidado.');
            redirect(APP_URL . '/modules/cuidados/index.php');
        }

        if ($id > 0) {
            db()->prepare("UPDATE planos_cuidado SET tema=?, paciente_id=?, profissional_id=?, titulo=?, objetivo=?, metas=?, conduta=?, frequencia_retorno=?, proxima_revisao=?, status=?, indicadores=?, orientacoes=? WHERE id=? AND empresa_id=?")
                ->execute([$tema, $paciente, $_SESSION['user_id'], $titulo, $objetivo, $metas, $conduta, $frequencia, $proximaRevisao, $status, $indicadores, $orientacoes, $id, $eid]);
            flash('success', 'Plano de cuidado atualizado.');
        } else {
            db()->prepare("INSERT INTO planos_cuidado (empresa_id, paciente_id, profissional_id, tema, titulo, objetivo, metas, conduta, frequencia_retorno, proxima_revisao, status, indicadores, orientacoes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$eid, $paciente, $_SESSION['user_id'], $tema, $titulo, $objetivo, $metas, $conduta, $frequencia, $proximaRevisao, $status, $indicadores, $orientacoes]);
            flash('success', 'Plano de cuidado cadastrado.');
        }

        log_action('plano_cuidado_salvar', 'planos_cuidado', $id ?: db()->lastInsertId(), 'Cadastro/edição de plano de cuidado');
        redirect(APP_URL . '/modules/cuidados/index.php' . ($paciente ? '?paciente_id=' . $paciente : ''));
    }

    if ($acao === 'concluir_plano') {
        $id = (int) input('id');
        db()->prepare("UPDATE planos_cuidado SET status='concluido', proxima_revisao=COALESCE(proxima_revisao, CURDATE()) WHERE id=? AND empresa_id=?")
            ->execute([$id, $eid]);
        flash('success', 'Plano de cuidado concluído.');
        log_action('plano_cuidado_concluir', 'planos_cuidado', $id, 'Conclusão de plano de cuidado');
        redirect(APP_URL . '/modules/cuidados/index.php' . ($pacienteId ? '?paciente_id=' . $pacienteId : ''));
    }
}

if ($editingPlanoId > 0) {
    $stmt = db()->prepare("SELECT * FROM planos_cuidado WHERE id=? AND empresa_id=? LIMIT 1");
    $stmt->execute([$editingPlanoId, $eid]);
    $editingPlano = $stmt->fetch() ?: null;
}

if ($editingAlertaId > 0) {
    $stmt = db()->prepare("SELECT * FROM paciente_alertas WHERE id=? AND empresa_id=? LIMIT 1");
    $stmt->execute([$editingAlertaId, $eid]);
    $editingAlerta = $stmt->fetch() ?: null;
}

$pacienteAtual = null;
if ($pacienteId > 0) {
    $stmt = db()->prepare("SELECT * FROM pacientes WHERE id=? AND empresa_id=? LIMIT 1");
    $stmt->execute([$pacienteId, $eid]);
    $pacienteAtual = $stmt->fetch() ?: null;
}

$sqlAlertas = "SELECT pa.*, p.nome AS paciente_nome, u.nome AS autor_nome
               FROM paciente_alertas pa
               JOIN pacientes p ON p.id = pa.paciente_id
               LEFT JOIN usuarios u ON u.id = pa.atualizado_por
               WHERE pa.empresa_id=? AND pa.ativo=1 AND pa.tema=?";
$paramsAlertas = [$eid, $temaAtual];
if ($pacienteId > 0) {
    $sqlAlertas .= " AND pa.paciente_id=?";
    $paramsAlertas[] = $pacienteId;
}
$sqlAlertas .= " ORDER BY FIELD(pa.prioridade,'critica','alta','moderada','baixa'), pa.atualizado_em DESC LIMIT 30";
$stmt = db()->prepare($sqlAlertas);
$stmt->execute($paramsAlertas);
$alertas = $stmt->fetchAll();

$sqlPlanos = "SELECT pc.*, p.nome AS paciente_nome, u.nome AS profissional_nome
              FROM planos_cuidado pc
              JOIN pacientes p ON p.id = pc.paciente_id
              LEFT JOIN usuarios u ON u.id = pc.profissional_id
              WHERE pc.empresa_id=? AND pc.tema=?";
$paramsPlanos = [$eid, $temaAtual];
if ($pacienteId > 0) {
    $sqlPlanos .= " AND pc.paciente_id=?";
    $paramsPlanos[] = $pacienteId;
}
$sqlPlanos .= " ORDER BY FIELD(pc.status,'ativo','em_acompanhamento','pausado','concluido','cancelado'), pc.atualizado_em DESC LIMIT 30";
$stmt = db()->prepare($sqlPlanos);
$stmt->execute($paramsPlanos);
$planos = $stmt->fetchAll();

$badgePrioridade = [
    'baixa' => 'badge-secondary',
    'moderada' => 'badge-info',
    'alta' => 'badge-warning',
    'critica' => 'badge-danger',
];
$badgeStatusPlano = [
    'ativo' => 'badge-success',
    'em_acompanhamento' => 'badge-info',
    'pausado' => 'badge-warning',
    'concluido' => 'badge-secondary',
    'cancelado' => 'badge-danger',
];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="space-y-5">
  <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
    <div>
      <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-clipboard2-heart text-primary"></i> Cuidados e Alertas</h1>
      <p class="text-sm text-slate-500">Acompanhamento longitudinal com planos de cuidado, retornos prioritários e lembretes clínicos da especialidade atual.</p>
      <p class="text-sm text-primary mt-1"><strong><?= e($temaMeta['icone']) ?> <?= e($temaMeta['nome']) ?></strong><?php if ($pacienteAtual): ?> · Paciente filtrado: <strong><?= e($pacienteAtual['nome']) ?></strong><?php endif; ?></p>
    </div>
    <div class="flex gap-2 flex-wrap">
      <a href="<?= APP_URL ?>/modules/protocolos/index.php" class="btn bg-slate-100 text-slate-700"><i class="bi bi-journal-check"></i> Protocolos</a>
      <a href="<?= APP_URL ?>/modules/modelos_clinicos/index.php" class="btn bg-slate-100 text-slate-700"><i class="bi bi-ui-checks-grid"></i> Modelos</a>
    </div>
  </div>

  <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
    <div class="md:col-span-3">
      <label class="text-xs text-slate-500">Paciente</label>
      <select data-patient-search name="paciente_id" class="form-select">
        <option value="0">Todos os pacientes</option>
        <?php foreach ($pacientes as $pac): ?>
          <option value="<?= (int) $pac['id'] ?>" <?= $pacienteId === (int) $pac['id'] ? 'selected' : '' ?>><?= e($pac['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex gap-2">
      <button class="btn btn-primary flex-1"><i class="bi bi-search"></i> Filtrar</button>
      <a href="index.php" class="btn bg-slate-100 text-slate-700">Limpar</a>
    </div>
  </form>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-exclamation-triangle"></i> <?= $editingAlerta ? 'Editar alerta' : 'Novo alerta' ?></h2>
      <form method="POST" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="salvar_alerta">
        <input type="hidden" name="id" value="<?= (int) ($editingAlerta['id'] ?? 0) ?>">
        <div>
          <label class="text-xs text-slate-500">Paciente</label>
          <select data-patient-search name="paciente_id" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach ($pacientes as $pac): ?>
              <option value="<?= (int) $pac['id'] ?>" <?= ((int) ($editingAlerta['paciente_id'] ?? $pacienteId) === (int) $pac['id']) ? 'selected' : '' ?>><?= e($pac['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
            <span class="font-medium text-slate-700">Especialidade:</span>
            <span class="ml-1"><?= e($temaMeta['icone']) ?> <?= e($temaMeta['nome']) ?></span>
          </div>
          <div>
            <label class="text-xs text-slate-500">Tipo</label>
            <select name="tipo" class="form-select">
              <?php foreach ($tiposAlerta as $tipoKey => $tipoLabel): ?>
                <option value="<?= e($tipoKey) ?>" <?= (($editingAlerta['tipo'] ?? 'clinico') === $tipoKey) ? 'selected' : '' ?>><?= e($tipoLabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="text-xs text-slate-500">Prioridade</label>
            <select name="prioridade" class="form-select">
              <?php foreach ($prioridades as $prioridadeKey => $prioridadeLabel): ?>
                <option value="<?= e($prioridadeKey) ?>" <?= (($editingAlerta['prioridade'] ?? 'moderada') === $prioridadeKey) ? 'selected' : '' ?>><?= e($prioridadeLabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div>
          <label class="text-xs text-slate-500">Título</label>
          <input type="text" name="titulo" required class="form-input" value="<?= e($editingAlerta['titulo'] ?? '') ?>">
        </div>
        <div>
          <label class="text-xs text-slate-500">Descrição</label>
          <textarea name="descricao" rows="3" class="form-textarea"><?= e($editingAlerta['descricao'] ?? '') ?></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-slate-500">Início</label>
            <input type="date" name="inicio_em" class="form-input" value="<?= e($editingAlerta['inicio_em'] ?? date('Y-m-d')) ?>">
          </div>
          <div>
            <label class="text-xs text-slate-500">Fim previsto</label>
            <input type="date" name="fim_em" class="form-input" value="<?= e($editingAlerta['fim_em'] ?? '') ?>">
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <?php if ($editingAlerta): ?><a href="index.php<?= $pacienteId ? '?paciente_id=' . $pacienteId : '' ?>" class="btn bg-slate-100 text-slate-700">Cancelar</a><?php endif; ?>
          <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar alerta</button>
        </div>
      </form>
    </div>

    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-bullseye"></i> <?= $editingPlano ? 'Editar plano de cuidado' : 'Novo plano de cuidado' ?></h2>
      <form method="POST" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="salvar_plano">
        <input type="hidden" name="id" value="<?= (int) ($editingPlano['id'] ?? 0) ?>">
        <div>
          <label class="text-xs text-slate-500">Paciente</label>
          <select data-patient-search name="paciente_id" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach ($pacientes as $pac): ?>
              <option value="<?= (int) $pac['id'] ?>" <?= ((int) ($editingPlano['paciente_id'] ?? $pacienteId) === (int) $pac['id']) ? 'selected' : '' ?>><?= e($pac['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
            <span class="font-medium text-slate-700">Especialidade:</span>
            <span class="ml-1"><?= e($temaMeta['icone']) ?> <?= e($temaMeta['nome']) ?></span>
          </div>
          <div>
            <label class="text-xs text-slate-500">Status</label>
            <select name="status" class="form-select">
              <?php foreach ($statusPlano as $statusKey => $statusLabel): ?>
                <option value="<?= e($statusKey) ?>" <?= (($editingPlano['status'] ?? 'ativo') === $statusKey) ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div>
          <label class="text-xs text-slate-500">Título</label>
          <input type="text" name="titulo" required class="form-input" value="<?= e($editingPlano['titulo'] ?? '') ?>">
        </div>
        <div>
          <label class="text-xs text-slate-500">Objetivo</label>
          <textarea name="objetivo" rows="2" class="form-textarea"><?= e($editingPlano['objetivo'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-xs text-slate-500">Metas</label>
          <textarea name="metas" rows="3" class="form-textarea"><?= e($editingPlano['metas'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-xs text-slate-500">Conduta</label>
          <textarea name="conduta" rows="3" class="form-textarea"><?= e($editingPlano['conduta'] ?? '') ?></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-slate-500">Frequência de retorno</label>
            <input type="text" name="frequencia_retorno" class="form-input" value="<?= e($editingPlano['frequencia_retorno'] ?? '') ?>" placeholder="Ex.: mensal, 15 dias">
          </div>
          <div>
            <label class="text-xs text-slate-500">Próxima revisão</label>
            <input type="date" name="proxima_revisao" class="form-input" value="<?= e($editingPlano['proxima_revisao'] ?? '') ?>">
          </div>
        </div>
        <div>
          <label class="text-xs text-slate-500">Indicadores / marcadores</label>
          <textarea name="indicadores" rows="2" class="form-textarea"><?= e($editingPlano['indicadores'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-xs text-slate-500">Orientações</label>
          <textarea name="orientacoes" rows="2" class="form-textarea"><?= e($editingPlano['orientacoes'] ?? '') ?></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <?php if ($editingPlano): ?><a href="index.php<?= $pacienteId ? '?paciente_id=' . $pacienteId : '' ?>" class="btn bg-slate-100 text-slate-700">Cancelar</a><?php endif; ?>
          <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar plano</button>
        </div>
      </form>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold text-slate-800"><i class="bi bi-exclamation-diamond"></i> Alertas ativos</h2>
        <span class="text-xs text-slate-400"><?= count($alertas) ?> registro(s)</span>
      </div>
      <div class="space-y-3">
        <?php if (!$alertas): ?>
          <p class="text-sm text-slate-400">Nenhum alerta ativo para os filtros informados.</p>
        <?php else: ?>
          <?php foreach ($alertas as $alerta): $meta = specialty_theme_meta($alerta['tema']); ?>
            <div class="rounded-2xl border p-4">
              <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                  <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="badge <?= $badgePrioridade[$alerta['prioridade']] ?? 'badge-secondary' ?>"><?= e($prioridades[$alerta['prioridade']] ?? $alerta['prioridade']) ?></span>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-700"><span><?= e($meta['icone']) ?></span> <?= e($meta['nome']) ?></span>
                    <span class="text-xs text-slate-500"><?= e($tiposAlerta[$alerta['tipo']] ?? $alerta['tipo']) ?></span>
                  </div>
                  <div class="font-semibold text-slate-800"><?= e($alerta['titulo']) ?></div>
                  <div class="text-sm text-slate-600"><?= e($alerta['paciente_nome']) ?></div>
                  <?php if (!empty($alerta['descricao'])): ?><div class="text-sm text-slate-500 mt-1 whitespace-pre-wrap"><?= e($alerta['descricao']) ?></div><?php endif; ?>
                  <div class="text-xs text-slate-400 mt-2">Início: <?= format_date($alerta['inicio_em']) ?><?= !empty($alerta['fim_em']) ? ' · Fim: ' . format_date($alerta['fim_em']) : '' ?></div>
                </div>
                <div class="flex gap-2 shrink-0">
                  <a href="index.php?editar_alerta=<?= (int) $alerta['id'] ?><?= $pacienteId ? '&paciente_id=' . $pacienteId : '' ?>" class="btn bg-amber-500 text-white"><i class="bi bi-pencil"></i></a>
                  <form method="POST" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="acao" value="encerrar_alerta">
                    <input type="hidden" name="id" value="<?= (int) $alerta['id'] ?>">
                    <button class="btn bg-rose-600 text-white" data-confirm="Encerrar este alerta?"><i class="bi bi-check2-circle"></i></button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold text-slate-800"><i class="bi bi-bullseye"></i> Planos de cuidado</h2>
        <span class="text-xs text-slate-400"><?= count($planos) ?> registro(s)</span>
      </div>
      <div class="space-y-3">
        <?php if (!$planos): ?>
          <p class="text-sm text-slate-400">Nenhum plano de cuidado cadastrado.</p>
        <?php else: ?>
          <?php foreach ($planos as $plano): $meta = specialty_theme_meta($plano['tema']); ?>
            <div class="rounded-2xl border p-4">
              <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                  <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="badge <?= $badgeStatusPlano[$plano['status']] ?? 'badge-secondary' ?>"><?= e($statusPlano[$plano['status']] ?? $plano['status']) ?></span>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-700"><span><?= e($meta['icone']) ?></span> <?= e($meta['nome']) ?></span>
                  </div>
                  <div class="font-semibold text-slate-800"><?= e($plano['titulo']) ?></div>
                  <div class="text-sm text-slate-600"><?= e($plano['paciente_nome']) ?><?= !empty($plano['profissional_nome']) ? ' · ' . e($plano['profissional_nome']) : '' ?></div>
                  <?php if (!empty($plano['objetivo'])): ?><div class="text-sm text-slate-500 mt-1"><strong>Objetivo:</strong> <?= e($plano['objetivo']) ?></div><?php endif; ?>
                  <?php if (!empty($plano['metas'])): ?><div class="text-sm text-slate-500 mt-1"><strong>Metas:</strong> <?= e($plano['metas']) ?></div><?php endif; ?>
                  <?php if (!empty($plano['frequencia_retorno']) || !empty($plano['proxima_revisao'])): ?><div class="text-xs text-slate-400 mt-2">Frequência: <?= e($plano['frequencia_retorno'] ?: '—') ?><?= !empty($plano['proxima_revisao']) ? ' · Próxima revisão: ' . format_date($plano['proxima_revisao']) : '' ?></div><?php endif; ?>
                </div>
                <div class="flex gap-2 shrink-0">
                  <a href="index.php?editar_plano=<?= (int) $plano['id'] ?><?= $pacienteId ? '&paciente_id=' . $pacienteId : '' ?>" class="btn bg-amber-500 text-white"><i class="bi bi-pencil"></i></a>
                  <?php if (($plano['status'] ?? '') !== 'concluido'): ?>
                  <form method="POST" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="acao" value="concluir_plano">
                    <input type="hidden" name="id" value="<?= (int) $plano['id'] ?>">
                    <button class="btn bg-emerald-600 text-white" data-confirm="Concluir este plano de cuidado?"><i class="bi bi-check2-circle"></i></button>
                  </form>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
