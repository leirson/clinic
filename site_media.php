<?php
require_once __DIR__.'/includes/functions.php';require_once __DIR__.'/includes/clinic_website.php';
header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: default-src 'none'; sandbox");
$slug=is_string($_GET['clinica']??null)?$_GET['clinica']:'';$name=is_string($_GET['img']??null)?$_GET['img']:'';
$preview=isset($_GET['preview']);
if($preview){site_require_admin();$eid=active_company_id();$s=site_read($eid);if($s['slug']!==$slug){http_response_code(404);exit;}}
else{$e=site_find($slug);if(!$e){http_response_code(404);exit;}$eid=(int)$e['id'];$s=site_read($eid);}
$allowed=[];
if($s['logo'])$allowed[]=$s['logo'];
if(!empty($s['sections']['hero']['enabled'])&&$s['hero_image'])$allowed[]=$s['hero_image'];
if(!empty($s['sections']['professionals']['enabled']))foreach(site_visible_cards($s,'professionals',$eid) as $p)if($p['image'])$allowed[]=$p['image'];
if(!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/',$name)||!in_array($name,$allowed,true)){http_response_code(404);exit;}
$dir=realpath(APP_PATH.'/storage/site/'.$eid);$path=realpath(APP_PATH.'/storage/site/'.$eid.'/'.$name);
if(!$dir||!$path||strpos($path,$dir.DIRECTORY_SEPARATOR)!==0||!is_file($path)){http_response_code(404);exit;}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)){http_response_code(404);exit;}
header('Cache-Control: no-store');header('Content-Type: '.$mime);header('Content-Length: '.filesize($path));readfile($path);
