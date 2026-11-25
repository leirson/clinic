<?php
$page_title = 'Novo Paciente';
require_once __DIR__ . '/../../includes/header.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    try {
        $fields = ['nome','tipo_pessoa','cpf_cnpj','rg','data_nascimento','sexo','email','telefone','celular',
                   'profissao','estado_civil','convenio','numero_carteirinha','cep','logradouro','numero',
                   'complemento','bairro','cidade','uf','codigo_municipio','tipo_sanguineo','alergias',
                   'medicamentos_uso','historico_familiar','observacoes'];
        $data = ['empresa_id' => active_company_id()];
        foreach ($fields as $f) $data[$f] = trim(input($f)) ?: null;
        $data['cpf_cnpj'] = only_numbers($data['cpf_cnpj']);
        $data['cep'] = only_numbers($data['cep']);
        $data['telefone'] = only_numbers($data['telefone']);
        $data['celular'] = only_numbers($data['celular']);
        $data['uf'] = strtoupper($data['uf'] ?? '');
        $data['data_nascimento'] = $data['data_nascimento'] ?: null;
        $cols = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $stmt = db()->prepare("INSERT INTO pacientes (".implode(',',$cols).") VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        $id = db()->lastInsertId();
        log_action('criar','pacientes',$id,'Paciente criado: '.$data['nome']);
        flash('success','Paciente cadastrado com sucesso!');
        redirect('ver.php?id='.$id);
    } catch (Exception $e) {
        flash('error','Erro: '.$e->getMessage());
    }
}
?>
<div class="mb-5">
  <a href="index.php" class="text-sm text-slate-500 hover:text-primary"><i class="bi bi-arrow-left"></i> Voltar</a>
  <h1 class="text-2xl font-bold text-slate-800">Novo Paciente</h1>
</div>
<?php $p = []; require __DIR__ . '/_form.php'; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
