<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

if (get_theme() !== 'psicologia' && !is_developer()) {
    flash('warning', 'Ative o tema Psicologia para acessar as escalas clínicas.');
    redirect(APP_URL . '/dashboard.php');
}

$page_title = 'Escalas Clínicas';
$eid = active_company_id();
$pacienteFiltro = (int) input('paciente_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    try {
        $pacienteId = (int) input('paciente_id');
        $profissionalId = (int) input('profissional_id', $_SESSION['user_id']);
        clinic_company_record('pacientes', $pacienteId);
        clinic_company_record('usuarios', $profissionalId);
        $nomeEscala = trim(input('nome_escala'));
        $dataAplicacao = input('data_aplicacao') ?: date('Y-m-d');
        $scoreTotal = trim(input('score_total'));
        $classificacao = trim(input('classificacao'));
        $observacoes = trim(input('observacoes'));

        if ($pacienteId <= 0 || $profissionalId <= 0 || $nomeEscala === '') {
            throw new Exception('Paciente, profissional e nome da escala são obrigatórios.');
        }

        $stmt = db()->prepare("INSERT INTO psicologia_escalas (empresa_id, paciente_id, profissional_id, nome_escala, score_total, classificacao, data_aplicacao, observacoes, criado_em, atualizado_em) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())");
        $stmt->execute([$eid, $pacienteId, $profissionalId, $nomeEscala, $scoreTotal, $classificacao, $dataAplicacao, $observacoes]);

        log_action('create', 'psicologia_escalas', db()->lastInsertId(), 'Nova escala clínica registrada.');
        flash('success', 'Escala clínica registrada com sucesso.');
        redirect(APP_URL . '/modules/psicologia/escalas.php?paciente_id=' . $pacienteId);
    } catch (Exception $e) {
        flash('error', 'Erro: ' . $e->getMessage());
    }
}

$pacientesStmt = db()->prepare("SELECT id, nome FROM pacientes WHERE empresa_id=? AND ativo=1 ORDER BY nome ASC");
$pacientesStmt->execute([$eid]);
$pacientes = $pacientesStmt->fetchAll();

$profStmt = db()->prepare("SELECT id, nome FROM usuarios WHERE empresa_id=? AND ativo=1 AND perfil IN ('admin','profissional') ORDER BY nome ASC");
$profStmt->execute([$eid]);
$profissionais = $profStmt->fetchAll();

$where = 'WHERE pe.empresa_id = ?';
$params = [$eid];
if ($pacienteFiltro > 0) {
    $where .= ' AND pe.paciente_id = ?';
    $params[] = $pacienteFiltro;
}

$stmt = db()->prepare("SELECT pe.*, pa.nome AS paciente_nome, u.nome AS profissional_nome
    FROM psicologia_escalas pe
    JOIN pacientes pa ON pa.id = pe.paciente_id
    JOIN usuarios u ON u.id = pe.profissional_id
    $where
    ORDER BY pe.data_aplicacao DESC, pe.id DESC
    LIMIT 100");
$stmt->execute($params);
$registros = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div>
    <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-clipboard-data text-primary"></i> Escalas Clínicas</h1>
    <p class="text-sm text-slate-500">Registro estruturado de instrumentos psicológicos, scores e classificação clínica.</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="<?= APP_URL ?>/modules/psicologia/index.php" class="btn bg-slate-100 text-slate-700"><i class="bi bi-arrow-left"></i> Psicologia</a>
    <a href="<?= APP_URL ?>/modules/psicologia/planos.php" class="btn bg-fuchsia-600 text-white"><i class="bi bi-bullseye"></i> Plano Terapêutico</a>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="xl:col-span-1">
    <form method="POST" class="card p-6 space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="text-sm font-medium">Paciente *</label>
        <select data-patient-search name="paciente_id" class="form-select" required>
          <option value="">Selecione</option>
          <?php foreach ($pacientes as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= ((int) input('paciente_id', $pacienteFiltro) === (int) $p['id']) ? 'selected' : '' ?>><?= e($p['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Profissional *</label>
        <select name="profissional_id" class="form-select" required>
          <?php foreach ($profissionais as $prof): ?>
            <option value="<?= (int) $prof['id'] ?>" <?= ((int) input('profissional_id', $_SESSION['user_id']) === (int) $prof['id']) ? 'selected' : '' ?>><?= e($prof['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Nome da escala *</label>
        <input name="nome_escala" value="<?= e(input('nome_escala')) ?>" class="form-input" placeholder="Ex: BDI-II, GAD-7, PHQ-9" required>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium">Data</label>
          <input type="date" name="data_aplicacao" value="<?= e(input('data_aplicacao', date('Y-m-d'))) ?>" class="form-input">
        </div>
        <div>
          <label class="text-sm font-medium">Score</label>
          <input name="score_total" value="<?= e(input('score_total')) ?>" class="form-input" placeholder="Ex: 18 / 27">
        </div>
      </div>
      <div>
        <label class="text-sm font-medium">Classificação</label>
        <input name="classificacao" value="<?= e(input('classificacao')) ?>" class="form-input" placeholder="Ex: Moderado, Grave, Dentro da faixa esperada">
      </div>
      <div>
        <label class="text-sm font-medium">Observações</label>
        <textarea name="observacoes" rows="5" class="form-textarea" placeholder="Contexto da aplicação, impressão clínica, encaminhamentos..."><?= e(input('observacoes')) ?></textarea>
      </div>
      <div class="flex justify-end">
        <button class="btn bg-violet-600 text-white"><i class="bi bi-save"></i> Registrar Escala</button>
      </div>
    </form>
  </div>

  <div class="xl:col-span-2 space-y-6">
    <form method="GET" class="card p-4 flex flex-col md:flex-row gap-3 md:items-end">
      <div class="flex-1">
        <label class="text-xs uppercase text-slate-500">Filtrar paciente</label>
        <select data-patient-search name="paciente_id" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($pacientes as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= $pacienteFiltro === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
    </form>

    <div class="card overflow-hidden">
      <div class="px-6 py-4 border-b">
        <h2 class="font-bold">Histórico de escalas</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="table-modern">
          <thead>
            <tr><th>Data</th><th>Escala</th><th>Paciente</th><th>Profissional</th><th>Score</th><th>Classificação</th></tr>
          </thead>
          <tbody>
            <?php if (!$registros): ?>
              <tr><td colspan="6" class="text-center py-10 text-slate-400">Nenhuma escala clínica registrada.</td></tr>
            <?php else: foreach ($registros as $r): ?>
              <tr>
                <td><?= format_date($r['data_aplicacao']) ?></td>
                <td>
                  <div class="font-medium"><?= e($r['nome_escala']) ?></div>
                  <?php if (!empty($r['observacoes'])): ?><div class="text-xs text-slate-500"><?= e(mb_substr($r['observacoes'], 0, 90)) ?></div><?php endif; ?>
                </td>
                <td><a href="<?= APP_URL ?>/modules/pacientes/ver.php?id=<?= (int) $r['paciente_id'] ?>" class="hover:underline"><?= e($r['paciente_nome']) ?></a></td>
                <td><?= e($r['profissional_nome']) ?></td>
                <td class="font-semibold text-violet-700"><?= e($r['score_total']) ?></td>
                <td><?= e($r['classificacao']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>