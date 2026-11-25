<?php
$page_title = 'Emitir NFSe';
require_once __DIR__ . '/../../includes/header.php';

$paciente_id = (int)input('paciente_id');
$ag_id = (int)input('agendamento_id') ?: null;

$empresa = current_empresa();
$prox_rps = (int)($empresa['asten_proximo_rps'] ?? 1);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    try {
        $pid = (int)input('paciente_id');
        clinic_validate_clinical_links($pid, 0, $ag_id);
        db()->beginTransaction();
        $rpsLock = db()->prepare('SELECT asten_proximo_rps FROM empresas WHERE id=? FOR UPDATE');
        $rpsLock->execute([active_company_id()]);
        $prox_rps = (int) $rpsLock->fetchColumn();
        $valor = (float)str_replace(',','.',str_replace('.','',input('valor_servico',0)));
        $aliq = (float)str_replace(',','.',input('aliquota_iss', $empresa['aliquota_iss']));
        $iss = round($valor * $aliq / 100, 2);
        $deducoes = (float)str_replace(',','.',str_replace('.','',input('valor_deducoes',0)));

        $stmt = db()->prepare("INSERT INTO nfse (empresa_id, paciente_id, agendamento_id, numero_rps, serie_rps, data_emissao, data_competencia, valor_servico, valor_deducoes, valor_iss, aliquota_iss, valor_liquido, discriminacao, item_lista_servico, codigo_tributacao_municipio, iss_retido, status, criado_por) VALUES (?,?,?,?,?,NOW(),?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
          active_company_id(), $pid, $ag_id,
          $prox_rps, $empresa['asten_serie_rps'] ?: '1',
          input('data_competencia', date('Y-m-d')),
          $valor, $deducoes, $iss, $aliq,
          $valor - $deducoes - ($iss > 0 && input('iss_retido') ? $iss : 0),
          input('discriminacao'),
          input('item_lista_servico', $empresa['item_lista_servico']),
          input('codigo_tributacao_municipio', $empresa['codigo_tributacao_municipio']),
          input('iss_retido') ? 1 : 0,
          'rascunho', $_SESSION['user_id']
        ]);
        $id = db()->lastInsertId();

        // Incrementar RPS
        db()->prepare("UPDATE empresas SET asten_proximo_rps = asten_proximo_rps + 1 WHERE id = ?")->execute([active_company_id()]);

        db()->commit();
        log_action('criar','nfse',$id);
        flash('success','NFSe criada como rascunho. Revise e envie quando estiver pronta.');

        if (input('enviar_agora')) {
            redirect('enviar.php?id='.$id);
        }
        redirect('ver.php?id='.$id);
    } catch (Exception $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash('error','Erro: '.$e->getMessage());
    }
}

$pacientes = db()->prepare("SELECT id, nome FROM pacientes WHERE empresa_id=? AND ativo=1 ORDER BY nome");
$pacientes->execute([active_company_id()]);
$pacientes = $pacientes->fetchAll();

// Sugerir discriminação baseada em agendamento
$disc_sugestao = '';
$valor_sugestao = '';
if ($ag_id) {
    $ag = db()->prepare("SELECT a.*, pr.nome AS proc_nome FROM agendamentos a LEFT JOIN procedimentos pr ON pr.id=a.procedimento_id WHERE a.id=? AND a.empresa_id=?");
    $ag->execute([$ag_id, active_company_id()]);
    $ag = $ag->fetch();
    if ($ag) {
        $disc_sugestao = "Prestação de serviços de " . ($ag['proc_nome'] ?: 'atendimento') . " realizado em " . format_date($ag['data_hora_inicio']);
        $valor_sugestao = number_format($ag['valor'], 2, ',', '.');
    }
}
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold"><i class="bi bi-receipt"></i> Emitir NFSe</h1></div>

<?php if (empty($empresa['asten_url_ws']) || empty($empresa['cnpj']) || $empresa['cnpj']==='00.000.000/0001-00'): ?>
<div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4 rounded">
  <strong>⚠ Atenção:</strong> Os dados da empresa e as credenciais da emissão NFSe Nacional devem ser configurados antes de emitir.
  <a href="../empresa/index.php" class="text-primary underline">Configurar agora</a>
</div>
<?php endif; ?>

<form method="POST" class="card p-6 space-y-5">
  <?= csrf_field() ?>
  <input type="hidden" name="agendamento_id" value="<?= $ag_id ?>">

  <div class="bg-slate-50 p-3 rounded text-xs text-slate-600">
    <strong>RPS:</strong> <?= $prox_rps ?>/<?= e($empresa['asten_serie_rps'] ?: '1') ?> · <strong>Ambiente:</strong> <?= e($empresa['asten_ambiente']) ?>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="md:col-span-2">
      <label class="text-sm font-medium">Tomador (Paciente) *</label>
      <select data-patient-search name="paciente_id" required class="form-select">
        <option value="">Selecione...</option>
        <?php foreach($pacientes as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $p['id']==$paciente_id?'selected':'' ?>><?= e($p['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm font-medium">Data Competência</label>
      <input type="date" name="data_competencia" value="<?= date('Y-m-d') ?>" class="form-input">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div>
      <label class="text-sm font-medium">Valor Serviços *</label>
      <input name="valor_servico" required value="<?= e($valor_sugestao) ?>" class="form-input" placeholder="0,00">
    </div>
    <div>
      <label class="text-sm font-medium">Deduções</label>
      <input name="valor_deducoes" value="0,00" class="form-input">
    </div>
    <div>
      <label class="text-sm font-medium">Alíquota ISS (%)</label>
      <input name="aliquota_iss" value="<?= e($empresa['aliquota_iss'] ?? '5') ?>" class="form-input">
    </div>
    <div class="flex items-end">
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="iss_retido" value="1"> ISS Retido
      </label>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="text-sm font-medium">Item Lista de Serviço (LC 116)</label>
      <input name="item_lista_servico" value="<?= e($empresa['item_lista_servico']) ?>" placeholder="Ex: 4.01" class="form-input">
      <p class="text-xs text-slate-500 mt-1">Médico: 4.01 · Dentista: 4.12</p>
    </div>
    <div>
      <label class="text-sm font-medium">Código Tributação Município</label>
      <input name="codigo_tributacao_municipio" value="<?= e($empresa['codigo_tributacao_municipio']) ?>" class="form-input">
    </div>
  </div>

  <div>
    <label class="text-sm font-medium">Discriminação do Serviço *</label>
    <textarea name="discriminacao" required rows="4" class="form-textarea" placeholder="Descreva o serviço prestado..."><?= e($disc_sugestao) ?></textarea>
  </div>

  <div class="flex justify-end gap-2 pt-3 border-t">
    <a href="index.php" class="btn bg-slate-200 text-slate-700">Cancelar</a>
    <button type="submit" name="enviar_agora" value="" class="btn bg-slate-600 text-white">Salvar Rascunho</button>
    <button type="submit" name="enviar_agora" value="1" class="btn btn-primary"><i class="bi bi-send"></i> Salvar e Enviar</button>
  </div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
