-- =====================================================
-- ClinicApp v9 - Protocolos, Modelos Clínicos e Cuidados
-- Compatível com Hostinger / phpMyAdmin
-- Objetivo:
--   1) Criar biblioteca de protocolos clínicos por tema
--   2) Criar modelos clínicos reutilizáveis
--   3) Habilitar alertas do paciente e planos de cuidado
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

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

SET FOREIGN_KEY_CHECKS = 1;
