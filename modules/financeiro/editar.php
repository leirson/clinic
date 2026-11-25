<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$id = (int)input('id');
$stmt = db()->prepare("SELECT * FROM contas_financeiras WHERE id=? AND empresa_id=?");
$stmt->execute([$id, active_company_id()]);
$c = $stmt->fetch();
if (!$c) { flash('error','Não encontrado'); redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    $valor = (float)str_replace(',','.',str_replace('.','',input('valor')));
    db()->prepare("UPDATE contas_financeiras SET descricao=?, valor=?, data_vencimento=?, categoria_id=?, forma_pagamento=?, status=?, observacoes=?, data_pagamento=IF(?='pago' AND data_pagamento IS NULL, CURDATE(), data_pagamento) WHERE id=?")
        ->execute([input('descricao'), $valor, input('data_vencimento'), (int)input('categoria_id') ?: null,
                   input('forma_pagamento'), input('status'), input('observacoes'), input('status'), $id]);
    flash('success','Atualizado!');
    redirect('index.php');
}

$cats = db()->prepare("SELECT * FROM categorias_financeiras WHERE empresa_id=? AND tipo=?");
$cats->execute([active_company_id(), $c['tipo']]);
$cats = $cats->fetchAll();

$page_title = 'Editar Lançamento';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold">Editar Lançamento</h1></div>
<form method="POST" class="card p-6 space-y-4">
  <?= csrf_field() ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2"><label class="text-sm">Descrição</label><input name="descricao" required value="<?= e($c['descricao']) ?>" class="form-input"></div>
    <div><label class="text-sm">Categoria</label><select name="categoria_id" class="form-select"><option value="">--</option><?php foreach($cats as $cat): ?><option value="<?= $cat['id'] ?>" <?= $cat['id']==$c['categoria_id']?'selected':'' ?>><?= e($cat['nome']) ?></option><?php endforeach; ?></select></div>
    <div><label class="text-sm">Valor</label><input name="valor" value="<?= number_format($c['valor'],2,',','.') ?>" class="form-input"></div>
    <div><label class="text-sm">Vencimento</label><input type="date" name="data_vencimento" value="<?= $c['data_vencimento'] ?>" class="form-input"></div>
    <div><label class="text-sm">Forma</label><select name="forma_pagamento" class="form-select"><?php foreach(['dinheiro','pix','cartao_debito','cartao_credito','boleto','transferencia','cheque','outros'] as $f): ?><option <?= $c['forma_pagamento']===$f?'selected':'' ?>><?= $f ?></option><?php endforeach; ?></select></div>
    <div><label class="text-sm">Status</label><select name="status" class="form-select"><?php foreach(['pendente','pago','atrasado','cancelado'] as $s): ?><option <?= $c['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div>
    <div class="md:col-span-2"><label class="text-sm">Observações</label><textarea name="observacoes" rows="2" class="form-textarea"><?= e($c['observacoes']) ?></textarea></div>
  </div>
  <div class="flex justify-end gap-2"><a href="index.php" class="btn bg-slate-200">Cancelar</a><button class="btn btn-primary">Salvar</button></div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
