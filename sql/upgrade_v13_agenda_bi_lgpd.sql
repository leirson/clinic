SET NAMES utf8mb4;
-- =====================================================
-- ClinicApp - Upgrade v13
-- Agenda inteligente + BI MVP + LGPD/Auditoria
-- Versão lógica alvo: 1.2.0
-- =====================================================

ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `percentual_repasse` DECIMAL(5,2) DEFAULT 0.00 AFTER `telefone`;

ALTER TABLE `agendamentos`
  ADD COLUMN IF NOT EXISTS `confirmacao_status` ENUM('pendente','confirmado','recusado','reagendar') DEFAULT 'pendente' AFTER `lembrete_email_enviado`,
  ADD COLUMN IF NOT EXISTS `confirmado_em` DATETIME DEFAULT NULL AFTER `confirmacao_status`,
  ADD COLUMN IF NOT EXISTS `ultimo_lembrete_em` DATETIME DEFAULT NULL AFTER `confirmado_em`,
  ADD COLUMN IF NOT EXISTS `cancelado_em` DATETIME DEFAULT NULL AFTER `ultimo_lembrete_em`,
  ADD COLUMN IF NOT EXISTS `cancelado_motivo` VARCHAR(255) DEFAULT NULL AFTER `cancelado_em`,
  ADD COLUMN IF NOT EXISTS `encaixe_origem_fila_id` INT(11) DEFAULT NULL AFTER `cancelado_motivo`;

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

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'app_versao', '1.2.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'app_versao'
);

UPDATE `configuracoes`
SET `valor` = '1.2.0'
WHERE `empresa_id` = 1 AND `chave` = 'app_versao';
