<?php
$page_title = 'Editar Paciente';
require_once __DIR__ . '/../../includes/header.php';

$id = (int)input('id');
$stmt = db()->prepare("SELECT * FROM pacientes WHERE id=? AND empresa_id=?");
$stmt->execute([$id, active_company_id()]);
$p = $stmt->fetch();
if (!$p) { flash('error','Paciente não encontrado.'); redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    try {
        $fields = ['nome','tipo_pessoa','cpf_cnpj','rg','data_nascimento','sexo','email','telefone','celular',
                   'profissao','estado_civil','convenio','numero_carteirinha','cep','logradouro','numero',
                   'complemento','bairro','cidade','uf','codigo_municipio','tipo_sanguineo','alergias',
                   'medicamentos_uso','historico_familiar','observacoes'];
        $set = []; $vals = [];
        foreach ($fields as $f) {
            $set[] = "$f = ?";
            $v = trim(input($f));
            if (in_array($f,['cpf_cnpj','cep','telefone','celular'])) $v = only_numbers($v);
            if ($f === 'uf') $v = strtoupper($v);
            if ($f === 'data_nascimento' && !$v) $v = null;
            $vals[] = $v ?: null;
        }
        $vals[] = $id;
        $vals[] = active_company_id();
        $stmt = db()->prepare("UPDATE pacientes SET ".implode(',',$set)." WHERE id = ? AND empresa_id = ?");
        $stmt->execute($vals);
        log_action('editar','pacientes',$id);
        flash('success','Paciente atualizado!');
        redirect('ver.php?id='.$id);
    } catch (Exception $e) {
        flash('error','Erro: '.$e->getMessage());
    }
}
?>
<div class="mb-5">
  <a href="ver.php?id=<?= $id ?>" class="text-sm text-slate-500 hover:text-primary"><i class="bi bi-arrow-left"></i> Voltar</a>
  <h1 class="text-2xl font-bold text-slate-800">Editar Paciente</h1>
</div>
<?php require __DIR__ . '/_form.php'; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
