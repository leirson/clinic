<?php
/** Lightweight public clinic layout. All editable content remains scoped to the clinic. */
function site_ui_icon($key, $class = '') {
 $paths=[
 'medical'=>'<path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6z"/>',
 'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18m-13 5h2m4 0h2"/>',
 'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 'pin'=>'<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
 'phone'=>'<path d="M5 3h4l2 5-3 2a14 14 0 0 0 6 6l2-3 5 2v4a2 2 0 0 1-2 2C10 21 3 14 3 5a2 2 0 0 1 2-2Z"/>',
 'mail'=>'<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/>',
 'people'=>'<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m2-16a3 3 0 0 1 0 6m2 10v-3a6 6 0 0 0-2-4"/>',
 'heart'=>'<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/><path d="M5 12h4l2-4 2 8 2-4h4"/>',
 'arrow'=>'<path d="M4 12h16m-6-6 6 6-6 6"/>',
 'quote'=>'<path d="M10 5H4v7h5c0 4-2 6-5 7m16-14h-6v7h5c0 4-2 6-5 7"/>',
 'chat'=>'<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 10 10 0 0 1-4-.9L3 21l1.9-5.5a10 10 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3 8.5 8.5 0 0 1 21 11.5Z"/>',
 'stethoscope'=>'<path d="M5 3v5a5 5 0 0 0 10 0V3M3 3h4m6 0h4M10 13v3a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="11" r="3"/>',
 'brain'=>'<path d="M12 5a4 4 0 0 0-7-1 4 4 0 0 0-2 7 4 4 0 0 0 3 7 3 3 0 0 0 6 2V5Zm0 0a4 4 0 0 1 7-1 4 4 0 0 1 2 7 4 4 0 0 1-3 7 3 3 0 0 1-6 2M8 8a3 3 0 0 0-2 3m10-3a3 3 0 0 1 2 3"/>',
 'eye'=>'<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
 'tooth'=>'<path d="M12 4C9 1 3 3 4 9c1 5 2 12 5 12 2 0 1-7 3-7s1 7 3 7c3 0 4-7 5-12 1-6-5-8-8-5Z"/>',
 ];
 return '<svg class="site-icon '.e($class).'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.($paths[$key]??$paths['medical']).'</svg>';
}
function site_specialty_icon($theme) {
 $keys=['dentista'=>'tooth','cardiologia'=>'heart','psicologia'=>'brain','psiquiatria'=>'brain','neurologia'=>'brain','oftalmologia'=>'eye','medico'=>'stethoscope'];
 return site_ui_icon($keys[$theme]??'medical');
}
function site_nav_links($visible) {
 foreach($visible as $key=>$sec){if(in_array($key,['hero','footer'],true))continue;?><a href="#<?=e($key)?>"><?=e($sec['title'])?></a><?php }
}
function site_render($eid,$s,$preview=false) {
 $visible=[];$cards=[];
 foreach(['professionals','specialties','reviews'] as $kind)$cards[$kind]=site_visible_cards($s,$kind,$eid);
 if($preview&&!empty($s['example_content']))foreach(($s['reviews']??[]) as $sample)if(!empty($sample['example_demo']))$cards['reviews'][]=$sample;
 foreach($s['sections'] as $key=>$section)if(!empty($section['enabled'])&&(!isset($cards[$key])||$cards[$key]))$visible[$key]=$section;
 uasort($visible,static function($a,$b){return $a['order']<=>$b['order'];});
 $cta=site_cta($s,$eid);$title=$s['seo_title']?:$s['name'];$description=$s['seo_description']?:$s['tagline'];
 $hasPortal=function_exists('portal_enabled')&&portal_enabled($eid);$showContact=isset($visible['contact']);
 $rgb=sscanf($s['color'],'#%02x%02x%02x');$fg=(($rgb[0]*299+$rgb[1]*587+$rgb[2]*114)/1000)>155?'#10212b':'#ffffff';
 header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: strict-origin-when-cross-origin');header("Content-Security-Policy: frame-ancestors 'self'; object-src 'none'; base-uri 'self'");
 ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=e($title)?></title><meta name="description" content="<?=e($description)?>"><?php if($preview):?><meta name="robots" content="noindex,nofollow"><?php endif;?><?=clinic_asset_styles(['resources/entries/site.js'])?></head>
 <body class="clinic-web" style="--site-primary:<?=e($s['color'])?>;--site-background:<?=e($s['background'])?>;--site-button-text:<?=$fg?>">
 <a class="site-skip" href="#main-content">Pular para o conteúdo</a>
 <div class="site-topbar"><div class="site-container"><span class="site-topbar-info"><?php if($showContact&&$s['hours']):?><?=site_ui_icon('clock')?><?=e($s['hours'])?><?php else:?><?=site_ui_icon('medical')?><?=e($s['tagline'])?><?php endif;?></span><a href="<?=APP_URL?>/login.php">Área profissional <span aria-hidden="true">↗</span></a></div></div>
 <?php if($preview):?><div class="site-preview" role="status">PRÉ-VISUALIZAÇÃO · Versão salva · <?=!empty($s['published'])?'Publicada':'Ainda não publicada'?> · <a href="<?=APP_URL?>/modules/pagina_web/index.php">Voltar ao editor</a></div><?php endif;?>
 <?php if(!empty($s['example_content'])):?><div class="site-preview" role="note">PÁGINA DE DEMONSTRAÇÃO · Textos, endereço e contatos ilustrativos. Não utilize estes dados para atendimento real.</div><?php endif;?>
 <header class="site-header"><div class="site-container site-nav"><a class="site-brand" href="<?=APP_URL?>/index.php?clinica=<?=rawurlencode($s['slug'])?>">
 <?php if($s['logo']):?><img src="<?=e(site_media_url($s,$s['logo'],$preview))?>" alt="<?=e($s['name'])?>" width="56" height="56"><?php else:?><span class="site-monogram" aria-hidden="true"><?=site_ui_icon('medical')?></span><?php endif;?><span><?=e($s['name'])?><small><?=e($s['tagline'])?></small></span></a>
 <nav class="site-links" aria-label="Navegação da clínica"><?php site_nav_links($visible);?></nav>
 <div class="site-nav-actions"><?php if($hasPortal):?><a class="site-button site-button-small" href="<?=APP_URL?>/portal/index.php?empresa_id=<?=(int)$eid?>"><?=site_ui_icon('calendar')?>Área do paciente</a><?php elseif($cta):?><a class="site-button site-button-small" href="<?=e($cta)?>"><?=site_ui_icon('calendar')?><?=e($s['cta_label'])?></a><?php endif;?></div>
 <?php if(count(array_diff(array_keys($visible),['hero','footer']))):?><details class="site-mobile-menu"><summary aria-controls="site-mobile-navigation"><span class="site-menu-open-label">Menu</span><span class="site-menu-close-label">Fechar</span><span class="site-menu-glyph" aria-hidden="true"><span></span><span></span><span></span></span></summary><nav id="site-mobile-navigation" aria-label="Navegação da clínica no celular"><?php site_nav_links($visible);?></nav></details><?php endif;?>
 </div></header>
 <main id="main-content">
 <?php foreach($visible as $key=>$sec):?>
 <?php if($key==='hero'):?>
 <section id="hero" class="site-hero"><div class="site-container site-hero-grid"><div class="site-hero-copy"><span class="site-eyebrow"><span class="site-status-dot" aria-hidden="true"></span><?=e($s['name'])?></span><h1><?=e($sec['title'])?></h1><p class="site-hero-description"><?=nl2br(e($sec['text']))?></p><div class="site-hero-actions"><?php if($cta):?><a class="site-button" href="<?=e($cta)?>"><?=site_ui_icon('calendar')?><?=e($s['cta_label'])?><?=site_ui_icon('arrow')?></a><?php endif;?><?php if(isset($visible['professionals'])):?><a class="site-text-link" href="#professionals">Conheça nossa equipe <?=site_ui_icon('arrow')?></a><?php endif;?></div>
 <?php if($showContact&&($s['phone']||$s['hours'])):?><div class="site-hero-details"><?php if($s['phone']):?><span><?=site_ui_icon('phone')?><span><small>Fale com a clínica</small><a href="tel:<?=e(preg_replace('/[^+0-9]/','',$s['phone']))?>"><?=e($s['phone'])?></a></span></span><?php endif;?><?php if($s['hours']):?><span><?=site_ui_icon('clock')?><span><small>Horários de atendimento</small><?=e($s['hours'])?></span></span><?php endif;?></div><?php endif;?></div>
 <div class="site-hero-visual">
 <?php if($s['hero_image']):?><figure class="site-hero-media"><img src="<?=e(site_media_url($s,$s['hero_image'],$preview))?>" alt="<?=e($s['name'])?>" fetchpriority="high" width="720" height="720"></figure><div class="site-image-caption"><?=site_ui_icon('medical')?><span><small><?=e($s['name'])?></small><strong><?=e($s['tagline'])?></strong></span></div>
 <?php else:?><div class="site-care-panel"><div class="site-care-panel-top"><span class="site-care-emblem"><?=site_ui_icon('stethoscope')?></span><span class="site-panel-label">Sua saúde<br><strong>em primeiro lugar.</strong></span></div><div class="site-care-panel-body"><span class="site-eyebrow">Bem-vindo à clínica</span><h2><?=e($s['tagline'])?></h2>
 <?php if(isset($visible['specialties'])):?><div class="site-care-specialties"><?php foreach(array_slice($cards['specialties'],0,3) as $card):?><a href="#specialties"><?=site_specialty_icon($card['theme'])?><span><?=e($card['name'])?></span><?=site_ui_icon('arrow')?></a><?php endforeach;?></div>
 <?php elseif($showContact&&$s['hours']):?><div class="site-care-hours"><?=site_ui_icon('clock')?><p><?=nl2br(e($s['hours']))?></p></div><?php endif;?>
 <?php if($cta):?><a class="site-care-panel-link" href="<?=e($cta)?>"><?=e($s['cta_label'])?><?=site_ui_icon('arrow')?></a><?php endif;?></div></div><?php endif;?>
 <span class="site-visual-cross" aria-hidden="true">+</span></div></div></section>
 <?php elseif($key==='footer'):?>
 <footer id="footer" class="site-footer"><div class="site-container"><div class="site-footer-grid"><div><div class="site-footer-brand"><?=site_ui_icon('medical')?><strong><?=e($s['name'])?></strong></div><p><?=e($s['tagline'])?></p><?php if($sec['title']):?><h2><?=e($sec['title'])?></h2><?php endif;?><?php if($sec['text']):?><p><?=nl2br(e($sec['text']))?></p><?php endif;?></div><nav aria-label="Links da clínica no rodapé"><?php site_nav_links($visible);?></nav><?php if($showContact):?><div class="site-footer-contact"><?php if($s['address']):?><p><?=site_ui_icon('pin')?><?=nl2br(e($s['address']))?></p><?php endif;?><?php if($s['phone']):?><a href="tel:<?=e(preg_replace('/[^+0-9]/','',$s['phone']))?>"><?=site_ui_icon('phone')?><?=e($s['phone'])?></a><?php endif;?></div><?php endif;?></div><div class="site-footer-bottom"><span>© <?=date('Y')?> · <?=e($s['footer_text'])?></span><a href="<?=APP_URL?>/login.php">Acesso da equipe ↗</a></div></div></footer>
 <?php else:?>
 <section id="<?=e($key)?>" class="site-section site-section-<?=e($key)?>"><div class="site-container">
 <?php if($key==='about'):?><div class="site-about-grid"><?php endif;?>
 <div class="site-section-heading"><span class="site-eyebrow"><?=site_ui_icon($key==='professionals'?'people':($key==='contact'?'pin':($key==='reviews'?'quote':'medical')))?><?=e($s['name'])?></span><h2><?=e($sec['title'])?></h2><p><?=nl2br(e($sec['text']))?></p><?php if($key==='about'&&$cta):?><a class="site-button site-button-outline" href="<?=e($cta)?>"><?=e($s['cta_label'])?><?=site_ui_icon('arrow')?></a><?php endif;?></div>
 <?php if($key==='about'):?><div class="site-about-panel"><span class="site-about-icon"><?=site_ui_icon('heart')?></span><p><?=e($s['tagline'])?></p><span class="site-about-signature"><?=e($s['name'])?></span><?php if(isset($visible['professionals'])):?><a class="site-text-link" href="#professionals">Conheça os profissionais <?=site_ui_icon('arrow')?></a><?php endif;?></div></div>
 <?php elseif($key==='contact'):?>
 <div class="site-contact-grid"><div class="site-card site-contact-card"><span class="site-contact-icon"><?=site_ui_icon('chat')?></span><h3>Vamos conversar</h3><div class="site-contact-links">
 <?php if($s['phone']):?><a href="tel:<?=e(preg_replace('/[^+0-9]/','',$s['phone']))?>"><?=site_ui_icon('phone')?><span><small>Telefone</small><?=e($s['phone'])?></span><?=site_ui_icon('arrow')?></a><?php endif;?>
 <?php if($s['email']):?><a href="mailto:<?=e($s['email'])?>"><?=site_ui_icon('mail')?><span><small>E-mail</small><?=e($s['email'])?></span><?=site_ui_icon('arrow')?></a><?php endif;?>
 <?php if($s['whatsapp']):$n=preg_replace('/\D/','',$s['whatsapp']);if(strlen($n)<=11)$n='55'.$n;?><a href="https://wa.me/<?=e($n)?>" target="_blank" rel="noopener noreferrer"><?=site_ui_icon('chat')?><span><small>Agendamentos e informações</small>Conversar no WhatsApp</span><?=site_ui_icon('arrow')?></a><?php endif;?>
 <?php if($s['instagram']):?><a href="<?=e($s['instagram'])?>" target="_blank" rel="noopener noreferrer"><span>Instagram da clínica</span><?=site_ui_icon('arrow')?></a><?php endif;?></div></div><div class="site-card site-visit-card"><span class="site-contact-icon"><?=site_ui_icon('pin')?></span><h3>Esperamos por você</h3><?php if($s['address']):?><div class="site-visit-row"><?=site_ui_icon('pin')?><div><h4>Endereço</h4><p><?=nl2br(e($s['address']))?></p></div></div><?php endif;?><?php if($s['hours']):?><div class="site-visit-row"><?=site_ui_icon('clock')?><div><h4>Horários de atendimento</h4><p><?=nl2br(e($s['hours']))?></p></div></div><?php endif;?><?php if($cta):?><a class="site-button" href="<?=e($cta)?>"><?=e($s['cta_label'])?><?=site_ui_icon('arrow')?></a><?php endif;?></div></div>
 <?php elseif(isset($cards[$key])):?>
 <div class="site-grid <?=$s['layout']==='list'?'site-grid-list':''?>">
 <?php foreach($cards[$key] as $card):?>
 <article class="site-card <?=$key==='reviews'?'site-review':($key==='professionals'?'site-professional':'site-specialty')?>">
 <?php if($key==='professionals'):?><div class="site-prof-portrait">
 <?php if($card['image']):?><img class="site-prof-photo" loading="lazy" src="<?=e(site_media_url($s,$card['image'],$preview))?>" alt="<?=e($card['name'])?>" width="480" height="480" decoding="async"><?php else:?><div class="site-avatar" aria-hidden="true"><span><?=e(mb_substr($card['name'],0,1))?></span><?=site_ui_icon('stethoscope')?></div><?php endif;?></div><div class="site-prof-body"><h3><?=e($card['name'])?></h3><p class="site-card-subtitle"><?=e($card['role'])?></p><?php if($card['registration']):?><p class="site-registration"><?=e($card['registration'])?></p><?php endif;?>
 <?php elseif($key==='specialties'):?><div class="site-specialty-top"><span class="site-specialty-icon"><?=site_specialty_icon($card['theme'])?></span><span class="site-card-decoration" aria-hidden="true">+</span></div><h3><?=e($card['name'])?></h3>
 <?php else:?><div class="site-review-top"><?=site_ui_icon('quote')?><div class="site-stars" aria-label="<?=e($card['rating'])?> de 5 estrelas"><?=str_repeat('★',(int)$card['rating'])?><span aria-hidden="true"><?=str_repeat('☆',5-(int)$card['rating'])?></span></div></div><?php endif;?>
 <p class="site-card-text"><?=nl2br(e($card['text']))?></p>
 <?php if($key==='reviews'):?><div class="site-review-author"><span class="site-review-initial" aria-hidden="true"><?=e(mb_substr($card['name'],0,1))?></span><h3><?=e($card['name'])?></h3></div><?php endif;?>
 <?php if($key==='professionals'):?></div><?php endif;?></article>
 <?php endforeach;?></div>
 <?php if($key==='reviews'):?><p class="site-small">Depoimentos selecionados e publicados pela clínica mediante autorização. Não são avaliações verificadas por plataforma externa.</p><?php endif;?>
 <?php endif;?></div></section>
 <?php endif;?>
 <?php endforeach;?></main>
 <?php if(!isset($visible['footer'])):?><div class="site-staff-access"><a href="<?=APP_URL?>/login.php">Acesso da equipe</a></div><?php endif;?>
 <?=clinic_asset_scripts(['resources/entries/site.js'])?>
 </body></html><?php
}
function site_render_access($eid,$s) {
 $staff=!empty($s['entry_staff']);$patient=!empty($s['entry_patient'])&&portal_enabled($eid);
 $rgb=sscanf($s['color'],'#%02x%02x%02x');$fg=(($rgb[0]*299+$rgb[1]*587+$rgb[2]*114)/1000)>155?'#10212b':'#ffffff';
 header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: frame-ancestors 'self'; object-src 'none'; base-uri 'self'");
 ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Acesso — <?=e($s['name'])?></title><?=clinic_asset_styles(['resources/entries/site.js'])?></head>
 <body class="clinic-web site-access-page" style="--site-primary:<?=e($s['color'])?>;--site-background:<?=e($s['background'])?>;--site-button-text:<?=$fg?>"><main class="site-access-shell" id="main-content"><span class="site-monogram" aria-hidden="true"><?=site_ui_icon('medical')?></span><span class="site-eyebrow">Bem-vindo à sua clínica</span><h1><?=e($s['name'])?></h1><p>Escolha como deseja acessar.</p><div class="site-access-grid">
 <?php if($patient):?><a class="site-access-card" data-entry="patient" href="<?=APP_URL?>/portal/index.php?empresa_id=<?=(int)$eid?>"><span class="site-access-symbol" aria-hidden="true"><?=site_ui_icon('calendar')?></span><h2>Área do paciente</h2><p>Consulte seus agendamentos e solicite um novo atendimento.</p><strong>Entrar como paciente <?=site_ui_icon('arrow')?></strong></a><?php endif;?>
 <?php if($staff):?><a class="site-access-card" data-entry="staff" href="<?=APP_URL?>/login.php"><span class="site-access-symbol" aria-hidden="true"><?=site_ui_icon('stethoscope')?></span><h2>Área profissional</h2><p>Acesso reservado aos profissionais e à equipe da clínica.</p><strong>Entrar como profissional <?=site_ui_icon('arrow')?></strong></a><?php endif;?>
 </div><?php if(!$staff&&!$patient):?><p role="status">Acesso de pacientes temporariamente indisponível. Entre em contato com a clínica.</p><?php endif;?>
 <small class="site-access-privacy">Suas informações são acessadas somente após identificação.</small></main></body></html><?php
}
