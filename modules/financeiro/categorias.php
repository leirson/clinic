<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Categorias Financeiras';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    if (input('acao')==='excluir') {
        db()->prepare("UPDATE categorias_financeiras SET ativo=0 WHERE id=? AND empresa_id=?")->execute([(int)input('id'), active_company_id()]);
    } else {
        db()->prepare("INSERT INTO categorias_financeiras (empresa_id,nome,tipo,cor) VALUES (?,?,?,?)")
            ->execute([active_company_id(), input('nome'), input('tipo'), input('cor','#0ea5e9')]);
    }
    flash('success','OK');
    redirect('categorias.php');
}

$cats = db()->prepare("SELECT * FROM categorias_financeiras WHERE empresa_id=? AND ativo=1 ORDER BY tipo, nome");
$cats->execute([active_company_id()]);
$cats = $cats->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold">Categorias Financeiras</h1></div>
<form method="POST" class="card p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
  <?= csrf_field() ?>
  <div><label class="text-xs">Nome</label><input name="nome" required class="form-input"></div>
  <div><label class="text-xs">Tipo</label><select name="tipo" class="form-select"><option value="receita">Receita</option><option value="despesa">Despesa</option></select></div>
  <div><label class="text-xs">Cor</label><input type="color" name="cor" value="#0ea5e9" class="form-input h-10"></div>
  <button class="btn btn-primary"><i class="bi bi-plus"></i> Adicionar</button>
</form>
<div class="card overflow-hidden">
<table class="table-modern">
<thead><tr><th>Cor</th><th>Nome</th><th>Tipo</th><th></th></tr></thead>
<tbody>
<?php foreach($cats as $c): ?>
<tr>
  <td><span class="inline-block w-6 h-6 rounded" style="background:<?= e($c['cor']) ?>"></span></td>
  <td><?= e($c['nome']) ?></td>
  <td><span class="badge <?= $c['tipo']==='receita'?'badge-success':'badge-danger' ?>"><?= $c['tipo'] ?></span></td>
  <td class="text-right">
    <form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="text-red-600" data-confirm="Remover?"><i class="bi bi-trash"></i></button></form>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
