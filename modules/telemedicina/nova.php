<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Nova Teleconsulta';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    try {
        $ag_id = (int)input('agendamento_id');
        clinic_company_record('agendamentos', $ag_id);
        $sala = 'ClinicApp-' . bin2hex(random_bytes(6)) . '-' . $ag_id;

        // Atualiza agendamento para telemedicina
        db()->prepare("UPDATE agendamentos SET modalidade='telemedicina' WHERE id=? AND empresa_id=?")->execute([$ag_id, active_company_id()]);

        $link_p = APP_URL . '/modules/telemedicina/sala.php?sala=' . $sala . '&role=paciente';
        $link_pr = APP_URL . '/modules/telemedicina/sala.php?sala=' . $sala . '&role=profissional';

        db()->prepare("INSERT INTO teleconsultas (empresa_id, agendamento_id, sala_id, link_paciente, link_profissional) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE sala_id=VALUES(sala_id), link_paciente=VALUES(link_paciente), link_profissional=VALUES(link_profissional)")
            ->execute([active_company_id(), $ag_id, $sala, $link_p, $link_pr]);
        flash('success','Teleconsulta criada!');
        redirect('index.php');
    } catch (Exception $e) {
        flash('error','Erro: '.$e->getMessage());
    }
}

// Buscar agendamentos futuros sem teleconsulta
$ags = db()->prepare("SELECT a.id, a.data_hora_inicio, p.nome AS pn FROM agendamentos a JOIN pacientes p ON p.id=a.paciente_id LEFT JOIN teleconsultas t ON t.agendamento_id=a.id WHERE a.empresa_id=? AND a.data_hora_inicio >= NOW() AND t.id IS NULL ORDER BY a.data_hora_inicio");
$ags->execute([active_company_id()]);
$ags = $ags->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold">Nova Teleconsulta</h1></div>
<form method="POST" class="card p-6 space-y-4">
  <?= csrf_field() ?>
  <div>
    <label class="text-sm">Agendamento *</label>
    <?php if(!$ags): ?>
      <div class="bg-amber-50 border border-amber-200 p-3 rounded text-sm">Não há agendamentos futuros disponíveis. <a href="../agenda/novo.php" class="text-primary">Criar novo agendamento</a></div>
    <?php else: ?>
    <select data-patient-search name="agendamento_id" required class="form-select">
      <option value="">Selecione...</option>
      <?php foreach($ags as $a): ?>
        <option value="<?= $a['id'] ?>"><?= format_date($a['data_hora_inicio'], true) ?> — <?= e($a['pn']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
  </div>
  <div class="text-xs text-slate-500">
    Será gerada uma sala única no Jitsi Meet (jitsi.org) com links separados para paciente e profissional.
  </div>
  <div class="flex justify-end gap-2"><a href="index.php" class="btn bg-slate-200">Cancelar</a><button class="btn btn-primary" <?= !$ags?'disabled':'' ?>>Criar Teleconsulta</button></div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
