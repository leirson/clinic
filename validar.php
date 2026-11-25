<?php
require_once __DIR__ . '/includes/functions.php';

$hash_curto = preg_replace('/[^a-f0-9]/i', '', input('h'));
$resultado = null;
$tipo = null;

header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
if (strlen($hash_curto) >= 16 && strlen($hash_curto) <= 64) {
    $r = db()->prepare("SELECT r.*, p.nome AS pn, u.nome AS prof, u.registro_profissional FROM receitas r JOIN pacientes p ON p.id=r.paciente_id JOIN usuarios u ON u.id=r.profissional_id WHERE r.hash_documento LIKE ?");
    $r->execute([$hash_curto.'%']);
    $resultado = $r->fetch();
    if ($resultado) $tipo = 'receita';
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Validação - <?= APP_NAME ?></title>
<?= clinic_asset_styles(['resources/entries/validar.js']) ?>
<link rel="icon" type="image/png" href="<?= APP_URL ?>/assets/img/icon-192.png">
</head><body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
<div class="max-w-md w-full">
  <div class="bg-white rounded-2xl shadow-xl p-6">
    <div class="text-center mb-5">
      <i class="bi bi-shield-check text-5xl text-primary"></i>
      <h1 class="text-xl font-bold mt-2">Validação de Documento</h1>
    </div>

    <?php if ($resultado): ?>
      <div class="bg-green-50 border border-green-200 p-4 rounded-lg">
        <p class="text-green-800 font-bold"><i class="bi bi-check-circle-fill"></i> Documento Válido</p>
        <dl class="mt-3 text-sm space-y-1">
          <div><dt class="text-slate-500 inline">Tipo:</dt><dd class="inline ml-2">Receita Médica</dd></div>
          <div><dt class="text-slate-500 inline">Paciente:</dt><dd class="inline ml-2"><?= e($resultado['pn']) ?></dd></div>
          <div><dt class="text-slate-500 inline">Emitido por:</dt><dd class="inline ml-2">Dr(a). <?= e($resultado['prof']) ?></dd></div>
          <div><dt class="text-slate-500 inline">Registro Profissional:</dt><dd class="inline ml-2"><?= e($resultado['registro_profissional']) ?></dd></div>
          <div><dt class="text-slate-500 inline">Data:</dt><dd class="inline ml-2"><?= format_date($resultado['data_emissao']) ?></dd></div>
        </dl>
        <p class="mt-3 text-xs text-slate-500">Hash SHA-256: <code class="break-all"><?= e($resultado['hash_documento']) ?></code></p>
      </div>
    <?php else: ?>
      <div class="bg-red-50 border border-red-200 p-4 rounded-lg">
        <p class="text-red-800 font-bold"><i class="bi bi-x-circle-fill"></i> Documento não encontrado</p>
        <p class="text-sm text-red-600 mt-1">O hash informado não corresponde a nenhum documento válido em nossa base.</p>
      </div>
    <?php endif; ?>

    <p class="text-xs text-center text-slate-400 mt-5">© <?= date('Y') ?> <?= APP_NAME ?></p>
  </div>
</div>
<?= clinic_asset_scripts(['resources/entries/validar.js']) ?>
</body></html>
