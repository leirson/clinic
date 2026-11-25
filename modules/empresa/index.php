<?php
$page_title = 'Empresa';
require_once __DIR__ . '/../../includes/header.php';
require_admin();

$id = active_company_id();
$hasCertColumns = db_column_exists('empresas', 'asten_certificado_a1') && db_column_exists('empresas', 'asten_certificado_senha');
$featureSettings = company_feature_settings($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    try {
        $fields = [
            'razao_social','nome_fantasia','cnpj','inscricao_municipal','inscricao_estadual','cnae',
            'item_lista_servico','codigo_tributacao_municipio','regime_tributario','aliquota_iss',
            'endereco_cep','endereco_logradouro','endereco_numero','endereco_complemento','endereco_bairro',
            'endereco_cidade','endereco_uf','endereco_codigo_municipio','telefone','email',
            'asten_ambiente','asten_token','asten_usuario','asten_senha','asten_url_ws','asten_serie_rps','asten_proximo_rps'
        ];

        if ($hasCertColumns) {
            $fields[] = 'asten_certificado_senha';
        }

        $set = [];
        $vals = [];
        foreach ($fields as $f) {
            $set[] = "$f = ?";
            $v = trim((string) input($f));
            if ($f === 'cnpj') $v = format_cpf_cnpj(only_numbers($v));
            if (in_array($f, ['endereco_cep','telefone'], true)) $v = only_numbers($v);
            if ($f === 'endereco_uf') $v = strtoupper($v);
            $vals[] = $v;
        }

        $set[] = 'optante_simples = ?';
        $vals[] = (int) (input('optante_simples') ? 1 : 0);
        $set[] = 'incentivador_cultural = ?';
        $vals[] = (int) (input('incentivador_cultural') ? 1 : 0);

        if ($hasCertColumns && !empty($_FILES['asten_certificado_file']['name'])) {
            $upload = upload_file('asten_certificado_file', 'certificados', ['pfx','p12']);
            if (!$upload) {
                throw new Exception('Não foi possível processar o upload do certificado A1.');
            }
            $set[] = 'asten_certificado_a1 = ?';
            $vals[] = $upload['arquivo'];
        }

        $vals[] = $id;
        $stmt = db()->prepare('UPDATE empresas SET ' . implode(',', $set) . ' WHERE id = ?');
        $stmt->execute($vals);

        app_config_set_many([
            'google_calendar_enabled' => input('google_calendar_enabled') ? '1' : '0',
            'reminder_email_enabled' => input('reminder_email_enabled') ? '1' : '0',
            'reminder_push_enabled' => input('reminder_push_enabled') ? '1' : '0',
            'pwa_install_enabled' => input('pwa_install_enabled') ? '1' : '0',
            'reminder_hours_before' => (string) max(1, min(168, (int) input('reminder_hours_before', 24))),
            'push_minutes_before' => (string) max(5, min(1440, (int) input('push_minutes_before', 60))),
            'reminder_window_minutes' => (string) max(5, min(180, (int) input('reminder_window_minutes', 30))),
        ], $id);

        log_action('editar','empresas',$id, 'Dados da empresa e integrações atualizados.');
        flash('success','Dados da empresa atualizados!');
        redirect('index.php');
    } catch (Exception $e) {
        flash('error','Erro: ' . $e->getMessage());
    }
}

$e = current_empresa();
$featureSettings = company_feature_settings($id);
$certInfo = null;
$certError = '';
if ($hasCertColumns && !empty($e['asten_certificado_a1'])) {
    $certPath = UPLOAD_PATH . '/' . ltrim((string) $e['asten_certificado_a1'], '/');
    if (is_file($certPath) && function_exists('openssl_pkcs12_read')) {
        $pkcs12 = @file_get_contents($certPath);
        $certs = [];
        if ($pkcs12 !== false && @openssl_pkcs12_read($pkcs12, $certs, (string) ($e['asten_certificado_senha'] ?? ''))) {
            $certInfo = !empty($certs['cert']) ? @openssl_x509_parse($certs['cert']) : null;
        } else {
            $certError = 'Certificado localizado, mas não foi possível validar com a senha atual.';
        }
    } else {
        $certError = 'Arquivo do certificado A1 não encontrado no servidor.';
    }
}
?>
<div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div>
    <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-building text-primary"></i> Empresa & Configurações</h1>
    <p class="text-sm text-slate-500">Dados do prestador, SMTP, NFSe, certificado A1 e integrações gerais</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="<?= APP_URL ?>/modules/empresa/portal.php" class="btn btn-primary">Área do paciente</a>
    <a href="<?= APP_URL ?>/modules/empresa/smtp_teste.php" class="btn bg-emerald-600 text-white"><i class="bi bi-envelope-check"></i> Testar SMTP</a>
  </div>
</div>

<form method="POST" enctype="multipart/form-data" class="space-y-5">
  <?= csrf_field() ?>

  <div class="card p-6">
    <h3 class="font-bold text-slate-700 mb-4 pb-2 border-b"><i class="bi bi-buildings"></i> Identificação</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="md:col-span-2"><label class="text-sm">Razão Social *</label><input name="razao_social" required value="<?= e($e['razao_social']) ?>" class="form-input"></div>
      <div><label class="text-sm">Nome Fantasia</label><input name="nome_fantasia" value="<?= e($e['nome_fantasia']) ?>" class="form-input"></div>
      <div><label class="text-sm">CNPJ *</label><input name="cnpj" required data-mask="##.###.###/####-##" value="<?= e($e['cnpj']) ?>" class="form-input"></div>
      <div><label class="text-sm">Inscrição Municipal *</label><input name="inscricao_municipal" value="<?= e($e['inscricao_municipal']) ?>" class="form-input"></div>
      <div><label class="text-sm">Inscrição Estadual</label><input name="inscricao_estadual" value="<?= e($e['inscricao_estadual']) ?>" class="form-input"></div>
      <div><label class="text-sm">CNAE</label><input name="cnae" value="<?= e($e['cnae']) ?>" class="form-input"></div>
      <div><label class="text-sm">Telefone</label><input name="telefone" data-mask="(##) #####-####" value="<?= e($e['telefone']) ?>" class="form-input"></div>
      <div><label class="text-sm">E-mail</label><input name="email" type="email" value="<?= e($e['email']) ?>" class="form-input"></div>
      <div class="md:col-span-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
        <div class="font-medium text-slate-700"><i class="bi bi-palette mr-1"></i> Especialidades da clínica</div>
        <p class="mt-1">A alteração da especialidade visual não é feita por esta tela. Use a aba <strong>Especialidades</strong> ou o <strong>perfil do usuário</strong>.</p>
      </div>
    </div>
  </div>

  <div class="card p-6">
    <h3 class="font-bold text-slate-700 mb-4 pb-2 border-b"><i class="bi bi-geo-alt"></i> Endereço</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div><label class="text-sm">CEP *</label><input name="endereco_cep" data-mask="#####-###" data-cep="endereco_" value="<?= e($e['endereco_cep']) ?>" class="form-input"></div>
      <div class="md:col-span-2"><label class="text-sm">Logradouro</label><input name="endereco_logradouro" value="<?= e($e['endereco_logradouro']) ?>" class="form-input"></div>
      <div><label class="text-sm">Número</label><input name="endereco_numero" value="<?= e($e['endereco_numero']) ?>" class="form-input"></div>
      <div><label class="text-sm">Complemento</label><input name="endereco_complemento" value="<?= e($e['endereco_complemento']) ?>" class="form-input"></div>
      <div><label class="text-sm">Bairro</label><input name="endereco_bairro" value="<?= e($e['endereco_bairro']) ?>" class="form-input"></div>
      <div><label class="text-sm">Cidade</label><input name="endereco_cidade" value="<?= e($e['endereco_cidade']) ?>" class="form-input"></div>
      <div><label class="text-sm">UF</label><input name="endereco_uf" maxlength="2" value="<?= e($e['endereco_uf']) ?>" class="form-input uppercase"></div>
      <div class="md:col-span-2"><label class="text-sm">Código Município (IBGE)</label><input name="endereco_codigo_municipio" value="<?= e($e['endereco_codigo_municipio']) ?>" class="form-input"></div>
    </div>
  </div>

  <div class="card p-6">
    <h3 class="font-bold text-slate-700 mb-4 pb-2 border-b"><i class="bi bi-cash-stack"></i> Tributação</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="text-sm">Regime Tributário</label>
        <select name="regime_tributario" class="form-select">
          <option value="1" <?= $e['regime_tributario']==='1'?'selected':'' ?>>Simples Nacional</option>
          <option value="2" <?= $e['regime_tributario']==='2'?'selected':'' ?>>Simples Excesso Receita</option>
          <option value="3" <?= $e['regime_tributario']==='3'?'selected':'' ?>>Sociedade Profissionais</option>
          <option value="4" <?= $e['regime_tributario']==='4'?'selected':'' ?>>Cooperativa</option>
          <option value="5" <?= $e['regime_tributario']==='5'?'selected':'' ?>>MEI</option>
          <option value="6" <?= $e['regime_tributario']==='6'?'selected':'' ?>>Normal</option>
        </select>
      </div>
      <div><label class="text-sm">Item Lista Serviço (LC 116)</label><input name="item_lista_servico" placeholder="Ex: 4.01 consultas/psicologia, 4.12 odontologia" value="<?= e($e['item_lista_servico']) ?>" class="form-input"></div>
      <div><label class="text-sm">Cód. Tributação Município</label><input name="codigo_tributacao_municipio" value="<?= e($e['codigo_tributacao_municipio']) ?>" class="form-input"></div>
      <div><label class="text-sm">Alíquota ISS (%)</label><input name="aliquota_iss" value="<?= e($e['aliquota_iss']) ?>" class="form-input"></div>
      <div class="flex items-center gap-2"><label class="flex items-center gap-2 text-sm mt-6"><input type="checkbox" name="optante_simples" value="1" <?= !empty($e['optante_simples'])?'checked':'' ?>> Optante Simples Nacional</label></div>
      <div class="flex items-center gap-2"><label class="flex items-center gap-2 text-sm mt-6"><input type="checkbox" name="incentivador_cultural" value="1" <?= !empty($e['incentivador_cultural'])?'checked':'' ?>> Incentivador Cultural</label></div>
    </div>
  </div>

  <div class="card p-6" id="nfse">
    <h3 class="font-bold text-slate-700 mb-4 pb-2 border-b"><i class="bi bi-shield-lock"></i> Emissão NFSe Nacional</h3>
    <p class="text-xs text-slate-500 mb-4">Configure o conector de emissão NFSe Nacional, endpoint do webservice e, se necessário, o certificado digital A1 para assinatura XML.</p>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="text-sm">Ambiente</label>
        <select name="asten_ambiente" class="form-select">
          <option value="homologacao" <?= $e['asten_ambiente']==='homologacao'?'selected':'' ?>>Homologação (testes)</option>
          <option value="producao" <?= $e['asten_ambiente']==='producao'?'selected':'' ?>>Produção</option>
        </select>
      </div>
      <div class="md:col-span-2"><label class="text-sm">URL Webservice NFSe Nacional</label><input name="asten_url_ws" value="<?= e($e['asten_url_ws']) ?>" placeholder="https://seu-endpoint-nacional.exemplo/ws" class="form-input"></div>
      <div><label class="text-sm">Usuário / integração</label><input name="asten_usuario" value="<?= e($e['asten_usuario']) ?>" class="form-input"></div>
      <div><label class="text-sm">Senha / segredo</label><input name="asten_senha" type="password" value="<?= e($e['asten_senha']) ?>" class="form-input"></div>
      <div><label class="text-sm">Token / chave de acesso</label><input name="asten_token" value="<?= e($e['asten_token']) ?>" class="form-input"></div>
      <div><label class="text-sm">Série RPS</label><input name="asten_serie_rps" value="<?= e($e['asten_serie_rps']) ?>" class="form-input"></div>
      <div><label class="text-sm">Próximo Nº RPS</label><input name="asten_proximo_rps" type="number" value="<?= e($e['asten_proximo_rps']) ?>" class="form-input"></div>
    </div>

    <?php if ($hasCertColumns): ?>
      <div class="mt-6 border-t pt-4">
        <h4 class="font-semibold text-slate-700 mb-3"><i class="bi bi-file-earmark-lock"></i> Certificado digital A1</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="md:col-span-2">
            <label class="text-sm">Arquivo A1 (.pfx/.p12)</label>
            <input type="file" name="asten_certificado_file" accept=".pfx,.p12" class="form-input py-2">
            <p class="text-xs text-slate-500 mt-1">Envie um novo arquivo apenas quando precisar substituir o certificado atual.</p>
          </div>
          <div>
            <label class="text-sm">Senha do certificado</label>
            <input type="password" name="asten_certificado_senha" value="<?= e($e['asten_certificado_senha'] ?? '') ?>" class="form-input">
          </div>
        </div>

        <div class="mt-4 rounded-xl border <?= $certInfo ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' ?> p-4 text-sm">
          <?php if ($certInfo): ?>
            <div class="font-semibold text-emerald-800 mb-2"><i class="bi bi-patch-check"></i> Certificado validado com sucesso</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-slate-700">
              <div><strong>Titular:</strong> <?= e($certInfo['subject']['CN'] ?? ($certInfo['subject']['O'] ?? 'Não identificado')) ?></div>
              <div><strong>Emitente:</strong> <?= e($certInfo['issuer']['CN'] ?? 'Não identificado') ?></div>
              <div><strong>Válido até:</strong> <?= !empty($certInfo['validTo_time_t']) ? date('d/m/Y H:i', (int) $certInfo['validTo_time_t']) : '-' ?></div>
              <div><strong>Arquivo:</strong> <?= e(basename((string) $e['asten_certificado_a1'])) ?></div>
            </div>
          <?php else: ?>
            <div class="font-semibold text-slate-700 mb-1"><i class="bi bi-info-circle"></i> Status do certificado</div>
            <p class="text-slate-600"><?= e($certError !== '' ? $certError : 'Nenhum certificado A1 validado no momento. O XML será enviado sem assinatura A1 até que um certificado válido seja configurado.') ?></p>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        Execute o upgrade mais recente do sistema para habilitar o suporte ao certificado digital A1 nesta tela.
      </div>
    <?php endif; ?>
  </div>

  <div class="card p-6">
    <h3 class="font-bold text-slate-700 mb-4 pb-2 border-b"><i class="bi bi-broadcast-pin"></i> Comunicação, lembretes e PWA</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <label class="rounded-xl border border-slate-200 p-4 flex items-start gap-3">
        <input type="checkbox" name="reminder_email_enabled" value="1" class="mt-1" <?= $featureSettings['reminder_email_enabled'] ? 'checked' : '' ?>>
        <span>
          <span class="font-semibold text-slate-800 block">Lembretes por e-mail</span>
          <span class="text-sm text-slate-500">Usa PHPMailer autenticado para avisar pacientes de consultas e telemedicina.</span>
        </span>
      </label>
      <label class="rounded-xl border border-slate-200 p-4 flex items-start gap-3">
        <input type="checkbox" name="reminder_push_enabled" value="1" class="mt-1" <?= $featureSettings['reminder_push_enabled'] ? 'checked' : '' ?>>
        <span>
          <span class="font-semibold text-slate-800 block">Notificações push no navegador</span>
          <span class="text-sm text-slate-500">Exibe avisos de consultas próximas quando a equipe estiver com o sistema aberto.</span>
        </span>
      </label>
      <label class="rounded-xl border border-slate-200 p-4 flex items-start gap-3">
        <input type="checkbox" name="google_calendar_enabled" value="1" class="mt-1" <?= $featureSettings['google_calendar_enabled'] ? 'checked' : '' ?>>
        <span>
          <span class="font-semibold text-slate-800 block">Google Calendar</span>
          <span class="text-sm text-slate-500">Habilita links rápidos e anexos .ics para importar compromissos.</span>
        </span>
      </label>
      <label class="rounded-xl border border-slate-200 p-4 flex items-start gap-3">
        <input type="checkbox" name="pwa_install_enabled" value="1" class="mt-1" <?= $featureSettings['pwa_install_enabled'] ? 'checked' : '' ?>>
        <span>
          <span class="font-semibold text-slate-800 block">PWA instalável</span>
          <span class="text-sm text-slate-500">Mostra atalhos para instalar o ClinicApp como aplicativo no celular ou desktop.</span>
        </span>
      </label>
      <div>
        <label class="text-sm">Antecedência do lembrete por e-mail (horas)</label>
        <input type="number" min="1" max="168" name="reminder_hours_before" value="<?= e($featureSettings['reminder_hours_before']) ?>" class="form-input">
      </div>
      <div>
        <label class="text-sm">Antecedência da notificação push (minutos)</label>
        <input type="number" min="5" max="1440" name="push_minutes_before" value="<?= e($featureSettings['push_minutes_before']) ?>" class="form-input">
      </div>
      <div>
        <label class="text-sm">Janela do cron de lembretes (minutos)</label>
        <input type="number" min="5" max="180" name="reminder_window_minutes" value="<?= e($featureSettings['reminder_window_minutes']) ?>" class="form-input">
      </div>
      <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
        <div class="font-semibold mb-1">Status técnico</div>
        <div>PHPMailer: <?= phpmailer_is_available() ? 'OK' : 'ausente' ?></div>
        <div>SMTP: <?= smtp_is_configured() ? 'configurado' : 'incompleto' ?></div>
        <div>Cron sugerido: <code>php cron/lembretes.php</code></div>
      </div>
    </div>
  </div>

  <div class="card p-6">
    <h3 class="font-bold text-slate-700 mb-3 pb-2 border-b"><i class="bi bi-stars"></i> Operação clínica inteligente</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="font-semibold text-slate-800 mb-1">Agenda inteligente</div>
        <p class="text-slate-600">Acesse a fila de encaixe para reaproveitar cancelamentos e acompanhar confirmações pendentes.</p>
        <a href="<?= APP_URL ?>/modules/agenda/fila.php" class="btn bg-violet-600 text-white mt-3"><i class="bi bi-hourglass-split"></i> Abrir fila de encaixe</a>
      </div>
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="font-semibold text-slate-800 mb-1">LGPD & Auditoria</div>
        <p class="text-slate-600">Consulte trilha crítica, perfis, exportações e consentimentos registrados.</p>
        <a href="<?= APP_URL ?>/modules/empresa/lgpd.php" class="btn bg-slate-800 text-white mt-3"><i class="bi bi-shield-lock"></i> Abrir central LGPD</a>
      </div>
    </div>
  </div>

  <div class="card p-6">
    <h3 class="font-bold text-slate-700 mb-3 pb-2 border-b"><i class="bi bi-arrow-repeat"></i> Atualização do Sistema</h3>
    <p class="text-sm text-slate-600">As configurações e a execução de atualização ficam centralizadas dentro da área de Empresa, sem item exclusivo no menu lateral.</p>
    <div class="mt-4 flex flex-wrap gap-2">
      <a href="<?= APP_URL ?>/modules/empresa/atualizacoes.php" class="btn bg-amber-500 text-white"><i class="bi bi-cloud-arrow-down"></i> Abrir atualização</a>
    </div>
  </div>

  <div class="flex justify-end gap-2">
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar Configurações</button>
  </div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
