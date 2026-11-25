SET NAMES utf8mb4;
-- Upgrade v14: corrigir empresa herdada do paciente em registros clínicos legados.
-- Faça backup antes de importar. Requer schema atualizado até v13.
-- Sem excluir dados. O paciente é a autoridade do vínculo multiempresa.
SET @CLINICAPP_V14_SAFE_UPDATES := @@SESSION.SQL_SAFE_UPDATES;
SET SESSION SQL_SAFE_UPDATES = 0;
START TRANSACTION;
UPDATE `prontuarios` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `receitas` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `anexos` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `odontograma` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `odontograma_historico` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `solicitacoes_exame` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE teleconsultas t JOIN agendamentos a ON a.id=t.agendamento_id SET t.empresa_id=a.empresa_id WHERE t.id>0 AND t.empresa_id<>a.empresa_id;
UPDATE plano_tratamento_itens i JOIN planos_tratamento p ON p.id=i.plano_id SET i.empresa_id=p.empresa_id WHERE i.id>0 AND i.empresa_id<>p.empresa_id;
UPDATE `psicologia_prontuario` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `psicologia_planos` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `psiquiatria_prontuario` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `pediatria_prontuario` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `ginecologia_obstetricia_prontuario` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `ortopedia_traumatologia_prontuario` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `cardiologia_prontuario` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `oftalmologia_prontuario` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
UPDATE `neurologia_prontuario` c JOIN pacientes p ON p.id=c.paciente_id SET c.empresa_id=p.empresa_id WHERE c.id>0 AND c.empresa_id<>p.empresa_id;
COMMIT;
SET SESSION SQL_SAFE_UPDATES = @CLINICAPP_V14_SAFE_UPDATES;
