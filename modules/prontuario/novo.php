<?php
$page_title = 'Nova Evolução';
require_once __DIR__ . '/../../includes/header.php';

$paciente_id = (int) input('paciente_id');
$ag_id = (int) input('agendamento_id') ?: null;
$themeRequest = trim((string) input('tema', ''));
$currentTheme = normalize_theme_choice($themeRequest !== '' ? $themeRequest : get_theme());
$isPsych = ($currentTheme === 'psicologia');
$isSpecialty = is_custom_specialty_theme($currentTheme);
$themeMeta = specialty_theme_meta($currentTheme);
$protocoloId = (int) input('protocolo_id');
$protocolosCatalog = [];
$protocoloSelecionado = null;

if (db_table_exists('protocolos_clinicos')) {
    $stmt = db()->prepare("SELECT * FROM protocolos_clinicos WHERE empresa_id=? AND ativo=1 AND tema=? ORDER BY titulo");
    $stmt->execute([active_company_id(), $currentTheme]);
    $protocolosCatalog = $stmt->fetchAll() ?: [];
    foreach ($protocolosCatalog as $protocolo) {
        if ((int) $protocolo['id'] === $protocoloId) {
            $protocoloSelecionado = $protocolo;
            break;
        }
    }
}

$defaults = [
    'queixa_principal' => '',
    'anamnese' => '',
    'exame_fisico' => '',
    'hipotese_diagnostica' => '',
    'conduta' => '',
    'evolucao' => '',
    'observacoes' => '',
];

if ($protocoloSelecionado && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $defaults['evolucao'] = trim((string) ($protocoloSelecionado['objetivo'] ?? ''));
    $defaults['conduta'] = trim(implode("\n\n", array_filter([
        !empty($protocoloSelecionado['checklist']) ? "Checklist\n" . $protocoloSelecionado['checklist'] : '',
        !empty($protocoloSelecionado['orientacoes']) ? "Orientações\n" . $protocoloSelecionado['orientacoes'] : '',
    ])));
    $defaults['observacoes'] = !empty($protocoloSelecionado['criterios']) ? "Alertas / critérios\n" . trim((string) $protocoloSelecionado['criterios']) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $uploadedPaths = [];
    try {
        clinic_validate_clinical_links((int) input('paciente_id'), (int) input('prontuario_id'), (int) input('agendamento_id'));
        db()->beginTransaction();

        $horaAtendimento = trim((string) input('hora_atendimento')) ?: date('H:i');
        $dataAtendimento = input('data_atendimento', date('Y-m-d')) . ' ' . $horaAtendimento . ':00';

        $stmt = db()->prepare("INSERT INTO prontuarios (empresa_id, paciente_id, profissional_id, agendamento_id, data_atendimento, anamnese, queixa_principal, exame_fisico, hipotese_diagnostica, conduta, evolucao, observacoes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            active_company_id(), (int) input('paciente_id'),
            $_SESSION['user_id'],
            (int) input('agendamento_id') ?: null,
            $dataAtendimento,
            input('anamnese'),
            input('queixa_principal'),
            input('exame_fisico'),
            input('hipotese_diagnostica'),
            input('conduta'),
            input('evolucao'),
            input('observacoes')
        ]);
        $id = (int) db()->lastInsertId();

        if ($isPsych) {
            $consentimentoRegistradoEm = trim((string) input('psi_consentimento_registrado_em'));
            $consentimentoRegistradoEm = $consentimentoRegistradoEm !== '' ? str_replace('T', ' ', $consentimentoRegistradoEm) . ':00' : null;

            psychology_save_prontuario_detail([
                'empresa_id' => active_company_id(),
                'prontuario_id' => $id,
                'paciente_id' => (int) input('paciente_id'),
                'profissional_id' => $_SESSION['user_id'],
                'tipo_registro' => input('psi_tipo_registro', 'sessao'),
                'demanda_principal' => input('psi_demanda_principal'),
                'historico_pessoal' => input('psi_historico_pessoal'),
                'historico_familiar' => input('psi_historico_familiar'),
                'historico_psiquiatrico' => input('psi_historico_psiquiatrico'),
                'uso_medicacao' => input('psi_uso_medicacao'),
                'fatores_risco' => input('psi_fatores_risco'),
                'fatores_protecao' => input('psi_fatores_protecao'),
                'objetivo_terapeutico' => input('psi_objetivo_terapeutico'),
                'queixa_historico' => input('psi_queixa_historico'),
                'expectativas_tratamento' => input('psi_expectativas_tratamento'),
                'encaminhado_por' => input('psi_encaminhado_por'),
                'motivo_encaminhamento' => input('psi_motivo_encaminhamento'),
                'historico_saude' => input('psi_historico_saude'),
                'uso_substancias' => input('psi_uso_substancias'),
                'sono_apetite' => input('psi_sono_apetite'),
                'suporte_rede' => input('psi_suporte_rede'),
                'contexto_escolar_profissional' => input('psi_contexto_escolar_profissional'),
                'antecedentes_trauma_luto' => input('psi_antecedentes_trauma_luto'),
                'humor_afeto' => input('psi_humor_afeto'),
                'pensamento_linguagem' => input('psi_pensamento_linguagem'),
                'comportamento' => input('psi_comportamento'),
                'cognicao_orientacao' => input('psi_cognicao_orientacao'),
                'estado_mental_aparencia' => input('psi_estado_mental_aparencia'),
                'estado_mental_sensopercepcao' => input('psi_estado_mental_sensopercepcao'),
                'risco_suicida' => input('psi_risco_suicida', 'nao_identificado'),
                'risco_autoheteroagressao' => input('psi_risco_autoheteroagressao'),
                'temas_sessao' => input('psi_temas_sessao'),
                'intervencoes_realizadas' => input('psi_intervencoes_realizadas'),
                'tecnicas_utilizadas' => input('psi_tecnicas_utilizadas'),
                'resposta_paciente' => input('psi_resposta_paciente'),
                'adesao_processo' => input('psi_adesao_processo'),
                'recursos_mobilizados' => input('psi_recursos_mobilizados'),
                'manejo_crise' => input('psi_manejo_crise'),
                'tarefas_entre_sessoes' => input('psi_tarefas_entre_sessoes'),
                'encaminhamentos' => input('psi_encaminhamentos'),
                'plano_sessao_seguinte' => input('psi_plano_sessao_seguinte'),
                'observacoes_confidenciais' => input('psi_observacoes_confidenciais'),
                'consentimento_tipo' => input('psi_consentimento_tipo', 'ambos'),
                'consentimento_terapia_texto' => input('psi_consentimento_terapia_texto'),
                'consentimento_tele_texto' => input('psi_consentimento_tele_texto'),
                'consentimento_privacidade_texto' => input('psi_consentimento_privacidade_texto'),
                'consentimento_compartilhamento_texto' => input('psi_consentimento_compartilhamento_texto'),
                'consentimento_gravacao_texto' => input('psi_consentimento_gravacao_texto'),
                'consentimento_terapia_aceito' => input('psi_consentimento_terapia_aceito') ? 1 : 0,
                'consentimento_tele_aceito' => input('psi_consentimento_tele_aceito') ? 1 : 0,
                'consentimento_privacidade_aceito' => input('psi_consentimento_privacidade_aceito') ? 1 : 0,
                'consentimento_compartilhamento_aceito' => input('psi_consentimento_compartilhamento_aceito') ? 1 : 0,
                'consentimento_gravacao_aceito' => input('psi_consentimento_gravacao_aceito') ? 1 : 0,
                'consentimento_nome_responsavel' => input('psi_consentimento_nome_responsavel'),
                'consentimento_documento' => input('psi_consentimento_documento'),
                'consentimento_parentesco' => input('psi_consentimento_parentesco'),
                'consentimento_testemunha' => input('psi_consentimento_testemunha'),
                'consentimento_registrado_em' => $consentimentoRegistradoEm,
            ]);

            psychology_save_plan([
                'id' => (int) input('psi_plano_id'),
                'empresa_id' => active_company_id(),
                'prontuario_id' => $id,
                'paciente_id' => (int) input('paciente_id'),
                'profissional_id' => $_SESSION['user_id'],
                'abordagem' => input('psi_plano_abordagem'),
                'demanda_focal' => input('psi_plano_demanda_focal'),
                'objetivo_principal' => input('psi_plano_objetivo_principal'),
                'metas' => input('psi_plano_metas'),
                'metas_curto_prazo' => input('psi_plano_metas_curto_prazo'),
                'metas_medio_prazo' => input('psi_plano_metas_medio_prazo'),
                'metas_longo_prazo' => input('psi_plano_metas_longo_prazo'),
                'estrategias_intervencao' => input('psi_plano_estrategias_intervencao'),
                'indicadores_evolucao' => input('psi_plano_indicadores_evolucao'),
                'criterios_alta' => input('psi_plano_criterios_alta'),
                'risco_monitorado' => input('psi_plano_risco_monitorado'),
                'frequencia_sugerida' => input('psi_plano_frequencia_sugerida'),
                'status' => input('psi_plano_status', 'ativo'),
                'data_inicio' => input('data_atendimento', date('Y-m-d')),
                'data_reavaliacao' => input('psi_plano_data_reavaliacao'),
                'observacoes' => input('psi_plano_observacoes'),
            ]);
        }

        if ($isSpecialty) {
            specialty_save_prontuario_detail($currentTheme, array_merge(specialty_request_payload($currentTheme), [
                'empresa_id' => active_company_id(),
                'prontuario_id' => $id,
                'paciente_id' => (int) input('paciente_id'),
                'profissional_id' => $_SESSION['user_id'],
            ]));
        }

        if (!empty($_FILES['anexos']['name'][0])) {
            $files = $_FILES['anexos'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                if ((int) $files['size'][$i] > 10 * 1024 * 1024) throw new RuntimeException('Anexo maior que 10MB.');
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'], true)) {
                    continue;
                }
                $_FILES['_clinical_attachment'] = [
                    'name' => $files['name'][$i], 'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                $upload = upload_file('_clinical_attachment', 'prontuarios');
                unset($_FILES['_clinical_attachment']);
                if (!$upload) throw new RuntimeException('Não foi possível salvar o anexo.');
                $uploadedPaths[] = UPLOAD_PATH . '/' . $upload['arquivo'];
                db()->prepare("INSERT INTO anexos (empresa_id, paciente_id, prontuario_id, tipo, titulo, arquivo, mime_type, tamanho, criado_por) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([active_company_id(), (int) input('paciente_id'), $id, 'documento', $upload['nome_original'], $upload['arquivo'], $upload['mime'], $upload['tamanho'], $_SESSION['user_id']]);
            }
        }

        db()->commit();
        $message = $isPsych ? 'Registro psicológico salvo com sucesso!' : ($isSpecialty ? 'Registro de ' . specialty_theme_meta($currentTheme)['nome'] . ' salvo com sucesso!' : 'Evolução registrada!');
        log_action('criar', 'prontuarios', $id, $message . ($protocoloSelecionado ? ' Protocolo aplicado: ' . $protocoloSelecionado['titulo'] : ''));
        flash('success', $message);
        redirect('ver.php?id=' . $id);
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        foreach ($uploadedPaths as $uploadedPath) {
            if (is_file($uploadedPath)) unlink($uploadedPath);
        }
        flash('error', 'Erro: ' . $e->getMessage());
    }
}

$pacientes = db()->prepare("SELECT id, nome FROM pacientes WHERE empresa_id=? AND ativo=1 ORDER BY nome");
$pacientes->execute([active_company_id()]);
$pacientes = $pacientes->fetchAll() ?: [];

$psiForm = psychology_prontuario_defaults();
$psiPlan = $isPsych ? psychology_plan_load($paciente_id, 0) : psychology_plan_defaults();
$specialtyForm = $isSpecialty ? specialty_record_defaults($currentTheme) : [];
$introTitle = $isPsych ? 'Novo Registro Psicológico' : ($isSpecialty ? 'Novo Registro de ' . $themeMeta['nome'] : 'Nova Evolução Clínica');

$fieldValue = static function ($name) use ($defaults) {
    return e($_POST[$name] ?? $defaults[$name] ?? '');
};
?>
<div class="space-y-5">
  <div>
    <a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a>
    <h1 class="text-2xl font-bold text-slate-800"><?= e($introTitle) ?></h1>
    <?php if ($isPsych): ?>
      <p class="text-sm text-slate-500 mt-1">Formulário ampliado com anamnese detalhada, evolução da sessão, plano terapêutico e termos de consentimento.</p>
    <?php elseif ($isSpecialty): ?>
      <p class="text-sm text-slate-500 mt-1"><?= e($themeMeta['descricao']) ?></p>
    <?php endif; ?>
  </div>

  <?php if ($protocoloSelecionado): ?>
    <div class="card p-4 border-violet-200 bg-violet-50">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <div class="font-semibold text-violet-900"><i class="bi bi-magic"></i> Protocolo pré-aplicado</div>
          <div class="text-sm text-violet-800"><?= e($protocoloSelecionado['titulo']) ?><?= !empty($protocoloSelecionado['categoria']) ? ' · ' . e($protocoloSelecionado['categoria']) : '' ?></div>
        </div>
        <a href="<?= APP_URL ?>/modules/protocolos/index.php?tema=<?= urlencode($currentTheme) ?><?= $paciente_id ? '&paciente_id=' . $paciente_id : '' ?>" class="text-sm text-violet-800 hover:underline">Voltar para protocolos</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($protocolosCatalog): ?>
    <div class="card p-5" id="protocol-panel">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between mb-3">
        <div>
          <h2 class="font-bold text-slate-800"><i class="bi bi-journal-check"></i> Protocolos acionáveis</h2>
          <p class="text-sm text-slate-500">As sugestões abaixo acompanham a queixa principal e ajudam a inserir checklist, alertas e orientações no registro.</p>
        </div>
        <a href="<?= APP_URL ?>/modules/protocolos/index.php?tema=<?= urlencode($currentTheme) ?><?= $paciente_id ? '&paciente_id=' . $paciente_id : '' ?>" class="btn bg-slate-100 text-slate-700"><i class="bi bi-box-arrow-up-right"></i> Biblioteca completa</a>
      </div>
      <div id="protocol-suggestions" class="grid grid-cols-1 xl:grid-cols-2 gap-3"></div>
      <div id="protocol-empty" class="text-sm text-slate-400 mt-2">Digite a queixa principal para ver sugestões contextuais.</div>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="card p-6 space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="agendamento_id" value="<?= $ag_id ?>">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Paciente *</label>
        <select data-patient-search name="paciente_id" required class="form-select">
          <option value="">Selecione...</option>
          <?php foreach ($pacientes as $paciente): ?>
            <option value="<?= $paciente['id'] ?>" <?= $paciente['id'] == $paciente_id ? 'selected' : '' ?>><?= e($paciente['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Data</label>
        <input type="date" name="data_atendimento" value="<?= e($_POST['data_atendimento'] ?? date('Y-m-d')) ?>" required class="form-input">
      </div>
      <div>
        <label class="text-sm font-medium">Hora</label>
        <input type="time" name="hora_atendimento" value="<?= e($_POST['hora_atendimento'] ?? date('H:i')) ?>" class="form-input">
      </div>
    </div>

    <div>
      <label class="text-sm font-medium"><?= $isPsych ? 'Queixa / demanda principal' : 'Queixa principal' ?></label>
      <textarea name="queixa_principal" id="queixa_principal" rows="2" class="form-textarea"><?= $fieldValue('queixa_principal') ?></textarea>
    </div>
    <div>
      <label class="text-sm font-medium"><?= $isPsych ? 'Anamnese livre / resumo clínico' : 'Anamnese / histórico relevante' ?></label>
      <textarea name="anamnese" rows="4" class="form-textarea"><?= $fieldValue('anamnese') ?></textarea>
    </div>
    <div>
      <label class="text-sm font-medium"><?= $isPsych ? 'Estado mental / observação clínica' : 'Exame físico / clínico' ?></label>
      <textarea name="exame_fisico" rows="3" class="form-textarea"><?= $fieldValue('exame_fisico') ?></textarea>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium"><?= $isPsych ? 'Hipótese diagnóstica / formulação de caso' : 'Hipótese diagnóstica' ?></label>
        <textarea name="hipotese_diagnostica" rows="3" class="form-textarea"><?= $fieldValue('hipotese_diagnostica') ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Conduta / plano</label>
        <textarea name="conduta" id="conduta" rows="3" class="form-textarea"><?= $fieldValue('conduta') ?></textarea>
      </div>
    </div>
    <div>
      <label class="text-sm font-medium">Evolução</label>
      <textarea name="evolucao" id="evolucao" rows="3" class="form-textarea"><?= $fieldValue('evolucao') ?></textarea>
    </div>
    <div>
      <label class="text-sm font-medium">Observações gerais</label>
      <textarea name="observacoes" id="observacoes" rows="2" class="form-textarea"><?= $fieldValue('observacoes') ?></textarea>
    </div>

    <?php if ($isPsych): ?>
      <?php require __DIR__ . '/_psicologia_form.php'; ?>
    <?php elseif ($isSpecialty): ?>
      <?php $specialtyTheme = $currentTheme; require __DIR__ . '/_specialty_form.php'; ?>
    <?php endif; ?>

    <div>
      <label class="text-sm font-medium">Anexos — múltiplos</label>
      <input type="file" name="anexos[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="form-input">
      <p class="text-xs text-slate-500 mt-1">Formatos: JPG, PNG, PDF, DOC e DOCX. Máx. 10MB por arquivo.</p>
    </div>
    <div class="flex justify-end gap-2 pt-3 border-t">
      <a href="index.php" class="btn bg-slate-200 text-slate-700">Cancelar</a>
      <button class="btn btn-primary"><i class="bi bi-check"></i> Salvar registro</button>
    </div>
  </form>
</div>

<?php if ($protocolosCatalog): ?>
<script>
const protocolosCatalog = <?= json_encode(array_map(static function ($item) {
    return [
        'id' => (int) $item['id'],
        'titulo' => (string) $item['titulo'],
        'categoria' => (string) ($item['categoria'] ?? ''),
        'objetivo' => (string) ($item['objetivo'] ?? ''),
        'checklist' => (string) ($item['checklist'] ?? ''),
        'criterios' => (string) ($item['criterios'] ?? ''),
        'orientacoes' => (string) ($item['orientacoes'] ?? ''),
        'tags' => (string) ($item['tags'] ?? ''),
    ];
}, $protocolosCatalog), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const preselectedProtocolId = <?= (int) ($protocoloSelecionado['id'] ?? 0) ?>;
const suggestionsEl = document.getElementById('protocol-suggestions');
const emptyEl = document.getElementById('protocol-empty');
const complaintEl = document.getElementById('queixa_principal');

function tokenize(text) {
  return (text || '').toLowerCase().replace(/[^\p{L}\p{N}\s]+/gu, ' ').split(/\s+/).filter(token => token.length >= 3);
}

function scoreProtocol(protocol, tokens) {
  const haystack = [protocol.titulo, protocol.categoria, protocol.objetivo, protocol.checklist, protocol.criterios, protocol.orientacoes, protocol.tags].join(' ').toLowerCase();
  return tokens.reduce((score, token) => {
    if (haystack.includes(token)) score += 2;
    if ((protocol.titulo || '').toLowerCase().includes(token)) score += 3;
    if ((protocol.tags || '').toLowerCase().includes(token)) score += 2;
    return score;
  }, 0) + (protocol.id === preselectedProtocolId ? 100 : 0);
}

function appendToField(fieldId, text, prefix) {
  const field = document.getElementById(fieldId);
  if (!field || !text) return;
  const cleanText = text.trim();
  const block = prefix ? `${prefix}\n${cleanText}` : cleanText;
  field.value = field.value.trim() ? `${field.value.trim()}\n\n${block}` : block;
  field.dispatchEvent(new Event('input'));
  field.focus();
}

function protocolCard(protocol) {
  const div = document.createElement('div');
  div.className = 'rounded-2xl border border-slate-200 bg-slate-50 p-4';
  div.innerHTML = `
    <div class="flex items-start justify-between gap-3 mb-2">
      <div>
        <div class="font-semibold text-slate-800">${protocol.titulo}</div>
        <div class="text-xs text-slate-500">${protocol.categoria || 'Protocolo contextual'}${protocol.tags ? ' · ' + protocol.tags : ''}</div>
      </div>
      ${protocol.id === preselectedProtocolId ? '<span class="badge badge-success">Pré-aplicado</span>' : ''}
    </div>
    ${protocol.objetivo ? `<p class="text-sm text-slate-600 mb-3">${protocol.objetivo}</p>` : ''}
    <div class="flex flex-wrap gap-2">
      ${protocol.checklist ? '<button type="button" class="btn bg-violet-600 text-white text-xs px-3 py-2" data-action="checklist">Checklist na conduta</button>' : ''}
      ${protocol.criterios ? '<button type="button" class="btn bg-amber-500 text-white text-xs px-3 py-2" data-action="criterios">Alertas nas observações</button>' : ''}
      ${protocol.orientacoes ? '<button type="button" class="btn bg-emerald-600 text-white text-xs px-3 py-2" data-action="orientacoes">Orientações na conduta</button>' : ''}
      <a class="btn bg-slate-100 text-slate-700 text-xs px-3 py-2" href="<?= APP_URL ?>/modules/protocolos/index.php?tema=<?= urlencode($currentTheme) ?><?= $paciente_id ? '&paciente_id=' . $paciente_id : '' ?>">Abrir biblioteca</a>
    </div>
  `;
  div.querySelectorAll('button[data-action]').forEach(btn => {
    btn.addEventListener('click', () => {
      if (btn.dataset.action === 'checklist') appendToField('conduta', protocol.checklist, 'Checklist');
      if (btn.dataset.action === 'criterios') appendToField('observacoes', protocol.criterios, 'Alertas / critérios');
      if (btn.dataset.action === 'orientacoes') appendToField('conduta', protocol.orientacoes, 'Orientações');
    });
  });
  return div;
}

function refreshProtocolSuggestions() {
  const tokens = tokenize(complaintEl.value || '');
  const ranked = protocolosCatalog
    .map(protocol => ({ protocol, score: scoreProtocol(protocol, tokens) }))
    .filter(item => item.score > 0 || item.protocol.id === preselectedProtocolId)
    .sort((a, b) => b.score - a.score)
    .slice(0, 4)
    .map(item => item.protocol);

  suggestionsEl.innerHTML = '';
  if (!ranked.length) {
    emptyEl.style.display = '';
    return;
  }
  emptyEl.style.display = 'none';
  ranked.forEach(protocol => suggestionsEl.appendChild(protocolCard(protocol)));
}

complaintEl.addEventListener('input', refreshProtocolSuggestions);
refreshProtocolSuggestions();
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
