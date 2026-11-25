<?php
$page_title = 'Prontuário Eletrônico';
require_once __DIR__ . '/../../includes/header.php';

$paciente_id = (int) input('paciente_id');
$where = 'WHERE 1=1';
$params = [];
if ($paciente_id) {
    $where .= ' AND p.paciente_id = ?';
    $params[] = $paciente_id;
}

$stmt = db()->prepare("SELECT p.*, pa.nome AS paciente_nome, u.nome AS prof_nome
  FROM prontuarios p
  JOIN pacientes pa ON pa.id = p.paciente_id
  JOIN usuarios u ON u.id = p.profissional_id
  $where
  ORDER BY p.data_atendimento DESC LIMIT 200");
$stmt->execute($params);
$pronts = $stmt->fetchAll() ?: [];

$paciente = null;
$timeline = [];
if ($paciente_id) {
    $s = db()->prepare('SELECT * FROM pacientes WHERE id=? AND empresa_id=?');
    $s->execute([$paciente_id, active_company_id()]);
    $paciente = $s->fetch() ?: null;
    if ($paciente) {
        $timeline = patient_longitudinal_timeline($paciente_id, 20);
    }
}
?>
<div class="space-y-5">
  <div class="flex flex-wrap justify-between items-center gap-3">
    <div>
      <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-journal-medical text-primary"></i> Prontuário Eletrônico</h1>
      <?php if ($paciente): ?>
        <p class="text-sm text-slate-500">Timeline longitudinal de <strong><?= e($paciente['nome']) ?></strong></p>
      <?php else: ?>
        <p class="text-sm text-slate-500">Histórico clínico centralizado por paciente e especialidade.</p>
      <?php endif; ?>
    </div>
    <div class="flex gap-2 flex-wrap">
      <?php if ($paciente_id): ?>
        <a href="../pacientes/ver.php?id=<?= $paciente_id ?>" class="btn bg-slate-100 text-slate-700"><i class="bi bi-arrow-left"></i> Paciente</a>
      <?php endif; ?>
      <a href="novo.php<?= $paciente_id ? '?paciente_id=' . $paciente_id : '' ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nova Evolução</a>
    </div>
  </div>

  <?php if ($paciente_id && odontograma_enabled()): ?>
    <div class="card p-4 flex justify-between items-center bg-cyan-50 border-cyan-200">
      <div>
        <strong class="text-cyan-800"><i class="bi bi-emoji-smile"></i> Odontograma do Paciente</strong>
        <p class="text-xs text-slate-600">Visualize e edite o mapa dental.</p>
      </div>
      <a href="odontograma.php?paciente_id=<?= $paciente_id ?>" class="btn bg-cyan-600 text-white">Abrir Odontograma</a>
    </div>
  <?php endif; ?>

  <?php if ($paciente_id && get_theme() === 'psicologia'): ?>
    <div class="card p-4 bg-violet-50 border-violet-200">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <strong class="text-violet-800"><i class="bi bi-flower1"></i> Recursos de Psicologia</strong>
          <p class="text-xs text-slate-600">Acesse escalas clínicas e o plano terapêutico vinculados a este paciente.</p>
        </div>
        <div class="flex gap-2 flex-wrap">
          <a href="../psicologia/escalas.php?paciente_id=<?= $paciente_id ?>" class="btn bg-violet-600 text-white">Escalas Clínicas</a>
          <a href="../psicologia/planos.php?paciente_id=<?= $paciente_id ?>" class="btn bg-fuchsia-600 text-white">Plano Terapêutico</a>
        </div>
      </div>
    </div>
  <?php elseif ($paciente_id && is_custom_specialty_theme(get_theme())): ?>
    <div class="card p-4 bg-slate-50 border-slate-200">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <strong class="text-slate-800"><span class="mr-1"><?= e(specialty_theme_meta(get_theme())['icone']) ?></span> <?= e(specialty_theme_meta(get_theme())['nome']) ?></strong>
          <p class="text-xs text-slate-600">Atalhos clínicos e formulário estruturado da especialidade ativa.</p>
        </div>
        <div class="flex gap-2 flex-wrap">
          <?php foreach (specialty_theme_resources(get_theme(), $paciente_id) as $resource): ?>
            <a href="<?= e($resource['url']) ?>" class="btn bg-slate-800 text-white"><?= e($resource['label']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($timeline): ?>
    <div class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold text-slate-800"><i class="bi bi-clock-history"></i> Timeline longitudinal</h2>
        <a href="../pacientes/ver.php?id=<?= $paciente_id ?>" class="text-sm text-primary hover:underline">Abrir ficha completa do paciente</a>
      </div>
      <div class="space-y-3">
        <?php foreach ($timeline as $item): ?>
          <a href="<?= e($item['url']) ?>" class="block rounded-2xl border border-slate-200 bg-slate-50 p-4 hover:border-primary/40 hover:bg-white transition">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-full flex items-center justify-center <?= e($item['class']) ?>"><i class="bi <?= e($item['icon']) ?>"></i></div>
              <div class="flex-1">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="font-semibold text-slate-800"><?= e($item['title']) ?></div>
                  <div class="text-xs text-slate-400"><?= format_date($item['date'], true) ?></div>
                </div>
                <div class="text-sm text-slate-600 mt-1"><?= e(mb_substr((string) ($item['subtitle'] ?? ''), 0, 180)) ?></div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="card overflow-hidden">
    <table class="table-modern">
      <thead><tr><th>Data</th><th>Paciente</th><th>Profissional</th><th>Queixa Principal</th><th></th></tr></thead>
      <tbody>
        <?php if (!$pronts): ?>
          <tr><td colspan="5" class="text-center py-10 text-slate-400">Nenhum registro.</td></tr>
        <?php else: foreach ($pronts as $p): ?>
          <tr>
            <td><?= format_date($p['data_atendimento'], true) ?></td>
            <td><a href="../pacientes/ver.php?id=<?= $p['paciente_id'] ?>" class="hover:underline font-medium"><?= e($p['paciente_nome']) ?></a></td>
            <td><?= e($p['prof_nome']) ?></td>
            <td><?= e(mb_substr($p['queixa_principal'] ?? '', 0, 80)) ?></td>
            <td class="text-right whitespace-nowrap">
              <a href="ver.php?id=<?= $p['id'] ?>" class="text-sky-600 mx-1"><i class="bi bi-eye"></i></a>
              <a href="editar.php?id=<?= $p['id'] ?>" class="text-amber-600 mx-1"><i class="bi bi-pencil"></i></a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
