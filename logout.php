<?php
require_once __DIR__ . '/includes/functions.php';
log_action('logout','usuarios',$_SESSION['user_id'] ?? null,'Logout');
session_unset();
session_destroy();
redirect(APP_URL . '/login.php');
