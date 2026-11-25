<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(json_encode(['ok'=>false])); }
csrf_validate();
$id = (int) input('id');
$stmt = db()->prepare('SELECT t.id FROM teleconsultas t JOIN agendamentos a ON a.id=t.agendamento_id WHERE t.id=? AND a.empresa_id=?');
$stmt->execute([$id, active_company_id()]);
if (!$stmt->fetch()) { http_response_code(404); exit(json_encode(['ok'=>false])); }
db()->prepare('UPDATE teleconsultas SET encerrada_em=NOW() WHERE id=?')->execute([$id]);
echo json_encode(['ok'=>true]);
