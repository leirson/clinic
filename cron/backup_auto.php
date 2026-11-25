<?php
/**
 * Cron de backup automático diário
 * Configurar no hPanel Hostinger: Avançado → Cron Jobs
 * Comando: php /home/USUARIO/domains/SEUDOMINIO/public_html/cron/backup_auto.php
 * Frequência sugerida: diariamente às 03:00
 */

require_once __DIR__ . '/../config/config.php';

$backup_dir = APP_PATH . '/storage/backups';
if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

$nome = 'auto_' . date('Ymd_His') . '.sql.gz';
$caminho = $backup_dir . '/' . $nome;

try {
    $ins = db()->prepare("INSERT INTO backups (tipo, arquivo, status) VALUES ('automatico', ?, 'em_andamento')");
    $ins->execute([$nome]);
    $bid = db()->lastInsertId();

    $pdo = db();
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $sql = "-- ClinicApp AutoBackup " . date('Y-m-d H:i:s') . "\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $t) {
        $sql .= "DROP TABLE IF EXISTS `$t`;\n";
        $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM);
        $sql .= $create[1] . ";\n\n";
        $stmt = $pdo->query("SELECT * FROM `$t`");
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cols = '`' . implode('`,`', array_keys($r)) . '`';
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($r));
            $sql .= "INSERT INTO `$t` ($cols) VALUES (" . implode(',', $vals) . ");\n";
        }
        $sql .= "\n";
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $gz = gzopen($caminho, 'wb9');
    gzwrite($gz, $sql);
    gzclose($gz);

    $tam = filesize($caminho);
    db()->prepare("UPDATE backups SET status='concluido', tamanho_bytes=?, concluido_em=NOW() WHERE id=?")
        ->execute([$tam, $bid]);

    // Retenção: manter 30 dias
    $antigos = db()->query("SELECT * FROM backups WHERE tipo='automatico' AND criado_em < DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchAll();
    foreach ($antigos as $a) {
        @unlink($backup_dir . '/' . $a['arquivo']);
        db()->prepare("DELETE FROM backups WHERE id=?")->execute([$a['id']]);
    }

    echo "Backup OK: $nome (" . round($tam/1024,2) . " KB)\n";
} catch (Exception $e) {
    if (!empty($bid)) db()->prepare("UPDATE backups SET status='erro', erro=? WHERE id=?")->execute([$e->getMessage(), $bid]);
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
