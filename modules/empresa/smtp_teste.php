<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$page_title = 'Teste SMTP';

$resultadoConexao = null;
$resultadoEnvio = null;
$destino = current_user()['email'] ?? '';
$nomeDestino = current_user()['nome'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $acao = input('acao');

    if ($acao === 'testar_conexao') {
        $resultadoConexao = smtp_test_connection();
        log_action('teste_smtp_conexao', 'configuracao', active_company_id(), $resultadoConexao['ok'] ? 'Conexão SMTP validada' : ('Falha SMTP: ' . ($resultadoConexao['raw_error'] ?? $resultadoConexao['error'] ?? 'sem detalhe')));
    }

    if ($acao === 'enviar_teste') {
        $destino = trim(input('email_destino'));
        $nomeDestino = trim(input('nome_destino'));

        if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            $resultadoEnvio = [
                'ok' => false,
                'error' => 'Informe um e-mail de destino válido para o teste.',
                'raw_error' => null,
            ];
        } else {
            $empresa = current_empresa();
            $resultadoConexao = smtp_test_connection();
            if (!$resultadoConexao['ok']) {
                $resultadoEnvio = [
                    'ok' => false,
                    'error' => 'A conexão SMTP falhou antes do envio. Corrija a configuração e tente novamente.',
                    'raw_error' => $resultadoConexao['raw_error'] ?? $resultadoConexao['error'] ?? null,
                ];
            } else {
                $assunto = 'Teste SMTP - ' . APP_NAME;
                $bodyHtml = '<h2 style="margin:0 0 12px">Teste SMTP realizado com sucesso</h2>'
                    . '<p>Olá' . ($nomeDestino ? ' ' . e($nomeDestino) : '') . ',</p>'
                    . '<p>Este é um e-mail de teste enviado pelo painel administrativo do <strong>' . e(APP_NAME) . '</strong>.</p>'
                    . '<p><strong>Empresa ativa:</strong> ' . e($empresa['nome_fantasia'] ?: $empresa['razao_social'] ?: 'Empresa') . '<br>'
                    . '<strong>Servidor SMTP:</strong> ' . e(SMTP_HOST) . ':' . (int) SMTP_PORT . '<br>'
                    . '<strong>Segurança:</strong> ' . e(defined('SMTP_SECURE') ? SMTP_SECURE : 'tls') . '</p>'
                    . '<p>Se você recebeu esta mensagem, a autenticação SMTP está funcionando corretamente.</p>'
                    . '<hr><p style="font-size:12px;color:#64748b">Mensagem automática de teste gerada em ' . date('d/m/Y H:i') . '.</p>';
                $bodyText = "Teste SMTP realizado com sucesso\n\n"
                    . 'Aplicação: ' . APP_NAME . "\n"
                    . 'Empresa ativa: ' . ($empresa['nome_fantasia'] ?: $empresa['razao_social'] ?: 'Empresa') . "\n"
                    . 'Servidor SMTP: ' . SMTP_HOST . ':' . SMTP_PORT . "\n"
                    . 'Segurança: ' . (defined('SMTP_SECURE') ? SMTP_SECURE : 'tls') . "\n\n"
                    . 'Se você recebeu esta mensagem, a autenticação SMTP está funcionando corretamente.';

                $resultadoEnvio = send_email($destino, $assunto, $bodyHtml, [
                    'to_name' => $nomeDestino,
                    'is_html' => true,
                    'alt_body' => $bodyText,
                    'from_name' => SMTP_FROM_NAME,
                    'reply_to' => SMTP_USER,
                    'reply_to_name' => SMTP_FROM_NAME,
                ]);

                log_action('teste_smtp_envio', 'configuracao', active_company_id(), $resultadoEnvio['ok'] ? ('E-mail de teste enviado para ' . $destino) : ('Falha envio SMTP para ' . $destino . ': ' . ($resultadoEnvio['raw_error'] ?? $resultadoEnvio['error'] ?? 'sem detalhe')));
            }
        }
    }
}

$empresa = current_empresa();
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div>
    <a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar para Empresa</a>
    <h1 class="text-2xl font-bold text-slate-800 mt-1"><i class="bi bi-envelope-check text-primary"></i> Teste SMTP</h1>
    <p class="text-sm text-slate-500">Valide a conexão SMTP e envie um e-mail de teste com mensagens amigáveis para diagnóstico.</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <span class="badge <?= smtp_is_configured() ? 'badge-success' : 'badge-danger' ?>"><?= smtp_is_configured() ? 'SMTP configurado' : 'SMTP incompleto' ?></span>
    <span class="badge <?= phpmailer_is_available() ? 'badge-success' : 'badge-danger' ?>"><?= phpmailer_is_available() ? 'PHPMailer OK' : 'PHPMailer ausente' ?></span>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
  <div class="card p-6 xl:col-span-2">
    <h3 class="font-bold text-slate-700 mb-4 pb-2 border-b"><i class="bi bi-plug"></i> Diagnóstico de conexão</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5 text-sm">
      <div><span class="text-slate-500">Servidor SMTP</span><div class="font-semibold text-slate-800"><?= e(SMTP_HOST) ?></div></div>
      <div><span class="text-slate-500">Porta</span><div class="font-semibold text-slate-800"><?= (int) SMTP_PORT ?></div></div>
      <div><span class="text-slate-500">Usuário</span><div class="font-semibold text-slate-800"><?= e(SMTP_USER) ?></div></div>
      <div><span class="text-slate-500">Segurança</span><div class="font-semibold text-slate-800"><?= e(defined('SMTP_SECURE') ? SMTP_SECURE : 'tls') ?></div></div>
      <div><span class="text-slate-500">Remetente</span><div class="font-semibold text-slate-800"><?= e(SMTP_FROM_NAME) ?></div></div>
      <div><span class="text-slate-500">Empresa ativa</span><div class="font-semibold text-slate-800"><?= e($empresa['nome_fantasia'] ?: $empresa['razao_social'] ?: 'Empresa') ?></div></div>
    </div>

    <form method="POST" class="mb-5">
      <?= csrf_field() ?>
      <input type="hidden" name="acao" value="testar_conexao">
      <button class="btn btn-primary"><i class="bi bi-wifi"></i> Testar conexão SMTP</button>
    </form>

    <?php if ($resultadoConexao): ?>
      <div class="rounded-lg border p-4 <?= $resultadoConexao['ok'] ? 'bg-green-50 border-green-200 text-green-800' : 'bg-amber-50 border-amber-200 text-amber-900' ?>">
        <div class="font-semibold flex items-center gap-2">
          <i class="bi <?= $resultadoConexao['ok'] ? 'bi-check-circle' : 'bi-exclamation-triangle' ?>"></i>
          <?= e($resultadoConexao['message'] ?? ($resultadoConexao['ok'] ? 'Conexão validada.' : 'Falha na conexão SMTP.')) ?>
        </div>
        <?php if (!$resultadoConexao['ok'] && !empty($resultadoConexao['error'])): ?>
          <p class="text-sm mt-2"><?= e($resultadoConexao['error']) ?></p>
        <?php endif; ?>
        <?php if (!$resultadoConexao['ok'] && !empty($resultadoConexao['raw_error'])): ?>
          <details class="mt-3 text-xs">
            <summary class="cursor-pointer">Detalhe técnico</summary>
            <pre class="mt-2 bg-white/70 p-3 rounded overflow-auto whitespace-pre-wrap"><?= e($resultadoConexao['raw_error']) ?></pre>
          </details>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
        Use este teste para validar se o servidor SMTP aceita conexão com as credenciais atuais antes de enviar mensagens reais aos pacientes.
      </div>
    <?php endif; ?>
  </div>

  <div class="card p-6">
    <h3 class="font-bold text-slate-700 mb-4 pb-2 border-b"><i class="bi bi-send-check"></i> Envio de e-mail de teste</h3>
    <form method="POST" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="acao" value="enviar_teste">

      <div>
        <label class="text-sm font-medium">E-mail de destino *</label>
        <input type="email" name="email_destino" required value="<?= e($destino) ?>" class="form-input" placeholder="voce@dominio.com">
      </div>

      <div>
        <label class="text-sm font-medium">Nome do destinatário</label>
        <input type="text" name="nome_destino" value="<?= e($nomeDestino) ?>" class="form-input" placeholder="Nome opcional">
      </div>

      <button class="btn bg-emerald-600 text-white w-full"><i class="bi bi-envelope-paper"></i> Enviar e-mail de teste</button>
    </form>

    <?php if ($resultadoEnvio): ?>
      <div class="mt-4 rounded-lg border p-4 <?= $resultadoEnvio['ok'] ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
        <div class="font-semibold flex items-center gap-2">
          <i class="bi <?= $resultadoEnvio['ok'] ? 'bi-check-circle' : 'bi-x-octagon' ?>"></i>
          <?= $resultadoEnvio['ok'] ? 'E-mail de teste enviado com sucesso.' : 'Falha no envio do e-mail de teste.' ?>
        </div>
        <?php if (!$resultadoEnvio['ok'] && !empty($resultadoEnvio['error'])): ?>
          <p class="text-sm mt-2"><?= e($resultadoEnvio['error']) ?></p>
        <?php endif; ?>
        <?php if (!$resultadoEnvio['ok'] && !empty($resultadoEnvio['raw_error'])): ?>
          <details class="mt-3 text-xs">
            <summary class="cursor-pointer">Detalhe técnico</summary>
            <pre class="mt-2 bg-white/70 p-3 rounded overflow-auto whitespace-pre-wrap"><?= e($resultadoEnvio['raw_error']) ?></pre>
          </details>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
      <div class="font-semibold mb-1">Boas práticas</div>
      <ul class="list-disc ml-5 space-y-1">
        <li>Use um e-mail do próprio domínio da clínica como remetente.</li>
        <li>Confirme host, porta e segurança TLS/SSL com a Hostinger.</li>
        <li>Se a conta de e-mail utiliza 2FA, gere uma senha de aplicativo específica para SMTP.</li>
        <li>Se a conexão falhar, revise firewall, DNS e autenticação SMTP.</li>
      </ul>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
