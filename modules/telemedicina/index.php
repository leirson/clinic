<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
$page_title = 'Telemedicina';

$stmt = db()->prepare("SELECT t.*, a.data_hora_inicio, a.status AS ag_status, p.nome AS paciente_nome, p.celular, p.email, u.nome AS prof_nome
  FROM teleconsultas t
  JOIN agendamentos a ON a.id = t.agendamento_id
  JOIN pacientes p ON p.id = a.paciente_id
  JOIN usuarios u ON u.id = a.profissional_id
  WHERE a.empresa_id = ? ORDER BY a.data_hora_inicio DESC LIMIT 100");
$stmt->execute([active_company_id()]);
$consultas = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex justify-between flex-wrap gap-3 mb-5">
  <div>
    <h1 class="text-2xl font-bold"><i class="bi bi-camera-video text-primary"></i> Telemedicina</h1>
    <p class="text-sm text-slate-500">Consultas online via Jitsi Meet</p>
  </div>
  <a href="nova.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nova Teleconsulta</a>
</div>

<div class="card p-4 mb-5 bg-blue-50 border border-blue-200">
  <h3 class="font-bold text-blue-900"><i class="bi bi-info-circle"></i> Como funciona</h3>
  <ul class="text-sm text-blue-800 mt-2 space-y-1">
    <li>1️⃣ Crie uma teleconsulta vinculada a um agendamento</li>
    <li>2️⃣ Sistema gera link único Jitsi (sem necessidade de cadastro)</li>
    <li>3️⃣ Envie o link ao paciente por WhatsApp/e-mail</li>
    <li>4️⃣ No horário, ambos acessam pelo navegador (PC ou celular)</li>
    <li>5️⃣ Após a consulta, registre prontuário e emita receita</li>
  </ul>
</div>

<div class="card overflow-hidden">
<table class="table-modern">
<thead><tr><th>Data/Hora</th><th>Paciente</th><th>Profissional</th><th>Sala</th><th>Status</th><th>Ações</th></tr></thead>
<tbody>
<?php if (!$consultas): ?><tr><td colspan="6" class="text-center py-10 text-slate-400">Nenhuma teleconsulta agendada.</td></tr>
<?php else: foreach($consultas as $t):
    $em_andamento = $t['iniciada_em'] && !$t['encerrada_em'];
    $tempo_para = strtotime($t['data_hora_inicio']) - time();
?>
<tr>
  <td><?= format_date($t['data_hora_inicio'], true) ?>
    <?php if ($tempo_para > 0 && $tempo_para < 3600): ?>
      <div class="text-xs text-orange-600 font-semibold">Em <?= round($tempo_para/60) ?> min</div>
    <?php endif; ?>
  </td>
  <td><?= e($t['paciente_nome']) ?></td>
  <td>Dr(a). <?= e($t['prof_nome']) ?></td>
  <td class="font-mono text-xs"><?= e($t['sala_id']) ?></td>
  <td>
    <?php if ($em_andamento): ?>
      <span class="badge badge-success">🔴 Ao vivo</span>
    <?php elseif ($t['encerrada_em']): ?>
      <span class="badge badge-secondary">Encerrada</span>
    <?php else: ?>
      <span class="badge badge-info">Agendada</span>
    <?php endif; ?>
  </td>
  <td class="text-right whitespace-nowrap">
    <a href="<?= e($t['link_profissional'] ?: ('sala.php?sala=' . urlencode($t['sala_id']) . '&role=profissional')) ?>" target="_blank" class="text-green-600 mx-1" title="Entrar"><i class="bi bi-camera-video-fill"></i></a>
    <a href="enviar_link.php?id=<?= $t['id'] ?>" class="text-blue-600 mx-1" title="Enviar link"><i class="bi bi-send"></i></a>
    <?php if ($t['celular']): ?>
    <a href="<?= wa_link($t['celular'], 'Link da sua teleconsulta: ' . ($t['link_paciente'] ?: (APP_URL . '/modules/telemedicina/sala.php?sala=' . $t['sala_id'] . '&role=paciente'))) ?>" target="_blank" class="text-green-700 mx-1"><i class="bi bi-whatsapp"></i></a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
