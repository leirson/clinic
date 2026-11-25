<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/nfse_nacional.php';
require_login();
require_post_action('Cancelar NFSe');

$id = (int)input('id');
$stmt = db()->prepare("SELECT * FROM nfse WHERE id=? AND empresa_id=?");
$stmt->execute([$id, active_company_id()]);
$n = $stmt->fetch();
if (!$n || $n['status'] !== 'autorizada') { flash('error','NFSe inválida para cancelamento.'); redirect('index.php'); }

$emp = current_empresa();
try {
    $gateway = new NFSeNacionalGateway([
        'ambiente' => $emp['asten_ambiente'],
        'url' => $emp['asten_url_ws'],
        'token' => $emp['asten_token'],
        'usuario' => $emp['asten_usuario'],
        'senha' => $emp['asten_senha'],
        'cert_path' => !empty($emp['asten_certificado_a1']) ? UPLOAD_PATH . '/' . ltrim($emp['asten_certificado_a1'], '/') : '',
        'cert_password' => $emp['asten_certificado_senha'] ?? ''
    ]);
    $r = $gateway->cancelar($n['numero_nfse'], $emp['cnpj'], $emp['inscricao_municipal'], $emp['endereco_codigo_municipio']);
    if ($r['ok'] ?? false) {
        db()->prepare("UPDATE nfse SET status='cancelada', motivo_cancelamento=?, xml_retorno=? WHERE id=?")
            ->execute(['Cancelada via sistema', $r['xml_retorno'] ?? '', $id]);
        flash('success','NFSe cancelada.');
    } else {
        flash('error','Erro: '.($r['mensagem'] ?? 'falha'));
    }
} catch (Exception $e) {
    flash('error','Exceção: '.$e->getMessage());
}
redirect('ver.php?id='.$id);
