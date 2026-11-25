<?php
$page_title = 'Editar Consulta';
require_once __DIR__ . '/../../includes/header.php';

$eid = active_company_id();
$id = (int) input('id');
$hasConfirmacao = db_column_exists('agendamentos', 'confirmacao_status');
$hasCancelado = db_column_exists('agendamentos', 'cancelado_motivo');

$stmt = db()->prepare("SELECT * FROM agendamentos WHERE id=? AND empresa_id=? LIMIT 1");
$stmt->execute([$id, $eid]);
$a = $stmt->fetch();
if (!$a) {
    flash('error', 'Consulta não encontrada.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    try {
        $pacienteId = (int) input('paciente_id');
        $profId = (int) input('profissional_id');
        $procId = (int) input('procedimento_id') ?: null;
        $dur = max(5, (int) input('duracao', 30));
        $ini = input('data') . ' ' . input('hora') . ':00';
        $fim = date('Y-m-d H:i:s', strtotime($ini) + ($dur * 60));
        $status = input('status', 'agendado');
        $valor = parse_money_br(input('valor', '0'));
        $obs = trim((string) input('observacoes'));
        $confirmacaoStatus = $hasConfirmacao ? input('confirmacao_status', $a['confirmacao_status'] ?? 'pendente') : null;
        $motivoCancelamento = $hasCancelado ? trim((string) input('cancelado_motivo')) : null;

        clinic_company_record('pacientes', $pacienteId);
        clinic_company_record('usuarios', $profId);
        clinic_company_record('procedimentos', (int) $procId, true);
        db()->beginTransaction();
        $locks = [(int) $a['profissional_id'], $profId]; sort($locks);
        $lock = db()->prepare('SELECT id FROM usuarios WHERE empresa_id=? AND id IN (?,?) ORDER BY id FOR UPDATE');
        $lock->execute([$eid, $locks[0], $locks[1]]);
        $bloqueio = db()->prepare('SELECT COUNT(*) FROM bloqueios_horario WHERE empresa_id=? AND (profissional_id=? OR profissional_id IS NULL) AND data_inicio<? AND data_fim>?');
        $bloqueio->execute([$eid, $profId, $fim, $ini]);
        if ((int) $bloqueio->fetchColumn()>0) throw new Exception('Horário bloqueado para este profissional.');

        $conflito = db()->prepare("SELECT COUNT(*) FROM agendamentos WHERE empresa_id=? AND profissional_id=? AND id<>? AND status NOT IN ('cancelado','faltou') AND data_hora_inicio < ? AND data_hora_fim > ?");
        $conflito->execute([$eid, $profId, $id, $fim, $ini]);
        if ((int) $conflito->fetchColumn() > 0) {
            throw new Exception('Já existe outra consulta nesse horário para o profissional.');
        }

        if ($hasConfirmacao && $hasCancelado) {
            db()->prepare("UPDATE agendamentos SET paciente_id=?, profissional_id=?, procedimento_id=?, data_hora_inicio=?, data_hora_fim=?, status=?, observacoes=?, valor=?, confirmacao_status=?, cancelado_motivo=? WHERE id=? AND empresa_id=?")
                ->execute([$pacienteId, $profId, $procId, $ini, $fim, $status, $obs, $valor, $confirmacaoStatus, $motivoCancelamento ?: null, $id, $eid]);
        } elseif ($hasConfirmacao) {
            db()->prepare("UPDATE agendamentos SET paciente_id=?, profissional_id=?, procedimento_id=?, data_hora_inicio=?, data_hora_fim=?, status=?, observacoes=?, valor=?, confirmacao_status=? WHERE id=? AND empresa_id=?")
                ->execute([$pacienteId, $profId, $procId, $ini, $fim, $status, $obs, $valor, $confirmacaoStatus, $id, $eid]);
        } else {
            db()->prepare("UPDATE agendamentos SET paciente_id=?, profissional_id=?, procedimento_id=?, data_hora_inicio=?, data_hora_fim=?, status=?, observacoes=?, valor=? WHERE id=? AND empresa_id=?")
                ->execute([$pacienteId, $profId, $procId, $ini, $fim, $status, $obs, $valor, $id, $eid]);
        }

        log_action('agenda_editar', 'agendamentos', $id, 'Consulta atualizada.');
        flash('success', 'Consulta atualizada com sucesso.');
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
<div class="mb-5">
  <a href="ver.php?id=<?= $id ?>" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a>
  <h1 class="text-2xl font-bold text-slate-800">Editar Consulta</h1>
</div>

<form method="POST" class="card p-6 space-y-4">
  <?= csrf_field() ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="text-sm">Paciente</label>
      <select data-patient-search name="paciente_id" class="form-select">
        <?php foreach ($pacientes as $paciente): ?>
          <option value="<?= (int) $paciente['id'] ?>" <?= (int) $a['paciente_id'] === (int) $paciente['id'] ? 'selected' : '' ?>><?= e($paciente['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm">Profissional</label>
      <select name="profissional_id" class="form-select">
        <?php foreach ($profs as $prof): ?>
          <option value="<?= (int) $prof['id'] ?>" <?= (int) $a['profissional_id'] === (int) $prof['id'] ? 'selected' : '' ?>><?= e($prof['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm">Procedimento</label>
      <select name="procedimento_id" id="procedimento" class="form-select">
        <option value="">--</option>
        <?php foreach ($procs as $proc): ?>
          <option value="<?= (int) $proc['id'] ?>" data-valor="<?= e((string) $proc['valor']) ?>" data-duracao="<?= (int) $proc['duracao_minutos'] ?>" <?= (int) ($a['procedimento_id'] ?? 0) === (int) $proc['id'] ? 'selected' : '' ?>><?= e($proc['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm">Status</label>
      <select name="status" class="form-select">
        <?php foreach (['agendado','confirmado','realizado','faltou','cancelado'] as $status): ?>
          <option value="<?= $status ?>" <?= ($a['status'] ?? '') === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($hasConfirmacao): ?>
      <div>
        <label class="text-sm">Confirmação</label>
        <select name="confirmacao_status" class="form-select">
          <?php foreach (['pendente','confirmado','recusado','reagendar'] as $status): ?>
            <option value="<?= $status ?>" <?= ($a['confirmacao_status'] ?? 'pendente') === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>
    <div>
      <label class="text-sm">Data</label>
      <input type="date" name="data" value="<?= date('Y-m-d', strtotime((string) $a['data_hora_inicio'])) ?>" class="form-input">
    </div>
    <div>
      <label class="text-sm">Hora</label>
      <input type="time" name="hora" value="<?= date('H:i', strtotime((string) $a['data_hora_inicio'])) ?>" class="form-input">
    </div>
    <div>
      <label class="text-sm">Duração (min)</label>
      <input type="number" name="duracao" id="duracao" value="<?= round((strtotime((string) $a['data_hora_fim']) - strtotime((string) $a['data_hora_inicio'])) / 60) ?>" class="form-input" min="5">
    </div>
    <div>
      <label class="text-sm">Valor</label>
      <input type="text" name="valor" id="valor" value="<?= number_format((float) $a['valor'], 2, ',', '.') ?>" class="form-input">
    </div>
    <?php if ($hasCancelado): ?>
      <div class="md:col-span-2">
        <label class="text-sm">Motivo do cancelamento</label>
        <input type="text" name="cancelado_motivo" value="<?= e($a['cancelado_motivo'] ?? '') ?>" class="form-input" placeholder="Preencha quando o status for cancelado">
      </div>
    <?php endif; ?>
    <div class="md:col-span-2">
      <label class="text-sm">Observações</label>
      <textarea name="observacoes" rows="3" class="form-textarea"><?= e($a['observacoes']) ?></textarea>
    </div>
  </div>
  <div class="flex gap-2 justify-end pt-3 border-t">
    <a href="ver.php?id=<?= $id ?>" class="btn bg-slate-200 text-slate-700">Cancelar</a>
    <button class="btn btn-primary">Salvar</button>
  </div>
</form>

<script>
document.getElementById('procedimento').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  if (opt.dataset.valor) document.getElementById('valor').value = parseFloat(opt.dataset.valor).toFixed(2).replace('.', ',');
  if (opt.dataset.duracao) document.getElementById('duracao').value = opt.dataset.duracao;
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
