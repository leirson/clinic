<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
require_post_action('Desativar usuário');
$id = (int)input('id');
if ($id == $_SESSION['user_id']) { flash('error','Não é possível excluir o próprio usuário'); redirect('index.php'); }
db()->prepare("UPDATE usuarios SET ativo=0 WHERE id=? AND empresa_id=?")->execute([$id, active_company_id()]);
flash('success','Usuário desativado');
redirect('index.php');
