-- Dados odontológicos FICTÍCIOS para os três pacientes demo de Odontologia.
-- Executar APÓS seed_pacientes_todas_especialidades.sql; requer upgrade_v2.sql.
-- Não substitui registros existentes. Sem profissional fictício ou histórico inventado.
SET NAMES utf8mb4;
SET @seed_empresa_id := (SELECT id FROM empresas WHERE nome_fantasia='Clínica Demo MultiEspecialidades' ORDER BY id LIMIT 1);
START TRANSACTION;
INSERT INTO odontograma (empresa_id,paciente_id,dente,face,tipo_denticao,condicao,observacao,tratamento_planejado,valor_estimado,data_registro,profissional_id)
SELECT p.empresa_id,p.id,s.dente,s.face,s.tipo,s.condicao,'[CLINICAPP-SEED-ODONTO-v1] Marcação fictícia para teste visual.',s.planejado,NULL,CURDATE(),NULL
FROM pacientes p JOIN (
SELECT 'dentista.01@example.invalid' AS email,'16' AS dente,'oclusal' AS face,'permanente' AS tipo,'carie' AS condicao,1 AS planejado
UNION ALL SELECT 'dentista.01@example.invalid','26','geral','permanente','restauracao',0
UNION ALL SELECT 'dentista.02@example.invalid','55','oclusal','deciduo','carie',1
UNION ALL SELECT 'dentista.02@example.invalid','65','geral','deciduo','restauracao',0
UNION ALL SELECT 'dentista.03@example.invalid','16','oclusal','misto','carie',1
UNION ALL SELECT 'dentista.03@example.invalid','55','geral','misto','restauracao',0
) s ON p.email=s.email
WHERE p.empresa_id=@seed_empresa_id AND p.observacoes LIKE '[CLINICAPP-SEED-PAC-v1:%'
AND NOT EXISTS (SELECT 1 FROM odontograma o WHERE o.paciente_id=p.id AND o.tipo_denticao=s.tipo AND o.dente=s.dente);
COMMIT;
