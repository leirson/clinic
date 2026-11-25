SET NAMES utf8mb4;
-- =====================================================
-- ClinicApp - Sistema de Gestão para Dentistas, Médicos e Psicologia
-- Banco de Dados MySQL - Hostinger Compatible
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- =====================================================
-- TABELA: empresas (dados do prestador/clínica)
-- =====================================================
CREATE TABLE IF NOT EXISTS `empresas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `razao_social` VARCHAR(200) NOT NULL,
  `nome_fantasia` VARCHAR(200) DEFAULT NULL,
  `cnpj` VARCHAR(20) NOT NULL,
  `inscricao_municipal` VARCHAR(30) DEFAULT NULL,
  `inscricao_estadual` VARCHAR(30) DEFAULT NULL,
  `cnae` VARCHAR(20) DEFAULT NULL,
  `item_lista_servico` VARCHAR(10) DEFAULT NULL,
  `codigo_tributacao_municipio` VARCHAR(20) DEFAULT NULL,
  `regime_tributario` ENUM('1','2','3','4','5','6') DEFAULT '1' COMMENT '1-SimplesNacional 2-SimplesExcessoReceita 3-SociedadeProfissionais 4-Cooperativa 5-MEI 6-Normal',
  `optante_simples` TINYINT(1) DEFAULT 1,
  `incentivador_cultural` TINYINT(1) DEFAULT 0,
  `aliquota_iss` DECIMAL(5,2) DEFAULT 0.00,
  `endereco_cep` VARCHAR(10) DEFAULT NULL,
  `endereco_logradouro` VARCHAR(200) DEFAULT NULL,
  `endereco_numero` VARCHAR(20) DEFAULT NULL,
  `endereco_complemento` VARCHAR(100) DEFAULT NULL,
  `endereco_bairro` VARCHAR(100) DEFAULT NULL,
  `endereco_cidade` VARCHAR(100) DEFAULT NULL,
  `endereco_uf` VARCHAR(2) DEFAULT NULL,
  `endereco_codigo_municipio` VARCHAR(10) DEFAULT NULL COMMENT 'Código IBGE',
  `telefone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `tema_padrao` ENUM('dentista','medico','psicologia','psiquiatria','pediatria','ginecologia_obstetricia','ortopedia_traumatologia','cardiologia','oftalmologia','neurologia') DEFAULT 'medico',
  `logo` VARCHAR(255) DEFAULT NULL,
  -- Credenciais Asten
  `asten_ambiente` ENUM('homologacao','producao') DEFAULT 'homologacao',
  `asten_token` TEXT DEFAULT NULL,
  `asten_usuario` VARCHAR(150) DEFAULT NULL,
  `asten_senha` VARCHAR(255) DEFAULT NULL,
  `asten_url_ws` VARCHAR(255) DEFAULT NULL,
  `asten_serie_rps` VARCHAR(10) DEFAULT '1',
  `asten_proximo_rps` INT(11) DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cnpj` (`cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: usuarios (login/profissionais/recepção)
-- =====================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `nome` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `perfil` ENUM('admin','profissional','recepcao') DEFAULT 'profissional',
  `especialidade` VARCHAR(100) DEFAULT NULL,
  `registro_profissional` VARCHAR(50) DEFAULT NULL COMMENT 'CRM/CRO/CRP',
  `telefone` VARCHAR(20) DEFAULT NULL,
  `percentual_repasse` DECIMAL(5,2) DEFAULT 0.00,
  `tema_preferido` ENUM('dentista','medico','psicologia','psiquiatria','pediatria','ginecologia_obstetricia','ortopedia_traumatologia','cardiologia','oftalmologia','neurologia') DEFAULT 'medico',
  `cor_tema` VARCHAR(20) DEFAULT 'blue',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `ativo` TINYINT(1) DEFAULT 1,
  `ultimo_login` DATETIME DEFAULT NULL,
  `token_reset` VARCHAR(100) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `fk_usuario_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: pacientes (clientes / tomador NFSe)
-- =====================================================
CREATE TABLE IF NOT EXISTS `pacientes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `nome` VARCHAR(200) NOT NULL,
  `tipo_pessoa` ENUM('F','J') DEFAULT 'F',
  `cpf_cnpj` VARCHAR(20) DEFAULT NULL,
  `rg` VARCHAR(20) DEFAULT NULL,
  `data_nascimento` DATE DEFAULT NULL,
  `sexo` ENUM('M','F','O') DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `telefone` VARCHAR(20) DEFAULT NULL,
  `celular` VARCHAR(20) DEFAULT NULL,
  `profissao` VARCHAR(100) DEFAULT NULL,
  `estado_civil` VARCHAR(30) DEFAULT NULL,
  `convenio` VARCHAR(100) DEFAULT NULL,
  `numero_carteirinha` VARCHAR(50) DEFAULT NULL,
  -- Endereço (necessário NFSe)
  `cep` VARCHAR(10) DEFAULT NULL,
  `logradouro` VARCHAR(200) DEFAULT NULL,
  `numero` VARCHAR(20) DEFAULT NULL,
  `complemento` VARCHAR(100) DEFAULT NULL,
  `bairro` VARCHAR(100) DEFAULT NULL,
  `cidade` VARCHAR(100) DEFAULT NULL,
  `uf` VARCHAR(2) DEFAULT NULL,
  `codigo_municipio` VARCHAR(10) DEFAULT NULL,
  -- Dados clínicos
  `tipo_sanguineo` VARCHAR(5) DEFAULT NULL,
  `alergias` TEXT DEFAULT NULL,
  `medicamentos_uso` TEXT DEFAULT NULL,
  `historico_familiar` TEXT DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `ativo` TINYINT(1) DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nome` (`nome`),
  KEY `idx_cpf` (`cpf_cnpj`),
  KEY `fk_pac_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: procedimentos / serviços oferecidos
-- =====================================================
CREATE TABLE IF NOT EXISTS `procedimentos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `nome` VARCHAR(200) NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  `valor` DECIMAL(10,2) DEFAULT 0.00,
  `duracao_minutos` INT(11) DEFAULT 30,
  `codigo_servico` VARCHAR(20) DEFAULT NULL COMMENT 'Item lista servico LC 116',
  `categoria` ENUM('clinico','odontologico','exame','procedimento','outros') DEFAULT 'clinico',
  `ativo` TINYINT(1) DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_proc_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: agendamentos
-- =====================================================
CREATE TABLE IF NOT EXISTS `agendamentos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `procedimento_id` INT(11) DEFAULT NULL,
  `data_hora_inicio` DATETIME NOT NULL,
  `data_hora_fim` DATETIME NOT NULL,
  `status` ENUM('agendado','confirmado','realizado','faltou','cancelado') DEFAULT 'agendado',
  `observacoes` TEXT DEFAULT NULL,
  `valor` DECIMAL(10,2) DEFAULT 0.00,
  `lembrete_email_enviado` TINYINT(1) DEFAULT 0,
  `confirmacao_status` ENUM('pendente','confirmado','recusado','reagendar') DEFAULT 'pendente',
  `confirmado_em` DATETIME DEFAULT NULL,
  `ultimo_lembrete_em` DATETIME DEFAULT NULL,
  `cancelado_em` DATETIME DEFAULT NULL,
  `cancelado_motivo` VARCHAR(255) DEFAULT NULL,
  `encaixe_origem_fila_id` INT(11) DEFAULT NULL,
  `criado_por` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_data` (`data_hora_inicio`),
  KEY `fk_ag_paciente` (`paciente_id`),
  KEY `fk_ag_prof` (`profissional_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: bloqueios de horário / feriados
-- =====================================================
CREATE TABLE IF NOT EXISTS `bloqueios_horario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `profissional_id` INT(11) DEFAULT NULL COMMENT 'NULL = todos profissionais',
  `data_inicio` DATETIME NOT NULL,
  `data_fim` DATETIME NOT NULL,
  `motivo` VARCHAR(200) DEFAULT NULL,
  `tipo` ENUM('feriado','almoco','folga','outro') DEFAULT 'outro',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bloq_data` (`data_inicio`,`data_fim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: prontuários (evoluções clínicas)
-- =====================================================
CREATE TABLE IF NOT EXISTS `prontuarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `agendamento_id` INT(11) DEFAULT NULL,
  `data_atendimento` DATETIME NOT NULL,
  `anamnese` TEXT DEFAULT NULL,
  `queixa_principal` TEXT DEFAULT NULL,
  `exame_fisico` TEXT DEFAULT NULL,
  `hipotese_diagnostica` TEXT DEFAULT NULL,
  `conduta` TEXT DEFAULT NULL,
  `evolucao` TEXT DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pront_paciente` (`paciente_id`),
  KEY `idx_pront_data` (`data_atendimento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: odontograma (específico dentista)
-- =====================================================
CREATE TABLE IF NOT EXISTS `odontograma` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` INT(11) NOT NULL,
  `dente` VARCHAR(5) NOT NULL COMMENT 'Notação FDI: 11-48',
  `face` ENUM('vestibular','lingual','mesial','distal','oclusal','incisal','geral') DEFAULT 'geral',
  `condicao` VARCHAR(50) DEFAULT NULL COMMENT 'carie, restauracao, ausente, etc',
  `cor` VARCHAR(20) DEFAULT NULL,
  `observacao` TEXT DEFAULT NULL,
  `data_registro` DATE DEFAULT NULL,
  `profissional_id` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_odonto_paciente` (`paciente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: receitas/prescrições
-- =====================================================
CREATE TABLE IF NOT EXISTS `receitas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `prontuario_id` INT(11) DEFAULT NULL,
  `tipo` ENUM('simples','controlada','atestado') DEFAULT 'simples',
  `conteudo` TEXT NOT NULL,
  `data_emissao` DATE NOT NULL,
  `dias_afastamento` INT(11) DEFAULT NULL COMMENT 'Para atestados',
  `cid` VARCHAR(10) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rec_paciente` (`paciente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: anexos (exames, raio-x, fotos)
-- =====================================================
CREATE TABLE IF NOT EXISTS `anexos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` INT(11) NOT NULL,
  `prontuario_id` INT(11) DEFAULT NULL,
  `tipo` ENUM('exame','raio_x','foto','documento','outro') DEFAULT 'outro',
  `titulo` VARCHAR(200) DEFAULT NULL,
  `descricao` TEXT DEFAULT NULL,
  `arquivo` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `tamanho` INT(11) DEFAULT NULL,
  `criado_por` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_anex_paciente` (`paciente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: psicologia_prontuario (prontuário psicológico estruturado)
-- =====================================================
CREATE TABLE IF NOT EXISTS `psicologia_prontuario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `prontuario_id` INT(11) NOT NULL,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `tipo_registro` ENUM('anamnese_inicial','sessao','retorno','encerramento') DEFAULT 'sessao',
  `demanda_principal` TEXT DEFAULT NULL,
  `historico_pessoal` TEXT DEFAULT NULL,
  `historico_familiar` TEXT DEFAULT NULL,
  `historico_psiquiatrico` TEXT DEFAULT NULL,
  `uso_medicacao` TEXT DEFAULT NULL,
  `fatores_risco` TEXT DEFAULT NULL,
  `fatores_protecao` TEXT DEFAULT NULL,
  `objetivo_terapeutico` TEXT DEFAULT NULL,
  `queixa_historico` TEXT DEFAULT NULL,
  `expectativas_tratamento` TEXT DEFAULT NULL,
  `encaminhado_por` VARCHAR(150) DEFAULT NULL,
  `motivo_encaminhamento` VARCHAR(255) DEFAULT NULL,
  `historico_saude` TEXT DEFAULT NULL,
  `uso_substancias` TEXT DEFAULT NULL,
  `sono_apetite` TEXT DEFAULT NULL,
  `suporte_rede` TEXT DEFAULT NULL,
  `contexto_escolar_profissional` TEXT DEFAULT NULL,
  `antecedentes_trauma_luto` TEXT DEFAULT NULL,
  `humor_afeto` TEXT DEFAULT NULL,
  `pensamento_linguagem` TEXT DEFAULT NULL,
  `comportamento` TEXT DEFAULT NULL,
  `cognicao_orientacao` TEXT DEFAULT NULL,
  `estado_mental_aparencia` TEXT DEFAULT NULL,
  `estado_mental_sensopercepcao` TEXT DEFAULT NULL,
  `risco_suicida` ENUM('nao_identificado','baixo','moderado','alto','em_monitoramento') DEFAULT 'nao_identificado',
  `risco_autoheteroagressao` TEXT DEFAULT NULL,
  `temas_sessao` TEXT DEFAULT NULL,
  `intervencoes_realizadas` TEXT DEFAULT NULL,
  `tecnicas_utilizadas` TEXT DEFAULT NULL,
  `resposta_paciente` TEXT DEFAULT NULL,
  `adesao_processo` TEXT DEFAULT NULL,
  `recursos_mobilizados` TEXT DEFAULT NULL,
  `manejo_crise` TEXT DEFAULT NULL,
  `tarefas_entre_sessoes` TEXT DEFAULT NULL,
  `encaminhamentos` TEXT DEFAULT NULL,
  `plano_sessao_seguinte` TEXT DEFAULT NULL,
  `observacoes_confidenciais` TEXT DEFAULT NULL,
  `consentimento_tipo` ENUM('presencial','teleatendimento','ambos') DEFAULT 'ambos',
  `consentimento_terapia_texto` TEXT DEFAULT NULL,
  `consentimento_tele_texto` TEXT DEFAULT NULL,
  `consentimento_privacidade_texto` TEXT DEFAULT NULL,
  `consentimento_compartilhamento_texto` TEXT DEFAULT NULL,
  `consentimento_gravacao_texto` TEXT DEFAULT NULL,
  `consentimento_terapia_aceito` TINYINT(1) DEFAULT 0,
  `consentimento_tele_aceito` TINYINT(1) DEFAULT 0,
  `consentimento_privacidade_aceito` TINYINT(1) DEFAULT 0,
  `consentimento_compartilhamento_aceito` TINYINT(1) DEFAULT 0,
  `consentimento_gravacao_aceito` TINYINT(1) DEFAULT 0,
  `consentimento_nome_responsavel` VARCHAR(160) DEFAULT NULL,
  `consentimento_documento` VARCHAR(60) DEFAULT NULL,
  `consentimento_parentesco` VARCHAR(80) DEFAULT NULL,
  `consentimento_testemunha` VARCHAR(160) DEFAULT NULL,
  `consentimento_registrado_em` DATETIME DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_psic_prontuario` (`prontuario_id`),
  KEY `idx_psic_prontuario_empresa` (`empresa_id`),
  KEY `idx_psic_prontuario_paciente` (`paciente_id`),
  KEY `idx_psic_prontuario_profissional` (`profissional_id`),
  KEY `idx_psic_prontuario_tipo` (`tipo_registro`),
  KEY `idx_psic_prontuario_risco` (`risco_suicida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: psicologia_planos (plano terapêutico)
-- =====================================================
CREATE TABLE IF NOT EXISTS `psicologia_planos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `prontuario_id` INT(11) DEFAULT NULL,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `abordagem` VARCHAR(120) DEFAULT NULL,
  `demanda_focal` TEXT DEFAULT NULL,
  `objetivo_principal` VARCHAR(255) NOT NULL,
  `metas` TEXT DEFAULT NULL,
  `metas_curto_prazo` TEXT DEFAULT NULL,
  `metas_medio_prazo` TEXT DEFAULT NULL,
  `metas_longo_prazo` TEXT DEFAULT NULL,
  `estrategias_intervencao` TEXT DEFAULT NULL,
  `indicadores_evolucao` TEXT DEFAULT NULL,
  `criterios_alta` TEXT DEFAULT NULL,
  `risco_monitorado` TEXT DEFAULT NULL,
  `frequencia_sugerida` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('ativo','em_acompanhamento','pausado','concluido') DEFAULT 'ativo',
  `data_inicio` DATE DEFAULT NULL,
  `data_reavaliacao` DATE DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_psic_planos_empresa` (`empresa_id`),
  KEY `idx_psic_planos_prontuario` (`prontuario_id`),
  KEY `idx_psic_planos_paciente` (`paciente_id`),
  KEY `idx_psic_planos_profissional` (`profissional_id`),
  KEY `idx_psic_planos_status` (`status`),
  KEY `idx_psic_planos_reavaliacao` (`data_reavaliacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: psicologia_escalas (instrumentos clínicos)
-- =====================================================
CREATE TABLE IF NOT EXISTS `psicologia_escalas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `nome_escala` VARCHAR(150) NOT NULL,
  `score_total` VARCHAR(50) DEFAULT NULL,
  `classificacao` VARCHAR(150) DEFAULT NULL,
  `data_aplicacao` DATE NOT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_psic_escalas_empresa` (`empresa_id`),
  KEY `idx_psic_escalas_paciente` (`paciente_id`),
  KEY `idx_psic_escalas_profissional` (`profissional_id`),
  KEY `idx_psic_escalas_data` (`data_aplicacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELAS: especialidades clínicas estruturadas
-- =====================================================
CREATE TABLE IF NOT EXISTS `psiquiatria_prontuario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `prontuario_id` INT(11) NOT NULL,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `psiq_tipo_atendimento` ENUM('primeira_consulta','retorno','interconsulta') DEFAULT 'retorno',
  `psiq_queixa_principal` TEXT DEFAULT NULL,
  `psiq_inicio_sintomas` VARCHAR(120) DEFAULT NULL,
  `psiq_fatores_desencadeantes` TEXT DEFAULT NULL,
  `psiq_historico_psiquiatrico` TEXT DEFAULT NULL,
  `psiq_internacoes_previas` TEXT DEFAULT NULL,
  `psiq_historico_familiar` TEXT DEFAULT NULL,
  `psiq_uso_substancias` TEXT DEFAULT NULL,
  `psiq_comorbidades` TEXT DEFAULT NULL,
  `psiq_humor_afeto` TEXT DEFAULT NULL,
  `psiq_pensamento` TEXT DEFAULT NULL,
  `psiq_percepcao` TEXT DEFAULT NULL,
  `psiq_cognicao` TEXT DEFAULT NULL,
  `psiq_juizo_critica` TEXT DEFAULT NULL,
  `psiq_risco_suicidio` ENUM('ausente','baixo','moderado','alto') DEFAULT 'ausente',
  `psiq_plano_seguranca` TEXT DEFAULT NULL,
  `psiq_risco_heteroagressao` TEXT DEFAULT NULL,
  `psiq_hipotese_diag` TEXT DEFAULT NULL,
  `psiq_cid10` VARCHAR(30) DEFAULT NULL,
  `psiq_medicacoes` TEXT DEFAULT NULL,
  `psiq_orientacoes_efeitos` TEXT DEFAULT NULL,
  `psiq_psicoterapia_indicada` ENUM('nao','sim') DEFAULT 'nao',
  `psiq_encaminhamentos` TEXT DEFAULT NULL,
  `psiq_conduta_urgente` TEXT DEFAULT NULL,
  `psiq_plano_retorno` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_psiquiatria_prontuario` (`prontuario_id`),
  KEY `idx_psiquiatria_empresa` (`empresa_id`),
  KEY `idx_psiquiatria_paciente` (`paciente_id`),
  KEY `idx_psiquiatria_profissional` (`profissional_id`),
  KEY `idx_psiquiatria_risco` (`psiq_risco_suicidio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pediatria_prontuario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `prontuario_id` INT(11) NOT NULL,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `ped_responsavel_nome` VARCHAR(180) DEFAULT NULL,
  `ped_responsavel_parentesco` VARCHAR(120) DEFAULT NULL,
  `ped_queixa_principal` TEXT DEFAULT NULL,
  `ped_inicio_quadro` VARCHAR(120) DEFAULT NULL,
  `ped_febre` ENUM('nao','sim') DEFAULT 'nao',
  `ped_tempo_febre` VARCHAR(120) DEFAULT NULL,
  `ped_sintomas_associados` TEXT DEFAULT NULL,
  `ped_contato_doentes` TEXT DEFAULT NULL,
  `ped_peso_atual` DECIMAL(10,2) DEFAULT NULL,
  `ped_altura_atual` DECIMAL(10,2) DEFAULT NULL,
  `ped_perimetro_cefalico` DECIMAL(10,2) DEFAULT NULL,
  `ped_curva_crescimento` ENUM('adequado','atencao','alterado') DEFAULT NULL,
  `ped_desenvolvimento_neuropsicomotor` TEXT DEFAULT NULL,
  `ped_aleitamento` TEXT DEFAULT NULL,
  `ped_vacinacao_em_dia` ENUM('sim','nao','incompleta') DEFAULT NULL,
  `ped_vacinas_pendentes` TEXT DEFAULT NULL,
  `ped_alergias` TEXT DEFAULT NULL,
  `ped_medicacoes_uso` TEXT DEFAULT NULL,
  `ped_temperatura` DECIMAL(4,1) DEFAULT NULL,
  `ped_fc` INT(11) DEFAULT NULL,
  `ped_fr` INT(11) DEFAULT NULL,
  `ped_saturacao` INT(11) DEFAULT NULL,
  `ped_exame_fisico` TEXT DEFAULT NULL,
  `ped_hipotese_diag` TEXT DEFAULT NULL,
  `ped_conduta` TEXT DEFAULT NULL,
  `ped_sinais_alarme` TEXT DEFAULT NULL,
  `ped_retorno` DATE DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pediatria_prontuario` (`prontuario_id`),
  KEY `idx_pediatria_empresa` (`empresa_id`),
  KEY `idx_pediatria_paciente` (`paciente_id`),
  KEY `idx_pediatria_profissional` (`profissional_id`),
  KEY `idx_pediatria_vacina` (`ped_vacinacao_em_dia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ginecologia_obstetricia_prontuario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `prontuario_id` INT(11) NOT NULL,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `go_tipo_atendimento` ENUM('ginecologia','obstetricia','pre_natal','puerperio') DEFAULT 'ginecologia',
  `go_queixa_principal` TEXT DEFAULT NULL,
  `go_dum` DATE DEFAULT NULL,
  `go_gpa` VARCHAR(60) DEFAULT NULL,
  `go_idade_gestacional` VARCHAR(60) DEFAULT NULL,
  `go_dpp` DATE DEFAULT NULL,
  `go_ciclo_menstrual` TEXT DEFAULT NULL,
  `go_contracepcao` TEXT DEFAULT NULL,
  `go_sangramento` TEXT DEFAULT NULL,
  `go_patologias_previas` TEXT DEFAULT NULL,
  `go_cirurgias_previas` TEXT DEFAULT NULL,
  `go_ist_risco` TEXT DEFAULT NULL,
  `go_ultimo_papanicolau` VARCHAR(120) DEFAULT NULL,
  `go_ultima_mamografia` VARCHAR(120) DEFAULT NULL,
  `go_pre_natal_risco` ENUM('nao','sim') DEFAULT 'nao',
  `go_movimentos_fetais` TEXT DEFAULT NULL,
  `go_sinais_alarme` TEXT DEFAULT NULL,
  `go_pa` VARCHAR(30) DEFAULT NULL,
  `go_peso` VARCHAR(30) DEFAULT NULL,
  `go_bcf` VARCHAR(50) DEFAULT NULL,
  `go_altura_uterina` VARCHAR(50) DEFAULT NULL,
  `go_exame_fisico` TEXT DEFAULT NULL,
  `go_exames_solicitados` TEXT DEFAULT NULL,
  `go_hipotese_diag` TEXT DEFAULT NULL,
  `go_conduta` TEXT DEFAULT NULL,
  `go_orientacoes` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_go_prontuario` (`prontuario_id`),
  KEY `idx_go_empresa` (`empresa_id`),
  KEY `idx_go_paciente` (`paciente_id`),
  KEY `idx_go_profissional` (`profissional_id`),
  KEY `idx_go_tipo` (`go_tipo_atendimento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ortopedia_traumatologia_prontuario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `prontuario_id` INT(11) NOT NULL,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `orto_regiao` VARCHAR(150) DEFAULT NULL,
  `orto_lateralidade` ENUM('direita','esquerda','bilateral','nao_aplica') DEFAULT 'nao_aplica',
  `orto_queixa_principal` TEXT DEFAULT NULL,
  `orto_mecanismo_trauma` TEXT DEFAULT NULL,
  `orto_tempo_sintomas` VARCHAR(120) DEFAULT NULL,
  `orto_escala_dor` TINYINT(3) DEFAULT NULL,
  `orto_limitacao_funcional` TEXT DEFAULT NULL,
  `orto_trauma_recente` ENUM('nao','sim') DEFAULT 'nao',
  `orto_neurovascular` TEXT DEFAULT NULL,
  `orto_inspecao` TEXT DEFAULT NULL,
  `orto_amplitude_movimento` TEXT DEFAULT NULL,
  `orto_forca` TEXT DEFAULT NULL,
  `orto_testes_especificos` TEXT DEFAULT NULL,
  `orto_rx` TEXT DEFAULT NULL,
  `orto_rm` TEXT DEFAULT NULL,
  `orto_usg` TEXT DEFAULT NULL,
  `orto_hipotese_diag` TEXT DEFAULT NULL,
  `orto_classificacao_fratura` VARCHAR(120) DEFAULT NULL,
  `orto_imobilizacao` TEXT DEFAULT NULL,
  `orto_medicacoes` TEXT DEFAULT NULL,
  `orto_fisioterapia` TEXT DEFAULT NULL,
  `orto_conduta` TEXT DEFAULT NULL,
  `orto_orientacoes` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ortopedia_prontuario` (`prontuario_id`),
  KEY `idx_ortopedia_empresa` (`empresa_id`),
  KEY `idx_ortopedia_paciente` (`paciente_id`),
  KEY `idx_ortopedia_profissional` (`profissional_id`),
  KEY `idx_ortopedia_regiao` (`orto_regiao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cardiologia_prontuario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `prontuario_id` INT(11) NOT NULL,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `cardio_queixa_principal` TEXT DEFAULT NULL,
  `cardio_dor_toracica` ENUM('nao','sim') DEFAULT 'nao',
  `cardio_dispneia` ENUM('nao','aos_grandes_esforcos','aos_medios_esforcos','aos_pequenos_esforcos','repouso') DEFAULT 'nao',
  `cardio_palpitacoes` ENUM('nao','sim') DEFAULT 'nao',
  `cardio_sincope` ENUM('nao','sim') DEFAULT 'nao',
  `cardio_hipertensao` ENUM('nao','sim') DEFAULT 'nao',
  `cardio_diabetes` ENUM('nao','sim') DEFAULT 'nao',
  `cardio_tabagismo` ENUM('nao','atual','cessado') DEFAULT 'nao',
  `cardio_hist_familiar` TEXT DEFAULT NULL,
  `cardio_pa` VARCHAR(30) DEFAULT NULL,
  `cardio_fc` INT(11) DEFAULT NULL,
  `cardio_imc` DECIMAL(5,2) DEFAULT NULL,
  `cardio_exame_fisico` TEXT DEFAULT NULL,
  `cardio_ecg` TEXT DEFAULT NULL,
  `cardio_eco` TEXT DEFAULT NULL,
  `cardio_teste_ergometrico` TEXT DEFAULT NULL,
  `cardio_laboratoriais` TEXT DEFAULT NULL,
  `cardio_hipotese_diag` TEXT DEFAULT NULL,
  `cardio_risco_cv` ENUM('baixo','intermediario','alto','muito_alto') DEFAULT NULL,
  `cardio_medicacoes` TEXT DEFAULT NULL,
  `cardio_metas` TEXT DEFAULT NULL,
  `cardio_conduta` TEXT DEFAULT NULL,
  `cardio_orientacoes` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cardiologia_prontuario` (`prontuario_id`),
  KEY `idx_cardiologia_empresa` (`empresa_id`),
  KEY `idx_cardiologia_paciente` (`paciente_id`),
  KEY `idx_cardiologia_profissional` (`profissional_id`),
  KEY `idx_cardiologia_risco` (`cardio_risco_cv`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `oftalmologia_prontuario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `prontuario_id` INT(11) NOT NULL,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `oftalmo_queixa_principal` TEXT DEFAULT NULL,
  `oftalmo_lateralidade` ENUM('od','oe','ou') DEFAULT 'ou',
  `oftalmo_inicio` VARCHAR(120) DEFAULT NULL,
  `oftalmo_dor_ocular` ENUM('nao','sim') DEFAULT 'nao',
  `oftalmo_trauma` ENUM('nao','sim') DEFAULT 'nao',
  `oftalmo_cirurgias_previas` TEXT DEFAULT NULL,
  `oftalmo_comorbidades` TEXT DEFAULT NULL,
  `oftalmo_av_sc_od` VARCHAR(30) DEFAULT NULL,
  `oftalmo_av_sc_oe` VARCHAR(30) DEFAULT NULL,
  `oftalmo_av_cc_od` VARCHAR(30) DEFAULT NULL,
  `oftalmo_av_cc_oe` VARCHAR(30) DEFAULT NULL,
  `oftalmo_tonometria` VARCHAR(40) DEFAULT NULL,
  `oftalmo_biomicroscopia` TEXT DEFAULT NULL,
  `oftalmo_fundo_olho` TEXT DEFAULT NULL,
  `oftalmo_refracao` TEXT DEFAULT NULL,
  `oftalmo_hipotese_diag` TEXT DEFAULT NULL,
  `oftalmo_exames_complementares` TEXT DEFAULT NULL,
  `oftalmo_prescricao` TEXT DEFAULT NULL,
  `oftalmo_conduta` TEXT DEFAULT NULL,
  `oftalmo_orientacoes` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_oftalmologia_prontuario` (`prontuario_id`),
  KEY `idx_oftalmologia_empresa` (`empresa_id`),
  KEY `idx_oftalmologia_paciente` (`paciente_id`),
  KEY `idx_oftalmologia_profissional` (`profissional_id`),
  KEY `idx_oftalmologia_lateralidade` (`oftalmo_lateralidade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neurologia_prontuario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `prontuario_id` INT(11) NOT NULL,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `neuro_queixa_principal` TEXT DEFAULT NULL,
  `neuro_inicio_quadro` VARCHAR(120) DEFAULT NULL,
  `neuro_instalacao` ENUM('subita','subaguda','cronica') DEFAULT NULL,
  `neuro_cefaleia` ENUM('nao','sim') DEFAULT 'nao',
  `neuro_tontura` ENUM('nao','sim') DEFAULT 'nao',
  `neuro_fraqueza` ENUM('nao','sim') DEFAULT 'nao',
  `neuro_parestesia` ENUM('nao','sim') DEFAULT 'nao',
  `neuro_crises_convulsivas` ENUM('nao','sim') DEFAULT 'nao',
  `neuro_descricao_crise` TEXT DEFAULT NULL,
  `neuro_frequencia_crises` VARCHAR(120) DEFAULT NULL,
  `neuro_deficit_focal` TEXT DEFAULT NULL,
  `neuro_ultimo_evento` VARCHAR(120) DEFAULT NULL,
  `neuro_antecedentes` TEXT DEFAULT NULL,
  `neuro_estado_mental` TEXT DEFAULT NULL,
  `neuro_pares_cranianos` TEXT DEFAULT NULL,
  `neuro_forca_tono` TEXT DEFAULT NULL,
  `neuro_reflexos` TEXT DEFAULT NULL,
  `neuro_sensibilidade` TEXT DEFAULT NULL,
  `neuro_coordenacao` TEXT DEFAULT NULL,
  `neuro_marcha` TEXT DEFAULT NULL,
  `neuro_sinais_meningeos` TEXT DEFAULT NULL,
  `neuro_tc` TEXT DEFAULT NULL,
  `neuro_rm` TEXT DEFAULT NULL,
  `neuro_eeg` TEXT DEFAULT NULL,
  `neuro_enmg` TEXT DEFAULT NULL,
  `neuro_liquor` TEXT DEFAULT NULL,
  `neuro_hipotese_diag` TEXT DEFAULT NULL,
  `neuro_conduta` TEXT DEFAULT NULL,
  `neuro_orientacoes` TEXT DEFAULT NULL,
  `neuro_retorno` DATE DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_neurologia_prontuario` (`prontuario_id`),
  KEY `idx_neurologia_empresa` (`empresa_id`),
  KEY `idx_neurologia_paciente` (`paciente_id`),
  KEY `idx_neurologia_profissional` (`profissional_id`),
  KEY `idx_neurologia_instalacao` (`neuro_instalacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELAS: protocolos, modelos e cuidados clínicos
-- =====================================================
CREATE TABLE IF NOT EXISTS `protocolos_clinicos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `tema` VARCHAR(60) NOT NULL DEFAULT 'medico',
  `titulo` VARCHAR(180) NOT NULL,
  `categoria` VARCHAR(120) DEFAULT NULL,
  `objetivo` TEXT DEFAULT NULL,
  `checklist` LONGTEXT DEFAULT NULL,
  `criterios` LONGTEXT DEFAULT NULL,
  `orientacoes` LONGTEXT DEFAULT NULL,
  `tags` VARCHAR(255) DEFAULT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_por` INT(11) DEFAULT NULL,
  `atualizado_por` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_protocolos_empresa_tema` (`empresa_id`,`tema`),
  KEY `idx_protocolos_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `modelos_clinicos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `tema` VARCHAR(60) NOT NULL DEFAULT 'medico',
  `tipo_documento` ENUM('anamnese','evolucao','retorno','orientacao','laudo','checklist','termo','outro') DEFAULT 'outro',
  `titulo` VARCHAR(180) NOT NULL,
  `conteudo` LONGTEXT NOT NULL,
  `campos_chave` VARCHAR(255) DEFAULT NULL,
  `tags` VARCHAR(255) DEFAULT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_por` INT(11) DEFAULT NULL,
  `atualizado_por` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_modelos_empresa_tema` (`empresa_id`,`tema`),
  KEY `idx_modelos_tipo` (`tipo_documento`),
  KEY `idx_modelos_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paciente_alertas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `paciente_id` INT(11) NOT NULL,
  `tema` VARCHAR(60) NOT NULL DEFAULT 'medico',
  `tipo` ENUM('clinico','alergia','medicacao','retorno','documental','risco','financeiro','outro') DEFAULT 'clinico',
  `prioridade` ENUM('baixa','moderada','alta','critica') DEFAULT 'moderada',
  `titulo` VARCHAR(180) NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  `inicio_em` DATE DEFAULT NULL,
  `fim_em` DATE DEFAULT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_por` INT(11) DEFAULT NULL,
  `atualizado_por` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_alertas_empresa_paciente` (`empresa_id`,`paciente_id`),
  KEY `idx_alertas_tema` (`tema`),
  KEY `idx_alertas_prioridade` (`prioridade`),
  KEY `idx_alertas_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `planos_cuidado` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) DEFAULT NULL,
  `tema` VARCHAR(60) NOT NULL DEFAULT 'medico',
  `titulo` VARCHAR(180) NOT NULL,
  `objetivo` TEXT DEFAULT NULL,
  `metas` LONGTEXT DEFAULT NULL,
  `conduta` LONGTEXT DEFAULT NULL,
  `frequencia_retorno` VARCHAR(120) DEFAULT NULL,
  `proxima_revisao` DATE DEFAULT NULL,
  `status` ENUM('ativo','em_acompanhamento','pausado','concluido','cancelado') DEFAULT 'ativo',
  `indicadores` TEXT DEFAULT NULL,
  `orientacoes` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_planos_cuidado_empresa_paciente` (`empresa_id`,`paciente_id`),
  KEY `idx_planos_cuidado_tema` (`tema`),
  KEY `idx_planos_cuidado_status` (`status`),
  KEY `idx_planos_cuidado_profissional` (`profissional_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: NFSe (notas fiscais)
-- =====================================================
CREATE TABLE IF NOT EXISTS `agenda_fila_espera` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) DEFAULT NULL,
  `procedimento_id` INT(11) DEFAULT NULL,
  `prioridade` ENUM('normal','alta','urgente') NOT NULL DEFAULT 'normal',
  `data_preferencial` DATE DEFAULT NULL,
  `hora_inicio_preferencial` TIME DEFAULT NULL,
  `hora_fim_preferencial` TIME DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `status` ENUM('ativo','contatado','agendado','cancelado') NOT NULL DEFAULT 'ativo',
  `agendamento_id` INT(11) DEFAULT NULL,
  `contatado_em` DATETIME DEFAULT NULL,
  `agendado_em` DATETIME DEFAULT NULL,
  `criado_por` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agenda_fila_empresa_status` (`empresa_id`,`status`),
  KEY `idx_agenda_fila_profissional` (`profissional_id`),
  KEY `idx_agenda_fila_paciente` (`paciente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paciente_consentimentos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `paciente_id` INT(11) NOT NULL,
  `tipo` ENUM('atendimento','privacidade','telemedicina','compartilhamento','marketing','financeiro','outro') NOT NULL DEFAULT 'atendimento',
  `finalidade` VARCHAR(255) NOT NULL,
  `base_legal` VARCHAR(180) DEFAULT NULL,
  `canal` VARCHAR(60) DEFAULT NULL,
  `status` ENUM('ativo','negado','revogado','expirado') NOT NULL DEFAULT 'ativo',
  `aceito_em` DATETIME DEFAULT NULL,
  `revogado_em` DATETIME DEFAULT NULL,
  `validade_ate` DATE DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `criado_por` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_consentimentos_empresa_paciente` (`empresa_id`,`paciente_id`),
  KEY `idx_consentimentos_status` (`status`),
  KEY `idx_consentimentos_validade` (`validade_ate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nfse` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `paciente_id` INT(11) NOT NULL,
  `agendamento_id` INT(11) DEFAULT NULL,
  `numero_rps` INT(11) NOT NULL,
  `serie_rps` VARCHAR(10) DEFAULT '1',
  `numero_nfse` VARCHAR(20) DEFAULT NULL COMMENT 'Retornado pela prefeitura',
  `codigo_verificacao` VARCHAR(50) DEFAULT NULL,
  `data_emissao` DATETIME NOT NULL,
  `data_competencia` DATE DEFAULT NULL,
  `valor_servico` DECIMAL(10,2) NOT NULL,
  `valor_deducoes` DECIMAL(10,2) DEFAULT 0.00,
  `valor_iss` DECIMAL(10,2) DEFAULT 0.00,
  `aliquota_iss` DECIMAL(5,2) DEFAULT 0.00,
  `valor_liquido` DECIMAL(10,2) DEFAULT 0.00,
  `discriminacao` TEXT NOT NULL,
  `item_lista_servico` VARCHAR(10) DEFAULT NULL,
  `codigo_tributacao_municipio` VARCHAR(20) DEFAULT NULL,
  `iss_retido` TINYINT(1) DEFAULT 0,
  `status` ENUM('rascunho','enviada','autorizada','cancelada','rejeitada','erro') DEFAULT 'rascunho',
  `protocolo` VARCHAR(50) DEFAULT NULL,
  `xml_envio` LONGTEXT DEFAULT NULL,
  `xml_retorno` LONGTEXT DEFAULT NULL,
  `pdf_url` VARCHAR(255) DEFAULT NULL,
  `mensagem_retorno` TEXT DEFAULT NULL,
  `motivo_cancelamento` TEXT DEFAULT NULL,
  `criado_por` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nfse_paciente` (`paciente_id`),
  KEY `idx_nfse_data` (`data_emissao`),
  KEY `idx_nfse_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: logs de acesso/atividade
-- =====================================================
CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) DEFAULT NULL,
  `acao` VARCHAR(100) DEFAULT NULL,
  `entidade` VARCHAR(50) DEFAULT NULL,
  `entidade_id` INT(11) DEFAULT NULL,
  `descricao` TEXT DEFAULT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_user` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: configurações gerais
-- =====================================================
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `chave` VARCHAR(50) NOT NULL,
  `valor` TEXT DEFAULT NULL,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Empresa padrão
INSERT INTO `empresas` (`id`,`razao_social`,`nome_fantasia`,`cnpj`,`tema_padrao`,`asten_ambiente`) VALUES
(1,'Clínica Demo LTDA','Clínica Demo','00.000.000/0001-00','medico','homologacao');

-- Usuário admin (senha: admin123)
INSERT INTO `usuarios` (`empresa_id`,`nome`,`email`,`senha`,`perfil`,`tema_preferido`,`cor_tema`,`ativo`) VALUES
(1,'Administrador','admin@clinicapp.com','$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa','admin','medico','blue',1);

-- Procedimentos exemplo
INSERT INTO `procedimentos` (`empresa_id`,`nome`,`descricao`,`valor`,`duracao_minutos`,`categoria`) VALUES
(1,'Consulta Clínica','Consulta médica geral',250.00,30,'clinico'),
(1,'Limpeza Dental','Profilaxia e remoção de tártaro',180.00,45,'odontologico'),
(1,'Restauração','Restauração em resina',280.00,60,'odontologico'),
(1,'Retorno','Consulta de retorno',150.00,20,'clinico');

-- Configurações
INSERT INTO `configuracoes` (`chave`,`valor`) VALUES
('app_nome','ClinicApp'),
('app_versao','1.2.0'),
('horario_inicio','08:00'),
('horario_fim','18:00'),
('intervalo_consulta','30'),
('dias_atendimento','1,2,3,4,5');

SET FOREIGN_KEY_CHECKS = 1;
