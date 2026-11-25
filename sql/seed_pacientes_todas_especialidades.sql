-- Pacientes FICTÍCIOS: 3 por especialidade, 30 por empresa selecionada.
-- NÃO cria prontuários, usuários, agendamentos ou dados clínicos reais.
-- A tabela pacientes não tem coluna especialidade: identificação no nome/observações.
-- Por segurança, o padrão é SOMENTE Clínica Demo MultiEspecialidades.
-- Para outra empresa, substitua a atribuição de @seed_empresa_id por um ID existente.
-- Sem DELETE/UPDATE, sem desativar Safe Updates/FKs; não rodar concorrentemente.
SET NAMES utf8mb4;
SET @seed_empresa_id := (SELECT id FROM empresas WHERE nome_fantasia='Clínica Demo MultiEspecialidades' ORDER BY id LIMIT 1);
-- Se a empresa não existir, nenhuma linha é inserida. Confira o diagnóstico abaixo.
SELECT @seed_empresa_id AS empresa_destino;
START TRANSACTION;
INSERT INTO pacientes (empresa_id,nome,email,data_nascimento,sexo,observacoes,ativo)
SELECT e.id,s.nome,s.email,s.data_nascimento,s.sexo,s.observacoes,1
FROM empresas e JOIN (
SELECT 'DEMO Clínica Médica 01' AS nome,'medico.01@example.invalid' AS email,'1988-05-10' AS data_nascimento,'F' AS sexo,'[CLINICAPP-SEED-PAC-v1:medico:01] Paciente inteiramente fictício para testes de Clínica Médica. Não contactar. Não usar para faturamento.' AS observacoes
UNION ALL
SELECT 'DEMO Clínica Médica 02','medico.02@example.invalid','1988-05-10','M','[CLINICAPP-SEED-PAC-v1:medico:02] Paciente inteiramente fictício para testes de Clínica Médica. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Clínica Médica 03','medico.03@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:medico:03] Paciente inteiramente fictício para testes de Clínica Médica. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Odontologia 01','dentista.01@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:dentista:01] Paciente inteiramente fictício para testes de Odontologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Odontologia 02','dentista.02@example.invalid','2022-05-10','M','[CLINICAPP-SEED-PAC-v1:dentista:02] Paciente inteiramente fictício para testes de Odontologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Odontologia 03','dentista.03@example.invalid','2018-05-10','F','[CLINICAPP-SEED-PAC-v1:dentista:03] Paciente inteiramente fictício para testes de Odontologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Psicologia 01','psicologia.01@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:psicologia:01] Paciente inteiramente fictício para testes de Psicologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Psicologia 02','psicologia.02@example.invalid','1988-05-10','M','[CLINICAPP-SEED-PAC-v1:psicologia:02] Paciente inteiramente fictício para testes de Psicologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Psicologia 03','psicologia.03@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:psicologia:03] Paciente inteiramente fictício para testes de Psicologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Psiquiatria 01','psiquiatria.01@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:psiquiatria:01] Paciente inteiramente fictício para testes de Psiquiatria. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Psiquiatria 02','psiquiatria.02@example.invalid','1988-05-10','M','[CLINICAPP-SEED-PAC-v1:psiquiatria:02] Paciente inteiramente fictício para testes de Psiquiatria. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Psiquiatria 03','psiquiatria.03@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:psiquiatria:03] Paciente inteiramente fictício para testes de Psiquiatria. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Pediatria 01','pediatria.01@example.invalid','2019-05-10','F','[CLINICAPP-SEED-PAC-v1:pediatria:01] Paciente inteiramente fictício para testes de Pediatria. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Pediatria 02','pediatria.02@example.invalid','2019-05-10','M','[CLINICAPP-SEED-PAC-v1:pediatria:02] Paciente inteiramente fictício para testes de Pediatria. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Pediatria 03','pediatria.03@example.invalid','2019-05-10','F','[CLINICAPP-SEED-PAC-v1:pediatria:03] Paciente inteiramente fictício para testes de Pediatria. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Ginecologia e Obstetrícia 01','ginecologia_obstetricia.01@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:ginecologia_obstetricia:01] Paciente inteiramente fictício para testes de Ginecologia e Obstetrícia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Ginecologia e Obstetrícia 02','ginecologia_obstetricia.02@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:ginecologia_obstetricia:02] Paciente inteiramente fictício para testes de Ginecologia e Obstetrícia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Ginecologia e Obstetrícia 03','ginecologia_obstetricia.03@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:ginecologia_obstetricia:03] Paciente inteiramente fictício para testes de Ginecologia e Obstetrícia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Ortopedia e Traumatologia 01','ortopedia_traumatologia.01@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:ortopedia_traumatologia:01] Paciente inteiramente fictício para testes de Ortopedia e Traumatologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Ortopedia e Traumatologia 02','ortopedia_traumatologia.02@example.invalid','1988-05-10','M','[CLINICAPP-SEED-PAC-v1:ortopedia_traumatologia:02] Paciente inteiramente fictício para testes de Ortopedia e Traumatologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Ortopedia e Traumatologia 03','ortopedia_traumatologia.03@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:ortopedia_traumatologia:03] Paciente inteiramente fictício para testes de Ortopedia e Traumatologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Cardiologia 01','cardiologia.01@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:cardiologia:01] Paciente inteiramente fictício para testes de Cardiologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Cardiologia 02','cardiologia.02@example.invalid','1988-05-10','M','[CLINICAPP-SEED-PAC-v1:cardiologia:02] Paciente inteiramente fictício para testes de Cardiologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Cardiologia 03','cardiologia.03@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:cardiologia:03] Paciente inteiramente fictício para testes de Cardiologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Oftalmologia 01','oftalmologia.01@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:oftalmologia:01] Paciente inteiramente fictício para testes de Oftalmologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Oftalmologia 02','oftalmologia.02@example.invalid','1988-05-10','M','[CLINICAPP-SEED-PAC-v1:oftalmologia:02] Paciente inteiramente fictício para testes de Oftalmologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Oftalmologia 03','oftalmologia.03@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:oftalmologia:03] Paciente inteiramente fictício para testes de Oftalmologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Neurologia 01','neurologia.01@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:neurologia:01] Paciente inteiramente fictício para testes de Neurologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Neurologia 02','neurologia.02@example.invalid','1988-05-10','M','[CLINICAPP-SEED-PAC-v1:neurologia:02] Paciente inteiramente fictício para testes de Neurologia. Não contactar. Não usar para faturamento.'
UNION ALL
SELECT 'DEMO Neurologia 03','neurologia.03@example.invalid','1988-05-10','F','[CLINICAPP-SEED-PAC-v1:neurologia:03] Paciente inteiramente fictício para testes de Neurologia. Não contactar. Não usar para faturamento.'
) s ON 1=1
WHERE e.id=@seed_empresa_id AND NOT EXISTS (SELECT 1 FROM pacientes p WHERE p.empresa_id=e.id AND p.email=s.email);
COMMIT;
