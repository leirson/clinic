<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Odontograma Avançado';

$paciente_id = (int)input('paciente_id');

if (!$paciente_id) {
    $ps = db()->prepare("SELECT id, nome, data_nascimento FROM pacientes WHERE empresa_id=? AND ativo=1 ORDER BY nome");
    $ps->execute([active_company_id()]);
    $ps = $ps->fetchAll();
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="mb-5"><h1 class="text-2xl font-bold"><i class="bi bi-emoji-smile text-primary"></i> Odontograma</h1><p class="text-sm text-slate-500">Selecione o paciente</p></div>';
    echo '<div class="card p-6"><div data-patient-list class="grid grid-cols-1 md:grid-cols-3 gap-2">';
    foreach($ps as $p) echo '<a data-patient-option href="?paciente_id='.$p['id'].'" class="p-3 border rounded-lg hover:border-primary hover:bg-blue-50">'.e($p['nome']).'</a>';
    echo '</div></div>';
    require __DIR__ . '/../../includes/footer.php';
    exit;
}

// Validar a empresa antes de consultar ou alterar qualquer dado clínico.
$pac = db()->prepare("SELECT * FROM pacientes WHERE id=? AND empresa_id=? LIMIT 1");
$pac->execute([$paciente_id, active_company_id()]);
$pac = $pac->fetch();
if (!$pac) {
    http_response_code(404);
    exit('Paciente não encontrado na empresa ativa.');
}

if (!db_column_exists('odontograma', 'tipo_denticao') || !db_column_exists('odontograma', 'tratamento_planejado') || !db_column_exists('odontograma', 'valor_estimado') || !db_table_exists('odontograma_historico')) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="card p-6">Odontograma requer as migrações do banco. Aplique sql/upgrade_v2.sql conforme a versão instalada.</div>';
    require __DIR__ . '/../../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    if (input('acao') === 'salvar') {
        try {
            $tipo_dent = (string) input('tipo_denticao', 'permanente');
            if (!in_array($tipo_dent, ['permanente', 'deciduo', 'misto'], true)) {
                throw new InvalidArgumentException('Dentição inválida.');
            }
            $dados = json_decode((string) input('dados', ''), true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados) || array_values($dados) !== $dados || count($dados) > 52) {
                throw new InvalidArgumentException('Dados inválidos. Nenhum registro foi apagado.');
            }
            $condicoes = ['carie','restauracao','tratamento','coroa','endodontia','implante','ausente','fraturado','extracao_indicada'];
            $faces = ['geral','vestibular','lingual','mesial','distal','oclusal','incisal'];
            $vistos = [];
            foreach ($dados as &$d) {
                if (!is_array($d)) throw new InvalidArgumentException('Registro inválido.');
                $tooth = (string) ($d['dente'] ?? '');
                $validTooth = $tipo_dent === 'permanente' ? preg_match('/^[1-4][1-8]$/', $tooth) : ($tipo_dent === 'deciduo' ? preg_match('/^[5-8][1-5]$/', $tooth) : preg_match('/^(?:[1-4][1-8]|[5-8][1-5])$/', $tooth));
                if (!$validTooth || isset($vistos[$tooth]) || !in_array($d['condicao'] ?? '', $condicoes, true) || !in_array($d['face'] ?? 'geral', $faces, true)) {
                    throw new InvalidArgumentException('Dente, face ou condição inválida ou duplicada.');
                }
                $vistos[$tooth] = true;
                $value = $d['valor'] ?? null;
                if ($value !== null && (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0 || (float) $value > 99999999.99)) {
                    throw new InvalidArgumentException('Valor estimado inválido.');
                }
                if (!is_scalar($d['obs'] ?? '') || strlen((string) ($d['obs'] ?? '')) > 10000) throw new InvalidArgumentException('Observação inválida.');
            }
            unset($d);
            db()->beginTransaction();
            // Serializar salvamentos concorrentes do mesmo paciente.
            $lock = db()->prepare('SELECT id FROM pacientes WHERE id=? AND empresa_id=? FOR UPDATE');
            $lock->execute([$paciente_id, active_company_id()]);
            if (!$lock->fetch()) throw new RuntimeException('Paciente indisponível.');
            $atual = db()->prepare('SELECT dente, condicao FROM odontograma WHERE paciente_id=? AND tipo_denticao=?');
            $atual->execute([$paciente_id, $tipo_dent]);
            $map_atual = [];
            foreach ($atual->fetchAll() as $row) $map_atual[$row['dente']] = $row['condicao'];
            db()->prepare('DELETE FROM odontograma WHERE paciente_id=? AND tipo_denticao=?')->execute([$paciente_id, $tipo_dent]);
            $ins = db()->prepare('INSERT INTO odontograma (empresa_id,paciente_id,dente,face,tipo_denticao,condicao,observacao,tratamento_planejado,valor_estimado,data_registro,profissional_id) VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),?)');
            $histIns = db()->prepare('INSERT INTO odontograma_historico (empresa_id,paciente_id,dente,condicao_anterior,condicao_nova,observacao,profissional_id) VALUES (?,?,?,?,?,?,?)');
            foreach ($dados as $d) {
                $ins->execute([active_company_id(),$paciente_id,$d['dente'],$d['face'] ?? 'geral',$tipo_dent,$d['condicao'],$d['obs'] ?? null,!empty($d['planejado']) ? 1 : 0,$d['valor'] ?? null,$_SESSION['user_id']]);
                $old = $map_atual[$d['dente']] ?? null;
                if ($old !== $d['condicao']) $histIns->execute([active_company_id(),$paciente_id,$d['dente'],$old,$d['condicao'],$d['obs'] ?? null,$_SESSION['user_id']]);
                unset($map_atual[$d['dente']]);
            }
            foreach ($map_atual as $tooth => $old) {
                $histIns->execute([active_company_id(),$paciente_id,$tooth,$old,'higido','Removida marcação na dentição '.$tipo_dent,$_SESSION['user_id']]);
            }
            db()->commit();
            flash('success', 'Odontograma salvo!');
        } catch (Throwable $error) {
            if (db()->inTransaction()) db()->rollBack();
            error_log('Odontograma: '.$error->getMessage());
            flash('error', $error instanceof InvalidArgumentException ? $error->getMessage() : 'Não foi possível salvar. Dados anteriores preservados.');
        }
        redirect('odontograma.php?paciente_id='.$paciente_id.'&tipo_denticao='.urlencode(in_array($tipo_dent ?? '', ['permanente','deciduo','misto'], true) ? $tipo_dent : 'permanente'));
    }
}

// Calcular idade para sugerir dentição
$idade = $pac['data_nascimento'] ? floor((time() - strtotime($pac['data_nascimento'])) / (86400 * 365.25)) : 30;
$sugestao = $idade < 6 ? 'deciduo' : ($idade < 12 ? 'misto' : 'permanente');
$tipo_denticao = (string) input('tipo_denticao', $sugestao);
if (!in_array($tipo_denticao, ['permanente','deciduo','misto'], true)) $tipo_denticao = $sugestao;

$reg = db()->prepare("SELECT * FROM odontograma WHERE paciente_id=? AND tipo_denticao=?");
$reg->execute([$paciente_id, $tipo_denticao]);
$reg_map = [];
foreach ($reg->fetchAll() as $r) $reg_map[$r['dente']] = $r;

// Histórico (timeline)
$hist = db()->prepare("SELECT h.*, u.nome AS prof FROM odontograma_historico h LEFT JOIN usuarios u ON u.id=h.profissional_id WHERE h.paciente_id=? ORDER BY h.data_registro DESC LIMIT 50");
$hist->execute([$paciente_id]);
$hist = $hist->fetchAll();

// Definir dentes por tipo
$dentes_config = [
    'permanente' => [
        'superiores' => [18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28],
        'inferiores' => [48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38],
    ],
    'deciduo' => [
        'superiores' => [55,54,53,52,51,61,62,63,64,65],
        'inferiores' => [85,84,83,82,81,71,72,73,74,75],
    ],
    'misto' => [
        'superiores' => [17,16,55,54,53,52,51,61,62,63,64,65,26,27],
        'inferiores' => [47,46,85,84,83,82,81,71,72,73,74,75,36,37],
    ],
];
$ds = $dentes_config[$tipo_denticao]['superiores'];
$di = $dentes_config[$tipo_denticao]['inferiores'];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5 flex justify-between flex-wrap gap-2">
  <div>
    <a href="../pacientes/ver.php?id=<?= $paciente_id ?>" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a>
    <h1 class="text-2xl font-bold"><i class="bi bi-emoji-smile"></i> Odontograma Avançado</h1>
    <p class="text-sm text-slate-500">Paciente: <strong><?= e($pac['nome']) ?></strong> · <?= $idade ?> anos · Sugestão: <em><?= $sugestao ?></em></p>
  </div>
  <a href="?paciente_id=<?= $paciente_id ?>&aba=historico" class="btn bg-slate-100 text-slate-700"><i class="bi bi-clock-history"></i> Timeline</a>
</div>

<div class="card p-3 mb-4 flex gap-2 flex-wrap">
  <?php foreach(['permanente'=>'🦷 Permanente','deciduo'=>'👶 Decídua','misto'=>'🔄 Mista'] as $k=>$l): ?>
  <a href="?paciente_id=<?= $paciente_id ?>&tipo_denticao=<?= $k ?>" class="px-4 py-2 rounded-lg <?= $tipo_denticao===$k?'bg-primary text-white':'bg-slate-100 hover:bg-slate-200' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<?php if(input('aba')==='historico'): ?>
<div class="card p-5">
  <h3 class="font-bold mb-4"><i class="bi bi-clock-history"></i> Timeline de Evolução</h3>
  <?php if(!$hist): ?>
    <p class="text-slate-400 text-center py-10">Sem histórico registrado.</p>
  <?php else: ?>
    <div class="relative pl-8 border-l-2 border-slate-200 space-y-4">
      <?php foreach($hist as $h): ?>
      <div class="relative">
        <div class="absolute -left-10 top-1 w-4 h-4 rounded-full bg-primary border-4 border-white"></div>
        <div class="card p-3 bg-slate-50">
          <div class="flex justify-between text-sm">
            <strong>Dente <?= e($h['dente']) ?></strong>
            <span class="text-xs text-slate-500"><?= format_date($h['data_registro'], true) ?></span>
          </div>
          <div class="text-xs text-slate-600 mt-1">
            <?= $h['condicao_anterior'] ? '<s>'.e($h['condicao_anterior']).'</s> → ' : '' ?>
            <strong class="text-primary"><?= e($h['condicao_nova']) ?></strong>
          </div>
          <?php if($h['observacao']): ?><div class="text-xs text-slate-500 mt-1"><?= e($h['observacao']) ?></div><?php endif; ?>
          <?php if($h['prof']): ?><div class="text-xs text-slate-400 mt-1">por Dr(a). <?= e($h['prof']) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php else: ?>

<div class="card p-6">
  <p class="text-sm text-slate-600 mb-4">Clique em cada dente para registrar a condição. Use as faces para detalhar.</p>

  <div class="text-center mb-4">
    <div class="text-xs text-slate-500 mb-2">SUPERIOR (Maxila)</div>
    <div class="flex justify-center gap-1 flex-wrap mb-2" id="row-sup"></div>
    <div class="my-4 border-b border-dashed border-slate-300"></div>
    <div class="flex justify-center gap-1 flex-wrap" id="row-inf"></div>
    <div class="text-xs text-slate-500 mt-2">INFERIOR (Mandíbula)</div>
  </div>

  <div class="flex flex-wrap gap-3 justify-center text-xs mt-4">
    <span class="flex items-center gap-1"><span class="w-4 h-4 bg-white border-2 border-slate-400 rounded"></span> Hígido</span>
    <span class="flex items-center gap-1"><span class="w-4 h-4 bg-red-200 border-2 border-red-500 rounded"></span> Cárie</span>
    <span class="flex items-center gap-1"><span class="w-4 h-4 bg-blue-200 border-2 border-blue-500 rounded"></span> Restauração</span>
    <span class="flex items-center gap-1"><span class="w-4 h-4 bg-yellow-200 border-2 border-yellow-500 rounded"></span> Em tratamento</span>
    <span class="flex items-center gap-1"><span class="w-4 h-4 bg-purple-200 border-2 border-purple-500 rounded"></span> Coroa/Prótese</span>
    <span class="flex items-center gap-1"><span class="w-4 h-4 bg-pink-200 border-2 border-pink-500 rounded"></span> Endodontia</span>
    <span class="flex items-center gap-1"><span class="w-4 h-4 bg-orange-200 border-2 border-orange-500 rounded"></span> Implante</span>
    <span class="flex items-center gap-1"><span class="w-4 h-4 bg-gray-700 rounded opacity-50"></span> Ausente</span>
    <span class="flex items-center gap-1"><span class="w-4 h-4 bg-green-200 border-2 border-dashed border-green-600 rounded"></span> Planejado</span>
  </div>

  <form method="POST" class="mt-6 text-center">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="salvar">
    <input type="hidden" name="tipo_denticao" value="<?= e($tipo_denticao) ?>">
    <input type="hidden" name="dados" id="dadosJson">
    <button onclick="salvar()" class="btn btn-primary"><i class="bi bi-check"></i> Salvar Odontograma</button>
    <p class="text-xs text-slate-500 mt-2">💡 Marque "Tratamento Planejado" para incluir no plano de tratamento</p>
  </form>
</div>

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
    <h3 class="font-bold text-lg mb-3">Dente <span id="denteNum"></span></h3>
    <div class="space-y-3">
      <div>
        <label class="text-sm font-medium">Condição:</label>
        <select id="condicao" class="form-select">
          <option value="">Hígido</option>
          <option value="carie">Cárie</option>
          <option value="restauracao">Restauração</option>
          <option value="tratamento">Em tratamento</option>
          <option value="coroa">Coroa/Prótese</option>
          <option value="endodontia">Endodontia</option>
          <option value="implante">Implante</option>
          <option value="ausente">Ausente</option>
          <option value="fraturado">Fraturado</option>
          <option value="extracao_indicada">Extração indicada</option>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Face:</label>
        <select id="face" class="form-select">
          <option value="geral">Geral (todo dente)</option>
          <option value="vestibular">Vestibular</option>
          <option value="lingual">Lingual/Palatina</option>
          <option value="mesial">Mesial</option>
          <option value="distal">Distal</option>
          <option value="oclusal">Oclusal</option>
          <option value="incisal">Incisal</option>
        </select>
      </div>
      <div>
        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" id="planejado"> Tratamento planejado (incluir em plano)
        </label>
      </div>
      <div>
        <label class="text-sm font-medium">Valor estimado:</label>
        <input type="text" id="valor" placeholder="0,00" class="form-input">
      </div>
      <div>
        <label class="text-sm font-medium">Observação:</label>
        <textarea id="obs" rows="3" class="form-textarea"></textarea>
      </div>
    </div>
    <div class="flex justify-end gap-2 mt-4">
      <button onclick="fecharModal()" class="btn bg-slate-200">Cancelar</button>
      <button onclick="aplicar()" class="btn btn-primary">Aplicar</button>
    </div>
  </div>
</div>

<script>
const dadosDentes = <?= json_encode((function() use ($reg_map){
    $r=[];
    foreach($reg_map as $k=>$v) $r[$k]=[
        'dente'=>$k,'condicao'=>$v['condicao'],'face'=>$v['face'],
        'obs'=>$v['observacao'],'planejado'=>$v['tratamento_planejado'],
        'valor'=>$v['valor_estimado']
    ];
    return (object) $r;
})(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const ds = <?= json_encode($ds) ?>;
const di = <?= json_encode($di) ?>;
let denteAtual = null;

function renderRow(arr, containerId, top = true) {
  const c = document.getElementById(containerId);
  c.innerHTML = arr.map(d => {
    const cond = dadosDentes[d]?.condicao || '';
    const plan = Number(dadosDentes[d]?.planejado) === 1 ? 'planejado' : '';
    return `<div class="text-center">
      ${top ? `<div class="text-[10px] text-slate-400">${d}</div>` : ''}
      <div class="tooth ${cond} ${plan}" onclick="abrirDente(${d})" data-dente="${d}">${d}</div>
      ${!top ? `<div class="text-[10px] text-slate-400">${d}</div>` : ''}
    </div>`;
  }).join('');
}
renderRow(ds, 'row-sup', true);
renderRow(di, 'row-inf', false);

function abrirDente(d) {
  denteAtual = d;
  document.getElementById('denteNum').textContent = d;
  const da = dadosDentes[d] || {};
  document.getElementById('condicao').value = da.condicao || '';
  document.getElementById('face').value = da.face || 'geral';
  document.getElementById('planejado').checked = Number(da.planejado) === 1;
  document.getElementById('valor').value = da.valor ? parseFloat(da.valor).toFixed(2).replace('.',',') : '';
  document.getElementById('obs').value = da.obs || '';
  document.getElementById('modal').classList.remove('hidden');
  document.getElementById('modal').classList.add('flex');
}
function fecharModal() {
  document.getElementById('modal').classList.add('hidden');
  document.getElementById('modal').classList.remove('flex');
}
function aplicar() {
  const cond = document.getElementById('condicao').value;
  if (!cond) {
    delete dadosDentes[denteAtual];
  } else {
    dadosDentes[denteAtual] = {
      dente: denteAtual,
      condicao: cond,
      face: document.getElementById('face').value,
      planejado: document.getElementById('planejado').checked ? 1 : 0,
      valor: parseFloat(document.getElementById('valor').value.replace(',','.')) || null,
      obs: document.getElementById('obs').value
    };
  }
  renderRow(ds, 'row-sup', true);
  renderRow(di, 'row-inf', false);
  fecharModal();
}
function salvar() {
  document.getElementById('dadosJson').value = JSON.stringify(Object.values(dadosDentes));
}
</script>

<style>
.tooth.coroa { background:#e9d5ff; border-color:#9333ea; }
.tooth.endodontia { background:#fbcfe8; border-color:#db2777; }
.tooth.implante { background:#fed7aa; border-color:#ea580c; }
.tooth.fraturado { background: linear-gradient(45deg, #fee2e2 50%, #fecaca 50%); border-color:#991b1b; }
.tooth.extracao_indicada { background:#fef3c7; border:2px dashed #d97706; }
.tooth.planejado { box-shadow: 0 0 0 3px #10b981; }
</style>

<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
