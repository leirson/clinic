<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Nova Receita';

$paciente_id = (int)input('paciente_id');
$prontuario_id = (int)input('prontuario_id') ?: null;
try { clinic_validate_clinical_links($paciente_id, $prontuario_id); }
catch (InvalidArgumentException $e) { http_response_code(404); exit('Paciente/prontuário não encontrado na empresa ativa.'); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    try {
        $conteudo = input('conteudo');
        $tipo = input('tipo','simples');

        // Hash do documento (para verificação de integridade)
        $hash = hash('sha256', $conteudo . $paciente_id . $_SESSION['user_id'] . time());

        // QR Code com link de validação
        $qr_data = APP_URL . '/validar.php?h=' . substr($hash,0,16);
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($qr_data);

        db()->prepare("INSERT INTO receitas (empresa_id, paciente_id, profissional_id, prontuario_id, tipo, conteudo, data_emissao, cid, hash_documento, qr_code, assinatura_digital) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([active_company_id(), $paciente_id, $_SESSION['user_id'], $prontuario_id, $tipo, $conteudo,
                       input('data_emissao', date('Y-m-d')), input('cid'),
                       $hash, $qr_url, input('assinatura_base64')]);
        $id = db()->lastInsertId();
        flash('success','Receita emitida com assinatura digital!');
        redirect('receita_pdf.php?id='.$id);
    } catch (Exception $e) {
        flash('error','Erro: '.$e->getMessage());
    }
}

$p = db()->prepare("SELECT * FROM pacientes WHERE id=? AND empresa_id=?");
$p->execute([$paciente_id, active_company_id()]); $p = $p->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold"><i class="bi bi-prescription2"></i> Nova Receita / Prescrição</h1><p class="text-sm text-slate-500">Paciente: <?= e($p['nome'] ?? '') ?></p></div>

<form method="POST" class="card p-6 space-y-4" id="formReceita" onsubmit="document.getElementById('assinaturaB64').value = sigCanvas.toDataURL();">
  <?= csrf_field() ?>
  <input type="hidden" name="assinatura_base64" id="assinaturaB64">

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label class="text-sm">Tipo</label>
      <select name="tipo" class="form-select">
        <option value="simples">Simples</option>
        <option value="controlada">Controlada</option>
      </select>
    </div>
    <div><label class="text-sm">Data</label><input type="date" name="data_emissao" value="<?= date('Y-m-d') ?>" class="form-input"></div>
    <div><label class="text-sm">CID (opcional)</label><input name="cid" class="form-input"></div>
  </div>

  <div>
    <label class="text-sm">Busca rápida de medicamentos</label>
    <div class="relative">
      <input id="medBusca" placeholder="Digite ao menos 2 letras..." class="form-input" autocomplete="off">
      <div id="medLista" class="absolute z-10 bg-white border rounded-lg shadow-lg w-full mt-1 max-h-60 overflow-auto hidden"></div>
    </div>
  </div>

  <div>
    <label class="text-sm">Prescrição *</label>
    <textarea name="conteudo" id="conteudo" rows="10" required class="form-textarea font-mono text-sm" placeholder="1. Dipirona 500mg — 1 comprimido de 6/6h se dor&#10;2. Amoxicilina 500mg — 1 cápsula de 8/8h por 7 dias"></textarea>
  </div>

  <div class="border-t pt-4">
    <h3 class="font-bold mb-2"><i class="bi bi-pen"></i> Assinatura Digital</h3>
    <p class="text-xs text-slate-500 mb-2">Assine no campo abaixo (touch ou mouse). A receita receberá hash criptográfico e QR Code de validação.</p>
    <canvas id="sigCanvas" width="500" height="120" class="border-2 border-dashed border-slate-300 rounded w-full touch-none bg-white"></canvas>
    <button type="button" onclick="limparAss()" class="text-xs text-red-600 mt-1">Limpar assinatura</button>
  </div>

  <div class="flex justify-end gap-2"><a href="javascript:history.back()" class="btn bg-slate-200">Cancelar</a><button class="btn btn-primary"><i class="bi bi-shield-check"></i> Emitir Assinada</button></div>
</form>

<script>
const sigCanvas = document.getElementById('sigCanvas');
const ctx = sigCanvas.getContext('2d');
ctx.lineWidth = 2; ctx.strokeStyle = '#1e40af'; ctx.lineCap = 'round';
let drawing = false;
function pos(e) {
  const r = sigCanvas.getBoundingClientRect();
  const t = e.touches ? e.touches[0] : e;
  return {x: t.clientX - r.left, y: t.clientY - r.top};
}
sigCanvas.addEventListener('mousedown', e => { drawing = true; const p=pos(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); });
sigCanvas.addEventListener('mousemove', e => { if (drawing) { const p=pos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); }});
sigCanvas.addEventListener('mouseup', () => drawing = false);
sigCanvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; const p=pos(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); });
sigCanvas.addEventListener('touchmove', e => { e.preventDefault(); if (drawing) { const p=pos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); }});
sigCanvas.addEventListener('touchend', () => drawing = false);
function limparAss() { ctx.clearRect(0,0,sigCanvas.width,sigCanvas.height); }

// Autocomplete medicamentos
const mb = document.getElementById('medBusca');
const ml = document.getElementById('medLista');
let timer;
mb.addEventListener('input', () => {
  clearTimeout(timer);
  const q = mb.value.trim();
  if (q.length < 2) { ml.classList.add('hidden'); return; }
  timer = setTimeout(async () => {
    const r = await fetch('<?= APP_URL ?>/api/medicamentos_search.php?q=' + encodeURIComponent(q));
    const meds = await r.json();
    ml.innerHTML = meds.map(m => `<div class="px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm border-b" onclick="addMed('${m.nome.replace(/'/g,"\\'")}','${(m.apresentacao||'').replace(/'/g,"\\'")}')"><strong>${m.nome}</strong> <span class="text-xs text-slate-500">${m.apresentacao||''}</span>${m.tipo!=='simples'?' <span class="badge badge-warning text-[10px]">'+m.tipo+'</span>':''}</div>`).join('') || '<div class="p-2 text-xs text-slate-400">Sem resultados</div>';
    ml.classList.remove('hidden');
  }, 200);
});
function addMed(nome, apres) {
  const ta = document.getElementById('conteudo');
  const linhas = ta.value.split('\n').filter(l => l.trim());
  const n = linhas.length + 1;
  ta.value += (ta.value && !ta.value.endsWith('\n') ? '\n' : '') + n + '. ' + nome + ' ' + apres + ' — Tomar __ de __/__h por __ dias';
  ta.focus();
  mb.value = '';
  ml.classList.add('hidden');
}
document.addEventListener('click', e => { if (!e.target.closest('#medBusca') && !e.target.closest('#medLista')) ml.classList.add('hidden'); });
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
