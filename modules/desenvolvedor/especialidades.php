<?php
require_once __DIR__ . '/../../includes/functions.php';
require_developer();
$page_title = 'Gerenciar Especialidades';
$empresaId = (int) input('empresa_id', active_company_id());
$stmt = db()->prepare('SELECT id, nome_fantasia, razao_social FROM empresas WHERE id=?');
$stmt->execute([$empresaId]);
$selectedCompany = $stmt->fetch();
if (!$selectedCompany) { http_response_code(404); exit('Empresa não encontrada.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    try {
        if (!app_config_uses_company_scope()) throw new RuntimeException('Atualize o banco para a estrutura multiempresa antes de configurar especialidades.');
        $chosen = $_POST['especialidades'] ?? [];
        if (!is_array($chosen)) throw new RuntimeException('Seleção inválida.');
        foreach ($chosen as $key) {
            if (!is_string($key) || !in_array($key, allowed_theme_keys(), true)) throw new RuntimeException('Especialidade inválida.');
        }
        $chosen = array_values(array_intersect(allowed_theme_keys(), $chosen));
        db()->beginTransaction();
        if (!app_config_set('especialidades_habilitadas', json_encode($chosen), $empresaId)) throw new RuntimeException('Não foi possível salvar.');
        log_action('especialidades_alterar', 'empresas', $empresaId, 'Especialidades habilitadas: ' . implode(', ', $chosen));
        db()->commit();
        flash('success', 'Especialidades da empresa atualizadas. Nenhum registro clínico foi excluído.');
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash('error', $e->getMessage());
    }
    redirect(APP_URL . '/modules/desenvolvedor/especialidades.php?empresa_id=' . $empresaId);
}
$companies = db()->query('SELECT id, nome_fantasia, razao_social FROM empresas ORDER BY nome_fantasia, razao_social')->fetchAll();
$enabled = clinic_enabled_specialties($empresaId);
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="space-y-5">
  <div class="flex justify-between items-center gap-3">
    <h1 class="text-2xl font-bold"><i class="bi bi-toggles text-primary"></i> Gerenciar Especialidades</h1>
    <a class="btn bg-slate-100" href="<?= APP_URL ?>/modules/desenvolvedor/index.php">Voltar ao painel</a>
  </div>
  <form method="GET" class="card p-5 flex gap-3 items-end flex-wrap">
    <div><label for="empresa" class="text-sm font-medium">Empresa a configurar</label>
    <select id="empresa" name="empresa_id" class="form-select">
      <?php foreach ($companies as $company): ?>
        <option value="<?= (int) $company['id'] ?>" <?= (int) $company['id'] === $empresaId ? 'selected' : '' ?>><?= e($company['nome_fantasia'] ?: $company['razao_social']) ?></option>
      <?php endforeach; ?>
    </select></div>
    <button class="btn btn-primary">Carregar empresa</button>
  </form>
  <form method="POST" class="space-y-5">
    <?= csrf_field() ?>
    <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
    <div class="card p-5">
      <h2 class="font-bold"><?= e($selectedCompany['nome_fantasia'] ?: $selectedCompany['razao_social']) ?></h2>
      <p class="text-sm text-slate-500 mt-2">Marque para habilitar; desmarque para desabilitar. As alterações valem somente para esta empresa e não alteram o contexto ativo do desenvolvedor.</p>
      <p class="text-sm text-slate-500 mt-2">Especialidades desabilitadas saem dos seletores e seus módulos específicos ficam bloqueados, inclusive por URL. Pacientes, profissionais e prontuários gerais são preservados. Reabilitar restaura o acesso aos módulos. Sem seleção, somente as funções gerais permanecem disponíveis.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <?php foreach (clinic_theme_catalog() as $key => $meta): $active = in_array($key, $enabled, true); ?>
        <label class="card p-5 border-2 <?= $active ? 'border-primary' : 'border-slate-200' ?> flex gap-3 items-start cursor-pointer">
          <input type="checkbox" name="especialidades[]" value="<?= e($key) ?>" <?= $active ? 'checked' : '' ?> class="mt-1">
          <span><span class="font-bold"><?= e($meta['icone'] . ' ' . $meta['nome']) ?></span><span class="block text-sm text-slate-500 mt-2"><?= e($meta['descricao']) ?></span><span class="block text-xs mt-2"><?= $active ? 'Habilitada' : 'Desabilitada' ?> — altere e salve para aplicar</span></span>
        </label>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar especialidades desta empresa</button>
  </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
