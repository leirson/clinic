<?php
$page_title = 'Detalhes do Paciente';
require_once __DIR__ . '/../../includes/header.php';

$id = (int) input('id');
$eid = active_company_id();
$hasCuidados = db_table_exists('paciente_alertas') && db_table_exists('planos_cuidado');
$hasConsentimentos = db_table_exists('paciente_consentimentos');

$stmt = db()->prepare('SELECT * FROM pacientes WHERE id=? AND empresa_id=?');
$stmt->execute([$id, $eid]);
$p = $stmt->fetch();
if (!$p) {
    flash('error', 'Paciente não encontrado.');
    redirect('index.php');
}

audit_entity_access('acesso_paciente', 'pacientes', $id, 'Visualização da ficha do paciente.');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasConsentimentos) {
    csrf_validate();
    $acao = input('acao');
    try {
        if ($acao === 'salvar_consentimento') {
            $tipo = trim((string) input('tipo'));
            $finalidade = trim((string) input('finalidade'));
            if ($tipo === '' || $finalidade === '') {
                throw new Exception('Informe o tipo e a finalidade do consentimento.');
            }
            $status = input('status', 'ativo');
            db()->prepare("INSERT INTO paciente_consentimentos (empresa_id, paciente_id, tipo, finalidade, base_legal, canal, status, aceito_em, validade_ate, observacoes, criado_por)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([
                    $eid,
                    $id,
                    $tipo,
                    $finalidade,
                    trim((string) input('base_legal')) ?: null,
                    trim((string) input('canal')) ?: null,
                    $status,
                    input('aceito_em') ?: date('Y-m-d H:i:s'),
                    input('validade_ate') ?: null,
                    trim((string) input('observacoes')) ?: null,
                    $_SESSION['user_id'],
                ]);
            $consentId = (int) db()->lastInsertId();
            log_action('lgpd_registrar_consentimento', 'paciente_consentimentos', $consentId, 'Consentimento registrado para o paciente #' . $id . '.');
            flash('success', 'Consentimento registrado com sucesso.');
            redirect('ver.php?id=' . $id . '#consentimentos');
        }

        if ($acao === 'revogar_consentimento') {
            $consentId = (int) input('consentimento_id');
            db()->prepare("UPDATE paciente_consentimentos SET status='revogado', revogado_em=NOW() WHERE id=? AND empresa_id=? AND paciente_id=?")
                ->execute([$consentId, $eid, $id]);
            log_action('lgpd_revogar_consentimento', 'paciente_consentimentos', $consentId, 'Consentimento revogado para o paciente #' . $id . '.');
            flash('success', 'Consentimento revogado.');
            redirect('ver.php?id=' . $id . '#consentimentos');
        }
    } catch (Throwable $e) {
        flash('error', 'Erro: ' . $e->getMessage());
        redirect('ver.php?id=' . $id . '#consentimentos');
    }
}

$ags = db()->prepare("SELECT a.*, u.nome AS prof FROM agendamentos a JOIN usuarios u ON u.id=a.profissional_id WHERE a.paciente_id=? AND a.empresa_id=? ORDER BY data_hora_inicio DESC LIMIT 10");
$ags->execute([$id, $eid]);
$ags = $ags->fetchAll() ?: [];

$pronts = db()->prepare("SELECT p.*, u.nome AS prof FROM prontuarios p JOIN usuarios u ON u.id=p.profissional_id WHERE p.paciente_id=? ORDER BY data_atendimento DESC LIMIT 10");
$pronts->execute([$id]);
$pronts = $pronts->fetchAll() ?: [];

$nfses = db()->prepare('SELECT * FROM nfse WHERE paciente_id=? AND empresa_id=? ORDER BY data_emissao DESC LIMIT 10');
$nfses->execute([$id, $eid]);
$nfses = $nfses->fetchAll() ?: [];

$currentTheme = normalize_theme_choice(get_theme());
$alertasAtivos = [];
$planosCuidado = [];
$timeline = patient_longitudinal_timeline($id, 25);
$consentimentos = $hasConsentimentos ? active_patient_consents($id, false) : [];
$consentimentosAtivos = array_values(array_filter($consentimentos, static fn($item) => ($item['status'] ?? '') === 'ativo'));
$badgePrioridade = [
    'baixa' => 'badge-secondary',
    'moderada' => 'badge-info',
    'alta' => 'badge-warning',
    'critica' => 'badge-danger',
];
$badgeStatusPlano = [
    'ativo' => 'badge-success',
    'em_acompanhamento' => 'badge-info',
    'pausado' => 'badge-warning',
    'concluido' => 'badge-secondary',
    'cancelado' => 'badge-danger',
];

if ($hasCuidados) {
    $stmt = db()->prepare("SELECT * FROM paciente_alertas WHERE empresa_id=? AND paciente_id=? AND ativo=1 ORDER BY FIELD(prioridade,'critica','alta','moderada','baixa'), atualizado_em DESC LIMIT 5");
    $stmt->execute([$eid, $id]);
    $alertasAtivos = $stmt->fetchAll() ?: [];

    $stmt = db()->prepare("SELECT pc.*, u.nome AS profissional_nome FROM planos_cuidado pc LEFT JOIN usuarios u ON u.id=pc.profissional_id WHERE pc.empresa_id=? AND pc.paciente_id=? ORDER BY FIELD(pc.status,'ativo','em_acompanhamento','pausado','concluido','cancelado'), pc.proxima_revisao IS NULL, pc.proxima_revisao ASC, pc.atualizado_em DESC LIMIT 5");
    $stmt->execute([$eid, $id]);
    $planosCuidado = $stmt->fetchAll() ?: [];
}
?>
<div class="space-y-5">
  <div class="mb-5 flex flex-wrap justify-between gap-2">
    <div>
      <a href="index.php" class="text-sm text-slate-500 hover:text-primary"><i class="bi bi-arrow-left"></i> Voltar</a>
      <h1 class="text-2xl font-bold text-slate-800"><?= e($p['nome']) ?></h1>
      <p class="text-sm text-slate-500"><?= format_cpf_cnpj($p['cpf_cnpj']) ?> · <?= e($p['email']) ?></p>
    </div>
    <div class="flex gap-2 flex-wrap">
      <a href="editar.php?id=<?= $id ?>" class="btn bg-amber-500 text-white"><i class="bi bi-pencil"></i> Editar</a>
      <a href="../agenda/novo.php?paciente_id=<?= $id ?>" class="btn btn-primary"><i class="bi bi-calendar-plus"></i> Agendar</a>
      <a href="../prontuario/novo.php?paciente_id=<?= $id ?>" class="btn bg-purple-600 text-white"><i class="bi bi-journal-plus"></i> Prontuário</a>
      <?php if ($currentTheme === 'psicologia'): ?>
        <a href="../psicologia/escalas.php?paciente_id=<?= $id ?>" class="btn bg-violet-600 text-white"><i class="bi bi-clipboard-data"></i> Escalas</a>
        <a href="../psicologia/planos.php?paciente_id=<?= $id ?>" class="btn bg-fuchsia-600 text-white"><i class="bi bi-bullseye"></i> Plano Terapêutico</a>
      <?php elseif (is_custom_specialty_theme($currentTheme)): ?>
        <?php foreach (specialty_theme_resources($currentTheme, $id) as $resource): ?>
          <a href="<?= e($resource['url']) ?>" class="btn bg-slate-800 text-white"><i class="bi <?= e($resource['icon']) ?>"></i> <?= e($resource['label']) ?></a>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if ($hasCuidados && !is_custom_specialty_theme($currentTheme)): ?>
        <a href="../cuidados/index.php?paciente_id=<?= $id ?>" class="btn bg-rose-600 text-white"><i class="bi bi-clipboard2-heart"></i> Cuidados</a>
      <?php endif; ?>
      <a href="../nfse/novo.php?paciente_id=<?= $id ?>" class="btn bg-green-600 text-white"><i class="bi bi-receipt"></i> NFSe</a>
      <?php if (($_SESSION['perfil'] ?? '') === 'admin' || is_developer()): ?>
        <a href="exportar.php?id=<?= $id ?>" class="btn bg-slate-800 text-white"><i class="bi bi-download"></i> Exportar LGPD</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">Agendamentos</p><p class="text-2xl font-bold text-sky-600"><?= count($ags) ?></p><p class="text-xs text-slate-400">Últimos compromissos</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">Registros clínicos</p><p class="text-2xl font-bold text-violet-600"><?= count($pronts) ?></p><p class="text-xs text-slate-400">Prontuários recentes</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">Consentimentos ativos</p><p class="text-2xl font-bold text-rose-600"><?= count($consentimentosAtivos) ?></p><p class="text-xs text-slate-400">LGPD e termos vigentes</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500 uppercase">Linha do tempo</p><p class="text-2xl font-bold text-emerald-600"><?= count($timeline) ?></p><p class="text-xs text-slate-400">Eventos clínicos e administrativos</p></div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="card p-5">
      <h3 class="font-bold mb-3"><i class="bi bi-person"></i> Dados Pessoais</h3>
      <dl class="text-sm space-y-2">
        <div><dt class="text-slate-500">Nascimento:</dt><dd><?= format_date($p['data_nascimento']) ?></dd></div>
        <div><dt class="text-slate-500">Sexo:</dt><dd><?= e($p['sexo']) ?></dd></div>
        <div><dt class="text-slate-500">Telefone:</dt><dd><?= format_phone($p['telefone']) ?></dd></div>
        <div><dt class="text-slate-500">Celular:</dt><dd><?php if ($p['celular']): ?><a class="text-green-600" target="_blank" href="<?= wa_link($p['celular']) ?>"><i class="bi bi-whatsapp"></i> <?= format_phone($p['celular']) ?></a><?php endif; ?></dd></div>
        <div><dt class="text-slate-500">Convênio:</dt><dd><?= e($p['convenio']) ?></dd></div>
        <div><dt class="text-slate-500">Profissão:</dt><dd><?= e($p['profissao']) ?></dd></div>
      </dl>
    </div>
    <div class="card p-5">
      <h3 class="font-bold mb-3"><i class="bi bi-geo-alt"></i> Endereço</h3>
      <p class="text-sm">
        <?= e($p['logradouro']) ?>, <?= e($p['numero']) ?><br>
        <?= e($p['bairro']) ?> — <?= e($p['cidade']) ?>/<?= e($p['uf']) ?><br>
        CEP: <?= e($p['cep']) ?>
      </p>
    </div>
    <div class="card p-5">
      <h3 class="font-bold mb-3"><i class="bi bi-heart-pulse"></i> Saúde</h3>
      <dl class="text-sm space-y-2">
        <div><dt class="text-slate-500">Tipo Sang.:</dt><dd><?= e($p['tipo_sanguineo']) ?></dd></div>
        <div><dt class="text-slate-500">Alergias:</dt><dd><?= nl2br(e($p['alergias'])) ?></dd></div>
        <div><dt class="text-slate-500">Medicamentos:</dt><dd><?= nl2br(e($p['medicamentos_uso'])) ?></dd></div>
      </dl>
    </div>
  </div>

  <?php if ($hasCuidados): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-bold"><i class="bi bi-exclamation-diamond"></i> Alertas Ativos</h3>
          <a href="../cuidados/index.php?paciente_id=<?= $id ?>" class="text-xs text-primary hover:underline">Abrir cuidados</a>
        </div>
        <?php if (!$alertasAtivos): ?>
          <p class="text-sm text-slate-400">Nenhum alerta ativo para este paciente.</p>
        <?php else: foreach ($alertasAtivos as $alerta): ?>
          <div class="border-b py-2 text-sm last:border-b-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="badge <?= $badgePrioridade[$alerta['prioridade']] ?? 'badge-secondary' ?>"><?= e(ucfirst($alerta['prioridade'])) ?></span>
              <span class="font-medium text-slate-800"><?= e($alerta['titulo']) ?></span>
            </div>
            <?php if (!empty($alerta['descricao'])): ?><div class="text-slate-500 mt-1 whitespace-pre-wrap"><?= e($alerta['descricao']) ?></div><?php endif; ?>
          </div>
        <?php endforeach; endif; ?>
      </div>
      <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-bold"><i class="bi bi-bullseye"></i> Planos de Cuidado</h3>
          <a href="../cuidados/index.php?paciente_id=<?= $id ?>" class="text-xs text-primary hover:underline">Gerenciar</a>
        </div>
        <?php if (!$planosCuidado): ?>
          <p class="text-sm text-slate-400">Nenhum plano de cuidado cadastrado.</p>
        <?php else: foreach ($planosCuidado as $plano): ?>
          <div class="border-b py-2 text-sm last:border-b-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="badge <?= $badgeStatusPlano[$plano['status']] ?? 'badge-secondary' ?>"><?= e(str_replace('_', ' ', ucfirst($plano['status']))) ?></span>
              <span class="font-medium text-slate-800"><?= e($plano['titulo']) ?></span>
            </div>
            <?php if (!empty($plano['objetivo'])): ?><div class="text-slate-500 mt-1"><?= e($plano['objetivo']) ?></div><?php endif; ?>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="card p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-slate-800"><i class="bi bi-clock-history"></i> Timeline longitudinal única</h3>
      <a href="../prontuario/index.php?paciente_id=<?= $id ?>" class="text-sm text-primary hover:underline">Abrir módulo de prontuário</a>
    </div>
    <div class="space-y-3">
      <?php foreach ($timeline as $item): ?>
        <a href="<?= e($item['url']) ?>" class="block rounded-2xl border border-slate-200 bg-slate-50 p-4 hover:border-primary/40 hover:bg-white transition">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center <?= e($item['class']) ?>"><i class="bi <?= e($item['icon']) ?>"></i></div>
            <div class="flex-1">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="font-semibold text-slate-800"><?= e($item['title']) ?></div>
                <div class="text-xs text-slate-400"><?= format_date($item['date'], true) ?></div>
              </div>
              <div class="text-sm text-slate-600 mt-1"><?= e(mb_substr((string) ($item['subtitle'] ?? ''), 0, 180)) ?></div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($hasConsentimentos): ?>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5" id="consentimentos">
      <div class="card p-5 xl:col-span-1">
        <h3 class="font-bold text-slate-800 mb-4"><i class="bi bi-shield-check"></i> Novo consentimento</h3>
        <form method="POST" class="space-y-3">
          <?= csrf_field() ?>
          <input type="hidden" name="acao" value="salvar_consentimento">
          <div>
            <label class="text-xs text-slate-500">Tipo</label>
            <select name="tipo" class="form-select" required>
              <?php foreach (['atendimento','privacidade','telemedicina','compartilhamento','marketing','financeiro','outro'] as $tipo): ?>
                <option value="<?= $tipo ?>"><?= ucfirst($tipo) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="text-xs text-slate-500">Finalidade</label>
            <input type="text" name="finalidade" required class="form-input" placeholder="Ex.: Teleconsulta e envio de documentos">
          </div>
          <div>
            <label class="text-xs text-slate-500">Base legal</label>
            <input type="text" name="base_legal" class="form-input" value="consentimento do titular">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-slate-500">Canal</label>
              <select name="canal" class="form-select">
                <?php foreach (['presencial','telefone','whatsapp','email','portal'] as $canal): ?>
                  <option value="<?= $canal ?>"><?= ucfirst($canal) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="text-xs text-slate-500">Status</label>
              <select name="status" class="form-select">
                <option value="ativo">Ativo</option>
                <option value="negado">Negado</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-slate-500">Aceito em</label>
              <input type="datetime-local" name="aceito_em" class="form-input" value="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div>
              <label class="text-xs text-slate-500">Validade até</label>
              <input type="date" name="validade_ate" class="form-input">
            </div>
          </div>
          <div>
            <label class="text-xs text-slate-500">Observações</label>
            <textarea name="observacoes" rows="3" class="form-textarea"></textarea>
          </div>
          <div class="flex justify-end">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Registrar</button>
          </div>
        </form>
      </div>
      <div class="xl:col-span-2 card p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-slate-800"><i class="bi bi-shield-lock"></i> Consentimentos registrados</h3>
          <a href="../empresa/lgpd.php" class="text-sm text-primary hover:underline">LGPD & auditoria</a>
        </div>
        <?php if (!$consentimentos): ?>
          <p class="text-sm text-slate-400">Nenhum consentimento registrado para este paciente.</p>
        <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($consentimentos as $consentimento): ?>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                  <div>
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="badge <?= status_badge_class($consentimento['status']) ?>"><?= e(ucfirst($consentimento['status'])) ?></span>
                      <span class="font-semibold text-slate-800"><?= e(ucfirst($consentimento['tipo'])) ?></span>
                    </div>
                    <div class="text-sm text-slate-700 mt-2"><?= e($consentimento['finalidade']) ?></div>
                    <div class="text-xs text-slate-500 mt-1">
                      Base legal: <?= e($consentimento['base_legal'] ?: '—') ?> · Canal: <?= e($consentimento['canal'] ?: '—') ?>
                      · Registro: <?= !empty($consentimento['aceito_em']) ? format_date($consentimento['aceito_em'], true) : format_date($consentimento['criado_em'], true) ?>
                    </div>
                    <?php if (!empty($consentimento['observacoes'])): ?><div class="text-xs text-slate-500 mt-1"><?= e($consentimento['observacoes']) ?></div><?php endif; ?>
                  </div>
                  <?php if (($consentimento['status'] ?? '') === 'ativo'): ?>
                    <form method="POST" class="inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="acao" value="revogar_consentimento">
                      <input type="hidden" name="consentimento_id" value="<?= (int) $consentimento['id'] ?>">
                      <button class="btn bg-rose-600 text-white" data-confirm="Revogar este consentimento?"><i class="bi bi-shield-x"></i> Revogar</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="card p-5">
      <h3 class="font-bold mb-3"><i class="bi bi-calendar"></i> Últimos Agendamentos</h3>
      <?php if (!$ags): ?><p class="text-sm text-slate-400">Nenhum.</p>
      <?php else: foreach ($ags as $a): ?>
        <div class="text-sm border-b py-2">
          <div><?= format_date($a['data_hora_inicio'], true) ?></div>
          <div class="text-xs text-slate-500">Dr(a). <?= e($a['prof']) ?> · <span class="badge <?= status_badge_class($a['status']) ?>"><?= e($a['status']) ?></span></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <div class="card p-5">
      <h3 class="font-bold mb-3"><i class="bi bi-journal-medical"></i> Prontuário</h3>
      <?php if (!$pronts): ?><p class="text-sm text-slate-400">Sem registros.</p>
      <?php else: foreach ($pronts as $pr): ?>
        <div class="text-sm border-b py-2">
          <a href="../prontuario/ver.php?id=<?= $pr['id'] ?>" class="hover:underline">
            <div><?= format_date($pr['data_atendimento'], true) ?></div>
            <div class="text-xs text-slate-500"><?= e(mb_substr($pr['queixa_principal'] ?? '', 0, 60)) ?></div>
          </a>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <div class="card p-5">
      <h3 class="font-bold mb-3"><i class="bi bi-receipt"></i> NFSe Emitidas</h3>
      <?php if (!$nfses): ?><p class="text-sm text-slate-400">Nenhuma.</p>
      <?php else: foreach ($nfses as $n): ?>
        <div class="text-sm border-b py-2">
          <div>NFSe #<?= e($n['numero_nfse'] ?: 'RPS ' . $n['numero_rps']) ?> — <?= money($n['valor_servico']) ?></div>
          <div class="text-xs text-slate-500"><?= format_date($n['data_emissao']) ?> · <span class="badge badge-info"><?= e($n['status']) ?></span></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
