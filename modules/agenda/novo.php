<?php
$page_title = 'Nova Consulta';
require_once __DIR__ . '/../../includes/header.php';

$eid = active_company_id();
$pacienteIdInicial = (int) input('paciente_id');
$dataInicial = input('data', date('Y-m-d'));
$hasFila = db_table_exists('agenda_fila_espera');
$hasConfirmacao = db_column_exists('agendamentos', 'confirmacao_status');
$form = [
    'paciente_id' => $pacienteIdInicial,
    'profissional_id' => (int) ($_SESSION['user_id'] ?? 0),
    'procedimento_id' => 0,
    'status' => 'agendado',
    'confirmacao_status' => 'pendente',
    'data' => $dataInicial,
    'hora' => '09:00',
    'duracao' => 30,
    'valor' => '0,00',
    'observacoes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $form = array_merge($form, [
        'paciente_id' => (int) input('paciente_id'),
        'profissional_id' => (int) input('profissional_id'),
        'procedimento_id' => (int) input('procedimento_id'),
        'status' => input('status', 'agendado'),
        'confirmacao_status' => input('confirmacao_status', 'pendente'),
        'data' => input('data'),
        'hora' => input('hora'),
        'duracao' => (int) input('duracao', 30),
        'valor' => input('valor', '0,00'),
        'observacoes' => input('observacoes'),
    ]);

    try {
        $pid = (int) $form['paciente_id'];
        $profId = (int) $form['profissional_id'];
        clinic_company_record('pacientes', $pid);
        clinic_company_record('usuarios', $profId);
        clinic_company_record('procedimentos', (int) $form['procedimento_id'], true);
        $procId = (int) $form['procedimento_id'] ?: null;
        $data = trim((string) $form['data']);
        $hora = trim((string) $form['hora']);
        $dur = max(5, (int) $form['duracao']);
        $valor = parse_money_br($form['valor']);
        $obs = trim((string) $form['observacoes']);
        $status = in_array($form['status'], ['agendado','confirmado'], true) ? $form['status'] : 'agendado';
        $confirmacaoStatus = $hasConfirmacao ? input('confirmacao_status', 'pendente') : null;

        if (!$pid || !$profId || !$data || !$hora) {
            throw new Exception('Preencha todos os campos obrigatórios.');
        }

        $ini = $data . ' ' . $hora . ':00';
        $fim = date('Y-m-d H:i:s', strtotime($ini) + ($dur * 60));

        db()->beginTransaction();
        $lock = db()->prepare('SELECT id FROM usuarios WHERE id=? AND empresa_id=? FOR UPDATE');
        $lock->execute([$profId, $eid]);

        $bloqueio = db()->prepare("SELECT COUNT(*) FROM bloqueios_horario WHERE empresa_id=? AND (profissional_id=? OR profissional_id IS NULL) AND data_inicio < ? AND data_fim > ?");
        $bloqueio->execute([$eid, $profId, $fim, $ini]);
        if ((int) $bloqueio->fetchColumn() > 0) {
            throw new Exception('Horário bloqueado para este profissional.');
        }

        $conflito = db()->prepare("SELECT COUNT(*) FROM agendamentos WHERE empresa_id=? AND profissional_id=? AND status NOT IN ('cancelado','faltou') AND data_hora_inicio < ? AND data_hora_fim > ?");
        $conflito->execute([$eid, $profId, $fim, $ini]);
        if ((int) $conflito->fetchColumn() > 0) {
            throw new Exception('Já existe consulta neste horário para o profissional. Use a fila de encaixe para reaproveitar cancelamentos.');
        }

        if ($hasConfirmacao) {
            $stmt = db()->prepare("INSERT INTO agendamentos (empresa_id,paciente_id,profissional_id,procedimento_id,data_hora_inicio,data_hora_fim,status,observacoes,valor,criado_por,confirmacao_status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$eid, $pid, $profId, $procId, $ini, $fim, $status, $obs, $valor, $_SESSION['user_id'], $status === 'confirmado' ? 'confirmado' : $confirmacaoStatus]);
        } else {
            $stmt = db()->prepare("INSERT INTO agendamentos (empresa_id,paciente_id,profissional_id,procedimento_id,data_hora_inicio,data_hora_fim,status,observacoes,valor,criado_por)
                VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$eid, $pid, $profId, $procId, $ini, $fim, $status, $obs, $valor, $_SESSION['user_id']]);
        }
        $id = (int) db()->lastInsertId();
        log_action('agenda_criar', 'agendamentos', $id, 'Consulta criada pela agenda inteligente.');
        flash('success', 'Consulta agendada com sucesso.');
        db()->commit();
        redirect('ver.php?id=' . $id);
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash('error', 'Erro: ' . $e->getMessage());
    }
}

$pacientes = db()->prepare("SELECT id, nome FROM pacientes WHERE empresa_id=? AND ativo=1 ORDER BY nome");
$pacientes->execute([$eid]);
$pacientes = $pacientes->fetchAll() ?: [];

$profs = db()->prepare("SELECT id, nome FROM usuarios WHERE empresa_id=? AND ativo=1 AND perfil IN ('admin','profissional') ORDER BY nome");
$profs->execute([$eid]);
$profs = $profs->fetchAll() ?: [];

$procs = db()->prepare("SELECT id, nome, valor, duracao_minutos FROM procedimentos WHERE empresa_id=? AND ativo=1 ORDER BY nome");
$procs->execute([$eid]);
$procs = $procs->fetchAll() ?: [];
?>
<div class="space-y-5">
  <div class="flex flex-wrap justify-between items-center gap-3">
    <div>
      <a href="index.php" class="text-sm text-slate-500 hover:text-primary"><i class="bi bi-arrow-left"></i> Voltar</a>
      <h1 class="text-2xl font-bold text-slate-800">Nova Consulta</h1>
      <p class="text-sm text-slate-500">Agende o atendimento e já deixe o fluxo de confirmação preparado.</p>
    </div>
    <?php if ($hasFila): ?>
      <a href="fila.php<?= $pacienteIdInicial ? '?paciente_id=' . $pacienteIdInicial : '' ?>" class="btn bg-violet-600 text-white"><i class="bi bi-hourglass-split"></i> Fila de encaixe</a>
    <?php endif; ?>
  </div>

  <form method="POST" class="card p-6 space-y-4">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium">Paciente *</label>
        <select data-patient-search name="paciente_id" required class="form-select">
          <option value="">Selecione...</option>
          <?php foreach ($pacientes as $paciente): ?>
            <option value="<?= (int) $paciente['id'] ?>" <?= (int) $form['paciente_id'] === (int) $paciente['id'] ? 'selected' : '' ?>><?= e($paciente['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Profissional *</label>
        <select name="profissional_id" required class="form-select">
          <option value="">Selecione...</option>
          <?php foreach ($profs as $prof): ?>
            <option value="<?= (int) $prof['id'] ?>" <?= (int) $form['profissional_id'] === (int) $prof['id'] ? 'selected' : '' ?>><?= e($prof['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Procedimento</label>
        <select name="procedimento_id" id="procedimento" class="form-select">
          <option value="">--</option>
          <?php foreach ($procs as $proc): ?>
            <option value="<?= (int) $proc['id'] ?>" data-valor="<?= e((string) $proc['valor']) ?>" data-duracao="<?= (int) $proc['duracao_minutos'] ?>" <?= (int) $form['procedimento_id'] === (int) $proc['id'] ? 'selected' : '' ?>><?= e($proc['nome']) ?> — <?= money($proc['valor']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Status inicial</label>
        <select name="status" class="form-select">
          <option value="agendado" <?= $form['status'] === 'agendado' ? 'selected' : '' ?>>Agendado</option>
          <option value="confirmado" <?= $form['status'] === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
        </select>
      </div>
      <?php if ($hasConfirmacao): ?>
        <div>
          <label class="text-sm font-medium">Confirmação</label>
          <select name="confirmacao_status" class="form-select">
            <?php foreach (['pendente','confirmado','recusado','reagendar'] as $status): ?>
              <option value="<?= $status ?>" <?= $form['confirmacao_status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div>
        <label class="text-sm font-medium">Data *</label>
        <input type="date" name="data" required value="<?= e($form['data']) ?>" class="form-input">
      </div>
      <div>
        <label class="text-sm font-medium">Hora *</label>
        <input type="time" name="hora" required class="form-input" value="<?= e($form['hora']) ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Duração (min)</label>
        <input type="number" name="duracao" id="duracao" value="<?= (int) $form['duracao'] ?>" class="form-input" min="5">
      </div>
      <div>
        <label class="text-sm font-medium">Valor (R$)</label>
        <input type="text" name="valor" id="valor" value="<?= e($form['valor']) ?>" class="form-input">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Observações</label>
        <textarea name="observacoes" rows="3" class="form-textarea"><?= e($form['observacoes']) ?></textarea>
      </div>
    </div>

    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
      <div class="font-semibold mb-1"><i class="bi bi-info-circle"></i> Fluxo inteligente</div>
      <p>Se o paciente ainda não confirmou, mantenha a confirmação como <strong>pendente</strong>. Após um cancelamento, o mesmo horário poderá ser reaproveitado usando a <strong>fila de encaixe</strong>.</p>
    </div>

    <div class="flex justify-end gap-2 pt-3 border-t">
      <a href="index.php" class="btn bg-slate-200 text-slate-700">Cancelar</a>
      <button class="btn btn-primary"><i class="bi bi-check"></i> Agendar</button>
    </div>
  </form>
</div>

<script>
document.getElementById('procedimento').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const valor = opt.dataset.valor;
  const duracao = opt.dataset.duracao;
  if (valor) {
    document.getElementById('valor').value = parseFloat(valor).toFixed(2).replace('.', ',');
  }
  if (duracao) {
    document.getElementById('duracao').value = duracao;
  }
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
