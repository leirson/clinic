<?php
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(json_encode(['ok'=>false])); }
csrf_validate();
$id = (int) input('id');
$sala = (string) input('sala');
if ($sala === '') { http_response_code(403); exit(json_encode(['ok'=>false])); }
$stmt = db()->prepare('SELECT id FROM teleconsultas WHERE id=? AND sala_id=?');
$stmt->execute([$id, $sala]);
if (!$stmt->fetch()) { http_response_code(404); exit(json_encode(['ok'=>false])); }
db()->prepare('UPDATE teleconsultas SET consentimento_paciente=1 WHERE id=? AND sala_id=?')->execute([$id,$sala]);
echo json_encode(['ok'=>true]);
