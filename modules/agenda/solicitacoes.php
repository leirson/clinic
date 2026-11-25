<?php
require_once __DIR__.'/../../includes/functions.php';require_login();
if(!in_array($_SESSION['perfil']??'',['admin','recepcao','desenvolvedor'],true)){http_response_code(403);exit('Acesso restrito à recepção e administração.');}
require_once __DIR__.'/../../includes/patient_portal.php';$eid=active_company_id();$page_title='Solicitações do portal';
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_validate();
 try{portal_review($eid,(int)input('id'),(string)input('acao'));flash('success','Solicitação analisada.');}
 catch(Throwable $e){flash('error',$e instanceof PDOException?'Não foi possível processar a solicitação.':$e->getMessage());}
 redirect('solicitacoes.php');
}
$rows=[];
if(portal_ready()){
 $s=db()->prepare("SELECT r.*,p.nome AS paciente,u.nome AS profissional FROM portal_solicitacoes r JOIN pacientes p ON p.id=r.paciente_id AND p.empresa_id=r.empresa_id JOIN usuarios u ON u.id=r.profissional_id AND u.empresa_id=r.empresa_id WHERE r.empresa_id=? ORDER BY (r.status='pendente') DESC,r.criado_em DESC LIMIT 200");$s->execute([$eid]);$rows=$s->fetchAll();
}
require_once __DIR__.'/../../includes/header.php';
?>
<div class="space-y-5"><h1 class="text-2xl font-bold">Solicitações da área do paciente</h1><a class="text-primary" href="index.php">Voltar à agenda</a>
<p>Confirme para criar o agendamento na agenda da clínica. A disponibilidade é verificada novamente ao confirmar. Solicitações pendentes não reservam horários. Mostrando até 200 solicitações, com as pendentes primeiro.</p>
<?php if(!portal_ready()):?><p>Importe a migração v15 para utilizar o portal.</p><?php elseif(!$rows):?><p>Nenhuma solicitação recebida.</p><?php endif;?>
<?php foreach($rows as $r):?><article class="card p-5 space-y-3"><h2 class="font-bold"><?=e($r['paciente'])?></h2><p><?=e(theme_option_label($r['especialidade']))?> · <?=e($r['profissional'])?></p><p><?=e(date('d/m/Y H:i',strtotime($r['data_inicio'])))?> — <?=e($r['status'])?></p>
<?php if($r['status']==='pendente'):?><form method="POST" class="flex gap-3 flex-wrap"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button class="btn btn-primary" name="acao" value="confirmar">Confirmar e criar agendamento</button><button class="btn bg-slate-100" name="acao" value="recusar">Recusar solicitação</button></form><?php elseif($r['agendamento_id']):?><a class="text-primary" href="ver.php?id=<?=(int)$r['agendamento_id']?>">Ver agendamento criado</a><?php endif;?></article><?php endforeach;?>
</div>
<?php require_once __DIR__.'/../../includes/footer.php';?>
