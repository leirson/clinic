<?php
require_once __DIR__ . '/../../includes/functions.php';

$id = (int) input('id');
$sala = preg_replace('/[^a-zA-Z0-9\-_]/', '', input('sala'));
$role = input('role', is_logged() ? 'profissional' : 'paciente');
if (!in_array($role, ['paciente', 'profissional'], true)) {
    $role = 'paciente';
}

if ($role === 'profissional' || (!$sala && $id > 0)) require_login();
$t = null;

if (!$sala && $id > 0) {
    $sql = "SELECT t.*, a.data_hora_inicio, p.nome AS pn, u.nome AS prof
            FROM teleconsultas t
            JOIN agendamentos a ON a.id = t.agendamento_id
            JOIN pacientes p ON p.id = a.paciente_id
            JOIN usuarios u ON u.id = a.profissional_id
            WHERE t.id = ?";
    $params = [$id];

    if ($role === 'profissional') {
        $sql .= " AND a.empresa_id = ?";
        $params[] = active_company_id();
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $t = $stmt->fetch();
    if ($t) {
        $sala = $t['sala_id'];
    }
}

if (!$sala) die('Sala inválida');

if (!$t) {
    $sql = "SELECT t.*, a.data_hora_inicio, p.nome AS pn, u.nome AS prof
            FROM teleconsultas t
            JOIN agendamentos a ON a.id = t.agendamento_id
            JOIN pacientes p ON p.id = a.paciente_id
            JOIN usuarios u ON u.id = a.profissional_id
            WHERE t.sala_id = ?";
    $params = [$sala];

    if ($role === 'profissional') {
        $sql .= " AND a.empresa_id = ?";
        $params[] = active_company_id();
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $t = $stmt->fetch();
}

if (!$t) die('Teleconsulta não encontrada');

// Marca início se primeira vez
if (!$t['iniciada_em'] && $role === 'profissional') {
    db()->prepare("UPDATE teleconsultas SET iniciada_em=NOW() WHERE id=?")->execute([$t['id']]);
}

$nome_exibicao = $role === 'profissional' ? ('Dr(a). ' . $t['prof']) : $t['pn'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Teleconsulta - <?= APP_NAME ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{margin:0;font-family:Arial,sans-serif;background:#1e293b;color:white;}
.header{background:#0f172a;padding:12px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #334155;}
.brand{font-size:18px;font-weight:bold;color:#0ea5e9;}
.info{font-size:13px;color:#94a3b8;}
#meet{height:calc(100vh - 60px);}
.consent{position:fixed;inset:0;background:rgba(0,0,0,0.85);display:flex;align-items:center;justify-content:center;padding:20px;z-index:10;}
.consent-box{background:white;color:#1e293b;padding:30px;border-radius:12px;max-width:500px;}
.btn-aceitar{background:#10b981;color:white;border:0;padding:10px 20px;border-radius:6px;cursor:pointer;font-weight:bold;}
.btn-rejeitar{background:#64748b;color:white;border:0;padding:10px 20px;border-radius:6px;cursor:pointer;margin-right:10px;}
</style>
</head>
<body>
<div class="header">
  <div>
    <div class="brand">🩺 <?= APP_NAME ?> · Teleconsulta</div>
    <div class="info">Sala: <code><?= e($sala) ?></code> · <?= e($nome_exibicao) ?></div>
  </div>
  <?php if ($role === 'profissional'): ?>
  <button onclick="encerrar()" style="background:#dc2626;color:white;border:0;padding:8px 16px;border-radius:6px;cursor:pointer;">Encerrar</button>
  <?php endif; ?>
</div>

<?php if (!$t['consentimento_paciente'] && $role === 'paciente'): ?>
<div class="consent" id="consent">
  <div class="consent-box">
    <h2>📋 Termo de Consentimento</h2>
    <p style="font-size:14px;">Você está prestes a iniciar uma <strong>consulta de telemedicina</strong>. Confirme que:</p>
    <ul style="font-size:13px;">
      <li>Concorda em realizar consulta por videochamada</li>
      <li>Está em local privado e tranquilo</li>
      <li>Compreende que o profissional poderá fazer registros no prontuário eletrônico</li>
      <li>Foi informado(a) que dados são protegidos pela LGPD</li>
    </ul>
    <div style="margin-top:20px;text-align:right;">
      <button class="btn-rejeitar" onclick="window.close()">Recusar</button>
      <button class="btn-aceitar" onclick="aceitarConsentimento()">Aceito e Continuar</button>
    </div>
  </div>
</div>
<?php endif; ?>

<div id="meet"></div>

<script src="https://meet.jit.si/external_api.js"></script>
<script>
const sala = <?= json_encode($sala) ?>;
const nome = <?= json_encode($nome_exibicao, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const csrf = <?= json_encode(csrf_token()) ?>;
const room = <?= json_encode($sala) ?>;

function iniciarMeet() {
  const opts = {
    roomName: sala,
    parentNode: document.getElementById('meet'),
    width: '100%',
    height: '100%',
    userInfo: { displayName: nome },
    configOverwrite: {
      startWithAudioMuted: false,
      startWithVideoMuted: false,
      disableModeratorIndicator: false,
      enableWelcomePage: false,
      prejoinPageEnabled: false
    },
    interfaceConfigOverwrite: {
      MOBILE_APP_PROMO: false,
      SHOW_JITSI_WATERMARK: false,
      DEFAULT_BACKGROUND: '#0f172a',
      TOOLBAR_BUTTONS: ['microphone','camera','desktop','fullscreen','fodeviceselection','hangup','chat','settings','raisehand','videoquality','tileview']
    }
  };
  const api = new JitsiMeetExternalAPI('meet.jit.si', opts);
  api.addEventListener('readyToClose', () => encerrar());
}

function aceitarConsentimento() {
  fetch('consentimento.php', {method:'POST', body:new URLSearchParams({id:<?= (int)$t['id'] ?>, sala:room, _token:csrf})}).then(r => { if (!r.ok) throw new Error('Consentimento não registrado'); return r.json(); }).then(() => {
    document.getElementById('consent').style.display = 'none';
    iniciarMeet();
  });
}

function encerrar() {
  <?php if ($role !== 'profissional'): ?>
  document.getElementById('meet').innerHTML = 'Você saiu da chamada. Pode fechar esta aba.';
  return;
  <?php endif; ?>
  if (confirm('Encerrar teleconsulta?')) {
    fetch('encerrar.php', {method:'POST', body:new URLSearchParams({id:<?= (int)$t['id'] ?>, _token:csrf})}).then(r => { if (!r.ok) throw new Error('Falha ao encerrar'); return r.json(); }).then(() => {
      <?php if ($role === 'profissional'): ?>
        window.location = '<?= APP_URL ?>/modules/telemedicina/index.php';
      <?php else: ?>
        window.close();
        document.body.innerHTML = '<div style="text-align:center;padding:60px;"><h2>Consulta encerrada</h2><p>Pode fechar esta aba.</p></div>';
      <?php endif; ?>
    });
  }
}

<?php if ($t['consentimento_paciente'] || $role === 'profissional'): ?>
  iniciarMeet();
<?php endif; ?>
</script>
</body>
</html>
