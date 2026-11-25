<?php
$page_title = 'Novo Usuário';
require_once __DIR__ . '/../../includes/header.php';
require_admin();

$hasRepasse = db_column_exists('usuarios', 'percentual_repasse');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    if (!is_developer() && input('perfil') === 'desenvolvedor') { http_response_code(403); exit('Perfil restrito ao desenvolvedor.'); }
    try {
        if (strlen(input('senha')) < 6) throw new Exception('Senha deve ter ao menos 6 caracteres');
        $perfil = input('perfil', 'profissional');
        if (!in_array($perfil, ['admin','profissional','recepcao'], true)) {
            throw new Exception('Perfil inválido para este módulo.');
        }
        $especialidade = trim(input('especialidade'));
        $temaEspecialidade = specialty_theme($especialidade);
        $temaPreferido = ($temaEspecialidade && $temaEspecialidade !== 'medico') ? $temaEspecialidade : 'medico';
        $percentualRepasse = $hasRepasse ? max(0, min(100, (float) input('percentual_repasse', 0))) : null;

        if ($hasRepasse) {
            $stmt = db()->prepare("INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, especialidade, registro_profissional, telefone, tema_preferido, percentual_repasse, ativo) VALUES (?,?,?,?,?,?,?,?,?,?,1)");
            $stmt->execute([
                active_company_id(), input('nome'), input('email'),
                password_hash(input('senha'), PASSWORD_BCRYPT, ['cost' => HASH_COST]),
                $perfil, $especialidade, input('registro_profissional'), only_numbers(input('telefone')),
                $temaPreferido, $percentualRepasse,
            ]);
        } else {
            $stmt = db()->prepare("INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, especialidade, registro_profissional, telefone, tema_preferido, ativo) VALUES (?,?,?,?,?,?,?,?,?,1)");
            $stmt->execute([
                active_company_id(), input('nome'), input('email'),
                password_hash(input('senha'), PASSWORD_BCRYPT, ['cost' => HASH_COST]),
                $perfil, $especialidade, input('registro_profissional'), only_numbers(input('telefone')),
                $temaPreferido,
            ]);
        }
        $id = (int) db()->lastInsertId();
        log_action('usuarios_criar', 'usuarios', $id, 'Usuário criado com perfil ' . $perfil . '.');
        flash('success', 'Usuário criado!');
        redirect('index.php');
    } catch (Exception $e) {
        flash('error', 'Erro: ' . $e->getMessage());
    }
}
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold">Novo Usuário</h1></div>
<?php $u = []; require __DIR__ . '/_form.php'; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
