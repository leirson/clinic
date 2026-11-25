<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json');
$q = trim(input('q'));
if (mb_strlen($q) < 2) { echo json_encode([]); exit; }

$stmt = db()->prepare("SELECT id, nome, apresentacao, principio_ativo, tipo FROM medicamentos WHERE empresa_id=? AND (nome LIKE ? OR principio_ativo LIKE ?) LIMIT 20");
$stmt->execute([active_company_id(), '%'.$q.'%', '%'.$q.'%']);
echo json_encode($stmt->fetchAll());
