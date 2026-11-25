<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) input('id');
$eid = active_company_id();
$stmt = db()->prepare('SELECT * FROM pacientes WHERE id=? AND empresa_id=? LIMIT 1');
$stmt->execute([$id, $eid]);
$paciente = $stmt->fetch();
if (!$paciente) {
    flash('error', 'Paciente não encontrado.');
    redirect('index.php');
}

$payload = [
    'exportado_em' => date('c'),
    'empresa_id' => $eid,
    'paciente' => $paciente,
    'agendamentos' => [],
    'prontuarios' => [],
    'receitas' => [],
    'nfse' => [],
    'consentimentos' => [],
    'logs' => [],
    'timeline' => patient_longitudinal_timeline($id, 120),
];

$stmt = db()->prepare('SELECT * FROM agendamentos WHERE empresa_id=? AND paciente_id=? ORDER BY data_hora_inicio DESC');
$stmt->execute([$eid, $id]);
$payload['agendamentos'] = $stmt->fetchAll() ?: [];

$stmt = db()->prepare('SELECT * FROM prontuarios WHERE paciente_id=? ORDER BY data_atendimento DESC');
$stmt->execute([$id]);
$payload['prontuarios'] = $stmt->fetchAll() ?: [];

if (db_table_exists('receitas')) {
    $stmt = db()->prepare('SELECT * FROM receitas WHERE paciente_id=? ORDER BY data_emissao DESC');
    $stmt->execute([$id]);
    $payload['receitas'] = $stmt->fetchAll() ?: [];
}

if (db_table_exists('nfse')) {
    $stmt = db()->prepare('SELECT * FROM nfse WHERE empresa_id=? AND paciente_id=? ORDER BY data_emissao DESC');
    $stmt->execute([$eid, $id]);
    $payload['nfse'] = $stmt->fetchAll() ?: [];
}

if (db_table_exists('paciente_consentimentos')) {
    $payload['consentimentos'] = active_patient_consents($id, false);
}

$logSql = "SELECT * FROM logs WHERE (entidade='pacientes' AND entidade_id=?) OR (entidade='prontuarios' AND entidade_id IN (SELECT id FROM prontuarios WHERE paciente_id=?))";
$logParams = [$id, $id];
if (db_table_exists('paciente_consentimentos')) {
    $logSql .= " OR (entidade='paciente_consentimentos' AND entidade_id IN (SELECT id FROM paciente_consentimentos WHERE empresa_id=? AND paciente_id=?))";
    $logParams[] = $eid;
    $logParams[] = $id;
}
$logSql .= " ORDER BY criado_em DESC LIMIT 500";
$stmt = db()->prepare($logSql);
$stmt->execute($logParams);
$payload['logs'] = $stmt->fetchAll() ?: [];

log_action('lgpd_exportar_paciente', 'pacientes', $id, 'Exportação LGPD do paciente em JSON.');

$fileName = 'paciente_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower((string) $paciente['nome'])) . '_' . date('Ymd_His') . '.json';
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
