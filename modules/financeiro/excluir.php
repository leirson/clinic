<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
require_post_action('Excluir lançamento');
db()->prepare("DELETE FROM contas_financeiras WHERE id=? AND empresa_id=?")->execute([(int)input('id'), active_company_id()]);
flash('success','Removido.');
redirect('index.php');
