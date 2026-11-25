<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Novo Lançamento';
$tipo = input('tipo', 'receita');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    try {
        clinic_company_record('pacientes', (int) input('paciente_id'), true);
        clinic_company_record('categorias_financeiras', (int) input('categoria_id'), true);
        clinic_company_record('agendamentos', (int) input('agendamento_id'), true);
        $parcelas = max(1, (int)input('parcelas',1));
        $valor_total = (float)str_replace(',','.',str_replace('.','',input('valor',0)));
        $valor_parcela = round($valor_total / $parcelas, 2);
        $grupo = $parcelas > 1 ? bin2hex(random_bytes(8)) : null;
        $vencimento = input('data_vencimento');

        $stmt = db()->prepare("INSERT INTO contas_financeiras (empresa_id, categoria_id, paciente_id, agendamento_id, tipo, descricao, valor, data_vencimento, forma_pagamento, status, parcela_numero, parcela_total, parcela_grupo, observacoes, criado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        for ($i=1; $i<=$parcelas; $i++) {
            $dt = $i === 1 ? $vencimento : date('Y-m-d', strtotime($vencimento . " +".($i-1)." month"));
            $stmt->execute([
                active_company_id(),
                (int)input('categoria_id') ?: null,
                (int)input('paciente_id') ?: null,
                (int)input('agendamento_id') ?: null,
                input('tipo'),
                input('descricao') . ($parcelas>1 ? " ($i/$parcelas)" : ''),
                $valor_parcela, $dt,
                input('forma_pagamento','dinheiro'),
                input('status','pendente'),
                $i, $parcelas, $grupo,
                input('observacoes'),
                $_SESSION['user_id']
            ]);
        }
        flash('success','Lançamento criado!');
        redirect('index.php');
    } catch (Exception $e) {
        flash('error','Erro: '.$e->getMessage());
    }
}

$cats = db()->prepare("SELECT * FROM categorias_financeiras WHERE empresa_id=? AND tipo=? AND ativo=1");
$cats->execute([active_company_id(), $tipo]);
$cats = $cats->fetchAll();

$pacs = db()->prepare("SELECT id,nome FROM pacientes WHERE empresa_id=? AND ativo=1 ORDER BY nome");
$pacs->execute([active_company_id()]);
$pacs = $pacs->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5">
  <a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a>
  <h1 class="text-2xl font-bold">Nova <?= $tipo==='receita'?'Receita':'Despesa' ?></h1>
</div>
<form method="POST" class="card p-6 space-y-4">
  <?= csrf_field() ?>
  <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2"><label class="text-sm">Descrição *</label><input name="descricao" required class="form-input"></div>
    <div>
      <label class="text-sm">Categoria</label>
      <select name="categoria_id" class="form-select"><option value="">--</option>
        <?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php if ($tipo==='receita'): ?>
    <div>
      <label class="text-sm">Paciente</label>
      <select data-patient-search name="paciente_id" class="form-select"><option value="">--</option>
        <?php foreach($pacs as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nome']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div><label class="text-sm">Valor *</label><input name="valor" required placeholder="0,00" class="form-input"></div>
    <div><label class="text-sm">Parcelas</label><input type="number" name="parcelas" value="1" min="1" max="36" class="form-input"></div>
    <div><label class="text-sm">Data Vencimento *</label><input type="date" name="data_vencimento" required value="<?= date('Y-m-d') ?>" class="form-input"></div>
    <div>
      <label class="text-sm">Forma de Pagamento</label>
      <select name="forma_pagamento" class="form-select">
        <option value="dinheiro">Dinheiro</option><option value="pix">PIX</option>
        <option value="cartao_debito">Cartão Débito</option><option value="cartao_credito">Cartão Crédito</option>
        <option value="boleto">Boleto</option><option value="transferencia">Transferência</option>
        <option value="cheque">Cheque</option><option value="outros">Outros</option>
      </select>
    </div>
    <div>
      <label class="text-sm">Status</label>
      <select name="status" class="form-select">
        <option value="pendente">Pendente</option><option value="pago">Pago</option>
      </select>
    </div>
    <div class="md:col-span-2"><label class="text-sm">Observações</label><textarea name="observacoes" rows="2" class="form-textarea"></textarea></div>
  </div>
  <div class="flex justify-end gap-2"><a href="index.php" class="btn bg-slate-200">Cancelar</a><button class="btn btn-primary">Salvar</button></div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
