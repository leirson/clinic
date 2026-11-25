<?php
require_once __DIR__.'/bootstrap.php';$p=portal_require_patient();
$eid=(int)$p['empresa_id'];
$s=db()->prepare('SELECT a.data_hora_inicio,a.data_hora_fim,a.status,a.modalidade,u.nome AS profissional FROM agendamentos a JOIN usuarios u ON u.id=a.profissional_id AND u.empresa_id=a.empresa_id WHERE a.empresa_id=? AND a.paciente_id=? ORDER BY a.data_hora_inicio DESC LIMIT 100');$s->execute([$eid,$p['id']]);$appointments=$s->fetchAll();
$s=db()->prepare('SELECT r.data_inicio,r.especialidade,r.status,u.nome AS profissional FROM portal_solicitacoes r JOIN usuarios u ON u.id=r.profissional_id AND u.empresa_id=r.empresa_id WHERE r.empresa_id=? AND r.paciente_id=? ORDER BY r.criado_em DESC LIMIT 100');$s->execute([$eid,$p['id']]);$requests=$s->fetchAll();
portal_header('Meus agendamentos');
?>
<div class="flex flex-wrap justify-between gap-3 items-center"><p>Olá, <?=e(explode(' ',trim($p['nome']))[0])?>.</p><form method="POST" action="sair.php"><?=csrf_field()?><button class="btn bg-slate-100">Sair com segurança</button></form></div>
<a class="btn btn-primary" href="novo.php">Solicitar novo agendamento</a>
<?php if(isset($_GET['solicitado'])):?><p role="status" class="card p-4">Solicitação enviada! Aguarde a confirmação da clínica. O horário ainda não está reservado.</p><?php endif;?>
<section class="card p-5 space-y-3"><h2 class="text-xl font-bold">Agenda — até 100 registros mais recentes</h2>
<?php if(!$appointments):?><p>Nenhum agendamento encontrado.</p><?php endif;?>
<?php foreach($appointments as $a):?><article class="border rounded-lg p-4"><strong><?=e(date('d/m/Y H:i',strtotime($a['data_hora_inicio'])))?></strong><p><?=e($a['profissional'])?></p><p><?=e(ucfirst($a['status']))?> · <?=e($a['modalidade'])?></p></article><?php endforeach;?>
</section>
<section class="card p-5 space-y-3"><h2 class="text-xl font-bold">Minhas solicitações — até 100 mais recentes</h2>
<?php if(!$requests):?><p>Nenhuma solicitação enviada.</p><?php endif;?>
<?php foreach($requests as $r):?><article class="border rounded-lg p-4"><strong><?=e(date('d/m/Y H:i',strtotime($r['data_inicio'])))?></strong><p><?=e(theme_option_label($r['especialidade']))?> · <?=e($r['profissional'])?></p><p><?=e(['pendente'=>'Pendente de confirmação pela clínica','confirmada'=>'Confirmada pela clínica','recusada'=>'Não aceita pela clínica'][$r['status']]??$r['status'])?></p></article><?php endforeach;?>
</section>
<?php portal_footer();?>
