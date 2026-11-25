<?php
$page_title = 'Usuários';
require_once __DIR__ . '/../../includes/header.php';
require_admin();

$users = db()->prepare("SELECT * FROM usuarios WHERE empresa_id=? AND perfil <> 'desenvolvedor' ORDER BY nome");
$users->execute([active_company_id()]);
$users = $users->fetchAll();
?>
<div class="flex justify-between items-center mb-5">
  <div><h1 class="text-2xl font-bold"><i class="bi bi-person-gear text-primary"></i> Gestão de Usuários</h1><p class="text-sm text-slate-500">Cadastro de profissionais, recepção e administradores</p></div>
  <a href="novo.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Novo Usuário</a>
</div>

<div class="card overflow-hidden">
  <table class="table-modern">
    <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Registro</th><th>Último Login</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach($users as $u): ?>
      <tr>
        <td><div class="font-medium"><?= e($u['nome']) ?></div><?php if($u['especialidade']): ?><div class="text-xs text-slate-500"><?= e($u['especialidade']) ?></div><?php endif; ?></td>
        <td><?= e($u['email']) ?></td>
        <td><span class="badge badge-info"><?= e($u['perfil']) ?></span></td>
        <td><?= e($u['registro_profissional']) ?></td>
        <td class="text-xs"><?= $u['ultimo_login'] ? format_date($u['ultimo_login'], true) : '—' ?></td>
        <td><span class="badge badge-<?= $u['ativo']?'success':'danger' ?>"><?= $u['ativo']?'Ativo':'Inativo' ?></span></td>
        <td class="text-right">
          <a href="editar.php?id=<?= $u['id'] ?>" class="text-amber-600 mx-1"><i class="bi bi-pencil"></i></a>
          <?php if ($u['id'] != $_SESSION['user_id']): ?>
          <a href="excluir.php?id=<?= $u['id'] ?>" data-confirm="Desativar usuário?" class="text-red-600 mx-1"><i class="bi bi-trash"></i></a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
