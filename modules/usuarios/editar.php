<?php
$page_title = 'Editar Usuário';
require_once __DIR__ . '/../../includes/header.php';
require_admin();

$id = (int) input('id');
$eid = active_company_id();
$hasRepasse = db_column_exists('usuarios', 'percentual_repasse');

$stmt = db()->prepare("SELECT * FROM usuarios WHERE id=? AND empresa_id=? AND perfil <> 'desenvolvedor'");
$stmt->execute([$id, $eid]);
$u = $stmt->fetch();
if (!$u) {
    flash('error', 'Usuário não encontrado');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    if (!is_developer() && input('perfil') === 'desenvolvedor') { http_response_code(403); exit('Perfil restrito ao desenvolvedor.'); }
    try {
        $perfil = input('perfil', 'profissional');
        if (!in_array($perfil, ['admin','profissional','recepcao'], true)) {
            throw new Exception('Perfil inválido para este módulo.');
        }

        $especialidade = trim((string) input('especialidade'));
        $temaPreferido = normalize_theme_choice($u['tema_preferido'] ?? 'medico');
        if ($temaPreferido === 'medico') {
            $temaEspecialidade = specialty_theme($especialidade);
            if ($temaEspecialidade && $temaEspecialidade !== 'medico') {
                $temaPreferido = $temaEspecialidade;
            }
        }

        $set = "nome=?, email=?, perfil=?, especialidade=?, registro_profissional=?, telefone=?, tema_preferido=?, ativo=?";
        $vals = [
            input('nome'),
            input('email'),
            $perfil,
            $especialidade,
            input('registro_profissional'),
            only_numbers(input('telefone')),
            $temaPreferido,
            input('ativo') ? 1 : 0,
        ];

        if ($hasRepasse) {
            $set .= ", percentual_repasse=?";
            $vals[] = max(0, min(100, (float) input('percentual_repasse', 0)));
        }

        if (input('senha')) {
            $set .= ", senha=?";
            $vals[] = password_hash(input('senha'), PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        }

        $vals[] = $id;
        $vals[] = $eid;
        db()->prepare("UPDATE usuarios SET {$set} WHERE id = ? AND empresa_id = ?")->execute($vals);

        log_action('usuarios_editar', 'usuarios', $id, 'Usuário atualizado para o perfil ' . $perfil . '.');
        flash('success', 'Usuário atualizado!');
        redirect('index.php');
    } catch (Exception $e) {
        flash('error', 'Erro: ' . $e->getMessage());
    }
}
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold">Editar Usuário</h1></div>
<?php require __DIR__ . '/_form.php'; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>