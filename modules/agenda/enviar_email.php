<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
require_post_action('Enviar comunicação ao paciente');

$id = (int) input('id');
$appointment = appointment_fetch_full($id, active_company_id());
if (!$appointment || empty($appointment['paciente_email'])) {
    flash('error', 'Paciente sem e-mail ou consulta não encontrada.');
    back();
}

$envio = send_appointment_reminder_email($appointment);

if ($envio['ok']) {
    db()->prepare("UPDATE agendamentos SET lembrete_email_enviado = 1 WHERE id = ?")->execute([$id]);
    log_action('lembrete_email', 'agendamentos', $id, 'Lembrete enviado para ' . $appointment['paciente_email']);
    flash('success', 'E-mail enviado para ' . $appointment['paciente_email'] . ' com anexo de calendário.');
} else {
    log_action('lembrete_email_falha', 'agendamentos', $id, (string) ($envio['error'] ?? 'Falha ao enviar lembrete por e-mail.'));
    flash('warning', 'Não foi possível enviar o e-mail: ' . ($envio['error'] ?? 'erro desconhecido'));
}

redirect('ver.php?id=' . $id);
