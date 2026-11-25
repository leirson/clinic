<?php
require_once __DIR__ . '/../../includes/functions.php';
require_developer();
$page_title = 'Alterar Senha do Desenvolvedor';
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    try {
        $atual = input('senha_atual');
        $nova = input('nova_senha');
        $confirmacao = input('confirmar_senha');

        if (!password_verify($atual, $user['senha'])) {
            throw new Exception('Senha atual incorreta.');
        }
        if (strlen($nova) < 8) {
            throw new Exception('A nova senha deve ter ao menos 8 caracteres.');
        }
        if ($nova !== $confirmacao) {
            throw new Exception('A confirmação da nova senha não confere.');
        }

        db()->prepare("UPDATE usuarios SET senha = ? WHERE id = ? AND perfil = 'desenvolvedor'")
            ->execute([password_hash($nova, PASSWORD_BCRYPT, ['cost' => HASH_COST]), $user['id']]);

        flash('success', 'Senha do desenvolvedor atualizada com sucesso.');
        redirect(APP_URL . '/modules/desenvolvedor/senha.php');
    } catch (Exception $e) {
        flash('error', 'Erro: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="max-w-2xl mx-auto">
  <div class="mb-5">
    <a href="<?= APP_URL ?>/modules/desenvolvedor/index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar ao painel</a>
    <h1 class="text-2xl font-bold text-slate-800 mt-2"><i class="bi bi-lock text-primary"></i> Alterar Minha Senha</h1>
    <p class="text-sm text-slate-500">Área reservada para a conta desenvolvedor oculta do módulo de usuários.</p>
  </div>

  <form method="POST" class="card p-6 space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="text-sm font-medium">Senha atual</label>
      <input type="password" name="senha_atual" required class="form-input">
    </div>
    <div>
      <label class="text-sm font-medium">Nova senha</label>
      <input type="password" name="nova_senha" required class="form-input">
    </div>
    <div>
      <label class="text-sm font-medium">Confirmar nova senha</label>
      <input type="password" name="confirmar_senha" required class="form-input">
    </div>
    <div class="flex justify-end gap-2 pt-3 border-t">
      <a href="<?= APP_URL ?>/modules/desenvolvedor/index.php" class="btn bg-slate-100 text-slate-700">Cancelar</a>
      <button class="btn btn-primary"><i class="bi bi-shield-lock"></i> Atualizar senha</button>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
