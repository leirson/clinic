<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$tipo = (string) input('tipo');
$id = (int) input('id');
if ($tipo === 'anexo') {
    $stmt = db()->prepare('SELECT a.arquivo, a.titulo AS nome_original FROM anexos a JOIN pacientes p ON p.id=a.paciente_id WHERE a.id=? AND p.empresa_id=? AND a.empresa_id=?');
} elseif ($tipo === 'exame') {
    $stmt = db()->prepare('SELECT a.arquivo_resultado AS arquivo, a.arquivo_resultado AS nome_original FROM solicitacoes_exame a JOIN pacientes p ON p.id=a.paciente_id WHERE a.id=? AND p.empresa_id=? AND a.empresa_id=?');
} else {
    http_response_code(404); exit('Arquivo não encontrado.');
}
$stmt->execute([$id, active_company_id(), active_company_id()]);
$file = $stmt->fetch();
$root = realpath(UPLOAD_PATH);
$path = $file && !empty($file['arquivo']) ? realpath(UPLOAD_PATH . '/' . $file['arquivo']) : false;
if (!$root || !$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($path) || !is_readable($path)) {
    http_response_code(404); exit('Arquivo não encontrado.');
}
$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$allowed = ['pdf','jpg','jpeg','png','doc','docx'];
if (!in_array($extension, $allowed, true)) { http_response_code(404); exit('Arquivo não disponível.'); }
$name = basename(str_replace('\\', '/', $file['nome_original'] ?: $file['arquivo']));
$name = str_replace(["\r", "\n", '"'], '', $name);
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
$inline = in_array($mime, ['application/pdf','image/jpeg','image/png'], true);
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="arquivo.' . $extension . '"; filename*=UTF-8\'\'' . rawurlencode($name));
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: sandbox; default-src 'none'");
audit_entity_access('download_arquivo', $tipo === 'anexo' ? 'anexos' : 'solicitacoes_exame', $id, 'Download autenticado de arquivo clínico.');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
while (ob_get_level()) ob_end_clean();
readfile($path);
exit;
