<?php
require_once __DIR__.'/bootstrap.php';$p=portal_require_patient();$eid=(int)$p['empresa_id'];
$theme=(string)input('tema','');$uid=(int)input('profissional_id');$date=(string)input('data',date('Y-m-d'));$error='';
$themes=clinic_enabled_specialties($eid);$professionals=portal_professionals($eid,$theme);$slots=[];
try{
 $slots=portal_slots($eid,$theme,$uid,$date);
 if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_validate();portal_request($p,$theme,$uid,$date,(string)input('hora'));redirect(APP_URL.'/portal/agenda.php?solicitado=1');
 }
}catch(Throwable $e){$error=$e instanceof PDOException?'Não foi possível processar. Tente novamente.':$e->getMessage();}
portal_header('Solicitar agendamento');
?>
<a href="agenda.php" class="text-primary">Voltar para meus agendamentos</a>
<?php if($error):?><p role="alert" class="card p-4 text-red-700"><?=e($error)?></p><?php endif;?>
<div class="card p-5 space-y-4"><p>Escolha especialidade, profissional e dia. Os horários seguem o expediente publicado pela clínica, excluem bloqueios e consultas existentes e têm antecedência mínima de 1 hora. Solicitar não garante a reserva.</p>
<form method="GET" class="space-y-4">
<label class="block">Especialidade<select name="tema" class="form-select" required onchange="this.form.profissional_id.value='';this.form.submit()"><option value="">Selecione...</option><?php foreach($themes as $t):?><option value="<?=e($t)?>" <?=$theme===$t?'selected':''?>><?=e(theme_option_label($t))?></option><?php endforeach;?></select></label>
<label class="block">Profissional<select name="profissional_id" class="form-select" required><option value="">Selecione...</option><?php foreach($professionals as $u):?><option value="<?=(int)$u['id']?>" <?=$uid===(int)$u['id']?'selected':''?>><?=e($u['nome'])?></option><?php endforeach;?></select></label>
<?php if($theme && !$professionals):?><p>Nenhum profissional disponível nessa especialidade.</p><?php endif;?>
<label class="block">Dia<input type="date" name="data" value="<?=e($date)?>" min="<?=date('Y-m-d')?>" max="<?=date('Y-m-d',strtotime('+90 days'))?>" required class="form-input"></label>
<button class="btn btn-primary">Consultar horários</button>
</form>
<?php if($slots):?>
<form method="POST" class="space-y-4"><?=csrf_field()?>
<input type="hidden" name="tema" value="<?=e($theme)?>"><input type="hidden" name="profissional_id" value="<?=$uid?>"><input type="hidden" name="data" value="<?=e($date)?>">
<label class="block">Horário<select name="hora" class="form-select" required><option value="">Selecione...</option><?php foreach($slots as $slot):?><option value="<?=e($slot)?>"><?=e($slot)?></option><?php endforeach;?></select></label>
<p class="text-sm">Solicitação para <?=e(theme_option_label($theme))?>, em <?=e(date('d/m/Y',strtotime($date)))?>. A clínica analisará e confirmará o atendimento.</p>
<button class="btn btn-primary">Enviar solicitação</button>
</form>
<?php elseif($uid):?><p role="status">Não há horários disponíveis nessa data. Escolha outro dia ou profissional.</p><?php endif;?>
</div>
<?php portal_footer();?>
