<?php
/**
 * Installer ClinicApp
 * Instalação guiada com opção de base vazia ou base de exemplo
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';

const INSTALL_LOCK_FILE = __DIR__ . '/config/install.lock';
const CONFIG_FILE = __DIR__ . '/config/config.php';
const VERSION_FILE = __DIR__ . '/config/version.php';
const GITHUB_FILE = __DIR__ . '/config/github.php';
const DB_CHARSET_DEFAULT = 'utf8mb4';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function starts_with(string $haystack, string $needle): bool
{
    return substr($haystack, 0, strlen($needle)) === $needle;
}

function ends_with(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }

    $needleLength = strlen($needle);
    if ($needleLength > strlen($haystack)) {
        return false;
    }

    return substr($haystack, -$needleLength) === $needle;
}

function current_app_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/installer.php'));
    $scriptDir = rtrim($scriptDir, '/');
    return $scheme . '://' . $host . ($scriptDir && $scriptDir !== '.' ? $scriptDir : '');
}

function installer_defaults(): array
{
    return [
        'db_host' => 'localhost',
        'db_name' => '',
        'db_user' => '',
        'db_pass' => '',
        'app_url' => current_app_url(),
        'install_mode' => 'empty',
        'seed_options' => [],
        'production' => '1',
        'timezone' => 'America/Sao_Paulo',
    ];
}

function read_installer_state(): array
{
    $defaults = installer_defaults();
    foreach ($defaults as $key => $value) {
        if ($key === 'seed_options') continue;
        if (isset($_POST[$key]) && is_scalar($_POST[$key])) {
            $defaults[$key] = $key === 'db_pass' ? (string) $_POST[$key] : trim((string) $_POST[$key]);
        }
    }
    $rawOptions = $_POST['seed_options'] ?? [];
    $defaults['seed_options'] = is_array($rawOptions) ? array_values(array_filter($rawOptions, 'is_string')) : [];
    return $defaults;
}

function validate_installer_input(array $data): array
{
    $errors = [];

    foreach (($data['seed_options'] ?? []) as $key) {
        if (!array_key_exists($key, installer_seed_catalog())) $errors[] = 'Opção de seed inválida.';
    }
    if ($data['db_host'] === '') {
        $errors[] = 'Informe o host do banco de dados.';
    }
    if ($data['db_name'] === '') {
        $errors[] = 'Informe o nome do banco de dados.';
    }
    if ($data['db_user'] === '') {
        $errors[] = 'Informe o usuário do banco de dados.';
    }
    if (!in_array($data['install_mode'], ['empty', 'demo', 'custom'], true)) {
        $errors[] = 'Selecione um modo de instalação válido.';
    }
    if (!filter_var($data['app_url'], FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $data['app_url']) || parse_url($data['app_url'], PHP_URL_QUERY) !== null || parse_url($data['app_url'], PHP_URL_FRAGMENT) !== null || parse_url($data['app_url'], PHP_URL_USER) !== null) {
        $errors[] = 'Informe uma URL da aplicação válida, começando com http:// ou https://.';
    }
    foreach (['db_host', 'db_name'] as $field) {
        if (preg_match('/[;\x00-\x1F]/', $data[$field])) $errors[] = 'Host ou nome de banco inválido.';
    }
    if (!in_array($data['timezone'], timezone_identifiers_list(), true)) {
        $errors[] = 'Informe um timezone válido.';
    }

    return $errors;
}

function pdo_options(): array
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
        $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
    }

    if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
        $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
    }

    return $options;
}

function pdo_server(string $host, string $user, string $pass): PDO
{
    $dsn = 'mysql:host=' . $host . ';charset=' . DB_CHARSET_DEFAULT;
    return new PDO($dsn, $user, $pass, pdo_options());
}

function pdo_database(string $host, string $dbName, string $user, string $pass): PDO
{
    $dsn = 'mysql:host=' . $host . ';dbname=' . $dbName . ';charset=' . DB_CHARSET_DEFAULT;
    return new PDO($dsn, $user, $pass, pdo_options());
}

function normalize_sql(string $sql): string
{
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);
    $sql = preg_replace('~/\*.*?\*/~s', '', $sql) ?? $sql;

    $filteredLines = [];
    foreach (explode("\n", $sql) as $line) {
        $trim = ltrim($line);
        if ($trim === '') {
            $filteredLines[] = $line;
            continue;
        }
        if (starts_with($trim, '--') || starts_with($trim, '#')) {
            continue;
        }
        $filteredLines[] = $line;
    }

    return implode("\n", $filteredLines);
}

function split_sql_statements(string $sql): array
{
    $sql = normalize_sql($sql);
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

            if (!$inSingle && !$inDouble && ends_with($buffer, $delimiter)) {
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

function statement_starts_with_resultset_keyword(string $statement): bool
{
    $keyword = strtoupper((string) strtok(ltrim($statement), " \t\r\n"));
    return in_array($keyword, ['SELECT', 'SHOW', 'DESCRIBE', 'EXPLAIN', 'CALL'], true);
}

function execute_sql_statement(PDO $pdo, string $statement): void
{
    if (statement_starts_with_resultset_keyword($statement)) {
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

function import_sql_file(PDO $pdo, string $path): int
{
    if (!is_file($path)) {
        throw new RuntimeException('Arquivo SQL não encontrado: ' . basename($path));
    }

    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Não foi possível ler o arquivo SQL: ' . basename($path));
    }

    $previousForeignKeys = (int) $pdo->query('SELECT @@SESSION.FOREIGN_KEY_CHECKS')->fetchColumn();
    $previousMode = (string) $pdo->query('SELECT @@SESSION.SQL_MODE')->fetchColumn();
    $count = 0;
    foreach (split_sql_statements($sql) as $statement) {
        try {
            execute_sql_statement($pdo, $statement);
            $count++;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $pdo->exec('SET SESSION FOREIGN_KEY_CHECKS=' . $previousForeignKeys);
            $pdo->exec('SET SESSION SQL_MODE=' . $pdo->quote($previousMode));
            $preview = preg_replace('/\s+/', ' ', trim(substr($statement, 0, 220))) ?? 'instrução SQL';
            throw new RuntimeException(
                'Erro ao importar ' . basename($path) . ': ' . $e->getMessage() . ' | Trecho: ' . $preview,
                0,
                $e
            );
        }
    }

    return $count;
}

function installer_seed_catalog(): array
{
    return [
        'profissionais' => ['label' => 'Profissionais das 10 especialidades e recepção', 'deps' => []],
        'catalogos' => ['label' => 'Catálogos: CID, medicamentos, exames, procedimentos e categorias', 'deps' => []],
        'modelos' => ['label' => 'Modelos clínicos de todas as especialidades', 'deps' => []],
        'protocolos' => ['label' => 'Protocolos clínicos por especialidade', 'deps' => []],
        'pacientes' => ['label' => 'Pacientes fictícios de todas as especialidades', 'deps' => []],
        'atendimentos' => ['label' => 'Demonstração de agenda, prontuários, odontograma, cuidados e financeiro', 'deps' => ['profissionais','catalogos','pacientes']],
    ];
}

function effective_seed_options(array $selected): array
{
    $catalog = installer_seed_catalog();
    $chosen = [];
    foreach ($selected as $key) {
        if (!is_string($key) || !isset($catalog[$key])) throw new InvalidArgumentException('Opção de seed inválida.');
        $chosen[$key] = true;
        foreach ($catalog[$key]['deps'] as $dep) $chosen[$dep] = true;
    }
    return array_values(array_filter(array_keys($catalog), static function ($key) use ($chosen) { return isset($chosen[$key]); }));
}

function sql_stack(string $mode, array $selected = []): array
{
    $files = [__DIR__ . '/sql/clinicapp_banco_geral.sql'];
    if ($mode === 'demo') {
        $files[] = __DIR__ . '/sql/clinicapp_seeds_completos.sql';
    } else {
        $files[] = __DIR__ . '/sql/seed_instalacao_minima.sql';
        if ($mode === 'custom') {
            foreach (effective_seed_options($selected) as $key) $files[] = __DIR__ . '/sql/seeds_instalacao/' . $key . '.sql';
        }
    }
    return $files;
}

function installer_upgrade_manifest(): array
{
    $manifestPath = __DIR__ . '/sql/install_manifest.json';
    $manifest = is_readable($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : null;
    if (!is_array($manifest) || empty($manifest['sources']) || empty($manifest['tables'])) throw new RuntimeException('Manifesto de upgrades ausente ou inválido. Reenvie o ZIP completo.');
    $actual = array_merge(['database.sql'], array_map('basename', glob(__DIR__ . '/sql/upgrade_v*.sql') ?: []));
    $registered = array_keys($manifest['sources']);
    sort($actual); sort($registered);
    if ($actual !== $registered) throw new RuntimeException('Há upgrades não consolidados ou arquivos faltando. Atualize o pacote completo antes de instalar.');
    foreach ($manifest['sources'] as $name => $hash) {
        if (basename($name) !== $name || !is_file(__DIR__ . '/sql/' . $name) || !hash_equals($hash, hash_file('sha256', __DIR__ . '/sql/' . $name))) throw new RuntimeException('Upgrade alterado ou inválido: ' . basename($name) . '. Reenvie o ZIP completo.');
    }
    if (!hash_equals($manifest['schema_sha256'], hash_file('sha256', __DIR__ . '/sql/clinicapp_banco_geral.sql'))) throw new RuntimeException('A estrutura consolidada não corresponde ao manifesto de upgrades.');
    return $manifest;
}

function verify_installed_schema(PDO $pdo, array $manifest): void
{
    foreach ($manifest['tables'] as $table => $expected) {
        if (!preg_match('/^[a-z0-9_]+$/i', $table)) throw new RuntimeException('Tabela inválida no manifesto.');
        $columns = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
        if (array_diff($expected['columns'], $columns)) throw new RuntimeException('Colunas de upgrades ausentes em ' . $table);
        $indexRows = $pdo->query('SHOW INDEX FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
        $indexes = array_column($indexRows, 'Key_name');
        if (array_diff($expected['indexes'], $indexes)) throw new RuntimeException('Índices de upgrades ausentes em ' . $table);
        $fkStmt = $pdo->prepare("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_TYPE='FOREIGN KEY'");
        $fkStmt->execute([$table]);
        if (array_diff($expected['foreign_keys'], $fkStmt->fetchAll(PDO::FETCH_COLUMN))) throw new RuntimeException('Relacionamentos de upgrades ausentes em ' . $table);
    }
}

function generate_config(array $data): string
{
    $production = $data['production'] === '1' ? 'true' : 'false';

    return "<?php\n"
        . "/**\n"
        . " * Arquivo de Configuração Principal\n"
        . " * ClinicApp - Sistema de Gestão Médica/Odontológica\n"
        . " * Gerado automaticamente pelo installer.php\n"
        . " */\n\n"
        . "require_once dirname(__DIR__) . '/includes/session.php';\n\n"
        . "if (!ob_get_level()) {\n"
        . "    ob_start();\n"
        . "}\n\n"
        . "define('DB_HOST', '" . addslashes($data['db_host']) . "');\n"
        . "define('DB_NAME', '" . addslashes($data['db_name']) . "');\n"
        . "define('DB_USER', '" . addslashes($data['db_user']) . "');\n"
        . "define('DB_PASS', '" . addslashes($data['db_pass']) . "');\n"
        . "define('DB_CHARSET', 'utf8mb4');\n\n"
        . "define('APP_NAME', 'ClinicApp');\n"
        . "if (is_file(__DIR__ . '/version.php')) {\n"
        . "    require_once __DIR__ . '/version.php';\n"
        . "}\n"
        . "if (is_file(__DIR__ . '/github.php')) {\n"
        . "    require_once __DIR__ . '/github.php';\n"
        . "}\n"
        . "if (!defined('APP_VERSION')) {\n"
        . "    define('APP_VERSION', '1.0.2');\n"
        . "}\n"
        . "define('APP_URL', '" . addslashes(rtrim($data['app_url'], '/')) . "');\n"
        . "define('APP_PATH', dirname(__DIR__));\n"
        . "define('UPLOAD_PATH', APP_PATH . '/assets/uploads');\n"
        . "define('UPLOAD_URL', APP_URL . '/assets/uploads');\n\n"
        . "define('SESSION_TIMEOUT', 7200);\n"
        . "define('HASH_COST', 10);\n"
        . "define('CSRF_TOKEN_NAME', '_token');\n\n"
        . "define('ASTEN_URL_HOMOLOG', 'https://homologacao.asten.com.br/nfse/ws/');\n"
        . "define('ASTEN_URL_PROD', 'https://nfse.asten.com.br/ws/');\n"
        . "define('ASTEN_TIMEOUT', 30);\n\n"
        . "define('SMTP_HOST', 'smtp.hostinger.com');\n"
        . "define('SMTP_PORT', 587);\n"
        . "define('SMTP_USER', 'no-reply@seudominio.com.br');\n"
        . "define('SMTP_PASS', 'SuaSenhaEmail');\n"
        . "define('SMTP_FROM_NAME', 'ClinicApp');\n"
        . "define('SMTP_SECURE', 'tls');\n\n"
        . "date_default_timezone_set('" . addslashes($data['timezone']) . "');\n\n"
        . "\$IS_PRODUCTION = " . $production . ";\n"
        . "if (\$IS_PRODUCTION) {\n"
        . "    error_reporting(0);\n"
        . "    ini_set('display_errors', 0);\n"
        . "} else {\n"
        . "    error_reporting(E_ALL);\n"
        . "    ini_set('display_errors', 1);\n"
        . "}\n\n"
        . "function db() {\n"
        . "    static \$pdo = null;\n"
        . "    if (\$pdo === null) {\n"
        . "        try {\n"
        . "            \$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;\n"
        . "            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, [\n"
        . "                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n"
        . "                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n"
        . "                PDO::ATTR_EMULATE_PREPARES => false\n"
        . "            ]);\n"
        . "        } catch (PDOException \$e) {\n"
        . "            die('Erro de conexão com o banco de dados. Verifique as credenciais em config/config.php');\n"
        . "        }\n"
        . "    }\n"
        . "    return \$pdo;\n"
        . "}\n";
}

function generate_version_file(): string
{
    return is_file(VERSION_FILE) ? (string) file_get_contents(VERSION_FILE) : "<?php\nif (!defined('APP_VERSION')) define('APP_VERSION', '1.2.0');\n";
}

function generate_github_file(): string
{
    return "<?php\n"
        . "if (!defined('GITHUB_UPDATE_REPO')) {\n"
        . "    define('GITHUB_UPDATE_REPO', 'leirson/Clinicapp');\n"
        . "}\n"
        . "if (!defined('GITHUB_UPDATE_BRANCH')) {\n"
        . "    define('GITHUB_UPDATE_BRANCH', 'main');\n"
        . "}\n"
        . "if (!defined('GITHUB_UPDATE_TOKEN')) {\n"
        . "    define('GITHUB_UPDATE_TOKEN', '');\n"
        . "}\n";
}

function ensure_upload_dirs(): void
{
    $dirs = [
        __DIR__ . '/assets/uploads',
        __DIR__ . '/assets/uploads/anexos',
        __DIR__ . '/assets/uploads/avatars',
        __DIR__ . '/assets/uploads/prontuarios',
        __DIR__ . '/assets/uploads/exames',
        __DIR__ . '/storage',
        __DIR__ . '/storage/backups',
        __DIR__ . '/storage/imports',
        __DIR__ . '/storage/site',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('Não foi possível criar a pasta: ' . basename($dir));
        }
        if (!is_writable($dir)) throw new RuntimeException('Pasta sem permissão de escrita: ' . str_replace(__DIR__ . '/', '', $dir));
    }
}

function write_install_lock(array $data): void
{
    $payload = [
        'installed_at' => date('c'),
        'app_url' => rtrim($data['app_url'], '/'),
        'install_mode' => $data['install_mode'],
        'db_name' => $data['db_name'],
        'seed_options' => $data['install_mode'] === 'custom' ? effective_seed_options($data['seed_options'] ?? []) : [],
        'upgrade_max' => installer_upgrade_manifest()['latest_upgrade'],
    ];
    if (file_put_contents(INSTALL_LOCK_FILE, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
        throw new RuntimeException('Não foi possível gravar a trava config/install.lock. Verifique as permissões.');
    }
}

function backup_existing_config(): ?string
{
    if (!is_file(CONFIG_FILE)) {
        return null;
    }

    $backupFile = __DIR__ . '/config/config.backup_' . date('Ymd_His') . '.php';
    if (!copy(CONFIG_FILE, $backupFile)) {
        throw new RuntimeException('Não foi possível criar backup do config/config.php atual.');
    }
    return basename($backupFile);
}

$errors = [];
$success = false;
$messages = [];
$state = read_installer_state();
$lockExists = is_file(INSTALL_LOCK_FILE);
if ($lockExists) {
    http_response_code(403);
    exit('Instalação bloqueada: config/install.lock já existe. Para manutenção, utilize o painel autenticado.');
}
if (empty($_SESSION['installer_token'])) $_SESSION['installer_token'] = bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = $_POST['installer_token'] ?? null;
    if (!is_string($sent) || !hash_equals($_SESSION['installer_token'], $sent)) {
        http_response_code(403);
        exit('Token de instalação inválido. Recarregue a página.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validate_installer_input($state);

    if (!$errors) {
        $installMutex = null;
        try {
            if (!is_dir(__DIR__ . '/config') || !is_writable(__DIR__ . '/config')) {
                throw new RuntimeException('A pasta config/ precisa existir e ter permissão de escrita.');
            }

            if (!extension_loaded('pdo_mysql') || !extension_loaded('mbstring') || !extension_loaded('fileinfo')) {
                throw new RuntimeException('Ative as extensões PHP pdo_mysql, mbstring e fileinfo na hospedagem.');
            }
            foreach (sql_stack($state['install_mode'], $state['seed_options']) as $sqlFile) {
                if (!is_file($sqlFile) || !is_readable($sqlFile)) throw new RuntimeException('SQL obrigatório ausente ou sem leitura: ' . basename($sqlFile));
            }
            $upgradeManifest = installer_upgrade_manifest();
            ensure_upload_dirs();
            $installMutex = fopen(__DIR__ . '/config/install.running', 'c');
            if (!$installMutex || !flock($installMutex, LOCK_EX | LOCK_NB)) throw new RuntimeException('Outra instalação está em andamento. Aguarde sua conclusão.');
            if (is_file(INSTALL_LOCK_FILE)) throw new RuntimeException('A instalação já foi concluída nesta aplicação.');
            @set_time_limit(300);

            $backup = backup_existing_config();
            if ($backup) {
                $messages[] = 'Backup do config atual criado: ' . $backup;
            }

            try {
                $pdo = pdo_database($state['db_host'], $state['db_name'], $state['db_user'], $state['db_pass']);
                $messages[] = 'Conexão com o banco existente validada.';
            } catch (PDOException $connectionError) {
                if ((int) ($connectionError->errorInfo[1] ?? 0) !== 1049) {
                    throw new RuntimeException('Não foi possível conectar ao banco. Confira host, nome, usuário, senha e permissões no painel da hospedagem.', 0, $connectionError);
                }
                try {
                    $pdoServer = pdo_server($state['db_host'], $state['db_user'], $state['db_pass']);
                    $dbNameQuoted = '`' . str_replace('`', '``', $state['db_name']) . '`';
                    $pdoServer->exec('CREATE DATABASE ' . $dbNameQuoted . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                    $pdo = pdo_database($state['db_host'], $state['db_name'], $state['db_user'], $state['db_pass']);
                    $messages[] = 'Novo banco criado.';
                } catch (PDOException $creationError) {
                    throw new RuntimeException('Crie um banco vazio no painel da hospedagem, vincule o usuário com permissões e informe os dados neste instalador.', 0, $creationError);
                }
            }
            if ($pdo->query('SHOW TABLES')->fetchColumn() !== false) {
                throw new RuntimeException('Instalação permitida somente em banco vazio. Selecione um novo banco; seus dados existentes não foram alterados.');
            }
            foreach (sql_stack($state['install_mode'], $state['seed_options']) as $file) {
                $executed = import_sql_file($pdo, $file);
                $messages[] = basename($file) . ' importado com ' . $executed . ' instrução(ões).';
                if (basename($file) === 'clinicapp_banco_geral.sql') verify_installed_schema($pdo, $upgradeManifest);
            }

            $messages[] = 'Upgrades v2 até v' . $upgradeManifest['latest_upgrade'] . ' conferidos: arquivos, tabelas, colunas, índices e relacionamentos.';
            if ($state['install_mode'] === 'custom') {
                $applied = effective_seed_options($state['seed_options']);
                $messages[] = 'Seeds aplicados: ' . ($applied ? implode(', ', $applied) : 'somente carga mínima obrigatória') . '. Dependências incluídas automaticamente.';
            }
            $configContent = generate_config($state);
            if (file_put_contents(CONFIG_FILE, $configContent) === false) {
                throw new RuntimeException('Falha ao gravar config/config.php.');
            }
            if (file_put_contents(VERSION_FILE, generate_version_file()) === false) {
                throw new RuntimeException('Falha ao gravar config/version.php.');
            }
            if (file_put_contents(GITHUB_FILE, generate_github_file()) === false) {
                throw new RuntimeException('Falha ao gravar config/github.php.');
            }
            $messages[] = 'Arquivos config/config.php, config/version.php e config/github.php gerados com sucesso.';

            ensure_upload_dirs();
            write_install_lock($state);

            $messages[] = $state['install_mode'] === 'demo'
                ? 'Base de exemplo instalada com UMA empresa e profissionais das 10 especialidades. Login demo principal: admin.demo@clinicapp.com / 123456.'
                : 'Instalação mínima/personalizada concluída. Login inicial: admin@clinicapp.com / admin123.';
            $messages[] = 'Por segurança, remova ou renomeie os arquivos install.php e installer.php após a instalação.';
            $success = true;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        } finally {
            if (is_resource($installMutex)) {
                flock($installMutex, LOCK_UN);
                fclose($installMutex);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador ClinicApp</title>
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #1d4ed8;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --success: #166534;
            --success-bg: #dcfce7;
            --danger: #991b1b;
            --danger-bg: #fee2e2;
            --warning: #854d0e;
            --warning-bg: #fef3c7;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #e0f2fe 0%, #f8fafc 35%, #eef2ff 100%);
            color: var(--text);
            min-height: 100vh;
        }
        .wrap {
            max-width: 980px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .card {
            background: var(--card);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.10);
            border: 1px solid rgba(226, 232, 240, 0.8);
            overflow: hidden;
        }
        .hero {
            padding: 32px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        .hero h1 {
            margin: 0 0 10px;
            font-size: 32px;
        }
        .hero p {
            margin: 0;
            color: rgba(255,255,255,0.9);
            line-height: 1.6;
        }
        .content {
            padding: 28px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .full { grid-column: 1 / -1; }
        label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 15px;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
            background: #fff;
        }
        input:focus,
        select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12);
        }
        .hint {
            color: var(--muted);
            font-size: 13px;
            margin-top: 6px;
            line-height: 1.5;
        }
        .radios {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .radio-card {
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 16px;
            cursor: pointer;
            transition: all .2s ease;
            background: #fff;
        }
        .radio-card:hover {
            border-color: var(--primary);
            transform: translateY(-1px);
        }
        .radio-card input {
            margin-right: 8px;
        }
        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 14px;
            padding: 14px 20px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            box-shadow: 0 12px 28px rgba(14, 165, 233, 0.22);
        }
        .alert {
            border-radius: 16px;
            padding: 16px 18px;
            margin-bottom: 18px;
            border: 1px solid transparent;
        }
        .alert-danger {
            background: var(--danger-bg);
            color: var(--danger);
            border-color: #fecaca;
        }
        .alert-success {
            background: var(--success-bg);
            color: var(--success);
            border-color: #bbf7d0;
        }
        .alert-warning {
            background: var(--warning-bg);
            color: var(--warning);
            border-color: #fde68a;
        }
        ul.clean {
            margin: 10px 0 0 18px;
            padding: 0;
        }
        .lock-note {
            margin-bottom: 20px;
        }
        .footer-note {
            margin-top: 24px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }
        @media (max-width: 760px) {
            .grid, .radios { grid-template-columns: 1fr; }
            .hero { padding: 24px; }
            .content { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="hero">
                <h1>Instalador do ClinicApp</h1>
                <p>Informe os dados do banco, escolha se deseja iniciar com base vazia ou base de exemplo e conclua a instalação em uma única tela.</p>
            </div>
            <div class="content">
                <?php if ($lockExists && !$success): ?>
                    <div class="alert alert-warning lock-note">
                        <strong>Atenção:</strong> já existe um arquivo de instalação concluída em <code>config/install.lock</code>.
                        A reinstalação está bloqueada para proteger os dados existentes.
                    </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <strong>Não foi possível concluir a instalação:</strong>
                        <ul class="clean">
                            <?php foreach ($errors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <strong>Instalação concluída com sucesso.</strong>
                        <ul class="clean">
                            <?php foreach ($messages as $message): ?>
                                <li><?= h($message) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="actions">
                        <a class="btn btn-primary" href="<?= h(rtrim($state['app_url'], '/')) ?>/login.php">Ir para o login</a>
                    </div>
                <?php else: ?>
                    <form method="POST" novalidate>
<input type="hidden" name="installer_token" value="<?= h($_SESSION['installer_token']) ?>">
                        <div class="grid">
                            <div>
                                <label for="db_host">Host do banco</label>
                                <input id="db_host" name="db_host" type="text" value="<?= h($state['db_host']) ?>" placeholder="localhost">
                            </div>
                            <div>
                                <label for="db_name">Nome do banco</label>
                                <input id="db_name" name="db_name" type="text" value="<?= h($state['db_name']) ?>" placeholder="u123456789_clinicapp">
                            </div>
                            <div>
                                <label for="db_user">Usuário do banco</label>
                                <input id="db_user" name="db_user" type="text" value="<?= h($state['db_user']) ?>" placeholder="u123456789_admin">
                            </div>
                            <div>
                                <label for="db_pass">Senha do banco</label>
                                <input id="db_pass" name="db_pass" type="password" value="<?= h($state['db_pass']) ?>" placeholder="Sua senha do MySQL">
                            </div>
                            <div class="full">
                                <label for="app_url">URL da aplicação</label>
                                <input id="app_url" name="app_url" type="text" value="<?= h($state['app_url']) ?>" placeholder="https://seudominio.com.br">
                                <div class="hint">Informe a URL pública do sistema, sem barra no final. Ex.: https://seudominio.com.br ou https://seudominio.com.br/medic</div>
                            </div>
                            <div>
                                <label for="timezone">Timezone</label>
                                <input id="timezone" name="timezone" type="text" value="<?= h($state['timezone']) ?>" placeholder="America/Sao_Paulo">
                            </div>
                            <div>
                                <label for="production">Modo da aplicação</label>
                                <select id="production" name="production">
                                    <option value="1" <?= $state['production'] === '1' ? 'selected' : '' ?>>Produção</option>
                                    <option value="0" <?= $state['production'] === '0' ? 'selected' : '' ?>>Desenvolvimento / debug</option>
                                </select>
                            </div>
                            <div class="full">
                                <label>Tipo de instalação</label>
                                <div class="radios">
                                    <label class="radio-card">
                                        <input type="radio" name="install_mode" value="empty" <?= $state['install_mode'] === 'empty' ? 'checked' : '' ?>>
                                        <strong>Instalação mínima — sem dados demo</strong>
                                        <div class="hint">Cria estrutura, uma empresa, administrador/desenvolvedor e licença, sem pacientes, catálogos ou modelos demo. Login: <strong>admin@clinicapp.com / admin123</strong>.</div>
                                    </label>
                                    <label class="radio-card">
                                        <input type="radio" name="install_mode" value="demo" <?= $state['install_mode'] === 'demo' ? 'checked' : '' ?>>
                                        <strong>Banco de exemplo</strong>
                                        <div class="hint">Importa estrutura + dados de demonstração com especialidades, protocolos, modelos, cuidados e agenda. Login demo principal: <strong>admin.demo@clinicapp.com / 123456</strong>.</div>
                                    </label>
                                    <label class="radio-card">
                                        <input type="radio" name="install_mode" value="custom" <?= $state['install_mode'] === 'custom' ? 'checked' : '' ?>>
                                        <strong>Personalizada — escolher seeds</strong>
                                        <div class="hint">Carga mínima obrigatória + somente os grupos selecionados abaixo. Login: admin@clinicapp.com / admin123.</div>
                                    </label>
                                </div>
                                <fieldset style="margin-top:16px;padding:16px;border:1px solid #cbd5e1;border-radius:10px">
                                    <legend>Seeds opcionais — usados somente na instalação personalizada</legend>
                                    <?php foreach (installer_seed_catalog() as $seedKey => $seedInfo): ?>
                                    <label style="display:block;margin:10px 0;font-weight:400">
                                        <input type="checkbox" name="seed_options[]" onchange="if(this.checked) document.querySelector('input[name=install_mode][value=custom]').checked=true" value="<?= h($seedKey) ?>" <?= in_array($seedKey, $state['seed_options'], true) ? 'checked' : '' ?>>
                                        <?= h($seedInfo['label']) ?>
                                    </label>
                                    <?php endforeach; ?>
                                    <p class="hint">Atendimentos exige profissionais, catálogos e pacientes: esses grupos serão incluídos automaticamente. Empresa, administrador, licença e configurações são sempre necessários. Profissionais demo usam senha 123456.</p>
                                </fieldset>
                                <p class="hint">Estrutura completa até v15 em qualquer modo. O instalador confere os arquivos de upgrades e os elementos criados no banco antes de concluir.</p>
                            </div>
                        </div>

                        <div class="actions">
                            <button class="btn btn-primary" type="submit">Instalar agora</button>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="footer-note">
                    <strong>Observações:</strong><br>
                    • O instalador cria o banco caso ele ainda não exista.<br>
                    • Importa a estrutura consolidada, já contendo os upgrades até v15, confere sua integridade e aplica somente os seeds do modo escolhido.<br>
                    • Após concluir, remova ou renomeie este arquivo por segurança.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
