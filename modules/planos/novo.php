<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Novo Plano de Tratamento';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    try {
        $pid = (int)input('paciente_id');
        clinic_company_record('pacientes', $pid);
        $titulo = input('titulo');
        $desconto = (float)str_replace(',','.',str_replace('.','',input('desconto',0)));
        $parcelas = (int)input('parcelas',1);

        $itens_json = input('itens','[]');
        $itens = json_decode($itens_json, true) ?: [];
        $total = 0;
        if (!is_array($itens) || !$itens || count($itens) > 100) throw new InvalidArgumentException('Informe itens válidos.');
        foreach ($itens as &$it) {
            if (!is_array($it)) throw new InvalidArgumentException('Item inválido.');
            clinic_company_record('procedimentos', (int) ($it['procedimento_id'] ?? 0), true);
            $qty = (float) ($it['quantidade'] ?? 1); $unit = (float) ($it['valor_unitario'] ?? 0);
            if ($qty <= 0 || $unit < 0 || !is_finite($qty * $unit)) throw new InvalidArgumentException('Quantidade/valor inválido.');
            $it['valor_total'] = round($qty * $unit, 2);
            $total += $it['valor_total'];
        }
        unset($it);
        if ($desconto < 0 || $desconto > $total || $parcelas < 1) throw new InvalidArgumentException('Desconto ou parcelas inválidos.');
        $final = $total - $desconto;

        db()->beginTransaction();
        db()->prepare("INSERT INTO planos_tratamento (empresa_id, paciente_id, profissional_id, titulo, descricao, valor_total, desconto, valor_final, parcelas, status, validade, observacoes) VALUES (?,?,?,?,?,?,?,?,?,'orcamento',?,?)")
            ->execute([active_company_id(), $pid, $_SESSION['user_id'], $titulo, input('descricao'),
                       $total, $desconto, $final, $parcelas, input('validade'), input('observacoes')]);
        $plano_id = db()->lastInsertId();

        $ins = db()->prepare("INSERT INTO plano_tratamento_itens (empresa_id, plano_id, procedimento_id, dente, face, descricao, quantidade, valor_unitario, valor_total, ordem) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $i = 1;
        foreach($itens as $it) {
            $ins->execute([
                active_company_id(), $plano_id,
                $it['procedimento_id'] ?: null,
                $it['dente'] ?? null,
                $it['face'] ?? null,
                $it['descricao'],
                $it['quantidade'] ?: 1,
                $it['valor_unitario'],
                $it['valor_total'],
                $i++
            ]);
        }
        db()->commit();
        flash('success','Plano criado!');
        redirect('ver.php?id='.$plano_id);
    } catch (Exception $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash('error','Erro: '.$e->getMessage());
    }
}

$pacs = db()->prepare("SELECT id,nome FROM pacientes WHERE empresa_id=? AND ativo=1 ORDER BY nome");
$pacs->execute([active_company_id()]); $pacs = $pacs->fetchAll();
$procs = db()->prepare("SELECT * FROM procedimentos WHERE empresa_id=? AND ativo=1");
$procs->execute([active_company_id()]); $procs = $procs->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold">Novo Plano de Tratamento</h1></div>

<form method="POST" onsubmit="prepararEnvio()" class="space-y-5">
  <?= csrf_field() ?>
  <input type="hidden" name="itens" id="itens">

  <div class="card p-6 space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="md:col-span-2">
        <label class="text-sm">Paciente *</label>
        <select data-patient-search name="paciente_id" required class="form-select"><option value="">--</option>
          <?php foreach($pacs as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nome']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label class="text-sm">Validade do orçamento</label><input type="date" name="validade" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="form-input"></div>
      <div class="md:col-span-3"><label class="text-sm">Título *</label><input name="titulo" required placeholder="Ex: Tratamento Reabilitador Superior" class="form-input"></div>
      <div class="md:col-span-3"><label class="text-sm">Descrição geral</label><textarea name="descricao" rows="2" class="form-textarea"></textarea></div>
    </div>
  </div>

  <div class="card p-6">
    <div class="flex justify-between items-center mb-3">
      <h3 class="font-bold">Itens do Tratamento</h3>
      <button type="button" onclick="addItem()" class="btn btn-primary"><i class="bi bi-plus"></i> Adicionar Item</button>
    </div>
    <table class="table-modern" id="tabela-itens">
      <thead><tr><th>Procedimento</th><th>Dente</th><th>Face</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th><th></th></tr></thead>
      <tbody id="tbody-itens"></tbody>
      <tfoot>
        <tr class="font-bold bg-slate-50">
          <td colspan="5" class="text-right">Subtotal:</td>
          <td id="subtotal">R$ 0,00</td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="card p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    <div><label class="text-sm">Desconto (R$)</label><input id="desconto" name="desconto" value="0,00" oninput="recalcular()" class="form-input"></div>
    <div><label class="text-sm">Parcelas</label><input type="number" name="parcelas" value="1" min="1" max="36" id="parcelas" oninput="recalcular()" class="form-input"></div>
    <div>
      <label class="text-sm">Valor Final</label>
      <p class="text-2xl font-bold text-primary" id="valor_final">R$ 0,00</p>
      <p class="text-xs text-slate-500" id="parcela_info">À vista</p>
    </div>
    <div class="md:col-span-3"><label class="text-sm">Observações</label><textarea name="observacoes" rows="2" class="form-textarea"></textarea></div>
  </div>

  <div class="flex justify-end gap-2">
    <a href="index.php" class="btn bg-slate-200">Cancelar</a>
    <button class="btn btn-primary"><i class="bi bi-check"></i> Salvar Plano</button>
  </div>
</form>

<script>
const procedimentos = <?= json_encode($procs) ?>;
const dentes = [
  '18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28',
  '48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38',
  '55','54','53','52','51','61','62','63','64','65','85','84','83','82','81','71','72','73','74','75'
];
const faces = ['Vestibular','Lingual','Mesial','Distal','Oclusal','Incisal','Geral'];
let itens = [];

function addItem() {
  itens.push({procedimento_id:'',dente:'',face:'',descricao:'',quantidade:1,valor_unitario:0,valor_total:0});
  renderItens();
}
function removerItem(i) { itens.splice(i,1); renderItens(); }
function atualizarItem(i, campo, valor) {
  itens[i][campo] = valor;
  if (campo === 'procedimento_id') {
    const p = procedimentos.find(p => p.id == valor);
    if (p) { itens[i].descricao = p.nome; itens[i].valor_unitario = parseFloat(p.valor); }
  }
  if (campo === 'quantidade' || campo === 'valor_unitario' || campo === 'procedimento_id') {
    itens[i].valor_total = (itens[i].quantidade || 1) * (itens[i].valor_unitario || 0);
  }
  renderItens();
}
function renderItens() {
  const tb = document.getElementById('tbody-itens');
  tb.innerHTML = itens.map((it, i) => `
    <tr>
      <td>
        <select onchange="atualizarItem(${i},'procedimento_id',this.value)" class="form-select text-xs">
          <option value="">-- Custom --</option>
          ${procedimentos.map(p=>`<option value="${p.id}" ${it.procedimento_id==p.id?'selected':''}>${p.nome}</option>`).join('')}
        </select>
        <input value="${it.descricao||''}" onchange="atualizarItem(${i},'descricao',this.value)" class="form-input text-xs mt-1" placeholder="Descrição">
      </td>
      <td><select onchange="atualizarItem(${i},'dente',this.value)" class="form-select text-xs"><option value="">--</option>${dentes.map(d=>`<option ${it.dente==d?'selected':''}>${d}</option>`).join('')}</select></td>
      <td><select onchange="atualizarItem(${i},'face',this.value)" class="form-select text-xs"><option value="">--</option>${faces.map(f=>`<option ${it.face==f?'selected':''}>${f}</option>`).join('')}</select></td>
      <td><input type="number" value="${it.quantidade}" min="1" onchange="atualizarItem(${i},'quantidade',parseInt(this.value))" class="form-input text-xs w-16"></td>
      <td><input value="${it.valor_unitario.toFixed(2).replace('.',',')}" onchange="atualizarItem(${i},'valor_unitario',parseFloat(this.value.replace(',','.'))||0)" class="form-input text-xs w-24"></td>
      <td class="font-semibold">${'R$ ' + it.valor_total.toFixed(2).replace('.',',')}</td>
      <td><button type="button" onclick="removerItem(${i})" class="text-red-600"><i class="bi bi-trash"></i></button></td>
    </tr>
  `).join('');
  recalcular();
}
function recalcular() {
  const sub = itens.reduce((a,b)=>a+b.valor_total,0);
  document.getElementById('subtotal').textContent = 'R$ '+sub.toFixed(2).replace('.',',');
  const desc = parseFloat((document.getElementById('desconto').value||'0').replace(',','.'))||0;
  const final = sub - desc;
  const parc = parseInt(document.getElementById('parcelas').value)||1;
  document.getElementById('valor_final').textContent = 'R$ '+final.toFixed(2).replace('.',',');
  document.getElementById('parcela_info').textContent = parc>1 ? `${parc}x de R$ ${(final/parc).toFixed(2).replace('.',',')}` : 'À vista';
}
function prepararEnvio() {
  document.getElementById('itens').value = JSON.stringify(itens);
}
addItem();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
