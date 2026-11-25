<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/totp.php';
require_login();

$page_title = 'Segurança (2FA)';
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $acao = input('acao');

    if ($acao === 'enable_totp') {
        $secret = input('secret');
        $codigo = input('codigo');
        if (TOTP::verify($secret, $codigo)) {
            $recovery = TOTP::generateRecoveryCodes(8);
            db()->prepare("UPDATE usuarios SET mfa_metodo='totp', mfa_secret=?, mfa_codigos_recuperacao=? WHERE id=?")
                ->execute([$secret, json_encode($recovery), $u['id']]);
            $_SESSION['_show_recovery'] = $recovery;
            flash('success','2FA por aplicativo ativado!');
        } else {
            flash('error','Código inválido. Tente novamente.');
        }
        redirect('seguranca.php');
    }

    if ($acao === 'enable_email') {
        $recovery = TOTP::generateRecoveryCodes(8);
        db()->prepare("UPDATE usuarios SET mfa_metodo='email', mfa_codigos_recuperacao=? WHERE id=?")
            ->execute([json_encode($recovery), $u['id']]);
        $_SESSION['_show_recovery'] = $recovery;
        flash('success','2FA por e-mail ativado!');
        redirect('seguranca.php');
    }

    if ($acao === 'disable') {
        db()->prepare("UPDATE usuarios SET mfa_metodo='nenhum', mfa_secret=NULL, mfa_codigos_recuperacao=NULL WHERE id=?")
            ->execute([$u['id']]);
        flash('success','2FA desativado.');
        redirect('seguranca.php');
    }
}

$novo_secret = TOTP::generateSecret();
$qr_url = TOTP::getQrCodeUrl($novo_secret, $u['email'], APP_NAME);
$recovery_show = $_SESSION['_show_recovery'] ?? null;
$smtpReady = smtp_is_configured() && phpmailer_is_available();
unset($_SESSION['_show_recovery']);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5">
  <h1 class="text-2xl font-bold"><i class="bi bi-shield-lock-fill text-primary"></i> Segurança</h1>
  <p class="text-sm text-slate-500">Autenticação em 2 Fatores (2FA)</p>
</div>

<?php if ($recovery_show): ?>
<div class="card p-6 mb-5 border-2 border-amber-500 bg-amber-50">
  <h3 class="font-bold text-amber-900 mb-2"><i class="bi bi-key-fill"></i> CÓDIGOS DE RECUPERAÇÃO</h3>
  <p class="text-sm text-amber-800 mb-3"><strong>Guarde estes códigos em local seguro!</strong> Cada um pode ser usado uma única vez caso você perca acesso ao seu método 2FA.</p>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-2 font-mono text-sm">
    <?php foreach($recovery_show as $rc): ?>
      <div class="bg-white p-2 rounded border text-center"><?= e($rc) ?></div>
    <?php endforeach; ?>
  </div>
  <button onclick="window.print()" class="btn bg-amber-600 text-white mt-3"><i class="bi bi-printer"></i> Imprimir</button>
</div>
<?php endif; ?>

<div class="card p-6 mb-5">
  <div class="flex items-center justify-between">
    <div>
      <h3 class="font-bold">Status do 2FA</h3>
      <p class="text-sm text-slate-500">Método atual: <strong class="capitalize"><?= e($u['mfa_metodo']) ?></strong></p>
    </div>
    <?php if ($u['mfa_metodo'] !== 'nenhum'): ?>
      <form method="POST" class="inline">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="disable">
        <button data-confirm="Desativar 2FA? Sua conta ficará menos segura." class="btn bg-red-600 text-white">
          <i class="bi bi-shield-x"></i> Desativar 2FA
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($u['mfa_metodo'] === 'nenhum'): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <!-- TOTP -->
  <div class="card p-6">
    <h3 class="font-bold text-lg mb-2"><i class="bi bi-phone"></i> App Autenticador (Recomendado)</h3>
    <p class="text-sm text-slate-600 mb-3">Use Google Authenticator, Microsoft Authenticator, Authy, etc.</p>
    <form method="POST" class="space-y-3">
      <?= csrf_field() ?>
      <input type="hidden" name="acao" value="enable_totp">
      <input type="hidden" name="secret" value="<?= e($novo_secret) ?>">

      <div class="text-center bg-slate-50 p-4 rounded">
        <img src="<?= $qr_url ?>" alt="QR Code" class="mx-auto mb-2">
        <p class="text-xs text-slate-500">Ou digite manualmente:</p>
        <code class="text-xs font-mono bg-white px-2 py-1 rounded border"><?= e($novo_secret) ?></code>
      </div>

      <div>
        <label class="text-sm font-medium">Código de 6 dígitos do app:</label>
        <input name="codigo" required maxlength="6" pattern="[0-9]{6}" class="form-input text-center text-xl tracking-widest font-mono" placeholder="000000">
      </div>
      <button class="btn btn-primary w-full"><i class="bi bi-check-lg"></i> Ativar TOTP</button>
    </form>
  </div>

  <!-- Email -->
  <div class="card p-6">
    <div class="flex items-start justify-between gap-3 mb-2">
      <h3 class="font-bold text-lg"><i class="bi bi-envelope-at"></i> E-mail</h3>
      <span class="badge <?= $smtpReady ? 'badge-success' : 'badge-warning' ?>"><?= $smtpReady ? 'SMTP pronto' : 'Revisar SMTP' ?></span>
    </div>
    <p class="text-sm text-slate-600 mb-3">Receba um código de 6 dígitos por e-mail a cada login.</p>
    <p class="text-xs text-slate-500 mb-3">Será enviado para: <strong><?= e($u['email']) ?></strong></p>
    <?php if (!$smtpReady): ?>
      <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 mb-4">
        O SMTP autenticado precisa estar configurado para o 2FA por e-mail funcionar corretamente.<br>
        <a href="<?= APP_URL ?>/modules/empresa/smtp_teste.php" class="font-semibold underline">Abrir teste SMTP</a>
      </div>
    <?php endif; ?>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="acao" value="enable_email">
      <button class="btn btn-primary w-full" <?= $smtpReady ? '' : 'disabled' ?>><i class="bi bi-envelope-check"></i> Ativar 2FA por E-mail</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
