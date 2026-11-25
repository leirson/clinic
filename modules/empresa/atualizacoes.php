<?php
$page_title = 'Atualizações do Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_admin();

$companyId = active_company_id();
$defaults = github_update_defaults();
$repo = $defaults['repo'];
$branch = $defaults['branch'];
$changelogPath = $defaults['changelog_path'] ?? 'CHANGELOG_v2.md';
$embeddedToken = defined('GITHUB_UPDATE_TOKEN') ? trim((string) GITHUB_UPDATE_TOKEN) : '';
$tokenMasked = $defaults['token'] !== '' ? github_token_mask($defaults['token']) : '';
$checkResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $action = input('action');

    try {
        if ($action === 'save_settings') {
            $repo = trim((string) input('github_repo'));
            $branch = trim((string) input('github_branch'));
            $token = trim((string) input('github_token'));
            $changelogPath = trim((string) input('github_changelog_path', 'CHANGELOG_v2.md'));

            if ($repo === '') {
                throw new RuntimeException('Informe o repositório GitHub no formato usuario/repositorio.');
            }

            app_config_set('github_repo', $repo, $companyId);
            app_config_set('github_branch', $branch !== '' ? $branch : 'main', $companyId);
            app_config_set('github_changelog_path', $changelogPath !== '' ? $changelogPath : 'CHANGELOG_v2.md', $companyId);
            if ($token !== '') {
                app_config_set('github_token', $token, $companyId);
                $tokenMasked = github_token_mask($token);
            }

            app_config_set('github_last_status', 'configurado', $companyId);
            log_action('update_settings', 'configuracoes', null, 'Configurações de atualização via GitHub atualizadas.');
            flash('success', 'Configurações de atualização salvas com sucesso.');
            redirect('atualizacoes.php');
        }

        if ($action === 'check_update') {
            $checkResult = github_updater_check();
            $changelogMessage = !empty($checkResult['changelog_content'])
                ? ' O changelog remoto foi lido e exibido abaixo.'
                : ' O arquivo de changelog não foi encontrado no caminho informado.';
            flash('success', (!empty($checkResult['update_available'])
                ? 'Há uma atualização disponível no repositório configurado.'
                : 'O sistema já está alinhado com a versão consultada no GitHub.') . $changelogMessage);
            redirect('atualizacoes.php');
        }

        if ($action === 'apply_update') {
            $result = github_apply_update();
            $migrationNames = array_map(static function ($item) {
                return (string) ($item['filename'] ?? '');
            }, (array) ($result['database_migrations'] ?? []));
            $migrationNames = array_values(array_filter($migrationNames));
            $migrationSummary = $migrationNames
                ? (' Banco atualizado com: ' . implode(', ', $migrationNames) . '.')
                : ' Nenhuma nova migração de banco foi necessária.';
            $backupSummary = !empty($result['database_backup']['filename'])
                ? (' Backup pré-atualização gerado: ' . (string) $result['database_backup']['filename'] . '.')
                : '';
            flash('success', 'Atualização aplicada com sucesso para a versão ' . e($result['remote_version']) . '.' . $migrationSummary . ' ' . $backupSummary . ' Faça login novamente se necessário.');
            redirect('atualizacoes.php');
        }

        if ($action === 'rollback_update') {
            $result = github_rollback_last_database_backup();
            flash('success', 'Rollback do banco executado com sucesso usando o backup ' . e($result['filename']) . '. Comandos restaurados: ' . (int) $result['statements'] . '.');
            redirect('atualizacoes.php');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('atualizacoes.php');
    }
}

$environment = updater_environment_status();
$currentVersion = app_current_version();
$lastCheckAt = app_config_get('github_last_check_at', '-');
$lastUpdateAt = app_config_get('github_last_update_at', '-');
$lastStatus = app_config_get('github_last_status', 'não configurado');
$lastCommitSha = (string) app_config_get('github_last_commit_sha', '');
$lastRemoteVersion = (string) app_config_get('github_last_remote_version', '');
$lastCommitMessage = (string) app_config_get('github_last_commit_message', '');
$lastCommitDate = (string) app_config_get('github_last_commit_date', '');
$lastError = (string) app_config_get('github_last_error', '');
$lastMigrationsRaw = (string) app_config_get('github_last_migrations', '');
$lastMigrations = json_decode($lastMigrationsRaw, true);
if (!is_array($lastMigrations)) {
    $lastMigrations = [];
}
$lastBackupFile = (string) app_config_get('github_last_backup_file', '');
$lastBackupAt = (string) app_config_get('github_last_backup_at', '');
$lastBackupSize = (string) app_config_get('github_last_backup_size', '0');
$lastRollbackStatus = (string) app_config_get('github_last_rollback_status', '');
$lastRollbackAt = (string) app_config_get('github_last_rollback_at', '');
$lastRollbackFile = (string) app_config_get('github_last_rollback_file', '');
$lastRollbackDetails = (string) app_config_get('github_last_rollback_details', '');
$canRollback = $lastBackupFile !== '';
$repo = (string) app_config_get('github_repo', $repo, $companyId);
$branch = (string) app_config_get('github_branch', $branch, $companyId);
$changelogPath = (string) app_config_get('github_changelog_path', $changelogPath, $companyId);
$storedToken = trim((string) app_config_get('github_token', '', $companyId));
$hasToken = $storedToken !== '' || $embeddedToken !== '';
$tokenMasked = $storedToken !== ''
    ? github_token_mask($storedToken)
    : ($embeddedToken !== '' ? github_token_mask($embeddedToken) : '');
$lastChangelog = trim((string) app_config_get('github_last_changelog', '', $companyId));
$lastChangelogPath = (string) app_config_get('github_changelog_path', $changelogPath, $companyId);
$lastChangelogHighlights = $lastChangelog !== ''
    ? changelog_extract_latest_section($lastChangelog, $lastRemoteVersion)
    : '';
$lastChangelogHighlightsHtml = $lastChangelogHighlights !== ''
    ? changelog_markdown_to_html($lastChangelogHighlights)
    : '';
$lastChangelogHtml = $lastChangelog !== ''
    ? changelog_markdown_to_html($lastChangelog)
    : '';
?>
<div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div>
    <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-arrow-repeat text-primary"></i> Atualizações do Sistema</h1>
    <p class="text-sm text-slate-500">Consulte versões, conecte o repositório privado e aplique atualizações diretamente pelo GitHub.</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="<?= APP_URL ?>/modules/empresa/index.php" class="btn bg-slate-200 text-slate-700"><i class="bi bi-arrow-left"></i> Voltar</a>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
  <div class="xl:col-span-2 space-y-5">
    <form method="POST" class="card p-6 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_settings">
      <div class="flex items-center justify-between gap-3 border-b pb-3">
        <h3 class="font-bold text-slate-700"><i class="bi bi-github"></i> Integração com GitHub</h3>
        <span class="badge <?= $hasToken ? 'badge-success' : 'badge-warning' ?>"><?= $hasToken ? 'Token salvo' : 'Token pendente' ?></span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="text-sm">Repositório</label>
          <input name="github_repo" value="<?= e($repo) ?>" class="form-input" placeholder="usuario/repositorio">
          <p class="text-xs text-slate-500 mt-1">Exemplo: leirson/Clinicapp</p>
        </div>
        <div>
          <label class="text-sm">Branch de atualização</label>
          <input name="github_branch" value="<?= e($branch) ?>" class="form-input" placeholder="main">
          <p class="text-xs text-slate-500 mt-1">Se não tiver certeza, use a branch padrão do repositório.</p>
        </div>
        <div>
          <label class="text-sm">Token GitHub</label>
          <input name="github_token" type="password" class="form-input" placeholder="Cole um token com acesso ao repositório privado">
          <p class="text-xs text-slate-500 mt-1">Token atual: <?= $tokenMasked !== '' ? e($tokenMasked) : 'não configurado' ?>. Deixe em branco para manter o token já embutido/configurado.</p>
        </div>
        <div class="md:col-span-2">
          <label class="text-sm">Arquivo de changelog</label>
          <input name="github_changelog_path" value="<?= e($changelogPath !== '' ? $changelogPath : 'CHANGELOG_v2.md') ?>" class="form-input" placeholder="CHANGELOG_v2.md">
          <p class="text-xs text-slate-500 mt-1">Exemplo: <code>CHANGELOG_v2.md</code>, <code>CHANGELOG.md</code> ou <code>docs/CHANGELOG_v2.md</code>.</p>
        </div>
      </div>
      <div class="flex justify-end">
        <button class="btn btn-primary"><i class="bi bi-save"></i> Salvar integração</button>
      </div>
    </form>

    <div class="card p-6 space-y-4">
      <div class="flex items-center justify-between gap-3 border-b pb-3">
        <h3 class="font-bold text-slate-700"><i class="bi bi-cloud-check"></i> Verificação e aplicação</h3>
        <span class="badge badge-info">Versão instalada: v<?= e($currentVersion) ?></span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-slate-500">Último status</div>
          <div class="font-semibold text-slate-800 mt-1"><?= e(ucwords(str_replace('_', ' ', $lastStatus))) ?></div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-slate-500">Última versão remota consultada</div>
          <div class="font-semibold text-slate-800 mt-1"><?= e($lastRemoteVersion !== '' ? $lastRemoteVersion : '-') ?></div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-slate-500">Última verificação</div>
          <div class="font-semibold text-slate-800 mt-1"><?= e($lastCheckAt ?: '-') ?></div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-slate-500">Última atualização aplicada</div>
          <div class="font-semibold text-slate-800 mt-1"><?= e($lastUpdateAt ?: '-') ?></div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 p-4 text-sm text-slate-600 space-y-2">
        <div><strong>Commit:</strong> <?= e($lastCommitSha !== '' ? substr($lastCommitSha, 0, 7) : '-') ?></div>
        <div><strong>Mensagem:</strong> <?= e($lastCommitMessage !== '' ? $lastCommitMessage : '-') ?></div>
        <div><strong>Data do commit remoto:</strong> <?= e($lastCommitDate !== '' ? format_date($lastCommitDate, true) : '-') ?></div>
        <div><strong>Último backup pré-atualização:</strong> <?= e($lastBackupFile !== '' ? $lastBackupFile : '-') ?><?php if ($lastBackupAt !== ''): ?> <span class="text-slate-500">(<?= e($lastBackupAt) ?><?php if ((int) $lastBackupSize > 0): ?> · <?= round(((int) $lastBackupSize) / 1024, 2) ?> KB<?php endif; ?>)</span><?php endif; ?></div>
        <div><strong>Rollback:</strong> <?= e($lastRollbackStatus !== '' ? ucwords(str_replace('_', ' ', $lastRollbackStatus)) : '-') ?><?php if ($lastRollbackAt !== ''): ?> <span class="text-slate-500">(<?= e($lastRollbackAt) ?>)</span><?php endif; ?></div>
        <?php if ($lastRollbackFile !== '' || $lastRollbackDetails !== ''): ?>
          <div><strong>Detalhes do rollback:</strong> <?= e(trim($lastRollbackFile . ' ' . $lastRollbackDetails)) ?></div>
        <?php endif; ?>
        <div><strong>Migrações de banco na última atualização:</strong>
          <?php if (!empty($lastMigrations)): ?>
            <ul class="mt-2 list-disc pl-5 text-slate-700">
              <?php foreach ($lastMigrations as $migration): ?>
                <li><?= e((string) ($migration['filename'] ?? '-')) ?><?php if (!empty($migration['statements'])): ?> <span class="text-slate-500">(<?= (int) $migration['statements'] ?> comandos)</span><?php endif; ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <span class="text-slate-700"> Nenhuma migração pendente foi executada.</span>
          <?php endif; ?>
        </div>
        <?php if ($lastError !== ''): ?>
          <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700"><strong>Último erro:</strong> <?= e($lastError) ?></div>
        <?php endif; ?>
      </div>

      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-4">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h4 class="font-semibold text-slate-800"><i class="bi bi-journal-text"></i> Changelog remoto lido do GitHub</h4>
            <p class="text-xs text-slate-500">Arquivo lido ao verificar atualização: <?= e($lastChangelogPath !== '' ? $lastChangelogPath : 'CHANGELOG_v2.md') ?></p>
          </div>
          <span class="badge <?= $lastChangelog !== '' ? 'badge-success' : 'badge-warning' ?>"><?= $lastChangelog !== '' ? 'Conteúdo carregado' : 'Arquivo não encontrado' ?></span>
        </div>
        <?php if ($lastChangelog !== ''): ?>
          <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <div class="rounded-xl border border-emerald-200 bg-white p-4">
              <div class="flex items-center justify-between gap-2 mb-3">
                <h5 class="font-semibold text-slate-800">Destaques da versão consultada</h5>
                <span class="text-xs text-slate-500">v<?= e($lastRemoteVersion !== '' ? $lastRemoteVersion : $currentVersion) ?></span>
              </div>
              <div class="prose prose-sm max-w-none text-slate-700 space-y-2">
                <?= $lastChangelogHighlightsHtml !== '' ? $lastChangelogHighlightsHtml : '<p class="text-sm text-slate-500">Não foi possível isolar uma seção específica; exibindo o conteúdo completo ao lado.</p>' ?>
              </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
              <div class="flex items-center justify-between gap-2 mb-3">
                <h5 class="font-semibold text-slate-800">Conteúdo completo lido</h5>
                <span class="text-xs text-slate-500">Atualizado em <?= e($lastCheckAt ?: '-') ?></span>
              </div>
              <div class="max-h-[28rem] overflow-y-auto pr-1">
                <div class="prose prose-sm max-w-none text-slate-700 space-y-2">
                  <?= $lastChangelogHtml ?>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">Ao clicar em <strong>Verificar atualização</strong>, o sistema consulta o GitHub, lê o arquivo de changelog configurado e exibe aqui as notas encontradas.</div>
        <?php endif; ?>
      </div>

      <div class="flex flex-wrap justify-end gap-2">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="check_update">
          <button class="btn bg-sky-600 text-white"><i class="bi bi-search"></i> Verificar atualização</button>
        </form>
        <form method="POST" onsubmit="return confirm('Deseja baixar a versão mais recente, gerar backup automático do banco, substituir os arquivos do sistema e aplicar as migrações pendentes?');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="apply_update">
          <button class="btn bg-emerald-600 text-white"><i class="bi bi-cloud-arrow-down"></i> Atualizar agora</button>
        </form>
        <?php if ($canRollback): ?>
        <form method="POST" onsubmit="return confirm('Deseja restaurar o banco a partir do último backup pré-atualização? Esta ação não reverte os arquivos do sistema.');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="rollback_update">
          <button class="btn bg-amber-600 text-white"><i class="bi bi-arrow-counterclockwise"></i> Rollback do banco</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="space-y-5">
    <div class="card p-6">
      <h3 class="font-bold text-slate-700 mb-4"><i class="bi bi-info-circle"></i> Ambiente do servidor</h3>
      <div class="space-y-3 text-sm">
        <div class="flex items-center justify-between"><span>ZipArchive</span><span class="badge <?= $environment['zip'] ? 'badge-success' : 'badge-danger' ?>"><?= $environment['zip'] ? 'Disponível' : 'Indisponível' ?></span></div>
        <div class="flex items-center justify-between"><span>cURL</span><span class="badge <?= $environment['curl'] ? 'badge-success' : 'badge-warning' ?>"><?= $environment['curl'] ? 'Disponível' : 'Fallback HTTP' ?></span></div>
        <div class="flex items-center justify-between"><span>Gzip (.gz)</span><span class="badge <?= $environment['gzip'] ? 'badge-success' : 'badge-warning' ?>"><?= $environment['gzip'] ? 'Disponível' : 'Indisponível' ?></span></div>
        <div class="flex items-center justify-between"><span>Pasta raiz gravável</span><span class="badge <?= $environment['root_writable'] ? 'badge-success' : 'badge-danger' ?>"><?= $environment['root_writable'] ? 'Sim' : 'Não' ?></span></div>
        <div class="flex items-center justify-between"><span>config/ gravável</span><span class="badge <?= $environment['config_writable'] ? 'badge-success' : 'badge-danger' ?>"><?= $environment['config_writable'] ? 'Sim' : 'Não' ?></span></div>
        <div class="flex items-center justify-between"><span>storage/backups gravável</span><span class="badge <?= $environment['backup_dir_writable'] ? 'badge-success' : 'badge-danger' ?>"><?= $environment['backup_dir_writable'] ? 'Sim' : 'Não' ?></span></div>
        <div class="flex items-center justify-between"><span>Banco pronto para migrações</span><span class="badge <?= $environment['db_migration_ready'] ? 'badge-success' : 'badge-danger' ?>"><?= $environment['db_migration_ready'] ? 'Sim' : 'Não' ?></span></div>
      </div>
    </div>

    <div class="card p-6">
      <h3 class="font-bold text-slate-700 mb-4"><i class="bi bi-shield-lock"></i> Regras da atualização</h3>
      <ul class="space-y-2 text-sm text-slate-600 list-disc pl-5">
        <li>O sistema preserva <code>config/config.php</code>.</li>
        <li>O sistema preserva <code>config/install.lock</code>.</li>
        <li>Arquivos em <code>assets/uploads</code> não são substituídos.</li>
        <li>Antes de atualizar, o sistema gera um backup automático do banco em <code>storage/backups/</code>.</li>
        <li>Ao atualizar, o sistema aplica automaticamente novos scripts <code>sql/upgrade_*.sql</code> ainda não executados.</li>
        <li>Se uma migração falhar, o sistema tenta restaurar automaticamente o último backup pré-atualização.</li>
        <li>O botão de rollback restaura apenas o banco de dados, sem reverter arquivos do sistema.</li>
        <li>A versão exibida passa a considerar <code>config/version.php</code>.</li>
      </ul>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
