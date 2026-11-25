<?php
require_once __DIR__.'/bootstrap.php';
if(portal_patient())redirect(APP_URL.'/portal/agenda.php');
$error='';$companies=portal_companies();
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_validate();
 try{
  if(portal_authenticate((int)input('empresa_id'),input('cpf'),input('telefone')))redirect(APP_URL.'/portal/agenda.php');
 }catch(Throwable $e){error_log('Portal: falha ao autenticar.');}
 $error='Não foi possível entrar. Confira os dados cadastrados na clínica ou tente novamente em 15 minutos.';
}
portal_header('Área do paciente');
?>
<div class="card p-6 space-y-4">
<p>Entre com seu CPF e o telefone já cadastrado na clínica. Informe o DDD. Para corrigir seu cadastro, entre em contato com a recepção.</p>
<?php if($error):?><p role="alert" class="text-red-700"><?=e($error)?></p><?php endif;?>
<?php if(!$companies):?><p>O acesso de pacientes não está disponível. Entre em contato com sua clínica.</p><?php else:?>
<form method="POST" class="space-y-4">
<?=csrf_field()?>
<label class="block">Clínica<select name="empresa_id" class="form-select" required><?php foreach($companies as $e):?><option value="<?=(int)$e['id']?>" <?=(int)input('empresa_id')===(int)$e['id']?'selected':''?>><?=e($e['nome_fantasia']?:$e['razao_social'])?></option><?php endforeach;?></select></label>
<label class="block">CPF<input name="cpf" type="text" inputmode="numeric" autocomplete="off" maxlength="14" required class="form-input" placeholder="000.000.000-00"></label>
<label class="block">Telefone com DDD<input name="telefone" type="tel" autocomplete="off" maxlength="20" required class="form-input" placeholder="(11) 99999-9999"></label>
<button class="btn btn-primary">Entrar na área do paciente</button>
</form>
<p class="text-sm text-slate-500">Acesso restrito aos seus agendamentos e solicitações. Não inclui prontuários, exames ou documentos clínicos. Utilize um dispositivo privado e saia ao terminar.</p>
<?php endif;?>
<a class="block text-primary" href="<?=APP_URL?>/login.php">Acesso da equipe da clínica</a>
</div>
<?php portal_footer();?>
