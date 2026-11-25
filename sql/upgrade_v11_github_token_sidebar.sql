SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

-- =====================================================
-- UPGRADE V11 - TOKEN EMBUTIDO E VERSÃO 1.0.2
-- =====================================================

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'app_versao', '1.0.2'
WHERE NOT EXISTS (
    SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'app_versao'
);

UPDATE `configuracoes`
SET `valor` = '1.0.2'
WHERE `empresa_id` = 1 AND `chave` = 'app_versao';

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'github_repo', 'leirson/Clinicapp'
WHERE NOT EXISTS (
    SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'github_repo'
);

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'github_branch', 'main'
WHERE NOT EXISTS (
    SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'github_branch'
);

UPDATE `configuracoes`
SET `valor` = 'main'
WHERE `empresa_id` = 1 AND `chave` = 'github_branch';

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;
