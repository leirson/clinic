<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/nfse_nacional.php';
require_login();
require_post_action('Enviar NFSe');

$id = (int)input('id');
$stmt = db()->prepare("SELECT n.* FROM nfse n JOIN pacientes p ON p.id=n.paciente_id AND p.empresa_id=n.empresa_id WHERE n.id=? AND n.empresa_id=?");
$stmt->execute([$id, active_company_id()]);
$dados = $stmt->fetch();
if ($dados && !in_array($dados['status'], ['rascunho','erro'], true)) { flash('error','Nota já enviada ou não disponível para reenvio. Consulte o status antes de reenviar.'); redirect('ver.php?id='.$id); }
if (!$dados) { flash('error','NFSe não encontrada.'); redirect('index.php'); }

$empresa = current_empresa();

if (empty($empresa['asten_url_ws'])) {
    flash('error','URL do webservice NFSe Nacional não configurada. Acesse Empresa > Configurações.');
    redirect('ver.php?id='.$id);
}

try {
    $gateway = new NFSeNacionalGateway([
        'ambiente' => $empresa['asten_ambiente'],
        'url' => $empresa['asten_url_ws'],
        'token' => $empresa['asten_token'],
        'usuario' => $empresa['asten_usuario'],
        'senha' => $empresa['asten_senha'],
        'cert_path' => !empty($empresa['asten_certificado_a1']) ? (UPLOAD_PATH . '/' . ltrim((string) $empresa['asten_certificado_a1'], '/')) : '',
        'cert_password' => $empresa['asten_certificado_senha'] ?? ''
    ]);

    // Re-buscar separadamente
    $st2 = db()->prepare("SELECT * FROM pacientes WHERE id=?");
    $st2->execute([$dados['paciente_id']]);
    $paciente = $st2->fetch();

    $st3 = db()->prepare("SELECT * FROM nfse WHERE id=?");
    $st3->execute([$id]);
    $nfse = $st3->fetch();

    $xml_rps = $gateway->montarXmlRps([
        'empresa' => $empresa,
        'tomador' => $paciente,
        'servico' => [
            'valor_servico' => $nfse['valor_servico'],
            'valor_deducoes' => $nfse['valor_deducoes'],
            'valor_iss' => $nfse['valor_iss'],
            'aliquota_iss' => $nfse['aliquota_iss'],
            'item_lista_servico' => $nfse['item_lista_servico'],
            'codigo_tributacao_municipio' => $nfse['codigo_tributacao_municipio'],
            'discriminacao' => $nfse['discriminacao'],
            'iss_retido' => $nfse['iss_retido']
        ],
        'rps' => [
            'numero' => $nfse['numero_rps'],
            'serie' => $nfse['serie_rps'],
            'data_emissao' => $nfse['data_emissao']
        ]
    ]);

    $ret = $gateway->enviarLote($xml_rps);

    $upd = db()->prepare("UPDATE nfse SET status=?, numero_nfse=?, protocolo=?, codigo_verificacao=?, mensagem_retorno=?, xml_envio=?, xml_retorno=? WHERE id=?");
    $upd->execute([
        $ret['status'] ?? ($ret['ok'] ? 'enviada' : 'erro'),
        $ret['numero_nfse'] ?? null,
        $ret['protocolo'] ?? null,
        $ret['codigo_verificacao'] ?? null,
        $ret['mensagem'] ?? ($ret['erro'] ?? null),
        $gateway->getUltimoXmlEnvio(),
        $ret['xml_retorno'] ?? null,
        $id
    ]);

    log_action('enviar_nfse','nfse',$id, json_encode($ret));

    if ($ret['ok'] ?? false) {
        flash('success','NFSe enviada com sucesso!' . ($ret['numero_nfse'] ? ' Número: '.$ret['numero_nfse'] : ''));
    } else {
        flash('error','Falha ao enviar: '.($ret['mensagem'] ?? $ret['erro'] ?? 'erro desconhecido'));
    }
} catch (Exception $e) {
    flash('error','Exceção: '.$e->getMessage());
}

redirect('ver.php?id='.$id);
