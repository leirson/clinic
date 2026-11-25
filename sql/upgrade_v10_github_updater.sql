SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

-- =====================================================
-- UPGRADE V10 - VERSIONAMENTO E ATUALIZAÇÕES VIA GITHUB
-- =====================================================

-- Mantém a versão lógica do sistema sincronizada
INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'app_versao', '1.0.1'
WHERE NOT EXISTS (
    SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'app_versao'
);

UPDATE `configuracoes`
SET `valor` = '1.0.1'
WHERE `empresa_id` = 1 AND `chave` = 'app_versao';

-- Configurações padrão do repositório de atualização
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

INSERT INTO `configuracoes` (`empresa_id`, `chave`, `valor`)
SELECT 1, 'github_last_status', 'pendente'
WHERE NOT EXISTS (
    SELECT 1 FROM `configuracoes` WHERE `empresa_id` = 1 AND `chave` = 'github_last_status'
);

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;
