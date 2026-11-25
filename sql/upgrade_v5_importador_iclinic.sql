-- =====================================================
-- ClinicApp v5 - Módulo de Importação Afya iClinic
-- Compatível com Hostinger / phpMyAdmin
-- Objetivo:
--   1) Permitir novos tipos de importação do Afya iClinic
--   2) Manter histórico por empresa
--   3) Melhorar performance de consulta do histórico
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

ALTER TABLE `importacoes`
    MODIFY COLUMN `tipo` ENUM(
        'pacientes',
        'agendamentos',
        'procedimentos',
        'pacientes_iclinic',
        'prontuarios_iclinic',
        'pacote_iclinic_zip'
    ) NOT NULL;

DROP PROCEDURE IF EXISTS `ca_add_index_if_missing`;
DELIMITER $$
CREATE PROCEDURE `ca_add_index_if_missing`(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL `ca_add_index_if_missing`('importacoes', 'idx_importacoes_empresa_data', '`empresa_id`, `criado_em`');
CALL `ca_add_index_if_missing`('importacoes', 'idx_importacoes_tipo', '`tipo`');

DROP PROCEDURE IF EXISTS `ca_add_index_if_missing`;

SET FOREIGN_KEY_CHECKS = 1;
