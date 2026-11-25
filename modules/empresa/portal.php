<?php
require_once __DIR__.'/../../includes/functions.php';require_admin();
require_once __DIR__.'/../../includes/patient_portal.php';$eid=active_company_id();$page_title='Área do paciente';
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_validate();
 try{
  if(!portal_ready())throw new RuntimeException('Importe sql/upgrade_v15_portal_paciente.sql antes de habilitar.');
  $start=(string)input('inicio');$end=(string)input('fim');$duration=(int)input('duracao');$days=input('dias',[]);
  if(!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/',$start)||!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/',$end)||$end<=$start||!in_array($duration,[15,20,30,40,60],true)||!is_array($days)||!$days)throw new RuntimeException('Confira expediente, duração e dias de atendimento.');
  foreach($days as $day)if(!is_scalar($day)||!preg_match('/^[1-7]$/',(string)$day))throw new RuntimeException('Dia de atendimento inválido.');
  if(input('habilitar') && !input('ciente'))throw new RuntimeException('Confirme ciência sobre o acesso com CPF e telefone.');
  db()->beginTransaction();
  $values=['portal_paciente_enabled'=>input('habilitar')?'1':'0','portal_paciente_inicio'=>$start,'portal_paciente_fim'=>$end,'portal_paciente_duracao'=>(string)$duration,'portal_paciente_dias'=>json_encode(array_values(array_unique(array_map('intval',$days))))];
  if(!portal_config($eid,'portal_paciente_secret'))$values['portal_paciente_secret']=bin2hex(random_bytes(32));
  app_config_set_many($values,$eid);log_action('configurar_portal','empresas',$eid,'Portal e expediente configurados.');db()->commit();flash('success','Configuração da área do paciente salva.');redirect('portal.php');
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());}
}
$enabled=portal_config($eid,'portal_paciente_enabled','0')==='1';[$days,$start,$end,$duration]=portal_hours($eid);
require_once __DIR__.'/../../includes/header.php';
?>
<div class="space-y-5"><h1 class="text-2xl font-bold">Área do paciente</h1><a class="text-primary" href="index.php">Voltar para Empresa</a>
<?php if(!portal_ready()):?><p class="card p-4 text-red-700">Migração pendente: importe somente sql/upgrade_v15_portal_paciente.sql no banco existente.</p><?php endif;?>
<form method="POST" class="card p-6 space-y-4"><?=csrf_field()?>
<label class="block"><input type="checkbox" name="habilitar" value="1" <?=$enabled?'checked':''?>> Habilitar área do paciente e mostrar o acesso na tela de login</label>
<p>Somente pacientes ativos já cadastrados nesta empresa podem entrar usando CPF e telefone/celular com DDD. O portal mostra exclusivamente a agenda e as solicitações do paciente. O recurso inicia desabilitado.</p>
<p class="text-red-700">CPF e telefone não comprovam posse do telefone e podem ser conhecidos por terceiros. Este modo tem autenticação de baixa garantia, mesmo com limite de tentativas. Para maior proteção, recomenda-se adicionar código por SMS/WhatsApp antes de disponibilizar o portal. Use HTTPS em produção e mantenha os cadastros atualizados.</p>
<label class="block"><input type="checkbox" name="ciente" value="1" required> Estou ciente das limitações de autenticação e da responsabilidade da clínica pelo uso deste acesso.</label>
<h2 class="font-bold">Expediente para solicitações online</h2><p class="text-sm">Configuração comum aos profissionais da clínica. Cadastre ausências, intervalos, feriados e exceções em bloqueios da agenda. Pedidos ficam pendentes até a análise da equipe; não reservam o horário.</p>
<div class="flex flex-wrap gap-4"><?php foreach([1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb',7=>'Dom'] as $n=>$label):?><label><input type="checkbox" name="dias[]" value="<?=$n?>" <?=in_array($n,array_map('intval',$days),true)?'checked':''?>> <?=$label?></label><?php endforeach;?></div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4"><label>Início<input type="time" name="inicio" required class="form-input" value="<?=e($start)?>"></label><label>Fim<input type="time" name="fim" required class="form-input" value="<?=e($end)?>"></label><label>Duração<select name="duracao" class="form-select"><?php foreach([15,20,30,40,60] as $v):?><option value="<?=$v?>" <?=$duration===$v?'selected':''?>><?=$v?> minutos</option><?php endforeach;?></select></label></div>
<button class="btn btn-primary">Salvar configuração</button>
<a class="btn bg-slate-100" href="<?=APP_URL?>/portal/index.php" target="_blank" rel="noopener">Abrir área do paciente</a>
</form></div>
<?php require_once __DIR__.'/../../includes/footer.php';?>
