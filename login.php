<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/totp.php';
require_once __DIR__ . '/includes/patient_portal.php';

if (is_logged()) redirect(APP_URL . '/dashboard.php');

$erro = '';
$step = $_SESSION['login_step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    // Etapa 1: usuário + senha
    if ($step === 1) {
        $email = trim(input('email'));
        $senha = input('senha');
        try {
            $stmt = db()->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
            $stmt->execute([$email]);
            $u = $stmt->fetch();
            if ($u && password_verify($senha, $u['senha'])) {
                // Tem 2FA? Vai para etapa 2
                if (($u['mfa_metodo'] ?? 'nenhum') !== 'nenhum') {
                    $podeRedirecionar2FA = true;
                    $_SESSION['login_pending_user'] = $u['id'];
                    $_SESSION['login_step'] = 2;

                    if ($u['mfa_metodo'] === 'email') {
                        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                        db()->prepare("UPDATE usuarios SET mfa_codigo_temp=?, mfa_codigo_expira=DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id=?")
                            ->execute([$codigo, $u['id']]);
                        $envio = send_login_2fa_code_email($u, $codigo);
                        if (!$envio['ok']) {
                            db()->prepare("UPDATE usuarios SET mfa_codigo_temp=NULL, mfa_codigo_expira=NULL WHERE id=?")
                                ->execute([$u['id']]);
                            unset($_SESSION['login_pending_user'], $_SESSION['login_step']);
                            $erro = 'Não foi possível enviar o código por e-mail: ' . $envio['error'];
                            $podeRedirecionar2FA = false;
                        }
                    }
                    if ($podeRedirecionar2FA) {
                        redirect(APP_URL . '/login.php');
                    }
                }

                if (($u['mfa_metodo'] ?? 'nenhum') === 'nenhum' && ($u['perfil'] ?? '') !== 'desenvolvedor' && !license_is_valid($u['empresa_id'])) {
                    $erro = 'Licença da empresa inválida, expirada ou bloqueada. Contate o desenvolvedor responsável.';
                } elseif (($u['mfa_metodo'] ?? 'nenhum') === 'nenhum') {
                    // Sem 2FA: login direto
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $u['id'];
                    $_SESSION['empresa_id'] = $u['empresa_id'];
                    $_SESSION['active_empresa_id'] = $u['empresa_id'];
                    $_SESSION['perfil'] = $u['perfil'];
                    $_SESSION['nome'] = $u['nome'];
                    $_SESSION['last_activity'] = time();
                    db()->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$u['id']]);
                    log_action('login', 'usuarios', $u['id']);
                    redirect(post_login_redirect($u));
                }
            } else {
                $erro = 'E-mail ou senha incorretos.';
            }
        } catch (Exception $e) {
            $erro = 'Erro: ' . $e->getMessage();
        }
    }
    // Etapa 2: código MFA
    elseif ($step === 2) {
        $uid = $_SESSION['login_pending_user'] ?? 0;
        $codigo = trim(input('codigo'));
        $stmt = db()->prepare("SELECT * FROM usuarios WHERE id=?");
        $stmt->execute([$uid]);
        $u = $stmt->fetch();
        $ok = false;
        if ($u && (int) $u['ativo'] === 1) {
            if ($u['mfa_metodo'] === 'totp' && TOTP::verify($u['mfa_secret'], $codigo)) {
                $ok = true;
            } elseif ($u['mfa_metodo'] === 'email' && $codigo === $u['mfa_codigo_temp']
                      && strtotime($u['mfa_codigo_expira']) > time()) {
                $ok = true;
                db()->prepare("UPDATE usuarios SET mfa_codigo_temp=NULL WHERE id=?")->execute([$uid]);
            } else {
                $recovery = json_decode($u['mfa_codigos_recuperacao'] ?? '[]', true) ?: [];
                if (in_array(strtoupper($codigo), $recovery)) {
                    $ok = true;
                    $recovery = array_values(array_diff($recovery, [strtoupper($codigo)]));
                    db()->prepare("UPDATE usuarios SET mfa_codigos_recuperacao=? WHERE id=?")
                        ->execute([json_encode($recovery), $uid]);
                }
            }
        }

        if ($ok) {
            if (($u['perfil'] ?? '') !== 'desenvolvedor' && !license_is_valid($u['empresa_id'])) {
                unset($_SESSION['login_step'], $_SESSION['login_pending_user']);
                $erro = 'Licença da empresa inválida, expirada ou bloqueada. Contate o desenvolvedor responsável.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['empresa_id'] = $u['empresa_id'];
                $_SESSION['active_empresa_id'] = $u['empresa_id'];
                $_SESSION['perfil'] = $u['perfil'];
                $_SESSION['nome'] = $u['nome'];
                $_SESSION['last_activity'] = time();
                unset($_SESSION['login_step'], $_SESSION['login_pending_user']);
                db()->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$u['id']]);
                log_action('login_2fa', 'usuarios', $u['id']);
                redirect(post_login_redirect($u));
            }
        } else {
            $erro = 'Código inválido ou expirado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="<?= APP_URL ?>/assets/img/icon-192.png">
<title>Login - <?= APP_NAME ?></title>
<?= clinic_asset_styles(['resources/entries/auth.js']) ?>
</head>
<body class="login-bg flex items-center justify-center p-4">
<div class="login-card w-full max-w-md p-8 rounded-2xl shadow-2xl fade-in">
  <div class="text-center mb-6">
    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-700 text-white text-4xl mb-3 shadow-lg">
      <i class="bi bi-heart-pulse-fill"></i>
    </div>
    <h1 class="text-2xl font-bold text-slate-800"><?= APP_NAME ?></h1>
    <p class="text-sm text-slate-500 mt-1">Sistema de Gestão para clínicas médicas, odontológicas e psicologia</p>
  </div>

  <?php if ($erro): ?>
  <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
    <i class="bi bi-exclamation-circle"></i> <?= e($erro) ?>
  </div>
  <?php endif; ?>
  <?php if (isset($_GET['timeout'])): ?>
  <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-4 text-sm">
    Sessão expirada. Faça login novamente.
  </div>
  <?php endif; ?>

  <?php if ($step === 1): ?>
  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">E-mail</label>
      <div class="relative">
        <i class="bi bi-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="email" name="email" required autofocus class="form-input pl-10" placeholder="seu@email.com" value="<?= e(input('email')) ?>">
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Senha</label>
      <div class="relative">
        <i class="bi bi-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="password" name="senha" required class="form-input pl-10" placeholder="••••••••">
      </div>
    </div>
    <button type="submit" class="w-full py-2.5 rounded-lg bg-gradient-to-r from-violet-500 to-blue-700 text-white font-semibold hover:opacity-90 transition shadow-md">
      <i class="bi bi-box-arrow-in-right mr-1"></i> Entrar
    </button>
  </form>
  <?php else: ?>
  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <div class="text-center">
      <i class="bi bi-shield-lock-fill text-4xl text-primary"></i>
      <h2 class="text-lg font-bold mt-2">Verificação em 2 Etapas</h2>
      <p class="text-xs text-slate-500">Digite o código do seu app autenticador ou e-mail.</p>
    </div>
    <div>
      <input type="text" name="codigo" required autofocus maxlength="8" pattern="[0-9A-Za-z]+" class="form-input text-center text-2xl tracking-widest font-mono" placeholder="000000">
    </div>
    <button type="submit" class="w-full py-2.5 rounded-lg bg-gradient-to-r from-violet-500 to-blue-700 text-white font-semibold">
      Verificar
    </button>
    <a href="<?= APP_URL ?>/login.php?cancel=1" class="block text-center text-xs text-slate-500 mt-2">Cancelar</a>
  </form>
  <?php endif; ?>

  <?php if (portal_companies()): ?>
  <div class="mt-6 pt-4 border-t border-slate-200 text-center">
    <a href="<?= APP_URL ?>/portal/index.php" class="btn btn-primary w-full"><i class="bi bi-person-heart"></i> Área do paciente</a>
    <p class="text-xs text-slate-500 mt-2">Consulte sua agenda e solicite um atendimento.</p>
  </div>
  <?php endif; ?>
  <div class="mt-6 pt-4 border-t border-slate-200 text-center text-xs text-slate-500">
    <p>© <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?></p>
  </div>
</div>
<?= clinic_asset_scripts(['resources/entries/auth.js']) ?>
</body>
</html>
<?php if (isset($_GET['cancel'])) { unset($_SESSION['login_step'], $_SESSION['login_pending_user']); } ?>
