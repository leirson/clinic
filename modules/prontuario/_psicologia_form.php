<?php
$psiForm = $psiForm ?? psychology_prontuario_defaults();
$psiPlan = $psiPlan ?? psychology_plan_defaults();
$consentTexts = psychology_default_consent_terms();
$psiForm = array_merge($consentTexts, $psiForm);
$tipoRegistroOptions = [
    'anamnese_inicial' => 'Anamnese inicial',
    'sessao' => 'Sessão',
    'retorno' => 'Retorno / reavaliação',
    'encerramento' => 'Encerramento / alta',
];
$riscoOptions = [
    'nao_identificado' => 'Não identificado',
    'baixo' => 'Baixo',
    'moderado' => 'Moderado',
    'alto' => 'Alto',
    'em_monitoramento' => 'Em monitoramento',
];
?>
<div class="rounded-2xl border border-violet-200 bg-gradient-to-r from-violet-50 to-fuchsia-50 p-5 space-y-5">
  <div>
    <h3 class="text-lg font-bold text-violet-900"><i class="bi bi-flower1"></i> Prontuário Psicológico Estruturado</h3>
    <p class="text-sm text-violet-700 mt-1">Campos ampliados para anamnese, evolução clínica, plano terapêutico e termos de consentimento.</p>
  </div>

  <div class="card p-5 bg-white/90">
    <h4 class="font-bold text-slate-800 mb-4"><i class="bi bi-person-vcard text-primary"></i> 1. Anamnese Psicológica</h4>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium">Tipo de registro</label>
        <select name="psi_tipo_registro" class="form-select">
          <?php foreach ($tipoRegistroOptions as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($psiForm['tipo_registro'] ?? 'sessao') === $value ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Encaminhado por</label>
        <input name="psi_encaminhado_por" value="<?= e($psiForm['encaminhado_por']) ?>" class="form-input" placeholder="Família, escola, psiquiatria, demanda espontânea...">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Demanda principal / contexto da procura</label>
        <textarea name="psi_demanda_principal" rows="3" class="form-textarea" placeholder="Contexto da queixa, gatilhos, momento de vida, fatores precipitantes..."><?= e($psiForm['demanda_principal']) ?></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">História da queixa atual</label>
        <textarea name="psi_queixa_historico" rows="3" class="form-textarea" placeholder="Início, frequência, intensidade, prejuízos e estratégias já tentadas..."><?= e($psiForm['queixa_historico']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Motivo do encaminhamento</label>
        <input name="psi_motivo_encaminhamento" value="<?= e($psiForm['motivo_encaminhamento']) ?>" class="form-input" placeholder="Queixa escolar, ansiedade, crise, luto...">
      </div>
      <div>
        <label class="text-sm font-medium">Expectativas sobre o tratamento</label>
        <textarea name="psi_expectativas_tratamento" rows="3" class="form-textarea" placeholder="O que o paciente espera alcançar com o processo terapêutico..."><?= e($psiForm['expectativas_tratamento']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Histórico pessoal</label>
        <textarea name="psi_historico_pessoal" rows="4" class="form-textarea" placeholder="Desenvolvimento, infância, escolaridade, vínculos, rotina, trabalho..."><?= e($psiForm['historico_pessoal']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Histórico familiar</label>
        <textarea name="psi_historico_familiar" rows="4" class="form-textarea" placeholder="Configuração familiar, eventos marcantes, rede de apoio, conflitos..."><?= e($psiForm['historico_familiar']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Histórico psiquiátrico / psicoterápico</label>
        <textarea name="psi_historico_psiquiatrico" rows="4" class="form-textarea" placeholder="Atendimentos prévios, diagnósticos, internações, psicoterapia anterior..."><?= e($psiForm['historico_psiquiatrico']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Histórico de saúde geral</label>
        <textarea name="psi_historico_saude" rows="4" class="form-textarea" placeholder="Doenças clínicas, dor crônica, comorbidades, eventos médicos relevantes..."><?= e($psiForm['historico_saude']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Uso de medicação</label>
        <textarea name="psi_uso_medicacao" rows="3" class="form-textarea" placeholder="Psicotrópicos, prescrição, adesão, efeitos percebidos..."><?= e($psiForm['uso_medicacao']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Uso de substâncias</label>
        <textarea name="psi_uso_substancias" rows="3" class="form-textarea" placeholder="Álcool, tabaco, cannabis, outras substâncias, padrão de uso..."><?= e($psiForm['uso_substancias']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Sono e apetite</label>
        <textarea name="psi_sono_apetite" rows="3" class="form-textarea" placeholder="Insônia, hipersonia, apetite, peso, energia, hábitos..."><?= e($psiForm['sono_apetite']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Rede de apoio / suporte</label>
        <textarea name="psi_suporte_rede" rows="3" class="form-textarea" placeholder="Família, amigos, comunidade, recursos institucionais..."><?= e($psiForm['suporte_rede']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Contexto escolar / profissional</label>
        <textarea name="psi_contexto_escolar_profissional" rows="3" class="form-textarea" placeholder="Desempenho, faltas, sobrecarga, relações, metas acadêmicas/profissionais..."><?= e($psiForm['contexto_escolar_profissional']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Traumas, perdas e eventos marcantes</label>
        <textarea name="psi_antecedentes_trauma_luto" rows="3" class="form-textarea" placeholder="Lutos, violência, separações, acidentes, experiências marcantes..."><?= e($psiForm['antecedentes_trauma_luto']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Fatores de risco</label>
        <textarea name="psi_fatores_risco" rows="4" class="form-textarea" placeholder="Autoagressão, impulsividade, violência, isolamento, vulnerabilidades..."><?= e($psiForm['fatores_risco']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Fatores de proteção</label>
        <textarea name="psi_fatores_protecao" rows="4" class="form-textarea" placeholder="Rede de apoio, motivação, espiritualidade, vínculos, rotina..."><?= e($psiForm['fatores_protecao']) ?></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Objetivo terapêutico inicial</label>
        <textarea name="psi_objetivo_terapeutico" rows="3" class="form-textarea" placeholder="Objetivo clínico pactuado na etapa inicial do acompanhamento..."><?= e($psiForm['objetivo_terapeutico']) ?></textarea>
      </div>
    </div>
  </div>

  <div class="card p-5 bg-white/90">
    <h4 class="font-bold text-slate-800 mb-4"><i class="bi bi-journal-richtext text-primary"></i> 2. Evolução da Sessão e Estado Mental</h4>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium">Aparência / atitude / contato</label>
        <textarea name="psi_estado_mental_aparencia" rows="3" class="form-textarea" placeholder="Apresentação, postura, higiene, contato visual, colaboração..."><?= e($psiForm['estado_mental_aparencia']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Humor e afeto</label>
        <textarea name="psi_humor_afeto" rows="3" class="form-textarea" placeholder="Humor predominante, afeto, congruência, labilidade..."><?= e($psiForm['humor_afeto']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Pensamento e linguagem</label>
        <textarea name="psi_pensamento_linguagem" rows="3" class="form-textarea" placeholder="Curso/forma do pensamento, conteúdo, fala, velocidade, coerência..."><?= e($psiForm['pensamento_linguagem']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Sensopercepção / conteúdo perceptivo</label>
        <textarea name="psi_estado_mental_sensopercepcao" rows="3" class="form-textarea" placeholder="Percepção, desrealização, alucinações, dissociação, alterações sensoperceptivas..."><?= e($psiForm['estado_mental_sensopercepcao']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Comportamento observado</label>
        <textarea name="psi_comportamento" rows="3" class="form-textarea" placeholder="Psicomotricidade, conduta em sessão, expressões, evitamentos, adesão..."><?= e($psiForm['comportamento']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Cognição / orientação / insight</label>
        <textarea name="psi_cognicao_orientacao" rows="3" class="form-textarea" placeholder="Atenção, memória, orientação, juízo crítico, insight, crítica..."><?= e($psiForm['cognicao_orientacao']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Risco suicida</label>
        <select name="psi_risco_suicida" class="form-select">
          <?php foreach ($riscoOptions as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($psiForm['risco_suicida'] ?? 'nao_identificado') === $value ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Risco auto / heteroagressivo e condutas</label>
        <textarea name="psi_risco_autoheteroagressao" rows="3" class="form-textarea" placeholder="Ideação, plano, acesso a meios, fatores atenuantes e condutas de segurança..."><?= e($psiForm['risco_autoheteroagressao']) ?></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Temas centrais trabalhados na sessão</label>
        <textarea name="psi_temas_sessao" rows="3" class="form-textarea" placeholder="Tópicos centrais, conflitos, eventos recentes, narrativas, vínculos..."><?= e($psiForm['temas_sessao']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Intervenções realizadas</label>
        <textarea name="psi_intervencoes_realizadas" rows="4" class="form-textarea" placeholder="Psicoeducação, escuta, interpretação, exposição, manejo emocional..."><?= e($psiForm['intervencoes_realizadas']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Técnicas / recursos utilizados</label>
        <textarea name="psi_tecnicas_utilizadas" rows="4" class="form-textarea" placeholder="TCC, ACT, técnicas expressivas, entrevista motivacional, grounding..."><?= e($psiForm['tecnicas_utilizadas']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Resposta do paciente</label>
        <textarea name="psi_resposta_paciente" rows="4" class="form-textarea" placeholder="Engajamento, compreensão, resistência, percepção de utilidade, afetos..."><?= e($psiForm['resposta_paciente']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Adesão ao processo terapêutico</label>
        <textarea name="psi_adesao_processo" rows="4" class="form-textarea" placeholder="Pontualidade, frequência, aderência a combinados, participação..."><?= e($psiForm['adesao_processo']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Recursos mobilizados / forças do paciente</label>
        <textarea name="psi_recursos_mobilizados" rows="3" class="form-textarea" placeholder="Recursos internos, estratégias adaptativas, apoio externo, coping..."><?= e($psiForm['recursos_mobilizados']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Manejo de crise / plano de segurança</label>
        <textarea name="psi_manejo_crise" rows="3" class="form-textarea" placeholder="Intervenções de segurança, contatos, orientação, acionamentos, contingência..."><?= e($psiForm['manejo_crise']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Tarefas / combinados entre sessões</label>
        <textarea name="psi_tarefas_entre_sessoes" rows="3" class="form-textarea" placeholder="Exercícios, registros, práticas, observações, combinados..."><?= e($psiForm['tarefas_entre_sessoes']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Encaminhamentos / articulações</label>
        <textarea name="psi_encaminhamentos" rows="3" class="form-textarea" placeholder="Psiquiatria, escola, família, rede assistencial, multiprofissional..."><?= e($psiForm['encaminhamentos']) ?></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Plano para a sessão seguinte</label>
        <textarea name="psi_plano_sessao_seguinte" rows="3" class="form-textarea" placeholder="Hipóteses de continuidade, temas a retomar, objetivos da próxima sessão..."><?= e($psiForm['plano_sessao_seguinte']) ?></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Observações confidenciais do profissional</label>
        <textarea name="psi_observacoes_confidenciais" rows="3" class="form-textarea" placeholder="Notas técnicas reservadas, formulação clínica, aspectos do vínculo..."><?= e($psiForm['observacoes_confidenciais']) ?></textarea>
      </div>
    </div>
  </div>

  <div class="card p-5 bg-white/90">
    <div class="flex items-center justify-between gap-3 mb-4">
      <h4 class="font-bold text-slate-800"><i class="bi bi-bullseye text-primary"></i> 3. Plano Terapêutico Vinculado</h4>
      <p class="text-xs text-slate-500">Se preenchido, cria ou atualiza um plano relacionado ao prontuário.</p>
    </div>
    <input type="hidden" name="psi_plano_id" value="<?= (int) ($psiPlan['id'] ?? 0) ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium">Abordagem</label>
        <input name="psi_plano_abordagem" value="<?= e($psiPlan['abordagem']) ?>" class="form-input" placeholder="Ex: TCC, Psicanálise, Sistêmica, Humanista">
      </div>
      <div>
        <label class="text-sm font-medium">Frequência sugerida</label>
        <input name="psi_plano_frequencia_sugerida" value="<?= e($psiPlan['frequencia_sugerida']) ?>" class="form-input" placeholder="Ex: semanal, quinzenal">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Demanda focal do plano</label>
        <textarea name="psi_plano_demanda_focal" rows="3" class="form-textarea" placeholder="Sintomas, contextos ou padrões prioritários para o plano terapêutico..."><?= e($psiPlan['demanda_focal']) ?></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Objetivo principal</label>
        <input name="psi_plano_objetivo_principal" value="<?= e($psiPlan['objetivo_principal']) ?>" class="form-input" placeholder="Objetivo terapêutico principal pactuado com o paciente">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Síntese das metas terapêuticas</label>
        <textarea name="psi_plano_metas" rows="3" class="form-textarea" placeholder="Resumo geral das metas de curto, médio e longo prazo..."><?= e($psiPlan['metas']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Metas de curto prazo</label>
        <textarea name="psi_plano_metas_curto_prazo" rows="3" class="form-textarea" placeholder="Regulação emocional, organização de rotina, estabilização..."><?= e($psiPlan['metas_curto_prazo']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Metas de médio prazo</label>
        <textarea name="psi_plano_metas_medio_prazo" rows="3" class="form-textarea" placeholder="Elaboração de conflitos, desenvolvimento de habilidades, autonomia..."><?= e($psiPlan['metas_medio_prazo']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Metas de longo prazo</label>
        <textarea name="psi_plano_metas_longo_prazo" rows="3" class="form-textarea" placeholder="Manutenção de ganhos, prevenção de recaída, alta, projeto de vida..."><?= e($psiPlan['metas_longo_prazo']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Estratégias / intervenções previstas</label>
        <textarea name="psi_plano_estrategias_intervencao" rows="3" class="form-textarea" placeholder="Psicoeducação, reestruturação cognitiva, trabalho com vínculos, treino de habilidades..."><?= e($psiPlan['estrategias_intervencao']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Indicadores de evolução</label>
        <textarea name="psi_plano_indicadores_evolucao" rows="3" class="form-textarea" placeholder="Assiduidade, redução de sintomas, funcionalidade, escalas, percepção subjetiva..."><?= e($psiPlan['indicadores_evolucao']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Critérios de alta / transição</label>
        <textarea name="psi_plano_criterios_alta" rows="3" class="form-textarea" placeholder="Critérios clínicos para alta, espaçamento ou encaminhamento..."><?= e($psiPlan['criterios_alta']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Riscos / pontos de monitoramento</label>
        <textarea name="psi_plano_risco_monitorado" rows="3" class="form-textarea" placeholder="Crise, recaída, autoagressão, abandono, vulnerabilidades que exigem seguimento..."><?= e($psiPlan['risco_monitorado']) ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Status</label>
        <select name="psi_plano_status" class="form-select">
          <?php foreach (['ativo'=>'Ativo','em_acompanhamento'=>'Em acompanhamento','pausado'=>'Pausado','concluido'=>'Concluído'] as $status => $label): ?>
            <option value="<?= $status ?>" <?= ($psiPlan['status'] ?? 'ativo') === $status ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Data de reavaliação</label>
        <input type="date" name="psi_plano_data_reavaliacao" value="<?= e($psiPlan['data_reavaliacao']) ?>" class="form-input">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Observações do plano</label>
        <textarea name="psi_plano_observacoes" rows="3" class="form-textarea" placeholder="Acordos terapêuticos, necessidade de reavaliação, contatos com rede, observações complementares..."><?= e($psiPlan['observacoes']) ?></textarea>
      </div>
    </div>
  </div>

  <div class="card p-5 bg-white/90">
    <h4 class="font-bold text-slate-800 mb-4"><i class="bi bi-file-earmark-lock2 text-primary"></i> 4. Termos de Consentimento</h4>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
      <div>
        <label class="text-sm font-medium">Tipo de consentimento</label>
        <select name="psi_consentimento_tipo" class="form-select">
          <option value="presencial" <?= ($psiForm['consentimento_tipo'] ?? 'ambos') === 'presencial' ? 'selected' : '' ?>>Atendimento presencial</option>
          <option value="teleatendimento" <?= ($psiForm['consentimento_tipo'] ?? '') === 'teleatendimento' ? 'selected' : '' ?>>Teleatendimento</option>
          <option value="ambos" <?= ($psiForm['consentimento_tipo'] ?? 'ambos') === 'ambos' ? 'selected' : '' ?>>Ambos</option>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Nome do responsável / paciente</label>
        <input name="psi_consentimento_nome_responsavel" value="<?= e($psiForm['consentimento_nome_responsavel']) ?>" class="form-input" placeholder="Nome de quem concordou com os termos">
      </div>
      <div>
        <label class="text-sm font-medium">Testemunha / profissional</label>
        <input name="psi_consentimento_testemunha" value="<?= e($psiForm['consentimento_testemunha']) ?>" class="form-input" placeholder="Nome da testemunha ou registrante">
      </div>
      <div>
        <label class="text-sm font-medium">Documento</label>
        <input name="psi_consentimento_documento" value="<?= e($psiForm['consentimento_documento']) ?>" class="form-input" placeholder="CPF / RG / prontuário">
      </div>
      <div>
        <label class="text-sm font-medium">Parentesco / relação</label>
        <input name="psi_consentimento_parentesco" value="<?= e($psiForm['consentimento_parentesco']) ?>" class="form-input" placeholder="Mãe, pai, responsável legal, próprio paciente...">
      </div>
      <div>
        <label class="text-sm font-medium">Data/hora do registro</label>
        <input type="datetime-local" name="psi_consentimento_registrado_em" value="<?= e(!empty($psiForm['consentimento_registrado_em']) ? date('Y-m-d\TH:i', strtotime($psiForm['consentimento_registrado_em'])) : date('Y-m-d\TH:i')) ?>" class="form-input">
      </div>
    </div>

    <div class="space-y-4">
      <div>
        <label class="text-sm font-medium">Termo de consentimento terapêutico</label>
        <textarea name="psi_consentimento_terapia_texto" rows="4" class="form-textarea"><?= e($psiForm['consentimento_terapia_texto']) ?></textarea>
        <label class="mt-2 inline-flex items-center gap-2 text-sm"><input type="checkbox" name="psi_consentimento_terapia_aceito" value="1" <?= !empty($psiForm['consentimento_terapia_aceito']) ? 'checked' : '' ?>> Termo terapêutico aceito</label>
      </div>
      <div>
        <label class="text-sm font-medium">Termo de consentimento para teleatendimento</label>
        <textarea name="psi_consentimento_tele_texto" rows="4" class="form-textarea"><?= e($psiForm['consentimento_tele_texto']) ?></textarea>
        <label class="mt-2 inline-flex items-center gap-2 text-sm"><input type="checkbox" name="psi_consentimento_tele_aceito" value="1" <?= !empty($psiForm['consentimento_tele_aceito']) ? 'checked' : '' ?>> Termo de teleatendimento aceito</label>
      </div>
      <div>
        <label class="text-sm font-medium">Termo de privacidade e dados sensíveis</label>
        <textarea name="psi_consentimento_privacidade_texto" rows="4" class="form-textarea"><?= e($psiForm['consentimento_privacidade_texto']) ?></textarea>
        <label class="mt-2 inline-flex items-center gap-2 text-sm"><input type="checkbox" name="psi_consentimento_privacidade_aceito" value="1" <?= !empty($psiForm['consentimento_privacidade_aceito']) ? 'checked' : '' ?>> Termo de privacidade aceito</label>
      </div>
      <div>
        <label class="text-sm font-medium">Autorização de compartilhamento com rede / responsáveis</label>
        <textarea name="psi_consentimento_compartilhamento_texto" rows="4" class="form-textarea"><?= e($psiForm['consentimento_compartilhamento_texto']) ?></textarea>
        <label class="mt-2 inline-flex items-center gap-2 text-sm"><input type="checkbox" name="psi_consentimento_compartilhamento_aceito" value="1" <?= !empty($psiForm['consentimento_compartilhamento_aceito']) ? 'checked' : '' ?>> Compartilhamento autorizado</label>
      </div>
      <div>
        <label class="text-sm font-medium">Autorização específica para gravação</label>
        <textarea name="psi_consentimento_gravacao_texto" rows="4" class="form-textarea"><?= e($psiForm['consentimento_gravacao_texto']) ?></textarea>
        <label class="mt-2 inline-flex items-center gap-2 text-sm"><input type="checkbox" name="psi_consentimento_gravacao_aceito" value="1" <?= !empty($psiForm['consentimento_gravacao_aceito']) ? 'checked' : '' ?>> Gravação autorizada</label>
      </div>
    </div>
  </div>
</div>
