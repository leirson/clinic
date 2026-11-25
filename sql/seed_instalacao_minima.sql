-- ClinicApp — carga mínima obrigatória: uma empresa, admin/desenvolvedor, licença e configurações
-- Uso exclusivo pelo instalador em banco novo.
SET NAMES utf8mb4;
SET @SEED_PREVIOUS_MODE=@@SESSION.SQL_MODE;
SET SESSION SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;
INSERT INTO `empresas` (`id`,`razao_social`,`nome_fantasia`,`cnpj`,`inscricao_municipal`,`inscricao_estadual`,`cnae`,`item_lista_servico`,`codigo_tributacao_municipio`,`regime_tributario`,`optante_simples`,`incentivador_cultural`,`aliquota_iss`,`endereco_cep`,`endereco_logradouro`,`endereco_numero`,`endereco_complemento`,`endereco_bairro`,`endereco_cidade`,`endereco_uf`,`endereco_codigo_municipio`,`telefone`,`email`,`dominio_principal`,`ativo`,`tema_padrao`,`logo`,`asten_ambiente`,`asten_token`,`asten_usuario`,`asten_senha`,`asten_url_ws`,`asten_serie_rps`,`asten_proximo_rps`,`asten_certificado_a1`,`asten_certificado_senha`,`criado_em`,`atualizado_em`) VALUES (1,'Clínica Demo LTDA','Clínica Demo','00.000.000/0001-00',NULL,NULL,NULL,NULL,NULL,'1',1,0,'0.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'medico',NULL,'homologacao',NULL,NULL,NULL,NULL,'1',1,NULL,NULL,'2026-11-11 23:52:12','2026-11-11 23:52:12');
INSERT INTO `usuarios` (`id`,`empresa_id`,`nome`,`email`,`senha`,`perfil`,`especialidade`,`registro_profissional`,`telefone`,`percentual_repasse`,`tema_preferido`,`mfa_metodo`,`cor_tema`,`avatar`,`ativo`,`ultimo_login`,`token_reset`,`criado_em`,`atualizado_em`,`mfa_secret`,`mfa_codigos_recuperacao`,`mfa_codigo_temp`,`mfa_codigo_expira`) VALUES (1,1,'Administrador','admin@clinicapp.com','$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa','admin',NULL,NULL,NULL,'0.00','medico','nenhum','blue',NULL,1,NULL,NULL,'2026-11-11 23:52:12','2026-11-11 23:52:12',NULL,NULL,NULL,NULL);
INSERT INTO `usuarios` (`id`,`empresa_id`,`nome`,`email`,`senha`,`perfil`,`especialidade`,`registro_profissional`,`telefone`,`percentual_repasse`,`tema_preferido`,`mfa_metodo`,`cor_tema`,`avatar`,`ativo`,`ultimo_login`,`token_reset`,`criado_em`,`atualizado_em`,`mfa_secret`,`mfa_codigos_recuperacao`,`mfa_codigo_temp`,`mfa_codigo_expira`) VALUES (2,1,'Desenvolvedor ClinicApp','dev@clinicapp.com','$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa','desenvolvedor','Licenciamento & Suporte',NULL,NULL,'0.00','medico','nenhum','purple',NULL,1,NULL,NULL,'2026-11-11 23:52:12','2026-11-11 23:52:12',NULL,NULL,NULL,NULL);
INSERT INTO `licencas` (`id`,`empresa_id`,`plano`,`status`,`chave_licenca`,`validade_inicio`,`validade_fim`,`max_usuarios`,`max_pacientes`,`observacoes`,`criado_por`,`atualizado_por`,`criado_em`,`atualizado_em`) VALUES (1,1,'premium','ativa','CLINICAPP-DEMO-001',CURDATE(),DATE_ADD(CURDATE(), INTERVAL 1 YEAR),100,10000,'Licença padrão inicial da empresa principal.',1,1,'2026-11-11 23:52:12','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'app_nome','ClinicApp','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'app_versao','1.2.0','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'dias_atendimento','1,2,3,4,5','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'github_branch','main','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'github_last_status','pendente','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'github_repo','leirson/Clinicapp','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'google_calendar_enabled','1','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'horario_fim','18:00','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'horario_inicio','08:00','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'intervalo_consulta','30','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'push_minutes_before','60','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'pwa_install_enabled','1','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'reminder_email_enabled','1','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'reminder_hours_before','24','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'reminder_push_enabled','1','2026-11-11 23:52:12');
INSERT INTO `configuracoes` (`empresa_id`,`chave`,`valor`,`atualizado_em`) VALUES (1,'reminder_window_minutes','30','2026-11-11 23:52:12');
COMMIT;
SET SESSION SQL_MODE=@SEED_PREVIOUS_MODE;
