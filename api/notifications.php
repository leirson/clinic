<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    $items = consume_due_system_notifications($_SESSION['user_id'] ?? 0, active_company_id(), 10);
    echo json_encode([
        'ok' => true,
        'items' => $items,
        'count' => count($items),
        'generated_at' => date('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'items' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
