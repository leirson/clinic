<?php
$page_title = 'Detalhes da Consulta';
require_once __DIR__ . '/../../includes/header.php';

$id = (int) input('id');
$eid = active_company_id();
$hasFila = db_table_exists('agenda_fila_espera');
$hasConfirmacao = db_column_exists('agendamentos', 'confirmacao_status');
$hasUltimoLembrete = db_column_exists('agendamentos', 'ultimo_lembrete_em');
$hasCancelado = db_column_exists('agendamentos', 'cancelado_em');

$a = appointment_fetch_full($id, $eid);
if (!$a) {
    flash('error', 'Consulta não encontrada.');
    redirect('index.php');
}

audit_entity_access('acesso_agendamento', 'agendamentos', $id, 'Visualização do agendamento do paciente #' . (int) $a['paciente_id'] . '.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $acao = input('acao', 'status');

    try {
        if ($acao === 'status') {
            $status = input('status');
            $confirmacaoStatus = $hasConfirmacao ? input('confirmacao_status', $a['confirmacao_status'] ?? 'pendente') : null;
            $motivoCancelamento = trim((string) input('cancelado_motivo'));
            if ($hasConfirmacao && $status === 'confirmado') {
                $confirmacaoStatus = 'confirmado';
            }
            if ($hasConfirmacao && in_array($status, ['cancelado', 'faltou'], true) && $confirmacaoStatus === 'confirmado') {
                $confirmacaoStatus = 'pendente';
            }

            if ($hasConfirmacao && $hasCancelado) {
                db()->prepare("UPDATE agendamentos SET status=?, confirmacao_status=?, confirmado_em=CASE WHEN ?='confirmado' THEN NOW() ELSE confirmado_em END, cancelado_em=CASE WHEN ?='cancelado' THEN NOW() ELSE cancelado_em END, cancelado_motivo=? WHERE id=? AND empresa_id=?")
                    ->execute([$status, $confirmacaoStatus, $confirmacaoStatus, $status, $motivoCancelamento ?: null, $id, $eid]);
            } elseif ($hasConfirmacao) {
                db()->prepare("UPDATE agendamentos SET status=?, confirmacao_status=?, confirmado_em=CASE WHEN ?='confirmado' THEN NOW() ELSE confirmado_em END WHERE id=? AND empresa_id=?")
                    ->execute([$status, $confirmacaoStatus, $confirmacaoStatus, $id, $eid]);
            } else {
                db()->prepare("UPDATE agendamentos SET status=? WHERE id=? AND empresa_id=?")
                    ->execute([$status, $id, $eid]);
            }
            log_action('agenda_status', 'agendamentos', $id, 'Status da consulta atualizado para ' . $status . '.');
            flash('success', 'Status atualizado com sucesso.');
            redirect('ver.php?id=' . $id);
        }

        if ($acao === 'registrar_lembrete') {
            if ($hasUltimoLembrete) {
                db()->prepare("UPDATE agendamentos SET ultimo_lembrete_em = NOW() WHERE id=? AND empresa_id=?")
                    ->execute([$id, $eid]);
            }
            log_action('agenda_lembrete_manual', 'agendamentos', $id, 'Lembrete manual registrado.');
            flash('success', 'Lembrete manual registrado.');
            redirect('ver.php?id=' . $id);
        }

        if ($acao === 'encaixar_fila') {
            $agendamentoId = agenda_waitlist_promote((int) input('fila_id'), [
                'profissional_id' => (int) $a['profissional_id'],
                'procedimento_id' => (int) ($a['procedimento_id'] ?? 0),
                'data_hora_inicio' => $a['data_hora_inicio'],
                'data_hora_fim' => $a['data_hora_fim'],
                'valor' => (float) ($a['valor'] ?? 0),
                'observacoes' => 'Encaixe gerado para reaproveitar cancelamento da consulta #' . $id,
            ], $_SESSION['user_id']);
            log_action('agenda_reaproveitar_cancelamento', 'agendamentos', $id, 'Horário reaproveitado com novo encaixe #' . $agendamentoId . '.');
            flash('success', 'Novo encaixe criado com sucesso.');
            redirect('ver.php?id=' . $agendamentoId);
        }
    } catch (Throwable $e) {
        flash('error', 'Erro: ' . $e->getMessage());
        redirect('ver.php?id=' . $id);
    }
}

$a = appointment_fetch_full($id, $eid);
$msgLembrete = sprintf(
    "Olá %s! 👋 Lembrete da sua consulta no dia %s às %s com %s. Por favor, confirme sua presença.",
    explode(' ', (string) $a['paciente_nome'])[0],
    date('d/m/Y', strtotime((string) $a['data_hora_inicio'])),
    date('H:i', strtotime((string) $a['data_hora_inicio'])),
    $a['prof_nome']
);
$googleCalendarUrl = appointment_google_calendar_link($a);
$modalidade = ucfirst((string) ($a['modalidade'] ?? 'presencial'));
$duracaoMin = round((strtotime((string) $a['data_hora_fim']) - strtotime((string) $a['data_hora_inicio'])) / 60);
$filaSugestoes = ($hasFila && ($a['status'] ?? '') === 'cancelado')
    ? agenda_waitlist_find_matches($eid, (int) $a['profissional_id'], $a['data_hora_inicio'], $a['data_hora_fim'], 5)
    : [];
?>
<div class="space-y-5">
  <div class="flex justify-between flex-wrap gap-2">
    <div>
      <a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a>
      <h1 class="text-2xl font-bold text-slate-800">Consulta #<?= $id ?></h1>
      <p class="text-sm text-slate-500">Gerencie confirmação, lembretes e reaproveitamento de cancelamento.</p>
    </div>
    <div class="flex gap-2 flex-wrap">
      <?php if ($hasFila): ?>
        <a href="fila.php?origem_agendamento_id=<?= $id ?>&profissional_id=<?= (int) $a['profissional_id'] ?>&procedimento_id=<?= (int) ($a['procedimento_id'] ?? 0) ?>&slot_start=<?= urlencode((string) $a['data_hora_inicio']) ?>&slot_end=<?= urlencode((string) $a['data_hora_fim']) ?>&valor=<?= urlencode((string) ($a['valor'] ?? 0)) ?>" class="btn bg-violet-600 text-white"><i class="bi bi-hourglass-split"></i> Abrir fila</a>
      <?php endif; ?>
      <a href="editar.php?id=<?= $id ?>" class="btn bg-amber-500 text-white"><i class="bi bi-pencil"></i> Editar</a>
      <a href="../prontuario/novo.php?paciente_id=<?= (int) $a['paciente_id'] ?>&agendamento_id=<?= $id ?>" class="btn bg-purple-600 text-white"><i class="bi bi-journal-plus"></i> Prontuário</a>
      <a href="../nfse/novo.php?paciente_id=<?= (int) $a['paciente_id'] ?>&agendamento_id=<?= $id ?>" class="btn bg-green-600 text-white"><i class="bi bi-receipt"></i> Emitir NFSe</a>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 card p-5 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
        <div><span class="text-slate-500">Paciente:</span> <a href="../pacientes/ver.php?id=<?= (int) $a['paciente_id'] ?>" class="text-primary hover:underline font-medium"><?= e($a['paciente_nome']) ?></a></div>
        <div><span class="text-slate-500">Profissional:</span> <?= e($a['prof_nome']) ?></div>
        <div><span class="text-slate-500">Data/Hora:</span> <?= format_date($a['data_hora_inicio'], true) ?></div>
        <div><span class="text-slate-500">Duração:</span> <?= $duracaoMin ?> min</div>
        <div><span class="text-slate-500">Procedimento:</span> <?= e($a['proc_nome']) ?></div>
        <div><span class="text-slate-500">Valor:</span> <?= money($a['valor']) ?></div>
        <div><span class="text-slate-500">Modalidade:</span> <span class="badge <?= ($a['modalidade'] ?? 'presencial') === 'telemedicina' ? 'badge-info' : 'badge-secondary' ?>"><?= e($modalidade) ?></span></div>
        <div><span class="text-slate-500">Status:</span> <span class="badge <?= status_badge_class($a['status']) ?>"><?= e($a['status']) ?></span></div>
        <?php if ($hasConfirmacao): ?>
          <div><span class="text-slate-500">Confirmação:</span> <span class="badge <?= status_badge_class($a['confirmacao_status'] ?? 'pendente') ?>"><?= e($a['confirmacao_status'] ?? 'pendente') ?></span></div>
          <div><span class="text-slate-500">Confirmado em:</span> <?= !empty($a['confirmado_em']) ? format_date($a['confirmado_em'], true) : '—' ?></div>
        <?php endif; ?>
        <div class="md:col-span-2"><span class="text-slate-500">Observações:</span><br><?= nl2br(e($a['observacoes'])) ?></div>
        <?php if (!empty($a['cancelado_motivo'])): ?>
          <div class="md:col-span-2 rounded-xl border border-rose-200 bg-rose-50 p-3 text-rose-900">
            <strong>Motivo do cancelamento:</strong> <?= e($a['cancelado_motivo']) ?>
          </div>
        <?php endif; ?>
      </div>

      <form method="POST" class="pt-3 border-t space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="status">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
          <div>
            <label class="text-sm font-medium">Status</label>
            <select name="status" class="form-select">
              <?php foreach (['agendado','confirmado','realizado','faltou','cancelado'] as $status): ?>
                <option value="<?= $status ?>" <?= ($a['status'] ?? '') === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($hasConfirmacao): ?>
            <div>
              <label class="text-sm font-medium">Confirmação</label>
              <select name="confirmacao_status" class="form-select">
                <?php foreach (['pendente','confirmado','recusado','reagendar'] as $status): ?>
                  <option value="<?= $status ?>" <?= ($a['confirmacao_status'] ?? 'pendente') === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
          <div>
            <label class="text-sm font-medium">Motivo do cancelamento</label>
            <input type="text" name="cancelado_motivo" value="<?= e($a['cancelado_motivo'] ?? '') ?>" class="form-input" placeholder="Opcional">
          </div>
        </div>
        <div class="flex justify-end">
          <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Atualizar status</button>
        </div>
      </form>
    </div>

    <div class="space-y-4">
      <div class="card p-5 space-y-3">
        <h3 class="font-bold text-slate-700"><i class="bi bi-bell"></i> Lembretes e confirmação</h3>
        <a href="<?= e($googleCalendarUrl) ?>" target="_blank" class="btn bg-sky-600 text-white w-full"><i class="bi bi-calendar-plus"></i> Google Calendar</a>
        <?php if (!empty($a['paciente_celular'])): ?>
          <a target="_blank" href="<?= wa_link($a['paciente_celular'], $msgLembrete) ?>" class="btn bg-green-600 text-white w-full"><i class="bi bi-whatsapp"></i> Abrir WhatsApp</a>
        <?php endif; ?>
        <?php if (!empty($a['paciente_email'])): ?>
          <a href="enviar_email.php?id=<?= $id ?>" class="btn bg-blue-600 text-white w-full"><i class="bi bi-envelope"></i> Enviar e-mail</a>
        <?php endif; ?>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="acao" value="registrar_lembrete">
          <button class="btn bg-slate-100 text-slate-700 w-full"><i class="bi bi-check2-square"></i> Registrar lembrete manual</button>
        </form>
        <div class="text-xs text-slate-500">
          Último lembrete: <?= !empty($a['ultimo_lembrete_em']) ? format_date($a['ultimo_lembrete_em'], true) : 'não registrado' ?>
        </div>
      </div>

      <?php if (($a['modalidade'] ?? 'presencial') === 'telemedicina' && !empty($a['link_paciente'])): ?>
        <div class="card p-5 space-y-3 border-sky-200 bg-sky-50">
          <h3 class="font-bold text-sky-900"><i class="bi bi-camera-video"></i> Telemedicina</h3>
          <div class="text-sm text-sky-900">Sala pronta para envio ao paciente e importação no calendário.</div>
          <div class="flex flex-wrap gap-2">
            <a href="../telemedicina/enviar_link.php?id=<?= (int) ($a['teleconsulta_id'] ?? 0) ?>" class="btn bg-blue-600 text-white"><i class="bi bi-send"></i> Enviar link</a>
            <a href="<?= e($a['link_profissional']) ?>" target="_blank" class="btn bg-emerald-600 text-white"><i class="bi bi-camera-video-fill"></i> Entrar na sala</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($hasFila && ($a['status'] ?? '') === 'cancelado'): ?>
        <div class="card p-5 space-y-3 border-violet-200 bg-violet-50">
          <h3 class="font-bold text-violet-900"><i class="bi bi-hourglass-split"></i> Reaproveitamento do cancelamento</h3>
          <?php if (!$filaSugestoes): ?>
            <p class="text-sm text-violet-900">Nenhum paciente compatível encontrado na fila de encaixe para este horário.</p>
            <a href="fila.php?origem_agendamento_id=<?= $id ?>&profissional_id=<?= (int) $a['profissional_id'] ?>&procedimento_id=<?= (int) ($a['procedimento_id'] ?? 0) ?>&slot_start=<?= urlencode((string) $a['data_hora_inicio']) ?>&slot_end=<?= urlencode((string) $a['data_hora_fim']) ?>&valor=<?= urlencode((string) ($a['valor'] ?? 0)) ?>" class="btn bg-violet-600 text-white w-full">Abrir fila</a>
          <?php else: ?>
            <?php foreach ($filaSugestoes as $fila): ?>
              <div class="rounded-xl border border-violet-200 bg-white p-3">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="font-semibold text-slate-800"><?= e($fila['paciente_nome']) ?></div>
                    <div class="text-xs text-slate-500">Prioridade: <?= e($fila['prioridade']) ?> · <?= !empty($fila['celular']) ? format_phone($fila['celular']) : 'sem celular' ?></div>
                    <?php if (!empty($fila['observacoes'])): ?><div class="text-xs text-slate-500 mt-1"><?= e($fila['observacoes']) ?></div><?php endif; ?>
                  </div>
                  <form method="POST" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="acao" value="encaixar_fila">
                    <input type="hidden" name="fila_id" value="<?= (int) $fila['id'] ?>">
                    <button class="btn bg-emerald-600 text-white" data-confirm="Criar novo encaixe para este paciente?"><i class="bi bi-lightning-charge"></i> Encaixar</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
            <a href="fila.php?origem_agendamento_id=<?= $id ?>&profissional_id=<?= (int) $a['profissional_id'] ?>&procedimento_id=<?= (int) ($a['procedimento_id'] ?? 0) ?>&slot_start=<?= urlencode((string) $a['data_hora_inicio']) ?>&slot_end=<?= urlencode((string) $a['data_hora_fim']) ?>&valor=<?= urlencode((string) ($a['valor'] ?? 0)) ?>" class="text-sm text-violet-800 hover:underline">Ver fila completa</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
