<?php
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/clinic_website.php';
require_once __DIR__.'/includes/clinic_website_view.php';
require_once __DIR__.'/includes/patient_portal.php';
header('Cache-Control: no-store');
if(isset($_GET['preview'])){site_require_admin();$eid=active_company_id();site_render($eid,site_read($eid),true);exit;}
$companies=site_entry_companies();$selected=null;
if(isset($_GET['clinica'])){
 $slug=is_string($_GET['clinica'])?$_GET['clinica']:'';
 foreach($companies as $company)if($company['configured']&&$company['site']['slug']===$slug){$selected=$company;break;}
 if(!$selected){http_response_code(404);exit('Página da clínica não encontrada ou indisponível.');}
}else{
 $host=strtolower(preg_replace('/:\d+$/','',(string)($_SERVER['HTTP_HOST']??'')));
 $matching=array_values(array_filter($companies,static function($e)use($host){
  $domain=trim((string)($e['dominio_principal']??''));if(!$domain)return false;
  $parsed=parse_url(strpos($domain,'://')!==false?$domain:'https://'.$domain);return strtolower($parsed['host']??'')===$host;
 }));
 if(count($matching)===1)$selected=$matching[0];
 else{
  $configured=array_values(array_filter($companies,static function($e){return $e['configured'];}));
  if(count($configured)===1)$selected=$configured[0];
  elseif(!$configured)redirect(APP_URL.(is_logged()?'/dashboard.php':'/login.php'));
  else $companies=$configured;
 }
}
if($selected){
 $s=$selected['site'];$eid=(int)$selected['id'];
 if(!empty($s['published']))site_render($eid,$s);
 elseif($selected['configured'])site_render_access($eid,$s);
 else redirect(APP_URL.(is_logged()?'/dashboard.php':'/login.php'));
 exit;
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Clínicas — <?=e(APP_NAME)?></title><?=clinic_asset_styles(['resources/entries/site.js'])?></head><body class="clinic-web"><main class="site-directory"><span class="site-eyebrow">Atendimento e cuidado</span><h1>Encontre sua clínica</h1><p>Selecione sua clínica para continuar.</p>
<?php foreach($companies as $e):?><a href="<?=e(APP_URL)?>/index.php?clinica=<?=rawurlencode($e['site']['slug'])?>"><strong><?=e($e['site']['name'])?></strong><small><?=e(!empty($e['site']['published'])?$e['site']['tagline']:'Acessar atendimento')?></small></a><?php endforeach;?>
</main></body></html>
