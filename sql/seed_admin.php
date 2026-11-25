<?php
/**
 * Script para gerar hash da senha padrão do admin.
 * Execute uma vez no servidor para criar/atualizar a senha do admin.
 * URL: https://seudominio.com/sql/seed_admin.php
 *
 * ATENÇÃO: Apague este arquivo após o uso!
 */
require_once __DIR__ . '/../config/config.php';

$email = 'admin@clinicapp.com';
$nova_senha = 'admin123';
$hash = password_hash($nova_senha, PASSWORD_BCRYPT, ['cost'=>10]);

try {
    $stmt = db()->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
    $r = $stmt->execute([$hash, $email]);

    echo "<h2>Seed Admin</h2>";
    echo "<p>Email: <b>$email</b></p>";
    echo "<p>Senha: <b>$nova_senha</b></p>";
    echo "<p>Hash gerado: <code>$hash</code></p>";
    echo "<p>Status: ".($r?'OK':'falha')."</p>";
    echo "<p style='color:red'><b>IMPORTANTE: APAGUE este arquivo após o uso!</b></p>";
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
