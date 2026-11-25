<?php
$page_title = 'Editar Evolução';
require_once __DIR__ . '/../../includes/header.php';

$id = (int) input('id');
$stmt = db()->prepare("SELECT pr.*, p.nome AS paciente_nome, p.empresa_id AS paciente_empresa FROM prontuarios pr JOIN pacientes p ON p.id = pr.paciente_id WHERE pr.id = ? LIMIT 1");
$stmt->execute([$id]);
$pr = $stmt->fetch();
if (!$pr || (int) ($pr['paciente_empresa'] ?? 0) !== active_company_id()) {
    flash('error', 'Prontuário não encontrado.');
    redirect('index.php');
}

$currentTheme = normalize_theme_choice(get_theme());
$isPsych = ($currentTheme === 'psicologia');
$isSpecialty = is_custom_specialty_theme($currentTheme);
$themeMeta = specialty_theme_meta($currentTheme);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    try {
        db()->beginTransaction();

        $horaAtendimento = trim((string) input('hora_atendimento')) ?: date('H:i', strtotime($pr['data_atendimento']));
        $dataAtendimento = input('data_atendimento', date('Y-m-d', strtotime($pr['data_atendimento']))) . ' ' . $horaAtendimento . ':00';

        $stmt = db()->prepare("UPDATE prontuarios SET data_atendimento=?, queixa_principal=?, anamnese=?, exame_fisico=?, hipotese_diagnostica=?, conduta=?, evolucao=?, observacoes=? WHERE id=?");
        $stmt->execute([
            $dataAtendimento,
            input('queixa_principal'),
            input('anamnese'),
            input('exame_fisico'),
            input('hipotese_diagnostica'),
            input('conduta'),
            input('evolucao'),
            input('observacoes'),
            $id
        ]);

        if ($isPsych) {
            $consentimentoRegistradoEm = trim((string) input('psi_consentimento_registrado_em'));
            $consentimentoRegistradoEm = $consentimentoRegistradoEm !== '' ? str_replace('T', ' ', $consentimentoRegistradoEm) . ':00' : null;

            psychology_save_prontuario_detail([
                'empresa_id' => active_company_id(),
                'prontuario_id' => $id,
                'paciente_id' => (int) $pr['paciente_id'],
                'profissional_id' => (int) $pr['profissional_id'],
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
                'paciente_id' => (int) $pr['paciente_id'],
                'profissional_id' => (int) $pr['profissional_id'],
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
                'data_inicio' => input('data_atendimento', date('Y-m-d', strtotime($pr['data_atendimento']))),
                'data_reavaliacao' => input('psi_plano_data_reavaliacao'),
                'observacoes' => input('psi_plano_observacoes'),
            ]);
        }

        if ($isSpecialty) {
            specialty_save_prontuario_detail($currentTheme, array_merge(specialty_request_payload($currentTheme), [
                'empresa_id' => active_company_id(),
                'prontuario_id' => $id,
                'paciente_id' => (int) $pr['paciente_id'],
                'profissional_id' => (int) $pr['profissional_id'],
            ]));
        }

        db()->commit();
        flash('success', $isPsych ? 'Registro psicológico atualizado!' : ($isSpecialty ? 'Registro de ' . $themeMeta['nome'] . ' atualizado!' : 'Prontuário atualizado!'));
        redirect('ver.php?id=' . $id);
    } catch (Exception $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash('error', 'Erro: ' . $e->getMessage());
    }
}

$psiForm = $isPsych ? psychology_prontuario_load($id) : psychology_prontuario_defaults();
$psiPlan = $isPsych ? psychology_plan_load((int) $pr['paciente_id'], $id) : psychology_plan_defaults();
$specialtyForm = $isSpecialty ? specialty_record_load($currentTheme, $id) : [];
?>
<div class="mb-5">
  <a href="ver.php?id=<?= $id ?>" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a>
  <h1 class="text-2xl font-bold"><?= e($isPsych ? 'Editar Registro Psicológico' : ($isSpecialty ? 'Editar Registro de ' . $themeMeta['nome'] : 'Editar Evolução')) ?></h1>
</div>
<form method="POST" class="card p-6 space-y-4">
  <?= csrf_field() ?>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="md:col-span-2">
      <label class="text-sm font-medium">Paciente</label>
      <input value="<?= e($pr['paciente_nome']) ?>" class="form-input bg-slate-50" disabled>
    </div>
    <div>
      <label class="text-sm font-medium">Data</label>
      <input type="date" name="data_atendimento" value="<?= e(date('Y-m-d', strtotime($pr['data_atendimento']))) ?>" required class="form-input">
    </div>
    <div>
      <label class="text-sm font-medium">Hora</label>
      <input type="time" name="hora_atendimento" value="<?= e(date('H:i', strtotime($pr['data_atendimento']))) ?>" class="form-input">
    </div>
  </div>

  <div>
    <label class="text-sm font-medium"><?= $isPsych ? 'Queixa / demanda principal' : 'Queixa principal' ?></label>
    <textarea name="queixa_principal" rows="2" class="form-textarea"><?= e($pr['queixa_principal']) ?></textarea>
  </div>
  <div>
    <label class="text-sm font-medium"><?= $isPsych ? 'Anamnese livre / resumo clínico' : 'Anamnese / histórico relevante' ?></label>
    <textarea name="anamnese" rows="4" class="form-textarea"><?= e($pr['anamnese']) ?></textarea>
  </div>
  <div>
    <label class="text-sm font-medium"><?= $isPsych ? 'Estado mental / observação clínica' : 'Exame físico / clínico' ?></label>
    <textarea name="exame_fisico" rows="3" class="form-textarea"><?= e($pr['exame_fisico']) ?></textarea>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="text-sm font-medium"><?= $isPsych ? 'Hipótese diagnóstica / formulação de caso' : 'Hipótese diagnóstica' ?></label>
      <textarea name="hipotese_diagnostica" rows="3" class="form-textarea"><?= e($pr['hipotese_diagnostica']) ?></textarea>
    </div>
    <div>
      <label class="text-sm font-medium">Conduta / plano</label>
      <textarea name="conduta" rows="3" class="form-textarea"><?= e($pr['conduta']) ?></textarea>
    </div>
  </div>
  <div>
    <label class="text-sm font-medium">Evolução</label>
    <textarea name="evolucao" rows="3" class="form-textarea"><?= e($pr['evolucao']) ?></textarea>
  </div>
  <div>
    <label class="text-sm font-medium">Observações gerais</label>
    <textarea name="observacoes" rows="2" class="form-textarea"><?= e($pr['observacoes']) ?></textarea>
  </div>

  <?php if ($isPsych): ?>
    <?php require __DIR__ . '/_psicologia_form.php'; ?>
  <?php elseif ($isSpecialty): ?>
    <?php $specialtyTheme = $currentTheme; require __DIR__ . '/_specialty_form.php'; ?>
  <?php endif; ?>

  <div class="flex justify-end gap-2">
    <a href="ver.php?id=<?= $id ?>" class="btn bg-slate-200">Cancelar</a>
    <button class="btn btn-primary">Salvar</button>
  </div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
