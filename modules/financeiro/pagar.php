<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();
require_post_action('Marcar lançamento como pago');
$id = (int)input('id');
db()->prepare("UPDATE contas_financeiras SET status='pago', data_pagamento=CURDATE() WHERE id=? AND empresa_id=?")
    ->execute([$id, active_company_id()]);
flash('success','Lançamento marcado como pago.');
redirect('index.php');
