-- ClinicApp: modelos pré-prontos para TODAS as especialidades do catálogo.
-- 10 especialidades x 3 modelos = 30 modelos por empresa existente.
-- Pré-requisitos: empresas e modelos_clinicos (database.sql ou upgrade_v9).
-- Abrangência: TODAS as empresas já cadastradas; nenhuma empresa é criada.
-- Reexecução sequencial não duplica empresa + tema + título.
-- Não altera modelos existentes, não cria pacientes, não executa DELETE/UPDATE.
-- Safe Updates permanece inalterado. Execute em uma única conexão.
-- Faça backup antes da importação; não execute simultaneamente em duas sessões.
-- Modelos são rascunhos: revisar e preencher conforme avaliação profissional.
-- Não importar automaticamente em produção nem tratar como protocolos validados.

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO modelos_clinicos (
  empresa_id,
  tema,
  tipo_documento,
  titulo,
  conteudo,
  campos_chave,
  tags,
  ativo,
  criado_por,
  atualizado_por
)
SELECT
  e.id,
  seed.tema,
  seed.tipo_documento,
  seed.titulo,
  seed.conteudo,
  seed.campos_chave,
  seed.tags,
  1,
  NULL,
  NULL
FROM empresas e
INNER JOIN (
  SELECT 'psiquiatria' AS tema, 'anamnese' AS tipo_documento, 'Anamnese psiquiátrica inicial' AS titulo,
         'Paciente em primeira avaliação psiquiátrica, relatando [queixa principal], com início em [período] e impacto em [sono, apetite, trabalho, vínculos]. Antecedentes psiquiátricos: [histórico]. Uso de substâncias: [descrição]. Exame do estado mental com [humor, afeto, pensamento, percepção, insight]. Hipóteses diagnósticas iniciais: [hipóteses]. Plano terapêutico inicial: [conduta].' AS conteudo,
         'queixa principal, início dos sintomas, histórico psiquiátrico, exame mental, hipótese diagnóstica, plano terapêutico' AS campos_chave,
         'psiquiatria, anamnese, consulta inicial, saúde mental' AS tags
  UNION ALL
  SELECT 'psiquiatria', 'evolucao', 'Evolução psiquiátrica ambulatorial',
         'Paciente retorna em acompanhamento, referindo [evolução dos sintomas], adesão [boa, parcial ou baixa] ao tratamento e presença de [efeitos colaterais ou benefícios]. No exame psíquico atual apresenta [humor, afeto, pensamento, percepção e insight]. Avaliado risco atual de autoagressão e heteroagressão como [descrição]. Mantida ou ajustada conduta com [medicações, psicoterapia, encaminhamentos] e retorno em [prazo].',
         'evolução dos sintomas, adesão terapêutica, exame psíquico, avaliação de risco, conduta, retorno',
         'psiquiatria, evolução, retorno, estabilização'
  UNION ALL
  SELECT 'psiquiatria', 'orientacao', 'Orientações de segurança e adesão em psiquiatria',
         'Orientado paciente e rede de apoio quanto ao uso correto das medicações, risco de interrupção sem supervisão, higiene do sono, redução de álcool e outras substâncias e sinais de alerta para retorno imediato, incluindo piora importante do humor, ideação suicida, agitação intensa, insônia grave ou sintomas psicóticos.',
         'medicação, rede de apoio, higiene do sono, substâncias, sinais de alerta',
         'psiquiatria, orientação, segurança, adesão'

  UNION ALL
  SELECT 'pediatria', 'anamnese', 'Anamnese pediátrica para queixa aguda',
         'Paciente pediátrico acompanhado por [responsável], com queixa principal de [queixa], iniciada há [tempo]. Associado a [febre, tosse, vômitos, diarreia, irritabilidade ou outros sintomas]. Alimentação e hidratação em [estado]. Diurese e evacuações [descrição]. Vacinação [em dia, incompleta ou pendente]. Antecedentes relevantes: [alergias, prematuridade, doenças prévias, medicações]. Exame físico direcionado com [achados]. Conduta inicial: [conduta].',
         'responsável, queixa aguda, febre, alimentação, hidratação, vacinação, exame físico, conduta',
         'pediatria, anamnese, urgência leve, consulta infantil'
  UNION ALL
  SELECT 'pediatria', 'evolucao', 'Evolução de puericultura e desenvolvimento',
         'Criança em consulta de seguimento, com crescimento em [faixa esperada ou observação], desenvolvimento neuropsicomotor [adequado ou descrição], alimentação [descrição], sono [descrição] e vacinação [situação]. Exame físico sem ou com [achados]. Reforçadas orientações sobre prevenção, rotina, estímulos e retorno em [prazo].',
         'crescimento, desenvolvimento, alimentação, sono, vacinação, exame físico, retorno',
         'pediatria, evolução, puericultura, acompanhamento'
  UNION ALL
  SELECT 'pediatria', 'orientacao', 'Orientações ao responsável e sinais de alarme pediátricos',
         'Responsável orientado quanto à hidratação frequente, controle de temperatura conforme peso e idade, uso correto das medicações prescritas, manutenção da alimentação conforme tolerância e observação de sinais de alarme. Retornar imediatamente em caso de prostração, dificuldade respiratória, recusa hídrica persistente, convulsão, sonolência excessiva, piora clínica ou febre prolongada.',
         'hidratação, antitérmico, alimentação, dificuldade respiratória, sinais de alarme',
         'pediatria, orientação, responsável, sinais de alarme'

  UNION ALL
  SELECT 'ginecologia_obstetricia', 'anamnese', 'Anamnese ginecológica ou obstétrica inicial',
         'Paciente em atendimento de [ginecologia, obstetrícia, pré-natal ou puerpério], com queixa principal de [queixa]. História gineco-obstétrica: DUM [data], G/P/A [descrição], método contraceptivo [descrição], ciclos menstruais [descrição]. Relata [sangramento, corrimento, dor pélvica, movimentação fetal ou outros sintomas]. Antecedentes pessoais e cirúrgicos: [histórico]. Exame físico ou ginecológico com [achados]. Hipótese diagnóstica inicial e plano: [conduta].',
         'tipo de atendimento, DUM, GPA, queixa, sangramento, dor pélvica, exame físico, conduta',
         'ginecologia, obstetrícia, anamnese, pré-natal'
  UNION ALL
  SELECT 'ginecologia_obstetricia', 'evolucao', 'Evolução de retorno ginecológico ou pré-natal',
         'Paciente em seguimento, relatando [evolução clínica]. Exames recentes: [descrição]. Mantém ou não [sangramento, corrimento, dor, contrações, movimentação fetal]. Ao exame, apresenta [achados]. Mantidas ou ajustadas orientações, terapêutica e calendário de exames. Próximo retorno programado para [prazo].',
         'evolução clínica, exames recentes, sintomas, exame, terapêutica, calendário de exames, retorno',
         'ginecologia, obstetrícia, evolução, retorno'
  UNION ALL
  SELECT 'ginecologia_obstetricia', 'orientacao', 'Orientações e sinais de alerta em ginecologia e obstetrícia',
         'Orientada quanto a uso correto da medicação prescrita, adesão aos exames solicitados, hábitos de vida saudáveis, sinais de alerta e necessidade de retorno precoce. Procurar assistência imediatamente em caso de sangramento importante, perda de líquido, febre, dor intensa, diminuição de movimentos fetais, cefaleia forte, escotomas, piora importante do quadro ou mal-estar persistente.',
         'medicação, exames, hábitos saudáveis, sangramento, perda de líquido, movimentos fetais, sinais de alerta',
         'ginecologia, obstetrícia, orientação, sinais de alerta'

  UNION ALL
  SELECT 'ortopedia_traumatologia', 'anamnese', 'Anamnese ortopédica para dor ou trauma',
         'Paciente com queixa principal de [dor ou limitação], localizada em [região anatômica], com lateralidade [direita, esquerda, bilateral]. Início há [tempo], com mecanismo de trauma ou sobrecarga [descrição]. Dor em escala [0 a 10], piora com [fatores] e melhora com [fatores]. Refere limitação funcional em [atividade]. Exame direcionado com [inspeção, amplitude, força, testes especiais, estado neurovascular]. Hipótese inicial e conduta: [conduta].',
         'região anatômica, lateralidade, mecanismo de trauma, escala de dor, limitação funcional, exame físico, conduta',
         'ortopedia, traumatologia, anamnese, dor musculoesquelética'
  UNION ALL
  SELECT 'ortopedia_traumatologia', 'evolucao', 'Evolução ortopédica com reavaliação funcional',
         'Paciente em seguimento por [lesão ou dor], relatando evolução [melhora, estabilidade ou piora]. Mantém dor em intensidade [escala], limitação em [atividade] e adesão [descrição] ao tratamento. Exame atual demonstra [amplitude de movimento, edema, força, testes]. Exames complementares: [descrição]. Mantida ou ajustada conduta com [analgesia, imobilização, fisioterapia, infiltração, encaminhamento] e retorno em [prazo].',
         'evolução da dor, limitação, adesão ao tratamento, exame atual, exames complementares, conduta',
         'ortopedia, evolução, reavaliação, funcionalidade'
  UNION ALL
  SELECT 'ortopedia_traumatologia', 'orientacao', 'Orientações de proteção articular e retorno ortopédico',
         'Orientado quanto a repouso relativo, uso correto de órtese ou imobilização quando indicada, analgesia conforme prescrição, gelo ou calor conforme fase clínica, progressão segura das atividades e início ou continuidade de reabilitação. Retornar imediatamente em caso de deformidade, piora importante da dor, febre, perda de força, alteração neurovascular distal ou incapacidade funcional acentuada.',
         'repouso relativo, imobilização, analgesia, reabilitação, perda de força, sinais de alarme',
         'ortopedia, orientação, reabilitação, proteção articular'

  UNION ALL
  SELECT 'cardiologia', 'anamnese', 'Anamnese cardiológica inicial',
         'Paciente em avaliação cardiológica por [dor torácica, dispneia, palpitações, síncope, hipertensão ou outro motivo]. Fatores de risco: [hipertensão, diabetes, dislipidemia, tabagismo, obesidade, história familiar]. Sintomas iniciados há [tempo], com impacto em [esforço, repouso, sono]. Medicações em uso: [descrição]. Exame físico cardiovascular com [achados], PA [valor], FC [valor]. Exames prévios: [ECG, ECO, MAPA, Holter, laboratoriais]. Hipótese diagnóstica e plano: [conduta].',
         'motivo da consulta, fatores de risco, medicações, exame físico, PA, FC, exames prévios, conduta',
         'cardiologia, anamnese, risco cardiovascular, consulta inicial'
  UNION ALL
  SELECT 'cardiologia', 'evolucao', 'Evolução de seguimento cardiológico',
         'Paciente retorna em seguimento por [diagnóstico], com sintomas atuais [descrição]. Refere adesão [boa, parcial ou baixa] às medicações e medidas não farmacológicas. Traz controle domiciliar de pressão ou frequência com [resultados]. Exames recentes mostram [achados]. Mantidas ou ajustadas medicações, metas terapêuticas e plano de reavaliação em [prazo].',
         'diagnóstico, sintomas atuais, adesão, controle pressórico, exames recentes, metas terapêuticas, retorno',
         'cardiologia, evolução, hipertensão, seguimento'
  UNION ALL
  SELECT 'cardiologia', 'orientacao', 'Orientações de controle cardiovascular domiciliar',
         'Orientado quanto ao uso regular das medicações, redução de sal, controle de peso, prática de atividade física conforme liberação clínica, cessação do tabagismo e monitorização domiciliar de pressão arterial quando indicada. Retornar com exames e diário pressórico. Procurar urgência em caso de dor torácica persistente, dispneia importante, síncope, palpitações intensas ou piora súbita do estado geral.',
         'medicação regular, redução de sal, peso, atividade física, diário pressórico, dor torácica, dispneia',
         'cardiologia, orientação, hipertensão, prevenção'

  UNION ALL
  SELECT 'oftalmologia', 'anamnese', 'Anamnese oftalmológica inicial',
         'Paciente com queixa visual principal de [baixa visual, olho vermelho, ardor, secreção, trauma ou outro], acometendo [OD, OE ou OU], iniciada há [tempo]. Relata ou nega [dor ocular, fotofobia, trauma, visão borrada, halos]. Antecedentes oftalmológicos: [cirurgias, uso de óculos, colírios, doenças prévias]. Exame inicial com [acuidade visual, biomicroscopia, tonometria, fundoscopia ou outros achados]. Hipótese e conduta: [conduta].',
         'queixa visual, lateralidade, dor ocular, fotofobia, trauma, antecedentes, acuidade visual, conduta',
         'oftalmologia, anamnese, baixa visual, consulta inicial'
  UNION ALL
  SELECT 'oftalmologia', 'evolucao', 'Evolução oftalmológica ambulatorial',
         'Paciente em retorno por [diagnóstico ou queixa], relatando evolução [descrição] após uso de [tratamento]. Acuidade visual atual [descrição]. Exame ocular com [achados]. Exames complementares ou refração: [descrição]. Mantida ou ajustada conduta com [lubrificação, antibiótico, correção óptica, investigação adicional] e retorno em [prazo].',
         'diagnóstico, tratamento, acuidade visual, exame ocular, refração, conduta, retorno',
         'oftalmologia, evolução, retorno, seguimento'
  UNION ALL
  SELECT 'oftalmologia', 'orientacao', 'Orientações oftalmológicas e sinais de urgência',
         'Orientado quanto ao uso correto dos colírios, higiene ocular, evitar automedicação e manipulação excessiva dos olhos, além de respeitar o retorno programado. Procurar avaliação urgente em caso de perda visual súbita, dor intensa, trauma ocular, secreção importante, fotofobia acentuada, piora rápida dos sintomas ou suspeita de corpo estranho.',
         'colírio, higiene ocular, automedicação, perda visual súbita, dor intensa, trauma ocular',
         'oftalmologia, orientação, colírio, urgência'

  UNION ALL
  SELECT 'neurologia', 'anamnese', 'Anamnese neurológica inicial',
         'Paciente em avaliação neurológica por [cefaleia, tontura, parestesia, crise, tremor, déficit motor, alteração de memória ou outro sintoma]. Início há [tempo], frequência [descrição] e fatores desencadeantes [descrição]. Sintomas associados: [náuseas, aura, alteração visual, fraqueza, dormência, alteração da fala]. Antecedentes relevantes e medicações em uso: [descrição]. Exame neurológico com [achados]. Hipótese diagnóstica inicial e plano de investigação ou tratamento: [conduta].',
         'sintoma neurológico, início, frequência, gatilhos, sintomas associados, exame neurológico, conduta',
         'neurologia, anamnese, cefaleia, consulta inicial'
  UNION ALL
  SELECT 'neurologia', 'evolucao', 'Evolução neurológica de seguimento',
         'Paciente retorna em seguimento por [diagnóstico ou síndrome], referindo evolução [melhora, estabilidade ou piora] dos sintomas. Mantém episódios de [cefaleia, crise, tontura, parestesia ou outro] com frequência [descrição]. Exames recentes: [RM, TC, EEG, ENMG ou outros]. Exame neurológico atual com [achados]. Mantido ou ajustado plano terapêutico, orientações e reavaliação em [prazo].',
         'diagnóstico, evolução dos sintomas, frequência, exames recentes, exame neurológico, plano terapêutico',
         'neurologia, evolução, seguimento, retorno'
  UNION ALL
  SELECT 'neurologia', 'orientacao', 'Orientações neurológicas e sinais de alarme',
         'Orientado quanto ao uso correto das medicações, manutenção de diário de sintomas quando aplicável, cuidados com sono, hidratação e gatilhos individuais. Procurar atendimento imediato em caso de déficit neurológico súbito, alteração de fala, convulsão prolongada, cefaleia explosiva, rebaixamento do nível de consciência, febre associada a rigidez de nuca ou piora abrupta do quadro.',
         'medicação, diário de sintomas, sono, hidratação, déficit neurológico súbito, cefaleia explosiva',
         'neurologia, orientação, sinais de alarme, crise'
  UNION ALL
  SELECT 'medico', 'anamnese', 'Anamnese clínica geral inicial', 'Queixa principal: [descrição]. História da doença atual: [início, duração, evolução e sintomas associados]. Antecedentes pessoais e familiares: [descrição]. Alergias e medicamentos em uso: [descrição]. Hábitos e contexto social: [descrição]. Sinais vitais e exame físico: [achados]. Hipóteses diagnósticas: [avaliação]. Plano individualizado: [investigação, conduta e retorno].', 'queixa, história atual, antecedentes, alergias, medicamentos, exame físico, avaliação, plano', 'clínica médica, anamnese, atendimento inicial'
  UNION ALL
  SELECT 'medico', 'evolucao', 'Evolução clínica geral de seguimento', 'Motivo do acompanhamento: [descrição]. Evolução desde a última consulta: [relato]. Adesão e efeitos adversos: [avaliação]. Exame físico e resultados disponíveis: [achados]. Avaliação dos problemas ativos: [descrição]. Conduta discutida: [plano]. Orientações e retorno: [registro].', 'evolução, problemas ativos, adesão, exame físico, resultados, conduta, retorno', 'clínica médica, evolução, acompanhamento'
  UNION ALL
  SELECT 'medico', 'orientacao', 'Orientações clínicas gerais individualizadas', 'Plano acordado com o paciente: [descrição]. Registrar orientações efetivamente fornecidas sobre [uso de medicamentos prescritos, autocuidado e acompanhamento]. Sinais de alerta específicos do caso e local de atendimento: [descrição]. Exames e retorno: [prazos]. Dúvidas e compreensão do paciente: [registro].', 'plano, autocuidado, medicamentos, sinais de alerta, retorno, compreensão', 'clínica médica, orientação, autocuidado'
  UNION ALL
  SELECT 'dentista', 'anamnese', 'Anamnese odontológica inicial', 'Queixa odontológica: [descrição]. História da dor ou desconforto: [localização, duração e fatores associados]. Antecedentes odontológicos e clínicos: [descrição]. Alergias, medicamentos e condições relevantes: [registro]. Exame extraoral e intraoral: [achados]. Dentes e superfícies envolvidos: [notação FDI]. Avaliação e plano proposto: [descrição].', 'queixa, dor, antecedentes, alergias, exame intraoral, dentes, avaliação, plano', 'odontologia, anamnese, avaliação inicial'
  UNION ALL
  SELECT 'dentista', 'evolucao', 'Evolução odontológica de tratamento', 'Procedimento ou acompanhamento: [descrição]. Dente, região e superfície: [notação FDI]. Achados e evolução: [registro]. Procedimento efetivamente realizado, materiais e anestesia quando aplicável: [detalhes]. Intercorrências: [descrição]. Orientações fornecidas e próxima etapa: [registro].', 'procedimento, dente, região, materiais, anestesia, intercorrências, retorno', 'odontologia, evolução, plano de tratamento'
  UNION ALL
  SELECT 'dentista', 'orientacao', 'Orientações odontológicas individualizadas', 'Atendimento ou procedimento realizado: [descrição]. Orientações individualizadas de higiene oral, alimentação e cuidados locais: [registro]. Medicamentos apenas conforme prescrição emitida: [registro]. Sinais de alerta e canal para intercorrências: [descrição]. Revisão e continuidade do tratamento: [prazo e plano].', 'procedimento, higiene oral, cuidados locais, prescrição, sinais de alerta, revisão', 'odontologia, orientação, acompanhamento'
  UNION ALL
  SELECT 'psicologia', 'anamnese', 'Entrevista psicológica inicial', 'Demanda e objetivos relatados: [descrição]. História e contexto da demanda: [registro]. Contexto familiar, social e ocupacional relevante: [descrição]. Acompanhamentos anteriores e atuais: [registro]. Observações clínicas: [descrição]. Avaliação de riscos e fatores de proteção, quando pertinente: [registro]. Contrato terapêutico, limites de sigilo e plano inicial: [registro].', 'demanda, contexto, histórico, observações, riscos, fatores de proteção, plano', 'psicologia, entrevista inicial, acolhimento'
  UNION ALL
  SELECT 'psicologia', 'evolucao', 'Evolução de sessão psicológica', 'Objetivos da sessão: [descrição]. Conteúdos clinicamente relevantes e observações: [registro necessário ao acompanhamento]. Intervenções efetivamente realizadas: [descrição]. Resposta e evolução observadas: [registro]. Riscos e proteção, se pertinente: [avaliação]. Plano terapêutico e próximos passos: [descrição]. Registrar somente informações necessárias ao cuidado.', 'objetivos, observações, intervenções, evolução, plano terapêutico', 'psicologia, evolução, sessão'
  UNION ALL
  SELECT 'psicologia', 'orientacao', 'Orientações e continuidade do cuidado psicológico', 'Orientações discutidas na sessão: [descrição]. Estratégias acordadas e atividades, quando pertinentes: [registro]. Rede de apoio e encaminhamentos pactuados: [descrição]. Plano de segurança, se indicado pela avaliação: [registro]. Em situação de risco imediato, buscar serviço de urgência. Continuidade do acompanhamento: [prazo].', 'orientações, estratégias, rede de apoio, encaminhamentos, segurança, retorno', 'psicologia, orientação, continuidade do cuidado'
) AS seed
LEFT JOIN modelos_clinicos mc
  ON mc.empresa_id = e.id
 AND mc.tema = seed.tema
 AND mc.titulo = seed.titulo
WHERE mc.id IS NULL;

COMMIT;
