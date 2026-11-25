<?php
/**
 * Funções utilitárias do sistema
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/specialty_forms.php';
require_once __DIR__ . '/specialty_access.php';

// ========== AUTENTICAÇÃO ==========
function is_logged() {
    return isset($_SESSION['user_id']);
}

function is_developer() {
    return (($_SESSION['perfil'] ?? '') === 'desenvolvedor');
}

function active_company_id() {
    if (is_developer()) {
        return (int) ($_SESSION['active_empresa_id'] ?? $_SESSION['empresa_id'] ?? 1);
    }
    return (int) ($_SESSION['empresa_id'] ?? 1);
}

function post_login_redirect($user = null) {
    $perfil = $user['perfil'] ?? ($_SESSION['perfil'] ?? '');
    return $perfil === 'desenvolvedor'
        ? APP_URL . '/modules/desenvolvedor/index.php'
        : APP_URL . '/dashboard.php';
}

function require_login() {
    if (!is_logged()) {
        redirect(APP_URL . '/login.php');
    }
    // Timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        redirect(APP_URL . '/login.php?timeout=1');
    }
    $user = current_user();
    if (!$user || !(int) $user['ativo'] || (int) $user['empresa_id'] !== (int) ($_SESSION['empresa_id'] ?? 0)) {
        session_unset(); session_regenerate_id(true);
        redirect(APP_URL . '/login.php');
    }
    $_SESSION['perfil'] = $user['perfil'];
    if (!headers_sent()) {
        header('Cache-Control: no-store, private');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
    }
    $_SESSION['last_activity'] = time();
    clinic_specialty_route_guard();
}

function require_admin() {
    require_login();
    if (is_developer()) {
        return;
    }
    if (($_SESSION['perfil'] ?? '') !== 'admin') {
        die('<div style="padding:40px;text-align:center;font-family:sans-serif"><h2>Acesso Negado</h2><p>Você não tem permissão para acessar esta página.</p><a href="' . APP_URL . '/dashboard.php">Voltar</a></div>');
    }
}

function require_developer() {
    require_login();
    if (!is_developer()) {
        die('<div style="padding:40px;text-align:center;font-family:sans-serif"><h2>Acesso Restrito</h2><p>Esta área é exclusiva do desenvolvedor.</p><a href="' . APP_URL . '/dashboard.php">Voltar</a></div>');
    }
}

// Confirmation GETs never mutate data; all writes require a valid POST token.
function require_post_action($title = 'Confirmar ação') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_validate();
        return;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        header('Allow: GET, POST'); http_response_code(405); exit('Método não permitido.');
    }
    header('Cache-Control: no-store, private');
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>'.e($title).'</title><body><main><h1>'.e($title).'</h1><p>Confirme para continuar.</p><form method="post">'.csrf_field().'<button type="submit">Confirmar</button></form><p><a href="index.php">Cancelar</a></p></main></body></html>';
    exit;
}

// Only fixed identifiers from this allowlist can reach SQL identifiers.
function clinic_company_record($table, $id, $optional = false) {
    $allowed = ['pacientes','usuarios','agendamentos','prontuarios','procedimentos','planos_tratamento','categorias_financeiras'];
    if (!in_array($table, $allowed, true)) throw new InvalidArgumentException('Entidade inválida.');
    $id = (int) $id;
    if ($optional && $id <= 0) return null;
    $stmt = db()->prepare("SELECT * FROM `$table` WHERE id=? AND empresa_id=? LIMIT 1");
    $stmt->execute([$id, active_company_id()]);
    $row = $stmt->fetch();
    // A developer may act as the logged-in professional in the selected company.
    if (!$row && $table === 'usuarios' && is_developer() && $id === (int) ($_SESSION['user_id'] ?? 0)) return current_user();
    if (!$row) throw new InvalidArgumentException('Registro não encontrado na empresa ativa.');
    return $row;
}
function clinic_validate_clinical_links($patientId, $recordId = 0, $appointmentId = 0) {
    clinic_company_record('pacientes', $patientId);
    foreach (['prontuarios' => $recordId, 'agendamentos' => $appointmentId] as $table => $id) {
        if (!(int) $id) continue;
        $row = clinic_company_record($table, $id);
        if ((int) $row['paciente_id'] !== (int) $patientId) throw new InvalidArgumentException('Vínculo com paciente incompatível.');
    }
}

function current_user() {
    if (!is_logged()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}

function current_empresa() {
    static $cache = [];
    $eid = active_company_id();
    if (!array_key_exists($eid, $cache)) {
        $stmt = db()->prepare("SELECT * FROM empresas WHERE id = ?");
        $stmt->execute([$eid]);
        $cache[$eid] = $stmt->fetch() ?: [];
    }
    return $cache[$eid];
}

function current_license($empresaId = null) {
    $empresaId = (int) ($empresaId ?: active_company_id());
    try {
        $stmt = db()->prepare("SELECT * FROM licencas WHERE empresa_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$empresaId]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function license_is_valid($empresaId = null) {
    if (is_developer()) return true;
    $lic = current_license($empresaId);
    if (!$lic) return true;
    if (!in_array($lic['status'], ['ativa','teste'], true)) return false;
    if (!empty($lic['validade_fim']) && strtotime($lic['validade_fim']) < strtotime(date('Y-m-d'))) {
        return false;
    }
    return true;
}

function license_status_label($empresaId = null) {
    $lic = current_license($empresaId);
    if (!$lic) {
        return ['label' => 'Sem licença', 'class' => 'badge-warning'];
    }

    $status = $lic['status'] ?? 'pendente';
    $map = [
        'ativa' => ['Licença ativa', 'badge-success'],
        'teste' => ['Licença teste', 'badge-info'],
        'expirada' => ['Licença expirada', 'badge-danger'],
        'bloqueada' => ['Licença bloqueada', 'badge-danger'],
        'cancelada' => ['Licença cancelada', 'badge-secondary'],
        'pendente' => ['Licença pendente', 'badge-warning'],
    ];

    $info = $map[$status] ?? ['Licença', 'badge-secondary'];
    if (!empty($lic['validade_fim']) && strtotime($lic['validade_fim']) < strtotime(date('Y-m-d')) && $status !== 'bloqueada') {
        return ['label' => 'Licença expirada', 'class' => 'badge-danger'];
    }

    return ['label' => $info[0], 'class' => $info[1]];
}

// ========== CSRF ==========
function csrf_token() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

function csrf_validate() {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    $expected = $_SESSION[CSRF_TOKEN_NAME] ?? null;
    if (!is_string($expected) || $expected === '' || !is_string($token) || $token === '' || !hash_equals($expected, $token)) {
        http_response_code(403);
        die('Token CSRF inválido. Recarregue a página.');
    }
}

// ========== FLASH MESSAGES ==========
function flash($key, $msg = null) {
    if ($msg === null) {
        $v = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $v;
    }
    $_SESSION['_flash'][$key] = $msg;
}

function flash_render() {
    $html = '';
    foreach (['success','error','warning','info'] as $type) {
        if ($msg = flash($type)) {
            $colors = [
                'success' => 'bg-green-100 border-green-500 text-green-800',
                'error'   => 'bg-red-100 border-red-500 text-red-800',
                'warning' => 'bg-yellow-100 border-yellow-500 text-yellow-800',
                'info'    => 'bg-blue-100 border-blue-500 text-blue-800',
            ];
            $html .= '<div class="border-l-4 ' . $colors[$type] . ' p-4 mb-4 rounded" role="alert">' . htmlspecialchars($msg) . '</div>';
        }
    }
    return $html;
}

// ========== HELPERS ==========
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function clinic_hex_to_rgb_channels($hex, $fallback = '14 165 233') {
    $hex = trim((string) $hex);
    if ($hex === '') {
        return $fallback;
    }

    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    if (!preg_match('/^[a-f0-9]{6}$/i', $hex)) {
        return $fallback;
    }

    return implode(' ', [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ]);
}

function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    }

    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    echo '<script>window.location.href=' . json_encode($url) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $safeUrl . '"></noscript>';
    exit;
}

function clinic_public_path($relativePath = '') {
    $basePath = dirname(__DIR__);
    return rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
}

function clinic_vite_hot_file_path() {
    return clinic_public_path('storage/vite.hot');
}

function clinic_vite_dev_server_url() {
    static $url = null;
    static $loaded = false;

    if ($loaded) {
        return $url;
    }

    $loaded = true;
    $url = '';
    if (!empty($GLOBALS['IS_PRODUCTION'])) return $url;
    $hotFile = clinic_vite_hot_file_path();
    if (!is_file($hotFile)) {
        return $url;
    }

    $value = trim((string) @file_get_contents($hotFile));
    if (filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['http','https'], true)) {
        $url = rtrim($value, '/');
    }

    return $url;
}

function clinic_vite_is_dev_server_running() {
    return clinic_vite_dev_server_url() !== '';
}

function clinic_vite_manifest_path() {
    return clinic_public_path('assets/build/.vite/manifest.json');
}

function clinic_vite_manifest() {
    static $manifest = null;
    static $loaded = false;

    if ($loaded) {
        return $manifest;
    }

    $loaded = true;
    $manifest = [];
    $manifestPath = clinic_vite_manifest_path();

    if (!is_file($manifestPath)) {
        return $manifest;
    }

    $json = @file_get_contents($manifestPath);
    if ($json === false) {
        return $manifest;
    }

    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        $manifest = $decoded;
    }

    return $manifest;
}

function clinic_vite_asset_url($entry) {
    if (clinic_vite_is_dev_server_running()) {
        return clinic_vite_dev_server_url() . '/' . ltrim($entry, '/');
    }

    $manifest = clinic_vite_manifest();
    if (isset($manifest[$entry]['file'])) {
        return APP_URL . '/assets/build/' . ltrim($manifest[$entry]['file'], '/');
    }

    return '';
}

function clinic_vite_css_urls($entry) {
    if (clinic_vite_is_dev_server_running()) {
        return [];
    }

    $manifest = clinic_vite_manifest();
    $urls = [];

    $visited = [];
    $collect = function ($key) use (&$collect, &$visited, &$urls, $manifest) {
        if (isset($visited[$key]) || !isset($manifest[$key])) return;
        $visited[$key] = true;
        $item = $manifest[$key];
        foreach (($item['imports'] ?? []) as $import) $collect($import);
        if (!empty($item['file']) && substr($item['file'], -4) === '.css') $urls[] = APP_URL . '/assets/build/' . ltrim($item['file'], '/');
        foreach (($item['css'] ?? []) as $css) $urls[] = APP_URL . '/assets/build/' . ltrim($css, '/');
    };
    $collect($entry);

    return array_values(array_unique(array_filter($urls)));
}

function clinic_asset_styles($entries = ['resources/entries/app.js']) {
    $html = '';

    foreach ((array) $entries as $entry) {
        foreach (clinic_vite_css_urls($entry) as $url) {
            $html .= '<link rel="stylesheet" href="' . e($url) . '">' . "\n";
        }
    }

    return $html;
}

function clinic_asset_scripts($entries = ['resources/entries/app.js']) {
    $html = '';

    if (clinic_vite_is_dev_server_running()) {
        $html .= '<script type="module" src="' . e(clinic_vite_dev_server_url() . '/@vite/client') . '"></script>' . "\n";
    }

    foreach ((array) $entries as $entry) {
        $url = clinic_vite_asset_url($entry);
        if ($url === '') {
            continue;
        }
        $html .= '<script type="module" src="' . e($url) . '"></script>' . "\n";
    }

    return $html;
}

function back() {
    $ref = $_SERVER['HTTP_REFERER'] ?? APP_URL;
    redirect($ref);
}

function input($key, $default = '') {
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function money($v) {
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

function format_date($d, $time = false) {
    if (!$d) return '';
    $ts = strtotime($d);
    return $time ? date('d/m/Y H:i', $ts) : date('d/m/Y', $ts);
}

function format_month_year_ptbr($timestamp = null) {
    $timestamp = $timestamp ?: time();

    if (class_exists('IntlDateFormatter')) {
        $formatter = new IntlDateFormatter(
            'pt_BR',
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            date_default_timezone_get(),
            null,
            'MMMM/yyyy'
        );
        $formatted = $formatter->format($timestamp);
        if ($formatted !== false) {
            return mb_convert_case($formatted, MB_CASE_TITLE, 'UTF-8');
        }
    }

    $meses = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];

    return $meses[(int) date('n', $timestamp)] . '/' . date('Y', $timestamp);
}

function only_numbers($s) {
    return preg_replace('/\D/', '', $s ?? '');
}

function format_cpf_cnpj($v) {
    $v = only_numbers($v);
    if (strlen($v) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $v);
    } elseif (strlen($v) === 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $v);
    }
    return $v;
}

function format_phone($v) {
    $v = only_numbers($v);
    if (strlen($v) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $v);
    } elseif (strlen($v) === 10) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $v);
    }
    return $v;
}

function wa_link($phone, $message = '') {
    $p = only_numbers($phone);
    if (strlen($p) === 11 || strlen($p) === 10) {
        $p = '55' . $p;
    }
    return 'https://wa.me/' . $p . ($message ? '?text=' . urlencode($message) : '');
}

function log_action($acao, $entidade = null, $entidade_id = null, $descricao = null) {
    try {
        $stmt = db()->prepare("INSERT INTO logs (empresa_id, usuario_id, acao, entidade, entidade_id, descricao, ip) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            active_company_id(), $_SESSION['user_id'] ?? null,
            $acao, $entidade, $entidade_id, $descricao,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {}
}

function upload_file($field, $subdir = 'geral', $allowed = ['jpg','jpeg','png','pdf','doc','docx']) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $f = $_FILES[$field];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        throw new Exception('Tipo de arquivo não permitido: ' . $ext);
    }
    if ($f['size'] > 10 * 1024 * 1024) {
        throw new Exception('Arquivo maior que 10MB');
    }
    if (!preg_match('~^[a-zA-Z0-9_-]+$~', $subdir)) throw new InvalidArgumentException('Diretório inválido.');
    if (!is_uploaded_file($f['tmp_name'])) throw new RuntimeException('Upload inválido.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $mimes = ['jpg'=>['image/jpeg'], 'jpeg'=>['image/jpeg'], 'png'=>['image/png'], 'pdf'=>['application/pdf'], 'doc'=>['application/msword','application/x-ole-storage','application/CDFV2'], 'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip']];
    if (isset($mimes[$ext]) && !in_array($mime, $mimes[$ext], true)) throw new RuntimeException('Conteúdo incompatível com a extensão.');
    $dir = UPLOAD_PATH . '/' . $subdir;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $path = $dir . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $path)) {
        throw new Exception('Falha ao enviar arquivo');
    }
    return [
        'arquivo' => $subdir . '/' . $name,
        'mime'    => $mime,
        'tamanho' => $f['size'],
        'nome_original' => $f['name']
    ];
}

function smtp_is_configured() {
    return defined('SMTP_HOST') && SMTP_HOST
        && defined('SMTP_PORT') && SMTP_PORT
        && defined('SMTP_USER') && SMTP_USER
        && defined('SMTP_PASS') && SMTP_PASS;
}

function phpmailer_is_available() {
    return file_exists(APP_PATH . '/includes/PHPMailer/src/PHPMailer.php')
        && file_exists(APP_PATH . '/includes/PHPMailer/src/SMTP.php')
        && file_exists(APP_PATH . '/includes/PHPMailer/src/Exception.php');
}

function smtp_friendly_error($error) {
    $error = trim((string) $error);
    if ($error === '') {
        return 'Falha desconhecida no envio SMTP.';
    }

    $normalized = mb_strtolower($error, 'UTF-8');

    if (strpos($normalized, 'authenticate') !== false || strpos($normalized, 'authentication') !== false || strpos($normalized, 'username and password not accepted') !== false) {
        return 'Falha de autenticação no SMTP. Revise usuário, senha e método de segurança configurados.';
    }
    if (strpos($normalized, 'could not connect to smtp host') !== false || strpos($normalized, 'connection refused') !== false || strpos($normalized, 'connection timed out') !== false || strpos($normalized, 'network is unreachable') !== false) {
        return 'Não foi possível conectar ao servidor SMTP. Verifique host, porta, firewall e liberação da hospedagem.';
    }
    if (strpos($normalized, 'certificate') !== false || strpos($normalized, 'tls') !== false || strpos($normalized, 'ssl') !== false || strpos($normalized, 'stream_socket_enable_crypto') !== false) {
        return 'Falha na negociação de segurança TLS/SSL. Revise porta, tipo de criptografia e certificado do servidor SMTP.';
    }
    if (strpos($normalized, 'invalid address') !== false || strpos($normalized, 'recipient address rejected') !== false) {
        return 'O e-mail informado parece inválido ou foi recusado pelo servidor SMTP.';
    }

    return $error;
}

function smtp_build_mailer($options = []) {
    if (!smtp_is_configured()) {
        throw new Exception('SMTP não configurado em config/config.php.');
    }

    if (!phpmailer_is_available()) {
        throw new Exception('Biblioteca PHPMailer ausente em includes/PHPMailer/.');
    }

    require_once APP_PATH . '/includes/PHPMailer/src/Exception.php';
    require_once APP_PATH . '/includes/PHPMailer/src/PHPMailer.php';
    require_once APP_PATH . '/includes/PHPMailer/src/SMTP.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = (int) SMTP_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->Timeout = (int) ($options['timeout'] ?? 20);

    $secure = strtolower((string) (defined('SMTP_SECURE') ? SMTP_SECURE : 'tls'));
    if ($secure === 'ssl') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($secure === 'tls') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }

    if (!empty($options['debug'])) {
        $mail->SMTPDebug = 2;
    }

    $fromEmail = trim((string) ($options['from_email'] ?? SMTP_USER));
    $fromName = trim((string) ($options['from_name'] ?? SMTP_FROM_NAME));
    $mail->setFrom($fromEmail, $fromName !== '' ? $fromName : APP_NAME);

    if (!empty($options['reply_to'])) {
        $mail->addReplyTo($options['reply_to'], trim((string) ($options['reply_to_name'] ?? '')));
    }

    return $mail;
}

function smtp_test_connection() {
    try {
        $mail = smtp_build_mailer(['timeout' => 15]);
        $ok = $mail->smtpConnect();
        if ($ok) {
            $mail->smtpClose();
            return [
                'ok' => true,
                'message' => 'Conexão SMTP validada com sucesso.',
                'error' => null,
                'raw_error' => null,
            ];
        }

        return [
            'ok' => false,
            'message' => 'Não foi possível validar a conexão SMTP.',
            'error' => 'Falha ao conectar sem detalhe adicional do servidor.',
            'raw_error' => null,
        ];
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'message' => 'Falha ao conectar no servidor SMTP.',
            'error' => smtp_friendly_error($e->getMessage()),
            'raw_error' => $e->getMessage(),
        ];
    }
}

function send_email($to, $subject, $body, $options = []) {
    $to = trim((string) $to);
    if ($to === '') {
        return ['ok' => false, 'error' => 'Destinatário de e-mail não informado.', 'raw_error' => null];
    }

    try {
        $mail = smtp_build_mailer($options);
        $mail->addAddress($to, trim((string) ($options['to_name'] ?? '')));
        $mail->Subject = $subject;
        $mail->isHTML(!empty($options['is_html']));
        $mail->Body = (string) $body;
        if (!empty($options['alt_body'])) {
            $mail->AltBody = (string) $options['alt_body'];
        } elseif (!empty($options['is_html'])) {
            $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", (string) $body)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        foreach (($options['attachments'] ?? []) as $attachment) {
            if (!empty($attachment['string'])) {
                $mail->addStringAttachment((string) $attachment['string'], (string) ($attachment['name'] ?? 'arquivo.txt'), (string) ($attachment['encoding'] ?? 'base64'), (string) ($attachment['type'] ?? 'application/octet-stream'));
                continue;
            }
            if (!empty($attachment['path']) && is_file($attachment['path'])) {
                $mail->addAttachment((string) $attachment['path'], (string) ($attachment['name'] ?? basename((string) $attachment['path'])));
            }
        }

        if (!empty($options['ical'])) {
            $icalMethod = trim((string) ($options['ical_method'] ?? 'REQUEST')) ?: 'REQUEST';
            $mail->Ical = (string) $options['ical'];
            $mail->ContentType = 'text/calendar; method=' . $icalMethod;
        }

        $mail->send();
        return ['ok' => true, 'error' => null, 'raw_error' => null];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => smtp_friendly_error($e->getMessage()), 'raw_error' => $e->getMessage()];
    }
}

function html_to_text($html) {
    return trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ["\n", "\n", "\n", "\n\n"], (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function calendar_format_utc($datetime) {
    $ts = strtotime((string) $datetime);
    if (!$ts) {
        $ts = time();
    }
    return gmdate('Ymd\THis\Z', $ts);
}

function calendar_escape_text($value) {
    $value = str_replace(["\\", ";", ",", "\r\n", "\r", "\n"], ["\\\\", "\\;", "\\,", "\\n", "\\n", "\\n"], (string) $value);
    return $value;
}

function calendar_event_uid($prefix, $id, $suffix = '') {
    $host = parse_url(APP_URL, PHP_URL_HOST) ?: 'clinicapp.local';
    return $prefix . '-' . $id . ($suffix !== '' ? '-' . preg_replace('/[^A-Za-z0-9\-]/', '', $suffix) : '') . '@' . $host;
}

function build_ics_event(array $event) {
    $uid = $event['uid'] ?? calendar_event_uid('evento', $event['id'] ?? random_int(1000, 9999));
    $summary = calendar_escape_text($event['summary'] ?? APP_NAME);
    $description = calendar_escape_text($event['description'] ?? '');
    $location = calendar_escape_text($event['location'] ?? '');
    $url = trim((string) ($event['url'] ?? ''));
    $method = strtoupper(trim((string) ($event['method'] ?? 'REQUEST')) ?: 'REQUEST');
    $dtStart = calendar_format_utc($event['start']);
    $dtEnd = calendar_format_utc($event['end']);
    $now = gmdate('Ymd\THis\Z');

    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//' . APP_NAME . '//PT-BR',
        'CALSCALE:GREGORIAN',
        'METHOD:' . $method,
        'BEGIN:VEVENT',
        'UID:' . $uid,
        'DTSTAMP:' . $now,
        'DTSTART:' . $dtStart,
        'DTEND:' . $dtEnd,
        'SUMMARY:' . $summary,
        'DESCRIPTION:' . $description,
    ];

    if ($location !== '') {
        $lines[] = 'LOCATION:' . $location;
    }
    if ($url !== '') {
        $lines[] = 'URL:' . calendar_escape_text($url);
    }

    $lines[] = 'END:VEVENT';
    $lines[] = 'END:VCALENDAR';

    return implode("\r\n", $lines) . "\r\n";
}

function google_calendar_event_url(array $event) {
    $params = [
        'action' => 'TEMPLATE',
        'text' => $event['summary'] ?? APP_NAME,
        'dates' => calendar_format_utc($event['start']) . '/' . calendar_format_utc($event['end']),
        'details' => $event['description'] ?? '',
        'location' => $event['location'] ?? '',
    ];
    if (!empty($event['url'])) {
        $params['details'] .= ($params['details'] !== '' ? "\n\n" : '') . $event['url'];
    }
    return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
}

function appointment_calendar_event(array $appointment) {
    $start = $appointment['data_hora_inicio'] ?? date('Y-m-d H:i:s');
    $end = $appointment['data_hora_fim'] ?? date('Y-m-d H:i:s', strtotime($start . ' +30 minutes'));
    $paciente = trim((string) ($appointment['paciente_nome'] ?? $appointment['pn'] ?? 'Paciente'));
    $profissional = trim((string) ($appointment['prof_nome'] ?? 'Profissional'));
    $procedimento = trim((string) ($appointment['proc_nome'] ?? 'Consulta'));
    $modalidade = trim((string) ($appointment['modalidade'] ?? 'presencial'));
    $location = $modalidade === 'telemedicina'
        ? 'Telemedicina - ' . APP_NAME
        : trim((string) ((current_empresa()['nome_fantasia'] ?? current_empresa()['razao_social'] ?? APP_NAME)));

    $description = "Paciente: {$paciente}\nProfissional: {$profissional}\nProcedimento: {$procedimento}\nModalidade: {$modalidade}";
    if (!empty($appointment['observacoes'])) {
        $description .= "\nObservações: " . trim((string) $appointment['observacoes']);
    }
    if (!empty($appointment['link_paciente'])) {
        $description .= "\nLink de acesso: " . $appointment['link_paciente'];
    }

    return [
        'id' => $appointment['id'] ?? 0,
        'uid' => calendar_event_uid('agendamento', $appointment['id'] ?? 0),
        'summary' => $procedimento . ' - ' . $paciente,
        'description' => $description,
        'location' => $location,
        'start' => $start,
        'end' => $end,
        'url' => !empty($appointment['link_paciente']) ? $appointment['link_paciente'] : '',
    ];
}

function push_notification_supported() {
    return true;
}

function pwa_is_installed() {
    return isset($_SERVER['HTTP_SEC_CH_UA_PLATFORM']);
}

// ========== TEMA ==========
function specialty_theme($especialidade) {
    $especialidade = mb_strtolower(trim((string) $especialidade), 'UTF-8');
    if ($especialidade === '') {
        return null;
    }

    if (strpos($especialidade, 'psicol') !== false || strpos($especialidade, 'psican') !== false || strpos($especialidade, 'neuropsic') !== false || strpos($especialidade, 'terapia') !== false) {
        return 'psicologia';
    }

    $keywords = [
        'psiquiatria' => ['psiquiatr', 'saude mental'],
        'pediatria' => ['pedia', 'pueric', 'neonat'],
        'ginecologia_obstetricia' => ['gineco', 'obstet', 'prenatal', 'pré-natal'],
        'ortopedia_traumatologia' => ['ortoped', 'traumat', 'coluna'],
        'cardiologia' => ['cardio'],
        'oftalmologia' => ['oftalmo', 'retina', 'glaucoma'],
        'neurologia' => ['neurolog', 'epilep'],
        'dentista' => ['odonto', 'dent', 'buco'],
    ];

    foreach ($keywords as $theme => $terms) {
        foreach ($terms as $term) {
            if (strpos($especialidade, $term) !== false) {
                return $theme;
            }
        }
    }

    return 'medico';
}

function get_theme() {
    if (is_logged()) {
        $empresa = current_empresa();
        $companyTheme = normalize_theme_choice($empresa['tema_padrao'] ?? 'medico');

        $u = current_user();
        $rawUserTheme = trim((string) ($u['tema_preferido'] ?? ''));
        if ($rawUserTheme !== '') {
            return clinic_available_theme(normalize_theme_choice($rawUserTheme, 'medico'));
        }

        $specialtyTheme = specialty_theme($u['especialidade'] ?? '');
        if ($specialtyTheme && $specialtyTheme !== 'medico') {
            return clinic_available_theme($specialtyTheme);
        }

        return clinic_available_theme($companyTheme ?: 'medico');
    }
    return 'medico';
}

function theme_color() {
    if (is_logged()) {
        $u = current_user();
        return $u['cor_tema'] ?? 'blue';
    }
    return 'blue';
}

function odontograma_enabled() {
    if (!clinic_specialty_enabled('dentista')) return false;
    $activeTheme = normalize_theme_choice(get_theme(), 'medico');
    if ($activeTheme === 'dentista') {
        return true;
    }

    $user = current_user();
    $specialtyTheme = specialty_theme($user['especialidade'] ?? '');
    if ($specialtyTheme === 'dentista') {
        return true;
    }

    $empresa = current_empresa();
    $companyTheme = normalize_theme_choice($empresa['tema_padrao'] ?? 'medico', 'medico');
    return $companyTheme === 'dentista';
}

function theme_config() {
    $tema = get_theme();
    $cor = theme_color();
    $configs = clinic_theme_catalog();
    $config = $configs[$tema] ?? $configs['medico'];

    $palette = [
        'blue' => ['primary' => '#0ea5e9', 'secondary' => '#1d4ed8', 'from' => 'from-sky-600', 'to' => 'to-blue-800'],
        'green' => ['primary' => '#10b981', 'secondary' => '#047857', 'from' => 'from-emerald-500', 'to' => 'to-green-700'],
        'teal' => ['primary' => '#14b8a6', 'secondary' => '#0f766e', 'from' => 'from-teal-500', 'to' => 'to-cyan-700'],
        'cyan' => ['primary' => '#06b6d4', 'secondary' => '#0e7490', 'from' => 'from-cyan-500', 'to' => 'to-sky-700'],
        'indigo' => ['primary' => '#6366f1', 'secondary' => '#4338ca', 'from' => 'from-indigo-500', 'to' => 'to-blue-800'],
        'purple' => ['primary' => '#a855f7', 'secondary' => '#7e22ce', 'from' => 'from-violet-500', 'to' => 'to-purple-800'],
        'rose' => ['primary' => '#f43f5e', 'secondary' => '#be123c', 'from' => 'from-rose-500', 'to' => 'to-pink-700'],
        'pink' => ['primary' => '#ec4899', 'secondary' => '#be185d', 'from' => 'from-pink-500', 'to' => 'to-fuchsia-700'],
    ];

    $accent = $palette[$cor] ?? $palette['blue'];
    $config['accent_primary'] = $accent['primary'];
    $config['accent_secondary'] = $accent['secondary'];
    $config['accent_gradient_from'] = $accent['from'];
    $config['accent_gradient_to'] = $accent['to'];

    return $config;
}

function db_table_exists($table) {
    static $cache = [];
    if (!array_key_exists($table, $cache)) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        $cache[$table] = ((int) $stmt->fetchColumn()) > 0;
    }
    return $cache[$table];
}

function db_column_exists($table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (!array_key_exists($key, $cache)) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        $cache[$key] = ((int) $stmt->fetchColumn()) > 0;
    }
    return $cache[$key];
}

function app_config_uses_company_scope() {
    return db_table_exists('configuracoes') && db_column_exists('configuracoes', 'empresa_id');
}

function app_config_get($key, $default = null, $empresaId = null) {
    if (!db_table_exists('configuracoes')) {
        return $default;
    }

    $empresaId = (int) ($empresaId ?: active_company_id());

    try {
        if (app_config_uses_company_scope()) {
            $stmt = db()->prepare("SELECT valor FROM configuracoes WHERE empresa_id = ? AND chave = ? LIMIT 1");
            $stmt->execute([$empresaId, $key]);
            $value = $stmt->fetchColumn();
            if ($value !== false) {
                return $value;
            }
        }

        $stmt = db()->prepare("SELECT valor FROM configuracoes WHERE chave = ? LIMIT 1");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function app_config_set($key, $value, $empresaId = null) {
    if (!db_table_exists('configuracoes')) {
        return false;
    }

    $empresaId = (int) ($empresaId ?: active_company_id());
    $value = $value === null ? null : (string) $value;

    if (app_config_uses_company_scope()) {
        $stmt = db()->prepare("INSERT INTO configuracoes (empresa_id, chave, valor) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        return $stmt->execute([$empresaId, $key, $value]);
    }

    $stmt = db()->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    return $stmt->execute([$key, $value]);
}

function app_config_set_many(array $items, $empresaId = null) {
    foreach ($items as $key => $value) {
        app_config_set($key, $value, $empresaId);
    }
}

function app_config_delete($key, $empresaId = null) {
    if (!db_table_exists('configuracoes')) {
        return false;
    }

    $empresaId = (int) ($empresaId ?: active_company_id());
    if (app_config_uses_company_scope()) {
        $stmt = db()->prepare("DELETE FROM configuracoes WHERE empresa_id = ? AND chave = ?");
        return $stmt->execute([$empresaId, $key]);
    }

    $stmt = db()->prepare("DELETE FROM configuracoes WHERE chave = ?");
    return $stmt->execute([$key]);
}

function app_current_version() {
    $storedVersion = trim((string) app_config_get('app_versao', APP_VERSION));
    if ($storedVersion === '') {
        return APP_VERSION;
    }
    return version_compare($storedVersion, APP_VERSION, '>') ? $storedVersion : APP_VERSION;
}

function github_update_defaults() {
    $defaultRepo = defined('GITHUB_UPDATE_REPO') ? GITHUB_UPDATE_REPO : 'leirson/Clinicapp';
    $defaultBranch = defined('GITHUB_UPDATE_BRANCH') ? GITHUB_UPDATE_BRANCH : 'main';
    $defaultToken = defined('GITHUB_UPDATE_TOKEN') ? GITHUB_UPDATE_TOKEN : '';
    $defaultChangelogPath = defined('GITHUB_UPDATE_CHANGELOG_PATH') ? GITHUB_UPDATE_CHANGELOG_PATH : 'CHANGELOG_v2.md';

    return [
        'repo' => trim((string) app_config_get('github_repo', $defaultRepo)),
        'branch' => trim((string) app_config_get('github_branch', $defaultBranch)),
        'token' => trim((string) app_config_get('github_token', $defaultToken)),
        'changelog_path' => trim((string) app_config_get('github_changelog_path', $defaultChangelogPath)),
    ];
}

function github_token_mask($token) {
    $token = trim((string) $token);
    $length = strlen($token);
    if ($length <= 8) {
        return str_repeat('•', $length);
    }
    return substr($token, 0, 4) . str_repeat('•', max(4, $length - 8)) . substr($token, -4);
}

function github_api_request($url, $token, array $headers = []) {
    $headers = array_merge([
        'Accept: application/vnd.github+json',
        'Authorization: Bearer ' . $token,
        'User-Agent: ClinicApp-Updater/' . APP_VERSION,
        'X-GitHub-Api-Version: 2022-11-28',
    ], $headers);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('Falha de comunicação com o GitHub: ' . $error);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? 'HTTP/1.1 500 Internal Server Error';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        $status = (int) ($matches[1] ?? 500);
        if ($body === false) {
            throw new RuntimeException('Falha de comunicação com o GitHub.');
        }
    }

    if ($status < 200 || $status >= 300) {
        $json = json_decode((string) $body, true);
        $message = is_array($json) ? ($json['message'] ?? 'Erro HTTP ' . $status) : ('Erro HTTP ' . $status);
        throw new RuntimeException('GitHub retornou erro: ' . $message);
    }

    return (string) $body;
}

function github_fetch_repo_info($repo, $token) {
    $url = 'https://api.github.com/repos/' . str_replace('%2F', '/', rawurlencode($repo));
    $data = json_decode(github_api_request($url, $token), true);
    if (!is_array($data)) {
        throw new RuntimeException('Resposta inválida ao consultar o repositório.');
    }
    return $data;
}

function github_fetch_commit_info($repo, $branch, $token) {
    $url = 'https://api.github.com/repos/' . str_replace('%2F', '/', rawurlencode($repo)) . '/commits/' . rawurlencode($branch);
    $data = json_decode(github_api_request($url, $token), true);
    if (!is_array($data)) {
        throw new RuntimeException('Resposta inválida ao consultar o commit mais recente.');
    }
    return $data;
}

function github_fetch_repo_file_content($repo, $branch, $token, $path) {
    $path = trim((string) $path, '/');
    if ($path === '') {
        return null;
    }
    $url = 'https://api.github.com/repos/' . str_replace('%2F', '/', rawurlencode($repo)) . '/contents/' . str_replace('%2F', '/', rawurlencode($path)) . '?ref=' . rawurlencode($branch);
    $data = json_decode(github_api_request($url, $token), true);
    if (!is_array($data) || empty($data['content'])) {
        return null;
    }

    $content = base64_decode((string) $data['content'], true);
    return $content === false ? null : $content;
}

function github_fetch_remote_version($repo, $branch, $token) {
    $content = github_fetch_repo_file_content($repo, $branch, $token, 'config/version.php');
    if ($content === null) {
        return null;
    }

    if (preg_match("/APP_VERSION'\)\) \{\s*define\('APP_VERSION', '([^']+)'\)/", $content, $matches)) {
        return $matches[1];
    }
    if (preg_match("/define\('APP_VERSION', '([^']+)'\)/", $content, $matches)) {
        return $matches[1];
    }

    return null;
}

function github_fetch_remote_changelog($repo, $branch, $token, $path = '') {
    $path = trim((string) $path);
    $candidates = array_values(array_unique(array_filter([
        $path !== '' ? $path : null,
        'CHANGELOG_v2.md',
        'docs/CHANGELOG_v2.md',
        'CHANGELOG.md',
        'changelog.md',
        'docs/CHANGELOG.md',
        'docs/changelog.md',
        'CHANGELOG.txt',
    ])));

    foreach ($candidates as $candidate) {
        try {
            $content = github_fetch_repo_file_content($repo, $branch, $token, $candidate);
            if ($content !== null && trim($content) !== '') {
                return [
                    'path' => $candidate,
                    'content' => $content,
                ];
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    return [
        'path' => $path !== '' ? $path : 'CHANGELOG.md',
        'content' => null,
    ];
}

function changelog_extract_latest_section($content, $version = '') {
    $content = trim((string) $content);
    if ($content === '') {
        return '';
    }

    $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
    $version = trim((string) $version);
    $matchedStart = null;

    if ($version !== '') {
        foreach ($lines as $index => $line) {
            $normalized = mb_strtolower(trim((string) $line), 'UTF-8');
            if ($normalized !== '' && str_contains($normalized, mb_strtolower($version, 'UTF-8'))) {
                $matchedStart = $index;
                break;
            }
        }
    }

    if ($matchedStart === null) {
        foreach ($lines as $index => $line) {
            if (preg_match('/^#{1,3}\s+/', trim((string) $line))) {
                $matchedStart = $index;
                break;
            }
        }
    }

    if ($matchedStart === null) {
        return implode("\n", array_slice($lines, 0, 40));
    }

    $section = [];
    for ($i = $matchedStart; $i < count($lines); $i++) {
        $line = (string) $lines[$i];
        if ($i > $matchedStart && preg_match('/^#{1,3}\s+/', trim($line))) {
            break;
        }
        $section[] = $line;
    }

    return trim(implode("\n", $section));
}

function changelog_markdown_to_html($markdown) {
    $markdown = trim((string) $markdown);
    if ($markdown === '') {
        return '<p class="text-sm text-slate-500">Nenhum conteúdo de changelog disponível.</p>';
    }

    $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
    $html = [];
    $inList = false;

    $inlineFormat = static function ($text) {
        $safe = e((string) $text);
        $safe = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $safe) ?? $safe;
        $safe = preg_replace('/`(.+?)`/u', '<code class="rounded bg-slate-100 px-1 py-0.5 text-xs">$1</code>', $safe) ?? $safe;
        return $safe;
    };

    foreach ($lines as $line) {
        $trimmed = trim((string) $line);
        if ($trimmed === '') {
            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }
            continue;
        }

        if (preg_match('/^(#{1,3})\s+(.+)$/u', $trimmed, $matches)) {
            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }
            $level = min(4, strlen($matches[1]) + 1);
            $html[] = '<h' . $level . ' class="font-bold text-slate-800 mt-3 mb-2">' . $inlineFormat($matches[2]) . '</h' . $level . '>';
            continue;
        }

        if (preg_match('/^[-*+]\s+(.+)$/u', $trimmed, $matches)) {
            if (!$inList) {
                $html[] = '<ul class="list-disc pl-5 space-y-1 text-sm text-slate-700">';
                $inList = true;
            }
            $html[] = '<li>' . $inlineFormat($matches[1]) . '</li>';
            continue;
        }

        if ($inList) {
            $html[] = '</ul>';
            $inList = false;
        }
        $html[] = '<p class="text-sm text-slate-700 leading-6">' . $inlineFormat($trimmed) . '</p>';
    }

    if ($inList) {
        $html[] = '</ul>';
    }

    return implode("\n", $html);
}

function updater_normalize_sql($sql) {
    $sql = str_replace(["\r\n", "\r"], "\n", (string) $sql);
    $sql = preg_replace('~/\*.*?\*/~s', '', $sql) ?? $sql;

    $filtered = [];
    foreach (explode("\n", $sql) as $line) {
        $trim = ltrim($line);
        if ($trim !== '' && (strpos($trim, '--') === 0 || strpos($trim, '#') === 0)) {
            continue;
        }
        $filtered[] = $line;
    }

    return implode("\n", $filtered);
}

function updater_split_sql_statements($sql) {
    $sql = updater_normalize_sql($sql);
    $statements = [];
    $buffer = '';
    $delimiter = ';';
    $inSingle = false;
    $inDouble = false;
    $escape = false;
    $lines = preg_split("/\n/", $sql) ?: [];

    foreach ($lines as $line) {
        if (!$inSingle && !$inDouble && preg_match('/^\s*DELIMITER\s+(.+?)\s*$/i', $line, $matches)) {
            $delimiter = $matches[1] !== '' ? $matches[1] : ';';
            continue;
        }

        $chunk = $line . "\n";
        $length = strlen($chunk);
        for ($i = 0; $i < $length; $i++) {
            $char = $chunk[$i];
            $buffer .= $char;

            if ($escape) {
                $escape = false;
                continue;
            }
            if ($char === '\\') {
                $escape = true;
                continue;
            }
            if ($char === "'" && !$inDouble) {
                $inSingle = !$inSingle;
                continue;
            }
            if ($char === '"' && !$inSingle) {
                $inDouble = !$inDouble;
                continue;
            }

            if (!$inSingle && !$inDouble && $delimiter !== '' && substr($buffer, -strlen($delimiter)) === $delimiter) {
                $statement = trim(substr($buffer, 0, -strlen($delimiter)));
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                $escape = false;
            }
        }
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

function updater_statement_returns_resultset($statement) {
    $keyword = strtoupper((string) strtok(ltrim((string) $statement), " \t\r\n"));
    return in_array($keyword, ['SELECT', 'SHOW', 'DESCRIBE', 'EXPLAIN', 'CALL'], true);
}

function updater_execute_sql_statement(PDO $pdo, $statement) {
    if (updater_statement_returns_resultset($statement)) {
        $stmt = $pdo->query($statement);
        if ($stmt instanceof PDOStatement) {
            do {
                $stmt->fetchAll();
            } while ($stmt->nextRowset());
            $stmt->closeCursor();
        }
        return;
    }
    $pdo->exec($statement);
}

function updater_migrations_ensure_table() {
    db()->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT(11) NOT NULL AUTO_INCREMENT,
        filename VARCHAR(190) NOT NULL,
        checksum VARCHAR(64) DEFAULT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        source VARCHAR(50) DEFAULT 'updater',
        PRIMARY KEY (id),
        UNIQUE KEY uk_schema_migrations_filename (filename)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function updater_migration_files_from_dir($baseDir) {
    $pattern = rtrim((string) $baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'upgrade_*.sql';
    $files = glob($pattern) ?: [];
    natsort($files);
    return array_values($files);
}

function updater_migration_checksum($path) {
    return is_file($path) ? (sha1_file($path) ?: null) : null;
}

function updater_migration_mark_applied($filename, $checksum = null, $source = 'updater') {
    updater_migrations_ensure_table();
    $stmt = db()->prepare("INSERT INTO schema_migrations (filename, checksum, applied_at, source)
        VALUES (?, ?, NOW(), ?)
        ON DUPLICATE KEY UPDATE checksum = VALUES(checksum), source = VALUES(source)");
    $stmt->execute([(string) $filename, $checksum, (string) $source]);
}

function updater_migration_applied_filenames() {
    updater_migrations_ensure_table();
    $rows = db()->query("SELECT filename FROM schema_migrations ORDER BY filename")->fetchAll(PDO::FETCH_COLUMN);
    return $rows ?: [];
}

function updater_seed_current_migrations() {
    updater_migrations_ensure_table();
    foreach (updater_migration_files_from_dir(APP_PATH) as $file) {
        updater_migration_mark_applied(basename($file), updater_migration_checksum($file), 'seed_local');
    }
}

function updater_backup_dir() {
    $dir = APP_PATH . '/storage/backups';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Não foi possível preparar o diretório de backups da atualização.');
    }
    return $dir;
}

function updater_backup_record_create($filename, $tipo = 'automatico') {
    if (!db_table_exists('backups')) {
        return 0;
    }

    try {
        $stmt = db()->prepare("INSERT INTO backups (tipo, arquivo, status) VALUES (?, ?, 'em_andamento')");
        $stmt->execute([(string) $tipo, (string) $filename]);
        return (int) db()->lastInsertId();
    } catch (Throwable $e) {
        return 0;
    }
}

function updater_backup_record_finish($backupId, $filename, $size, $error = null) {
    if ($backupId <= 0 || !db_table_exists('backups')) {
        return;
    }

    $status = $error === null ? 'concluido' : 'erro';
    $stmt = db()->prepare("UPDATE backups SET status = ?, arquivo = ?, tamanho_bytes = ?, erro = ?, concluido_em = NOW() WHERE id = ?");
    $stmt->execute([$status, (string) $filename, (int) $size, $error, (int) $backupId]);
}

function updater_dump_database_sql(PDO $pdo, $label = 'ClinicApp Backup') {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sql = '-- ' . $label . ' ' . date('Y-m-d H:i:s') . "\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        if (!isset($create[1])) {
            throw new RuntimeException('Não foi possível obter a estrutura da tabela ' . $table . '.');
        }
        $sql .= $create[1] . ";\n\n";

        $stmt = $pdo->query("SELECT * FROM `{$table}`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cols = '`' . implode('`,`', array_keys($row)) . '`';
            $vals = array_map(static function ($value) use ($pdo) {
                return $value === null ? 'NULL' : $pdo->quote($value);
            }, array_values($row));
            $sql .= "INSERT INTO `{$table}` ({$cols}) VALUES (" . implode(',', $vals) . ");\n";
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

function updater_create_database_backup($context = 'update') {
    $backupDir = updater_backup_dir();
    $baseName = $context . '_' . date('Ymd_His') . '.sql';
    $filePath = $backupDir . DIRECTORY_SEPARATOR . $baseName;
    $backupId = updater_backup_record_create($baseName, 'automatico');

    try {
        $sql = updater_dump_database_sql(db(), 'ClinicApp Pre-Update Backup');
        if (file_put_contents($filePath, $sql) === false) {
            throw new RuntimeException('Não foi possível gravar o backup pré-atualização do banco.');
        }

        if (function_exists('gzopen')) {
            $gzPath = $filePath . '.gz';
            $gz = gzopen($gzPath, 'wb9');
            if ($gz === false) {
                throw new RuntimeException('Não foi possível compactar o backup pré-atualização do banco.');
            }
            gzwrite($gz, $sql);
            gzclose($gz);
            @unlink($filePath);
            $filePath = $gzPath;
            $baseName .= '.gz';
        }

        $size = (int) (filesize($filePath) ?: 0);
        updater_backup_record_finish($backupId, $baseName, $size, null);

        return [
            'id' => $backupId,
            'filename' => $baseName,
            'path' => $filePath,
            'size' => $size,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    } catch (Throwable $e) {
        updater_backup_record_finish($backupId, $baseName, 0, $e->getMessage());
        if (is_file($filePath)) {
            @unlink($filePath);
        }
        if (is_file($filePath . '.gz')) {
            @unlink($filePath . '.gz');
        }
        throw $e;
    }
}

function updater_read_backup_contents($path) {
    if (!is_file($path)) {
        throw new RuntimeException('Arquivo de backup não encontrado para rollback.');
    }

    if (substr((string) $path, -3) === '.gz') {
        if (!function_exists('gzopen')) {
            throw new RuntimeException('O servidor não suporta leitura de backups compactados (.gz).');
        }
        $handle = gzopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Não foi possível abrir o backup compactado para rollback.');
        }
        $contents = '';
        while (!gzeof($handle)) {
            $contents .= (string) gzread($handle, 8192);
        }
        gzclose($handle);
        return $contents;
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Não foi possível ler o backup de rollback.');
    }
    return $contents;
}

function updater_restore_database_backup($path) {
    $sql = updater_read_backup_contents($path);
    $count = 0;
    foreach (updater_split_sql_statements($sql) as $statement) {
        try {
            updater_execute_sql_statement(db(), $statement);
            $count++;
        } catch (PDOException $e) {
            $preview = preg_replace('/\s+/', ' ', trim(substr((string) $statement, 0, 220))) ?? 'instrução SQL';
            throw new RuntimeException('Erro ao restaurar backup: ' . $e->getMessage() . ' | Trecho: ' . $preview, 0, $e);
        }
    }
    return $count;
}

function github_rollback_last_database_backup() {
    $backupFile = trim((string) app_config_get('github_last_backup_file', ''));
    if ($backupFile === '') {
        throw new RuntimeException('Nenhum backup pré-atualização foi registrado para rollback.');
    }

    $path = updater_backup_dir() . DIRECTORY_SEPARATOR . basename($backupFile);
    $statements = updater_restore_database_backup($path);
    app_config_set_many([
        'github_last_rollback_status' => 'sucesso',
        'github_last_rollback_at' => date('Y-m-d H:i:s'),
        'github_last_rollback_file' => basename($backupFile),
        'github_last_rollback_details' => 'Rollback manual executado com sucesso.',
        'github_last_status' => 'rollback_manual_executado',
        'github_last_error' => '',
    ]);

    log_action('rollback', 'sistema', null, 'Rollback manual do banco executado a partir de ' . basename($backupFile));

    return [
        'filename' => basename($backupFile),
        'path' => $path,
        'statements' => $statements,
        'rolled_back_at' => date('Y-m-d H:i:s'),
    ];
}

function updater_import_sql_file($path) {
    if (!is_file($path)) {
        throw new RuntimeException('Arquivo SQL não encontrado: ' . basename((string) $path));
    }
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Não foi possível ler o arquivo SQL: ' . basename((string) $path));
    }

    $count = 0;
    foreach (updater_split_sql_statements($sql) as $statement) {
        try {
            updater_execute_sql_statement(db(), $statement);
            $count++;
        } catch (PDOException $e) {
            $preview = preg_replace('/\s+/', ' ', trim(substr((string) $statement, 0, 220))) ?? 'instrução SQL';
            throw new RuntimeException('Erro ao aplicar ' . basename((string) $path) . ': ' . $e->getMessage() . ' | Trecho: ' . $preview, 0, $e);
        }
    }
    return $count;
}

function updater_apply_database_migrations_from_source($sourceRoot) {
    updater_seed_current_migrations();
    $applied = array_flip(updater_migration_applied_filenames());
    $executed = [];

    foreach (updater_migration_files_from_dir($sourceRoot) as $file) {
        $filename = basename($file);
        if (isset($applied[$filename])) {
            continue;
        }

        $count = updater_import_sql_file($file);
        updater_migration_mark_applied($filename, updater_migration_checksum($file), 'github_update');
        $executed[] = [
            'filename' => $filename,
            'statements' => $count,
        ];
        $applied[$filename] = true;
    }

    return $executed;
}

function github_updater_check() {
    $settings = github_update_defaults();
    $repo = trim((string) $settings['repo']);
    $token = trim((string) $settings['token']);
    $branch = trim((string) $settings['branch']);
    $changelogPath = trim((string) ($settings['changelog_path'] ?? 'CHANGELOG.md'));

    if ($repo === '') {
        throw new RuntimeException('Informe o repositório GitHub nas configurações de atualização.');
    }
    if ($token === '') {
        throw new RuntimeException('Informe o token GitHub nas configurações de atualização.');
    }

    $repoInfo = github_fetch_repo_info($repo, $token);
    if ($branch === '') {
        $branch = (string) ($repoInfo['default_branch'] ?? 'main');
        app_config_set('github_branch', $branch);
    }

    $commitInfo = github_fetch_commit_info($repo, $branch, $token);
    $remoteVersion = github_fetch_remote_version($repo, $branch, $token);
    $changelogInfo = github_fetch_remote_changelog($repo, $branch, $token, $changelogPath);
    $currentVersion = app_current_version();
    $remoteSha = (string) ($commitInfo['sha'] ?? '');
    $lastSha = (string) app_config_get('github_last_commit_sha', '');
    $updateAvailable = $remoteVersion
        ? version_compare($remoteVersion, $currentVersion, '>')
        : ($remoteSha !== '' && $remoteSha !== $lastSha);

    $result = [
        'repo' => $repo,
        'branch' => $branch,
        'current_version' => $currentVersion,
        'remote_version' => $remoteVersion ?: $currentVersion,
        'remote_sha' => $remoteSha,
        'remote_short_sha' => $remoteSha !== '' ? substr($remoteSha, 0, 7) : '',
        'last_sha' => $lastSha,
        'update_available' => $updateAvailable,
        'default_branch' => (string) ($repoInfo['default_branch'] ?? $branch),
        'commit_message' => trim((string) ($commitInfo['commit']['message'] ?? '')),
        'commit_date' => (string) ($commitInfo['commit']['committer']['date'] ?? ''),
        'checked_at' => date('Y-m-d H:i:s'),
        'changelog_path' => (string) ($changelogInfo['path'] ?? $changelogPath),
        'changelog_content' => trim((string) ($changelogInfo['content'] ?? '')),
    ];

    app_config_set_many([
        'github_repo' => $repo,
        'github_branch' => $branch,
        'github_last_check_at' => $result['checked_at'],
        'github_last_commit_sha' => $remoteSha,
        'github_last_commit_message' => $result['commit_message'],
        'github_last_commit_date' => $result['commit_date'],
        'github_last_remote_version' => $result['remote_version'],
        'github_last_status' => $updateAvailable ? 'atualizacao_disponivel' : 'atualizado',
        'github_changelog_path' => $result['changelog_path'],
        'github_last_changelog' => $result['changelog_content'],
    ]);

    return $result;
}

function updater_remove_tree($path) {
    if (!file_exists($path)) {
        return;
    }
    if (is_file($path) || is_link($path)) {
        @unlink($path);
        return;
    }
    $items = scandir($path) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        updater_remove_tree($path . DIRECTORY_SEPARATOR . $item);
    }
    @rmdir($path);
}

function updater_find_zip_root($path) {
    $items = array_values(array_filter(scandir($path) ?: [], static function ($item) {
        return $item !== '.' && $item !== '..';
    }));
    if (count($items) === 1 && is_dir($path . DIRECTORY_SEPARATOR . $items[0])) {
        return $path . DIRECTORY_SEPARATOR . $items[0];
    }
    return $path;
}

function updater_should_skip_path($relativePath) {
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    if ($relativePath === '') {
        return false;
    }

    $skip = [
        'config/config.php',
        'config/install.lock',
        'assets/uploads',
        '.git',
        '.github',
    ];

    foreach ($skip as $prefix) {
        if ($relativePath === $prefix || strpos($relativePath, $prefix . '/') === 0) {
            return true;
        }
    }

    return false;
}

function updater_copy_tree($source, $destination, $relativeBase = '') {
    if (!is_dir($source)) {
        throw new RuntimeException('Diretório de origem da atualização não encontrado.');
    }

    $items = scandir($source);
    if ($items === false) {
        throw new RuntimeException('Não foi possível listar os arquivos baixados do GitHub.');
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $srcPath = $source . DIRECTORY_SEPARATOR . $item;
        $relativePath = ltrim($relativeBase . '/' . $item, '/');
        if (updater_should_skip_path($relativePath)) {
            continue;
        }

        $dstPath = $destination . DIRECTORY_SEPARATOR . $item;
        if (is_dir($srcPath)) {
            if (!is_dir($dstPath) && !@mkdir($dstPath, 0775, true) && !is_dir($dstPath)) {
                throw new RuntimeException('Não foi possível criar a pasta: ' . $relativePath);
            }
            updater_copy_tree($srcPath, $dstPath, $relativePath);
            continue;
        }

        $dir = dirname($dstPath);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Não foi possível preparar a pasta do arquivo: ' . $relativePath);
        }
        if (!@copy($srcPath, $dstPath)) {
            throw new RuntimeException('Falha ao substituir o arquivo: ' . $relativePath);
        }
    }
}

function github_download_zipball($repo, $branch, $token, $destinationFile) {
    $url = 'https://api.github.com/repos/' . str_replace('%2F', '/', rawurlencode($repo)) . '/zipball/' . rawurlencode($branch);
    $headers = [
        'Accept: application/vnd.github+json',
        'Authorization: Bearer ' . $token,
        'User-Agent: ClinicApp-Updater/' . APP_VERSION,
        'X-GitHub-Api-Version: 2022-11-28',
    ];

    if (function_exists('curl_init')) {
        $fp = fopen($destinationFile, 'wb');
        if ($fp === false) {
            throw new RuntimeException('Não foi possível preparar o arquivo temporário do pacote de atualização.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $ok = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$ok || $status < 200 || $status >= 300) {
            @unlink($destinationFile);
            throw new RuntimeException('Não foi possível baixar o pacote do GitHub' . ($error ? ': ' . $error : '.'));
        }
        return;
    }

    $body = github_api_request($url, $token, ['Accept: application/zip']);
    if (file_put_contents($destinationFile, $body) === false) {
        throw new RuntimeException('Não foi possível salvar o pacote de atualização baixado.');
    }
}

function github_apply_update() {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('A extensão ZipArchive não está disponível no servidor.');
    }

    $check = github_updater_check();
    $settings = github_update_defaults();
    $repo = $settings['repo'];
    $branch = $settings['branch'];
    $token = $settings['token'];

    $tmpBase = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'clinicapp_update_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    $zipFile = $tmpBase . '.zip';
    $extractDir = $tmpBase . '_src';
    $backupInfo = null;
    $executedMigrations = [];

    try {
        github_download_zipball($repo, $branch, $token, $zipFile);

        if (!@mkdir($extractDir, 0775, true) && !is_dir($extractDir)) {
            throw new RuntimeException('Não foi possível preparar a pasta temporária da atualização.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException('Não foi possível abrir o pacote ZIP baixado do GitHub.');
        }
        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            throw new RuntimeException('Não foi possível extrair o pacote ZIP da atualização.');
        }
        $zip->close();

        $sourceRoot = updater_find_zip_root($extractDir);
        $remoteVersion = $check['remote_version'] ?? null;
        $versionFile = $sourceRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'version.php';
        if (is_file($versionFile)) {
            $versionContent = file_get_contents($versionFile);
            if ($versionContent && preg_match("/define\('APP_VERSION', '([^']+)'\)/", $versionContent, $matches)) {
                $remoteVersion = $matches[1];
            }
        }

        $backupInfo = updater_create_database_backup('update_pre');
        app_config_set_many([
            'github_last_backup_file' => $backupInfo['filename'],
            'github_last_backup_at' => $backupInfo['created_at'],
            'github_last_backup_size' => (string) $backupInfo['size'],
            'github_last_rollback_status' => 'nao_necessario',
            'github_last_rollback_at' => '',
            'github_last_rollback_file' => '',
            'github_last_rollback_details' => '',
        ]);

        try {
            $executedMigrations = updater_apply_database_migrations_from_source($sourceRoot);
        } catch (Throwable $migrationError) {
            $rollbackStatus = 'sucesso';
            $rollbackDetails = 'Rollback automático executado com sucesso.';
            $rollbackFile = $backupInfo['filename'] ?? '';
            $rollbackAt = date('Y-m-d H:i:s');

            try {
                updater_restore_database_backup($backupInfo['path']);
            } catch (Throwable $rollbackError) {
                $rollbackStatus = 'falhou';
                $rollbackDetails = 'Falha no rollback automático: ' . $rollbackError->getMessage();
            }

            app_config_set_many([
                'github_last_status' => $rollbackStatus === 'sucesso' ? 'erro_migracao_rollback_ok' : 'erro_migracao_rollback_falhou',
                'github_last_error' => $migrationError->getMessage(),
                'github_last_migrations' => json_encode($executedMigrations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'github_last_rollback_status' => $rollbackStatus,
                'github_last_rollback_at' => $rollbackAt,
                'github_last_rollback_file' => $rollbackFile,
                'github_last_rollback_details' => $rollbackDetails,
            ]);

            log_action('update_error', 'sistema', null, 'Falha em migração durante atualização via GitHub. Rollback automático: ' . $rollbackStatus . '. Backup: ' . $rollbackFile);

            throw new RuntimeException(
                'Falha ao aplicar as migrações do banco: ' . $migrationError->getMessage() .
                ' | Backup pré-atualização: ' . $rollbackFile .
                ' | Rollback automático: ' . $rollbackStatus .
                ($rollbackStatus !== 'sucesso' ? ' (' . $rollbackDetails . ')' : '')
            , 0, $migrationError);
        }

        updater_copy_tree($sourceRoot, APP_PATH);

        if ($remoteVersion) {
            app_config_set('app_versao', $remoteVersion);
        }
        app_config_set_many([
            'github_last_status' => 'atualizado',
            'github_last_update_at' => date('Y-m-d H:i:s'),
            'github_last_commit_sha' => $check['remote_sha'] ?? '',
            'github_last_remote_version' => $remoteVersion ?: app_current_version(),
            'github_last_migrations' => json_encode($executedMigrations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'github_last_error' => '',
            'github_last_rollback_status' => 'nao_necessario',
            'github_last_rollback_at' => '',
            'github_last_rollback_file' => '',
            'github_last_rollback_details' => '',
        ]);

        $migrationSummary = $executedMigrations
            ? (' | Banco atualizado: ' . implode(', ', array_map(static function ($item) { return $item['filename']; }, $executedMigrations)))
            : ' | Banco já estava alinhado';
        log_action('update', 'sistema', null, 'Atualização aplicada via GitHub ' . $repo . ' [' . ($branch ?: 'default') . '] | Backup prévio: ' . (($backupInfo['filename'] ?? 'n/d')) . $migrationSummary);

        return [
            'repo' => $repo,
            'branch' => $branch,
            'remote_sha' => $check['remote_sha'] ?? '',
            'remote_version' => $remoteVersion ?: app_current_version(),
            'updated_at' => date('Y-m-d H:i:s'),
            'database_migrations' => $executedMigrations,
            'database_backup' => $backupInfo,
        ];
    } catch (Throwable $e) {
        if ((string) app_config_get('github_last_status', '') === '') {
            app_config_set('github_last_status', 'erro');
        }
        app_config_set('github_last_error', $e->getMessage());
        throw $e;
    } finally {
        if (is_file($zipFile)) {
            @unlink($zipFile);
        }
        updater_remove_tree($extractDir);
    }
}

function updater_environment_status() {
    $dbOk = true;
    try {
        updater_migrations_ensure_table();
    } catch (Throwable $e) {
        $dbOk = false;
    }

    $backupDir = APP_PATH . '/storage/backups';
    $backupDirReady = (is_dir($backupDir) || @mkdir($backupDir, 0755, true)) && is_writable($backupDir);

    return [
        'zip' => class_exists('ZipArchive'),
        'curl' => function_exists('curl_init'),
        'gzip' => function_exists('gzopen'),
        'config_writable' => is_writable(APP_PATH . '/config'),
        'root_writable' => is_writable(APP_PATH),
        'backup_dir_writable' => $backupDirReady,
        'db_migration_ready' => $dbOk,
    ];
}

function psychology_default_consent_terms() {
    return [
        'consentimento_terapia_texto' => "Declaro que fui orientado(a) sobre a proposta terapêutica, objetivos do acompanhamento psicológico, limites éticos, sigilo profissional e forma de registro em prontuário.",
        'consentimento_tele_texto' => "Autorizo a realização de atendimentos psicológicos por teleatendimento quando aplicável, ciente das orientações de privacidade, ambiente reservado, contingências técnicas e limites do atendimento remoto.",
        'consentimento_privacidade_texto' => "Estou ciente sobre o tratamento dos meus dados pessoais e dados sensíveis para fins assistenciais, registro em prontuário, comunicação clínica e continuidade do cuidado, conforme a legislação aplicável.",
        'consentimento_compartilhamento_texto' => "Autorizo o compartilhamento de informações estritamente necessárias com responsáveis legais ou outros profissionais da rede de cuidado apenas quando houver indicação clínica, dever legal ou minha autorização específica.",
        'consentimento_gravacao_texto' => "Estou ciente de que gravações de sessões somente poderão ocorrer mediante autorização específica, finalidade definida e registro expresso deste consentimento.",
    ];
}

function psychology_prontuario_defaults() {
    return array_merge([
        'id' => 0,
        'empresa_id' => active_company_id(),
        'prontuario_id' => 0,
        'paciente_id' => 0,
        'profissional_id' => $_SESSION['user_id'] ?? 0,
        'tipo_registro' => 'sessao',
        'demanda_principal' => '',
        'historico_pessoal' => '',
        'historico_familiar' => '',
        'historico_psiquiatrico' => '',
        'uso_medicacao' => '',
        'fatores_risco' => '',
        'fatores_protecao' => '',
        'objetivo_terapeutico' => '',
        'queixa_historico' => '',
        'expectativas_tratamento' => '',
        'encaminhado_por' => '',
        'motivo_encaminhamento' => '',
        'historico_saude' => '',
        'uso_substancias' => '',
        'sono_apetite' => '',
        'suporte_rede' => '',
        'contexto_escolar_profissional' => '',
        'antecedentes_trauma_luto' => '',
        'humor_afeto' => '',
        'pensamento_linguagem' => '',
        'comportamento' => '',
        'cognicao_orientacao' => '',
        'estado_mental_aparencia' => '',
        'estado_mental_sensopercepcao' => '',
        'risco_suicida' => 'nao_identificado',
        'risco_autoheteroagressao' => '',
        'temas_sessao' => '',
        'intervencoes_realizadas' => '',
        'tecnicas_utilizadas' => '',
        'resposta_paciente' => '',
        'adesao_processo' => '',
        'recursos_mobilizados' => '',
        'manejo_crise' => '',
        'tarefas_entre_sessoes' => '',
        'encaminhamentos' => '',
        'plano_sessao_seguinte' => '',
        'observacoes_confidenciais' => '',
        'consentimento_tipo' => 'ambos',
        'consentimento_terapia_aceito' => 0,
        'consentimento_tele_aceito' => 0,
        'consentimento_privacidade_aceito' => 0,
        'consentimento_compartilhamento_aceito' => 0,
        'consentimento_gravacao_aceito' => 0,
        'consentimento_nome_responsavel' => '',
        'consentimento_documento' => '',
        'consentimento_parentesco' => '',
        'consentimento_testemunha' => '',
        'consentimento_registrado_em' => '',
    ], psychology_default_consent_terms());
}

function psychology_prontuario_load($prontuarioId) {
    $defaults = psychology_prontuario_defaults();
    if (!$prontuarioId || !db_table_exists('psicologia_prontuario')) {
        return $defaults;
    }

    try {
        $stmt = db()->prepare("SELECT * FROM psicologia_prontuario WHERE prontuario_id = ? AND empresa_id = ? LIMIT 1");
        $stmt->execute([(int) $prontuarioId, active_company_id()]);
        $row = $stmt->fetch();
        if (!$row) {
            return $defaults;
        }
        $data = array_merge($defaults, $row);
        foreach (['consentimento_terapia_texto','consentimento_tele_texto','consentimento_privacidade_texto','consentimento_compartilhamento_texto','consentimento_gravacao_texto'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                $data[$field] = $defaults[$field];
            }
        }
        return $data;
    } catch (Exception $e) {
        return $defaults;
    }
}

function psychology_plan_defaults() {
    return [
        'id' => 0,
        'empresa_id' => active_company_id(),
        'prontuario_id' => 0,
        'paciente_id' => 0,
        'profissional_id' => $_SESSION['user_id'] ?? 0,
        'abordagem' => '',
        'demanda_focal' => '',
        'objetivo_principal' => '',
        'metas' => '',
        'metas_curto_prazo' => '',
        'metas_medio_prazo' => '',
        'metas_longo_prazo' => '',
        'estrategias_intervencao' => '',
        'indicadores_evolucao' => '',
        'criterios_alta' => '',
        'risco_monitorado' => '',
        'frequencia_sugerida' => '',
        'status' => 'ativo',
        'data_inicio' => date('Y-m-d'),
        'data_reavaliacao' => '',
        'observacoes' => '',
    ];
}

function psychology_plan_load($pacienteId = 0, $prontuarioId = 0) {
    $defaults = psychology_plan_defaults();
    if (!db_table_exists('psicologia_planos')) {
        return $defaults;
    }

    try {
        if ($prontuarioId > 0 && db_column_exists('psicologia_planos', 'prontuario_id')) {
            $stmt = db()->prepare("SELECT * FROM psicologia_planos WHERE prontuario_id = ? AND empresa_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([(int) $prontuarioId, active_company_id()]);
            $row = $stmt->fetch();
            if ($row) {
                return array_merge($defaults, $row);
            }
        }

        if ($pacienteId > 0) {
            $stmt = db()->prepare("SELECT * FROM psicologia_planos WHERE empresa_id = ? AND paciente_id = ? ORDER BY atualizado_em DESC, id DESC LIMIT 1");
            $stmt->execute([active_company_id(), (int) $pacienteId]);
            $row = $stmt->fetch();
            if ($row) {
                return array_merge($defaults, $row);
            }
        }
    } catch (Exception $e) {
        return $defaults;
    }

    return $defaults;
}

function psychology_save_prontuario_detail($payload) {
    if (!db_table_exists('psicologia_prontuario')) {
        return 0;
    }

    $fields = [
        'empresa_id','prontuario_id','paciente_id','profissional_id','tipo_registro','demanda_principal','historico_pessoal','historico_familiar',
        'historico_psiquiatrico','uso_medicacao','fatores_risco','fatores_protecao','objetivo_terapeutico','queixa_historico',
        'expectativas_tratamento','encaminhado_por','motivo_encaminhamento','historico_saude','uso_substancias','sono_apetite',
        'suporte_rede','contexto_escolar_profissional','antecedentes_trauma_luto','humor_afeto','pensamento_linguagem','comportamento',
        'cognicao_orientacao','estado_mental_aparencia','estado_mental_sensopercepcao','risco_suicida','risco_autoheteroagressao',
        'temas_sessao','intervencoes_realizadas','tecnicas_utilizadas','resposta_paciente','adesao_processo','recursos_mobilizados',
        'manejo_crise','tarefas_entre_sessoes','encaminhamentos','plano_sessao_seguinte','observacoes_confidenciais','consentimento_tipo',
        'consentimento_terapia_texto','consentimento_tele_texto','consentimento_privacidade_texto','consentimento_compartilhamento_texto',
        'consentimento_gravacao_texto','consentimento_terapia_aceito','consentimento_tele_aceito','consentimento_privacidade_aceito',
        'consentimento_compartilhamento_aceito','consentimento_gravacao_aceito','consentimento_nome_responsavel','consentimento_documento',
        'consentimento_parentesco','consentimento_testemunha','consentimento_registrado_em'
    ];

    $data = psychology_prontuario_defaults();
    foreach ($fields as $field) {
        if (array_key_exists($field, $payload)) {
            $data[$field] = $payload[$field];
        }
    }

    foreach (['consentimento_terapia_aceito','consentimento_tele_aceito','consentimento_privacidade_aceito','consentimento_compartilhamento_aceito','consentimento_gravacao_aceito'] as $field) {
        $data[$field] = !empty($data[$field]) ? 1 : 0;
    }

    $data['empresa_id'] = active_company_id();
    clinic_validate_clinical_links((int) $data['paciente_id'], (int) ($payload['prontuario_id'] ?? 0));
    clinic_company_record('usuarios', (int) $data['profissional_id']);

    if (trim((string) $data['consentimento_registrado_em']) === '') {
        $data['consentimento_registrado_em'] = null;
    }

    $exists = db()->prepare("SELECT id FROM psicologia_prontuario WHERE prontuario_id = ? AND empresa_id = ? LIMIT 1");
    $exists->execute([(int) $data['prontuario_id'], active_company_id()]);
    $currentId = (int) ($exists->fetchColumn() ?: 0);

    if ($currentId > 0) {
        $set = [];
        $vals = [];
        foreach ($fields as $field) {
            $set[] = "`$field` = ?";
            $vals[] = $data[$field];
        }
        $vals[] = $currentId;
        $vals[] = active_company_id();
        db()->prepare("UPDATE psicologia_prontuario SET " . implode(',', $set) . ", atualizado_em = NOW() WHERE id = ? AND empresa_id = ?")
            ->execute($vals);
        return $currentId;
    }

    $cols = '`' . implode('`,`', $fields) . '`';
    $placeholders = rtrim(str_repeat('?,', count($fields)), ',');
    $vals = [];
    foreach ($fields as $field) {
        $vals[] = $data[$field];
    }
    db()->prepare("INSERT INTO psicologia_prontuario ($cols, criado_em, atualizado_em) VALUES ($placeholders, NOW(), NOW())")
        ->execute($vals);
    return (int) db()->lastInsertId();
}

function psychology_save_plan($payload) {
    if (!db_table_exists('psicologia_planos')) {
        return 0;
    }

    $signals = [
        trim((string) ($payload['objetivo_principal'] ?? '')),
        trim((string) ($payload['abordagem'] ?? '')),
        trim((string) ($payload['metas'] ?? '')),
        trim((string) ($payload['demanda_focal'] ?? '')),
        trim((string) ($payload['metas_curto_prazo'] ?? '')),
        trim((string) ($payload['metas_medio_prazo'] ?? '')),
        trim((string) ($payload['metas_longo_prazo'] ?? '')),
        trim((string) ($payload['estrategias_intervencao'] ?? '')),
        trim((string) ($payload['indicadores_evolucao'] ?? '')),
        trim((string) ($payload['criterios_alta'] ?? '')),
        trim((string) ($payload['risco_monitorado'] ?? '')),
    ];
    if (!array_filter($signals, static fn($v) => $v !== '')) {
        return 0;
    }

    $fields = ['empresa_id','paciente_id','profissional_id','abordagem','demanda_focal','objetivo_principal','metas','metas_curto_prazo','metas_medio_prazo','metas_longo_prazo','estrategias_intervencao','indicadores_evolucao','criterios_alta','risco_monitorado','frequencia_sugerida','status','data_inicio','data_reavaliacao','observacoes'];
    $data = psychology_plan_defaults();
    foreach ($fields as $field) {
        if (array_key_exists($field, $payload)) {
            $data[$field] = $payload[$field];
        }
    }

    $data['empresa_id'] = active_company_id();
    clinic_validate_clinical_links((int) $data['paciente_id'], (int) ($payload['prontuario_id'] ?? 0));
    clinic_company_record('usuarios', (int) $data['profissional_id']);

    if (trim((string) $data['data_reavaliacao']) === '') {
        $data['data_reavaliacao'] = null;
    }

    $planId = (int) ($payload['id'] ?? 0);
    if ($planId <= 0 && db_column_exists('psicologia_planos', 'prontuario_id') && !empty($payload['prontuario_id'])) {
        $stmt = db()->prepare("SELECT id FROM psicologia_planos WHERE prontuario_id = ? AND empresa_id = ? LIMIT 1");
        $stmt->execute([(int) $payload['prontuario_id'], active_company_id()]);
        $planId = (int) ($stmt->fetchColumn() ?: 0);
    }

    if ($planId > 0) {
        $owner = db()->prepare('SELECT paciente_id FROM psicologia_planos WHERE id=? AND empresa_id=?');
        $owner->execute([$planId, active_company_id()]);
        $ownedPatient = $owner->fetchColumn();
        if ($ownedPatient === false || (int) $ownedPatient !== (int) $data['paciente_id']) {
            throw new InvalidArgumentException('Plano terapêutico não pertence ao paciente da empresa ativa.');
        }
        $set = [];
        $vals = [];
        foreach ($fields as $field) {
            $set[] = "`$field` = ?";
            $vals[] = $data[$field];
        }
        if (db_column_exists('psicologia_planos', 'prontuario_id')) {
            $set[] = "`prontuario_id` = ?";
            $vals[] = (int) ($payload['prontuario_id'] ?? 0) ?: null;
        }
        $vals[] = $planId;
        $vals[] = active_company_id();
        db()->prepare("UPDATE psicologia_planos SET " . implode(',', $set) . ", atualizado_em = NOW() WHERE id = ? AND empresa_id = ?")
            ->execute($vals);
        return $planId;
    }

    $insertFields = $fields;
    if (db_column_exists('psicologia_planos', 'prontuario_id')) {
        $insertFields[] = 'prontuario_id';
        $data['prontuario_id'] = (int) ($payload['prontuario_id'] ?? 0) ?: null;
    }
    $cols = '`' . implode('`,`', $insertFields) . '`';
    $placeholders = rtrim(str_repeat('?,', count($insertFields)), ',');
    $vals = [];
    foreach ($insertFields as $field) {
        $vals[] = $data[$field] ?? null;
    }
    db()->prepare("INSERT INTO psicologia_planos ($cols, criado_em, atualizado_em) VALUES ($placeholders, NOW(), NOW())")
        ->execute($vals);
    return (int) db()->lastInsertId();
}

require_once __DIR__ . '/automation.php';

if (!function_exists('parse_money_br')) {
    function parse_money_br($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }
        $normalized = str_replace(['R$', ' '], '', $value);
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);
        return (float) $normalized;
    }
}

if (!function_exists('status_badge_class')) {
    function status_badge_class($status) {
        $map = [
            'agendado' => 'badge-info',
            'confirmado' => 'badge-success',
            'realizado' => 'badge-secondary',
            'faltou' => 'badge-warning',
            'cancelado' => 'badge-danger',
            'pendente' => 'badge-warning',
            'pago' => 'badge-success',
            'atrasado' => 'badge-danger',
            'ativo' => 'badge-success',
            'revogado' => 'badge-danger',
            'negado' => 'badge-secondary',
            'contatado' => 'badge-info',
            'agendado_fila' => 'badge-success',
        ];
        return $map[$status] ?? 'badge-secondary';
    }
}

if (!function_exists('audit_page_access')) {
    function audit_page_access() {
        if (!is_logged()) {
            return;
        }
        $route = trim((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($route === '' || str_contains($route, '/api/')) {
            return;
        }
        $bucket = date('YmdHi', floor(time() / 600) * 600);
        $key = 'audit_page_' . md5($route . '|' . $bucket . '|' . (string) ($_SESSION['user_id'] ?? '0'));
        if (!empty($_SESSION[$key])) {
            return;
        }
        $_SESSION[$key] = 1;
        log_action('acesso_pagina', 'rotas', null, $route);
    }
}

if (!function_exists('audit_entity_access')) {
    function audit_entity_access($action, $entity, $entityId, $description = '') {
        if (!is_logged()) {
            return;
        }
        $entityId = (int) $entityId;
        if ($entityId <= 0) {
            return;
        }
        $bucket = date('YmdHi', floor(time() / 300) * 300);
        $key = 'audit_entity_' . md5($action . '|' . $entity . '|' . $entityId . '|' . (string) ($_SESSION['user_id'] ?? '0') . '|' . $bucket);
        if (!empty($_SESSION[$key])) {
            return;
        }
        $_SESSION[$key] = 1;
        log_action($action, $entity, $entityId, $description);
    }
}

if (!function_exists('patient_longitudinal_timeline')) {
    function patient_longitudinal_timeline($pacienteId, $limit = 60) {
        $pacienteId = (int) $pacienteId;
        $limit = max(5, min(200, (int) $limit));
        $items = [];

        $stmt = db()->prepare("SELECT a.id, a.data_hora_inicio AS data_evento, a.status, a.valor, pr.nome AS procedimento_nome, u.nome AS profissional_nome
            FROM agendamentos a
            LEFT JOIN procedimentos pr ON pr.id = a.procedimento_id
            LEFT JOIN usuarios u ON u.id = a.profissional_id
            WHERE a.empresa_id = ? AND a.paciente_id = ?
            ORDER BY a.data_hora_inicio DESC
            LIMIT {$limit}");
        $stmt->execute([active_company_id(), $pacienteId]);
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $items[] = [
                'date' => $row['data_evento'],
                'type' => 'agenda',
                'icon' => 'bi-calendar-event',
                'class' => 'text-sky-600 bg-sky-50',
                'title' => ($row['procedimento_nome'] ?: 'Consulta') . ' · ' . ucfirst((string) $row['status']),
                'subtitle' => 'Profissional: ' . ($row['profissional_nome'] ?: '—') . ' · ' . money($row['valor'] ?? 0),
                'url' => APP_URL . '/modules/agenda/ver.php?id=' . (int) $row['id'],
            ];
        }

        $stmt = db()->prepare("SELECT pr.id, pr.data_atendimento AS data_evento, pr.queixa_principal, pr.hipotese_diagnostica, u.nome AS profissional_nome
            FROM prontuarios pr
            LEFT JOIN usuarios u ON u.id = pr.profissional_id
            WHERE pr.paciente_id = ?
            ORDER BY pr.data_atendimento DESC
            LIMIT {$limit}");
        $stmt->execute([$pacienteId]);
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $items[] = [
                'date' => $row['data_evento'],
                'type' => 'prontuario',
                'icon' => 'bi-journal-medical',
                'class' => 'text-violet-600 bg-violet-50',
                'title' => 'Registro clínico',
                'subtitle' => trim((string) ($row['queixa_principal'] ?: $row['hipotese_diagnostica'] ?: 'Sem resumo')) . ' · ' . ($row['profissional_nome'] ?: '—'),
                'url' => APP_URL . '/modules/prontuario/ver.php?id=' . (int) $row['id'],
            ];
        }

        if (db_table_exists('receitas')) {
            $stmt = db()->prepare("SELECT id, data_emissao AS data_evento, tipo AS tipo_documento, conteudo
                FROM receitas WHERE paciente_id = ?
                ORDER BY data_emissao DESC LIMIT {$limit}");
            $stmt->execute([$pacienteId]);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $items[] = [
                    'date' => $row['data_evento'],
                    'type' => 'receita',
                    'icon' => 'bi-prescription2',
                    'class' => 'text-emerald-600 bg-emerald-50',
                    'title' => ucfirst((string) ($row['tipo_documento'] ?: 'Documento clínico')),
                    'subtitle' => mb_substr(trim(strip_tags((string) ($row['conteudo'] ?? ''))), 0, 120),
                    'url' => APP_URL . '/modules/prontuario/receita_pdf.php?id=' . (int) $row['id'],
                ];
            }
        }

        if (db_table_exists('nfse')) {
            $stmt = db()->prepare("SELECT id, data_emissao AS data_evento, numero_nfse, numero_rps, status, valor_servico
                FROM nfse WHERE empresa_id = ? AND paciente_id = ?
                ORDER BY data_emissao DESC LIMIT {$limit}");
            $stmt->execute([active_company_id(), $pacienteId]);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $items[] = [
                    'date' => $row['data_evento'],
                    'type' => 'nfse',
                    'icon' => 'bi-receipt',
                    'class' => 'text-amber-600 bg-amber-50',
                    'title' => 'NFSe ' . ($row['numero_nfse'] ?: ('RPS ' . $row['numero_rps'])),
                    'subtitle' => ucfirst((string) $row['status']) . ' · ' . money($row['valor_servico'] ?? 0),
                    'url' => APP_URL . '/modules/nfse/index.php',
                ];
            }
        }

        if (db_table_exists('paciente_consentimentos')) {
            $stmt = db()->prepare("SELECT id, COALESCE(aceito_em, criado_em) AS data_evento, tipo, status, finalidade
                FROM paciente_consentimentos
                WHERE empresa_id = ? AND paciente_id = ?
                ORDER BY COALESCE(aceito_em, criado_em) DESC
                LIMIT {$limit}");
            $stmt->execute([active_company_id(), $pacienteId]);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $items[] = [
                    'date' => $row['data_evento'],
                    'type' => 'consentimento',
                    'icon' => 'bi-shield-check',
                    'class' => 'text-rose-600 bg-rose-50',
                    'title' => 'Consentimento · ' . ucfirst((string) $row['tipo']),
                    'subtitle' => ucfirst((string) $row['status']) . ' · ' . mb_substr((string) ($row['finalidade'] ?? ''), 0, 120),
                    'url' => APP_URL . '/modules/pacientes/ver.php?id=' . $pacienteId . '#consentimentos',
                ];
            }
        }

        usort($items, static function ($a, $b) {
            return strcmp((string) $b['date'], (string) $a['date']);
        });

        return array_slice($items, 0, $limit);
    }
}

if (!function_exists('protocol_tokenize')) {
    function protocol_tokenize($text) {
        $text = mb_strtolower(trim((string) $text), 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_values(array_filter($parts, static function ($token) {
            return mb_strlen($token, 'UTF-8') >= 3;
        }));
    }
}

if (!function_exists('protocol_context_suggestions')) {
    function protocol_context_suggestions($theme, $text, $limit = 5) {
        if (!db_table_exists('protocolos_clinicos')) {
            return [];
        }
        $theme = normalize_theme_choice($theme ?: get_theme());
        $tokens = protocol_tokenize($text);
        if (!$tokens) {
            return [];
        }
        $stmt = db()->prepare("SELECT * FROM protocolos_clinicos WHERE empresa_id = ? AND ativo = 1 AND tema = ? ORDER BY titulo");
        $stmt->execute([active_company_id(), $theme]);
        $protocolos = $stmt->fetchAll() ?: [];
        $ranked = [];
        foreach ($protocolos as $protocolo) {
            $haystack = mb_strtolower(implode(' ', [
                $protocolo['titulo'] ?? '',
                $protocolo['categoria'] ?? '',
                $protocolo['objetivo'] ?? '',
                $protocolo['checklist'] ?? '',
                $protocolo['criterios'] ?? '',
                $protocolo['orientacoes'] ?? '',
                $protocolo['tags'] ?? '',
            ]), 'UTF-8');
            $score = 0;
            foreach ($tokens as $token) {
                if (str_contains($haystack, $token)) {
                    $score += 2;
                }
                if (str_contains(mb_strtolower((string) ($protocolo['titulo'] ?? ''), 'UTF-8'), $token)) {
                    $score += 3;
                }
                if (str_contains(mb_strtolower((string) ($protocolo['tags'] ?? ''), 'UTF-8'), $token)) {
                    $score += 2;
                }
            }
            if ($score > 0) {
                $protocolo['_score'] = $score;
                $ranked[] = $protocolo;
            }
        }
        usort($ranked, static function ($a, $b) {
            return ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0);
        });
        return array_slice($ranked, 0, max(1, min(8, (int) $limit)));
    }
}

if (!function_exists('active_patient_consents')) {
    function active_patient_consents($pacienteId, $onlyActive = false) {
        if (!db_table_exists('paciente_consentimentos')) {
            return [];
        }
        $sql = "SELECT pc.*, u.nome AS registrado_por_nome
            FROM paciente_consentimentos pc
            LEFT JOIN usuarios u ON u.id = pc.criado_por
            WHERE pc.empresa_id = ? AND pc.paciente_id = ?";
        $params = [active_company_id(), (int) $pacienteId];
        if ($onlyActive) {
            $sql .= " AND pc.status = 'ativo'";
        }
        $sql .= " ORDER BY COALESCE(pc.aceito_em, pc.criado_em) DESC, pc.id DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }
}

if (!function_exists('agenda_waitlist_find_matches')) {
    function agenda_waitlist_find_matches($empresaId, $profissionalId, $slotStart, $slotEnd, $limit = 5) {
        if (!db_table_exists('agenda_fila_espera')) {
            return [];
        }
        $empresaId = (int) $empresaId;
        $profissionalId = (int) $profissionalId;
        $slotDate = date('Y-m-d', strtotime((string) $slotStart));
        $slotTime = date('H:i:s', strtotime((string) $slotStart));
        $limit = max(1, min(10, (int) $limit));
        $stmt = db()->prepare("SELECT f.*, p.nome AS paciente_nome, p.celular, p.email, pr.nome AS procedimento_nome, u.nome AS profissional_nome
            FROM agenda_fila_espera f
            JOIN pacientes p ON p.id = f.paciente_id
            LEFT JOIN procedimentos pr ON pr.id = f.procedimento_id
            LEFT JOIN usuarios u ON u.id = f.profissional_id
            WHERE f.empresa_id = ?
              AND f.status IN ('ativo','contatado')
              AND (f.profissional_id IS NULL OR f.profissional_id = ?)
              AND (f.data_preferencial IS NULL OR f.data_preferencial <= ?)
              AND (f.hora_inicio_preferencial IS NULL OR f.hora_inicio_preferencial <= ?)
              AND (f.hora_fim_preferencial IS NULL OR f.hora_fim_preferencial >= ?)
            ORDER BY FIELD(f.prioridade, 'urgente','alta','normal'), f.criado_em ASC
            LIMIT {$limit}");
        $stmt->execute([$empresaId, $profissionalId, $slotDate, $slotTime, $slotTime]);
        return $stmt->fetchAll() ?: [];
    }
}

if (!function_exists('agenda_waitlist_promote')) {
    function agenda_waitlist_promote($filaId, array $slot, $createdBy = null) {
        if (!db_table_exists('agenda_fila_espera')) {
            throw new RuntimeException('Fila de encaixe indisponível nesta instalação.');
        }
        $stmt = db()->prepare("SELECT * FROM agenda_fila_espera WHERE id = ? AND empresa_id = ? LIMIT 1");
        $stmt->execute([(int) $filaId, active_company_id()]);
        $fila = $stmt->fetch();
        if (!$fila) {
            throw new RuntimeException('Registro da fila não encontrado.');
        }
        if (!in_array($fila['status'], ['ativo','contatado'], true)) {
            throw new RuntimeException('Este registro da fila não está disponível para encaixe.');
        }
        $inicio = trim((string) ($slot['data_hora_inicio'] ?? ''));
        $fim = trim((string) ($slot['data_hora_fim'] ?? ''));
        $profissionalId = (int) ($slot['profissional_id'] ?? $fila['profissional_id'] ?? 0);
        if ($inicio === '' || $fim === '' || $profissionalId <= 0) {
            throw new RuntimeException('Dados do horário indisponíveis para o encaixe.');
        }
        $procedimentoId = (int) ($slot['procedimento_id'] ?? $fila['procedimento_id'] ?? 0) ?: null;
        $valor = isset($slot['valor']) ? (float) $slot['valor'] : 0.0;
        $obs = trim((string) ($slot['observacoes'] ?? ''));
        db()->prepare("INSERT INTO agendamentos (empresa_id, paciente_id, profissional_id, procedimento_id, data_hora_inicio, data_hora_fim, status, observacoes, valor, criado_por, confirmacao_status, encaixe_origem_fila_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([
                active_company_id(),
                (int) $fila['paciente_id'],
                $profissionalId,
                $procedimentoId,
                $inicio,
                $fim,
                'agendado',
                $obs !== '' ? $obs : ('Encaixe gerado a partir da fila #' . (int) $fila['id'] . (trim((string) ($fila['observacoes'] ?? '')) !== '' ? ' · ' . trim((string) $fila['observacoes']) : '')),
                $valor,
                $createdBy ?: ($_SESSION['user_id'] ?? null),
                'pendente',
                (int) $fila['id'],
            ]);
        $agendamentoId = (int) db()->lastInsertId();
        db()->prepare("UPDATE agenda_fila_espera SET status='agendado', agendamento_id=?, agendado_em=NOW(), atualizado_em=NOW() WHERE id=?")
            ->execute([$agendamentoId, (int) $fila['id']]);
        log_action('agenda_encaixe_fila', 'agendamentos', $agendamentoId, 'Encaixe criado a partir da fila de espera #' . (int) $fila['id']);
        return $agendamentoId;
    }
}

if (!function_exists('business_days_between')) {
    function business_days_between($startDate, $endDate, array $workingDays = [1,2,3,4,5]) {
        $start = new DateTime(date('Y-m-d', strtotime((string) $startDate)));
        $end = new DateTime(date('Y-m-d', strtotime((string) $endDate)));
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        $end = (clone $end)->modify('+1 day');
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        $count = 0;
        foreach ($period as $day) {
            $dow = (int) $day->format('N');
            if (in_array($dow, $workingDays, true)) {
                $count++;
            }
        }
        return $count;
    }
}

if (!function_exists('theoretical_schedule_capacity')) {
    function theoretical_schedule_capacity($startDate, $endDate, $empresaId = null) {
        $empresaId = (int) ($empresaId ?: active_company_id());
        $inicio = app_config_get('horario_inicio', '08:00', $empresaId);
        $fim = app_config_get('horario_fim', '18:00', $empresaId);
        $intervalo = max(5, (int) app_config_get('intervalo_consulta', '30', $empresaId));
        $diasAtendimento = array_map('intval', array_filter(explode(',', (string) app_config_get('dias_atendimento', '1,2,3,4,5', $empresaId))));
        if (!$diasAtendimento) {
            $diasAtendimento = [1,2,3,4,5];
        }
        $minutes = max(0, (strtotime('1970-01-01 ' . $fim) - strtotime('1970-01-01 ' . $inicio)) / 60);
        $slotsPerDay = max(1, (int) floor($minutes / $intervalo));
        $profissionais = db()->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ? AND ativo = 1 AND perfil IN ('admin','profissional')");
        $profissionais->execute([$empresaId]);
        $profissionaisAtivos = max(1, (int) $profissionais->fetchColumn());
        $diasUteis = business_days_between($startDate, $endDate, $diasAtendimento);
        return [
            'working_days' => $diasUteis,
            'slots_per_day' => $slotsPerDay,
            'active_professionals' => $profissionaisAtivos,
            'total_slots' => $diasUteis * $slotsPerDay * $profissionaisAtivos,
        ];
    }
}
