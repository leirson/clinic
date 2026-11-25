<?php
require_once __DIR__ . '/../includes/functions.php';

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'cgi-fcgi') {
    header('Content-Type: text/plain; charset=utf-8');
}

try {
    $stats = cron_dispatch_due_reminders();
    $message = sprintf(
        "Lembretes processados com sucesso. Empresas: %d | E-mails enviados: %d | Falhas: %d | Push em fila: %d\n",
        (int) ($stats['checked_companies'] ?? 0),
        (int) ($stats['emails_sent'] ?? 0),
        (int) ($stats['emails_failed'] ?? 0),
        (int) ($stats['push_queued'] ?? 0)
    );
    echo $message;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Erro ao processar lembretes: ' . $e->getMessage() . "\n";
}
