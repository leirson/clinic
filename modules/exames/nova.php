<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Nova Solicitação de Exames';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    try {
        clinic_validate_clinical_links((int) input('paciente_id'), (int) input('prontuario_id'), (int) input('agendamento_id'));
        db()->prepare("INSERT INTO solicitacoes_exame (empresa_id, paciente_id, profissional_id, prontuario_id, exames, indicacao_clinica, cid_codigo, urgencia, data_solicitacao, status, observacoes) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([
                active_company_id(), (int)input('paciente_id'), $_SESSION['user_id'],
                (int)input('prontuario_id') ?: null,
                input('exames'),
                input('indicacao_clinica'),
                input('cid_codigo'),
                input('urgencia','rotina'),
                input('data_solicitacao', date('Y-m-d')),
                'solicitado',
                input('observacoes')
            ]);
        $id = db()->lastInsertId();
        flash('success','Solicitação criada!');
        redirect('pdf.php?id='.$id);
    } catch (Exception $e) {
        flash('error','Erro: '.$e->getMessage());
    }
}

$pacs = db()->prepare("SELECT id,nome FROM pacientes WHERE empresa_id=? AND ativo=1 ORDER BY nome");
$pacs->execute([active_company_id()]); $pacs = $pacs->fetchAll();

$modelos = db()->prepare("SELECT * FROM exames_modelos WHERE empresa_id=? AND ativo=1 ORDER BY categoria, nome");
$modelos->execute([active_company_id()]); $modelos = $modelos->fetchAll();

$paciente_id = (int)input('paciente_id');
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold">Nova Solicitação de Exames</h1></div>

<form method="POST" class="card p-6 space-y-4">
  <?= csrf_field() ?>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="md:col-span-2">
      <label class="text-sm">Paciente *</label>
      <select data-patient-search name="paciente_id" required class="form-select">
        <option value="">--</option>
        <?php foreach($pacs as $p): ?><option value="<?= $p['id'] ?>" <?= $p['id']==$paciente_id?'selected':'' ?>><?= e($p['nome']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm">Urgência</label>
      <select name="urgencia" class="form-select">
        <option value="rotina">Rotina</option>
        <option value="urgente">Urgente</option>
        <option value="emergencia">Emergência</option>
      </select>
    </div>
    <div>
      <label class="text-sm">Data</label>
      <input type="date" name="data_solicitacao" value="<?= date('Y-m-d') ?>" class="form-input">
    </div>
    <div class="md:col-span-2 relative">
      <label class="text-sm">CID-10 (busca por código ou descrição)</label>
      <input id="cidBusca" autocomplete="off" placeholder="Digite ao menos 2 caracteres..." class="form-input">
      <input type="hidden" name="cid_codigo" id="cidCodigo">
      <div id="cidLista" class="absolute z-10 bg-white border rounded-lg shadow-lg w-full mt-1 max-h-60 overflow-auto hidden"></div>
      <p class="text-xs text-slate-500 mt-1" id="cidSel"></p>
    </div>
  </div>

  <div>
    <h3 class="font-bold text-sm mb-2">📋 Modelos Pré-prontos</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
      <?php foreach($modelos as $m): ?>
      <button type="button" onclick="usarModelo(<?= htmlspecialchars(json_encode($m),ENT_QUOTES) ?>)" class="text-left p-3 border rounded-lg hover:border-primary hover:bg-blue-50 text-sm">
        <div class="font-semibold"><?= e($m['nome']) ?></div>
        <div class="text-xs text-slate-500"><?= e($m['categoria']) ?></div>
      </button>
      <?php endforeach; ?>
    </div>
  </div>

  <div>
    <label class="text-sm">Exames Solicitados *</label>
    <textarea name="exames" id="exames" rows="8" required class="form-textarea font-mono text-sm" placeholder="1. Hemograma completo&#10;2. Glicose em jejum&#10;..."></textarea>
  </div>

  <div>
    <label class="text-sm">Indicação Clínica</label>
    <textarea name="indicacao_clinica" rows="2" class="form-textarea" placeholder="Justificativa clínica para os exames..."></textarea>
  </div>

  <div>
    <label class="text-sm">Observações / Instruções ao paciente</label>
    <textarea name="observacoes" id="obs" rows="3" class="form-textarea" placeholder="Jejum, coleta de urina, etc."></textarea>
  </div>

  <div class="flex justify-end gap-2"><a href="index.php" class="btn bg-slate-200">Cancelar</a><button class="btn btn-primary"><i class="bi bi-printer"></i> Emitir e Imprimir</button></div>
</form>

<script>
function usarModelo(m) {
  document.getElementById('exames').value = m.exames;
  if (m.instrucoes) document.getElementById('obs').value = m.instrucoes;
}

// Autocomplete CID-10
const cb = document.getElementById('cidBusca');
const cl = document.getElementById('cidLista');
let timer;
cb.addEventListener('input', () => {
  clearTimeout(timer);
  const q = cb.value.trim();
  if (q.length < 2) { cl.classList.add('hidden'); return; }
  timer = setTimeout(async () => {
    const r = await fetch('<?= APP_URL ?>/api/cid10_search.php?q=' + encodeURIComponent(q));
    const dados = await r.json();
    cl.innerHTML = dados.map(d => `<div class="px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm border-b" onclick="selCID('${d.codigo}','${d.descricao.replace(/'/g,"\\'")}')"><strong>${d.codigo}</strong> — ${d.descricao}</div>`).join('') || '<div class="p-2 text-xs text-slate-400">Sem resultados</div>';
    cl.classList.remove('hidden');
  }, 200);
});
function selCID(cod, desc) {
  document.getElementById('cidCodigo').value = cod;
  cb.value = cod + ' — ' + desc;
  document.getElementById('cidSel').textContent = '✓ ' + cod + ': ' + desc;
  cl.classList.add('hidden');
}
document.addEventListener('click', e => { if (!e.target.closest('#cidBusca') && !e.target.closest('#cidLista')) cl.classList.add('hidden'); });
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
