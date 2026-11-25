<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$tipo = input('tipo', 'pacientes');

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="template_' . $tipo . '.csv"');
echo "\xEF\xBB\xBF";

if ($tipo === 'pacientes') {
    echo "nome,cpf_cnpj,email,telefone,celular,data_nascimento,sexo,cep,logradouro,numero,bairro,cidade,uf\n";
    echo "João da Silva,12345678901,joao@email.com,1133334444,11999998888,01/01/1980,M,01310100,Av Paulista,1000,Bela Vista,São Paulo,SP\n";
    echo "Maria Souza,98765432100,maria@email.com,,11988887777,15/05/1975,F,04567000,Av Brigadeiro,2500,Itaim,São Paulo,SP\n";
} elseif ($tipo === 'procedimentos') {
    echo "nome,valor,duracao,categoria\n";
    echo "Consulta Clínica,250.00,30,clinico\n";
    echo "Limpeza Dental,180.00,45,odontologico\n";
    echo "Restauração,280.00,60,odontologico\n";
} elseif ($tipo === 'pacientes_iclinic') {
    echo "patient_code,name,birth_date,cpf,mobile_phone,home_phone,email,zip_code,address,number,neighborhood,city,state,gender,occupation,observation\n";
    echo "1001,Paciente Teste,1970-03-21,12345678901,11999998888,1133334444,paciente@teste.com,01310100,Av Paulista,1000,Bela Vista,São Paulo,SP,F,Analista,Paciente importado do Afya iClinic\n";
    echo "1002,Carlos Mendes,1985-10-11,98765432100,11977776666,,carlos@teste.com,30140071,Rua da Bahia,1500,Centro,Belo Horizonte,MG,M,Médico,Observação livre do cadastro\n";
} else {
    echo "patient_birth_date,patient_name,physician_id,date,start_time,end_time,tab_name,eventblock_name,eventblock_text\n";
    echo "1970-03-21,Paciente Teste,1,2026-05-10,09:00:00,09:20:00,Atendimento,Queixa Principal,Dores lombares há 7 dias\n";
    echo "1970-03-21,Paciente Teste,1,2026-05-10,09:00:00,09:20:00,Atendimento,Anamnese,Paciente refere piora ao sentar e melhora parcial com repouso\n";
    echo "1970-03-21,Paciente Teste,1,2026-05-10,09:00:00,09:20:00,Atendimento,Conduta,Orientado repouso relativo e solicitado exame complementar\n";
}
exit;
