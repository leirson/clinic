<?php
$page_title = 'Especialidade';
require_once __DIR__ . '/../../includes/header.php';

$tema = normalize_theme_choice(input('tema', get_theme()));
if (!is_custom_specialty_theme($tema)) {
    $tema = specialty_theme(current_user()['especialidade'] ?? '') ?: $tema;
}
if (!is_custom_specialty_theme($tema)) {
    flash('error', 'Selecione uma especialidade clínica estruturada.');
    redirect(APP_URL . '/dashboard.php');
}

$meta = specialty_theme_meta($tema);
$stats = specialty_module_stats($tema);
$resources = specialty_theme_resources($tema);
$definition = specialty_form_definitions($tema);
?>
<div class="space-y-5">
  <div class="rounded-3xl p-6 text-white bg-gradient-to-r <?= e($meta['gradient_from']) ?> <?= e($meta['gradient_to']) ?> shadow-lg">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <div class="text-5xl mb-3"><?= e($meta['icone']) ?></div>
        <h1 class="text-3xl font-bold"><?= e($meta['nome']) ?></h1>
        <p class="text-white/90 mt-2 max-w-3xl"><?= e($meta['descricao']) ?></p>
        <p class="text-sm text-white/70 mt-3">Tema ativo: <span class="font-semibold"><?= e($tema) ?></span></p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="<?= APP_URL ?>/modules/prontuario/novo.php" class="btn bg-white text-slate-800"><i class="bi bi-plus-circle"></i> Novo atendimento</a>
        <a href="<?= APP_URL ?>/modules/prontuario/index.php" class="btn bg-white/15 text-white border border-white/20"><i class="bi bi-journal-text"></i> Ver prontuários</a>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="card p-5">
      <div class="text-sm text-slate-500">Registros no mês</div>
      <div class="text-3xl font-bold text-slate-800 mt-2"><?= (int) $stats['records_month'] ?></div>
    </div>
    <div class="card p-5">
      <div class="text-sm text-slate-500">Pacientes no mês</div>
      <div class="text-3xl font-bold text-slate-800 mt-2"><?= (int) $stats['patients_month'] ?></div>
    </div>
    <div class="card p-5">
      <div class="text-sm text-slate-500">Campos obrigatórios</div>
      <div class="text-3xl font-bold text-slate-800 mt-2"><?= (int) $stats['required_fields'] ?></div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="xl:col-span-2 card p-6">
      <h2 class="text-lg font-bold text-slate-800 mb-4">Estrutura do atendimento</h2>
      <div class="space-y-4">
        <?php foreach (($definition['sections'] ?? []) as $section): ?>
          <div class="rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-800"><?= e($section['title']) ?></h3>
            <div class="mt-3 flex flex-wrap gap-2">
              <?php foreach (($section['fields'] ?? []) as $field): ?>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600 <?= !empty($field['required']) ? 'border border-amber-200 bg-amber-50 text-amber-700' : '' ?>"><?= e($field['label']) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-6">
        <h2 class="text-lg font-bold text-slate-800 mb-4">Atalhos da especialidade</h2>
        <div class="space-y-3">
          <?php foreach ($resources as $resource): ?>
            <a href="<?= e($resource['url']) ?>" class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:border-primary/30 hover:bg-slate-50 transition">
              <div class="w-11 h-11 rounded-xl flex items-center justify-center <?= e($resource['class']) ?>"><i class="bi <?= e($resource['icon']) ?>"></i></div>
              <div>
                <div class="font-semibold text-slate-800"><?= e($resource['label']) ?></div>
                <div class="text-sm text-slate-500"><?= e($resource['description']) ?></div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card p-6">
        <h2 class="text-lg font-bold text-slate-800 mb-3">Checklist rápido</h2>
        <ul class="space-y-2 text-sm text-slate-600">
          <?php foreach (specialty_required_labels($tema) as $label): ?>
            <li class="flex items-start gap-2"><i class="bi bi-check2-circle text-primary mt-0.5"></i><span><?= e($label) ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
