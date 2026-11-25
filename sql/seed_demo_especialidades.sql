SET NAMES utf8mb4;
-- =====================================================
-- SEED DEMO COMPLETO - TODAS AS ESPECIALIDADES
-- Projeto: ClinicApp
-- Objetivo: popular uma base de exemplo com dados coerentes para
--           médico, odontologia, psicologia, psiquiatria, pediatria,
--           ginecologia/obstetrícia, ortopedia/traumatologia,
--           cardiologia, oftalmologia e neurologia.
--
-- Modo de uso:
-- 1) Importe primeiro sql/database.sql
-- 2) Depois importe este arquivo
--
-- Credenciais demo:
--   Admin: admin.demo@clinicapp.com
--   Senha: 123456
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET @OLD_SQL_SAFE_UPDATES := @@SQL_SAFE_UPDATES;
SET SQL_SAFE_UPDATES = 0;
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

DELETE FROM teleconsultas WHERE agendamento_id BETWEEN 4001 AND 4100;
DELETE FROM contas_financeiras WHERE empresa_id = 2;
DELETE FROM categorias_financeiras WHERE empresa_id = 2;
DELETE FROM logs WHERE usuario_id BETWEEN 200 AND 211;
DELETE FROM importacoes WHERE empresa_id = 2;
DELETE FROM backups WHERE arquivo LIKE 'demo_%' OR id BETWEEN 8801 AND 8899;


-- -----------------------------------------------------
-- LIMPEZA DA EMPRESA DEMO (id = 2)
-- -----------------------------------------------------
DELETE FROM psicologia_escalas WHERE empresa_id = 2;
DELETE FROM psicologia_planos WHERE empresa_id = 2;
DELETE FROM psicologia_prontuario WHERE empresa_id = 2;
DELETE FROM psiquiatria_prontuario WHERE empresa_id = 2;
DELETE FROM pediatria_prontuario WHERE empresa_id = 2;
DELETE FROM ginecologia_obstetricia_prontuario WHERE empresa_id = 2;
DELETE FROM ortopedia_traumatologia_prontuario WHERE empresa_id = 2;
DELETE FROM cardiologia_prontuario WHERE empresa_id = 2;
DELETE FROM oftalmologia_prontuario WHERE empresa_id = 2;
DELETE FROM neurologia_prontuario WHERE empresa_id = 2;
DELETE FROM protocolos_clinicos WHERE empresa_id = 2;
DELETE FROM modelos_clinicos WHERE empresa_id = 2;
DELETE FROM paciente_alertas WHERE empresa_id = 2 OR paciente_id BETWEEN 2001 AND 2030;
DELETE FROM planos_cuidado WHERE empresa_id = 2 OR paciente_id BETWEEN 2001 AND 2030;
DELETE FROM odontograma WHERE paciente_id BETWEEN 2001 AND 2030;
DELETE FROM anexos WHERE paciente_id BETWEEN 2001 AND 2030 OR prontuario_id BETWEEN 5001 AND 5020;
DELETE FROM receitas WHERE paciente_id BETWEEN 2001 AND 2030 OR prontuario_id BETWEEN 5001 AND 5020;
DELETE FROM nfse WHERE empresa_id = 2;
DELETE FROM prontuarios WHERE id BETWEEN 5001 AND 5020 OR paciente_id BETWEEN 2001 AND 2030 OR profissional_id BETWEEN 201 AND 210;
DELETE FROM agendamentos WHERE id BETWEEN 4001 AND 4010 OR empresa_id = 2;
DELETE FROM bloqueios_horario WHERE empresa_id = 2;
DELETE FROM procedimentos WHERE id BETWEEN 3001 AND 3010 OR empresa_id = 2;
DELETE FROM pacientes WHERE id BETWEEN 2001 AND 2030 OR empresa_id = 2;
DELETE FROM usuarios WHERE id BETWEEN 200 AND 211 OR empresa_id = 2;
DELETE FROM empresas WHERE id = 2;

-- -----------------------------------------------------
-- EMPRESA DEMO
-- -----------------------------------------------------
INSERT INTO empresas (
  id, razao_social, nome_fantasia, cnpj, inscricao_municipal, cnae,
  item_lista_servico, codigo_tributacao_municipio, regime_tributario,
  optante_simples, incentivador_cultural, aliquota_iss,
  endereco_cep, endereco_logradouro, endereco_numero, endereco_complemento,
  endereco_bairro, endereco_cidade, endereco_uf, endereco_codigo_municipio,
  telefone, email, tema_padrao, asten_ambiente, asten_serie_rps, asten_proximo_rps
) VALUES (
  2,
  'Centro Integrado de Especialidades Demo LTDA',
  'Clínica Demo MultiEspecialidades',
  '98.765.432/0001-10',
  '123456',
  '8630501',
  '4.01',
  '401',
  '1',
  1,
  0,
  2.00,
  '01310-100',
  'Avenida Paulista',
  '1500',
  'Conjunto 1201',
  'Bela Vista',
  'São Paulo',
  'SP',
  '3550308',
  '1133334444',
  'contato.demo@clinicapp.com',
  'medico',
  'homologacao',
  '1',
  1
);

-- -----------------------------------------------------
-- USUÁRIOS DEMO
-- senha para todos: 123456
-- hash bcrypt gerado em 2026-06-04
-- -----------------------------------------------------
INSERT INTO usuarios (
  id, empresa_id, nome, email, senha, perfil, especialidade,
  registro_profissional, telefone, tema_preferido, cor_tema, ativo
) VALUES
  (200, 2, 'Administrador Demo', 'admin.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'admin', 'Gestão Clínica', 'ADM-0001', '11999990000', 'medico', 'blue', 1),
  (201, 2, 'Dr. André Clínica Médica', 'medico.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'profissional', 'Clínica Médica', 'CRM-SP 100001', '11999990001', 'medico', 'blue', 1),
  (202, 2, 'Dra. Beatriz Odontologia', 'dentista.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'profissional', 'Odontologia', 'CRO-SP 200002', '11999990002', 'dentista', 'teal', 1),
  (203, 2, 'Dra. Camila Psicologia', 'psicologia.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'profissional', 'Psicologia Clínica', 'CRP-SP 300003', '11999990003', 'psicologia', 'purple', 1),
  (204, 2, 'Dr. Daniel Psiquiatria', 'psiquiatria.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'profissional', 'Psiquiatria', 'CRM-SP 400004', '11999990004', 'psiquiatria', 'purple', 1),
  (205, 2, 'Dra. Elisa Pediatria', 'pediatria.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'profissional', 'Pediatria', 'CRM-SP 500005', '11999990005', 'pediatria', 'cyan', 1),
  (206, 2, 'Dra. Fernanda GO', 'go.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'profissional', 'Ginecologia e Obstetrícia', 'CRM-SP 600006', '11999990006', 'ginecologia_obstetricia', 'rose', 1),
  (207, 2, 'Dr. Gustavo Ortopedia', 'ortopedia.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'profissional', 'Ortopedia e Traumatologia', 'CRM-SP 700007', '11999990007', 'ortopedia_traumatologia', 'green', 1),
  (208, 2, 'Dr. Henrique Cardiologia', 'cardiologia.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'profissional', 'Cardiologia', 'CRM-SP 800008', '11999990008', 'cardiologia', 'rose', 1),
  (209, 2, 'Dra. Isabela Oftalmologia', 'oftalmo.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'profissional', 'Oftalmologia', 'CRM-SP 900009', '11999990009', 'oftalmologia', 'indigo', 1),
  (210, 2, 'Dr. João Neurologia', 'neuro.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'profissional', 'Neurologia', 'CRM-SP 100010', '11999990010', 'neurologia', 'teal', 1),
  (211, 2, 'Recepção Demo', 'recepcao.demo@clinicapp.com', '$2y$10$IilAhPYZu1Gndoll49wAnu3CIULlDejZPzjY4YZkCzyQtukfkOx2y', 'recepcao', 'Recepção', 'REC-0001', '11999990011', 'medico', 'blue', 1);

-- -----------------------------------------------------
-- PACIENTES DEMO
-- -----------------------------------------------------
INSERT INTO pacientes (
  id, empresa_id, nome, tipo_pessoa, cpf_cnpj, rg, data_nascimento, sexo,
  email, telefone, celular, profissao, estado_civil, convenio,
  numero_carteirinha, cep, logradouro, numero, complemento, bairro,
  cidade, uf, codigo_municipio, tipo_sanguineo, alergias, medicamentos_uso,
  historico_familiar, observacoes, ativo
) VALUES
  (2001, 2, 'Carlos Alberto Lima', 'F', '123.456.789-01', '11.111.111-1', '1982-04-13', 'M', 'carlos.lima@email.com', '1130010001', '11970010001', 'Administrador', 'Casado', 'Particular', NULL, '01310-200', 'Rua São Carlos do Pinhal', '101', 'Apto 11', 'Bela Vista', 'São Paulo', 'SP', '3550308', 'O+', 'Alergia a dipirona', 'Losartana 50mg ao dia', 'Pai hipertenso; mãe diabética', 'Paciente de clínica médica para check-up anual.', 1),
  (2002, 2, 'Mariana Costa Rocha', 'F', '123.456.789-02', '22.222.222-2', '1990-09-21', 'F', 'mariana.rocha@email.com', '1130010002', '11970010002', 'Designer', 'Solteira', 'Odonto Plus', 'OD12345', '04002-000', 'Rua Vergueiro', '450', NULL, 'Liberdade', 'São Paulo', 'SP', '3550308', 'A+', 'Sem alergias medicamentosas conhecidas', 'Nenhum', 'Mãe com doença periodontal', 'Busca avaliação odontológica e profilaxia.', 1),
  (2003, 2, 'Paula Mendes Souza', 'F', '123.456.789-03', '33.333.333-3', '1995-01-17', 'F', 'paula.souza@email.com', '1130010003', '11970010003', 'Analista de RH', 'Solteira', 'Particular', NULL, '04534-011', 'Rua Joaquim Floriano', '88', NULL, 'Itaim Bibi', 'São Paulo', 'SP', '3550308', 'B+', 'Sem alergias relatadas', 'Sertralina 50mg prescrita por psiquiatria há 4 meses', 'Histórico familiar de ansiedade', 'Paciente em psicoterapia por ansiedade e autocobrança.', 1),
  (2004, 2, 'Rafael Nogueira Prado', 'F', '123.456.789-04', '44.444.444-4', '1988-11-09', 'M', 'rafael.prado@email.com', '1130010004', '11970010004', 'Engenheiro', 'Casado', 'Executivo Saúde', 'EX45454', '05409-000', 'Rua Capote Valente', '210', NULL, 'Pinheiros', 'São Paulo', 'SP', '3550308', 'AB+', 'Sem alergias', 'Escitalopram 10mg à noite', 'Tio com transtorno bipolar', 'Encaminhado para seguimento psiquiátrico por humor deprimido e insônia.', 1),
  (2005, 2, 'Lucas Martins Ferreira', 'F', '123.456.789-05', '55.555.555-5', '2021-07-03', 'M', 'responsavel.lucas@email.com', '1130010005', '11970010005', NULL, NULL, 'Kids Saúde', 'KD2021', '04101-000', 'Rua Domingos de Morais', '900', NULL, 'Vila Mariana', 'São Paulo', 'SP', '3550308', 'O+', 'Alergia a amoxicilina', 'Xarope antitérmico se necessário', 'Pai asmático', 'Paciente pediátrico acompanhado pela mãe.', 1),
  (2006, 2, 'Juliana Ferreira Alves', 'F', '123.456.789-06', '66.666.666-6', '1992-06-14', 'F', 'juliana.alves@email.com', '1130010006', '11970010006', 'Advogada', 'Casada', 'Mulher Integral', 'GO90909', '01452-001', 'Rua dos Pinheiros', '560', NULL, 'Pinheiros', 'São Paulo', 'SP', '3550308', 'A-', 'Sem alergias', 'Ácido fólico e sulfato ferroso', 'Mãe hipertensa', 'Gestante em seguimento pré-natal de baixo risco.', 1),
  (2007, 2, 'Pedro Henrique Dias', 'F', '123.456.789-07', '77.777.777-7', '1979-02-25', 'M', 'pedro.dias@email.com', '1130010007', '11970010007', 'Professor', 'Casado', 'Particular', NULL, '05005-000', 'Rua Clélia', '1200', NULL, 'Lapa', 'São Paulo', 'SP', '3550308', 'B-', 'Sem alergias', 'Ibuprofeno eventual', 'Pai com artrose de joelho', 'Queixa de dor em joelho direito após corrida.', 1),
  (2008, 2, 'Ana Paula Ribeiro', 'F', '123.456.789-08', '88.888.888-8', '1968-08-30', 'F', 'ana.ribeiro@email.com', '1130010008', '11970010008', 'Aposentada', 'Viúva', 'Cardio Vida', 'CV10101', '01153-000', 'Alameda Barão de Limeira', '730', NULL, 'Campos Elíseos', 'São Paulo', 'SP', '3550308', 'O-', 'Sem alergias', 'Anlodipino 5mg e rosuvastatina 20mg', 'Irmão infartou aos 54 anos', 'Em avaliação de risco cardiovascular e palpitações.', 1),
  (2009, 2, 'Eduardo Siqueira Melo', 'F', '123.456.789-09', '99.999.999-9', '1975-12-05', 'M', 'eduardo.melo@email.com', '1130010009', '11970010009', 'Jornalista', 'Divorciado', 'Visão Max', 'VM20202', '02011-000', 'Rua Voluntários da Pátria', '300', NULL, 'Santana', 'São Paulo', 'SP', '3550308', 'A+', 'Sem alergias', 'Colírio lubrificante eventual', 'Mãe com glaucoma', 'Baixa visual progressiva e dificuldade para leitura.', 1),
  (2010, 2, 'Sônia Regina Barbosa', 'F', '123.456.789-10', '10.101.010-1', '1985-03-29', 'F', 'sonia.barbosa@email.com', '1130010010', '11970010010', 'Contadora', 'Casada', 'Neuro Care', 'NC30303', '03178-200', 'Rua Serra de Bragança', '150', NULL, 'Tatuapé', 'São Paulo', 'SP', '3550308', 'AB-', 'Sem alergias', 'Topiramato 25mg à noite', 'Histórico materno de enxaqueca', 'Crises de cefaleia e parestesias em investigação.', 1);

-- -----------------------------------------------------
-- PACIENTES DEMO ADICIONAIS (JUL-DEZ/2026)
-- -----------------------------------------------------
INSERT INTO pacientes (
  id, empresa_id, nome, tipo_pessoa, cpf_cnpj, rg, data_nascimento, sexo,
  email, telefone, celular, profissao, estado_civil, convenio,
  numero_carteirinha, cep, logradouro, numero, complemento, bairro,
  cidade, uf, codigo_municipio, tipo_sanguineo, alergias, medicamentos_uso,
  historico_familiar, observacoes, ativo
) VALUES
  (2011, 2, 'Renata Valverde', 'F', '223.456.789-11', '21.111.111-1', '1986-02-10', 'F', 'renata.valverde@email.com', '1130010011', '11970010011', 'Gerente Comercial', 'Casada', 'Particular', NULL, '04546-001', 'Rua Tabapuã', '410', NULL, 'Itaim Bibi', 'São Paulo', 'SP', '3550308', 'A+', 'Sem alergias', 'Vitamina D esporádica', 'Mãe com hipertensão', 'Paciente de clínica médica para seguimento preventivo.', 1),
  (2012, 2, 'Otávio Menezes', 'F', '223.456.789-12', '21.222.222-2', '1971-07-19', 'M', 'otavio.menezes@email.com', '1130010012', '11970010012', 'Consultor', 'Divorciado', 'Executivo Saúde', 'EX202612', '05414-020', 'Rua Mateus Grou', '88', NULL, 'Pinheiros', 'São Paulo', 'SP', '3550308', 'O+', 'Dipirona', 'Rosuvastatina 10mg', 'Pai com diabetes', 'Busca revisão clínica e orientação de risco metabólico.', 1),
  (2013, 2, 'Bianca Ferraz', 'F', '223.456.789-13', '21.333.333-3', '1994-04-03', 'F', 'bianca.ferraz@email.com', '1130010013', '11970010013', 'Publicitária', 'Solteira', 'Odonto Prime', 'OD202613', '04008-002', 'Rua Apeninos', '520', NULL, 'Paraíso', 'São Paulo', 'SP', '3550308', 'B+', 'Sem alergias', 'Nenhum', 'Histórico familiar de bruxismo', 'Paciente de odontologia estética e preventiva.', 1),
  (2014, 2, 'Marcelo Tavares', 'F', '223.456.789-14', '21.444.444-4', '1980-10-26', 'M', 'marcelo.tavares@email.com', '1130010014', '11970010014', 'Executivo de Vendas', 'Casado', 'Particular', NULL, '04107-030', 'Rua Humberto I', '140', 'Sala 3', 'Vila Mariana', 'São Paulo', 'SP', '3550308', 'AB+', 'Penicilina', 'Nenhum', 'Mãe com perda dentária precoce', 'Necessita avaliação restauradora e controle periodontal.', 1),
  (2015, 2, 'Lívia Corrêa', 'F', '223.456.789-15', '21.555.555-5', '1997-01-30', 'F', 'livia.correa@email.com', '1130010015', '11970010015', 'Arquiteta', 'Solteira', 'Particular', NULL, '04538-132', 'Rua Iguatemi', '121', NULL, 'Itaim Bibi', 'São Paulo', 'SP', '3550308', 'O-', 'Sem alergias', 'Sertralina 25mg', 'Pai com ansiedade', 'Paciente em busca de psicoterapia por estresse e autocobrança.', 1),
  (2016, 2, 'Fernando Cabral', 'F', '223.456.789-16', '21.666.666-6', '1989-12-15', 'M', 'fernando.cabral@email.com', '1130010016', '11970010016', 'Analista de Sistemas', 'Casado', 'Particular', NULL, '01309-010', 'Rua Peixoto Gomide', '640', NULL, 'Bela Vista', 'São Paulo', 'SP', '3550308', 'A-', 'Sem alergias', 'Clonazepam SOS', 'Irmã com transtorno ansioso', 'Deseja acompanhamento psicológico focado em sono e organização emocional.', 1),
  (2017, 2, 'Patrícia Seabra', 'F', '223.456.789-17', '21.777.777-7', '1983-06-08', 'F', 'patricia.seabra@email.com', '1130010017', '11970010017', 'Jornalista', 'Divorciada', 'Particular', NULL, '04005-001', 'Rua Cubatão', '700', NULL, 'Paraíso', 'São Paulo', 'SP', '3550308', 'A+', 'Sem alergias', 'Escitalopram 10mg', 'Tio com depressão recorrente', 'Seguimento psiquiátrico por ansiedade e humor deprimido.', 1),
  (2018, 2, 'Tiago Monteiro', 'F', '223.456.789-18', '21.888.888-8', '1991-03-22', 'M', 'tiago.monteiro@email.com', '1130010018', '11970010018', 'Coordenador Logístico', 'Solteiro', 'Particular', NULL, '05014-000', 'Rua Tito', '55', NULL, 'Lapa', 'São Paulo', 'SP', '3550308', 'B-', 'Sem alergias', 'Quetiapina 25mg à noite', 'Pai etilista', 'Precisa ajuste terapêutico e monitoramento de insônia.', 1),
  (2019, 2, 'Manuela Dias', 'F', '223.456.789-19', '21.999.999-9', '2018-09-12', 'F', 'responsavel.manuela@email.com', '1130010019', '11970010019', NULL, NULL, 'Kids Saúde', 'KD2019A', '04266-010', 'Rua Caravelas', '91', NULL, 'Vila Mariana', 'São Paulo', 'SP', '3550308', 'O+', 'Amoxicilina', 'Nenhum', 'Pai com rinite alérgica', 'Acompanhamento pediátrico de rotina e vacinação.', 1),
  (2020, 2, 'Gabriel Bittencourt', 'F', '223.456.789-20', '20.202.020-2', '2023-02-05', 'M', 'responsavel.gabriel@email.com', '1130010020', '11970010020', NULL, NULL, 'Infantil Mais', 'IM2020', '04118-020', 'Rua Sena Madureira', '230', NULL, 'Vila Clementino', 'São Paulo', 'SP', '3550308', 'O+', 'Sem alergias', 'Nebulização eventual', 'Mãe asmática', 'Criança em acompanhamento de puericultura.', 1),
  (2021, 2, 'Camila Rezende', 'F', '223.456.789-21', '20.212.121-2', '1993-05-09', 'F', 'camila.rezende@email.com', '1130010021', '11970010021', 'Nutricionista', 'Casada', 'Mulher Integral', 'GO202621', '05433-000', 'Rua Artur de Azevedo', '808', NULL, 'Pinheiros', 'São Paulo', 'SP', '3550308', 'A+', 'Sem alergias', 'Polivitamínico', 'Mãe hipertensa', 'Consulta ginecológica preventiva e planejamento reprodutivo.', 1),
  (2022, 2, 'Natália Fagundes', 'F', '223.456.789-22', '20.222.222-2', '1987-11-27', 'F', 'natalia.fagundes@email.com', '1130010022', '11970010022', 'Advogada', 'Casada', 'Particular', NULL, '01415-002', 'Alameda Jaú', '1150', 'Apto 91', 'Jardins', 'São Paulo', 'SP', '3550308', 'AB-', 'Sem alergias', 'Ácido fólico', 'Irmã com endometriose', 'Acompanhamento ginecológico com rastreio e avaliação hormonal.', 1),
  (2023, 2, 'Rogério Paiva', 'F', '223.456.789-23', '20.232.323-2', '1976-01-18', 'M', 'rogerio.paiva@email.com', '1130010023', '11970010023', 'Professor Universitário', 'Casado', 'Particular', NULL, '05017-010', 'Rua Pio XI', '1400', NULL, 'Alto da Lapa', 'São Paulo', 'SP', '3550308', 'B+', 'Cetoprofeno', 'Nenhum', 'Pai com artrose', 'Dor lombar e sobrecarga musculoesquelética crônica.', 1),
  (2024, 2, 'Helena Duarte', 'F', '223.456.789-24', '20.242.424-2', '1985-08-11', 'F', 'helena.duarte@email.com', '1130010024', '11970010024', 'Personal Trainer', 'Solteira', 'Particular', NULL, '01224-020', 'Rua Turiassu', '510', NULL, 'Perdizes', 'São Paulo', 'SP', '3550308', 'O-', 'Sem alergias', 'Anti-inflamatório eventual', 'Histórico de hérnia de disco em familiar', 'Busca ortopedia por dor em ombro e limitação funcional.', 1),
  (2025, 2, 'Sérgio Camargo', 'F', '223.456.789-25', '20.252.525-2', '1965-04-16', 'M', 'sergio.camargo@email.com', '1130010025', '11970010025', 'Empresário', 'Casado', 'Cardio Vida', 'CV202625', '01152-020', 'Alameda Nothmann', '300', NULL, 'Santa Cecília', 'São Paulo', 'SP', '3550308', 'AB+', 'Sem alergias', 'Losartana 50mg e AAS', 'Pai infartado aos 58 anos', 'Acompanhamento de pressão, dispneia e risco cardiovascular.', 1),
  (2026, 2, 'Denise Arcoverde', 'F', '223.456.789-26', '20.262.626-2', '1972-09-01', 'F', 'denise.arcoverde@email.com', '1130010026', '11970010026', 'Empresária', 'Viúva', 'Executivo Saúde', 'EX202626', '01308-200', 'Rua Frei Caneca', '910', NULL, 'Consolação', 'São Paulo', 'SP', '3550308', 'A-', 'Sem alergias', 'Rosuvastatina 20mg', 'Irmã com arritmia', 'Necessita reavaliação cardiológica e revisão medicamentosa.', 1),
  (2027, 2, 'Rita Albuquerque', 'F', '223.456.789-27', '20.272.727-2', '1969-12-08', 'F', 'rita.albuquerque@email.com', '1130010027', '11970010027', 'Professora', 'Casada', 'Visão Max', 'VM202627', '02022-001', 'Rua Conselheiro Moreira de Barros', '720', NULL, 'Santana', 'São Paulo', 'SP', '3550308', 'B+', 'Sem alergias', 'Colírio lubrificante', 'Mãe com catarata', 'Queixa de baixa visual para leitura e ressecamento ocular.', 1),
  (2028, 2, 'Leonardo Abranches', 'F', '223.456.789-28', '20.282.828-2', '1982-07-02', 'M', 'leonardo.abranches@email.com', '1130010028', '11970010028', 'Fotógrafo', 'Solteiro', 'Particular', NULL, '03171-010', 'Rua Emília Marengo', '250', NULL, 'Tatuapé', 'São Paulo', 'SP', '3550308', 'O+', 'Sem alergias', 'Nenhum', 'Pai com glaucoma', 'Busca avaliação oftalmológica por cefaleia ocular e embaçamento visual.', 1),
  (2029, 2, 'Marta Neves', 'F', '223.456.789-29', '20.292.929-2', '1984-10-14', 'F', 'marta.neves@email.com', '1130010029', '11970010029', 'Contadora', 'Casada', 'Neuro Care', 'NC202629', '03322-000', 'Rua Serra de Japi', '74', NULL, 'Tatuapé', 'São Paulo', 'SP', '3550308', 'AB-', 'Sem alergias', 'Topiramato 25mg', 'Mãe com enxaqueca', 'Acompanhamento neurológico por cefaleias recorrentes.', 1),
  (2030, 2, 'Bruno Estrela', 'F', '223.456.789-30', '20.303.030-3', '1978-05-28', 'M', 'bruno.estrela@email.com', '1130010030', '11970010030', 'Administrador', 'Divorciado', 'Particular', NULL, '04289-120', 'Rua Luís Góis', '320', NULL, 'Saúde', 'São Paulo', 'SP', '3550308', 'A+', 'Sem alergias', 'Pregabalina 75mg', 'Irmão com epilepsia', 'Encaminhado para neurologia por parestesias e tonturas.', 1);

-- -----------------------------------------------------
-- PROCEDIMENTOS DEMO
-- -----------------------------------------------------
INSERT INTO procedimentos (
  id, empresa_id, nome, descricao, valor, duracao_minutos, codigo_servico, categoria, ativo
) VALUES
  (3001, 2, 'Consulta Clínica Médica', 'Avaliação clínica geral e check-up', 250.00, 40, '4.01', 'clinico', 1),
  (3002, 2, 'Consulta Odontológica', 'Avaliação bucal e planejamento terapêutico', 180.00, 40, '4.12', 'odontologico', 1),
  (3003, 2, 'Sessão de Psicoterapia', 'Atendimento psicológico individual', 220.00, 50, '4.01', 'clinico', 1),
  (3004, 2, 'Consulta Psiquiátrica', 'Primeira consulta ou seguimento em saúde mental', 350.00, 50, '4.01', 'clinico', 1),
  (3005, 2, 'Consulta Pediátrica', 'Puericultura e atendimento infantil', 260.00, 40, '4.01', 'clinico', 1),
  (3006, 2, 'Consulta Ginecologia/Obstetrícia', 'Avaliação ginecológica e pré-natal', 290.00, 45, '4.01', 'clinico', 1),
  (3007, 2, 'Consulta Ortopédica', 'Avaliação musculoesquelética', 280.00, 45, '4.01', 'clinico', 1),
  (3008, 2, 'Consulta Cardiológica', 'Avaliação cardiovascular', 320.00, 45, '4.01', 'clinico', 1),
  (3009, 2, 'Consulta Oftalmológica', 'Avaliação visual e exame oftalmológico', 300.00, 40, '4.01', 'clinico', 1),
  (3010, 2, 'Consulta Neurológica', 'Avaliação neurológica clínica', 340.00, 50, '4.01', 'clinico', 1);

-- -----------------------------------------------------
-- CATEGORIAS FINANCEIRAS DEMO
-- -----------------------------------------------------
INSERT INTO categorias_financeiras (
  id, empresa_id, nome, tipo, cor, ativo
) VALUES
  (8101, 2, 'Clínica Médica', 'receita', '#0ea5e9', 1),
  (8102, 2, 'Odontologia', 'receita', '#06b6d4', 1),
  (8103, 2, 'Psicologia', 'receita', '#a855f7', 1),
  (8104, 2, 'Psiquiatria', 'receita', '#7c3aed', 1),
  (8105, 2, 'Pediatria', 'receita', '#22c55e', 1),
  (8106, 2, 'Ginecologia e Obstetrícia', 'receita', '#ec4899', 1),
  (8107, 2, 'Ortopedia e Traumatologia', 'receita', '#f59e0b', 1),
  (8108, 2, 'Cardiologia', 'receita', '#ef4444', 1),
  (8109, 2, 'Oftalmologia', 'receita', '#6366f1', 1),
  (8110, 2, 'Neurologia', 'receita', '#14b8a6', 1),
  (8111, 2, 'Aluguel e condomínio', 'despesa', '#b91c1c', 1),
  (8112, 2, 'Folha clínica e honorários', 'despesa', '#f97316', 1),
  (8113, 2, 'Materiais e insumos', 'despesa', '#d946ef', 1),
  (8114, 2, 'Marketing e aquisição', 'despesa', '#4f46e5', 1),
  (8115, 2, 'Serviços terceirizados', 'despesa', '#64748b', 1);

-- -----------------------------------------------------
-- AGENDAMENTOS DEMO
-- -----------------------------------------------------
INSERT INTO agendamentos (
  id, empresa_id, paciente_id, profissional_id, procedimento_id,
  data_hora_inicio, data_hora_fim, status, observacoes, valor,
  lembrete_email_enviado, criado_por
) VALUES
  (4001, 2, 2001, 201, 3001, '2026-05-12 09:00:00', '2026-05-12 09:40:00', 'realizado', 'Check-up anual com revisão de fatores de risco.', 250.00, 1, 211),
  (4002, 2, 2002, 202, 3002, '2026-05-12 10:00:00', '2026-05-12 10:40:00', 'realizado', 'Avaliação inicial e profilaxia programada.', 180.00, 1, 211),
  (4003, 2, 2003, 203, 3003, '2026-05-12 11:00:00', '2026-05-12 11:50:00', 'realizado', 'Sessão de acompanhamento de ansiedade.', 220.00, 1, 211),
  (4004, 2, 2004, 204, 3004, '2026-05-12 13:00:00', '2026-05-12 13:50:00', 'realizado', 'Avaliação de humor, sono e adesão.', 350.00, 1, 211),
  (4005, 2, 2005, 205, 3005, '2026-05-12 14:00:00', '2026-05-12 14:40:00', 'realizado', 'Atendimento pediátrico por febre e tosse.', 260.00, 1, 211),
  (4006, 2, 2006, 206, 3006, '2026-05-12 15:00:00', '2026-05-12 15:45:00', 'realizado', 'Retorno de pré-natal de baixo risco.', 290.00, 1, 211),
  (4007, 2, 2007, 207, 3007, '2026-05-12 16:00:00', '2026-05-12 16:45:00', 'realizado', 'Dor em joelho direito pós-exercício.', 280.00, 1, 211),
  (4008, 2, 2008, 208, 3008, '2026-05-13 09:00:00', '2026-05-13 09:45:00', 'realizado', 'Palpitações e estratificação de risco.', 320.00, 1, 211),
  (4009, 2, 2009, 209, 3009, '2026-05-13 10:00:00', '2026-05-13 10:40:00', 'realizado', 'Baixa visual progressiva para longe e perto.', 300.00, 1, 211),
  (4010, 2, 2010, 210, 3010, '2026-05-13 11:00:00', '2026-05-13 11:50:00', 'realizado', 'Cefaleia recorrente e parestesias intermitentes.', 340.00, 1, 211);

-- -----------------------------------------------------
-- AGENDAMENTOS DEMO ADICIONAIS (JUL-DEZ/2026)
-- -----------------------------------------------------
INSERT INTO agendamentos (
  id, empresa_id, paciente_id, profissional_id, procedimento_id,
  data_hora_inicio, data_hora_fim, status, observacoes, valor,
  lembrete_email_enviado, criado_por
) VALUES
  (4011, 2, 2011, 201, 3001, '2026-07-01 09:00:00', '2026-07-01 09:40:00', 'realizado', 'Consulta de prevenção e revisão metabólica.', 250.00, 1, 211),
  (4012, 2, 2012, 201, 3001, '2026-08-12 09:00:00', '2026-08-12 09:40:00', 'confirmado', 'Retorno/primeiro atendimento futuro de clínica médica.', 250.00, 0, 211),
  (4013, 2, 2011, 201, 3001, '2026-09-26 16:00:00', '2026-09-26 16:40:00', 'agendado', 'Seguimento programado de clínica médica até o final do ano.', 250.00, 0, 211),
  (4014, 2, 2013, 202, 3002, '2026-07-01 10:00:00', '2026-07-01 10:40:00', 'realizado', 'Avaliação inicial com foco restaurador e periodontal.', 180.00, 1, 211),
  (4015, 2, 2014, 202, 3002, '2026-08-15 14:00:00', '2026-08-15 14:40:00', 'confirmado', 'Retorno/primeiro atendimento futuro de odontologia.', 180.00, 0, 211),
  (4016, 2, 2013, 202, 3002, '2026-10-10 10:30:00', '2026-10-10 11:10:00', 'agendado', 'Seguimento programado de odontologia até o final do ano.', 180.00, 0, 211),
  (4017, 2, 2015, 203, 3003, '2026-07-02 11:00:00', '2026-07-02 11:50:00', 'realizado', 'Sessão inicial focada em ansiedade e autocuidado.', 220.00, 1, 211),
  (4018, 2, 2016, 203, 3003, '2026-08-20 09:00:00', '2026-08-20 09:50:00', 'confirmado', 'Retorno/primeiro atendimento futuro de psicologia.', 220.00, 0, 211),
  (4019, 2, 2015, 203, 3003, '2026-10-24 16:00:00', '2026-10-24 16:50:00', 'agendado', 'Seguimento programado de psicologia até o final do ano.', 220.00, 0, 211),
  (4020, 2, 2017, 204, 3004, '2026-07-02 13:30:00', '2026-07-02 14:20:00', 'realizado', 'Avaliação inicial de humor, sono e adesão medicamentosa.', 350.00, 1, 211),
  (4021, 2, 2018, 204, 3004, '2026-09-05 14:00:00', '2026-09-05 14:50:00', 'confirmado', 'Retorno/primeiro atendimento futuro de psiquiatria.', 350.00, 0, 211),
  (4022, 2, 2017, 204, 3004, '2026-11-05 10:30:00', '2026-11-05 11:20:00', 'agendado', 'Seguimento programado de psiquiatria até o final do ano.', 350.00, 0, 211),
  (4023, 2, 2019, 205, 3005, '2026-07-03 14:30:00', '2026-07-03 15:10:00', 'realizado', 'Consulta de rotina com revisão do calendário vacinal.', 260.00, 1, 211),
  (4024, 2, 2020, 205, 3005, '2026-09-12 09:00:00', '2026-09-12 09:40:00', 'confirmado', 'Retorno/primeiro atendimento futuro de pediatria.', 260.00, 0, 211),
  (4025, 2, 2019, 205, 3005, '2026-11-12 16:00:00', '2026-11-12 16:40:00', 'agendado', 'Seguimento programado de pediatria até o final do ano.', 260.00, 0, 211),
  (4026, 2, 2021, 206, 3006, '2026-07-03 15:30:00', '2026-07-03 16:15:00', 'realizado', 'Avaliação ginecológica preventiva com rastreio e planejamento.', 290.00, 1, 211),
  (4027, 2, 2022, 206, 3006, '2026-09-19 14:00:00', '2026-09-19 14:45:00', 'confirmado', 'Retorno/primeiro atendimento futuro de ginecologia e obstetrícia.', 290.00, 0, 211),
  (4028, 2, 2021, 206, 3006, '2026-11-19 10:30:00', '2026-11-19 11:15:00', 'agendado', 'Seguimento programado de ginecologia e obstetrícia até o final do ano.', 290.00, 0, 211),
  (4029, 2, 2023, 207, 3007, '2026-07-04 09:00:00', '2026-07-04 09:45:00', 'realizado', 'Avaliação de dor articular ou sobrecarga musculoesquelética.', 280.00, 1, 211),
  (4030, 2, 2024, 207, 3007, '2026-10-03 09:00:00', '2026-10-03 09:45:00', 'agendado', 'Retorno/primeiro atendimento futuro de ortopedia e traumatologia.', 280.00, 0, 211),
  (4031, 2, 2023, 207, 3007, '2026-12-03 16:00:00', '2026-12-03 16:45:00', 'agendado', 'Seguimento programado de ortopedia e traumatologia até o final do ano.', 280.00, 0, 211),
  (4032, 2, 2025, 208, 3008, '2026-07-04 10:00:00', '2026-07-04 10:45:00', 'realizado', 'Estratificação de risco e revisão de pressão arterial.', 320.00, 1, 211),
  (4033, 2, 2026, 208, 3008, '2026-10-17 14:00:00', '2026-10-17 14:45:00', 'agendado', 'Retorno/primeiro atendimento futuro de cardiologia.', 320.00, 0, 211),
  (4034, 2, 2025, 208, 3008, '2026-12-10 10:30:00', '2026-12-10 11:15:00', 'agendado', 'Seguimento programado de cardiologia até o final do ano.', 320.00, 0, 211),
  (4035, 2, 2027, 209, 3009, '2026-07-05 11:00:00', '2026-07-05 11:40:00', 'realizado', 'Avaliação de acuidade, ressecamento ocular e refração.', 300.00, 1, 211),
  (4036, 2, 2028, 209, 3009, '2026-11-07 09:00:00', '2026-11-07 09:40:00', 'agendado', 'Retorno/primeiro atendimento futuro de oftalmologia.', 300.00, 0, 211),
  (4037, 2, 2027, 209, 3009, '2026-12-17 16:00:00', '2026-12-17 16:40:00', 'agendado', 'Seguimento programado de oftalmologia até o final do ano.', 300.00, 0, 211),
  (4038, 2, 2029, 210, 3010, '2026-07-05 14:00:00', '2026-07-05 14:50:00', 'realizado', 'Investigação de cefaleia, parestesias ou tonturas.', 340.00, 1, 211),
  (4039, 2, 2030, 210, 3010, '2026-11-21 14:00:00', '2026-11-21 14:50:00', 'agendado', 'Retorno/primeiro atendimento futuro de neurologia.', 340.00, 0, 211),
  (4040, 2, 2029, 210, 3010, '2026-12-22 10:30:00', '2026-12-22 11:20:00', 'agendado', 'Seguimento programado de neurologia até o final do ano.', 340.00, 0, 211);

-- -----------------------------------------------------
-- PRONTUÁRIOS DEMO
-- -----------------------------------------------------
INSERT INTO prontuarios (empresa_id, 
  id, paciente_id, profissional_id, agendamento_id, data_atendimento,
  anamnese, queixa_principal, exame_fisico, hipotese_diagnostica,
  conduta, evolucao, observacoes
) VALUES
  (2, 5001, 2001, 201, 4001, '2026-05-12 09:10:00', 'Paciente comparece para check-up anual, refere sedentarismo parcial e controle irregular da pressão.', 'Deseja avaliação geral, revisão de exames e ajuste de hábitos.', 'Bom estado geral, hidratado, PA 138x86 mmHg, FC 78 bpm, ausculta cardíaca e pulmonar sem alterações relevantes.', 'Hipertensão estágio 1 em acompanhamento; sobrepeso; dislipidemia em investigação.', 'Solicitados exames laboratoriais, reforço de dieta hipossódica e atividade física regular.', 'Paciente orientado e concordante com mudanças de estilo de vida.', 'Retorno em 30 dias com exames.'),
  (2, 5002, 2002, 202, 4002, '2026-05-12 10:10:00', 'Paciente relata sangramento gengival esporádico e sensibilidade em molar superior direito.', 'Avaliação odontológica inicial e limpeza.', 'Biofilme moderado, gengivite leve e restauração antiga infiltrada em 16.', 'Gengivite associada a biofilme e suspeita de infiltração em restauração.', 'Profilaxia, orientação de higiene, radiografia interproximal e planejamento restaurador.', 'Boa compreensão e adesão às orientações.', 'Retorno para restauração e reavaliação periodontal.'),
  (2, 5003, 2003, 203, 4003, '2026-05-12 11:05:00', 'Paciente em seguimento psicológico por ansiedade, preocupação excessiva e perfeccionismo no trabalho.', 'Ansiedade, autocobrança e ruminação.', 'Sem exame físico aplicável; apresentação compatível com bom autocuidado.', 'Sintomas ansiosos com prejuízo leve a moderado no funcionamento ocupacional.', 'Psicoeducação, identificação de gatilhos e treino inicial de respiração diafragmática.', 'Paciente engajada, verbaliza alívio após reestruturação cognitiva inicial.', 'Manter registro de pensamentos automáticos.'),
  (2, 5004, 2004, 204, 4004, '2026-05-12 13:05:00', 'Paciente com histórico de humor deprimido, insônia terminal e queda de energia há cerca de 5 meses.', 'Humor deprimido, irritabilidade e insônia.', 'Estado geral preservado, sem alterações neurológicas focais observáveis na consulta.', 'Episódio depressivo em investigação; transtorno ansioso associado.', 'Ajuste de antidepressivo, orientação de higiene do sono e avaliação de risco.', 'Paciente consciente, crítico, sem sinais psicóticos no momento.', 'Retorno em 15 dias.'),
  (2, 5005, 2005, 205, 4005, '2026-05-12 14:05:00', 'Mãe refere febre há 2 dias, coriza e tosse seca, com redução leve do apetite.', 'Febre e sintomas respiratórios altos.', 'Afebril no momento após antitérmico, orofaringe hiperemiada leve, ausculta pulmonar sem ruídos adventícios.', 'Infecção viral de vias aéreas superiores.', 'Medidas de suporte, hidratação, controle térmico e sinais de alarme.', 'Criança ativa, hidratada e sem desconforto respiratório.', 'Retorno se persistência da febre ou piora clínica.'),
  (2, 5006, 2006, 206, 4006, '2026-05-12 15:05:00', 'Gestante G2P1A0, 24 semanas, sem queixas relevantes, boa aceitação alimentar e movimentação fetal presente.', 'Retorno de pré-natal.', 'PA 110x70 mmHg, peso adequado para a idade gestacional, BCF presente, sem edema.', 'Gestação de baixo risco em evolução habitual.', 'Manter suplementação, solicitar exames de rotina do trimestre e reforçar sinais de alerta.', 'Paciente orientada e tranquila.', 'Retorno em 4 semanas.'),
  (2, 5007, 2007, 207, 4007, '2026-05-12 16:05:00', 'Dor em joelho direito há 3 semanas, piora ao correr e subir escadas, sem trauma direto importante.', 'Dor no joelho direito.', 'Dor à palpação patelofemoral, sem derrame importante, arco preservado com desconforto no agachamento.', 'Síndrome dolorosa femoropatelar; tendinopatia associada a sobrecarga.', 'Analgesia, redução temporária do impacto e encaminhamento para fisioterapia.', 'Paciente entende necessidade de ajuste do treino.', 'Retorno em 21 dias.'),
  (2, 5008, 2008, 208, 4008, '2026-05-13 09:05:00', 'Paciente hipertensa, com palpitações episódicas e dispneia aos médios esforços.', 'Palpitações e avaliação de risco cardiovascular.', 'PA 146x88 mmHg, FC 84 bpm, sem edema, bulhas normofonéticas.', 'Hipertensão arterial sistêmica; palpitações em investigação; risco CV intermediário/alto.', 'Solicitados ECG, ecocardiograma e Holter; ajuste de antihipertensivo e metas pressóricas.', 'Paciente compreende necessidade de estratificação.', 'Retorno com exames.'),
  (2, 5009, 2009, 209, 4009, '2026-05-13 10:05:00', 'Paciente com baixa visual progressiva e dificuldade para leitura noturna, nega trauma ocular.', 'Visão embaçada e cansaço visual.', 'Sem hiperemia importante; acuidade reduzida sem correção em ambos os olhos.', 'Erro refracional associado a suspeita de catarata inicial.', 'Atualização de refração, orientação e solicitação de exames complementares.', 'Paciente orientado sobre acompanhamento seriado.', 'Revisão após exames e adaptação de correção óptica.'),
  (2, 5010, 2010, 210, 4010, '2026-05-13 11:05:00', 'Paciente refere cefaleia pulsátil recorrente, parestesias transitórias em membro superior esquerdo e fotofobia.', 'Cefaleia e parestesias.', 'Sem déficit focal fixo na consulta, marcha preservada, fala sem alterações.', 'Enxaqueca com aura em investigação diferencial; necessidade de exclusão de outras causas neurológicas.', 'Solicitada RM de crânio, ajuste de profilaxia e diário de cefaleia.', 'Paciente sem sinais de alarme no momento.', 'Retorno em 30 dias ou antes se piora.' );

-- -----------------------------------------------------
-- PRONTUÁRIOS DEMO ADICIONAIS
-- -----------------------------------------------------
INSERT INTO prontuarios (empresa_id, 
  id, paciente_id, profissional_id, agendamento_id, data_atendimento,
  anamnese, queixa_principal, exame_fisico, hipotese_diagnostica,
  conduta, evolucao, observacoes
) VALUES
  (2, 5011, 2011, 201, 4011, '2026-07-01 09:00:10', 'Paciente em consulta de seguimento preventivo, com revisão de hábitos, rotina de exames e fatores de risco metabólico.', 'Deseja avaliação clínica geral e orientação de prevenção.', 'Bom estado geral, sinais vitais estáveis e sem alterações agudas relevantes ao exame físico.', 'Acompanhamento preventivo; necessidade de reforço em hábitos de vida e controle laboratorial.', 'Solicitados exames de rotina, reforçadas orientações sobre alimentação, sono e atividade física.', 'Paciente aderente e concordante com retorno programado.', 'Retorno com exames em prazo habitual.'),
  (2, 5012, 2013, 202, 4014, '2026-07-01 10:00:10', 'Paciente refere sensibilidade localizada e interesse em revisão odontológica preventiva.', 'Sensibilidade dentária e necessidade de avaliação bucal.', 'Biofilme leve a moderado, restaurações prévias íntegras exceto ponto suspeito em molar.', 'Cárie inicial / necessidade de acompanhamento periodontal preventivo.', 'Orientação de higiene, profilaxia e planejamento restaurador conforme exame clínico.', 'Boa aceitação do plano terapêutico proposto.', 'Agendado retorno para execução e reavaliação.'),
  (2, 5013, 2015, 203, 4017, '2026-07-02 11:00:10', 'Paciente busca atendimento por sobrecarga emocional, ansiedade e dificuldade de organização da rotina.', 'Ansiedade, estresse e autocobrança.', 'Apresentação adequada, discurso coerente e contato preservado.', 'Sintomas ansiosos com impacto funcional leve a moderado.', 'Psicoeducação, acolhimento, identificação de gatilhos e proposta de acompanhamento.', 'Paciente engajado e disposto a manter registro entre sessões.', 'Manter seguimento semanal e tarefas entre sessões.'),
  (2, 5014, 2017, 204, 4020, '2026-07-02 13:30:10', 'Paciente em avaliação por humor deprimido, insônia e oscilação de energia nas últimas semanas.', 'Humor deprimido, ansiedade e sono não reparador.', 'Estado mental sem sinais psicóticos, crítica preservada e sem risco agudo identificado.', 'Transtorno ansioso/depressivo em investigação e seguimento.', 'Ajuste terapêutico inicial, higiene do sono e orientação sobre sinais de alerta.', 'Paciente compreende plano e aceita acompanhamento próximo.', 'Retorno breve para reavaliação de resposta clínica.'),
  (2, 5015, 2019, 205, 4023, '2026-07-03 14:30:10', 'Consulta de puericultura/seguimento, com revisão de crescimento, alimentação e marcos de desenvolvimento.', 'Rotina pediátrica e orientações preventivas.', 'Criança ativa, hidratada, sem sinais de toxemia ou desconforto respiratório.', 'Seguimento pediátrico sem intercorrências maiores no momento.', 'Orientações de sono, alimentação, vacinação e sinais de alerta aos responsáveis.', 'Responsável orientado e sem dúvidas ao final da consulta.', 'Programado retorno conforme faixa etária ou antes se necessidade.'),
  (2, 5016, 2021, 206, 4026, '2026-07-03 15:30:10', 'Paciente comparece para rotina ginecológica / seguimento com foco em prevenção e sintomas do ciclo.', 'Avaliação ginecológica preventiva.', 'Sem alterações agudas; exame clínico compatível com seguimento ambulatorial.', 'Seguimento ginecológico habitual; rastreio e acompanhamento conforme faixa etária.', 'Solicitados exames preventivos e reforçadas orientações sobre sinais de alerta.', 'Paciente acolhida, esclarecida e concordante com conduta.', 'Retorno programado após exames ou conforme necessidade clínica.'),
  (2, 5017, 2023, 207, 4029, '2026-07-04 09:00:10', 'Paciente com dor osteomuscular relacionada a esforço repetitivo / atividade física.', 'Dor articular e limitação funcional leve.', 'Dor à mobilização específica, sem sinais inflamatórios sistêmicos ou déficit neurovascular.', 'Síndrome dolorosa musculoesquelética por sobrecarga / inflamação local.', 'Analgesia, orientação funcional, ajuste de carga e encaminhamento para fisioterapia se indicado.', 'Paciente compreende medidas de proteção e retorno programado.', 'Monitorar resposta clínica e necessidade de imagem complementar.'),
  (2, 5018, 2025, 208, 4032, '2026-07-04 10:00:10', 'Paciente em reavaliação cardiológica por controle pressórico, dispneia ou palpitações intermitentes.', 'Controle cardiovascular e revisão de risco.', 'PA em faixa limítrofe, ausculta sem achados agudos e sem edema periférico.', 'Risco cardiovascular em seguimento, com necessidade de rastreio complementar conforme quadro.', 'Reforçadas metas pressóricas, atividade física e solicitação de exames complementares.', 'Paciente aderente e motivado a manter acompanhamento.', 'Retorno com monitorização e exames programados.'),
  (2, 5019, 2027, 209, 4035, '2026-07-05 11:00:10', 'Paciente refere cansaço visual, olho seco ou dificuldade progressiva para leitura.', 'Baixa visual / desconforto ocular.', 'Acuidade reduzida sem correção; segmento anterior sem intercorrências agudas.', 'Erro refracional / superfície ocular em avaliação, com seguimento ambulatorial.', 'Orientada refração, lubrificação ocular e complementação diagnóstica quando indicada.', 'Paciente orientado sobre acompanhamento seriado.', 'Retorno após exames e adaptação de conduta óptica.'),
  (2, 5020, 2029, 210, 4038, '2026-07-05 14:00:10', 'Paciente em investigação de cefaleia, tonturas ou parestesias transitórias sem sinais de alarme atuais.', 'Cefaleia recorrente / sintomas neurológicos intermitentes.', 'Sem déficit focal fixo ao exame clínico nesta consulta.', 'Cefaleia primária / quadro neurológico em investigação diferencial.', 'Solicitados exames conforme quadro, diário de sintomas e orientação sobre sinais de urgência.', 'Paciente compreende necessidade de monitorização e retorno.', 'Revisão com exames e avaliação de resposta terapêutica.');

-- -----------------------------------------------------
-- ODONTOLOGIA - EXEMPLO DE ODONTOGRAMA
-- -----------------------------------------------------
INSERT INTO odontograma (empresa_id, 
  id, paciente_id, dente, face, condicao, cor, observacao, data_registro, profissional_id
) VALUES
  (2, 6001, 2002, '16', 'oclusal', 'restauracao', 'orange', 'Necessita substituição de restauração antiga.', '2026-05-12', 202),
  (2, 6002, 2002, '26', 'geral', 'tratamento', 'yellow', 'Acúmulo de placa supragengival.', '2026-05-12', 202);

-- -----------------------------------------------------
-- PSICOLOGIA - DETALHES, PLANO E ESCALA
-- -----------------------------------------------------
INSERT INTO psicologia_prontuario (
  id, empresa_id, prontuario_id, paciente_id, profissional_id, tipo_registro,
  demanda_principal, historico_pessoal, historico_familiar, historico_psiquiatrico,
  uso_medicacao, fatores_risco, fatores_protecao, objetivo_terapeutico,
  queixa_historico, expectativas_tratamento, encaminhado_por, motivo_encaminhamento,
  historico_saude, uso_substancias, sono_apetite, suporte_rede,
  contexto_escolar_profissional, antecedentes_trauma_luto, humor_afeto,
  pensamento_linguagem, comportamento, cognicao_orientacao,
  estado_mental_aparencia, estado_mental_sensopercepcao, risco_suicida,
  risco_autoheteroagressao, temas_sessao, intervencoes_realizadas,
  tecnicas_utilizadas, resposta_paciente, adesao_processo, recursos_mobilizados,
  manejo_crise, tarefas_entre_sessoes, encaminhamentos, plano_sessao_seguinte,
  observacoes_confidenciais, consentimento_tipo, consentimento_terapia_texto,
  consentimento_tele_texto, consentimento_privacidade_texto,
  consentimento_compartilhamento_texto, consentimento_gravacao_texto,
  consentimento_terapia_aceito, consentimento_tele_aceito,
  consentimento_privacidade_aceito, consentimento_compartilhamento_aceito,
  consentimento_gravacao_aceito, consentimento_nome_responsavel,
  consentimento_documento, consentimento_parentesco, consentimento_testemunha,
  consentimento_registrado_em
) VALUES (
  6101, 2, 5003, 2003, 203, 'sessao',
  'Ansiedade associada à autocobrança e medo de falhar no ambiente de trabalho.',
  'Refere histórico de perfeccionismo desde adolescência e dificuldade em dizer não.',
  'Mãe com traços ansiosos e dinâmica familiar rígida quanto a desempenho.',
  'Acompanhamento psiquiátrico ambulatorial sem internações prévias.',
  'Sertralina 50mg/dia.',
  'Sobrecarga profissional, privação de sono em períodos de fechamento e autocrítica elevada.',
  'Rede de apoio familiar e boa capacidade reflexiva.',
  'Reduzir intensidade da ansiedade e ampliar repertório de enfrentamento.',
  'Queixas iniciadas com intensificação das demandas no trabalho nos últimos 12 meses.',
  'Deseja aprender a controlar a ansiedade e melhorar equilíbrio vida-trabalho.',
  'Demanda espontânea',
  'Busca por manejo de ansiedade e autocobrança.',
  'Sem comorbidades clínicas relevantes relatadas.',
  'Uso social de álcool; nega outras substâncias.',
  'Sono irregular em semanas mais estressantes; apetite preservado.',
  'Boa relação com irmã e amigos próximos.',
  'Trabalho com metas agressivas e dificuldade em pausas.',
  'Relata término afetivo importante há 2 anos ainda com impacto emocional residual.',
  'Ansiosa, porém com afeto congruente e acesso emocional preservado.',
  'Curso do pensamento organizado, linguagem clara e objetiva.',
  'Cooperativa, atenta e participativa.',
  'Orientada em tempo, espaço e pessoa.',
  'Boa higiene e vestimenta adequada.',
  'Sem alterações sensoperceptivas relatadas.',
  'baixo',
  'Nega ideação atual e história de auto/heteroagressão.',
  'Ansiedade de desempenho, limites pessoais, rotina de descanso.',
  'Psicoeducação, identificação de pensamentos automáticos e validação emocional.',
  'Reestruturação cognitiva inicial e respiração diafragmática.',
  'Refere alívio e maior clareza sobre gatilhos.',
  'Boa adesão.',
  'Escrita terapêutica e apoio de rede social.',
  'Não aplicável.',
  'Registrar situações de ansiedade e pensamentos automáticos.',
  'Sem encaminhamentos externos neste momento.',
  'Aprofundar crenças centrais relacionadas a desempenho.',
  'Sem observações confidenciais adicionais.',
  'ambos',
  'Paciente orientada sobre objetivos, sigilo e limites do acompanhamento psicológico.',
  'Paciente ciente das orientações para atendimentos remotos quando necessários.',
  'Paciente informada sobre tratamento de dados pessoais e sensíveis para fins assistenciais.',
  'Paciente autoriza compartilhamento apenas quando clinicamente necessário e com respaldo ético/legal.',
  'Gravações somente mediante autorização específica.',
  1, 1, 1, 1, 0,
  'Paula Mendes Souza',
  '123.456.789-03',
  'Própria paciente',
  'Administrador Demo',
  '2026-05-12 11:00:00'
);

INSERT INTO psicologia_planos (
  id, empresa_id, prontuario_id, paciente_id, profissional_id, abordagem,
  demanda_focal, objetivo_principal, metas, metas_curto_prazo,
  metas_medio_prazo, metas_longo_prazo, estrategias_intervencao,
  indicadores_evolucao, criterios_alta, risco_monitorado,
  frequencia_sugerida, status, data_inicio, data_reavaliacao, observacoes
) VALUES (
  6201, 2, 5003, 2003, 203, 'Terapia Cognitivo-Comportamental',
  'Ansiedade de desempenho, perfeccionismo e ruminação.',
  'Reduzir sintomas ansiosos e ampliar flexibilidade cognitiva.',
  'Psicoeducação, manejo fisiológico da ansiedade, reestruturação cognitiva e treino de limites.',
  'Identificar gatilhos e registrar pensamentos automáticos por 4 semanas.',
  'Diminuir episódios de ansiedade intensa no trabalho e aumentar uso de técnicas de regulação.',
  'Desenvolver rotina sustentável com menor autocobrança e melhor qualidade de vida.',
  'Sessões semanais, tarefas entre sessões, registros cognitivos e experimentos comportamentais.',
  'Redução de ruminação, melhora do sono, autoavaliação de ansiedade e adesão às tarefas.',
  'Sintomas controlados, autonomia em estratégias de enfrentamento e melhora funcional sustentada.',
  'Baixo risco, sem ideação atual; manter rastreio periódico.',
  'Semanal', 'ativo', '2026-05-12', '2026-06-23',
  'Plano terapêutico inicial com reavaliação em seis semanas.'
);

INSERT INTO psicologia_escalas (
  id, empresa_id, paciente_id, profissional_id, nome_escala,
  score_total, classificacao, data_aplicacao, observacoes
) VALUES (
  6301, 2, 2003, 203, 'GAD-7', '12', 'Ansiedade moderada', '2026-05-12', 'Escala aplicada na primeira fase do acompanhamento.'
);

-- -----------------------------------------------------
-- PSIQUIATRIA
-- -----------------------------------------------------
INSERT INTO psiquiatria_prontuario (
  id, empresa_id, prontuario_id, paciente_id, profissional_id,
  psiq_tipo_atendimento, psiq_queixa_principal, psiq_inicio_sintomas,
  psiq_fatores_desencadeantes, psiq_historico_psiquiatrico,
  psiq_internacoes_previas, psiq_historico_familiar, psiq_uso_substancias,
  psiq_comorbidades, psiq_humor_afeto, psiq_pensamento, psiq_percepcao,
  psiq_cognicao, psiq_juizo_critica, psiq_risco_suicidio,
  psiq_plano_seguranca, psiq_risco_heteroagressao, psiq_hipotese_diag,
  psiq_cid10, psiq_medicacoes, psiq_orientacoes_efeitos,
  psiq_psicoterapia_indicada, psiq_encaminhamentos,
  psiq_conduta_urgente, psiq_plano_retorno
) VALUES (
  6401, 2, 5004, 2004, 204,
  'retorno',
  'Humor deprimido, insônia e irritabilidade.',
  'Há cerca de 5 meses, com piora progressiva.',
  'Sobrecarga ocupacional e conflitos familiares recentes.',
  'Acompanhamento ambulatorial prévio sem internações e sem episódios psicóticos.',
  'Nega internações psiquiátricas prévias.',
  'Tio com transtorno bipolar; mãe com ansiedade.',
  'Uso social de álcool; nega outras substâncias.',
  'Insônia e sintomas ansiosos associados.',
  'Humor deprimido, afeto rebaixado, reativo em alguns momentos.',
  'Curso do pensamento organizado, conteúdo com preocupação excessiva, sem delírios.',
  'Nega alucinações.',
  'Atenção e memória preservadas na consulta.',
  'Crítica parcialmente preservada com boa adesão potencial.',
  'baixo',
  'Orientado a procurar suporte familiar, serviço de urgência e contato emergencial se piora importante ou ideação suicida.',
  'Nega heteroagressividade.',
  'Episódio depressivo com sintomas ansiosos associados.',
  'F32.1',
  'Escitalopram 10mg/noite, com possibilidade de ajuste gradual.',
  'Orientado sobre latência terapêutica, náusea inicial, sonolência e importância de não interromper abruptamente.',
  'sim',
  'Manter seguimento psicológico concomitante.',
  'Sem indicação de internação ou manejo urgente nesta consulta.',
  'Retorno em 15 dias para reavaliação de resposta e tolerabilidade.'
);

-- -----------------------------------------------------
-- PEDIATRIA
-- -----------------------------------------------------
INSERT INTO pediatria_prontuario (
  id, empresa_id, prontuario_id, paciente_id, profissional_id,
  ped_responsavel_nome, ped_responsavel_parentesco, ped_queixa_principal,
  ped_inicio_quadro, ped_febre, ped_tempo_febre, ped_sintomas_associados,
  ped_contato_doentes, ped_peso_atual, ped_altura_atual,
  ped_perimetro_cefalico, ped_curva_crescimento,
  ped_desenvolvimento_neuropsicomotor, ped_aleitamento,
  ped_vacinacao_em_dia, ped_vacinas_pendentes, ped_alergias,
  ped_medicacoes_uso, ped_temperatura, ped_fc, ped_fr, ped_saturacao,
  ped_exame_fisico, ped_hipotese_diag, ped_conduta,
  ped_sinais_alarme, ped_retorno
) VALUES (
  6501, 2, 5005, 2005, 205,
  'Camila Martins Ferreira',
  'Mãe',
  'Febre e coriza há 2 dias.',
  'Início há 48 horas.',
  'sim',
  '2 dias',
  'Tosse seca, obstrução nasal e leve hiporexia.',
  'Contato com colegas gripados na escola.',
  15.20,
  96.00,
  50.00,
  'adequado',
  'Marcos do desenvolvimento adequados para a idade.',
  'Alimentação já diversificada, sem aleitamento materno atual.',
  'incompleta',
  'Reforço de influenza pendente conforme calendário relatado pela mãe.',
  'Alergia a amoxicilina.',
  'Antitérmico se necessário.',
  37.8,
  108,
  24,
  98,
  'Bom estado geral, hidratado, sem tiragem, orofaringe discretamente hiperemiada e ausculta pulmonar sem ruídos adventícios.',
  'Infecção viral de vias aéreas superiores.',
  'Suporte clínico, hidratação, lavagem nasal e observação domiciliar.',
  'Procurar serviço imediatamente se desconforto respiratório, prostração, febre persistente ou recusa alimentar importante.',
  '2026-05-15'
);

-- -----------------------------------------------------
-- GINECOLOGIA E OBSTETRÍCIA
-- -----------------------------------------------------
INSERT INTO ginecologia_obstetricia_prontuario (
  id, empresa_id, prontuario_id, paciente_id, profissional_id,
  go_tipo_atendimento, go_queixa_principal, go_dum, go_gpa,
  go_idade_gestacional, go_dpp, go_ciclo_menstrual, go_contracepcao,
  go_sangramento, go_patologias_previas, go_cirurgias_previas,
  go_ist_risco, go_ultimo_papanicolau, go_ultima_mamografia,
  go_pre_natal_risco, go_movimentos_fetais, go_sinais_alarme,
  go_pa, go_peso, go_bcf, go_altura_uterina, go_exame_fisico,
  go_exames_solicitados, go_hipotese_diag, go_conduta, go_orientacoes
) VALUES (
  6601, 2, 5006, 2006, 206,
  'pre_natal',
  'Retorno de pré-natal sem intercorrências.',
  '2025-11-24',
  'G2P1A0',
  '24 semanas',
  '2026-08-31',
  'Ciclos regulares antes da gestação.',
  'Não aplicável na gestação atual.',
  'Nega sangramentos.',
  'Nega hipertensão, diabetes ou trombofilias.',
  'Cesárea prévia há 4 anos.',
  'Sem fatores de risco para IST relatados.',
  'Coleta em 2025 com resultado dentro da normalidade.',
  'Não se aplica pela idade e contexto clínico.',
  'nao',
  'Movimentos fetais presentes e regulares.',
  'Orientada sobre perda de líquido, sangramento, cefaleia intensa, redução de movimentos fetais e dor persistente.',
  '110x70',
  '67,5 kg',
  '148 bpm',
  '24 cm',
  'Bom estado geral, sem edema, útero compatível com IG.',
  'Hemograma, glicemia e sorologias de rotina do trimestre.',
  'Gestação de baixo risco em evolução adequada.',
  'Manter suplementação, rotina de pré-natal e reavaliação periódica.',
  'Retornar em 4 semanas e procurar serviço se sinais de alarme.'
);

-- -----------------------------------------------------
-- ORTOPEDIA E TRAUMATOLOGIA
-- -----------------------------------------------------
INSERT INTO ortopedia_traumatologia_prontuario (
  id, empresa_id, prontuario_id, paciente_id, profissional_id,
  orto_regiao, orto_lateralidade, orto_queixa_principal,
  orto_mecanismo_trauma, orto_tempo_sintomas, orto_escala_dor,
  orto_limitacao_funcional, orto_trauma_recente, orto_neurovascular,
  orto_inspecao, orto_amplitude_movimento, orto_forca,
  orto_testes_especificos, orto_rx, orto_rm, orto_usg,
  orto_hipotese_diag, orto_classificacao_fratura, orto_imobilizacao,
  orto_medicacoes, orto_fisioterapia, orto_conduta, orto_orientacoes
) VALUES (
  6701, 2, 5007, 2007, 207,
  'Joelho',
  'direita',
  'Dor anterior no joelho direito ao correr e subir escadas.',
  'Sobrecarga esportiva sem trauma de alta energia.',
  '3 semanas',
  6,
  'Desconforto para corrida, agachamento e escadas.',
  'nao',
  'Sem déficit neurovascular distal.',
  'Sem deformidades, discreto edema periarticular.',
  'Amplitude preservada com dor terminal em flexão.',
  'Força global preservada.',
  'Dor à compressão patelar e teste funcional com agachamento positivo para dor.',
  'Sem alterações ósseas agudas aparentes no contexto clínico.',
  'Não indicada neste momento se boa resposta inicial ao tratamento conservador.',
  'Não aplicável.',
  'Síndrome dolorosa femoropatelar / tendinopatia por sobrecarga.',
  NULL,
  'Não indicada.',
  'Anti-inflamatório por curto período e gelo local se necessário.',
  'Fisioterapia com fortalecimento de cadeia cinética e controle de carga.',
  'Tratamento conservador inicial.',
  'Reduzir impacto, alongamentos orientados e retorno progressivo após melhora.'
);

-- -----------------------------------------------------
-- CARDIOLOGIA
-- -----------------------------------------------------
INSERT INTO cardiologia_prontuario (
  id, empresa_id, prontuario_id, paciente_id, profissional_id,
  cardio_queixa_principal, cardio_dor_toracica, cardio_dispneia,
  cardio_palpitacoes, cardio_sincope, cardio_hipertensao,
  cardio_diabetes, cardio_tabagismo, cardio_hist_familiar,
  cardio_pa, cardio_fc, cardio_imc, cardio_exame_fisico,
  cardio_ecg, cardio_eco, cardio_teste_ergometrico,
  cardio_laboratoriais, cardio_hipotese_diag, cardio_risco_cv,
  cardio_medicacoes, cardio_metas, cardio_conduta, cardio_orientacoes
) VALUES (
  6801, 2, 5008, 2008, 208,
  'Palpitações e dispneia aos médios esforços.',
  'nao',
  'aos_medios_esforcos',
  'sim',
  'nao',
  'sim',
  'nao',
  'cessado',
  'Irmão com infarto agudo do miocárdio aos 54 anos.',
  '146x88',
  84,
  28.40,
  'Bulhas normofonéticas em 2 tempos, sem sopros evidentes, sem edema periférico.',
  'ECG solicitado para avaliação de ritmo e possíveis alterações de sobrecarga.',
  'Ecocardiograma solicitado para avaliação estrutural.',
  'Teste ergométrico programado conforme estratificação clínica.',
  'Perfil lipídico e glicemia de jejum solicitados.',
  'Hipertensão arterial sistêmica com palpitações em investigação; risco cardiovascular aumentado.',
  'alto',
  'Anlodipino 5mg/dia mantido; considerar ajuste após MAPA/Holter.',
  'PA < 130x80 mmHg, LDL conforme estratificação e perda ponderal progressiva.',
  'Estratificação completa com ECG, eco, Holter e controle pressórico.',
  'Reduzir sódio, manter atividade física orientada e registrar episódios de palpitação.'
);

-- -----------------------------------------------------
-- OFTALMOLOGIA
-- -----------------------------------------------------
INSERT INTO oftalmologia_prontuario (
  id, empresa_id, prontuario_id, paciente_id, profissional_id,
  oftalmo_queixa_principal, oftalmo_lateralidade, oftalmo_inicio,
  oftalmo_dor_ocular, oftalmo_trauma, oftalmo_cirurgias_previas,
  oftalmo_comorbidades, oftalmo_av_sc_od, oftalmo_av_sc_oe,
  oftalmo_av_cc_od, oftalmo_av_cc_oe, oftalmo_tonometria,
  oftalmo_biomicroscopia, oftalmo_fundo_olho, oftalmo_refracao,
  oftalmo_hipotese_diag, oftalmo_exames_complementares,
  oftalmo_prescricao, oftalmo_conduta, oftalmo_orientacoes
) VALUES (
  6901, 2, 5009, 2009, 209,
  'Baixa visual progressiva e cansaço visual.',
  'ou',
  'Evolução gradual nos últimos meses.',
  'nao',
  'nao',
  'Nega cirurgias oculares prévias.',
  'Histórico familiar de glaucoma materno.',
  '20/60',
  '20/50',
  '20/30',
  '20/25',
  '14/15 mmHg',
  'Biomicroscopia sem sinais inflamatórios importantes; discreta opacificação cristaliniana inicial.',
  'Papilas sem edema; retina sem alterações agudas ao exame clínico inicial.',
  'Melhora parcial com correção óptica subjetiva.',
  'Erro refracional associado a catarata inicial em acompanhamento.',
  'Mapeamento de retina e paquimetria conforme seguimento.',
  'Óculos com atualização de grau conforme refração final.',
  'Acompanhamento periódico e ajuste óptico.',
  'Orientado sobre sinais de piora visual súbita e necessidade de revisão regular.'
);

-- -----------------------------------------------------
-- NEUROLOGIA
-- -----------------------------------------------------
INSERT INTO neurologia_prontuario (
  id, empresa_id, prontuario_id, paciente_id, profissional_id,
  neuro_queixa_principal, neuro_inicio_quadro, neuro_instalacao,
  neuro_cefaleia, neuro_tontura, neuro_fraqueza, neuro_parestesia,
  neuro_crises_convulsivas, neuro_descricao_crise, neuro_frequencia_crises,
  neuro_deficit_focal, neuro_ultimo_evento, neuro_antecedentes,
  neuro_estado_mental, neuro_pares_cranianos, neuro_forca_tono,
  neuro_reflexos, neuro_sensibilidade, neuro_coordenacao, neuro_marcha,
  neuro_sinais_meningeos, neuro_tc, neuro_rm, neuro_eeg, neuro_enmg,
  neuro_liquor, neuro_hipotese_diag, neuro_conduta, neuro_orientacoes,
  neuro_retorno
) VALUES (
  7001, 2, 5010, 2010, 210,
  'Cefaleia recorrente com parestesias transitórias.',
  'Quadro com meses de evolução, mais intenso nas últimas semanas.',
  'cronica',
  'sim',
  'nao',
  'nao',
  'sim',
  'nao',
  NULL,
  NULL,
  'Nega déficit focal persistente; parestesia transitória em membro superior esquerdo.',
  'Crise mais recente há 4 dias.',
  'Histórico pessoal de enxaqueca; mãe com enxaqueca.',
  'Lúcida, orientada e com linguagem preservada.',
  'Sem alterações evidentes ao exame clínico.',
  'Força global preservada, tônus sem alterações relevantes.',
  'Reflexos simétricos.',
  'Sensibilidade preservada no exame atual.',
  'Sem dismetria ou ataxia.',
  'Marcha preservada.',
  'Ausentes.',
  'Não realizada nesta etapa por ausência de sinais agudos.',
  'RM de crânio solicitada para investigação complementar.',
  'Não indicado neste momento.',
  'Não indicado.',
  'Não indicado.',
  'Enxaqueca com aura em investigação diferencial.',
  'Diário de cefaleia, ajuste medicamentoso e RM de crânio.',
  'Retornar com exames e procurar urgência se déficit focal persistente, cefaleia explosiva ou rebaixamento do nível de consciência.',
  '2026-06-12'
);

-- -----------------------------------------------------
-- PROTOCOLOS CLÍNICOS
-- -----------------------------------------------------
INSERT INTO protocolos_clinicos (
  id, empresa_id, tema, titulo, categoria, objetivo, checklist,
  criterios, orientacoes, tags, ativo, criado_por, atualizado_por
) VALUES
  (7101, 2, 'psiquiatria', 'Acolhimento inicial em risco psíquico', 'Risco', 'Padronizar acolhimento, estratificação e encaminhamento de pacientes com sofrimento psíquico agudo.',
   '- Confirmar queixa principal e fator precipitante\n- Avaliar ideação suicida, plano e meios\n- Revisar suporte familiar e rede de proteção\n- Definir necessidade de observação/encaminhamento',
   'Priorizar se risco suicida moderado/alto, agitação importante, psicose desorganizada ou ausência de suporte.',
   'Registrar plano de segurança, orientar sinais de alerta e documentar responsável quando aplicável.',
   'risco, urgência, psiquiatria', 1, 204, 204),
  (7102, 2, 'pediatria', 'Febre sem sinais de toxemia', 'Retorno', 'Organizar avaliação e orientação domiciliar nos quadros febris de baixa complexidade.',
   '- Confirmar duração da febre\n- Checar hidratação, aceitação alimentar e diurese\n- Revisar vacinação\n- Informar sinais de alarme',
   'Reavaliar rapidamente se prostração, desconforto respiratório, sonolência excessiva ou febre persistente.',
   'Entregar orientação escrita de hidratação, controle térmico e retorno programado.',
   'pediatria, febre, sinais de alarme', 1, 205, 205),
  (7103, 2, 'cardiologia', 'Seguimento ambulatorial de hipertensão', 'Seguimento', 'Padronizar consulta de revisão pressórica e adesão ao tratamento.',
   '- Conferir pressão arterial e frequência cardíaca\n- Revisar adesão medicamentosa\n- Checar eventos adversos\n- Solicitar exames conforme risco',
   'Escalonar investigação se PA persistentemente elevada, sintomas de isquemia ou sinais de lesão de órgão-alvo.',
   'Reforçar metas pressóricas, atividade física e retorno com monitorização domiciliar quando possível.',
   'hipertensão, cardiologia, risco cardiovascular', 1, 208, 208),
  (7104, 2, 'neurologia', 'Cefaleia com sinais de atenção', 'Triagem', 'Apoiar triagem clínica de cefaleias com necessidade de investigação complementar.',
   '- Caracterizar padrão da dor\n- Revisar déficits focais e alterações visuais\n- Checar uso prévio de analgesia\n- Definir necessidade de neuroimagem',
   'Encaminhar urgência se déficit focal persistente, cefaleia explosiva, febre com rigidez de nuca ou rebaixamento do nível de consciência.',
   'Orientar diário de sintomas, gatilhos e retorno com exames solicitados.',
   'cefaleia, neurologia, red flags', 1, 210, 210),
  (7105, 2, 'medico', 'Check-up clínico e prevenção cardiometabólica', 'Prevenção', 'Padronizar atendimento clínico preventivo com foco em fatores de risco, exames de rotina e orientação de estilo de vida.',
   '- Revisar antecedentes pessoais e familiares\n- Avaliar pressão arterial, IMC e circunferência abdominal\n- Solicitar/checar exames laboratoriais periódicos\n- Reforçar plano de prevenção e retorno',
   'Priorizar seguimento se houver hipertensão, obesidade, diabetes, tabagismo ou alterações laboratoriais relevantes.',
   'Orientar alimentação, atividade física, sono, cessação do tabagismo e retorno com exames conforme risco.',
   'clinica medica, prevenção, check-up', 1, 201, 201),
  (7106, 2, 'dentista', 'Avaliação inicial com risco de cárie e periodontal', 'Triagem', 'Organizar avaliação odontológica inicial com estratificação de risco, higiene oral e definição do plano terapêutico.',
   '- Identificar queixa principal e dor\n- Avaliar biofilme, sangramento gengival e lesões cariosas\n- Definir necessidade de radiografias\n- Registrar plano imediato e retorno',
   'Antecipar intervenção se dor espontânea, abscesso, sangramento persistente, mobilidade dentária ou suspeita de infecção.',
   'Reforçar técnica de higiene oral, controle de dieta cariogênica e cronograma de tratamento por etapas.',
   'odontologia, cárie, periodontal, triagem', 1, 202, 202),
  (7107, 2, 'psicologia', 'Acolhimento inicial e definição de plano terapêutico', 'Acolhimento', 'Padronizar entrevista inicial, mapeamento de demandas, fatores precipitantes e objetivos terapêuticos.',
   '- Identificar demanda principal e contexto atual\n- Levantar história emocional, familiar e ocupacional\n- Avaliar risco psíquico e rede de apoio\n- Definir objetivo inicial e frequência sugerida',
   'Priorizar encaminhamento compartilhado se houver risco de autoagressão, sintomas graves ou prejuízo funcional importante.',
   'Registrar contrato terapêutico, combinados de acompanhamento e indicadores de evolução clínica.',
   'psicologia, acolhimento, plano terapêutico', 1, 203, 203),
  (7108, 2, 'ginecologia_obstetricia', 'Rastreamento ginecológico e seguimento pré-natal habitual', 'Seguimento', 'Padronizar consulta preventiva ginecológica e o acompanhamento obstétrico de rotina com foco em sinais de alerta.',
   '- Revisar queixas, ciclo menstrual e histórico obstétrico\n- Conferir exames preventivos e laboratoriais\n- Avaliar sinais de alerta ginecológicos/obstétricos\n- Definir retorno e exames complementares',
   'Escalonar rapidamente se houver sangramento anormal, dor intensa, hipertensão gestacional, redução de movimentos fetais ou sinais infecciosos.',
   'Orientar adesão ao pré-natal, exames periódicos, vacinação e retorno imediato diante de sinais de alerta.',
   'ginecologia, obstetricia, pré-natal, rastreio', 1, 206, 206),
  (7109, 2, 'ortopedia_traumatologia', 'Dor osteomuscular sem trauma grave', 'Conduta inicial', 'Padronizar abordagem de dor musculoesquelética ambulatorial com avaliação funcional, analgesia e indicação de imagem.',
   '- Localizar dor e mecanismo de sobrecarga/trauma\n- Avaliar amplitude de movimento e sinais inflamatórios\n- Identificar necessidade de raio-X/USG/RM\n- Definir proteção articular e reabilitação',
   'Encaminhar urgência se houver deformidade, déficit neurovascular, incapacidade de apoio, febre ou suspeita de fratura/agressão articular importante.',
   'Orientar repouso relativo, analgesia, gelo/calor conforme caso e retorno para reavaliação funcional.',
   'ortopedia, trauma, dor musculoesquelética', 1, 207, 207),
  (7110, 2, 'oftalmologia', 'Triagem de baixa visual e olho vermelho', 'Triagem', 'Organizar avaliação inicial de queixas visuais frequentes, identificando sinais de gravidade e necessidade de investigação rápida.',
   '- Avaliar acuidade visual e lateralidade da queixa\n- Investigar dor, secreção, fotofobia e trauma\n- Medir pressão intraocular quando indicado\n- Definir necessidade de exame complementar e retorno',
   'Priorizar urgência se perda visual súbita, trauma ocular, dor intensa, fotofobia importante, secreção purulenta ou suspeita de glaucoma agudo.',
   'Orientar evitar automedicação ocular, manter acompanhamento e retornar imediatamente se houver piora dos sintomas.',
   'oftalmologia, baixa visual, olho vermelho, triagem', 1, 209, 209);

-- -----------------------------------------------------
-- MODELOS CLÍNICOS
-- -----------------------------------------------------
INSERT INTO modelos_clinicos (
  id, empresa_id, tema, tipo_documento, titulo, conteudo,
  campos_chave, tags, ativo, criado_por, atualizado_por
) VALUES
  (7201, 2, 'psiquiatria', 'evolucao', 'Evolução ambulatorial em saúde mental',
   'Paciente retorna em acompanhamento, referindo [sintomas principais]. Relata adesão [boa/parcial/baixa] ao tratamento, com [efeitos/benefícios]. Ao exame psíquico, apresenta [humor/afeto], pensamento [descrição], juízo crítico [descrição]. Mantida/ajustada conduta com [medicação/intervenção], orientado sobre sinais de alerta e retorno em [prazo].',
   'sintomas principais, adesão, exame psíquico, conduta', 'psiquiatria, evolução, retorno', 1, 204, 204),
  (7202, 2, 'pediatria', 'orientacao', 'Orientações domiciliares para febre infantil',
   'Orientado responsável quanto à hidratação frequente, controle térmico conforme peso/idade, repouso relativo e observação de diurese. Retornar imediatamente em caso de prostração, dificuldade respiratória, recusa alimentar persistente, convulsão, sonolência excessiva ou febre prolongada.',
   'hidratação, sinais de alarme, retorno', 'pediatria, orientação, febre', 1, 205, 205),
  (7203, 2, 'ginecologia_obstetricia', 'retorno', 'Retorno de pré-natal habitual',
   'Gestante em seguimento de pré-natal, com boa aceitação alimentar e movimentação fetal presente. Mantidas orientações sobre sinais de alerta, suplementação prescrita e calendário de exames. Próximo retorno programado para [data/período].',
   'idade gestacional, sinais de alerta, próximo retorno', 'pré-natal, obstetrícia, retorno', 1, 206, 206),
  (7204, 2, 'cardiologia', 'laudo', 'Resumo para risco cardiovascular',
   'Paciente em acompanhamento cardiológico por [motivo]. Apresenta fatores de risco: [fatores]. Exames prévios demonstram [resultados]. Mantido plano com foco em metas pressóricas, controle lipídico, atividade física e reavaliação com [exames/retorno].',
   'motivo, fatores de risco, exames, plano', 'cardiologia, laudo, risco', 1, 208, 208),
  (7205, 2, 'neurologia', 'checklist', 'Checklist de retorno para cefaleia',
   '- Atualizar frequência e intensidade da dor\n- Rever gatilhos e medicações usadas\n- Confirmar presença de aura/sinais neurológicos\n- Conferir exames solicitados\n- Reforçar sinais de alarme e plano de seguimento',
   'frequência, intensidade, gatilhos, exames', 'neurologia, cefaleia, checklist', 1, 210, 210);

-- -----------------------------------------------------
-- MODELOS CLÍNICOS ADICIONAIS (TODAS AS ESPECIALIDADES)
-- -----------------------------------------------------
INSERT INTO modelos_clinicos (
  id, empresa_id, tema, tipo_documento, titulo, conteudo,
  campos_chave, tags, ativo, criado_por, atualizado_por
) VALUES
  (7206, 2, 'medico', 'anamnese', 'Anamnese clínica geral inicial', 'Paciente em consulta inicial referindo [queixa principal]. Antecedentes relevantes: [antecedentes]. Medicações em uso: [medicações]. Hábitos de vida: [hábitos]. Exame físico direcionado: [achados]. Hipótese diagnóstica inicial: [hipóteses]. Conduta proposta: [conduta].', 'queixa principal, antecedentes, medicações, hábitos, exame físico', 'medico, anamnese, consulta inicial', 1, 201, 201),
  (7207, 2, 'dentista', 'checklist', 'Checklist de avaliação odontológica inicial', '- Revisar queixa principal e dor
- Inspecionar biofilme, gengiva e restaurações prévias
- Avaliar necessidade radiográfica
- Registrar planejamento imediato e retorno', 'dor, higiene, radiografia, plano', 'odontologia, checklist, avaliação inicial', 1, 202, 202),
  (7208, 2, 'psicologia', 'evolucao', 'Evolução breve de psicoterapia', 'Sessão dedicada a [tema central], com acolhimento de [emoções/reações]. Intervenções realizadas: [intervenções]. Resposta do paciente: [resposta]. Tarefa/combinação para próxima sessão: [tarefa].', 'tema central, intervenções, resposta, tarefa', 'psicologia, evolução, sessão', 1, 203, 203),
  (7209, 2, 'psiquiatria', 'retorno', 'Retorno psiquiátrico de estabilização', 'Paciente retorna referindo [sintomas], adesão [boa/parcial/baixa] ao tratamento e [efeitos colaterais/benefícios]. Avaliado risco atual, sono, apetite e funcionalidade. Mantida/ajustada conduta com retorno em [prazo].', 'sintomas, adesão, risco, sono, funcionalidade', 'psiquiatria, retorno, estabilização', 1, 204, 204),
  (7210, 2, 'pediatria', 'checklist', 'Checklist de puericultura', '- Atualizar peso, estatura e desenvolvimento
- Revisar alimentação, sono e eliminação
- Conferir vacinação
- Orientar sinais de alerta e próximo retorno', 'crescimento, alimentação, vacinação, retorno', 'pediatria, puericultura, checklist', 1, 205, 205),
  (7211, 2, 'ginecologia_obstetricia', 'orientacao', 'Orientações para rotina ginecológica / pré-natal', 'Orientada quanto a sinais de alerta, adesão aos exames preventivos, suplementação quando indicada e retorno se [sintoma]. Manter acompanhamento periódico e hábitos saudáveis.', 'sinais de alerta, exames, suplementação, retorno', 'ginecologia, obstetrícia, orientação', 1, 206, 206),
  (7212, 2, 'ortopedia_traumatologia', 'evolucao', 'Evolução ortopédica por dor mecânica', 'Paciente em seguimento por [dor/lesão], com intensidade [escala]. Exame focal demonstra [achados]. Mantidas orientações de proteção articular, analgesia e reabilitação com retorno em [prazo].', 'dor, exame focal, analgesia, reabilitação', 'ortopedia, evolução, dor', 1, 207, 207),
  (7213, 2, 'cardiologia', 'orientacao', 'Orientações de controle pressórico domiciliar', 'Paciente orientado a registrar pressão arterial em horários padronizados, manter uso correto das medicações, reduzir sal, manter atividade física compatível e retornar com diário pressórico e exames.', 'pressão arterial, medicação, dieta, atividade física', 'cardiologia, orientação, hipertensão', 1, 208, 208),
  (7214, 2, 'oftalmologia', 'laudo', 'Resumo oftalmológico ambulatorial', 'Paciente com [queixa visual], acuidade [achado], superfície ocular [descrição] e hipótese de [diagnóstico]. Indicada correção óptica/lubrificação/investigação complementar com retorno em [prazo].', 'queixa visual, acuidade, hipótese, retorno', 'oftalmologia, laudo, ambulatório', 1, 209, 209),
  (7215, 2, 'neurologia', 'retorno', 'Retorno neurológico para cefaleia e sintomas sensitivos', 'Paciente relata [frequência] de cefaleia, presença/ausência de aura e [sintomas associados]. Exame neurológico atual [achados]. Reforçado diário de sintomas, ajuste terapêutico e sinais de urgência.', 'cefaleia, aura, sintomas associados, exame neurológico', 'neurologia, retorno, cefaleia', 1, 210, 210);

-- -----------------------------------------------------
-- MODELOS DE EXAMES / LAUDOS (TODAS AS ESPECIALIDADES)
-- -----------------------------------------------------
INSERT INTO modelos_clinicos (
  id, empresa_id, tema, tipo_documento, titulo, conteudo,
  campos_chave, tags, ativo, criado_por, atualizado_por
) VALUES
  (7216, 2, 'medico', 'laudo', 'Modelo de solicitação e revisão de exames laboratoriais', 'Solicitados/revistos os seguintes exames: [hemograma], [glicemia], [perfil lipídico], [função renal], [função hepática] e [outros]. Indicação clínica: [motivo]. Resultados principais: [achados]. Conduta após análise: [conduta], com retorno em [prazo].', 'motivo, exames solicitados, achados, conduta, retorno', 'medico, exames, laboratório, laudo', 1, 201, 201),
  (7217, 2, 'dentista', 'laudo', 'Modelo de exames complementares odontológicos', 'Avaliação odontológica com necessidade de exames complementares. Solicitar/revisar: [radiografia periapical], [panorâmica], [interproximal], [tomografia] e [fotografia clínica]. Indicação: [suspeita clínica]. Achados relevantes: [achados]. Planejamento proposto: [plano terapêutico].', 'radiografia, tomografia, achados, plano terapêutico', 'odontologia, exames, radiografia, laudo', 1, 202, 202),
  (7218, 2, 'psicologia', 'laudo', 'Modelo de devolutiva de avaliação psicológica / escalas', 'Realizada avaliação psicológica com uso de [instrumentos/escala], observando [contexto de aplicação]. Resultados sugerem [síntese clínica], com indicadores em [faixa/classificação]. Recomenda-se [encaminhamento/intervenção], mantendo acompanhamento e reavaliação em [prazo].', 'instrumentos, contexto, síntese clínica, classificação, recomendação', 'psicologia, avaliação, escala, devolutiva', 1, 203, 203),
  (7219, 2, 'psiquiatria', 'laudo', 'Modelo de solicitação de exames para seguimento psicofarmacológico', 'Paciente em uso de [medicação] com indicação de solicitar/revisar [hemograma], [TSH], [função hepática], [função renal], [ECG] e [outros] conforme perfil clínico. Objetivo: monitorar [segurança/efeitos adversos/diagnóstico diferencial]. Resultados: [achados]. Conduta: [ajuste terapêutico/seguimento].', 'medicação, exames, segurança, achados, ajuste terapêutico', 'psiquiatria, exames, monitorização, laudo', 1, 204, 204),
  (7220, 2, 'pediatria', 'laudo', 'Modelo de solicitação e revisão de exames pediátricos', 'Paciente pediátrico em investigação de [quadro clínico]. Solicitar/revisar: [hemograma], [PCR], [urina], [teste viral], [imagem] e [outros]. Sinais clínicos associados: [achados]. Interpretação inicial: [síntese]. Orientações ao responsável e retorno em [prazo].', 'quadro clínico, exames, achados, interpretação, retorno', 'pediatria, exames, investigação, laudo', 1, 205, 205),
  (7221, 2, 'ginecologia_obstetricia', 'laudo', 'Modelo de exames preventivos / pré-natal', 'Solicitados/revistos exames de [citologia oncótica], [USG], [sorologias], [tipagem/Rh], [glicemia], [urocultura] e [outros]. Indicação: [rotina ginecológica/pré-natal/queixa]. Resultados principais: [achados]. Conduta e seguimento: [conduta], com retorno em [prazo].', 'citologia, ultrassom, sorologias, achados, conduta', 'ginecologia, obstetrícia, exames, pré-natal', 1, 206, 206),
  (7222, 2, 'ortopedia_traumatologia', 'laudo', 'Modelo de imagem osteomuscular e correlação clínica', 'Paciente com [dor/trauma/limitação]. Solicitar/revisar [raio-X], [ultrassom], [ressonância] ou [tomografia] da região [local]. Achados de imagem: [achados]. Correlação clínica: [hipótese]. Plano de tratamento: [analgesia, fisioterapia, imobilização ou cirurgia], com reavaliação em [prazo].', 'local, exame de imagem, achados, hipótese, tratamento', 'ortopedia, exames, imagem, laudo', 1, 207, 207),
  (7223, 2, 'cardiologia', 'laudo', 'Modelo de revisão de exames cardiovasculares', 'Revisados os exames [ECG], [ecocardiograma], [MAPA/Holter], [teste ergométrico] e [laboratoriais]. Principais achados: [achados]. Estratificação de risco: [baixo/moderado/alto]. Conduta definida: [ajuste medicamentoso, exames adicionais, retorno], com seguimento em [prazo].', 'ECG, ecocardiograma, risco, achados, conduta', 'cardiologia, exames, ECG, ecocardiograma', 1, 208, 208),
  (7224, 2, 'oftalmologia', 'laudo', 'Modelo de avaliação e exames oftalmológicos', 'Paciente com [queixa visual]. Realizados/solicitados [acuidade visual], [refração], [tonometria], [fundoscopia], [biomicroscopia], [campimetria] e [outros]. Achados principais: [achados]. Impressão diagnóstica: [hipótese]. Conduta: [correção óptica/tratamento/investigação], com retorno em [prazo].', 'acuidade visual, tonometria, fundoscopia, achados, conduta', 'oftalmologia, exames, refração, laudo', 1, 209, 209),
  (7225, 2, 'neurologia', 'laudo', 'Modelo de exames neurológicos complementares', 'Paciente em investigação de [cefaleia, crise, déficit, tontura ou parestesia]. Solicitar/revisar [RM], [TC], [EEG], [ENMG], [doppler] e [outros] conforme quadro. Achados: [achados]. Correlação clínica: [hipótese diagnóstica]. Plano: [seguimento/encaminhamento/ajuste terapêutico], com retorno em [prazo].', 'quadro neurológico, RM, EEG, achados, hipótese, plano', 'neurologia, exames, imagem, EEG, laudo', 1, 210, 210);

-- -----------------------------------------------------
-- FINANCEIRO DEMO EXPANDIDO (ATÉ DEZ/2026)
-- -----------------------------------------------------
INSERT INTO contas_financeiras (
  id, empresa_id, categoria_id, paciente_id, agendamento_id, nfse_id,
  tipo, descricao, valor, data_vencimento, data_pagamento, forma_pagamento,
  status, parcela_numero, parcela_total, parcela_grupo, observacoes, criado_por
) VALUES
  (8201, 2, 8101, 2011, 4011, NULL, 'receita', 'Clínica médica - Renata Valverde', 250.00, '2026-07-01', '2026-07-01', 'pix', 'pago', 1, 1, 'REC-4011', 'Recebimento registrado em ambiente demo.', 211),
  (8202, 2, 8101, 2012, 4012, NULL, 'receita', 'Clínica médica - Otávio Menezes', 250.00, '2026-08-12', NULL, 'cartao_credito', 'pendente', 1, 1, 'REC-4012', 'Recebível projetado até o final do ano.', 211),
  (8203, 2, 8101, 2011, 4013, NULL, 'receita', 'Retorno clínica médica - Renata Valverde', 250.00, '2026-09-26', NULL, 'pix', 'pendente', 1, 1, 'REC-4013', 'Retorno futuro previsto em ambiente demo.', 211),
  (8204, 2, 8102, 2013, 4014, NULL, 'receita', 'Odontologia - Bianca Ferraz', 180.00, '2026-07-01', '2026-07-01', 'pix', 'pago', 1, 1, 'REC-4014', 'Recebimento registrado em ambiente demo.', 211),
  (8205, 2, 8102, 2014, 4015, NULL, 'receita', 'Odontologia - Marcelo Tavares', 180.00, '2026-08-15', NULL, 'cartao_credito', 'pendente', 1, 1, 'REC-4015', 'Recebível projetado até o final do ano.', 211),
  (8206, 2, 8102, 2013, 4016, NULL, 'receita', 'Retorno odontologia - Bianca Ferraz', 180.00, '2026-10-10', NULL, 'pix', 'pendente', 1, 1, 'REC-4016', 'Retorno futuro previsto em ambiente demo.', 211),
  (8207, 2, 8103, 2015, 4017, NULL, 'receita', 'Psicologia - Lívia Corrêa', 220.00, '2026-07-02', '2026-07-02', 'pix', 'pago', 1, 1, 'REC-4017', 'Recebimento registrado em ambiente demo.', 211),
  (8208, 2, 8103, 2016, 4018, NULL, 'receita', 'Psicologia - Fernando Cabral', 220.00, '2026-08-20', NULL, 'cartao_credito', 'pendente', 1, 1, 'REC-4018', 'Recebível projetado até o final do ano.', 211),
  (8209, 2, 8103, 2015, 4019, NULL, 'receita', 'Retorno psicologia - Lívia Corrêa', 220.00, '2026-10-24', NULL, 'pix', 'pendente', 1, 1, 'REC-4019', 'Retorno futuro previsto em ambiente demo.', 211),
  (8210, 2, 8104, 2017, 4020, NULL, 'receita', 'Psiquiatria - Patrícia Seabra', 350.00, '2026-07-02', '2026-07-02', 'pix', 'pago', 1, 1, 'REC-4020', 'Recebimento registrado em ambiente demo.', 211),
  (8211, 2, 8104, 2018, 4021, NULL, 'receita', 'Psiquiatria - Tiago Monteiro', 350.00, '2026-09-05', NULL, 'cartao_credito', 'pendente', 1, 1, 'REC-4021', 'Recebível projetado até o final do ano.', 211),
  (8212, 2, 8104, 2017, 4022, NULL, 'receita', 'Retorno psiquiatria - Patrícia Seabra', 350.00, '2026-11-05', NULL, 'pix', 'pendente', 1, 1, 'REC-4022', 'Retorno futuro previsto em ambiente demo.', 211),
  (8213, 2, 8105, 2019, 4023, NULL, 'receita', 'Pediatria - Manuela Dias', 260.00, '2026-07-03', '2026-07-03', 'pix', 'pago', 1, 1, 'REC-4023', 'Recebimento registrado em ambiente demo.', 211),
  (8214, 2, 8105, 2020, 4024, NULL, 'receita', 'Pediatria - Gabriel Bittencourt', 260.00, '2026-09-12', NULL, 'cartao_credito', 'pendente', 1, 1, 'REC-4024', 'Recebível projetado até o final do ano.', 211),
  (8215, 2, 8105, 2019, 4025, NULL, 'receita', 'Retorno pediatria - Manuela Dias', 260.00, '2026-11-12', NULL, 'pix', 'pendente', 1, 1, 'REC-4025', 'Retorno futuro previsto em ambiente demo.', 211),
  (8216, 2, 8106, 2021, 4026, NULL, 'receita', 'Ginecologia e Obstetrícia - Camila Rezende', 290.00, '2026-07-03', '2026-07-03', 'pix', 'pago', 1, 1, 'REC-4026', 'Recebimento registrado em ambiente demo.', 211),
  (8217, 2, 8106, 2022, 4027, NULL, 'receita', 'Ginecologia e Obstetrícia - Natália Fagundes', 290.00, '2026-09-19', NULL, 'cartao_credito', 'pendente', 1, 1, 'REC-4027', 'Recebível projetado até o final do ano.', 211),
  (8218, 2, 8106, 2021, 4028, NULL, 'receita', 'Retorno ginecologia e obstetrícia - Camila Rezende', 290.00, '2026-11-19', NULL, 'pix', 'pendente', 1, 1, 'REC-4028', 'Retorno futuro previsto em ambiente demo.', 211),
  (8219, 2, 8107, 2023, 4029, NULL, 'receita', 'Ortopedia e Traumatologia - Rogério Paiva', 280.00, '2026-07-04', '2026-07-04', 'pix', 'pago', 1, 1, 'REC-4029', 'Recebimento registrado em ambiente demo.', 211),
  (8220, 2, 8107, 2024, 4030, NULL, 'receita', 'Ortopedia e Traumatologia - Helena Duarte', 280.00, '2026-10-03', NULL, 'cartao_credito', 'pendente', 1, 1, 'REC-4030', 'Recebível projetado até o final do ano.', 211),
  (8221, 2, 8107, 2023, 4031, NULL, 'receita', 'Retorno ortopedia e traumatologia - Rogério Paiva', 280.00, '2026-12-03', NULL, 'pix', 'pendente', 1, 1, 'REC-4031', 'Retorno futuro previsto em ambiente demo.', 211),
  (8222, 2, 8108, 2025, 4032, NULL, 'receita', 'Cardiologia - Sérgio Camargo', 320.00, '2026-07-04', '2026-07-04', 'pix', 'pago', 1, 1, 'REC-4032', 'Recebimento registrado em ambiente demo.', 211),
  (8223, 2, 8108, 2026, 4033, NULL, 'receita', 'Cardiologia - Denise Arcoverde', 320.00, '2026-10-17', NULL, 'cartao_credito', 'pendente', 1, 1, 'REC-4033', 'Recebível projetado até o final do ano.', 211),
  (8224, 2, 8108, 2025, 4034, NULL, 'receita', 'Retorno cardiologia - Sérgio Camargo', 320.00, '2026-12-10', NULL, 'pix', 'pendente', 1, 1, 'REC-4034', 'Retorno futuro previsto em ambiente demo.', 211),
  (8225, 2, 8109, 2027, 4035, NULL, 'receita', 'Oftalmologia - Rita Albuquerque', 300.00, '2026-07-05', '2026-07-05', 'pix', 'pago', 1, 1, 'REC-4035', 'Recebimento registrado em ambiente demo.', 211),
  (8226, 2, 8109, 2028, 4036, NULL, 'receita', 'Oftalmologia - Leonardo Abranches', 300.00, '2026-11-07', NULL, 'cartao_credito', 'pendente', 1, 1, 'REC-4036', 'Recebível projetado até o final do ano.', 211),
  (8227, 2, 8109, 2027, 4037, NULL, 'receita', 'Retorno oftalmologia - Rita Albuquerque', 300.00, '2026-12-17', NULL, 'pix', 'pendente', 1, 1, 'REC-4037', 'Retorno futuro previsto em ambiente demo.', 211),
  (8228, 2, 8110, 2029, 4038, NULL, 'receita', 'Neurologia - Marta Neves', 340.00, '2026-07-05', '2026-07-05', 'pix', 'pago', 1, 1, 'REC-4038', 'Recebimento registrado em ambiente demo.', 211),
  (8229, 2, 8110, 2030, 4039, NULL, 'receita', 'Neurologia - Bruno Estrela', 340.00, '2026-11-21', NULL, 'cartao_credito', 'pendente', 1, 1, 'REC-4039', 'Recebível projetado até o final do ano.', 211),
  (8230, 2, 8110, 2029, 4040, NULL, 'receita', 'Retorno neurologia - Marta Neves', 340.00, '2026-12-22', NULL, 'pix', 'pendente', 1, 1, 'REC-4040', 'Retorno futuro previsto em ambiente demo.', 211),
  (8231, 2, 8111, NULL, NULL, NULL, 'despesa', 'Aluguel da clínica - 07/2026', 6800.00, '2026-07-06', '2026-07-06', 'transferencia', 'pago', 1, 1, 'DESP-07-8111', 'Lançamento automático do seed demo expandido.', 200),
  (8232, 2, 8112, NULL, NULL, NULL, 'despesa', 'Folha clínica e apoio operacional - 07/2026', 15400.00, '2026-07-07', '2026-07-07', 'transferencia', 'pago', 1, 1, 'DESP-07-8112', 'Lançamento automático do seed demo expandido.', 200),
  (8233, 2, 8113, NULL, NULL, NULL, 'despesa', 'Materiais e insumos assistenciais - 07/2026', 2450.00, '2026-07-08', '2026-07-08', 'pix', 'pago', 1, 1, 'DESP-07-8113', 'Lançamento automático do seed demo expandido.', 200),
  (8234, 2, 8114, NULL, NULL, NULL, 'despesa', 'Campanhas digitais e captação - 07/2026', 980.00, '2026-07-09', '2026-07-09', 'boleto', 'pago', 1, 1, 'DESP-07-8114', 'Lançamento automático do seed demo expandido.', 200),
  (8235, 2, 8115, NULL, NULL, NULL, 'despesa', 'Serviços terceirizados e laboratório - 07/2026', 1320.00, '2026-07-10', '2026-07-10', 'transferencia', 'pago', 1, 1, 'DESP-07-8115', 'Lançamento automático do seed demo expandido.', 200),
  (8236, 2, 8111, NULL, NULL, NULL, 'despesa', 'Aluguel da clínica - 08/2026', 6800.00, '2026-08-06', '2026-08-06', 'transferencia', 'pago', 1, 1, 'DESP-08-8111', 'Lançamento automático do seed demo expandido.', 200),
  (8237, 2, 8112, NULL, NULL, NULL, 'despesa', 'Folha clínica e apoio operacional - 08/2026', 15400.00, '2026-08-07', '2026-08-07', 'transferencia', 'pago', 1, 1, 'DESP-08-8112', 'Lançamento automático do seed demo expandido.', 200),
  (8238, 2, 8113, NULL, NULL, NULL, 'despesa', 'Materiais e insumos assistenciais - 08/2026', 2450.00, '2026-08-08', '2026-08-08', 'pix', 'pago', 1, 1, 'DESP-08-8113', 'Lançamento automático do seed demo expandido.', 200),
  (8239, 2, 8114, NULL, NULL, NULL, 'despesa', 'Campanhas digitais e captação - 08/2026', 980.00, '2026-08-09', '2026-08-09', 'boleto', 'pago', 1, 1, 'DESP-08-8114', 'Lançamento automático do seed demo expandido.', 200),
  (8240, 2, 8115, NULL, NULL, NULL, 'despesa', 'Serviços terceirizados e laboratório - 08/2026', 1320.00, '2026-08-10', '2026-08-10', 'transferencia', 'pago', 1, 1, 'DESP-08-8115', 'Lançamento automático do seed demo expandido.', 200),
  (8241, 2, 8111, NULL, NULL, NULL, 'despesa', 'Aluguel da clínica - 09/2026', 6800.00, '2026-09-06', '2026-09-06', 'transferencia', 'pago', 1, 1, 'DESP-09-8111', 'Lançamento automático do seed demo expandido.', 200),
  (8242, 2, 8112, NULL, NULL, NULL, 'despesa', 'Folha clínica e apoio operacional - 09/2026', 15400.00, '2026-09-07', '2026-09-07', 'transferencia', 'pago', 1, 1, 'DESP-09-8112', 'Lançamento automático do seed demo expandido.', 200),
  (8243, 2, 8113, NULL, NULL, NULL, 'despesa', 'Materiais e insumos assistenciais - 09/2026', 2450.00, '2026-09-08', '2026-09-08', 'pix', 'pago', 1, 1, 'DESP-09-8113', 'Lançamento automático do seed demo expandido.', 200),
  (8244, 2, 8114, NULL, NULL, NULL, 'despesa', 'Campanhas digitais e captação - 09/2026', 980.00, '2026-09-09', NULL, 'boleto', 'atrasado', 1, 1, 'DESP-09-8114', 'Lançamento automático do seed demo expandido.', 200),
  (8245, 2, 8115, NULL, NULL, NULL, 'despesa', 'Serviços terceirizados e laboratório - 09/2026', 1320.00, '2026-09-10', NULL, 'transferencia', 'pendente', 1, 1, 'DESP-09-8115', 'Lançamento automático do seed demo expandido.', 200),
  (8246, 2, 8111, NULL, NULL, NULL, 'despesa', 'Aluguel da clínica - 10/2026', 6800.00, '2026-10-06', '2026-10-06', 'transferencia', 'pago', 1, 1, 'DESP-10-8111', 'Lançamento automático do seed demo expandido.', 200),
  (8247, 2, 8112, NULL, NULL, NULL, 'despesa', 'Folha clínica e apoio operacional - 10/2026', 15400.00, '2026-10-07', '2026-10-07', 'transferencia', 'pago', 1, 1, 'DESP-10-8112', 'Lançamento automático do seed demo expandido.', 200),
  (8248, 2, 8113, NULL, NULL, NULL, 'despesa', 'Materiais e insumos assistenciais - 10/2026', 2450.00, '2026-10-08', '2026-10-08', 'pix', 'pago', 1, 1, 'DESP-10-8113', 'Lançamento automático do seed demo expandido.', 200),
  (8249, 2, 8114, NULL, NULL, NULL, 'despesa', 'Campanhas digitais e captação - 10/2026', 980.00, '2026-10-09', NULL, 'boleto', 'pendente', 1, 1, 'DESP-10-8114', 'Lançamento automático do seed demo expandido.', 200),
  (8250, 2, 8115, NULL, NULL, NULL, 'despesa', 'Serviços terceirizados e laboratório - 10/2026', 1320.00, '2026-10-10', NULL, 'transferencia', 'pendente', 1, 1, 'DESP-10-8115', 'Lançamento automático do seed demo expandido.', 200),
  (8251, 2, 8111, NULL, NULL, NULL, 'despesa', 'Aluguel da clínica - 11/2026', 6800.00, '2026-11-06', '2026-11-06', 'transferencia', 'pago', 1, 1, 'DESP-11-8111', 'Lançamento automático do seed demo expandido.', 200),
  (8252, 2, 8112, NULL, NULL, NULL, 'despesa', 'Folha clínica e apoio operacional - 11/2026', 15400.00, '2026-11-07', '2026-11-07', 'transferencia', 'pago', 1, 1, 'DESP-11-8112', 'Lançamento automático do seed demo expandido.', 200),
  (8253, 2, 8113, NULL, NULL, NULL, 'despesa', 'Materiais e insumos assistenciais - 11/2026', 2450.00, '2026-11-08', '2026-11-08', 'pix', 'pago', 1, 1, 'DESP-11-8113', 'Lançamento automático do seed demo expandido.', 200),
  (8254, 2, 8114, NULL, NULL, NULL, 'despesa', 'Campanhas digitais e captação - 11/2026', 980.00, '2026-11-09', NULL, 'boleto', 'pendente', 1, 1, 'DESP-11-8114', 'Lançamento automático do seed demo expandido.', 200),
  (8255, 2, 8115, NULL, NULL, NULL, 'despesa', 'Serviços terceirizados e laboratório - 11/2026', 1320.00, '2026-11-10', NULL, 'transferencia', 'pendente', 1, 1, 'DESP-11-8115', 'Lançamento automático do seed demo expandido.', 200),
  (8256, 2, 8111, NULL, NULL, NULL, 'despesa', 'Aluguel da clínica - 12/2026', 6800.00, '2026-12-06', '2026-12-06', 'transferencia', 'pago', 1, 1, 'DESP-12-8111', 'Lançamento automático do seed demo expandido.', 200),
  (8257, 2, 8112, NULL, NULL, NULL, 'despesa', 'Folha clínica e apoio operacional - 12/2026', 15400.00, '2026-12-07', '2026-12-07', 'transferencia', 'pago', 1, 1, 'DESP-12-8112', 'Lançamento automático do seed demo expandido.', 200),
  (8258, 2, 8113, NULL, NULL, NULL, 'despesa', 'Materiais e insumos assistenciais - 12/2026', 2450.00, '2026-12-08', '2026-12-08', 'pix', 'pago', 1, 1, 'DESP-12-8113', 'Lançamento automático do seed demo expandido.', 200),
  (8259, 2, 8114, NULL, NULL, NULL, 'despesa', 'Campanhas digitais e captação - 12/2026', 980.00, '2026-12-09', NULL, 'boleto', 'pendente', 1, 1, 'DESP-12-8114', 'Lançamento automático do seed demo expandido.', 200),
  (8260, 2, 8115, NULL, NULL, NULL, 'despesa', 'Serviços terceirizados e laboratório - 12/2026', 1320.00, '2026-12-10', NULL, 'transferencia', 'pendente', 1, 1, 'DESP-12-8115', 'Lançamento automático do seed demo expandido.', 200);

-- -----------------------------------------------------
-- ALERTAS DO PACIENTE
-- -----------------------------------------------------
INSERT INTO paciente_alertas (
  id, empresa_id, paciente_id, tema, tipo, prioridade, titulo,
  descricao, inicio_em, fim_em, ativo, criado_por, atualizado_por
) VALUES
  (7301, 2, 2001, 'medico', 'alergia', 'alta', 'Alergia medicamentosa registrada',
   'Paciente refere alergia a dipirona; evitar prescrição sem revisão prévia.', '2026-05-12', NULL, 1, 201, 201),
  (7302, 2, 2005, 'pediatria', 'retorno', 'moderada', 'Retorno clínico em 72 horas se febre persistir',
   'Mãe orientada a retornar antes se houver prostração, recusa alimentar ou desconforto respiratório.', '2026-05-12', '2026-05-15', 1, 205, 205),
  (7303, 2, 2008, 'cardiologia', 'risco', 'critica', 'Estratificação cardiovascular pendente',
   'Paciente com hipertensão e palpitações; acompanhar realização de ECG, eco e Holter.', '2026-05-13', NULL, 1, 208, 208),
  (7304, 2, 2010, 'neurologia', 'clinico', 'alta', 'Monitorar sinais neurológicos de alerta',
   'Orientada a procurar urgência se déficit focal persistente, cefaleia explosiva ou rebaixamento do nível de consciência.', '2026-05-13', NULL, 1, 210, 210);

-- -----------------------------------------------------
-- PLANOS DE CUIDADO
-- -----------------------------------------------------
INSERT INTO planos_cuidado (
  id, empresa_id, paciente_id, profissional_id, tema, titulo, objetivo,
  metas, conduta, frequencia_retorno, proxima_revisao, status,
  indicadores, orientacoes
) VALUES
  (7401, 2, 2004, 204, 'psiquiatria', 'Plano de seguimento do humor e sono',
   'Reduzir sintomas depressivos e melhorar higiene do sono.',
   '- Reavaliar humor e energia em 15 dias\n- Monitorar adesão ao antidepressivo\n- Revisar rotina de sono',
   'Ajuste farmacológico, psicoeducação e rastreio de risco em cada retorno.',
   'quinzenal', '2026-05-27', 'em_acompanhamento',
   'PHQ-9, qualidade do sono, adesão medicamentosa',
   'Manter rotina regular de sono e buscar suporte se houver piora aguda.'),
  (7402, 2, 2006, 206, 'ginecologia_obstetricia', 'Plano de cuidado pré-natal habitual',
   'Manter gestação de baixo risco com seguimento adequado.',
   '- Realizar exames do trimestre\n- Acompanhar ganho ponderal\n- Revisar sinais de alerta obstétricos',
   'Consultas periódicas, suplementação e educação em saúde.',
   'mensal', '2026-06-09', 'ativo',
   'PA, BCF, peso materno, exames laboratoriais',
   'Retornar antes em caso de sangramento, redução de movimentos fetais ou cefaleia persistente.'),
  (7403, 2, 2008, 208, 'cardiologia', 'Controle pressórico e investigação de palpitações',
   'Melhorar controle da pressão arterial e concluir estratificação cardiovascular.',
   '- Monitorar PA domiciliar\n- Realizar ECG, eco e Holter\n- Reforçar adesão ao tratamento',
   'Ajuste antihipertensivo, exame complementar e revisão de estilo de vida.',
   '30 dias', '2026-06-12', 'ativo',
   'PA média, FC, sintomas de palpitação, exames realizados',
   'Evitar automedicação e procurar atendimento se dor torácica ou dispneia importante.'),
  (7404, 2, 2010, 210, 'neurologia', 'Seguimento de cefaleia com aura',
   'Reduzir frequência das crises e excluir causas secundárias.',
   '- Preencher diário de cefaleia\n- Realizar RM de crânio\n- Revisar resposta ao tratamento',
   'Profilaxia medicamentosa, educação sobre gatilhos e revisão com exames.',
   '30 dias', '2026-06-12', 'ativo',
   'Frequência das crises, intensidade, sintomas neurológicos associados',
   'Procurar urgência se ocorrer déficit focal persistente ou alteração do nível de consciência.');

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = @OLD_SQL_SAFE_UPDATES;
