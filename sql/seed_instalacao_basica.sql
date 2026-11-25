-- ClinicApp 1.2.0 — carga mínima para instalação sem dados clínicos
-- Uso exclusivo: banco NOVO, estrutura consolidada até v14.
-- Não importar em banco de produção existente. Não contém DELETE, DROP de tabelas reais nem TRUNCATE.
-- Uma empresa. Todos os nomes, pacientes e registros clínicos são fictícios.
-- Demo: admin.demo@clinicapp.com / 123456; profissionais também 123456. Troque as senhas.
SET NAMES utf8mb4;
SET @CLINIC_OLD_SQL_MODE = @@SESSION.SQL_MODE;
SET SESSION SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
CREATE TEMPORARY TABLE clinicapp_seed_guard (ok TINYINT NOT NULL PRIMARY KEY);
INSERT INTO clinicapp_seed_guard VALUES (1);
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `agenda_fila_espera` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `agendamentos` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `anexos` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `backups` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `bloqueios_horario` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `cardiologia_prontuario` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `categorias_financeiras` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `cid10` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `configuracoes` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `contas_financeiras` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `empresas` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `exames_modelos` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `ginecologia_obstetricia_prontuario` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `importacoes` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `licencas` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `logs` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `medicamentos` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `modelos_clinicos` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `neurologia_prontuario` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `nfse` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `notificacoes_sistema` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `odontograma` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `odontograma_historico` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `oftalmologia_prontuario` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `ortopedia_traumatologia_prontuario` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `paciente_alertas` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `paciente_consentimentos` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `paciente_diagnosticos` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `pacientes` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `pediatria_prontuario` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `plano_tratamento_itens` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `planos_cuidado` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `planos_tratamento` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `procedimentos` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `prontuarios` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `protocolos_clinicos` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `psicologia_escalas` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `psicologia_planos` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `psicologia_prontuario` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `psiquiatria_prontuario` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `receitas` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `solicitacoes_exame` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `teleconsultas` LIMIT 1;
INSERT INTO clinicapp_seed_guard SELECT 1 FROM `usuarios` LIMIT 1;
DROP TEMPORARY TABLE clinicapp_seed_guard;
SET @CLINIC_OLD_FK = @@SESSION.FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS=0;
START TRANSACTION;

-- categorias_financeiras: 10 registros
INSERT INTO `categorias_financeiras` (`id`,`empresa_id`,`nome`,`tipo`,`cor`,`ativo`) VALUES
(1,1,'Consultas','receita','#10b981',1),
(2,1,'Procedimentos','receita','#0ea5e9',1),
(3,1,'Convênios','receita','#8b5cf6',1),
(4,1,'Aluguel','despesa','#ef4444',1),
(5,1,'Salários','despesa','#f59e0b',1),
(6,1,'Materiais Clínicos','despesa','#ec4899',1),
(7,1,'Equipamentos','despesa','#6366f1',1),
(8,1,'Marketing','despesa','#14b8a6',1),
(9,1,'Impostos','despesa','#dc2626',1),
(10,1,'Outros','despesa','#64748b',1);

-- cid10: 160 registros
INSERT INTO `cid10` (`empresa_id`,`codigo`,`descricao`,`categoria`) VALUES
(1,'A09','Diarreia e gastroenterite de origem infecciosa presumível','Infecciosas'),
(1,'A90','Dengue clássica','Infecciosas'),
(1,'A91','Febre hemorrágica devida ao vírus do dengue','Infecciosas'),
(1,'B01','Varicela','Infecciosas'),
(1,'B05','Sarampo','Infecciosas'),
(1,'B26','Caxumba','Infecciosas'),
(1,'B34','Doença viral não especificada','Infecciosas'),
(1,'B35','Dermatofitose','Pele'),
(1,'B37','Candidíase','Infecciosas'),
(1,'B86','Escabiose','Pele'),
(1,'C50','Neoplasia maligna da mama','Oncologia'),
(1,'D50','Anemia por deficiência de ferro','Hematologia'),
(1,'D64','Outras anemias','Hematologia'),
(1,'E03','Outros hipotireoidismos','Endócrino'),
(1,'E05','Tireotoxicose [hipertireoidismo]','Endócrino'),
(1,'E10','Diabetes mellitus tipo 1','Endócrino'),
(1,'E11','Diabetes mellitus tipo 2','Endócrino'),
(1,'E14','Diabetes mellitus não especificado','Endócrino'),
(1,'E66','Obesidade','Endócrino'),
(1,'E78','Distúrbios do metabolismo de lipoproteínas','Endócrino'),
(1,'F32','Episódios depressivos','Mental'),
(1,'F33','Transtorno depressivo recorrente','Mental'),
(1,'F41','Outros transtornos ansiosos','Mental'),
(1,'F41.0','Transtorno de pânico','Mental'),
(1,'F41.1','Ansiedade generalizada','Mental'),
(1,'F43','Reações ao estresse grave e transtornos de adaptação','Mental'),
(1,'F51','Transtornos não-orgânicos do sono','Mental'),
(1,'G43','Enxaqueca','Neurologia'),
(1,'G44','Outras síndromes de algias cefálicas','Neurologia'),
(1,'G47','Distúrbios do sono','Neurologia'),
(1,'H10','Conjuntivite','Oftalmologia'),
(1,'H52','Transtornos da refração e da acomodação','Oftalmologia'),
(1,'H66','Otite média supurativa','Otorrino'),
(1,'H81','Transtornos da função vestibular','Otorrino'),
(1,'I10','Hipertensão essencial','Cardiologia'),
(1,'I11','Doença cardíaca hipertensiva','Cardiologia'),
(1,'I15','Hipertensão secundária','Cardiologia'),
(1,'I20','Angina pectoris','Cardiologia'),
(1,'I21','Infarto agudo do miocárdio','Cardiologia'),
(1,'I25','Doença isquêmica crônica do coração','Cardiologia');
INSERT INTO `cid10` (`empresa_id`,`codigo`,`descricao`,`categoria`) VALUES
(1,'I48','Fibrilação e flutter atrial','Cardiologia'),
(1,'I50','Insuficiência cardíaca','Cardiologia'),
(1,'I63','Infarto cerebral','Neurologia'),
(1,'I83','Varizes dos membros inferiores','Vascular'),
(1,'I84','Hemorroidas','Gastro'),
(1,'J00','Nasofaringite aguda [resfriado comum]','Respiratório'),
(1,'J01','Sinusite aguda','Respiratório'),
(1,'J02','Faringite aguda','Respiratório'),
(1,'J03','Amigdalite aguda','Respiratório'),
(1,'J04','Laringite e traqueíte agudas','Respiratório'),
(1,'J06','Infecções agudas das vias aéreas superiores','Respiratório'),
(1,'J11','Influenza [gripe]','Respiratório'),
(1,'J18','Pneumonia','Respiratório'),
(1,'J20','Bronquite aguda','Respiratório'),
(1,'J30','Rinite alérgica','Respiratório'),
(1,'J32','Sinusite crônica','Respiratório'),
(1,'J35','Doenças crônicas das amígdalas e adenoides','Respiratório'),
(1,'J40','Bronquite não especificada','Respiratório'),
(1,'J44','Doença pulmonar obstrutiva crônica','Respiratório'),
(1,'J45','Asma','Respiratório'),
(1,'K02','Cárie dentária','Odontologia'),
(1,'K04','Doenças da polpa e dos tecidos periapicais','Odontologia'),
(1,'K05','Gengivite e doenças periodontais','Odontologia'),
(1,'K08','Outros transtornos dos dentes e estruturas de sustentação','Odontologia'),
(1,'K21','Doença de refluxo gastroesofágico','Gastro'),
(1,'K25','Úlcera gástrica','Gastro'),
(1,'K29','Gastrite e duodenite','Gastro'),
(1,'K30','Dispepsia','Gastro'),
(1,'K35','Apendicite aguda','Gastro'),
(1,'K52','Outras gastroenterites e colites não infecciosas','Gastro'),
(1,'K57','Doença diverticular do intestino','Gastro'),
(1,'K59','Outros transtornos funcionais do intestino','Gastro'),
(1,'K80','Colelitíase','Gastro'),
(1,'K81','Colecistite','Gastro'),
(1,'L20','Dermatite atópica','Pele'),
(1,'L23','Dermatite alérgica de contato','Pele'),
(1,'L29','Prurido','Pele'),
(1,'L30','Outras dermatites','Pele'),
(1,'L40','Psoríase','Pele'),
(1,'L50','Urticária','Pele');
INSERT INTO `cid10` (`empresa_id`,`codigo`,`descricao`,`categoria`) VALUES
(1,'L70','Acne','Pele'),
(1,'M05','Artrite reumatoide soropositiva','Reumatologia'),
(1,'M10','Gota','Reumatologia'),
(1,'M15','Poliartrose','Reumatologia'),
(1,'M17','Gonartrose [artrose do joelho]','Reumatologia'),
(1,'M25','Outros transtornos articulares','Reumatologia'),
(1,'M40','Cifose e lordose','Ortopedia'),
(1,'M41','Escoliose','Ortopedia'),
(1,'M42','Osteocondrose da coluna vertebral','Ortopedia'),
(1,'M47','Espondilose','Ortopedia'),
(1,'M51','Outros transtornos de discos intervertebrais','Ortopedia'),
(1,'M54','Dorsalgia','Ortopedia'),
(1,'M54.2','Cervicalgia','Ortopedia'),
(1,'M54.5','Dor lombar baixa','Ortopedia'),
(1,'M62','Outros transtornos musculares','Ortopedia'),
(1,'M65','Sinovite e tenossinovite','Ortopedia'),
(1,'M70','Transtornos dos tecidos moles relacionados com o uso','Ortopedia'),
(1,'M75','Lesões do ombro','Ortopedia'),
(1,'M77','Outras entesopatias','Ortopedia'),
(1,'M79','Outros transtornos dos tecidos moles','Ortopedia'),
(1,'M81','Osteoporose','Ortopedia'),
(1,'N18','Doença renal crônica','Nefrologia'),
(1,'N20','Calculose do rim e do ureter','Urologia'),
(1,'N30','Cistite','Urologia'),
(1,'N39','Outros transtornos do trato urinário','Urologia'),
(1,'N40','Hiperplasia da próstata','Urologia'),
(1,'N76','Outras afecções inflamatórias da vagina e da vulva','Ginecologia'),
(1,'N91','Menstruação ausente, escassa ou rara','Ginecologia'),
(1,'N92','Menstruação excessiva, frequente ou irregular','Ginecologia'),
(1,'N94','Dor associada com órgãos genitais femininos','Ginecologia'),
(1,'N95','Transtornos da menopausa','Ginecologia'),
(1,'O00','Gravidez ectópica','Gineco-Obstetrícia'),
(1,'O10','Hipertensão pré-existente complicando gravidez','Gineco-Obstetrícia'),
(1,'O20','Hemorragia do início da gravidez','Gineco-Obstetrícia'),
(1,'O24','Diabetes mellitus na gravidez','Gineco-Obstetrícia'),
(1,'R05','Tosse','Sintomas'),
(1,'R06','Anormalidades da respiração','Sintomas'),
(1,'R07','Dor de garganta e no peito','Sintomas'),
(1,'R10','Dor abdominal','Sintomas'),
(1,'R11','Náusea e vômitos','Sintomas');
INSERT INTO `cid10` (`empresa_id`,`codigo`,`descricao`,`categoria`) VALUES
(1,'R19','Outros sintomas e sinais do aparelho digestivo','Sintomas'),
(1,'R20','Distúrbios da sensibilidade cutânea','Sintomas'),
(1,'R21','Eritema e outras erupções cutâneas não específicas','Sintomas'),
(1,'R30','Dor associada à micção','Sintomas'),
(1,'R31','Hematúria não específica','Sintomas'),
(1,'R42','Tontura e instabilidade','Sintomas'),
(1,'R50','Febre não especificada','Sintomas'),
(1,'R51','Cefaleia','Sintomas'),
(1,'R52','Dor não classificada em outra parte','Sintomas'),
(1,'R53','Mal estar e fadiga','Sintomas'),
(1,'R55','Síncope e colapso','Sintomas'),
(1,'R56','Convulsões','Sintomas'),
(1,'R60','Edema','Sintomas'),
(1,'R63','Sintomas relacionados à ingestão de alimentos','Sintomas'),
(1,'S00','Traumatismo superficial da cabeça','Trauma'),
(1,'S06','Traumatismo intracraniano','Trauma'),
(1,'S40','Traumatismo superficial do ombro e do braço','Trauma'),
(1,'S52','Fratura do antebraço','Trauma'),
(1,'S60','Traumatismo superficial do punho e da mão','Trauma'),
(1,'S70','Traumatismo superficial do quadril e da coxa','Trauma'),
(1,'S72','Fratura do fêmur','Trauma'),
(1,'S80','Traumatismo superficial da perna','Trauma'),
(1,'S82','Fratura da perna, incluindo tornozelo','Trauma'),
(1,'S90','Traumatismo superficial do tornozelo e do pé','Trauma'),
(1,'T14','Traumatismo de região não especificada do corpo','Trauma'),
(1,'T78','Efeitos adversos não classificados em outra parte','Outros'),
(1,'Z00','Exame geral e investigação de pessoas sem queixas','Preventivo'),
(1,'Z01','Outros exames especiais e investigações','Preventivo'),
(1,'Z02','Exame médico e consulta para fins administrativos','Preventivo'),
(1,'Z03','Observação e avaliação médica para suspeitas','Preventivo'),
(1,'Z11','Exame especial de rastreamento para doenças infecciosas','Preventivo'),
(1,'Z12','Exame especial de rastreamento para neoplasias','Preventivo'),
(1,'Z13','Exame especial de rastreamento de outras doenças','Preventivo'),
(1,'Z23','Necessidade de imunização contra doenças bacterianas','Preventivo'),
(1,'Z30','Anticoncepção','Preventivo'),
(1,'Z34','Supervisão de gravidez normal','Preventivo'),
(1,'Z35','Supervisão de gravidez de alto risco','Preventivo'),
(1,'Z39','Exame e cuidados pós-natais','Preventivo'),
(1,'Z71','Aconselhamento médico não classificado em outra parte','Preventivo'),
(1,'Z76','Pessoas em contato com serviços de saúde','Preventivo');

-- configuracoes: 16 registros
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES
(1,'app_nome','ClinicApp','2026-11-11 23:52:12'),
(1,'app_versao','1.2.0','2026-11-11 23:52:12'),
(1,'dias_atendimento','1,2,3,4,5','2026-11-11 23:52:12'),
(1,'github_branch','main','2026-11-11 23:52:12'),
(1,'github_last_status','pendente','2026-11-11 23:52:12'),
(1,'github_repo','leirson/Clinicapp','2026-11-11 23:52:12'),
(1,'google_calendar_enabled','1','2026-11-11 23:52:12'),
(1,'horario_fim','18:00','2026-11-11 23:52:12'),
(1,'horario_inicio','08:00','2026-11-11 23:52:12'),
(1,'intervalo_consulta','30','2026-11-11 23:52:12'),
(1,'push_minutes_before','60','2026-11-11 23:52:12'),
(1,'pwa_install_enabled','1','2026-11-11 23:52:12'),
(1,'reminder_email_enabled','1','2026-11-11 23:52:12'),
(1,'reminder_hours_before','24','2026-11-11 23:52:12'),
(1,'reminder_push_enabled','1','2026-11-11 23:52:12'),
(1,'reminder_window_minutes','30','2026-11-11 23:52:12');

-- empresas: 1 registros
INSERT INTO `empresas` (`id`,`razao_social`,`nome_fantasia`,`cnpj`,`inscricao_municipal`,`inscricao_estadual`,`cnae`,`item_lista_servico`,`codigo_tributacao_municipio`,`regime_tributario`,`optante_simples`,`incentivador_cultural`,`aliquota_iss`,`endereco_cep`,`endereco_logradouro`,`endereco_numero`,`endereco_complemento`,`endereco_bairro`,`endereco_cidade`,`endereco_uf`,`endereco_codigo_municipio`,`telefone`,`email`,`dominio_principal`,`ativo`,`tema_padrao`,`logo`,`asten_ambiente`,`asten_token`,`asten_usuario`,`asten_senha`,`asten_url_ws`,`asten_serie_rps`,`asten_proximo_rps`,`asten_certificado_a1`,`asten_certificado_senha`,`criado_em`,`atualizado_em`) VALUES
(1,'Clínica Demo LTDA','Clínica Demo','00.000.000/0001-00',NULL,NULL,NULL,NULL,NULL,'1',1,0,'0.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'medico',NULL,'homologacao',NULL,NULL,NULL,NULL,'1',1,NULL,NULL,'2026-11-11 23:52:12','2026-11-11 23:52:12');

-- exames_modelos: 6 registros
INSERT INTO `exames_modelos` (`id`,`empresa_id`,`nome`,`categoria`,`exames`,`instrucoes`,`ativo`) VALUES
(1,1,'Check-up Básico','Rotina','Hemograma completo\nGlicose em jejum\nColesterol total e frações\nTriglicerídeos\nTGO/TGP\nUreia/Creatinina\nTSH\nExame de urina (EAS)','Jejum de 12 horas. Trazer documento com foto.',1),
(2,1,'Perfil Lipídico','Cardiologia','Colesterol total\nHDL\nLDL\nVLDL\nTriglicerídeos','Jejum de 12 horas.',1),
(3,1,'Função Hepática','Hepatologia','TGO (AST)\nTGP (ALT)\nGGT\nFosfatase alcalina\nBilirrubinas total e frações\nProteínas totais e frações','Jejum de 8 horas.',1),
(4,1,'Função Renal','Nefrologia','Ureia\nCreatinina\nClearance de creatinina\nEAS\nMicroalbuminúria','Coletar primeira urina da manhã.',1),
(5,1,'Hormônios Tireoide','Endocrinologia','TSH\nT4 livre\nT3\nAnti-TPO\nAnti-tireoglobulina','Não necessita jejum.',1),
(6,1,'Pré-natal 1º Trimestre','Ginecologia','Hemograma completo\nTipagem sanguínea + Rh\nGlicemia jejum\nVDRL\nAnti-HIV\nHBsAg\nAnti-HCV\nToxoplasmose IgG e IgM\nRubéola IgG e IgM\nCMV IgG e IgM\nEAS\nUrocultura','Jejum de 8 horas para glicemia.',1);

-- licencas: 1 registros
INSERT INTO `licencas` (`id`,`empresa_id`,`plano`,`status`,`chave_licenca`,`validade_inicio`,`validade_fim`,`max_usuarios`,`max_pacientes`,`observacoes`,`criado_por`,`atualizado_por`,`criado_em`,`atualizado_em`) VALUES
(1,1,'premium','ativa','CLINICAPP-DEMO-001',CURDATE(),DATE_ADD(CURDATE(), INTERVAL 1 YEAR),25,10000,'Licença padrão inicial da empresa principal.',1,1,'2026-11-11 23:52:12','2026-11-11 23:52:12');

-- medicamentos: 47 registros
INSERT INTO `medicamentos` (`id`,`empresa_id`,`nome`,`apresentacao`,`principio_ativo`,`tipo`) VALUES
(1,1,'Dipirona Sódica','500mg comprimido','Dipirona','simples'),
(2,1,'Paracetamol','500mg comprimido','Paracetamol','simples'),
(3,1,'Paracetamol Gotas','200mg/mL solução oral','Paracetamol','simples'),
(4,1,'Ibuprofeno','400mg comprimido','Ibuprofeno','simples'),
(5,1,'Ibuprofeno','600mg comprimido','Ibuprofeno','simples'),
(6,1,'Nimesulida','100mg comprimido','Nimesulida','simples'),
(7,1,'Diclofenaco','50mg comprimido','Diclofenaco sódico','simples'),
(8,1,'Amoxicilina','500mg cápsula','Amoxicilina','antibiotico'),
(9,1,'Amoxicilina + Clavulanato','875mg + 125mg comprimido','Amoxicilina/Clavulanato','antibiotico'),
(10,1,'Azitromicina','500mg comprimido','Azitromicina','antibiotico'),
(11,1,'Cefalexina','500mg cápsula','Cefalexina','antibiotico'),
(12,1,'Ciprofloxacino','500mg comprimido','Ciprofloxacino','antibiotico'),
(13,1,'Omeprazol','20mg cápsula','Omeprazol','simples'),
(14,1,'Pantoprazol','40mg comprimido','Pantoprazol','simples'),
(15,1,'Ranitidina','150mg comprimido','Ranitidina','simples'),
(16,1,'Bromoprida','10mg comprimido','Bromoprida','simples'),
(17,1,'Metoclopramida','10mg comprimido','Metoclopramida','simples'),
(18,1,'Loratadina','10mg comprimido','Loratadina','simples'),
(19,1,'Desloratadina','5mg comprimido','Desloratadina','simples'),
(20,1,'Cetirizina','10mg comprimido','Cetirizina','simples'),
(21,1,'Losartana','50mg comprimido','Losartana potássica','simples'),
(22,1,'Losartana','100mg comprimido','Losartana potássica','simples'),
(23,1,'Captopril','25mg comprimido','Captopril','simples'),
(24,1,'Enalapril','10mg comprimido','Maleato de enalapril','simples'),
(25,1,'Hidroclorotiazida','25mg comprimido','Hidroclorotiazida','simples'),
(26,1,'Atenolol','50mg comprimido','Atenolol','simples'),
(27,1,'Propranolol','40mg comprimido','Propranolol','simples'),
(28,1,'Sinvastatina','20mg comprimido','Sinvastatina','simples'),
(29,1,'Sinvastatina','40mg comprimido','Sinvastatina','simples'),
(30,1,'Atorvastatina','20mg comprimido','Atorvastatina cálcica','simples'),
(31,1,'Metformina','500mg comprimido','Cloridrato de metformina','simples'),
(32,1,'Metformina','850mg comprimido','Cloridrato de metformina','simples'),
(33,1,'Glibenclamida','5mg comprimido','Glibenclamida','simples'),
(34,1,'Levotiroxina','50mcg comprimido','Levotiroxina sódica','simples'),
(35,1,'Levotiroxina','100mcg comprimido','Levotiroxina sódica','simples'),
(36,1,'Fluoxetina','20mg cápsula','Cloridrato de fluoxetina','controlada_b'),
(37,1,'Sertralina','50mg comprimido','Cloridrato de sertralina','controlada_b'),
(38,1,'Escitalopram','10mg comprimido','Oxalato de escitalopram','controlada_b'),
(39,1,'Clonazepam','2mg comprimido','Clonazepam','controlada_b'),
(40,1,'Alprazolam','0,5mg comprimido','Alprazolam','controlada_b');
INSERT INTO `medicamentos` (`id`,`empresa_id`,`nome`,`apresentacao`,`principio_ativo`,`tipo`) VALUES
(41,1,'Diazepam','10mg comprimido','Diazepam','controlada_b'),
(42,1,'Tramadol','50mg cápsula','Cloridrato de tramadol','controlada_a'),
(43,1,'Codeína + Paracetamol','30mg + 500mg comprimido','Codeína/Paracetamol','controlada_a'),
(44,1,'Dexametasona','4mg comprimido','Dexametasona','simples'),
(45,1,'Prednisona','20mg comprimido','Prednisona','simples'),
(46,1,'Salbutamol','100mcg/dose aerossol','Sulfato de salbutamol','simples'),
(47,1,'Budesonida','200mcg/dose pó inalatório','Budesonida','simples');

-- procedimentos: 4 registros
INSERT INTO `procedimentos` (`id`,`empresa_id`,`nome`,`descricao`,`valor`,`duracao_minutos`,`codigo_servico`,`categoria`,`ativo`,`criado_em`) VALUES
(1,1,'Consulta Clínica','Consulta médica geral','250.00',30,NULL,'clinico',1,'2026-11-11 23:52:12'),
(2,1,'Limpeza Dental','Profilaxia e remoção de tártaro','180.00',45,NULL,'odontologico',1,'2026-11-11 23:52:12'),
(3,1,'Restauração','Restauração em resina','280.00',60,NULL,'odontologico',1,'2026-11-11 23:52:12'),
(4,1,'Retorno','Consulta de retorno','150.00',20,NULL,'clinico',1,'2026-11-11 23:52:12');

-- usuarios: 2 registros
INSERT INTO `usuarios` (`id`,`empresa_id`,`nome`,`email`,`senha`,`perfil`,`especialidade`,`registro_profissional`,`telefone`,`percentual_repasse`,`tema_preferido`,`mfa_metodo`,`cor_tema`,`avatar`,`ativo`,`ultimo_login`,`token_reset`,`criado_em`,`atualizado_em`,`mfa_secret`,`mfa_codigos_recuperacao`,`mfa_codigo_temp`,`mfa_codigo_expira`) VALUES
(1,1,'Administrador','admin@clinicapp.com','$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa','admin',NULL,NULL,NULL,'0.00','medico','nenhum','blue',NULL,1,NULL,NULL,'2026-11-11 23:52:12','2026-11-11 23:52:12',NULL,NULL,NULL,NULL),
(2,1,'Desenvolvedor ClinicApp','dev@clinicapp.com','$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa','desenvolvedor','Licenciamento & Suporte',NULL,NULL,'0.00','medico','nenhum','purple',NULL,1,NULL,NULL,'2026-11-11 23:52:12','2026-11-11 23:52:12',NULL,NULL,NULL,NULL);
COMMIT;
SET FOREIGN_KEY_CHECKS=@CLINIC_OLD_FK;
SET SESSION SQL_MODE=@CLINIC_OLD_SQL_MODE;
