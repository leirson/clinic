SET NAMES utf8mb4;
-- =====================================================
-- ClinicApp - Seed de Dados de Exemplo
-- Uso recomendado: ambiente de demonstração / homologação
-- Execute APÓS sql/database.sql e sql/upgrade_v2.sql
-- Senha padrão dos usuários de exemplo: admin123
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Limpeza de dados anteriores (somente demo)
DELETE FROM `teleconsultas`;
DELETE FROM `solicitacoes_exame`;
DELETE FROM `paciente_diagnosticos`;
DELETE FROM `receitas`;
DELETE FROM `odontograma_historico`;
DELETE FROM `odontograma`;
DELETE FROM `plano_tratamento_itens`;
DELETE FROM `planos_tratamento`;
DELETE FROM `prontuarios`;
DELETE FROM `agendamentos`;
DELETE FROM `contas_financeiras`;
DELETE FROM `categorias_financeiras`;
DELETE FROM `anexos`;
DELETE FROM `logs`;
DELETE FROM `importacoes`;
DELETE FROM `backups`;
DELETE FROM `procedimentos`;
DELETE FROM `pacientes`;
DELETE FROM `usuarios`;
DELETE FROM `empresas`;

ALTER TABLE `empresas` AUTO_INCREMENT = 1;
ALTER TABLE `usuarios` AUTO_INCREMENT = 1;
ALTER TABLE `pacientes` AUTO_INCREMENT = 1;
ALTER TABLE `procedimentos` AUTO_INCREMENT = 1;
ALTER TABLE `agendamentos` AUTO_INCREMENT = 1;
ALTER TABLE `prontuarios` AUTO_INCREMENT = 1;
ALTER TABLE `odontograma` AUTO_INCREMENT = 1;
ALTER TABLE `receitas` AUTO_INCREMENT = 1;
ALTER TABLE `anexos` AUTO_INCREMENT = 1;
ALTER TABLE `categorias_financeiras` AUTO_INCREMENT = 1;
ALTER TABLE `contas_financeiras` AUTO_INCREMENT = 1;
ALTER TABLE `planos_tratamento` AUTO_INCREMENT = 1;
ALTER TABLE `plano_tratamento_itens` AUTO_INCREMENT = 1;
ALTER TABLE `odontograma_historico` AUTO_INCREMENT = 1;
ALTER TABLE `teleconsultas` AUTO_INCREMENT = 1;
ALTER TABLE `paciente_diagnosticos` AUTO_INCREMENT = 1;
ALTER TABLE `exames_modelos` AUTO_INCREMENT = 1;
ALTER TABLE `solicitacoes_exame` AUTO_INCREMENT = 1;
ALTER TABLE `medicamentos` AUTO_INCREMENT = 1;
ALTER TABLE `backups` AUTO_INCREMENT = 1;
ALTER TABLE `importacoes` AUTO_INCREMENT = 1;

-- =====================================================
-- EMPRESA
-- =====================================================
INSERT INTO `empresas` (
  `id`,`razao_social`,`nome_fantasia`,`cnpj`,`inscricao_municipal`,`inscricao_estadual`,`cnae`,`item_lista_servico`,
  `codigo_tributacao_municipio`,`regime_tributario`,`optante_simples`,`incentivador_cultural`,`aliquota_iss`,
  `endereco_cep`,`endereco_logradouro`,`endereco_numero`,`endereco_complemento`,`endereco_bairro`,`endereco_cidade`,`endereco_uf`,`endereco_codigo_municipio`,
  `telefone`,`email`,`tema_padrao`,`asten_ambiente`,`asten_url_ws`,`asten_serie_rps`,`asten_proximo_rps`
) VALUES (
  1,'Clínica Vida Integrada LTDA','Clínica Vida Integrada','12.345.678/0001-90','123456','ISENTO','8630-5/03','4.01',
  '0401','1',1,0,2.00,
  '01310-100','Avenida Paulista','1500','Conjunto 1204','Bela Vista','São Paulo','SP','3550308',
  '(11) 3333-4400','contato@clinicavida.demo','medico','homologacao','https://homologacao.asten.com.br/nfse/ws/','1',128
);

-- =====================================================
-- USUÁRIOS
-- senha padrão para todos: admin123
-- =====================================================
INSERT INTO `usuarios` (
  `id`,`empresa_id`,`nome`,`email`,`senha`,`perfil`,`especialidade`,`registro_profissional`,`telefone`,`tema_preferido`,`mfa_metodo`,`cor_tema`,`ativo`,`ultimo_login`
) VALUES
(1,1,'Administrador do Sistema','admin@clinicapp.com','$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa','admin','Gestão Clínica',NULL,'(11) 99999-0001','medico','nenhum','blue',1,'2026-05-14 08:12:00'),
(2,1,'Dra. Mariana Costa','mariana@clinicapp.com','$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa','profissional','Clínica Geral','CRM-SP 123456','(11) 99999-0002','medico','totp','indigo',1,'2026-05-14 09:02:00'),
(3,1,'Dr. Felipe Azevedo','felipe@clinicapp.com','$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa','profissional','Odontologia','CRO-SP 65432','(11) 99999-0003','dentista','email','teal',1,'2026-05-14 10:16:00'),
(4,1,'Juliana Recepção','juliana@clinicapp.com','$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa','recepcao','Atendimento',NULL,'(11) 99999-0004','medico','nenhum','green',1,'2026-05-14 07:55:00'),
(5,1,'Dra. Larissa Menezes','larissa@clinicapp.com','$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa','profissional','Psicologia Clínica','CRP-SP 12345','(11) 98888-7755','psicologia','email','purple',1,'2026-05-14 11:20:00');

-- =====================================================
-- PACIENTES
-- =====================================================
INSERT INTO `pacientes` (
  `id`,`empresa_id`,`nome`,`tipo_pessoa`,`cpf_cnpj`,`rg`,`data_nascimento`,`sexo`,`email`,`telefone`,`celular`,`profissao`,`estado_civil`,`convenio`,`numero_carteirinha`,
  `cep`,`logradouro`,`numero`,`complemento`,`bairro`,`cidade`,`uf`,`codigo_municipio`,
  `tipo_sanguineo`,`alergias`,`medicamentos_uso`,`historico_familiar`,`observacoes`,`ativo`
) VALUES
(1,1,'Ana Paula Ribeiro','F','123.456.789-01','23.456.789-0','1989-04-12','F','ana.ribeiro@email.demo','(11) 3322-1101','(11) 98888-1101','Arquiteta','Casada','SulAmérica','SA-203948','01311-000','Rua Cincinato Braga','220','Apto 54','Bela Vista','São Paulo','SP','3550308','O+','Dipirona','Losartana 50mg','Hipertensão em ambos os pais','Paciente acompanha pressão em casa.',1),
(2,1,'Carlos Henrique Souza','F','234.567.890-12','34.567.890-1','1978-08-03','M','carlos.souza@email.demo','(11) 3344-2202','(11) 97777-2202','Analista Financeiro','Divorciado','Bradesco Saúde','BR-778812','01415-001','Alameda Santos','890',NULL,'Cerqueira César','São Paulo','SP','3550308','A+','Penicilina','Metformina 850mg','Diabetes tipo 2 na família','Paciente com controle glicêmico irregular.',1),
(3,1,'Marina Lopes Ferreira','F','345.678.901-23','45.678.901-2','1995-01-21','F','marina.lopes@email.demo','(11) 3355-3303','(11) 96666-3303','Designer','Solteira','Unimed','UN-554433','04003-001','Rua Vergueiro','1450','Sala 03','Paraíso','São Paulo','SP','3550308','B+','Látex','Antialérgico se necessário','Asma em irmã','Prefere lembrete por WhatsApp.',1),
(4,1,'Roberto Almeida Santos','F','456.789.012-34','56.789.012-3','1967-11-14','M','roberto.almeida@email.demo','(11) 3366-4404','(11) 95555-4404','Aposentado','Casado','Particular',NULL,'04101-300','Rua Domingos de Morais','450',NULL,'Vila Mariana','São Paulo','SP','3550308','AB-','AAS','Sinvastatina 20mg','Histórico familiar de DAC','Retornos trimestrais.',1),
(5,1,'Beatriz Nunes Prado','F','567.890.123-45','67.890.123-4','2002-06-09','F','beatriz.prado@email.demo','(11) 3377-5505','(11) 94444-5505','Estudante','Solteira','Amil','AM-991122','04567-000','Rua Funchal','310','Bloco B','Vila Olímpia','São Paulo','SP','3550308','O-','Nenhuma','Nenhum','Mãe com hipotireoidismo','Paciente odontológica.',1),
(6,1,'João Pedro Lima','F','678.901.234-56','78.901.234-5','1984-09-27','M','joao.lima@email.demo','(11) 3388-6606','(11) 93333-6606','Empresário','Casado','Particular',NULL,'04795-100','Avenida João Dias','1200','Conj. 45','Santo Amaro','São Paulo','SP','3550308','B-','Iodado','Omeprazol eventual','Pai com câncer de próstata','Paciente aprecia teleconsulta para retorno.',1),
(7,1,'Helena Martins Rocha','F','789.012.345-67','89.012.345-6','2012-03-18','F','responsavel.helena@email.demo','(11) 3399-7707','(11) 92222-7707','Estudante','Solteira','Porto Seguro','PS-445566','05001-000','Rua Heitor Penteado','175',NULL,'Sumaré','São Paulo','SP','3550308','A-','Amoxicilina','Nenhum','Histórico de rinite alérgica','Paciente odontopediátrica.',1),
(8,1,'Empresa Exemplo Serviços Ltda','J','98.765.432/0001-10',NULL,NULL,NULL,'financeiro@empresaexemplo.demo','(11) 3000-8080','(11) 91111-8080','Serviços Administrativos',NULL,'Particular',NULL,'01153-000','Rua Barra Funda','820','Conjunto 11','Barra Funda','São Paulo','SP','3550308',NULL,NULL,NULL,NULL,'Cliente PJ para emissão de NFSe.',1);

-- =====================================================
-- PROCEDIMENTOS
-- =====================================================
INSERT INTO `procedimentos` (`id`,`empresa_id`,`nome`,`descricao`,`valor`,`duracao_minutos`,`codigo_servico`,`categoria`,`ativo`) VALUES
(1,1,'Consulta Clínica Geral','Avaliação médica inicial com anamnese e conduta',250.00,30,'4.01','clinico',1),
(2,1,'Retorno Médico','Reavaliação de tratamento ou exames',180.00,20,'4.01','clinico',1),
(3,1,'Teleconsulta Clínica','Atendimento remoto por sala virtual',220.00,30,'4.01','clinico',1),
(4,1,'Profilaxia Dental','Limpeza, remoção de placa e polimento',190.00,45,'4.03','odontologico',1),
(5,1,'Restauração em Resina','Restauração de elemento dentário',320.00,60,'4.03','odontologico',1),
(6,1,'Avaliação Odontológica','Consulta inicial odontológica',160.00,30,'4.03','odontologico',1),
(7,1,'Check-up Laboratorial','Solicitação de rotina com interpretação posterior',140.00,15,'4.01','exame',1),
(8,1,'Sessão de Psicoterapia','Atendimento psicológico individual com evolução terapêutica',210.00,50,'4.01','clinico',1);

-- =====================================================
-- CATEGORIAS FINANCEIRAS
-- =====================================================
INSERT INTO `categorias_financeiras` (`id`,`empresa_id`,`nome`,`tipo`,`cor`,`ativo`) VALUES
(1,1,'Consultas','receita','#10b981',1),
(2,1,'Procedimentos Odontológicos','receita','#0ea5e9',1),
(3,1,'Telemedicina','receita','#8b5cf6',1),
(4,1,'Exames e Check-up','receita','#14b8a6',1),
(5,1,'Aluguel','despesa','#ef4444',1),
(6,1,'Folha de Pagamento','despesa','#f59e0b',1),
(7,1,'Materiais Clínicos','despesa','#ec4899',1),
(8,1,'Marketing Digital','despesa','#6366f1',1);

-- =====================================================
-- AGENDAMENTOS / CONSULTAS
-- =====================================================
INSERT INTO `agendamentos` (
  `id`,`empresa_id`,`paciente_id`,`profissional_id`,`procedimento_id`,`data_hora_inicio`,`data_hora_fim`,`status`,`modalidade`,`observacoes`,`valor`,`lembrete_email_enviado`,`criado_por`
) VALUES
(1,1,1,2,1,'2026-05-05 09:00:00','2026-05-05 09:30:00','realizado','presencial','Consulta de rotina com ajuste de anti-hipertensivo.',250.00,1,4),
(2,1,2,2,7,'2026-05-06 10:00:00','2026-05-06 10:20:00','realizado','presencial','Solicitação de exames metabólicos.',140.00,1,4),
(3,1,3,2,3,'2026-05-07 15:00:00','2026-05-07 15:30:00','realizado','telemedicina','Retorno por teleconsulta após sinusite.',220.00,1,4),
(4,1,4,2,2,'2026-05-12 08:40:00','2026-05-12 09:00:00','confirmado','presencial','Revisão de exames e ajuste de estatina.',180.00,1,4),
(5,1,5,3,6,'2026-05-08 14:00:00','2026-05-08 14:30:00','realizado','presencial','Avaliação odontológica inicial.',160.00,1,4),
(6,1,5,3,5,'2026-05-16 11:00:00','2026-05-16 12:00:00','agendado','presencial','Restauração do elemento 16.',320.00,0,4),
(7,1,7,3,4,'2026-05-17 09:00:00','2026-05-17 09:45:00','confirmado','presencial','Profilaxia e orientação de higiene oral.',190.00,0,4),
(8,1,6,2,3,'2026-05-18 18:00:00','2026-05-18 18:30:00','agendado','telemedicina','Teleconsulta para retorno de gastrite.',220.00,0,4),
(9,1,1,2,2,'2026-05-20 16:20:00','2026-05-20 16:40:00','agendado','presencial','Retorno para avaliação de pressão arterial.',180.00,0,4),
(10,1,3,2,1,'2026-05-10 11:00:00','2026-05-10 11:30:00','faltou','presencial','Paciente não compareceu.',250.00,1,4);

-- =====================================================
-- PRONTUÁRIOS
-- =====================================================
INSERT INTO `prontuarios` (
  `id`,`paciente_id`,`profissional_id`,`agendamento_id`,`data_atendimento`,`anamnese`,`queixa_principal`,`exame_fisico`,`hipotese_diagnostica`,`conduta`,`evolucao`,`observacoes`
) VALUES
(1,1,2,1,'2026-05-05 09:15:00','Hipertensa há 5 anos, em uso de losartana 50mg/dia. Refere sedentarismo parcial.','Cefaleia leve e necessidade de revisão de medicação.','PA 148x92 mmHg, FC 78 bpm, sem sinais de alarme.','Hipertensão arterial sistêmica sem controle ideal.','Reforçadas medidas não farmacológicas e aumento de seguimento. Mantida losartana e solicitado MAPA se persistência.','Paciente compreendeu orientação e retornará em 15 dias.','Preferir consultas pela manhã.'),
(2,2,2,2,'2026-05-06 10:10:00','DM2 em uso irregular de metformina. Dieta com alto teor de carboidrato.','Cansaço, sede aumentada e oscilação glicêmica.','IMC 31, glicemia capilar 212 mg/dL.','Diabetes mellitus tipo 2 descompensado.','Solicitados exames laboratoriais completos, reforço dietético e retorno em 7 dias.','Motivado a aderir melhor ao tratamento.','Necessita acompanhamento multiprofissional.'),
(3,3,2,3,'2026-05-07 15:10:00','Histórico de rinite alérgica e sinusite de repetição.','Congestão nasal residual após tratamento.','Sem febre, saturação normal, fala em frases completas.','Sinusite aguda em melhora clínica.','Manter lavagem nasal, hidratação e finalizar medicação prescrita anteriormente.','Boa resposta terapêutica; liberada para atividades habituais.','Teleconsulta concluída sem intercorrências.'),
(4,5,3,5,'2026-05-08 14:10:00','Paciente sem comorbidades, escovação 2x ao dia, uso irregular de fio dental.','Sensibilidade em molar superior direito.','Lesão de cárie em elemento 16, biofilme supragengival leve.','Cárie dentária em esmalte/dentina no elemento 16.','Indicada restauração em resina, orientação de higiene e profilaxia.','Paciente aceitou plano de tratamento proposto.','Solicitada radiografia bite-wing se sensibilidade persistir.'),
(5,4,2,4,'2026-05-12 08:50:00','Dislipidemia em uso de sinvastatina 20mg. Sem dor torácica.','Retorno com exames de rotina.','PA 128x80 mmHg, LDL ainda acima da meta.','Dislipidemia mista com resposta parcial ao tratamento.','Ajustado tratamento e mantido acompanhamento trimestral.','Evolução estável.','Reforçada adesão à dieta e caminhada.'),
(6,7,3,7,'2026-05-17 09:20:00','Paciente pediátrica com rinite alérgica, boa colaboração em consultório.','Consulta preventiva odontológica.','Placa bacteriana leve e inflamação gengival discreta.','Gengivite leve associada à higiene insuficiente.','Profilaxia, aplicação tópica de flúor e reforço educativo para responsável.','Sem dor, procedimento bem tolerado.','Retorno preventivo em 6 meses.');

-- =====================================================
-- DIAGNÓSTICOS / CID-10
-- =====================================================
INSERT INTO `paciente_diagnosticos` (
  `id`,`paciente_id`,`prontuario_id`,`cid_codigo`,`descricao`,`tipo`,`data_diagnostico`,`profissional_id`
) VALUES
(1,1,1,'I10','Hipertensão essencial','principal','2026-05-05',2),
(2,2,2,'E11','Diabetes mellitus tipo 2','principal','2026-05-06',2),
(3,3,3,'J01','Sinusite aguda','principal','2026-05-07',2),
(4,4,5,'E78','Dislipidemia / distúrbio do metabolismo de lipoproteínas','principal','2026-05-12',2);

-- =====================================================
-- RECEITAS / PRESCRIÇÕES / ATESTADOS
-- =====================================================
INSERT INTO `receitas` (
  `id`,`paciente_id`,`profissional_id`,`prontuario_id`,`tipo`,`conteudo`,`data_emissao`,`dias_afastamento`,`cid`,`assinatura_certificado`,`hash_documento`
) VALUES
(1,1,2,1,'simples','Losartana 50mg - tomar 1 comprimido VO pela manhã por 30 dias.\nAferir pressão arterial 2x por semana e registrar.','2026-05-05',NULL,'I10','ICP-Brasil Demo','HASH-DEMO-0001'),
(2,2,2,2,'simples','Metformina 850mg - tomar 1 comprimido VO após café e jantar por 60 dias.\nOrientado controle alimentar.','2026-05-06',NULL,'E11','ICP-Brasil Demo','HASH-DEMO-0002'),
(3,3,2,3,'atestado','Atesto para fins laborais que a paciente necessita de 2 dias de afastamento para recuperação clínica.','2026-05-07',2,'J01','ICP-Brasil Demo','HASH-DEMO-0003'),
(4,5,3,4,'simples','Ibuprofeno 400mg - tomar 1 comprimido VO a cada 8h por 3 dias se dor.','2026-05-08',NULL,NULL,'ICP-Brasil Demo','HASH-DEMO-0004');

-- =====================================================
-- SOLICITAÇÕES DE EXAME
-- =====================================================
INSERT INTO `solicitacoes_exame` (
  `id`,`paciente_id`,`profissional_id`,`prontuario_id`,`exames`,`indicacao_clinica`,`cid_codigo`,`urgencia`,`data_solicitacao`,`status`,`arquivo_resultado`,`observacoes`
) VALUES
(1,2,2,2,'Hemograma completo\nGlicose em jejum\nHemoglobina glicada\nCreatinina\nPerfil lipídico','Avaliação metabólica de diabetes tipo 2 com fadiga e polidipsia.','E11','urgente','2026-05-06','solicitado',NULL,'Paciente orientado sobre jejum de 8 a 12 horas.'),
(2,1,2,1,'MAPA 24h\nCreatinina\nPotássio sérico','Ajuste terapêutico em hipertensão com controle pressórico inadequado.','I10','rotina','2026-05-05','solicitado',NULL,'Retornar com exames em até 15 dias.'),
(3,4,2,5,'Perfil lipídico\nTGO/TGP\nCK total','Monitoramento de estatina e risco cardiovascular.','E78','rotina','2026-05-12','recebido','exames/resultado_roberto_almeida.pdf','Resultados inseridos no prontuário.'),
(4,5,3,4,'Radiografia bite-wing bilateral','Avaliação complementar por sensibilidade em elemento 16.','K02','rotina','2026-05-08','solicitado',NULL,'Exame odontológico complementar.');

-- =====================================================
-- TELECONSULTAS
-- =====================================================
INSERT INTO `teleconsultas` (
  `id`,`agendamento_id`,`sala_id`,`link_paciente`,`link_profissional`,`iniciada_em`,`encerrada_em`,`gravacao_url`,`consentimento_paciente`
) VALUES
(1,3,'clinicapp-marina-retorno-20260507','https://meet.jit.si/clinicapp-marina-retorno-20260507','https://meet.jit.si/clinicapp-marina-retorno-20260507#config.startWithVideoMuted=false','2026-05-07 15:00:00','2026-05-07 15:26:00',NULL,1),
(2,8,'clinicapp-joao-tele-20260518','https://meet.jit.si/clinicapp-joao-tele-20260518','https://meet.jit.si/clinicapp-joao-tele-20260518#config.startWithAudioMuted=false',NULL,NULL,NULL,1);

-- =====================================================
-- ODONTOGRAMA (exemplos odontológicos)
-- =====================================================
INSERT INTO `odontograma` (
  `id`,`paciente_id`,`dente`,`face`,`tipo_denticao`,`condicao`,`cor`,`observacao`,`data_registro`,`profissional_id`,`tratamento_planejado`,`valor_estimado`
) VALUES
(1,5,'16','oclusal','permanente','carie','#ef4444','Lesão oclusal com indicação restauradora.','2026-05-08',3,1,320.00),
(2,5,'26','geral','permanente','restauracao','#0ea5e9','Restauração antiga em bom estado.','2026-05-08',3,0,NULL),
(3,7,'55','oclusal','deciduo','selante','#10b981','Selante preservado.','2026-05-17',3,0,NULL),
(4,7,'16','geral','misto','erupcao','#f59e0b','Dente em erupção acompanhada.','2026-05-17',3,0,NULL);

INSERT INTO `odontograma_historico` (
  `id`,`paciente_id`,`dente`,`face`,`condicao_anterior`,`condicao_nova`,`observacao`,`profissional_id`,`data_registro`
) VALUES
(1,5,'16','oclusal',NULL,'carie','Primeiro registro de lesão em avaliação inicial.',3,'2026-05-08 14:18:00'),
(2,7,'16','geral',NULL,'erupcao','Acompanhamento de dentição mista.',3,'2026-05-17 09:25:00');

-- =====================================================
-- PLANO DE TRATAMENTO ODONTOLÓGICO
-- =====================================================
INSERT INTO `planos_tratamento` (
  `id`,`empresa_id`,`paciente_id`,`profissional_id`,`titulo`,`descricao`,`valor_total`,`desconto`,`valor_final`,`parcelas`,`status`,`aprovado_em`,`aprovado_assinatura`,`validade`,`observacoes`
) VALUES
(1,1,5,3,'Plano Restaurador Inicial','Tratamento para restauração do elemento 16 e profilaxia preventiva.',510.00,30.00,480.00,2,'aprovado','2026-05-08 14:25:00','ASSINATURA-DEMO-BEATRIZ','2026-06-08','Parcelamento em 2x no cartão.'),
(2,1,7,3,'Plano Preventivo Infantil','Acompanhamento semestral, profilaxia e reforço de higiene.',190.00,0.00,190.00,1,'em_execucao',NULL,NULL,'2026-11-17','Responsável orientado sobre alimentação cariogênica.');

INSERT INTO `plano_tratamento_itens` (
  `id`,`plano_id`,`procedimento_id`,`dente`,`face`,`descricao`,`quantidade`,`valor_unitario`,`valor_total`,`status`,`executado_em`,`executado_por`,`ordem`
) VALUES
(1,1,5,'16','oclusal','Restauração em resina composta do elemento 16',1,320.00,320.00,'pendente',NULL,NULL,1),
(2,1,4,NULL,NULL,'Profilaxia e orientação de higiene oral',1,190.00,190.00,'concluido','2026-05-08',3,2),
(3,2,4,NULL,NULL,'Profilaxia preventiva infantil',1,190.00,190.00,'em_andamento','2026-05-17',3,1);

-- =====================================================
-- PSICOLOGIA
-- =====================================================
INSERT INTO `psicologia_planos` (
  `id`,`empresa_id`,`paciente_id`,`profissional_id`,`abordagem`,`objetivo_principal`,`metas`,`frequencia_sugerida`,`status`,`data_inicio`,`data_reavaliacao`,`observacoes`
) VALUES
(1,1,3,5,'TCC','Reduzir sintomas ansiosos e melhorar organização emocional.','Mapear gatilhos, construir rotina de regulação emocional e reforçar autocuidado.','semanal','ativo','2026-05-10','2026-06-10','Paciente prefere encontros híbridos e acompanha exercícios entre sessões.');

INSERT INTO `psicologia_escalas` (
  `id`,`empresa_id`,`paciente_id`,`profissional_id`,`nome_escala`,`score_total`,`classificacao`,`data_aplicacao`,`observacoes`
) VALUES
(1,1,3,5,'GAD-7','14/21','Ansiedade moderada','2026-05-10','Aplicação inicial em contexto de queixa ansiosa e dificuldade de sono.'),
(2,1,6,5,'PHQ-9','9/27','Sintomas leves a moderados','2026-05-18','Paciente relatou estresse ocupacional e irritabilidade.');

-- =====================================================
-- FINANCEIRO
-- =====================================================
INSERT INTO `contas_financeiras` (
  `id`,`empresa_id`,`categoria_id`,`paciente_id`,`agendamento_id`,`nfse_id`,`tipo`,`descricao`,`valor`,`data_vencimento`,`data_pagamento`,`forma_pagamento`,`status`,`parcela_numero`,`parcela_total`,`parcela_grupo`,`observacoes`,`criado_por`
) VALUES
(1,1,1,1,1,NULL,'receita','Consulta clínica - Ana Paula Ribeiro',250.00,'2026-05-05','2026-05-05','pix','pago',1,1,'REC-ANA-001','Recebido no balcão.',4),
(2,1,4,2,2,NULL,'receita','Check-up laboratorial - Carlos Henrique Souza',140.00,'2026-05-06','2026-05-06','cartao_credito','pago',1,1,'REC-CARLOS-001','Pagamento em 1x.',4),
(3,1,3,3,3,NULL,'receita','Teleconsulta - Marina Lopes Ferreira',220.00,'2026-05-07','2026-05-07','pix','pago',1,1,'REC-MARINA-001','Pagamento antecipado via PIX.',4),
(4,1,2,5,5,NULL,'receita','Avaliação odontológica - Beatriz Prado',160.00,'2026-05-08','2026-05-08','cartao_debito','pago',1,1,'REC-BEATRIZ-001','Recebido na recepção.',4),
(5,1,2,5,6,NULL,'receita','Restauração em resina - parcela 1/2',240.00,'2026-05-16',NULL,'cartao_credito','pendente',1,2,'PARC-PLANO-0001','Plano de tratamento parcelado.',4),
(6,1,2,5,6,NULL,'receita','Restauração em resina - parcela 2/2',240.00,'2026-06-16',NULL,'cartao_credito','pendente',2,2,'PARC-PLANO-0001','Plano de tratamento parcelado.',4),
(7,1,5,NULL,NULL,NULL,'despesa','Aluguel da clínica - maio/2026',4500.00,'2026-05-10','2026-05-10','transferencia','pago',1,1,'DESP-ALUGUEL-202605','Contrato mensal da unidade Paulista.',1),
(8,1,6,NULL,NULL,NULL,'despesa','Folha salarial recepção - maio/2026',3200.00,'2026-05-05','2026-05-05','transferencia','pago',1,1,'DESP-FOLHA-202605','Pagamento equipe administrativa.',1),
(9,1,7,NULL,NULL,NULL,'despesa','Materiais odontológicos e EPI',890.75,'2026-05-09','2026-05-09','pix','pago',1,1,'DESP-MATERIAIS-202605','Compra em distribuidora clínica.',1),
(10,1,8,NULL,NULL,NULL,'despesa','Campanha Google Ads',650.00,'2026-05-11',NULL,'boleto','atrasado',1,1,'DESP-MKT-202605','Fatura em aberto.',1),
(11,1,1,4,4,NULL,'receita','Retorno médico - Roberto Almeida Santos',180.00,'2026-05-12','2026-05-12','dinheiro','pago',1,1,'REC-ROBERTO-001','Pagamento em espécie.',4),
(12,1,3,6,8,NULL,'receita','Teleconsulta - João Pedro Lima',220.00,'2026-05-18',NULL,'pix','pendente',1,1,'REC-JOAO-001','Link liberado mediante confirmação de pagamento.',4);

-- =====================================================
-- ANEXOS DE EXEMPLO
-- =====================================================
INSERT INTO `anexos` (
  `id`,`paciente_id`,`prontuario_id`,`tipo`,`titulo`,`descricao`,`arquivo`,`mime_type`,`tamanho`,`criado_por`
) VALUES
(1,4,5,'exame','Perfil lipídico - maio 2026','Exame importado para acompanhamento clínico.','exames/perfil_lipidico_roberto_202605.pdf','application/pdf',248320,2),
(2,5,4,'raio_x','Radiografia bite-wing - solicitação','Arquivo de imagem associado ao caso odontológico.','raiox/bitewing_beatriz_20260508.jpg','image/jpeg',532110,3);

-- =====================================================
-- BACKUP / IMPORTAÇÃO / LOGS DE EXEMPLO
-- =====================================================
INSERT INTO `backups` (`id`,`tipo`,`arquivo`,`tamanho_bytes`,`status`,`erro`,`criado_em`,`concluido_em`) VALUES
(1,'automatico','storage/backups/backup_2026_05_14_030000.sql.gz',245760,'concluido',NULL,'2026-05-14 03:00:00','2026-05-14 03:01:12');

INSERT INTO `importacoes` (`id`,`empresa_id`,`tipo`,`arquivo`,`total_linhas`,`sucesso`,`erros`,`log`,`status`,`criado_por`,`criado_em`) VALUES
(1,1,'pacientes','storage/imports/pacientes_maio_demo.csv',25,25,0,'Importação de demonstração executada com sucesso.','concluido',1,'2026-05-13 17:40:00');

INSERT INTO `logs` (`usuario_id`,`acao`,`entidade`,`entidade_id`,`descricao`,`ip`) VALUES
(1,'login','usuarios',1,'Acesso administrativo em ambiente demo.','127.0.0.1'),
(4,'create','agendamentos',8,'Agendamento de teleconsulta criado para João Pedro Lima.','127.0.0.1'),
(2,'create','prontuarios',3,'Prontuário gerado após teleconsulta de Marina Lopes Ferreira.','127.0.0.1'),
(3,'update','planos_tratamento',1,'Plano de tratamento odontológico aprovado pela paciente.','127.0.0.1');

SET FOREIGN_KEY_CHECKS = 1;
