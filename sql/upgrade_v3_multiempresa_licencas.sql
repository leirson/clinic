SET NAMES utf8mb4;
-- =====================================================
-- ClinicApp v3 - Multiempresa + Licenças + Desenvolvedor
-- Execute após database.sql e upgrade_v2.sql
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `usuarios`
  MODIFY COLUMN `perfil` ENUM('admin','profissional','recepcao','desenvolvedor') DEFAULT 'profissional';

ALTER TABLE `empresas`
  ADD COLUMN IF NOT EXISTS `dominio_principal` VARCHAR(255) DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `ativo` TINYINT(1) DEFAULT 1 AFTER `dominio_principal`;

CREATE TABLE IF NOT EXISTS `licencas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `plano` ENUM('starter','profissional','premium','enterprise') DEFAULT 'profissional',
  `status` ENUM('ativa','teste','pendente','expirada','bloqueada','cancelada') DEFAULT 'ativa',
  `chave_licenca` VARCHAR(64) NOT NULL,
  `validade_inicio` DATE DEFAULT NULL,
  `validade_fim` DATE DEFAULT NULL,
  `max_usuarios` INT(11) DEFAULT 10,
  `max_pacientes` INT(11) DEFAULT 1000,
  `observacoes` TEXT DEFAULT NULL,
  `criado_por` INT(11) DEFAULT NULL,
  `atualizado_por` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chave_licenca` (`chave_licenca`),
  KEY `idx_licenca_empresa` (`empresa_id`),
  KEY `idx_licenca_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `licencas` (`empresa_id`,`plano`,`status`,`chave_licenca`,`validade_inicio`,`validade_fim`,`max_usuarios`,`max_pacientes`,`observacoes`,`criado_por`,`atualizado_por`)
SELECT 1, 'premium', 'ativa', 'CLINICAPP-DEMO-001', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH), 25, 10000, 'Licença padrão inicial da empresa principal.', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `licencas` WHERE `empresa_id` = 1);

INSERT INTO `usuarios` (`empresa_id`,`nome`,`email`,`senha`,`perfil`,`especialidade`,`tema_preferido`,`cor_tema`,`ativo`)
SELECT 1,'Desenvolvedor ClinicApp','dev@clinicapp.com','$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa','desenvolvedor','Licenciamento & Suporte','medico','purple',1
WHERE NOT EXISTS (SELECT 1 FROM `usuarios` WHERE `email` = 'dev@clinicapp.com');

SET FOREIGN_KEY_CHECKS = 1;
