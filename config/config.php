<?php
/**
 * Arquivo de Configuração Principal
 * ClinicApp - Sistema de Gestão Médica/Odontológica
 */

// Iniciar sessão se ainda não iniciada
require_once dirname(__DIR__) . '/includes/session.php';

// Buffer de saída para evitar erro "headers already sent"
// Útil quando algum módulo processa POST e redireciona após carregar partes do layout.
if (!ob_get_level()) {
    ob_start();
}

// ========== BANCO DE DADOS ==========
// Ajuste para suas credenciais do Hostinger
define('DB_HOST', 'localhost');
define('DB_NAME', 'u000000000_clinicapp');   // Ex: u123456789_clinicapp
define('DB_USER', 'u000000000_admin');       // Ex: u123456789_admin
define('DB_PASS', 'SuaSenhaSegura123');
define('DB_CHARSET', 'utf8mb4');

// ========== APLICAÇÃO ==========
define('APP_NAME', 'ClinicApp');
if (is_file(__DIR__ . '/version.php')) {
    require_once __DIR__ . '/version.php';
}
if (is_file(__DIR__ . '/github.php')) {
    require_once __DIR__ . '/github.php';
}
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.1.3');
}
define('APP_URL', 'https://seudominio.com.br'); // Sem barra no final
define('APP_PATH', dirname(__DIR__));
define('UPLOAD_PATH', APP_PATH . '/assets/uploads');
define('UPLOAD_URL', APP_URL . '/assets/uploads');

// ========== SEGURANÇA ==========
define('SESSION_TIMEOUT', 7200); // 2 horas
define('HASH_COST', 10);
define('CSRF_TOKEN_NAME', '_token');

// ========== NFSe Nacional / compatibilidade SOAP ==========
// Endpoints (homologação e produção - ajustar conforme sua prefeitura)
define('ASTEN_URL_HOMOLOG', 'https://homologacao.asten.com.br/nfse/ws/');
define('ASTEN_URL_PROD', 'https://nfse.asten.com.br/ws/');
define('ASTEN_TIMEOUT', 30);

// ========== E-MAIL (SMTP) ==========
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'no-reply@seudominio.com.br');
define('SMTP_PASS', 'SuaSenhaEmail');
define('SMTP_FROM_NAME', 'ClinicApp');
define('SMTP_SECURE', 'tls');

// ========== TIMEZONE ==========
date_default_timezone_set('America/Sao_Paulo');

// ========== ERRO ==========
// Em produção: 0  | Em dev: E_ALL
$IS_PRODUCTION = true;
if ($IS_PRODUCTION) {
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ========== CONEXÃO PDO ==========
function db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die('Erro de conexão com o banco de dados. Verifique as credenciais em config/config.php');
        }
    }
    return $pdo;
}
