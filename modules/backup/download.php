<?php
require_once __DIR__ . '/../../includes/functions.php';
require_developer();

$id = (int)input('id');
$b = db()->prepare("SELECT * FROM backups WHERE id=?");
$b->execute([$id]);
$b = $b->fetch();
if (!$b) die('Backup não encontrado');

$root = realpath(APP_PATH . '/storage/backups');
$file = $root ? realpath($root . '/' . basename($b['arquivo'])) : false;
if (!$file || strpos($file, $root . DIRECTORY_SEPARATOR) !== 0) { http_response_code(404); exit('Arquivo não encontrado.'); }
if (!file_exists($file)) die('Arquivo inexistente');

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
