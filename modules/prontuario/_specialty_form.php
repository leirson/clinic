<?php
$specialtyTheme = $specialtyTheme ?? (function_exists('get_theme') ? get_theme() : 'medico');
$specialtyMeta = specialty_theme_meta($specialtyTheme);
$specialtyDefinition = specialty_form_definitions($specialtyTheme);
$specialtyForm = $specialtyForm ?? specialty_record_defaults($specialtyTheme);
if (!$specialtyDefinition) {
    return;
}
?>
<div class="rounded-2xl border border-primary/20 bg-white p-5 space-y-5">
  <div>
    <h3 class="text-lg font-bold text-slate-800"><span class="mr-2"><?= e($specialtyMeta['icone']) ?></span><?= e($specialtyDefinition['title']) ?></h3>
    <p class="text-sm text-slate-500 mt-1"><?= e($specialtyDefinition['description']) ?></p>
  </div>

  <?php foreach ($specialtyDefinition['sections'] as $section): ?>
    <div class="card p-5 bg-slate-50/70">
      <h4 class="font-bold text-slate-800 mb-1"><?= e($section['title']) ?></h4>
      <?php if (!empty($section['description'])): ?>
        <p class="text-xs text-slate-500 mb-4"><?= e($section['description']) ?></p>
      <?php endif; ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($section['fields'] as $field):
          $name = $field['name'];
          $label = $field['label'];
          $value = $specialtyForm[$name] ?? '';
          $type = $field['type'] ?? 'text';
          $widthClass = ($field['width'] ?? 'half') === 'full' ? 'md:col-span-2' : '';
          $required = !empty($field['required']);
          $requiredAttr = $required ? 'required' : '';
          $placeholder = $field['placeholder'] ?? '';
          $rows = (int) ($field['rows'] ?? 3);
          $min = isset($field['min']) ? ' min="' . e((string) $field['min']) . '"' : '';
          $max = isset($field['max']) ? ' max="' . e((string) $field['max']) . '"' : '';
          $step = isset($field['step']) ? ' step="' . e((string) $field['step']) . '"' : '';
        ?>
          <div class="<?= $widthClass ?>">
            <label class="text-sm font-medium"><?= e($label) ?><?= $required ? ' *' : '' ?></label>
            <?php if ($type === 'textarea'): ?>
              <textarea name="<?= e($name) ?>" rows="<?= $rows ?>" class="form-textarea" <?= $requiredAttr ?> placeholder="<?= e($placeholder) ?>"><?= e($value) ?></textarea>
            <?php elseif ($type === 'select'): ?>
              <select name="<?= e($name) ?>" class="form-select" <?= $requiredAttr ?>>
                <option value="">Selecione...</option>
                <?php foreach (($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                  <option value="<?= e($optionValue) ?>" <?= (string) $value === (string) $optionValue ? 'selected' : '' ?>><?= e($optionLabel) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" class="form-input" <?= $requiredAttr . $min . $max . $step ?> placeholder="<?= e($placeholder) ?>">
            <?php endif; ?>
            <?php if (!empty($field['required_if'])): ?>
              <p class="text-[11px] text-slate-400 mt-1">Obrigatório conforme a resposta informada no campo relacionado.</p>
            <?php endif; ?>
            <?php if (!empty($field['help'])): ?>
              <p class="text-xs text-amber-700 mt-1"><?= e($field['help']) ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
