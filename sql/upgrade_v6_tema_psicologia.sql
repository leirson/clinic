-- =====================================================
-- ClinicApp v6 - Tema exclusivo de Psicologia
-- Compatível com Hostinger / phpMyAdmin
-- Objetivo:
--   1) Adicionar o tema Psicologia no painel e portal
--   2) Criar recursos separados da especialidade
--   3) Ativar o tema automaticamente por especialidade quando aplicável
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

ALTER TABLE `empresas`
    MODIFY COLUMN `tema_padrao` ENUM('dentista','medico','psicologia') DEFAULT 'medico';

ALTER TABLE `usuarios`
    MODIFY COLUMN `tema_preferido` ENUM('dentista','medico','psicologia') DEFAULT 'medico';

CREATE TABLE IF NOT EXISTS `psicologia_planos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `abordagem` VARCHAR(120) DEFAULT NULL,
  `objetivo_principal` VARCHAR(255) NOT NULL,
  `metas` TEXT DEFAULT NULL,
  `frequencia_sugerida` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('ativo','em_acompanhamento','pausado','concluido') DEFAULT 'ativo',
  `data_inicio` DATE DEFAULT NULL,
  `data_reavaliacao` DATE DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_psic_planos_empresa` (`empresa_id`),
  KEY `idx_psic_planos_paciente` (`paciente_id`),
  KEY `idx_psic_planos_profissional` (`profissional_id`),
  KEY `idx_psic_planos_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

UPDATE `usuarios`
   SET `tema_preferido` = 'psicologia'
 WHERE `tema_preferido` = 'medico'
   AND (
        LOWER(COALESCE(`especialidade`, '')) LIKE '%psicol%'
     OR LOWER(COALESCE(`especialidade`, '')) LIKE '%psicoter%'
     OR LOWER(COALESCE(`especialidade`, '')) LIKE '%psican%'
     OR LOWER(COALESCE(`especialidade`, '')) LIKE '%neuropsic%'
   );

SET FOREIGN_KEY_CHECKS = 1;