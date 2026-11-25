<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Solicitação de Exames';

$id = (int)input('id');
$stmt = db()->prepare("SELECT s.*, p.nome AS pn, u.nome AS prof FROM solicitacoes_exame s JOIN pacientes p ON p.id=s.paciente_id JOIN usuarios u ON u.id=s.profissional_id WHERE s.id=? AND p.empresa_id=?");
$stmt->execute([$id, active_company_id()]);
$s = $stmt->fetch();
if (!$s) { flash('error','Não encontrado'); redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    if (!empty($_FILES['resultado']['name'])) {
        try {
            $file = upload_file('resultado', 'exames', ['pdf','jpg','jpeg','png']);
            if (!$file) throw new RuntimeException('Falha no upload.');
            db()->prepare("UPDATE solicitacoes_exame SET arquivo_resultado=?, status='recebido' WHERE id=? AND empresa_id=?")->execute([$file['arquivo'], $id, active_company_id()]);
            flash('success','Resultado anexado!');
        } catch (Throwable $e) { flash('error', $e->getMessage()); }

    } else {
        if (!in_array(input('status'), ['solicitado','realizado','recebido','cancelado'], true)) { http_response_code(422); exit('Status inválido.'); }
        db()->prepare("UPDATE solicitacoes_exame SET status=? WHERE id=? AND empresa_id=?")->execute([input('status'), $id, active_company_id()]);
        flash('success','Atualizado.');
    }
    redirect('ver.php?id='.$id);
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5 flex justify-between flex-wrap gap-2">
  <div>
    <a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a>
    <h1 class="text-2xl font-bold">Solicitação #<?= $id ?></h1>
    <p class="text-sm text-slate-500"><?= e($s['pn']) ?> · <?= format_date($s['data_solicitacao']) ?></p>
  </div>
  <a href="pdf.php?id=<?= $id ?>" target="_blank" class="btn bg-purple-600 text-white"><i class="bi bi-printer"></i> Imprimir</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
<div class="card p-5">
  <h3 class="font-bold mb-3">Exames Solicitados</h3>
  <pre class="text-sm whitespace-pre-wrap font-sans"><?= e($s['exames']) ?></pre>
  <?php if($s['indicacao_clinica']): ?>
    <h3 class="font-bold mt-4 mb-2">Indicação Clínica</h3>
    <p class="text-sm"><?= nl2br(e($s['indicacao_clinica'])) ?></p>
  <?php endif; ?>
  <?php if($s['cid_codigo']): ?><p class="text-sm mt-3"><strong>CID:</strong> <?= e($s['cid_codigo']) ?></p><?php endif; ?>
</div>

<div class="card p-5 space-y-4">
  <h3 class="font-bold">Resultado / Status</h3>
  <form method="POST">
    <?= csrf_field() ?>
    <label class="text-sm">Status</label>
    <select name="status" class="form-select mb-2">
      <?php foreach(['solicitado','realizado','recebido','cancelado'] as $st): ?>
        <option <?= $s['status']===$st?'selected':'' ?>><?= $st ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary w-full">Atualizar</button>
  </form>

  <form method="POST" enctype="multipart/form-data" class="border-t pt-3">
    <?= csrf_field() ?>
    <label class="text-sm">Anexar resultado (PDF/imagem)</label>
    <input type="file" name="resultado" accept=".pdf,.jpg,.jpeg,.png" required class="form-input mb-2">
    <button class="btn bg-green-600 text-white w-full"><i class="bi bi-upload"></i> Enviar</button>
  </form>

  <?php if($s['arquivo_resultado']): ?>
    <a href="<?= APP_URL ?>/modules/arquivos/download.php?tipo=exame&amp;id=<?= (int) $s['id'] ?>" target="_blank" class="btn bg-purple-600 text-white block text-center"><i class="bi bi-file-earmark-medical"></i> Ver Resultado</a>
  <?php endif; ?>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
