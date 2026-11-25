<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Modelos de Exames';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    if (input('acao')==='excluir') {
        db()->prepare("UPDATE exames_modelos SET ativo=0 WHERE id=? AND empresa_id=?")->execute([(int)input('id'), active_company_id()]);
    } elseif (input('acao')==='editar') {
        db()->prepare("UPDATE exames_modelos SET nome=?, categoria=?, exames=?, instrucoes=? WHERE id=? AND empresa_id=?")
            ->execute([input('nome'), input('categoria'), input('exames'), input('instrucoes'), (int)input('id'), active_company_id()]);
    } else {
        db()->prepare("INSERT INTO exames_modelos (empresa_id, nome, categoria, exames, instrucoes) VALUES (?,?,?,?,?)")
            ->execute([active_company_id(), input('nome'), input('categoria'), input('exames'), input('instrucoes')]);
    }
    flash('success','Salvo.');
    redirect('modelos.php');
}

$modelos = db()->prepare("SELECT * FROM exames_modelos WHERE empresa_id=? AND ativo=1 ORDER BY categoria, nome");
$modelos->execute([active_company_id()]); $modelos = $modelos->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold">Modelos de Exames</h1></div>

<form method="POST" class="card p-4 mb-5 grid grid-cols-1 md:grid-cols-2 gap-3">
  <?= csrf_field() ?>
  <div><label class="text-xs">Nome</label><input name="nome" required class="form-input"></div>
  <div><label class="text-xs">Categoria</label><input name="categoria" placeholder="Ex: Cardiologia, Rotina" class="form-input"></div>
  <div class="md:col-span-2"><label class="text-xs">Exames (um por linha)</label><textarea name="exames" rows="4" required class="form-textarea font-mono text-sm"></textarea></div>
  <div class="md:col-span-2"><label class="text-xs">Instruções ao paciente</label><textarea name="instrucoes" rows="2" class="form-textarea"></textarea></div>
  <div class="md:col-span-2 text-right"><button class="btn btn-primary"><i class="bi bi-plus"></i> Criar Modelo</button></div>
</form>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<?php foreach($modelos as $m): ?>
<div class="card p-4">
  <div class="flex justify-between mb-2">
    <div>
      <h3 class="font-bold"><?= e($m['nome']) ?></h3>
      <span class="badge badge-info"><?= e($m['categoria']) ?></span>
    </div>
    <form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="text-red-600" data-confirm="Excluir?"><i class="bi bi-trash"></i></button></form>
  </div>
  <pre class="text-xs whitespace-pre-wrap font-sans text-slate-600 bg-slate-50 p-2 rounded"><?= e($m['exames']) ?></pre>
  <?php if($m['instrucoes']): ?><p class="text-xs text-slate-500 mt-2"><strong>Instruções:</strong> <?= e($m['instrucoes']) ?></p><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
