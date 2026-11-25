SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

-- =====================================================
-- UPGRADE V12 - COMUNICAÇÃO, CALENDÁRIO, PWA E LEMBRETES
-- Versão lógica alvo: 1.1.0
-- =====================================================

ALTER TABLE `empresas`
  ADD COLUMN IF NOT EXISTS `asten_certificado_a1` VARCHAR(255) DEFAULT NULL AFTER `asten_proximo_rps`,
  ADD COLUMN IF NOT EXISTS `asten_certificado_senha` VARCHAR(255) DEFAULT NULL AFTER `asten_certificado_a1`;

CREATE TABLE IF NOT EXISTS `notificacoes_sistema` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `usuario_id` INT(11) DEFAULT NULL,
  `tipo` VARCHAR(50) NOT NULL DEFAULT 'lembrete',
  `titulo` VARCHAR(160) NOT NULL,
  `mensagem` TEXT DEFAULT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  `referencia_tabela` VARCHAR(80) DEFAULT NULL,
  `referencia_id` INT(11) DEFAULT NULL,
  `dedupe_key` VARCHAR(120) DEFAULT NULL,
  `agendar_para` DATETIME NOT NULL,
  `entregue_em` DATETIME DEFAULT NULL,
  `lida_em` DATETIME DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_notif_dedupe` (`dedupe_key`),
  KEY `idx_notif_empresa_usuario` (`empresa_id`, `usuario_id`),
  KEY `idx_notif_agendar` (`agendar_para`),
  KEY `idx_notif_entregue` (`entregue_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'app_versao', '1.1.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'app_versao'
);

UPDATE `configuracoes`
SET `valor` = '1.1.0'
WHERE `empresa_id` = 1 AND `chave` = 'app_versao';

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'google_calendar_enabled', '1'
WHERE NOT EXISTS (
  SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'google_calendar_enabled'
);

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'reminder_email_enabled', '1'
WHERE NOT EXISTS (
  SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'reminder_email_enabled'
);

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'reminder_push_enabled', '1'
WHERE NOT EXISTS (
  SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'reminder_push_enabled'
);

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'reminder_hours_before', '24'
WHERE NOT EXISTS (
  SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'reminder_hours_before'
);

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'push_minutes_before', '60'
WHERE NOT EXISTS (
  SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'push_minutes_before'
);

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'reminder_window_minutes', '30'
WHERE NOT EXISTS (
  SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'reminder_window_minutes'
);

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'pwa_install_enabled', '1'
WHERE NOT EXISTS (
  SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'pwa_install_enabled'
);

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;
