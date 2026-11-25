<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$page_title = 'Protocolos Clínicos';
$eid = active_company_id();
$temaParam = trim((string) input('tema', ''));
$temaAtual = normalize_theme_choice($temaParam !== '' ? $temaParam : get_theme());
$temaMeta = specialty_theme_meta($temaAtual);
$q = trim((string) input('q', ''));
$editingId = (int) input('editar');
$editing = null;
$pacienteId = (int) input('paciente_id');

if (!db_table_exists('protocolos_clinicos')) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="card p-6"><h1 class="text-2xl font-bold mb-3"><i class="bi bi-journal-check text-primary"></i> Protocolos Clínicos</h1><p class="text-sm text-slate-600">Execute o SQL atualizado do sistema para habilitar este módulo.</p></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $acao = input('acao');

    if ($acao === 'salvar') {
        $id = (int) input('id');
        $tema = $temaAtual;
        $titulo = trim((string) input('titulo'));
        $categoria = trim((string) input('categoria'));
        $objetivo = trim((string) input('objetivo'));
        $checklist = trim((string) input('checklist'));
        $criterios = trim((string) input('criterios'));
        $orientacoes = trim((string) input('orientacoes'));
        $tags = trim((string) input('tags'));

        if ($titulo === '') {
            flash('error', 'Informe o título do protocolo.');
            redirect(APP_URL . '/modules/protocolos/index.php?tema=' . urlencode($temaAtual));
        }

        if ($id > 0) {
            db()->prepare("UPDATE protocolos_clinicos SET tema=?, titulo=?, categoria=?, objetivo=?, checklist=?, criterios=?, orientacoes=?, tags=?, atualizado_por=? WHERE id=? AND empresa_id=?")
                ->execute([$tema, $titulo, $categoria, $objetivo, $checklist, $criterios, $orientacoes, $tags, $_SESSION['user_id'], $id, $eid]);
            flash('success', 'Protocolo atualizado com sucesso.');
            log_action('protocolos_salvar', 'protocolos_clinicos', $id, 'Atualização de protocolo clínico.');
        } else {
            db()->prepare("INSERT INTO protocolos_clinicos (empresa_id, tema, titulo, categoria, objetivo, checklist, criterios, orientacoes, tags, criado_por, atualizado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$eid, $tema, $titulo, $categoria, $objetivo, $checklist, $criterios, $orientacoes, $tags, $_SESSION['user_id'], $_SESSION['user_id']]);
            $novoId = (int) db()->lastInsertId();
            flash('success', 'Protocolo cadastrado com sucesso.');
            log_action('protocolos_salvar', 'protocolos_clinicos', $novoId, 'Cadastro de protocolo clínico.');
        }
        redirect(APP_URL . '/modules/protocolos/index.php?tema=' . urlencode($temaAtual));
    }

    if ($acao === 'arquivar') {
        $id = (int) input('id');
        db()->prepare("UPDATE protocolos_clinicos SET ativo=0, atualizado_por=? WHERE id=? AND empresa_id=?")
            ->execute([$_SESSION['user_id'], $id, $eid]);
        flash('success', 'Protocolo arquivado.');
        log_action('protocolos_arquivar', 'protocolos_clinicos', $id, 'Arquivamento de protocolo clínico');
        redirect(APP_URL . '/modules/protocolos/index.php?tema=' . urlencode($temaAtual));
    }
}

if ($editingId > 0) {
    $stmt = db()->prepare("SELECT * FROM protocolos_clinicos WHERE id=? AND empresa_id=? LIMIT 1");
    $stmt->execute([$editingId, $eid]);
    $editing = $stmt->fetch() ?: null;
}

$sql = "SELECT pc.*, u.nome AS autor_nome
        FROM protocolos_clinicos pc
        LEFT JOIN usuarios u ON u.id = pc.atualizado_por
        WHERE pc.empresa_id=? AND pc.ativo=1 AND pc.tema=?";
$params = [$eid, $temaAtual];
if ($q !== '') {
    $sql .= " AND (pc.titulo LIKE ? OR pc.categoria LIKE ? OR pc.tags LIKE ? OR pc.objetivo LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$sql .= " ORDER BY pc.titulo";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$protocolos = $stmt->fetchAll() ?: [];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="space-y-5">
  <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
    <div>
      <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-journal-check text-primary"></i> Protocolos Clínicos</h1>
      <p class="text-sm text-slate-500">Protocolos acionáveis com checklist, critérios, alertas e orientações aplicáveis no prontuário.</p>
      <p class="text-sm text-primary mt-1"><strong><?= e($temaMeta['icone']) ?> <?= e($temaMeta['nome']) ?></strong></p>
    </div>
    <div class="flex gap-2 flex-wrap">
      <a href="<?= APP_URL ?>/modules/modelos_clinicos/index.php?tema=<?= urlencode($temaAtual) ?>" class="btn bg-slate-100 text-slate-700"><i class="bi bi-ui-checks-grid"></i> Modelos Clínicos</a>
      <?php if ($pacienteId > 0): ?>
        <a href="<?= APP_URL ?>/modules/prontuario/novo.php?paciente_id=<?= $pacienteId ?>" class="btn bg-violet-600 text-white"><i class="bi bi-journal-plus"></i> Novo prontuário</a>
      <?php endif; ?>
    </div>
  </div>

  <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
    <input type="hidden" name="tema" value="<?= e($temaAtual) ?>">
    <?php if ($pacienteId > 0): ?><input type="hidden" name="paciente_id" value="<?= $pacienteId ?>"><?php endif; ?>
    <div class="md:col-span-4">
      <label class="text-xs text-slate-500">Busca</label>
      <input type="text" name="q" value="<?= e($q) ?>" class="form-input" placeholder="Título, categoria, objetivo ou tags">
    </div>
    <div class="flex gap-2">
      <button class="btn btn-primary flex-1"><i class="bi bi-search"></i> Filtrar</button>
      <a href="index.php?tema=<?= urlencode($temaAtual) ?><?= $pacienteId > 0 ? '&paciente_id=' . $pacienteId : '' ?>" class="btn bg-slate-100 text-slate-700">Limpar</a>
    </div>
  </form>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="xl:col-span-1 card p-5">
      <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-plus-circle"></i> <?= $editing ? 'Editar protocolo' : 'Novo protocolo' ?></h2>
      <form method="POST" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
          <span class="font-medium text-slate-700">Especialidade:</span>
          <span class="ml-1"><?= e($temaMeta['icone']) ?> <?= e($temaMeta['nome']) ?></span>
        </div>
        <div>
          <label class="text-xs text-slate-500">Título</label>
          <input type="text" name="titulo" required class="form-input" value="<?= e($editing['titulo'] ?? '') ?>" placeholder="Ex.: Dor torácica de baixo risco">
        </div>
        <div>
          <label class="text-xs text-slate-500">Categoria</label>
          <input type="text" name="categoria" class="form-input" value="<?= e($editing['categoria'] ?? '') ?>" placeholder="Check-up, risco, seguimento...">
        </div>
        <div>
          <label class="text-xs text-slate-500">Objetivo</label>
          <textarea name="objetivo" rows="2" class="form-textarea"><?= e($editing['objetivo'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-xs text-slate-500">Checklist operacional</label>
          <textarea name="checklist" rows="5" class="form-textarea" placeholder="Uma linha por item"><?= e($editing['checklist'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-xs text-slate-500">Critérios / sinais de alerta</label>
          <textarea name="criterios" rows="3" class="form-textarea"><?= e($editing['criterios'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-xs text-slate-500">Orientações finais</label>
          <textarea name="orientacoes" rows="3" class="form-textarea"><?= e($editing['orientacoes'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-xs text-slate-500">Tags</label>
          <input type="text" name="tags" class="form-input" value="<?= e($editing['tags'] ?? '') ?>" placeholder="cefaleia, hipertensão, retorno">
        </div>
        <div class="flex justify-end gap-2">
          <?php if ($editing): ?><a href="index.php?tema=<?= urlencode($temaAtual) ?>" class="btn bg-slate-100 text-slate-700">Cancelar</a><?php endif; ?>
          <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
      </form>
    </div>

    <div class="xl:col-span-2 space-y-4">
      <?php if (!$protocolos): ?>
        <div class="card p-8 text-center text-slate-400">Nenhum protocolo encontrado para os filtros aplicados.</div>
      <?php else: ?>
        <?php foreach ($protocolos as $protocolo): ?>
          <div class="card p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
              <div class="space-y-2 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-700"><span><?= e($temaMeta['icone']) ?></span> <?= e($temaMeta['nome']) ?></span>
                  <?php if (!empty($protocolo['categoria'])): ?><span class="badge badge-info"><?= e($protocolo['categoria']) ?></span><?php endif; ?>
                </div>
                <div>
                  <h3 class="text-lg font-bold text-slate-800"><?= e($protocolo['titulo']) ?></h3>
                  <?php if (!empty($protocolo['objetivo'])): ?><p class="text-sm text-slate-600 mt-1"><?= nl2br(e($protocolo['objetivo'])) ?></p><?php endif; ?>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                  <div class="rounded-xl bg-slate-50 border p-3">
                    <div class="text-xs uppercase text-slate-400 mb-1">Checklist</div>
                    <div class="text-slate-700 whitespace-pre-wrap"><?= e($protocolo['checklist'] ?: '—') ?></div>
                  </div>
                  <div class="rounded-xl bg-slate-50 border p-3">
                    <div class="text-xs uppercase text-slate-400 mb-1">Critérios</div>
                    <div class="text-slate-700 whitespace-pre-wrap"><?= e($protocolo['criterios'] ?: '—') ?></div>
                  </div>
                  <div class="rounded-xl bg-slate-50 border p-3">
                    <div class="text-xs uppercase text-slate-400 mb-1">Orientações</div>
                    <div class="text-slate-700 whitespace-pre-wrap"><?= e($protocolo['orientacoes'] ?: '—') ?></div>
                  </div>
                </div>
                <?php if (!empty($protocolo['tags'])): ?><div class="text-xs text-slate-500">Tags: <?= e($protocolo['tags']) ?></div><?php endif; ?>
              </div>
              <div class="flex gap-2 shrink-0 flex-wrap justify-end">
                <a href="<?= APP_URL ?>/modules/prontuario/novo.php?tema=<?= urlencode($temaAtual) ?><?= $pacienteId > 0 ? '&paciente_id=' . $pacienteId : '' ?>&protocolo_id=<?= (int) $protocolo['id'] ?>" class="btn bg-violet-600 text-white"><i class="bi bi-magic"></i> Usar no prontuário</a>
                <a href="index.php?tema=<?= urlencode($temaAtual) ?>&editar=<?= (int) $protocolo['id'] ?>" class="btn bg-amber-500 text-white"><i class="bi bi-pencil"></i></a>
                <form method="POST" class="inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="arquivar">
                  <input type="hidden" name="id" value="<?= (int) $protocolo['id'] ?>">
                  <button class="btn bg-rose-600 text-white" data-confirm="Arquivar este protocolo?"><i class="bi bi-archive"></i></button>
                </form>
              </div>
            </div>
            <div class="mt-3 text-xs text-slate-400">Última atualização: <?= format_date($protocolo['atualizado_em'] ?? $protocolo['criado_em'], true) ?><?= !empty($protocolo['autor_nome']) ? ' · ' . e($protocolo['autor_nome']) : '' ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
