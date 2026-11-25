<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
require_post_action('Remover paciente');
$id = (int)input('id');
$stmt = db()->prepare("UPDATE pacientes SET ativo=0 WHERE id=? AND empresa_id=?");
$stmt->execute([$id, active_company_id()]);
log_action('excluir','pacientes',$id);
flash('success','Paciente removido.');
redirect('index.php');
