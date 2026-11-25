<?php
$page_title = 'Meu Perfil';
require_once __DIR__ . '/../../includes/header.php';

$id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    try {
        $especialidade = trim(input('especialidade'));
        $temaPreferido = normalize_theme_choice(input('tema_preferido', 'medico'));
        clinic_require_specialty($temaPreferido);
        $temaEspecialidade = specialty_theme($especialidade);
        if ($temaPreferido === 'medico' && $temaEspecialidade && $temaEspecialidade !== 'medico') {
            $temaPreferido = clinic_available_theme($temaEspecialidade);
        }

        $set = "nome=?, email=?, telefone=?, especialidade=?, registro_profissional=?, tema_preferido=?";
        $vals = [input('nome'), input('email'), only_numbers(input('telefone')),
                 $especialidade, input('registro_profissional'), $temaPreferido];
        if (input('senha')) {
            if (strlen(input('senha')) < 6) throw new Exception('Senha mínima 6 caracteres');
            $set .= ", senha=?";
            $vals[] = password_hash(input('senha'), PASSWORD_BCRYPT, ['cost'=>HASH_COST]);
        }
        $vals[] = $id;
        db()->prepare("UPDATE usuarios SET $set WHERE id = ?")->execute($vals);
        flash('success','Perfil atualizado!');
        redirect('perfil.php');
    } catch (Exception $e) {
        flash('error','Erro: '.$e->getMessage());
    }
}

$u = current_user();
?>
<div class="mb-5"><h1 class="text-2xl font-bold"><i class="bi bi-person-circle"></i> Meu Perfil</h1></div>
<form method="POST" class="card p-6 space-y-4 max-w-3xl">
  <?= csrf_field() ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div><label class="text-sm">Nome</label><input name="nome" required value="<?= e($u['nome']) ?>" class="form-input"></div>
    <div><label class="text-sm">E-mail</label><input name="email" type="email" required value="<?= e($u['email']) ?>" class="form-input"></div>
    <div><label class="text-sm">Telefone</label><input name="telefone" data-mask="(##) #####-####" value="<?= e($u['telefone']) ?>" class="form-input"></div>
    <div><label class="text-sm">Especialidade</label><input name="especialidade" value="<?= e($u['especialidade']) ?>" class="form-input"></div>
    <div><label class="text-sm">Registro (CRM/CRO/CRP)</label><input name="registro_profissional" value="<?= e($u['registro_profissional']) ?>" class="form-input"></div>
    <div>
      <label class="text-sm">Tema Preferido</label>
      <select name="tema_preferido" class="form-select">
        <?php foreach (theme_select_choices() as $themeKey => $themeLabel): ?>
          <option value="<?= e($themeKey) ?>" <?= ($u['tema_preferido'] ?? 'medico') === $themeKey ? 'selected' : '' ?>><?= e($themeLabel) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2"><label class="text-sm">Nova Senha (deixe vazio para não alterar)</label><input name="senha" type="password" class="form-input"></div>
  </div>
  <div class="flex justify-end"><button class="btn btn-primary"><i class="bi bi-check"></i> Salvar</button></div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
