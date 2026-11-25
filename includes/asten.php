<?php
/**
 * Cliente NFSe Asten com suporte inicial a certificado A1.
 * Observação: o layout ABRASF pode variar por município; a assinatura XML aqui
 * aplica XMLDSig padrão no primeiro nó com atributo Id (prioriza InfDeclaracaoPrestacaoServico).
 */

class AstenNFSe {
    private $url;
    private $token;
    private $usuario;
    private $senha;
    private $ambiente;
    private $timeout;
    private $ultimo_xml_envio;
    private $ultimo_xml_retorno;
    private $certPath;
    private $certPassword;
    private $certInfo;

    public function __construct(array $config) {
        $this->ambiente = $config['ambiente'] ?? 'homologacao';
        $this->url = $config['url'] ?: ($this->ambiente === 'producao' ? ASTEN_URL_PROD : ASTEN_URL_HOMOLOG);
        $this->token = $config['token'] ?? '';
        $this->usuario = $config['usuario'] ?? '';
        $this->senha = $config['senha'] ?? '';
        $this->timeout = ASTEN_TIMEOUT;
        $this->certPath = $config['cert_path'] ?? '';
        $this->certPassword = $config['cert_password'] ?? '';
        $this->certInfo = null;
    }

    public function montarXmlRps(array $dados) {
        $emp = $dados['empresa'];
        $tom = $dados['tomador'];
        $srv = $dados['servico'];
        $rps = $dados['rps'];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<EnviarLoteRpsEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">';
        $xml .= '<LoteRps Id="lote' . $rps['numero'] . '" versao="2.03">';
        $xml .= '<NumeroLote>' . $rps['numero'] . '</NumeroLote>';
        $xml .= '<CpfCnpj><Cnpj>' . only_numbers($emp['cnpj']) . '</Cnpj></CpfCnpj>';
        $xml .= '<InscricaoMunicipal>' . htmlspecialchars((string) $emp['inscricao_municipal']) . '</InscricaoMunicipal>';
        $xml .= '<QuantidadeRps>1</QuantidadeRps>';
        $xml .= '<ListaRps>';
        $xml .= '<Rps>';
        $xml .= '<InfDeclaracaoPrestacaoServico Id="rps' . $rps['numero'] . '">';

        $xml .= '<Rps>';
        $xml .= '<IdentificacaoRps>';
        $xml .= '<Numero>' . $rps['numero'] . '</Numero>';
        $xml .= '<Serie>' . htmlspecialchars((string) $rps['serie']) . '</Serie>';
        $xml .= '<Tipo>1</Tipo>';
        $xml .= '</IdentificacaoRps>';
        $xml .= '<DataEmissao>' . date('Y-m-d\TH:i:s', strtotime((string) $rps['data_emissao'])) . '</DataEmissao>';
        $xml .= '<Status>1</Status>';
        $xml .= '</Rps>';

        $xml .= '<Competencia>' . date('Y-m-d', strtotime((string) $rps['data_emissao'])) . '</Competencia>';

        $xml .= '<Servico>';
        $xml .= '<Valores>';
        $xml .= '<ValorServicos>' . number_format((float) $srv['valor_servico'], 2, '.', '') . '</ValorServicos>';
        $xml .= '<ValorDeducoes>' . number_format((float) ($srv['valor_deducoes'] ?? 0), 2, '.', '') . '</ValorDeducoes>';
        $xml .= '<Aliquota>' . number_format(((float) $srv['aliquota_iss']) / 100, 4, '.', '') . '</Aliquota>';
        $xml .= '<ValorIss>' . number_format((float) $srv['valor_iss'], 2, '.', '') . '</ValorIss>';
        $xml .= '<DescontoIncondicionado>0.00</DescontoIncondicionado>';
        $xml .= '<DescontoCondicionado>0.00</DescontoCondicionado>';
        $xml .= '</Valores>';
        $xml .= '<IssRetido>' . (!empty($srv['iss_retido']) ? '1' : '2') . '</IssRetido>';
        $xml .= '<ItemListaServico>' . htmlspecialchars((string) $srv['item_lista_servico']) . '</ItemListaServico>';
        $xml .= '<CodigoTributacaoMunicipio>' . htmlspecialchars((string) $srv['codigo_tributacao_municipio']) . '</CodigoTributacaoMunicipio>';
        $xml .= '<Discriminacao>' . htmlspecialchars((string) $srv['discriminacao']) . '</Discriminacao>';
        $xml .= '<CodigoMunicipio>' . htmlspecialchars((string) $emp['endereco_codigo_municipio']) . '</CodigoMunicipio>';
        $xml .= '<ExigibilidadeISS>1</ExigibilidadeISS>';
        $xml .= '</Servico>';

        $xml .= '<Prestador>';
        $xml .= '<CpfCnpj><Cnpj>' . only_numbers($emp['cnpj']) . '</Cnpj></CpfCnpj>';
        $xml .= '<InscricaoMunicipal>' . htmlspecialchars((string) $emp['inscricao_municipal']) . '</InscricaoMunicipal>';
        $xml .= '</Prestador>';

        $xml .= '<Tomador>';
        if (!empty($tom['cpf_cnpj'])) {
            $cpfcnpj = only_numbers((string) $tom['cpf_cnpj']);
            $xml .= '<IdentificacaoTomador><CpfCnpj>';
            if (strlen($cpfcnpj) === 11) {
                $xml .= '<Cpf>' . $cpfcnpj . '</Cpf>';
            } else {
                $xml .= '<Cnpj>' . $cpfcnpj . '</Cnpj>';
            }
            $xml .= '</CpfCnpj></IdentificacaoTomador>';
        }
        $xml .= '<RazaoSocial>' . htmlspecialchars((string) $tom['nome']) . '</RazaoSocial>';
        if (!empty($tom['logradouro'])) {
            $xml .= '<Endereco>';
            $xml .= '<Endereco>' . htmlspecialchars((string) $tom['logradouro']) . '</Endereco>';
            $xml .= '<Numero>' . htmlspecialchars((string) ($tom['numero'] ?? 'S/N')) . '</Numero>';
            if (!empty($tom['complemento'])) {
                $xml .= '<Complemento>' . htmlspecialchars((string) $tom['complemento']) . '</Complemento>';
            }
            $xml .= '<Bairro>' . htmlspecialchars((string) ($tom['bairro'] ?? '')) . '</Bairro>';
            $xml .= '<CodigoMunicipio>' . htmlspecialchars((string) ($tom['codigo_municipio'] ?? '')) . '</CodigoMunicipio>';
            $xml .= '<Uf>' . htmlspecialchars((string) ($tom['uf'] ?? '')) . '</Uf>';
            $xml .= '<Cep>' . only_numbers((string) ($tom['cep'] ?? '')) . '</Cep>';
            $xml .= '</Endereco>';
        }
        if (!empty($tom['email']) || !empty($tom['telefone'])) {
            $xml .= '<Contato>';
            if (!empty($tom['telefone'])) {
                $xml .= '<Telefone>' . only_numbers((string) $tom['telefone']) . '</Telefone>';
            }
            if (!empty($tom['email'])) {
                $xml .= '<Email>' . htmlspecialchars((string) $tom['email']) . '</Email>';
            }
            $xml .= '</Contato>';
        }
        $xml .= '</Tomador>';

        $xml .= '<RegimeEspecialTributacao>' . htmlspecialchars((string) $emp['regime_tributario']) . '</RegimeEspecialTributacao>';
        $xml .= '<OptanteSimplesNacional>' . (!empty($emp['optante_simples']) ? '1' : '2') . '</OptanteSimplesNacional>';
        $xml .= '<IncentivoFiscal>' . (!empty($emp['incentivador_cultural']) ? '1' : '2') . '</IncentivoFiscal>';

        $xml .= '</InfDeclaracaoPrestacaoServico>';
        $xml .= '</Rps>';
        $xml .= '</ListaRps>';
        $xml .= '</LoteRps>';
        $xml .= '</EnviarLoteRpsEnvio>';

        return $this->assinarXmlSeDisponivel($xml);
    }

    public function enviarLote($xml_rps) {
        $envelope  = '<?xml version="1.0" encoding="UTF-8"?>';
        $envelope .= '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">';
        $envelope .= '<soap:Header>';
        if ($this->token) {
            $envelope .= '<nfse:Token>' . htmlspecialchars($this->token) . '</nfse:Token>';
        }
        $envelope .= '</soap:Header>';
        $envelope .= '<soap:Body><nfse:RecepcionarLoteRpsRequest><nfse:xml><![CDATA[' . $xml_rps . ']]></nfse:xml></nfse:RecepcionarLoteRpsRequest></soap:Body></soap:Envelope>';

        return $this->executarCurl('RecepcionarLoteRps', $envelope);
    }

    public function consultarRps($numero_rps, $serie, $cnpj, $im) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<ConsultarNfseRpsEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">';
        $xml .= '<IdentificacaoRps><Numero>' . $numero_rps . '</Numero><Serie>' . $serie . '</Serie><Tipo>1</Tipo></IdentificacaoRps>';
        $xml .= '<Prestador><CpfCnpj><Cnpj>' . only_numbers($cnpj) . '</Cnpj></CpfCnpj><InscricaoMunicipal>' . $im . '</InscricaoMunicipal></Prestador>';
        $xml .= '</ConsultarNfseRpsEnvio>';
        return $this->_chamar('ConsultarNfsePorRps', $xml);
    }

    public function cancelar($numero_nfse, $cnpj, $im, $codigo_municipio, $motivo_codigo = 1) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<CancelarNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd"><Pedido><InfPedidoCancelamento>';
        $xml .= '<IdentificacaoNfse><Numero>' . $numero_nfse . '</Numero><CpfCnpj><Cnpj>' . only_numbers($cnpj) . '</Cnpj></CpfCnpj><InscricaoMunicipal>' . $im . '</InscricaoMunicipal><CodigoMunicipio>' . $codigo_municipio . '</CodigoMunicipio></IdentificacaoNfse>';
        $xml .= '<CodigoCancelamento>' . $motivo_codigo . '</CodigoCancelamento>';
        $xml .= '</InfPedidoCancelamento></Pedido></CancelarNfseEnvio>';
        return $this->_chamar('CancelarNfse', $xml);
    }

    private function _chamar($acao, $xml_corpo) {
        $xml_corpo = $this->assinarXmlSeDisponivel($xml_corpo);
        $envelope  = '<?xml version="1.0" encoding="UTF-8"?>';
        $envelope .= '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">';
        $envelope .= '<soap:Header>';
        if ($this->token) {
            $envelope .= '<nfse:Token>' . htmlspecialchars($this->token) . '</nfse:Token>';
        }
        $envelope .= '</soap:Header><soap:Body><nfse:' . $acao . 'Request><nfse:xml><![CDATA[' . $xml_corpo . ']]></nfse:xml></nfse:' . $acao . 'Request></soap:Body></soap:Envelope>';
        return $this->executarCurl($acao, $envelope);
    }

    private function executarCurl($acao, $envelope) {
        $this->ultimo_xml_envio = $envelope;
        $headers = [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "' . $acao . '"',
            'Content-Length: ' . strlen($envelope),
        ];
        if ($this->usuario) {
            $headers[] = 'Authorization: Basic ' . base64_encode($this->usuario . ':' . $this->senha);
        }

        $ch = curl_init($this->url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $envelope,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $retorno = curl_exec($ch);
        $erro = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->ultimo_xml_retorno = $retorno;

        if ($erro) {
            return ['ok' => false, 'erro' => 'cURL: ' . $erro, 'http' => $http, 'retorno' => $retorno];
        }
        return $this->parsearRetorno($retorno, $http);
    }

    private function parsearRetorno($retorno, $http) {
        if (!$retorno) {
            return ['ok' => false, 'erro' => 'Sem retorno do servidor', 'http' => $http];
        }

        $numero = '';
        $protocolo = '';
        $codigo_verif = '';
        $mensagem = '';
        $status = 'enviada';

        if (preg_match('/<Numero>([^<]+)<\/Numero>/i', $retorno, $m)) $numero = $m[1];
        if (preg_match('/<Protocolo>([^<]+)<\/Protocolo>/i', $retorno, $m)) $protocolo = $m[1];
        if (preg_match('/<CodigoVerificacao>([^<]+)<\/CodigoVerificacao>/i', $retorno, $m)) $codigo_verif = $m[1];
        if (preg_match('/<Mensagem>([^<]+)<\/Mensagem>/i', $retorno, $m)) $mensagem = $m[1];

        if (stripos($retorno, '<ListaMensagemRetorno>') !== false && stripos($retorno, '<Codigo>') !== false) {
            $status = 'rejeitada';
        } elseif ($numero) {
            $status = 'autorizada';
        }

        return [
            'ok' => $status === 'autorizada' || $status === 'enviada',
            'status' => $status,
            'numero_nfse' => $numero,
            'protocolo' => $protocolo,
            'codigo_verificacao' => $codigo_verif,
            'mensagem' => $mensagem,
            'xml_retorno' => $retorno,
            'http' => $http,
        ];
    }

    public function getUltimoXmlEnvio() { return $this->ultimo_xml_envio; }
    public function getUltimoXmlRetorno() { return $this->ultimo_xml_retorno; }

    public function getCertificateInfo() {
        if ($this->certInfo !== null) {
            return $this->certInfo;
        }
        if (!$this->certPath || !is_file($this->certPath) || !function_exists('openssl_pkcs12_read')) {
            return $this->certInfo = null;
        }
        $pkcs12 = @file_get_contents($this->certPath);
        if ($pkcs12 === false) {
            return $this->certInfo = null;
        }
        $certs = [];
        if (!@openssl_pkcs12_read($pkcs12, $certs, (string) $this->certPassword)) {
            return $this->certInfo = null;
        }
        $parsed = !empty($certs['cert']) ? @openssl_x509_parse($certs['cert']) : null;
        return $this->certInfo = [
            'bundle' => $certs,
            'parsed' => $parsed ?: [],
        ];
    }

    private function assinarXmlSeDisponivel($xml) {
        if (!$this->certPath || !is_file($this->certPath) || !function_exists('openssl_sign') || !class_exists('DOMDocument')) {
            return $xml;
        }

        $certInfo = $this->getCertificateInfo();
        if (!$certInfo || empty($certInfo['bundle']['pkey']) || empty($certInfo['bundle']['cert'])) {
            return $xml;
        }

        try {
            return $this->assinarXml($xml, $certInfo['bundle']['pkey'], $certInfo['bundle']['cert']);
        } catch (Throwable $e) {
            return $xml;
        }
    }

    private function assinarXml($xml, $privateKeyPem, $certPem) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (!@$dom->loadXML($xml)) {
            return $xml;
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('a', 'http://www.abrasf.org.br/nfse.xsd');
        $target = $xpath->query('//*[@Id]')->item(0);
        if (!$target instanceof DOMElement) {
            return $xml;
        }

        $referenceId = $target->getAttribute('Id');
        if ($referenceId === '') {
            return $xml;
        }

        $canonicalTarget = $target->C14N(true, false);
        $digestValue = base64_encode(sha1($canonicalTarget, true));

        $signedInfo = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:SignedInfo');
        $canonMethod = $dom->createElement('ds:CanonicalizationMethod');
        $canonMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
        $signedInfo->appendChild($canonMethod);

        $sigMethod = $dom->createElement('ds:SignatureMethod');
        $sigMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');
        $signedInfo->appendChild($sigMethod);

        $reference = $dom->createElement('ds:Reference');
        $reference->setAttribute('URI', '#' . $referenceId);
        $transforms = $dom->createElement('ds:Transforms');
        $transform1 = $dom->createElement('ds:Transform');
        $transform1->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        $transforms->appendChild($transform1);
        $transform2 = $dom->createElement('ds:Transform');
        $transform2->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
        $transforms->appendChild($transform2);
        $reference->appendChild($transforms);

        $digestMethod = $dom->createElement('ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');
        $reference->appendChild($digestMethod);
        $reference->appendChild($dom->createElement('ds:DigestValue', $digestValue));
        $signedInfo->appendChild($reference);

        $signatureNode = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Signature');
        $signatureNode->appendChild($signedInfo);

        $signedInfoCanonical = $signedInfo->C14N(true, false);
        $signatureValue = '';
        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if (!$privateKey) {
            return $xml;
        }
        if (!openssl_sign($signedInfoCanonical, $signatureValue, $privateKey, OPENSSL_ALGO_SHA1)) {
            return $xml;
        }
        $signatureNode->appendChild($dom->createElement('ds:SignatureValue', base64_encode($signatureValue)));

        $keyInfo = $dom->createElement('ds:KeyInfo');
        $x509Data = $dom->createElement('ds:X509Data');
        $cleanCert = str_replace(["-----BEGIN CERTIFICATE-----", "-----END CERTIFICATE-----", "\r", "\n"], '', $certPem);
        $x509Data->appendChild($dom->createElement('ds:X509Certificate', $cleanCert));
        $keyInfo->appendChild($x509Data);
        $signatureNode->appendChild($keyInfo);

        $target->appendChild($signatureNode);
        return $dom->saveXML();
    }
}
