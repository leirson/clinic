<?php
require_once __DIR__.'/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');exit;}
csrf_validate();$_SESSION=[];session_regenerate_id(true);redirect(APP_URL.'/portal/index.php');
