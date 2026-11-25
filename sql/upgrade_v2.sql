SET NAMES utf8mb4;
-- =====================================================
-- ClinicApp v2.0 - UPGRADE Script
-- Adiciona: Financeiro, BI, 2FA, PWA, Backup, Importação,
-- Plano de Tratamento, Odontograma Avançado, Telemedicina,
-- CID-10, Exames, Prescrição Eletrônica, Assinatura Digital
-- Execute APÓS o database.sql original
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ========== FINANCEIRO ==========
CREATE TABLE IF NOT EXISTS `categorias_financeiras` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `nome` VARCHAR(100) NOT NULL,
  `tipo` ENUM('receita','despesa') NOT NULL,
  `cor` VARCHAR(20) DEFAULT '#0ea5e9',
  `ativo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contas_financeiras` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `categoria_id` INT(11) DEFAULT NULL,
  `paciente_id` INT(11) DEFAULT NULL,
  `agendamento_id` INT(11) DEFAULT NULL,
  `nfse_id` INT(11) DEFAULT NULL,
  `tipo` ENUM('receita','despesa') NOT NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL,
  `data_vencimento` DATE NOT NULL,
  `data_pagamento` DATE DEFAULT NULL,
  `forma_pagamento` ENUM('dinheiro','pix','cartao_debito','cartao_credito','boleto','transferencia','cheque','outros') DEFAULT 'dinheiro',
  `status` ENUM('pendente','pago','atrasado','cancelado') DEFAULT 'pendente',
  `parcela_numero` INT(11) DEFAULT 1,
  `parcela_total` INT(11) DEFAULT 1,
  `parcela_grupo` VARCHAR(36) DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `criado_por` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fin_data` (`data_vencimento`),
  KEY `idx_fin_status` (`status`),
  KEY `idx_fin_paciente` (`paciente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== 2FA ==========
ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `mfa_metodo` ENUM('nenhum','email','totp') DEFAULT 'nenhum' AFTER `tema_preferido`,
  ADD COLUMN IF NOT EXISTS `mfa_secret` VARCHAR(64) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `mfa_codigos_recuperacao` TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `mfa_codigo_temp` VARCHAR(10) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `mfa_codigo_expira` DATETIME DEFAULT NULL;

-- ========== PLANO DE TRATAMENTO ODONTOLÓGICO ==========
CREATE TABLE IF NOT EXISTS `planos_tratamento` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  `valor_total` DECIMAL(10,2) DEFAULT 0.00,
  `desconto` DECIMAL(10,2) DEFAULT 0.00,
  `valor_final` DECIMAL(10,2) DEFAULT 0.00,
  `parcelas` INT(11) DEFAULT 1,
  `status` ENUM('orcamento','aprovado','em_execucao','concluido','cancelado') DEFAULT 'orcamento',
  `aprovado_em` DATETIME DEFAULT NULL,
  `aprovado_assinatura` TEXT DEFAULT NULL,
  `validade` DATE DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pt_paciente` (`paciente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `plano_tratamento_itens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `plano_id` INT(11) NOT NULL,
  `procedimento_id` INT(11) DEFAULT NULL,
  `dente` VARCHAR(10) DEFAULT NULL,
  `face` VARCHAR(20) DEFAULT NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `quantidade` INT(11) DEFAULT 1,
  `valor_unitario` DECIMAL(10,2) NOT NULL,
  `valor_total` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pendente','em_andamento','concluido','cancelado') DEFAULT 'pendente',
  `executado_em` DATE DEFAULT NULL,
  `executado_por` INT(11) DEFAULT NULL,
  `ordem` INT(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pti_plano` (`plano_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== ODONTOGRAMA AVANÇADO ==========
ALTER TABLE `odontograma`
  ADD COLUMN IF NOT EXISTS `tipo_denticao` ENUM('permanente','deciduo','misto') DEFAULT 'permanente' AFTER `face`,
  ADD COLUMN IF NOT EXISTS `tratamento_planejado` TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `valor_estimado` DECIMAL(10,2) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `odontograma_historico` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` INT(11) NOT NULL,
  `dente` VARCHAR(5) NOT NULL,
  `face` VARCHAR(20) DEFAULT NULL,
  `condicao_anterior` VARCHAR(50) DEFAULT NULL,
  `condicao_nova` VARCHAR(50) DEFAULT NULL,
  `observacao` TEXT DEFAULT NULL,
  `profissional_id` INT(11) DEFAULT NULL,
  `data_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_odh_paciente` (`paciente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== TELEMEDICINA (MÉDICO) ==========
CREATE TABLE IF NOT EXISTS `teleconsultas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `agendamento_id` INT(11) NOT NULL,
  `sala_id` VARCHAR(100) NOT NULL,
  `link_paciente` VARCHAR(500) DEFAULT NULL,
  `link_profissional` VARCHAR(500) DEFAULT NULL,
  `iniciada_em` DATETIME DEFAULT NULL,
  `encerrada_em` DATETIME DEFAULT NULL,
  `gravacao_url` VARCHAR(500) DEFAULT NULL,
  `consentimento_paciente` TINYINT(1) DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ag` (`agendamento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `agendamentos`
  ADD COLUMN IF NOT EXISTS `modalidade` ENUM('presencial','telemedicina') DEFAULT 'presencial' AFTER `status`;

-- ========== CID-10 / CIAP-2 (MÉDICO) ==========
CREATE TABLE IF NOT EXISTS `cid10` (
  `codigo` VARCHAR(10) NOT NULL,
  `descricao` VARCHAR(500) NOT NULL,
  `categoria` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`codigo`),
  KEY `idx_cid_desc` (`descricao`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paciente_diagnosticos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` INT(11) NOT NULL,
  `prontuario_id` INT(11) DEFAULT NULL,
  `cid_codigo` VARCHAR(10) DEFAULT NULL,
  `descricao` VARCHAR(500) DEFAULT NULL,
  `tipo` ENUM('principal','secundario','suspeita') DEFAULT 'principal',
  `data_diagnostico` DATE NOT NULL,
  `profissional_id` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_diag_paciente` (`paciente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== EXAMES (MÉDICO) ==========
CREATE TABLE IF NOT EXISTS `exames_modelos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL DEFAULT 1,
  `nome` VARCHAR(200) NOT NULL,
  `categoria` VARCHAR(100) DEFAULT NULL,
  `exames` TEXT NOT NULL,
  `instrucoes` TEXT DEFAULT NULL,
  `ativo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `solicitacoes_exame` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` INT(11) NOT NULL,
  `profissional_id` INT(11) NOT NULL,
  `prontuario_id` INT(11) DEFAULT NULL,
  `exames` TEXT NOT NULL,
  `indicacao_clinica` TEXT DEFAULT NULL,
  `cid_codigo` VARCHAR(10) DEFAULT NULL,
  `urgencia` ENUM('rotina','urgente','emergencia') DEFAULT 'rotina',
  `data_solicitacao` DATE NOT NULL,
  `status` ENUM('solicitado','realizado','recebido','cancelado') DEFAULT 'solicitado',
  `arquivo_resultado` VARCHAR(255) DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_se_paciente` (`paciente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== PRESCRIÇÃO ELETRÔNICA (MÉDICO) ==========
ALTER TABLE `receitas`
  ADD COLUMN IF NOT EXISTS `assinatura_digital` LONGTEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `assinatura_certificado` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `qr_code` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `hash_documento` VARCHAR(128) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `memed_id` VARCHAR(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `memed_url` VARCHAR(500) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `medicamentos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(200) NOT NULL,
  `apresentacao` VARCHAR(200) DEFAULT NULL,
  `principio_ativo` VARCHAR(200) DEFAULT NULL,
  `tipo` ENUM('simples','controlada_b','controlada_a','antibiotico','outros') DEFAULT 'simples',
  PRIMARY KEY (`id`),
  KEY `idx_med_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== BACKUP ==========
CREATE TABLE IF NOT EXISTS `backups` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tipo` ENUM('manual','automatico') DEFAULT 'manual',
  `arquivo` VARCHAR(255) NOT NULL,
  `tamanho_bytes` BIGINT(20) DEFAULT 0,
  `status` ENUM('em_andamento','concluido','erro') DEFAULT 'em_andamento',
  `erro` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `concluido_em` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== IMPORTAÇÃO ==========
CREATE TABLE IF NOT EXISTS `importacoes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo` ENUM('pacientes','agendamentos','procedimentos') NOT NULL,
  `arquivo` VARCHAR(255) NOT NULL,
  `total_linhas` INT(11) DEFAULT 0,
  `sucesso` INT(11) DEFAULT 0,
  `erros` INT(11) DEFAULT 0,
  `log` LONGTEXT DEFAULT NULL,
  `status` ENUM('processando','concluido','erro') DEFAULT 'processando',
  `criado_por` INT(11) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== DADOS INICIAIS ==========

-- Categorias financeiras padrão
INSERT IGNORE INTO `categorias_financeiras` (`empresa_id`,`nome`,`tipo`,`cor`) VALUES
(1,'Consultas','receita','#10b981'),
(1,'Procedimentos','receita','#0ea5e9'),
(1,'Convênios','receita','#8b5cf6'),
(1,'Aluguel','despesa','#ef4444'),
(1,'Salários','despesa','#f59e0b'),
(1,'Materiais Clínicos','despesa','#ec4899'),
(1,'Equipamentos','despesa','#6366f1'),
(1,'Marketing','despesa','#14b8a6'),
(1,'Impostos','despesa','#dc2626'),
(1,'Outros','despesa','#64748b');

-- Modelos de exames
INSERT IGNORE INTO `exames_modelos` (`empresa_id`,`nome`,`categoria`,`exames`,`instrucoes`) VALUES
(1,'Check-up Básico','Rotina','Hemograma completo\nGlicose em jejum\nColesterol total e frações\nTriglicerídeos\nTGO/TGP\nUreia/Creatinina\nTSH\nExame de urina (EAS)','Jejum de 12 horas. Trazer documento com foto.'),
(1,'Perfil Lipídico','Cardiologia','Colesterol total\nHDL\nLDL\nVLDL\nTriglicerídeos','Jejum de 12 horas.'),
(1,'Função Hepática','Hepatologia','TGO (AST)\nTGP (ALT)\nGGT\nFosfatase alcalina\nBilirrubinas total e frações\nProteínas totais e frações','Jejum de 8 horas.'),
(1,'Função Renal','Nefrologia','Ureia\nCreatinina\nClearance de creatinina\nEAS\nMicroalbuminúria','Coletar primeira urina da manhã.'),
(1,'Hormônios Tireoide','Endocrinologia','TSH\nT4 livre\nT3\nAnti-TPO\nAnti-tireoglobulina','Não necessita jejum.'),
(1,'Pré-natal 1º Trimestre','Ginecologia','Hemograma completo\nTipagem sanguínea + Rh\nGlicemia jejum\nVDRL\nAnti-HIV\nHBsAg\nAnti-HCV\nToxoplasmose IgG e IgM\nRubéola IgG e IgM\nCMV IgG e IgM\nEAS\nUrocultura','Jejum de 8 horas para glicemia.');

-- CID-10 — Top 200 mais usados em clínica geral
INSERT IGNORE INTO `cid10` (`codigo`,`descricao`,`categoria`) VALUES
('A09','Diarreia e gastroenterite de origem infecciosa presumível','Infecciosas'),
('A90','Dengue clássica','Infecciosas'),
('A91','Febre hemorrágica devida ao vírus do dengue','Infecciosas'),
('B01','Varicela','Infecciosas'),
('B05','Sarampo','Infecciosas'),
('B26','Caxumba','Infecciosas'),
('B34','Doença viral não especificada','Infecciosas'),
('B35','Dermatofitose','Pele'),
('B37','Candidíase','Infecciosas'),
('B86','Escabiose','Pele'),
('C50','Neoplasia maligna da mama','Oncologia'),
('D50','Anemia por deficiência de ferro','Hematologia'),
('D64','Outras anemias','Hematologia'),
('E03','Outros hipotireoidismos','Endócrino'),
('E05','Tireotoxicose [hipertireoidismo]','Endócrino'),
('E10','Diabetes mellitus tipo 1','Endócrino'),
('E11','Diabetes mellitus tipo 2','Endócrino'),
('E14','Diabetes mellitus não especificado','Endócrino'),
('E66','Obesidade','Endócrino'),
('E78','Distúrbios do metabolismo de lipoproteínas','Endócrino'),
('F32','Episódios depressivos','Mental'),
('F33','Transtorno depressivo recorrente','Mental'),
('F41','Outros transtornos ansiosos','Mental'),
('F41.0','Transtorno de pânico','Mental'),
('F41.1','Ansiedade generalizada','Mental'),
('F43','Reações ao estresse grave e transtornos de adaptação','Mental'),
('F51','Transtornos não-orgânicos do sono','Mental'),
('G43','Enxaqueca','Neurologia'),
('G44','Outras síndromes de algias cefálicas','Neurologia'),
('G47','Distúrbios do sono','Neurologia'),
('H10','Conjuntivite','Oftalmologia'),
('H52','Transtornos da refração e da acomodação','Oftalmologia'),
('H66','Otite média supurativa','Otorrino'),
('H81','Transtornos da função vestibular','Otorrino'),
('I10','Hipertensão essencial','Cardiologia'),
('I11','Doença cardíaca hipertensiva','Cardiologia'),
('I15','Hipertensão secundária','Cardiologia'),
('I20','Angina pectoris','Cardiologia'),
('I21','Infarto agudo do miocárdio','Cardiologia'),
('I25','Doença isquêmica crônica do coração','Cardiologia'),
('I48','Fibrilação e flutter atrial','Cardiologia'),
('I50','Insuficiência cardíaca','Cardiologia'),
('I63','Infarto cerebral','Neurologia'),
('I83','Varizes dos membros inferiores','Vascular'),
('I84','Hemorroidas','Gastro'),
('J00','Nasofaringite aguda [resfriado comum]','Respiratório'),
('J01','Sinusite aguda','Respiratório'),
('J02','Faringite aguda','Respiratório'),
('J03','Amigdalite aguda','Respiratório'),
('J04','Laringite e traqueíte agudas','Respiratório'),
('J06','Infecções agudas das vias aéreas superiores','Respiratório'),
('J11','Influenza [gripe]','Respiratório'),
('J18','Pneumonia','Respiratório'),
('J20','Bronquite aguda','Respiratório'),
('J30','Rinite alérgica','Respiratório'),
('J32','Sinusite crônica','Respiratório'),
('J35','Doenças crônicas das amígdalas e adenoides','Respiratório'),
('J40','Bronquite não especificada','Respiratório'),
('J44','Doença pulmonar obstrutiva crônica','Respiratório'),
('J45','Asma','Respiratório'),
('K02','Cárie dentária','Odontologia'),
('K04','Doenças da polpa e dos tecidos periapicais','Odontologia'),
('K05','Gengivite e doenças periodontais','Odontologia'),
('K08','Outros transtornos dos dentes e estruturas de sustentação','Odontologia'),
('K21','Doença de refluxo gastroesofágico','Gastro'),
('K25','Úlcera gástrica','Gastro'),
('K29','Gastrite e duodenite','Gastro'),
('K30','Dispepsia','Gastro'),
('K35','Apendicite aguda','Gastro'),
('K52','Outras gastroenterites e colites não infecciosas','Gastro'),
('K57','Doença diverticular do intestino','Gastro'),
('K59','Outros transtornos funcionais do intestino','Gastro'),
('K80','Colelitíase','Gastro'),
('K81','Colecistite','Gastro'),
('L20','Dermatite atópica','Pele'),
('L23','Dermatite alérgica de contato','Pele'),
('L29','Prurido','Pele'),
('L30','Outras dermatites','Pele'),
('L40','Psoríase','Pele'),
('L50','Urticária','Pele'),
('L70','Acne','Pele'),
('M05','Artrite reumatoide soropositiva','Reumatologia'),
('M10','Gota','Reumatologia'),
('M15','Poliartrose','Reumatologia'),
('M17','Gonartrose [artrose do joelho]','Reumatologia'),
('M25','Outros transtornos articulares','Reumatologia'),
('M40','Cifose e lordose','Ortopedia'),
('M41','Escoliose','Ortopedia'),
('M42','Osteocondrose da coluna vertebral','Ortopedia'),
('M47','Espondilose','Ortopedia'),
('M51','Outros transtornos de discos intervertebrais','Ortopedia'),
('M54','Dorsalgia','Ortopedia'),
('M54.2','Cervicalgia','Ortopedia'),
('M54.5','Dor lombar baixa','Ortopedia'),
('M62','Outros transtornos musculares','Ortopedia'),
('M65','Sinovite e tenossinovite','Ortopedia'),
('M70','Transtornos dos tecidos moles relacionados com o uso','Ortopedia'),
('M75','Lesões do ombro','Ortopedia'),
('M77','Outras entesopatias','Ortopedia'),
('M79','Outros transtornos dos tecidos moles','Ortopedia'),
('M81','Osteoporose','Ortopedia'),
('N18','Doença renal crônica','Nefrologia'),
('N20','Calculose do rim e do ureter','Urologia'),
('N30','Cistite','Urologia'),
('N39','Outros transtornos do trato urinário','Urologia'),
('N40','Hiperplasia da próstata','Urologia'),
('N76','Outras afecções inflamatórias da vagina e da vulva','Ginecologia'),
('N91','Menstruação ausente, escassa ou rara','Ginecologia'),
('N92','Menstruação excessiva, frequente ou irregular','Ginecologia'),
('N94','Dor associada com órgãos genitais femininos','Ginecologia'),
('N95','Transtornos da menopausa','Ginecologia'),
('O00','Gravidez ectópica','Gineco-Obstetrícia'),
('O10','Hipertensão pré-existente complicando gravidez','Gineco-Obstetrícia'),
('O20','Hemorragia do início da gravidez','Gineco-Obstetrícia'),
('O24','Diabetes mellitus na gravidez','Gineco-Obstetrícia'),
('R05','Tosse','Sintomas'),
('R06','Anormalidades da respiração','Sintomas'),
('R07','Dor de garganta e no peito','Sintomas'),
('R10','Dor abdominal','Sintomas'),
('R11','Náusea e vômitos','Sintomas'),
('R19','Outros sintomas e sinais do aparelho digestivo','Sintomas'),
('R20','Distúrbios da sensibilidade cutânea','Sintomas'),
('R21','Eritema e outras erupções cutâneas não específicas','Sintomas'),
('R30','Dor associada à micção','Sintomas'),
('R31','Hematúria não específica','Sintomas'),
('R42','Tontura e instabilidade','Sintomas'),
('R50','Febre não especificada','Sintomas'),
('R51','Cefaleia','Sintomas'),
('R52','Dor não classificada em outra parte','Sintomas'),
('R53','Mal estar e fadiga','Sintomas'),
('R55','Síncope e colapso','Sintomas'),
('R56','Convulsões','Sintomas'),
('R60','Edema','Sintomas'),
('R63','Sintomas relacionados à ingestão de alimentos','Sintomas'),
('S00','Traumatismo superficial da cabeça','Trauma'),
('S06','Traumatismo intracraniano','Trauma'),
('S40','Traumatismo superficial do ombro e do braço','Trauma'),
('S52','Fratura do antebraço','Trauma'),
('S60','Traumatismo superficial do punho e da mão','Trauma'),
('S70','Traumatismo superficial do quadril e da coxa','Trauma'),
('S72','Fratura do fêmur','Trauma'),
('S80','Traumatismo superficial da perna','Trauma'),
('S82','Fratura da perna, incluindo tornozelo','Trauma'),
('S90','Traumatismo superficial do tornozelo e do pé','Trauma'),
('T14','Traumatismo de região não especificada do corpo','Trauma'),
('T78','Efeitos adversos não classificados em outra parte','Outros'),
('Z00','Exame geral e investigação de pessoas sem queixas','Preventivo'),
('Z01','Outros exames especiais e investigações','Preventivo'),
('Z02','Exame médico e consulta para fins administrativos','Preventivo'),
('Z03','Observação e avaliação médica para suspeitas','Preventivo'),
('Z11','Exame especial de rastreamento para doenças infecciosas','Preventivo'),
('Z12','Exame especial de rastreamento para neoplasias','Preventivo'),
('Z13','Exame especial de rastreamento de outras doenças','Preventivo'),
('Z23','Necessidade de imunização contra doenças bacterianas','Preventivo'),
('Z30','Anticoncepção','Preventivo'),
('Z34','Supervisão de gravidez normal','Preventivo'),
('Z35','Supervisão de gravidez de alto risco','Preventivo'),
('Z39','Exame e cuidados pós-natais','Preventivo'),
('Z71','Aconselhamento médico não classificado em outra parte','Preventivo'),
('Z76','Pessoas em contato com serviços de saúde','Preventivo');

-- Medicamentos comuns
INSERT IGNORE INTO `medicamentos` (`nome`,`apresentacao`,`principio_ativo`,`tipo`) VALUES
('Dipirona Sódica','500mg comprimido','Dipirona','simples'),
('Paracetamol','500mg comprimido','Paracetamol','simples'),
('Paracetamol Gotas','200mg/mL solução oral','Paracetamol','simples'),
('Ibuprofeno','400mg comprimido','Ibuprofeno','simples'),
('Ibuprofeno','600mg comprimido','Ibuprofeno','simples'),
('Nimesulida','100mg comprimido','Nimesulida','simples'),
('Diclofenaco','50mg comprimido','Diclofenaco sódico','simples'),
('Amoxicilina','500mg cápsula','Amoxicilina','antibiotico'),
('Amoxicilina + Clavulanato','875mg + 125mg comprimido','Amoxicilina/Clavulanato','antibiotico'),
('Azitromicina','500mg comprimido','Azitromicina','antibiotico'),
('Cefalexina','500mg cápsula','Cefalexina','antibiotico'),
('Ciprofloxacino','500mg comprimido','Ciprofloxacino','antibiotico'),
('Omeprazol','20mg cápsula','Omeprazol','simples'),
('Pantoprazol','40mg comprimido','Pantoprazol','simples'),
('Ranitidina','150mg comprimido','Ranitidina','simples'),
('Bromoprida','10mg comprimido','Bromoprida','simples'),
('Metoclopramida','10mg comprimido','Metoclopramida','simples'),
('Loratadina','10mg comprimido','Loratadina','simples'),
('Desloratadina','5mg comprimido','Desloratadina','simples'),
('Cetirizina','10mg comprimido','Cetirizina','simples'),
('Losartana','50mg comprimido','Losartana potássica','simples'),
('Losartana','100mg comprimido','Losartana potássica','simples'),
('Captopril','25mg comprimido','Captopril','simples'),
('Enalapril','10mg comprimido','Maleato de enalapril','simples'),
('Hidroclorotiazida','25mg comprimido','Hidroclorotiazida','simples'),
('Atenolol','50mg comprimido','Atenolol','simples'),
('Propranolol','40mg comprimido','Propranolol','simples'),
('Sinvastatina','20mg comprimido','Sinvastatina','simples'),
('Sinvastatina','40mg comprimido','Sinvastatina','simples'),
('Atorvastatina','20mg comprimido','Atorvastatina cálcica','simples'),
('Metformina','500mg comprimido','Cloridrato de metformina','simples'),
('Metformina','850mg comprimido','Cloridrato de metformina','simples'),
('Glibenclamida','5mg comprimido','Glibenclamida','simples'),
('Levotiroxina','50mcg comprimido','Levotiroxina sódica','simples'),
('Levotiroxina','100mcg comprimido','Levotiroxina sódica','simples'),
('Fluoxetina','20mg cápsula','Cloridrato de fluoxetina','controlada_b'),
('Sertralina','50mg comprimido','Cloridrato de sertralina','controlada_b'),
('Escitalopram','10mg comprimido','Oxalato de escitalopram','controlada_b'),
('Clonazepam','2mg comprimido','Clonazepam','controlada_b'),
('Alprazolam','0,5mg comprimido','Alprazolam','controlada_b'),
('Diazepam','10mg comprimido','Diazepam','controlada_b'),
('Tramadol','50mg cápsula','Cloridrato de tramadol','controlada_a'),
('Codeína + Paracetamol','30mg + 500mg comprimido','Codeína/Paracetamol','controlada_a'),
('Dexametasona','4mg comprimido','Dexametasona','simples'),
('Prednisona','20mg comprimido','Prednisona','simples'),
('Salbutamol','100mcg/dose aerossol','Sulfato de salbutamol','simples'),
('Budesonida','200mcg/dose pó inalatório','Budesonida','simples');

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- FIM DO UPGRADE v2.0
-- =====================================================
