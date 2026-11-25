<?php
$page_title = 'Pacientes';
require_once __DIR__ . '/../../includes/header.php';

$q = trim(input('q'));
$sql = "SELECT * FROM pacientes WHERE empresa_id = ? AND ativo = 1";
$params = [active_company_id()];
if ($q) {
    $sql .= " AND (nome LIKE ? OR cpf_cnpj LIKE ? OR email LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
$sql .= " ORDER BY nome ASC LIMIT 200";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$pacientes = $stmt->fetchAll();
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
  <div>
    <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-people-fill text-primary"></i> Pacientes</h1>
    <p class="text-sm text-slate-500">Cadastro e gestão de pacientes</p>
  </div>
  <a href="novo.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Novo Paciente</a>
</div>

<form method="GET" class="card p-4 mb-4">
  <div class="flex gap-2">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar por nome, CPF/CNPJ ou e-mail..." class="form-input flex-1">
    <button class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
    <?php if ($q): ?><a href="index.php" class="btn bg-slate-100 text-slate-700">Limpar</a><?php endif; ?>
  </div>
</form>

<div class="card overflow-hidden">
  <table class="table-modern">
    <thead>
      <tr>
        <th>Nome</th><th>CPF/CNPJ</th><th>Telefone</th><th>E-mail</th><th>Cidade</th><th class="text-right">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($pacientes)): ?>
        <tr><td colspan="6" class="text-center py-10 text-slate-400">Nenhum paciente encontrado.</td></tr>
      <?php else: foreach ($pacientes as $p): ?>
      <tr>
        <td>
          <div class="font-medium text-slate-800"><?= e($p['nome']) ?></div>
          <?php if ($p['data_nascimento']): ?>
            <div class="text-xs text-slate-500"><?= format_date($p['data_nascimento']) ?></div>
          <?php endif; ?>
        </td>
        <td><?= e(format_cpf_cnpj($p['cpf_cnpj'])) ?></td>
        <td>
          <?php if ($p['celular']): ?>
          <a href="<?= wa_link($p['celular']) ?>" target="_blank" class="text-green-600 hover:underline">
            <i class="bi bi-whatsapp"></i> <?= e(format_phone($p['celular'])) ?>
          </a>
          <?php else: ?>
            <?= e(format_phone($p['telefone'])) ?>
          <?php endif; ?>
        </td>
        <td class="text-sm"><?= e($p['email']) ?></td>
        <td class="text-sm"><?= e($p['cidade'].($p['uf']?' / '.$p['uf']:'')) ?></td>
        <td class="text-right whitespace-nowrap">
          <a href="ver.php?id=<?= $p['id'] ?>" class="text-sky-600 hover:text-sky-800 mx-1" title="Ver"><i class="bi bi-eye"></i></a>
          <a href="../prontuario/index.php?paciente_id=<?= $p['id'] ?>" class="text-purple-600 hover:text-purple-800 mx-1" title="Prontuário"><i class="bi bi-journal-medical"></i></a>
          <a href="../agenda/novo.php?paciente_id=<?= $p['id'] ?>" class="text-green-600 hover:text-green-800 mx-1" title="Agendar"><i class="bi bi-calendar-plus"></i></a>
          <a href="editar.php?id=<?= $p['id'] ?>" class="text-amber-600 hover:text-amber-800 mx-1" title="Editar"><i class="bi bi-pencil"></i></a>
          <a href="excluir.php?id=<?= $p['id'] ?>" data-confirm="Confirma exclusão deste paciente?" class="text-red-600 hover:text-red-800 mx-1" title="Excluir"><i class="bi bi-trash"></i></a>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
