<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$page_title = 'Modelos Clínicos';
$eid = active_company_id();
$temaAtual = normalize_theme_choice(get_theme());
$temaMeta = specialty_theme_meta($temaAtual);
$tiposDocumento = [
    'anamnese' => 'Anamnese',
    'evolucao' => 'Evolução',
    'retorno' => 'Retorno',
    'orientacao' => 'Orientações',
    'laudo' => 'Laudo / parecer',
    'checklist' => 'Checklist',
    'termo' => 'Termo / consentimento',
    'outro' => 'Outro',
];
$tipoFiltro = trim((string) input('tipo_documento', ''));
$q = trim((string) input('q', ''));
$editingId = (int) input('editar');
$editing = null;

if (!db_table_exists('modelos_clinicos')) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="card p-6"><h1 class="text-2xl font-bold mb-3"><i class="bi bi-ui-checks-grid text-primary"></i> Modelos Clínicos</h1><p class="text-sm text-slate-600">Execute o SQL atualizado do sistema para habilitar este módulo.</p></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $acao = input('acao');

    if ($acao === 'salvar') {
        $id = (int) input('id');
        $tema = $temaAtual;
        $tipoDocumento = array_key_exists(input('tipo_documento', 'outro'), $tiposDocumento) ? input('tipo_documento', 'outro') : 'outro';
        $titulo = trim((string) input('titulo'));
        $conteudo = trim((string) input('conteudo'));
        $camposChave = trim((string) input('campos_chave'));
        $tags = trim((string) input('tags'));

        if ($titulo === '' || $conteudo === '') {
            flash('error', 'Informe o título e o conteúdo do modelo clínico.');
            redirect(APP_URL . '/modules/modelos_clinicos/index.php');
        }

        if ($id > 0) {
            db()->prepare("UPDATE modelos_clinicos SET tema=?, tipo_documento=?, titulo=?, conteudo=?, campos_chave=?, tags=?, atualizado_por=? WHERE id=? AND empresa_id=?")
                ->execute([$tema, $tipoDocumento, $titulo, $conteudo, $camposChave, $tags, $_SESSION['user_id'], $id, $eid]);
            flash('success', 'Modelo clínico atualizado.');
        } else {
            db()->prepare("INSERT INTO modelos_clinicos (empresa_id, tema, tipo_documento, titulo, conteudo, campos_chave, tags, criado_por, atualizado_por) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$eid, $tema, $tipoDocumento, $titulo, $conteudo, $camposChave, $tags, $_SESSION['user_id'], $_SESSION['user_id']]);
            flash('success', 'Modelo clínico cadastrado.');
        }

        log_action('modelos_salvar', 'modelos_clinicos', $id ?: db()->lastInsertId(), 'Cadastro/edição de modelo clínico');
        redirect(APP_URL . '/modules/modelos_clinicos/index.php');
    }

    if ($acao === 'arquivar') {
        $id = (int) input('id');
        db()->prepare("UPDATE modelos_clinicos SET ativo=0, atualizado_por=? WHERE id=? AND empresa_id=?")
            ->execute([$_SESSION['user_id'], $id, $eid]);
        flash('success', 'Modelo clínico arquivado.');
        log_action('modelos_arquivar', 'modelos_clinicos', $id, 'Arquivamento de modelo clínico');
        redirect(APP_URL . '/modules/modelos_clinicos/index.php');
    }
}

if ($editingId > 0) {
    $stmt = db()->prepare("SELECT * FROM modelos_clinicos WHERE id=? AND empresa_id=? LIMIT 1");
    $stmt->execute([$editingId, $eid]);
    $editing = $stmt->fetch() ?: null;
}

$sql = "SELECT mc.*, u.nome AS autor_nome
        FROM modelos_clinicos mc
        LEFT JOIN usuarios u ON u.id = mc.atualizado_por
        WHERE mc.empresa_id=? AND mc.ativo=1 AND mc.tema=?";
$params = [$eid, $temaAtual];
if ($tipoFiltro !== '' && array_key_exists($tipoFiltro, $tiposDocumento)) {
    $sql .= " AND mc.tipo_documento=?";
    $params[] = $tipoFiltro;
}
if ($q !== '') {
    $sql .= " AND (mc.titulo LIKE ? OR mc.tags LIKE ? OR mc.campos_chave LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$sql .= " ORDER BY mc.tipo_documento, mc.titulo";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$modelos = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="space-y-5">
  <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
    <div>
      <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-ui-checks-grid text-primary"></i> Modelos Clínicos</h1>
      <p class="text-sm text-slate-500">Textos prontos para anamnese, evolução, orientações, laudos e documentos recorrentes da especialidade atual.</p>
      <p class="text-sm text-primary mt-1"><strong><?= e($temaMeta['icone']) ?> <?= e($temaMeta['nome']) ?></strong></p>
    </div>
    <div class="flex gap-2 flex-wrap">
      <a href="<?= APP_URL ?>/modules/protocolos/index.php" class="btn bg-slate-100 text-slate-700"><i class="bi bi-journal-check"></i> Protocolos</a>
      <a href="<?= APP_URL ?>/modules/cuidados/index.php" class="btn btn-primary"><i class="bi bi-clipboard2-heart"></i> Cuidados</a>
    </div>
  </div>

  <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
    <div class="md:col-span-3">
      <label class="text-xs text-slate-500">Busca</label>
      <input type="text" name="q" value="<?= e($q) ?>" class="form-input" placeholder="Título, tags ou campos-chave">
    </div>
    <div>
      <label class="text-xs text-slate-500">Tipo</label>
      <select name="tipo_documento" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($tiposDocumento as $tipoKey => $tipoLabel): ?>
          <option value="<?= e($tipoKey) ?>" <?= $tipoFiltro === $tipoKey ? 'selected' : '' ?>><?= e($tipoLabel) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex gap-2">
      <button class="btn btn-primary flex-1"><i class="bi bi-search"></i> Filtrar</button>
      <a href="index.php" class="btn bg-slate-100 text-slate-700">Limpar</a>
    </div>
  </form>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-plus-circle"></i> <?= $editing ? 'Editar modelo' : 'Novo modelo' ?></h2>
      <form method="POST" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
          <span class="font-medium text-slate-700">Especialidade:</span>
          <span class="ml-1"><?= e($temaMeta['icone']) ?> <?= e($temaMeta['nome']) ?></span>
        </div>
        <div>
          <label class="text-xs text-slate-500">Tipo de documento</label>
          <select name="tipo_documento" class="form-select">
            <?php foreach ($tiposDocumento as $tipoKey => $tipoLabel): ?>
              <option value="<?= e($tipoKey) ?>" <?= (($editing['tipo_documento'] ?? 'evolucao') === $tipoKey) ? 'selected' : '' ?>><?= e($tipoLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-xs text-slate-500">Título</label>
          <input type="text" name="titulo" required class="form-input" value="<?= e($editing['titulo'] ?? '') ?>">
        </div>
        <div>
          <label class="text-xs text-slate-500">Campos-chave</label>
          <input type="text" name="campos_chave" class="form-input" value="<?= e($editing['campos_chave'] ?? '') ?>" placeholder="Ex.: dor, sinais de alarme, retorno">
        </div>
        <div>
          <label class="text-xs text-slate-500">Conteúdo do modelo</label>
          <textarea name="conteudo" rows="10" class="form-textarea" required><?= e($editing['conteudo'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-xs text-slate-500">Tags</label>
          <input type="text" name="tags" class="form-input" value="<?= e($editing['tags'] ?? '') ?>" placeholder="separadas por vírgula">
        </div>
        <div class="flex justify-end gap-2">
          <?php if ($editing): ?><a href="index.php" class="btn bg-slate-100 text-slate-700">Cancelar</a><?php endif; ?>
          <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
      </form>
    </div>

    <div class="xl:col-span-2 space-y-4">
      <?php if (!$modelos): ?>
        <div class="card p-8 text-center text-slate-400">Nenhum modelo clínico encontrado.</div>
      <?php else: ?>
        <?php foreach ($modelos as $modelo): $meta = specialty_theme_meta($modelo['tema']); ?>
          <div class="card p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
              <div class="space-y-2 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-700"><span><?= e($meta['icone']) ?></span> <?= e($meta['nome']) ?></span>
                  <span class="badge badge-info"><?= e($tiposDocumento[$modelo['tipo_documento']] ?? $modelo['tipo_documento']) ?></span>
                </div>
                <h3 class="text-lg font-bold text-slate-800"><?= e($modelo['titulo']) ?></h3>
                <?php if (!empty($modelo['campos_chave'])): ?><div class="text-xs text-slate-500">Campos-chave: <?= e($modelo['campos_chave']) ?></div><?php endif; ?>
                <div class="rounded-xl bg-slate-50 border p-4 text-sm text-slate-700 whitespace-pre-wrap"><?= e($modelo['conteudo']) ?></div>
                <?php if (!empty($modelo['tags'])): ?><div class="text-xs text-slate-500">Tags: <?= e($modelo['tags']) ?></div><?php endif; ?>
              </div>
              <div class="flex gap-2 shrink-0">
                <a href="index.php?editar=<?= $modelo['id'] ?>" class="btn bg-amber-500 text-white"><i class="bi bi-pencil"></i></a>
                <form method="POST" class="inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="arquivar">
                  <input type="hidden" name="id" value="<?= (int) $modelo['id'] ?>">
                  <button class="btn bg-rose-600 text-white" data-confirm="Arquivar este modelo?"><i class="bi bi-archive"></i></button>
                </form>
              </div>
            </div>
            <div class="mt-3 text-xs text-slate-400">Última atualização: <?= format_date($modelo['atualizado_em'] ?? $modelo['criado_em'], true) ?><?= !empty($modelo['autor_nome']) ? ' · ' . e($modelo['autor_nome']) : '' ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
