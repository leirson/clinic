-- =====================================================
-- ClinicApp v7 - Prontuário Psicológico Estruturado
-- Compatível com Hostinger / phpMyAdmin
-- Objetivo:
--   1) Criar tabela estruturada para prontuário psicológico
--   2) Expandir o plano terapêutico com metas e critérios clínicos
--   3) Habilitar consentimentos ampliados e avaliação de risco
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

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

DROP PROCEDURE IF EXISTS `ca_add_column_if_missing`;
DELIMITER $$
CREATE PROCEDURE `ca_add_column_if_missing`(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
          FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = p_table
           AND COLUMN_NAME = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL `ca_add_column_if_missing`('psicologia_planos', 'prontuario_id', 'INT(11) DEFAULT NULL AFTER `empresa_id`');
CALL `ca_add_column_if_missing`('psicologia_planos', 'demanda_focal', 'TEXT DEFAULT NULL AFTER `abordagem`');
CALL `ca_add_column_if_missing`('psicologia_planos', 'metas_curto_prazo', 'TEXT DEFAULT NULL AFTER `metas`');
CALL `ca_add_column_if_missing`('psicologia_planos', 'metas_medio_prazo', 'TEXT DEFAULT NULL AFTER `metas_curto_prazo`');
CALL `ca_add_column_if_missing`('psicologia_planos', 'metas_longo_prazo', 'TEXT DEFAULT NULL AFTER `metas_medio_prazo`');
CALL `ca_add_column_if_missing`('psicologia_planos', 'estrategias_intervencao', 'TEXT DEFAULT NULL AFTER `metas_longo_prazo`');
CALL `ca_add_column_if_missing`('psicologia_planos', 'indicadores_evolucao', 'TEXT DEFAULT NULL AFTER `estrategias_intervencao`');
CALL `ca_add_column_if_missing`('psicologia_planos', 'criterios_alta', 'TEXT DEFAULT NULL AFTER `indicadores_evolucao`');
CALL `ca_add_column_if_missing`('psicologia_planos', 'risco_monitorado', 'TEXT DEFAULT NULL AFTER `criterios_alta`');

DROP PROCEDURE IF EXISTS `ca_add_column_if_missing`;

SET FOREIGN_KEY_CHECKS = 1;
