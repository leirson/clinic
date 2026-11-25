<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
require_post_action('Enviar comunicação ao paciente');
$page_title = 'Enviar Link';

$id = (int) input('id');
$stmt = db()->prepare("SELECT t.id AS teleconsulta_id, a.id AS agendamento_id
    FROM teleconsultas t
    JOIN agendamentos a ON a.id = t.agendamento_id
    WHERE t.id = ? AND a.empresa_id = ?");
$stmt->execute([$id, active_company_id()]);
$row = $stmt->fetch();
if (!$row) {
    flash('error', 'Teleconsulta não encontrada.');
    redirect('index.php');
}

$t = appointment_fetch_full((int) $row['agendamento_id'], active_company_id());
if (!$t) {
    flash('error', 'Agendamento vinculado não encontrado.');
    redirect('index.php');
}

$enviado = false;
$erro_envio = '';
$googleCalendarUrl = appointment_google_calendar_link($t);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $envio = send_telemedicine_link_email($t);
    $enviado = $envio['ok'];
    $erro_envio = $envio['error'] ?? '';
    log_action($enviado ? 'telemedicina_email' : 'telemedicina_email_falha', 'teleconsultas', $id, $enviado ? ('Link enviado para ' . ($t['paciente_email'] ?? '')) : $erro_envio);
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold">Enviar Link da Teleconsulta</h1></div>
<div class="card p-6 space-y-4">
  <?php if ($enviado): ?>
    <div class="bg-green-50 border border-green-200 p-3 rounded text-sm text-green-800"><i class="bi bi-check-circle"></i> E-mail enviado para <?= e($t['paciente_email']) ?> com arquivo de calendário.</div>
  <?php elseif ($erro_envio): ?>
    <div class="bg-amber-50 border border-amber-200 p-3 rounded text-sm text-amber-800"><i class="bi bi-exclamation-circle"></i> Falha no envio: <?= e($erro_envio) ?></div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div>
      <h3 class="font-bold mb-2">Link do Paciente</h3>
      <div class="flex gap-2">
        <input value="<?= e($t['link_paciente']) ?>" readonly class="form-input font-mono text-xs" id="lp">
        <button onclick="navigator.clipboard.writeText(document.getElementById('lp').value);this.textContent='Copiado!'" class="btn btn-primary">Copiar</button>
      </div>
    </div>
    <div>
      <h3 class="font-bold mb-2">Google Calendar</h3>
      <a href="<?= e($googleCalendarUrl) ?>" target="_blank" class="btn bg-sky-600 text-white"><i class="bi bi-calendar-plus"></i> Adicionar ao Google Calendar</a>
    </div>
  </div>

  <div>
    <h3 class="font-bold mb-2">Link do Profissional</h3>
    <div class="flex gap-2">
      <input value="<?= e($t['link_profissional']) ?>" readonly class="form-input font-mono text-xs" id="lpr">
      <button onclick="navigator.clipboard.writeText(document.getElementById('lpr').value);this.textContent='Copiado!'" class="btn btn-primary">Copiar</button>
    </div>
  </div>

  <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
    <div><strong>Paciente:</strong> <?= e($t['paciente_nome']) ?></div>
    <div><strong>Data/Hora:</strong> <?= format_date($t['data_hora_inicio'], true) ?></div>
    <div><strong>Profissional:</strong> <?= e($t['prof_nome']) ?></div>
  </div>

  <div class="flex gap-2 flex-wrap pt-3 border-t">
    <?php if (!empty($t['paciente_email'])): ?>
      <form method="POST"><?= csrf_field() ?><button class="btn bg-blue-600 text-white"><i class="bi bi-envelope"></i> Enviar por E-mail</button></form>
    <?php endif; ?>
    <?php if (!empty($t['paciente_celular'])):
      $msg = 'Olá ' . explode(' ', (string) $t['paciente_nome'])[0] . '! Sua teleconsulta está agendada para ' . format_date($t['data_hora_inicio'], true) . '. Acesse no horário: ' . $t['link_paciente'];
    ?>
      <a href="<?= wa_link($t['paciente_celular'], $msg) ?>" target="_blank" class="btn bg-green-600 text-white"><i class="bi bi-whatsapp"></i> Enviar por WhatsApp</a>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
