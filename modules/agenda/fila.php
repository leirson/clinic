<?php
$page_title = 'Fila de Encaixe';
require_once __DIR__ . '/../../includes/header.php';

if (!db_table_exists('agenda_fila_espera')) {
    echo '<div class="card p-6"><h1 class="text-2xl font-bold mb-3"><i class="bi bi-hourglass-split text-primary"></i> Fila de Encaixe</h1><p class="text-sm text-slate-600">Execute o upgrade mais recente do sistema para habilitar a fila de encaixe da agenda.</p></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    return;
}

$eid = active_company_id();
$editingId = (int) input('editar');
$editing = null;
$slotContext = [
    'origem_agendamento_id' => (int) input('origem_agendamento_id'),
    'profissional_id' => (int) input('profissional_id'),
    'procedimento_id' => (int) input('procedimento_id'),
    'slot_start' => trim((string) input('slot_start')),
    'slot_end' => trim((string) input('slot_end')),
    'valor' => (float) input('valor', 0),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $acao = input('acao');

    try {
        if ($acao === 'salvar') {
            $id = (int) input('id');
            $pacienteId = (int) input('paciente_id');
            if ($pacienteId <= 0) {
                throw new Exception('Selecione o paciente.');
            }
            $dados = [
                'paciente_id' => $pacienteId,
                'profissional_id' => (int) input('profissional_id') ?: null,
                'procedimento_id' => (int) input('procedimento_id') ?: null,
                'prioridade' => input('prioridade', 'normal'),
                'data_preferencial' => input('data_preferencial') ?: null,
                'hora_inicio_preferencial' => input('hora_inicio_preferencial') ? input('hora_inicio_preferencial') . ':00' : null,
                'hora_fim_preferencial' => input('hora_fim_preferencial') ? input('hora_fim_preferencial') . ':00' : null,
                'observacoes' => trim((string) input('observacoes')),
                'status' => input('status', 'ativo'),
            ];

            if ($id > 0) {
                db()->prepare("UPDATE agenda_fila_espera SET paciente_id=?, profissional_id=?, procedimento_id=?, prioridade=?, data_preferencial=?, hora_inicio_preferencial=?, hora_fim_preferencial=?, observacoes=?, status=?, atualizado_em=NOW() WHERE id=? AND empresa_id=?")
                    ->execute([
                        $dados['paciente_id'], $dados['profissional_id'], $dados['procedimento_id'], $dados['prioridade'],
                        $dados['data_preferencial'], $dados['hora_inicio_preferencial'], $dados['hora_fim_preferencial'],
                        $dados['observacoes'], $dados['status'], $id, $eid,
                    ]);
                log_action('agenda_fila_editar', 'agenda_fila_espera', $id, 'Fila de encaixe atualizada.');
                flash('success', 'Registro da fila atualizado com sucesso.');
            } else {
                db()->prepare("INSERT INTO agenda_fila_espera (empresa_id, paciente_id, profissional_id, procedimento_id, prioridade, data_preferencial, hora_inicio_preferencial, hora_fim_preferencial, observacoes, status, criado_por, atualizado_em) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())")
                    ->execute([
                        $eid, $dados['paciente_id'], $dados['profissional_id'], $dados['procedimento_id'], $dados['prioridade'],
                        $dados['data_preferencial'], $dados['hora_inicio_preferencial'], $dados['hora_fim_preferencial'],
                        $dados['observacoes'], $dados['status'], $_SESSION['user_id'],
                    ]);
                $novoId = (int) db()->lastInsertId();
                log_action('agenda_fila_criar', 'agenda_fila_espera', $novoId, 'Paciente incluído na fila de encaixe.');
                flash('success', 'Paciente adicionado à fila de encaixe.');
            }
            redirect('fila.php');
        }

        if ($acao === 'contatado') {
            $id = (int) input('id');
            db()->prepare("UPDATE agenda_fila_espera SET status='contatado', contatado_em=NOW(), atualizado_em=NOW() WHERE id=? AND empresa_id=?")
                ->execute([$id, $eid]);
            log_action('agenda_fila_contatado', 'agenda_fila_espera', $id, 'Paciente da fila marcado como contatado.');
            flash('success', 'Paciente marcado como contatado.');
            redirect('fila.php');
        }

        if ($acao === 'arquivar') {
            $id = (int) input('id');
            db()->prepare("UPDATE agenda_fila_espera SET status='cancelado', atualizado_em=NOW() WHERE id=? AND empresa_id=?")
                ->execute([$id, $eid]);
            log_action('agenda_fila_cancelar', 'agenda_fila_espera', $id, 'Registro removido da fila de encaixe.');
            flash('success', 'Registro removido da fila.');
            redirect('fila.php');
        }

        if ($acao === 'encaixar') {
            $filaId = (int) input('id');
            $slot = [
                'profissional_id' => (int) input('slot_profissional_id'),
                'procedimento_id' => (int) input('slot_procedimento_id') ?: null,
                'data_hora_inicio' => input('slot_start'),
                'data_hora_fim' => input('slot_end'),
                'valor' => (float) input('slot_valor', 0),
                'observacoes' => 'Encaixe via fila de espera',
            ];
            $agendamentoId = agenda_waitlist_promote($filaId, $slot, $_SESSION['user_id']);
            if ((int) input('origem_agendamento_id') > 0) {
                db()->prepare("UPDATE agendamentos SET observacoes = CONCAT(COALESCE(observacoes,''), CASE WHEN COALESCE(observacoes,'')='' THEN '' ELSE ' | ' END, ?) WHERE id=? AND empresa_id=?")
                    ->execute(['Horário reaproveitado pela fila de encaixe no agendamento #' . $agendamentoId, (int) input('origem_agendamento_id'), $eid]);
            }
            flash('success', 'Encaixe criado com sucesso.');
            redirect('ver.php?id=' . $agendamentoId);
        }
    } catch (Throwable $e) {
        flash('error', 'Erro: ' . $e->getMessage());
        redirect('fila.php');
    }
}

if ($editingId > 0) {
    $stmt = db()->prepare("SELECT * FROM agenda_fila_espera WHERE id=? AND empresa_id=? LIMIT 1");
    $stmt->execute([$editingId, $eid]);
    $editing = $stmt->fetch() ?: null;
}

$statusFiltro = trim((string) input('status', ''));
$sql = "SELECT f.*, p.nome AS paciente_nome, p.celular, u.nome AS profissional_nome, pr.nome AS procedimento_nome,
        ag.data_hora_inicio AS encaixe_data
        FROM agenda_fila_espera f
        JOIN pacientes p ON p.id = f.paciente_id
        LEFT JOIN usuarios u ON u.id = f.profissional_id
        LEFT JOIN procedimentos pr ON pr.id = f.procedimento_id
        LEFT JOIN agendamentos ag ON ag.id = f.agendamento_id
        WHERE f.empresa_id = ?";
$params = [$eid];
if ($statusFiltro !== '') {
    $sql .= " AND f.status = ?";
    $params[] = $statusFiltro;
}
$sql .= " ORDER BY FIELD(f.status,'ativo','contatado','agendado','cancelado'), FIELD(f.prioridade,'urgente','alta','normal'), f.criado_em ASC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$filas = $stmt->fetchAll() ?: [];

$stats = [
    'ativos' => 0,
    'urgentes' => 0,
    'agendados' => 0,
];
foreach ($filas as $fila) {
    if (in_array($fila['status'], ['ativo','contatado'], true)) {
        $stats['ativos']++;
    }
    if (($fila['prioridade'] ?? '') === 'urgente' && in_array($fila['status'], ['ativo','contatado'], true)) {
        $stats['urgentes']++;
    }
    if (($fila['status'] ?? '') === 'agendado') {
        $stats['agendados']++;
    }
}

$pacientes = db()->prepare("SELECT id, nome FROM pacientes WHERE empresa_id=? AND ativo=1 ORDER BY nome");
$pacientes->execute([$eid]);
$pacientes = $pacientes->fetchAll() ?: [];

$profs = db()->prepare("SELECT id, nome FROM usuarios WHERE empresa_id=? AND ativo=1 AND perfil IN ('admin','profissional') ORDER BY nome");
$profs->execute([$eid]);
$profs = $profs->fetchAll() ?: [];

$procs = db()->prepare("SELECT id, nome FROM procedimentos WHERE empresa_id=? AND ativo=1 ORDER BY nome");
$procs->execute([$eid]);
$procs = $procs->fetchAll() ?: [];
?>
<div class="space-y-5">
  <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
    <div>
      <a href="index.php" class="text-sm text-slate-500 hover:text-primary"><i class="bi bi-arrow-left"></i> Voltar para agenda</a>
      <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-hourglass-split text-primary"></i> Fila de Encaixe</h1>
      <p class="text-sm text-slate-500">Gerencie pacientes disponíveis para ocupar cancelamentos e horários vagos.</p>
    </div>
    <div class="grid grid-cols-3 gap-3 min-w-[320px]">
      <div class="card p-4"><div class="text-xs uppercase text-slate-500">Ativos</div><div class="text-2xl font-bold text-sky-600"><?= $stats['ativos'] ?></div></div>
      <div class="card p-4"><div class="text-xs uppercase text-slate-500">Urgentes</div><div class="text-2xl font-bold text-rose-600"><?= $stats['urgentes'] ?></div></div>
      <div class="card p-4"><div class="text-xs uppercase text-slate-500">Convertidos</div><div class="text-2xl font-bold text-emerald-600"><?= $stats['agendados'] ?></div></div>
    </div>
  </div>

  <?php if ($slotContext['slot_start'] !== '' && $slotContext['slot_end'] !== ''): ?>
    <div class="card p-4 border-sky-200 bg-sky-50">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <div class="font-semibold text-sky-900"><i class="bi bi-lightning-charge"></i> Horário disponível para reaproveitamento</div>
          <div class="text-sm text-sky-800 mt-1">Slot: <strong><?= format_date($slotContext['slot_start'], true) ?></strong> até <?= date('H:i', strtotime($slotContext['slot_end'])) ?>.</div>
        </div>
        <div class="text-xs text-sky-700">Selecione um registro da fila abaixo e use <strong>Encaixar</strong>.</div>
      </div>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-plus-circle"></i> <?= $editing ? 'Editar fila' : 'Novo registro na fila' ?></h2>
      <form method="POST" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
        <div>
          <label class="text-xs text-slate-500">Paciente</label>
          <select data-patient-search name="paciente_id" required class="form-select">
            <option value="">Selecione...</option>
            <?php foreach ($pacientes as $paciente): ?>
              <option value="<?= (int) $paciente['id'] ?>" <?= (int) ($editing['paciente_id'] ?? input('paciente_id')) === (int) $paciente['id'] ? 'selected' : '' ?>><?= e($paciente['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-xs text-slate-500">Profissional preferencial</label>
          <select name="profissional_id" class="form-select">
            <option value="">Qualquer disponível</option>
            <?php foreach ($profs as $prof): ?>
              <option value="<?= (int) $prof['id'] ?>" <?= (int) ($editing['profissional_id'] ?? input('profissional_id')) === (int) $prof['id'] ? 'selected' : '' ?>><?= e($prof['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-xs text-slate-500">Procedimento preferencial</label>
          <select name="procedimento_id" class="form-select">
            <option value="">Não informado</option>
            <?php foreach ($procs as $proc): ?>
              <option value="<?= (int) $proc['id'] ?>" <?= (int) ($editing['procedimento_id'] ?? input('procedimento_id')) === (int) $proc['id'] ? 'selected' : '' ?>><?= e($proc['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-slate-500">Data preferencial</label>
            <input type="date" name="data_preferencial" class="form-input" value="<?= e($editing['data_preferencial'] ?? '') ?>">
          </div>
          <div>
            <label class="text-xs text-slate-500">Prioridade</label>
            <select name="prioridade" class="form-select">
              <?php foreach (['normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= ($editing['prioridade'] ?? 'normal') === $value ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-slate-500">Janela inicial</label>
            <input type="time" name="hora_inicio_preferencial" class="form-input" value="<?= e(!empty($editing['hora_inicio_preferencial']) ? substr((string) $editing['hora_inicio_preferencial'], 0, 5) : '') ?>">
          </div>
          <div>
            <label class="text-xs text-slate-500">Janela final</label>
            <input type="time" name="hora_fim_preferencial" class="form-input" value="<?= e(!empty($editing['hora_fim_preferencial']) ? substr((string) $editing['hora_fim_preferencial'], 0, 5) : '') ?>">
          </div>
        </div>
        <div>
          <label class="text-xs text-slate-500">Observações</label>
          <textarea name="observacoes" rows="3" class="form-textarea" placeholder="Disponibilidade, convênio, observações de contato..."><?= e($editing['observacoes'] ?? '') ?></textarea>
        </div>
        <?php if ($editing): ?>
          <div>
            <label class="text-xs text-slate-500">Status</label>
            <select name="status" class="form-select">
              <?php foreach (['ativo', 'contatado', 'agendado', 'cancelado'] as $status): ?>
                <option value="<?= $status ?>" <?= ($editing['status'] ?? 'ativo') === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
        <div class="flex justify-end gap-2 pt-3 border-t">
          <?php if ($editing): ?><a href="fila.php" class="btn bg-slate-100 text-slate-700">Cancelar</a><?php endif; ?>
          <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
      </form>
    </div>

    <div class="xl:col-span-2 space-y-4">
      <form method="GET" class="card p-4 flex flex-wrap gap-2 items-end">
        <div>
          <label class="text-xs text-slate-500">Status</label>
          <select name="status" class="form-select">
            <option value="">Todos</option>
            <?php foreach (['ativo', 'contatado', 'agendado', 'cancelado'] as $status): ?>
              <option value="<?= $status ?>" <?= $statusFiltro === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
        <?php if ($statusFiltro !== ''): ?><a href="fila.php" class="btn bg-slate-100 text-slate-700">Limpar</a><?php endif; ?>
      </form>

      <?php if (!$filas): ?>
        <div class="card p-8 text-center text-slate-400">Nenhum paciente cadastrado na fila de encaixe.</div>
      <?php else: ?>
        <?php foreach ($filas as $fila): ?>
          <div class="card p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
              <div class="space-y-2 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="badge <?= status_badge_class($fila['status'] === 'agendado' ? 'agendado_fila' : $fila['status']) ?>"><?= e(ucfirst($fila['status'])) ?></span>
                  <span class="badge <?= status_badge_class($fila['prioridade'] === 'urgente' ? 'cancelado' : ($fila['prioridade'] === 'alta' ? 'warning' : 'secondary')) ?>"><?= e(ucfirst($fila['prioridade'])) ?></span>
                  <?php if (!empty($fila['procedimento_nome'])): ?><span class="badge badge-info"><?= e($fila['procedimento_nome']) ?></span><?php endif; ?>
                </div>
                <div>
                  <h3 class="text-lg font-bold text-slate-800"><?= e($fila['paciente_nome']) ?></h3>
                  <p class="text-sm text-slate-500">
                    <?= !empty($fila['profissional_nome']) ? 'Profissional: ' . e($fila['profissional_nome']) . ' · ' : 'Qualquer profissional · ' ?>
                    <?= !empty($fila['data_preferencial']) ? 'Data: ' . format_date($fila['data_preferencial']) . ' · ' : '' ?>
                    <?= !empty($fila['hora_inicio_preferencial']) ? substr((string) $fila['hora_inicio_preferencial'], 0, 5) : 'horário livre' ?>
                    <?= !empty($fila['hora_fim_preferencial']) ? ' às ' . substr((string) $fila['hora_fim_preferencial'], 0, 5) : '' ?>
                  </p>
                </div>
                <?php if (!empty($fila['observacoes'])): ?><div class="text-sm text-slate-600 whitespace-pre-wrap"><?= e($fila['observacoes']) ?></div><?php endif; ?>
                <div class="text-xs text-slate-400">
                  Incluído em <?= format_date($fila['criado_em'], true) ?>
                  <?php if (!empty($fila['contatado_em'])): ?> · Contato em <?= format_date($fila['contatado_em'], true) ?><?php endif; ?>
                  <?php if (!empty($fila['encaixe_data'])): ?> · Encaixe em <?= format_date($fila['encaixe_data'], true) ?><?php endif; ?>
                </div>
              </div>
              <div class="flex gap-2 shrink-0 flex-wrap justify-end">
                <?php if ($slotContext['slot_start'] !== '' && in_array($fila['status'], ['ativo', 'contatado'], true)): ?>
                  <form method="POST" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="acao" value="encaixar">
                    <input type="hidden" name="id" value="<?= (int) $fila['id'] ?>">
                    <input type="hidden" name="slot_profissional_id" value="<?= (int) ($slotContext['profissional_id'] ?: $fila['profissional_id']) ?>">
                    <input type="hidden" name="slot_procedimento_id" value="<?= (int) ($slotContext['procedimento_id'] ?: $fila['procedimento_id']) ?>">
                    <input type="hidden" name="slot_start" value="<?= e($slotContext['slot_start']) ?>">
                    <input type="hidden" name="slot_end" value="<?= e($slotContext['slot_end']) ?>">
                    <input type="hidden" name="slot_valor" value="<?= e((string) $slotContext['valor']) ?>">
                    <input type="hidden" name="origem_agendamento_id" value="<?= (int) $slotContext['origem_agendamento_id'] ?>">
                    <button class="btn bg-emerald-600 text-white" data-confirm="Criar encaixe para este paciente?"><i class="bi bi-lightning-charge"></i> Encaixar</button>
                  </form>
                <?php endif; ?>
                <?php if (in_array($fila['status'], ['ativo'], true)): ?>
                  <form method="POST" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="acao" value="contatado">
                    <input type="hidden" name="id" value="<?= (int) $fila['id'] ?>">
                    <button class="btn bg-sky-600 text-white"><i class="bi bi-telephone"></i> Contatado</button>
                  </form>
                <?php endif; ?>
                <a href="fila.php?editar=<?= (int) $fila['id'] ?>" class="btn bg-amber-500 text-white"><i class="bi bi-pencil"></i></a>
                <?php if ($fila['status'] !== 'cancelado'): ?>
                  <form method="POST" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="acao" value="arquivar">
                    <input type="hidden" name="id" value="<?= (int) $fila['id'] ?>">
                    <button class="btn bg-rose-600 text-white" data-confirm="Remover da fila?"><i class="bi bi-x-circle"></i></button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
