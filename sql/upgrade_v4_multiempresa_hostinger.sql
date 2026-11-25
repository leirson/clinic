-- =====================================================
-- ClinicApp v4 - Migração completa para Multiempresa
-- Compatível com Hostinger / phpMyAdmin
-- Objetivo:
--   1) Garantir empresa padrão (ID 1)
--   2) Adicionar empresa_id nas tabelas operacionais e auxiliares
--   3) Preencher registros antigos com a empresa padrão ou via relacionamento
--   4) Criar índices e chaves estrangeiras para isolamento por empresa
--   5) Ajustar usuários para perfil desenvolvedor e e-mail por empresa
--   6) Criar estrutura de licenças caso ainda não exista
--
-- IMPORTANTE:
--   - Faça backup antes de executar.
--   - Execute após database.sql e upgrade_v2.sql.
--   - Pode ser executado em bases já em produção, desde que a estrutura
--     básica do ClinicApp esteja presente.
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

-- =====================================================
-- EMPRESA PADRÃO
-- =====================================================
INSERT INTO `empresas` (
    `id`, `razao_social`, `nome_fantasia`, `cnpj`, `email`, `telefone`, `tema_padrao`, `asten_ambiente`
)
SELECT
    1,
    'Empresa Padrão ClinicApp',
    'Empresa Padrão',
    '00.000.000/0001-00',
    'admin@clinicapp.com',
    '',
    'medico',
    'homologacao'
WHERE NOT EXISTS (
    SELECT 1 FROM `empresas` WHERE `id` = 1
);

-- =====================================================
-- HELPERS DE MIGRAÇÃO
-- =====================================================
DROP PROCEDURE IF EXISTS `ca_add_column`;
DROP PROCEDURE IF EXISTS `ca_add_index`;
DROP PROCEDURE IF EXISTS `ca_add_unique`;
DROP PROCEDURE IF EXISTS `ca_add_fk`;
DROP PROCEDURE IF EXISTS `ca_drop_index`;
DROP PROCEDURE IF EXISTS `ca_fix_config_pk`;

DELIMITER $$

CREATE PROCEDURE `ca_add_column`(
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
        SET @sql = CONCAT(
            'ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE `ca_add_index`(
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

CREATE PROCEDURE `ca_add_unique`(
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
            'ALTER TABLE `', p_table, '` ADD UNIQUE KEY `', p_index, '` (', p_columns, ')'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE `ca_drop_index`(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64)
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` DROP INDEX `', p_index, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE `ca_add_fk`(
    IN p_table VARCHAR(64),
    IN p_fk VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_ref_table VARCHAR(64),
    IN p_ref_column VARCHAR(64)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND CONSTRAINT_NAME = p_fk
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `', p_table,
            '` ADD CONSTRAINT `', p_fk,
            '` FOREIGN KEY (`', p_column, '`) REFERENCES `', p_ref_table, '` (`', p_ref_column, '`)',
            ' ON UPDATE CASCADE ON DELETE RESTRICT'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE `ca_fix_config_pk`()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'configuracoes'
    ) AND NOT EXISTS (
        SELECT 1
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'configuracoes'
          AND CONSTRAINT_NAME = 'PRIMARY'
          AND COLUMN_NAME = 'empresa_id'
    ) THEN
        SET @sql = 'ALTER TABLE `configuracoes` DROP PRIMARY KEY, ADD PRIMARY KEY (`empresa_id`,`chave`)';
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- AJUSTES ESTRUTURAIS BASE (EMPRESA / USUÁRIOS / LICENÇAS)
-- =====================================================
ALTER TABLE `usuarios`
    MODIFY COLUMN `perfil` ENUM('admin','profissional','recepcao','desenvolvedor') DEFAULT 'profissional';

CALL `ca_add_column`('empresas', 'dominio_principal', 'VARCHAR(255) NULL AFTER `email`');
CALL `ca_add_column`('empresas', 'ativo', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER `dominio_principal`');

CALL `ca_drop_index`('usuarios', 'uk_email');
CALL `ca_add_unique`('usuarios', 'uk_usuario_empresa_email', '`empresa_id`,`email`');

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
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL `ca_add_index`('licencas', 'idx_licencas_empresa', '`empresa_id`');
CALL `ca_add_index`('licencas', 'idx_licencas_status', '`status`');
CALL `ca_add_unique`('licencas', 'uk_licencas_chave', '`chave_licenca`');

INSERT INTO `licencas` (
    `empresa_id`, `plano`, `status`, `chave_licenca`, `validade_inicio`, `validade_fim`, `max_usuarios`, `max_pacientes`, `observacoes`, `criado_por`, `atualizado_por`
)
SELECT
    1,
    'premium',
    'ativa',
    'CLINICAPP-DEMO-001',
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 12 MONTH),
    25,
    10000,
    'Licença inicial da empresa padrão.',
    1,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `licencas` WHERE `empresa_id` = 1
);

INSERT INTO `usuarios` (
    `empresa_id`, `nome`, `email`, `senha`, `perfil`, `especialidade`, `tema_preferido`, `cor_tema`, `ativo`
)
SELECT
    1,
    'Desenvolvedor ClinicApp',
    'dev@clinicapp.com',
    '$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa',
    'desenvolvedor',
    'Licenciamento & Suporte',
    'medico',
    'purple',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `usuarios` WHERE `email` = 'dev@clinicapp.com' AND `empresa_id` = 1
);

-- =====================================================
-- ADIÇÃO DE empresa_id NAS TABELAS QUE AINDA NÃO POSSUEM
-- =====================================================
CALL `ca_add_column`('prontuarios', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');
CALL `ca_add_column`('odontograma', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');
CALL `ca_add_column`('receitas', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');
CALL `ca_add_column`('anexos', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');
CALL `ca_add_column`('logs', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');
CALL `ca_add_column`('configuracoes', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 FIRST');
CALL `ca_add_column`('plano_tratamento_itens', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');
CALL `ca_add_column`('odontograma_historico', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');
CALL `ca_add_column`('teleconsultas', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');
CALL `ca_add_column`('paciente_diagnosticos', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');
CALL `ca_add_column`('solicitacoes_exame', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');
CALL `ca_add_column`('backups', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');

-- Catálogos / tabelas de apoio também recebem empresa_id
CALL `ca_add_column`('cid10', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 FIRST');
CALL `ca_add_column`('medicamentos', 'empresa_id', 'INT(11) NOT NULL DEFAULT 1 AFTER `id`');

-- =====================================================
-- BACKFILL DE EMPRESA_ID NOS REGISTROS ANTIGOS
-- =====================================================
UPDATE `usuarios` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;
UPDATE `pacientes` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;
UPDATE `procedimentos` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;
UPDATE `agendamentos` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;
UPDATE `bloqueios_horario` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;
UPDATE `nfse` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;
UPDATE `categorias_financeiras` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;
UPDATE `contas_financeiras` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;
UPDATE `planos_tratamento` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;
UPDATE `exames_modelos` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;
UPDATE `importacoes` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;
UPDATE `licencas` SET `empresa_id` = 1 WHERE `empresa_id` IS NULL OR `empresa_id` = 0;

UPDATE `prontuarios` pr
LEFT JOIN `pacientes` pa ON pa.`id` = pr.`paciente_id`
LEFT JOIN `agendamentos` ag ON ag.`id` = pr.`agendamento_id`
SET pr.`empresa_id` = COALESCE(pa.`empresa_id`, ag.`empresa_id`, 1)
WHERE pr.`empresa_id` IS NULL OR pr.`empresa_id` = 0 OR pr.`empresa_id` = 1;

UPDATE `odontograma` od
LEFT JOIN `pacientes` pa ON pa.`id` = od.`paciente_id`
SET od.`empresa_id` = COALESCE(pa.`empresa_id`, 1)
WHERE od.`empresa_id` IS NULL OR od.`empresa_id` = 0 OR od.`empresa_id` = 1;

UPDATE `receitas` rc
LEFT JOIN `pacientes` pa ON pa.`id` = rc.`paciente_id`
LEFT JOIN `prontuarios` pr ON pr.`id` = rc.`prontuario_id`
SET rc.`empresa_id` = COALESCE(pa.`empresa_id`, pr.`empresa_id`, 1)
WHERE rc.`empresa_id` IS NULL OR rc.`empresa_id` = 0 OR rc.`empresa_id` = 1;

UPDATE `anexos` an
LEFT JOIN `prontuarios` pr ON pr.`id` = an.`prontuario_id`
LEFT JOIN `pacientes` pa ON pa.`id` = an.`paciente_id`
SET an.`empresa_id` = COALESCE(pr.`empresa_id`, pa.`empresa_id`, 1)
WHERE an.`empresa_id` IS NULL OR an.`empresa_id` = 0 OR an.`empresa_id` = 1;

UPDATE `logs` lg
LEFT JOIN `usuarios` u ON u.`id` = lg.`usuario_id`
SET lg.`empresa_id` = COALESCE(u.`empresa_id`, 1)
WHERE lg.`empresa_id` IS NULL OR lg.`empresa_id` = 0 OR lg.`empresa_id` = 1;

UPDATE `configuracoes`
SET `empresa_id` = 1
WHERE `empresa_id` IS NULL OR `empresa_id` = 0;

UPDATE `plano_tratamento_itens` pti
LEFT JOIN `planos_tratamento` pt ON pt.`id` = pti.`plano_id`
SET pti.`empresa_id` = COALESCE(pt.`empresa_id`, 1)
WHERE pti.`empresa_id` IS NULL OR pti.`empresa_id` = 0 OR pti.`empresa_id` = 1;

UPDATE `odontograma_historico` oh
LEFT JOIN `pacientes` pa ON pa.`id` = oh.`paciente_id`
SET oh.`empresa_id` = COALESCE(pa.`empresa_id`, 1)
WHERE oh.`empresa_id` IS NULL OR oh.`empresa_id` = 0 OR oh.`empresa_id` = 1;

UPDATE `teleconsultas` tc
LEFT JOIN `agendamentos` ag ON ag.`id` = tc.`agendamento_id`
SET tc.`empresa_id` = COALESCE(ag.`empresa_id`, 1)
WHERE tc.`empresa_id` IS NULL OR tc.`empresa_id` = 0 OR tc.`empresa_id` = 1;

UPDATE `paciente_diagnosticos` pd
LEFT JOIN `pacientes` pa ON pa.`id` = pd.`paciente_id`
LEFT JOIN `prontuarios` pr ON pr.`id` = pd.`prontuario_id`
SET pd.`empresa_id` = COALESCE(pa.`empresa_id`, pr.`empresa_id`, 1)
WHERE pd.`empresa_id` IS NULL OR pd.`empresa_id` = 0 OR pd.`empresa_id` = 1;

UPDATE `solicitacoes_exame` se
LEFT JOIN `pacientes` pa ON pa.`id` = se.`paciente_id`
LEFT JOIN `prontuarios` pr ON pr.`id` = se.`prontuario_id`
SET se.`empresa_id` = COALESCE(pa.`empresa_id`, pr.`empresa_id`, 1)
WHERE se.`empresa_id` IS NULL OR se.`empresa_id` = 0 OR se.`empresa_id` = 1;

UPDATE `backups`
SET `empresa_id` = 1
WHERE `empresa_id` IS NULL OR `empresa_id` = 0;

UPDATE `cid10`
SET `empresa_id` = 1
WHERE `empresa_id` IS NULL OR `empresa_id` = 0;

UPDATE `medicamentos`
SET `empresa_id` = 1
WHERE `empresa_id` IS NULL OR `empresa_id` = 0;

-- Ajusta PK de configuracoes para suportar chave por empresa
CALL `ca_fix_config_pk`();

-- =====================================================
-- ÍNDICES DE EMPRESA
-- =====================================================
CALL `ca_add_index`('usuarios', 'idx_usuarios_empresa', '`empresa_id`');
CALL `ca_add_index`('pacientes', 'idx_pacientes_empresa', '`empresa_id`');
CALL `ca_add_index`('procedimentos', 'idx_procedimentos_empresa', '`empresa_id`');
CALL `ca_add_index`('agendamentos', 'idx_agendamentos_empresa', '`empresa_id`');
CALL `ca_add_index`('bloqueios_horario', 'idx_bloqueios_empresa', '`empresa_id`');
CALL `ca_add_index`('prontuarios', 'idx_prontuarios_empresa', '`empresa_id`');
CALL `ca_add_index`('odontograma', 'idx_odontograma_empresa', '`empresa_id`');
CALL `ca_add_index`('receitas', 'idx_receitas_empresa', '`empresa_id`');
CALL `ca_add_index`('anexos', 'idx_anexos_empresa', '`empresa_id`');
CALL `ca_add_index`('nfse', 'idx_nfse_empresa', '`empresa_id`');
CALL `ca_add_index`('logs', 'idx_logs_empresa', '`empresa_id`');
CALL `ca_add_index`('configuracoes', 'idx_configuracoes_empresa', '`empresa_id`');
CALL `ca_add_index`('categorias_financeiras', 'idx_cat_fin_empresa', '`empresa_id`');
CALL `ca_add_index`('contas_financeiras', 'idx_contas_fin_empresa', '`empresa_id`');
CALL `ca_add_index`('planos_tratamento', 'idx_planos_empresa', '`empresa_id`');
CALL `ca_add_index`('plano_tratamento_itens', 'idx_plano_itens_empresa', '`empresa_id`');
CALL `ca_add_index`('odontograma_historico', 'idx_odonto_hist_empresa', '`empresa_id`');
CALL `ca_add_index`('teleconsultas', 'idx_teleconsultas_empresa', '`empresa_id`');
CALL `ca_add_index`('paciente_diagnosticos', 'idx_diag_empresa', '`empresa_id`');
CALL `ca_add_index`('exames_modelos', 'idx_exames_modelos_empresa', '`empresa_id`');
CALL `ca_add_index`('solicitacoes_exame', 'idx_solicitacoes_empresa', '`empresa_id`');
CALL `ca_add_index`('backups', 'idx_backups_empresa', '`empresa_id`');
CALL `ca_add_index`('importacoes', 'idx_importacoes_empresa', '`empresa_id`');
CALL `ca_add_index`('licencas', 'idx_licencas_empresa_ref', '`empresa_id`');
CALL `ca_add_index`('cid10', 'idx_cid10_empresa', '`empresa_id`');
CALL `ca_add_index`('medicamentos', 'idx_medicamentos_empresa', '`empresa_id`');

-- =====================================================
-- CHAVES ESTRANGEIRAS DE ISOLAMENTO POR EMPRESA
-- =====================================================
CALL `ca_add_fk`('usuarios', 'fk_usuarios_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('pacientes', 'fk_pacientes_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('procedimentos', 'fk_procedimentos_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('agendamentos', 'fk_agendamentos_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('bloqueios_horario', 'fk_bloqueios_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('prontuarios', 'fk_prontuarios_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('odontograma', 'fk_odontograma_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('receitas', 'fk_receitas_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('anexos', 'fk_anexos_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('nfse', 'fk_nfse_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('logs', 'fk_logs_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('configuracoes', 'fk_config_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('categorias_financeiras', 'fk_cat_fin_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('contas_financeiras', 'fk_contas_fin_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('planos_tratamento', 'fk_planos_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('plano_tratamento_itens', 'fk_plano_itens_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('odontograma_historico', 'fk_odonto_hist_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('teleconsultas', 'fk_teleconsultas_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('paciente_diagnosticos', 'fk_diag_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('exames_modelos', 'fk_exames_modelos_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('solicitacoes_exame', 'fk_solicitacoes_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('backups', 'fk_backups_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('importacoes', 'fk_importacoes_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('licencas', 'fk_licencas_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('cid10', 'fk_cid10_empresa_multi', 'empresa_id', 'empresas', 'id');
CALL `ca_add_fk`('medicamentos', 'fk_medicamentos_empresa_multi', 'empresa_id', 'empresas', 'id');

-- =====================================================
-- RELATÓRIO FINAL DE CONFERÊNCIA
-- =====================================================
SELECT 'empresas' AS tabela, COUNT(*) AS total FROM `empresas`
UNION ALL SELECT 'usuarios', COUNT(*) FROM `usuarios`
UNION ALL SELECT 'pacientes', COUNT(*) FROM `pacientes`
UNION ALL SELECT 'agendamentos', COUNT(*) FROM `agendamentos`
UNION ALL SELECT 'prontuarios', COUNT(*) FROM `prontuarios`
UNION ALL SELECT 'receitas', COUNT(*) FROM `receitas`
UNION ALL SELECT 'anexos', COUNT(*) FROM `anexos`
UNION ALL SELECT 'teleconsultas', COUNT(*) FROM `teleconsultas`
UNION ALL SELECT 'solicitacoes_exame', COUNT(*) FROM `solicitacoes_exame`
UNION ALL SELECT 'backups', COUNT(*) FROM `backups`
UNION ALL SELECT 'licencas', COUNT(*) FROM `licencas`;

SELECT
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND COLUMN_NAME = 'empresa_id'
ORDER BY TABLE_NAME;

-- =====================================================
-- LIMPEZA DE HELPERS
-- =====================================================
DROP PROCEDURE IF EXISTS `ca_add_column`;
DROP PROCEDURE IF EXISTS `ca_add_index`;
DROP PROCEDURE IF EXISTS `ca_add_unique`;
DROP PROCEDURE IF EXISTS `ca_add_fk`;
DROP PROCEDURE IF EXISTS `ca_drop_index`;
DROP PROCEDURE IF EXISTS `ca_fix_config_pk`;

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;
