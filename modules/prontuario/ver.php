<?php
$page_title = 'Detalhes da Evolução';
require_once __DIR__ . '/../../includes/header.php';

$id = (int) input('id');
$stmt = db()->prepare("SELECT pr.*, p.nome AS pn, p.empresa_id AS paciente_empresa, u.nome AS prof FROM prontuarios pr JOIN pacientes p ON p.id=pr.paciente_id JOIN usuarios u ON u.id=pr.profissional_id WHERE pr.id=? LIMIT 1");
$stmt->execute([$id]);
$pr = $stmt->fetch();
if (!$pr || (int) ($pr['paciente_empresa'] ?? 0) !== active_company_id()) {
    flash('error', 'Não encontrado');
    redirect('index.php');
}

audit_entity_access('acesso_prontuario', 'prontuarios', $id, 'Visualização do prontuário do paciente #' . (int) $pr['paciente_id'] . '.');

$anexos = db()->prepare("SELECT * FROM anexos WHERE prontuario_id=? ORDER BY criado_em DESC");
$anexos->execute([$id]);
$anexos = $anexos->fetchAll();

$receitas = db()->prepare("SELECT * FROM receitas WHERE prontuario_id=? ORDER BY data_emissao DESC");
$receitas->execute([$id]);
$receitas = $receitas->fetchAll();

$currentTheme = normalize_theme_choice(get_theme());
$isPsych = ($currentTheme === 'psicologia');
$isSpecialty = is_custom_specialty_theme($currentTheme);
$themeMeta = specialty_theme_meta($currentTheme);
$psi = $isPsych ? psychology_prontuario_load($id) : psychology_prontuario_defaults();
$psiPlan = $isPsych ? psychology_plan_load((int) $pr['paciente_id'], $id) : psychology_plan_defaults();
$specialtyData = $isSpecialty ? specialty_record_load($currentTheme, $id) : [];
$specialtyDefinition = $isSpecialty ? specialty_form_definitions($currentTheme) : null;

$tipoRegistroLabel = [
    'anamnese_inicial' => 'Anamnese inicial',
    'sessao' => 'Sessão',
    'retorno' => 'Retorno / reavaliação',
    'encerramento' => 'Encerramento / alta',
];
$riscoLabel = [
    'nao_identificado' => 'Não identificado',
    'baixo' => 'Baixo',
    'moderado' => 'Moderado',
    'alto' => 'Alto',
    'em_monitoramento' => 'Em monitoramento',
];
$planStatusBadge = [
    'ativo' => 'badge-success',
    'em_acompanhamento' => 'badge-info',
    'pausado' => 'badge-warning',
    'concluido' => 'badge-secondary',
];
$riscoBadge = [
    'alto' => 'badge-warning',
    'moderado' => 'badge-warning',
    'baixo' => 'badge-info',
    'em_monitoramento' => 'badge-info',
    'nao_identificado' => 'badge-secondary',
];
?>
<div class="mb-5 flex justify-between flex-wrap gap-2">
  <div>
    <a href="index.php?paciente_id=<?= $pr['paciente_id'] ?>" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a>
    <h1 class="text-2xl font-bold"><?= e($pr['pn']) ?></h1>
    <p class="text-sm text-slate-500"><?= format_date($pr['data_atendimento'], true) ?> · <?= $isPsych ? 'Profissional' : 'Dr(a).' ?> <?= e($pr['prof']) ?></p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="editar.php?id=<?= $id ?>" class="btn bg-amber-500 text-white"><i class="bi bi-pencil"></i> Editar</a>
    <a href="receita.php?prontuario_id=<?= $id ?>&paciente_id=<?= $pr['paciente_id'] ?>" class="btn bg-purple-600 text-white"><i class="bi bi-prescription2"></i> Nova Receita</a>
    <a href="atestado.php?prontuario_id=<?= $id ?>&paciente_id=<?= $pr['paciente_id'] ?>" class="btn bg-indigo-600 text-white"><i class="bi bi-file-medical"></i> Atestado</a>
    <?php if ($isPsych): ?>
      <a href="../psicologia/planos.php?paciente_id=<?= $pr['paciente_id'] ?>" class="btn bg-fuchsia-600 text-white"><i class="bi bi-bullseye"></i> Plano Terapêutico</a>
    <?php elseif ($isSpecialty): ?>
      <a href="<?= specialty_module_url($currentTheme) ?>" class="btn bg-slate-800 text-white"><i class="bi bi-stars"></i> <?= e($themeMeta['nome']) ?></a>
    <?php endif; ?>
  </div>
</div>

<div class="card p-6 space-y-4">
  <?php foreach([
      'queixa_principal' => $isPsych ? 'Queixa / demanda principal' : 'Queixa principal',
      'anamnese' => $isPsych ? 'Anamnese livre / resumo clínico' : 'Anamnese / histórico relevante',
      'exame_fisico' => $isPsych ? 'Estado mental / observação clínica' : 'Exame físico',
      'hipotese_diagnostica' => $isPsych ? 'Hipótese diagnóstica / formulação de caso' : 'Hipótese diagnóstica',
      'conduta' => 'Conduta',
      'evolucao' => 'Evolução',
      'observacoes' => 'Observações gerais'
  ] as $k => $l): if (!empty($pr[$k])): ?>
    <div>
      <h3 class="font-bold text-slate-700 mb-1"><?= e($l) ?></h3>
      <p class="text-sm text-slate-600 whitespace-pre-wrap"><?= e($pr[$k]) ?></p>
    </div>
  <?php endif; endforeach; ?>
</div>

<?php if ($isSpecialty && $specialtyDefinition): ?>
  <div class="rounded-2xl border border-primary/20 bg-white p-5 space-y-5 mt-5">
    <div class="flex items-center justify-between gap-3 flex-wrap">
      <div>
        <h2 class="text-lg font-bold text-slate-800"><span class="mr-2"><?= e($themeMeta['icone']) ?></span><?= e($specialtyDefinition['title']) ?></h2>
        <p class="text-sm text-slate-500 mt-1"><?= e($specialtyDefinition['description']) ?></p>
      </div>
      <span class="badge badge-info"><?= e($themeMeta['nome']) ?></span>
    </div>
    <?php foreach (($specialtyDefinition['sections'] ?? []) as $section): ?>
      <?php
        $filled = [];
        foreach (($section['fields'] ?? []) as $field) {
            $value = trim((string) ($specialtyData[$field['name']] ?? ''));
            if ($value !== '') {
                $filled[] = ['label' => $field['label'], 'value' => $value, 'full' => (($field['width'] ?? 'half') === 'full')];
            }
        }
      ?>
      <?php if ($filled): ?>
        <div class="card p-5 bg-slate-50/70">
          <h3 class="font-bold text-slate-800 mb-4"><?= e($section['title']) ?></h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <?php foreach ($filled as $item): ?>
              <div class="<?= $item['full'] ? 'md:col-span-2' : '' ?>">
                <div class="text-xs uppercase tracking-wide text-slate-500 mb-1"><?= e($item['label']) ?></div>
                <div class="text-slate-700 whitespace-pre-wrap"><?= e($item['value']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($isPsych): ?>
<div class="rounded-2xl border border-violet-200 bg-gradient-to-r from-violet-50 to-fuchsia-50 p-5 space-y-5 mt-5">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <h2 class="text-lg font-bold text-violet-900"><i class="bi bi-flower1"></i> Detalhamento Psicológico</h2>
      <p class="text-sm text-violet-700 mt-1">Informações estruturadas complementares do prontuário psicológico.</p>
    </div>
    <div class="flex gap-2 flex-wrap">
      <span class="badge badge-info"><?= e($tipoRegistroLabel[$psi['tipo_registro'] ?? 'sessao'] ?? 'Sessão') ?></span>
      <span class="badge <?= $riscoBadge[$psi['risco_suicida'] ?? 'nao_identificado'] ?? 'badge-secondary' ?>">Risco: <?= e($riscoLabel[$psi['risco_suicida'] ?? 'nao_identificado'] ?? 'Não identificado') ?></span>
    </div>
  </div>

  <div class="card p-5 bg-white/90">
    <h3 class="font-bold text-slate-800 mb-4">Anamnese Psicológica Ampliada</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <?php foreach([
          'demanda_principal' => 'Demanda principal / contexto da procura',
          'queixa_historico' => 'História da queixa atual',
          'expectativas_tratamento' => 'Expectativas sobre o tratamento',
          'encaminhado_por' => 'Encaminhado por',
          'motivo_encaminhamento' => 'Motivo do encaminhamento',
          'historico_pessoal' => 'Histórico pessoal',
          'historico_familiar' => 'Histórico familiar',
          'historico_psiquiatrico' => 'Histórico psiquiátrico / psicoterápico',
          'historico_saude' => 'Histórico de saúde geral',
          'uso_medicacao' => 'Uso de medicação',
          'uso_substancias' => 'Uso de substâncias',
          'sono_apetite' => 'Sono e apetite',
          'suporte_rede' => 'Rede de apoio / suporte',
          'contexto_escolar_profissional' => 'Contexto escolar / profissional',
          'antecedentes_trauma_luto' => 'Traumas, perdas e eventos marcantes',
          'fatores_risco' => 'Fatores de risco',
          'fatores_protecao' => 'Fatores de proteção',
          'objetivo_terapeutico' => 'Objetivo terapêutico inicial'
      ] as $field => $label): if (!empty($psi[$field])): ?>
        <div class="<?= in_array($field, ['demanda_principal','queixa_historico','expectativas_tratamento','historico_pessoal','historico_familiar','historico_psiquiatrico','historico_saude','fatores_risco','fatores_protecao','objetivo_terapeutico'], true) ? 'md:col-span-2' : '' ?>">
          <div class="text-xs uppercase text-slate-500 mb-1"><?= e($label) ?></div>
          <div class="text-slate-700 whitespace-pre-wrap"><?= e($psi[$field]) ?></div>
        </div>
      <?php endif; endforeach; ?>
    </div>
  </div>

  <div class="card p-5 bg-white/90">
    <h3 class="font-bold text-slate-800 mb-4">Evolução da Sessão e Estado Mental</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <?php foreach([
          'estado_mental_aparencia' => 'Aparência / atitude / contato',
          'humor_afeto' => 'Humor e afeto',
          'pensamento_linguagem' => 'Pensamento e linguagem',
          'estado_mental_sensopercepcao' => 'Sensopercepção / conteúdo perceptivo',
          'comportamento' => 'Comportamento observado',
          'cognicao_orientacao' => 'Cognição / orientação / insight',
          'risco_autoheteroagressao' => 'Risco auto / heteroagressivo e condutas',
          'temas_sessao' => 'Temas centrais trabalhados',
          'intervencoes_realizadas' => 'Intervenções realizadas',
          'tecnicas_utilizadas' => 'Técnicas / recursos utilizados',
          'resposta_paciente' => 'Resposta do paciente',
          'adesao_processo' => 'Adesão ao processo terapêutico',
          'recursos_mobilizados' => 'Recursos mobilizados / forças do paciente',
          'manejo_crise' => 'Manejo de crise / plano de segurança',
          'tarefas_entre_sessoes' => 'Tarefas / combinados entre sessões',
          'encaminhamentos' => 'Encaminhamentos / articulações',
          'plano_sessao_seguinte' => 'Plano para a sessão seguinte',
          'observacoes_confidenciais' => 'Observações confidenciais do profissional'
      ] as $field => $label): if (!empty($psi[$field])): ?>
        <div class="<?= in_array($field, ['temas_sessao','intervencoes_realizadas','tecnicas_utilizadas','plano_sessao_seguinte','observacoes_confidenciais'], true) ? 'md:col-span-2' : '' ?>">
          <div class="text-xs uppercase text-slate-500 mb-1"><?= e($label) ?></div>
          <div class="text-slate-700 whitespace-pre-wrap"><?= e($psi[$field]) ?></div>
        </div>
      <?php endif; endforeach; ?>
    </div>
  </div>

  <?php if (!empty($psiPlan['objetivo_principal']) || !empty($psiPlan['metas']) || !empty($psiPlan['abordagem']) || !empty($psiPlan['demanda_focal'])): ?>
  <div class="card p-5 bg-white/90">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-4">
      <div>
        <h3 class="font-bold text-slate-800">Plano Terapêutico Vinculado</h3>
        <p class="text-sm text-slate-500">Objetivos, metas e critérios de acompanhamento relacionados a este prontuário.</p>
      </div>
      <span class="badge <?= $planStatusBadge[$psiPlan['status'] ?? 'ativo'] ?? 'badge-secondary' ?>"><?= e(str_replace('_', ' ', $psiPlan['status'] ?? 'ativo')) ?></span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <?php foreach([
          'abordagem' => 'Abordagem',
          'demanda_focal' => 'Demanda focal',
          'objetivo_principal' => 'Objetivo principal',
          'metas' => 'Síntese das metas terapêuticas',
          'metas_curto_prazo' => 'Metas de curto prazo',
          'metas_medio_prazo' => 'Metas de médio prazo',
          'metas_longo_prazo' => 'Metas de longo prazo',
          'estrategias_intervencao' => 'Estratégias / intervenções previstas',
          'indicadores_evolucao' => 'Indicadores de evolução',
          'criterios_alta' => 'Critérios de alta / transição',
          'risco_monitorado' => 'Riscos / pontos de monitoramento',
          'frequencia_sugerida' => 'Frequência sugerida',
          'observacoes' => 'Observações do plano'
      ] as $field => $label): if (!empty($psiPlan[$field])): ?>
        <div class="<?= in_array($field, ['demanda_focal','objetivo_principal','metas','metas_curto_prazo','metas_medio_prazo','metas_longo_prazo','estrategias_intervencao','indicadores_evolucao','criterios_alta','risco_monitorado','observacoes'], true) ? 'md:col-span-2' : '' ?>">
          <div class="text-xs uppercase text-slate-500 mb-1"><?= e($label) ?></div>
          <div class="text-slate-700 whitespace-pre-wrap"><?= e($psiPlan[$field]) ?></div>
        </div>
      <?php endif; endforeach; ?>
      <?php if (!empty($psiPlan['data_reavaliacao'])): ?>
        <div>
          <div class="text-xs uppercase text-slate-500 mb-1">Reavaliação</div>
          <div class="text-slate-700"><?= format_date($psiPlan['data_reavaliacao']) ?></div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($anexos): ?>
<div class="card p-6 mt-5">
  <h3 class="font-bold text-slate-700 mb-3"><i class="bi bi-paperclip"></i> Anexos</h3>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <?php foreach($anexos as $a): ?>
      <a href="<?= APP_URL ?>/modules/arquivos/download.php?tipo=anexo&amp;id=<?= (int) $a['id'] ?>" target="_blank" class="p-3 border rounded-lg hover:border-primary text-center">
        <i class="bi bi-file-earmark text-3xl text-slate-400"></i>
        <p class="text-xs mt-1 truncate"><?= e($a['titulo']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($receitas): ?>
<div class="card p-6 mt-5">
  <h3 class="font-bold text-slate-700 mb-3"><i class="bi bi-prescription2"></i> Receitas / Atestados</h3>
  <ul class="space-y-2">
    <?php foreach($receitas as $r): ?>
      <li class="flex justify-between items-center p-3 border rounded-lg">
        <div>
          <span class="badge badge-info"><?= e($r['tipo']) ?></span>
          <span class="text-sm ml-2"><?= format_date($r['data_emissao']) ?></span>
          <span class="text-xs text-slate-500"><?= e(mb_substr($r['conteudo'],0,80)) ?></span>
        </div>
        <a href="receita_pdf.php?id=<?= $r['id'] ?>" target="_blank" class="text-primary"><i class="bi bi-printer"></i></a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
