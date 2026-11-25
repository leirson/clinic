<?php
$page_title = 'Atestado Médico';
require_once __DIR__ . '/../../includes/header.php';

$paciente_id = (int)input('paciente_id');
$prontuario_id = (int)input('prontuario_id') ?: null;
try { clinic_validate_clinical_links($paciente_id, $prontuario_id); }
catch (InvalidArgumentException $e) { http_response_code(404); exit('Paciente/prontuário não encontrado na empresa ativa.'); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    $stmt = db()->prepare("INSERT INTO receitas (empresa_id, paciente_id, profissional_id, prontuario_id, tipo, conteudo, data_emissao, dias_afastamento, cid) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([active_company_id(), $paciente_id, $_SESSION['user_id'], $prontuario_id, 'atestado', input('conteudo'), input('data_emissao', date('Y-m-d')), (int)input('dias_afastamento'), input('cid')]);
    $id = db()->lastInsertId();
    flash('success','Atestado emitido!');
    redirect('receita_pdf.php?id='.$id);
}

$p = db()->prepare("SELECT * FROM pacientes WHERE id=? AND empresa_id=?");
$p->execute([$paciente_id, active_company_id()]); $p = $p->fetch();
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold"><i class="bi bi-file-medical"></i> Atestado Médico</h1><p class="text-sm text-slate-500">Paciente: <?= e($p['nome'] ?? '') ?></p></div>

<form method="POST" class="card p-6 space-y-4">
  <?= csrf_field() ?>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div><label class="text-sm">Data</label><input type="date" name="data_emissao" value="<?= date('Y-m-d') ?>" class="form-input"></div>
    <div><label class="text-sm">Dias de Afastamento</label><input type="number" name="dias_afastamento" value="1" min="1" class="form-input"></div>
    <div><label class="text-sm">CID</label><input name="cid" class="form-input"></div>
  </div>
  <div>
    <label class="text-sm">Texto do Atestado</label>
    <textarea name="conteudo" rows="8" class="form-textarea" required>Atesto, para os devidos fins, que o(a) paciente <?= e($p['nome'] ?? '') ?> compareceu a esta clínica em <?= date('d/m/Y') ?> e necessita de afastamento de suas atividades por __ (___) dia(s), a partir desta data, por motivo de saúde.</textarea>
  </div>
  <div class="flex justify-end gap-2"><a href="javascript:history.back()" class="btn bg-slate-200">Cancelar</a><button class="btn btn-primary"><i class="bi bi-printer"></i> Emitir</button></div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
