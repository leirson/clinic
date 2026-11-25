<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$page_title = 'Especialidades';
$themes = array_intersect_key(clinic_theme_catalog(), array_flip(clinic_enabled_specialties()));
$colorChoices = [
    'blue' => ['label' => 'Azul', 'hex' => '#0ea5e9'],
    'green' => ['label' => 'Verde', 'hex' => '#10b981'],
    'teal' => ['label' => 'Teal', 'hex' => '#14b8a6'],
    'cyan' => ['label' => 'Ciano', 'hex' => '#06b6d4'],
    'indigo' => ['label' => 'Índigo', 'hex' => '#6366f1'],
    'purple' => ['label' => 'Roxo', 'hex' => '#a855f7'],
    'rose' => ['label' => 'Rose', 'hex' => '#f43f5e'],
    'pink' => ['label' => 'Pink', 'hex' => '#ec4899'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $temaEscolhido = normalize_theme_choice(input('tema', 'medico'));
    clinic_require_specialty($temaEscolhido);
    $corEscolhida = array_key_exists(input('cor', 'blue'), $colorChoices) ? input('cor', 'blue') : 'blue';

    db()->prepare("UPDATE usuarios SET tema_preferido=?, cor_tema=? WHERE id=?")
        ->execute([$temaEscolhido, $corEscolhida, $_SESSION['user_id']]);

    flash('success', 'Especialidades atualizadas com sucesso!');
    redirect(APP_URL . '/modules/temas/index.php');
}

$u = current_user();
$tema_atual = get_theme();
$cor_atual = $u['cor_tema'] ?? 'blue';

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5">
  <h1 class="text-2xl font-bold"><i class="bi bi-palette text-primary"></i> Especialidades</h1>
  <p class="text-sm text-slate-500">Escolha a especialidade principal e a identidade visual que melhor representam sua rotina clínica.</p>
</div>

<?php if (!$themes): ?><div class="card p-5">Nenhuma especialidade habilitada. Solicite a habilitação ao desenvolvedor.</div><?php else: ?>
<form method="POST" class="space-y-5">
  <?= csrf_field() ?>

  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($themes as $themeKey => $meta): ?>
      <label data-specialty-option class="specialty-option card p-6 cursor-pointer border-2 transition hover:shadow-lg <?= $tema_atual === $themeKey ? 'border-primary ring-4 ring-primary/10 is-selected' : 'border-transparent hover:border-slate-200' ?>">
        <input type="radio" name="tema" value="<?= e($themeKey) ?>" <?= $tema_atual === $themeKey ? 'checked' : '' ?> class="hidden" data-specialty-radio>
        <div class="text-center">
          <div class="w-24 h-24 rounded-full bg-gradient-to-br <?= e($meta['gradient_from']) ?> <?= e($meta['gradient_to']) ?> text-white text-5xl flex items-center justify-center mx-auto mb-3 shadow-lg"><?= e($meta['icone']) ?></div>
          <h3 class="text-xl font-bold text-slate-800"><?= e($meta['nome']) ?></h3>
          <p class="text-sm text-slate-500 mt-2 min-h-[56px]"><?= e($meta['descricao']) ?></p>
          <div class="mt-3 text-xs uppercase tracking-wide text-slate-400"><?= e($themeKey) ?></div>
          <div class="mt-4 inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600"><?= e($meta['summary']) ?></div>
        </div>
      </label>
    <?php endforeach; ?>
  </div>

  <div class="card p-5">
    <h3 class="font-bold text-slate-700 mb-3">Cor de destaque</h3>
    <p class="text-sm text-slate-500 mb-4">A cor escolhida é usada nos botões, badges e destaques do sistema.</p>
    <div class="flex gap-3 flex-wrap">
      <?php foreach ($colorChoices as $colorKey => $color): ?>
        <label class="flex flex-col items-center gap-1 cursor-pointer">
          <input type="radio" name="cor" value="<?= e($colorKey) ?>" <?= $cor_atual === $colorKey ? 'checked' : '' ?> class="hidden peer">
          <div class="w-12 h-12 rounded-full border-4 peer-checked:ring-4 peer-checked:ring-slate-300 transition" style="background:<?= e($color['hex']) ?>;"></div>
          <span class="text-xs"><?= e($color['label']) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="flex justify-end">
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Aplicar especialidades</button>
  </div>
</form>
<?php endif; require_once __DIR__ . '/../../includes/footer.php'; ?>
