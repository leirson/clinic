<?php
function site_require_admin() {
    require_login();
    if (($_SESSION['perfil']??'')!=='admin') {http_response_code(403);exit('Página da Web: acesso exclusivo do administrador da clínica.');}
}
function site_default($eid) {
    $s=db()->prepare('SELECT nome_fantasia,razao_social,telefone,email,endereco_logradouro,endereco_numero,endereco_bairro,endereco_cidade,endereco_uf FROM empresas WHERE id=?');$s->execute([$eid]);$e=$s->fetch()?:[];
    return ['published'=>false,'entry_staff'=>true,'entry_patient'=>false,'slug'=>'clinica-'.(int)$eid,'name'=>$e['nome_fantasia']??$e['razao_social']??'Minha clínica','tagline'=>'Cuidado próximo, atendimento com atenção.','color'=>'#0e7490','background'=>'#f8fafc','layout'=>'cards','logo'=>'','hero_image'=>'','seo_title'=>'','seo_description'=>'','phone'=>$e['telefone']??'','whatsapp'=>'','email'=>$e['email']??'','address'=>trim(implode(', ',array_filter([$e['endereco_logradouro']??'',$e['endereco_numero']??'',$e['endereco_bairro']??'',$e['endereco_cidade']??'',$e['endereco_uf']??'']))),'instagram'=>'','hours'=>'Segunda a sexta, mediante agendamento.','cta_label'=>'Solicitar atendimento','cta_type'=>'portal','footer_text'=>'Todos os direitos reservados.','sections'=>[
      'hero'=>['enabled'=>true,'order'=>10,'title'=>'Saúde e bem-estar em cada atendimento','text'=>'Conheça nossa equipe e encontre o cuidado que você procura.'],
      'about'=>['enabled'=>true,'order'=>20,'title'=>'Sobre a clínica','text'=>'Personalize este espaço com a história, a proposta e os diferenciais da sua clínica.'],
      'specialties'=>['enabled'=>true,'order'=>30,'title'=>'Nossas especialidades','text'=>'Conheça nossas áreas de atendimento.'],
      'professionals'=>['enabled'=>true,'order'=>40,'title'=>'Nossa equipe','text'=>'Profissionais que cuidam de você.'],
      'reviews'=>['enabled'=>false,'order'=>50,'title'=>'Avaliações','text'=>'Depoimentos publicados com autorização.'],
      'contact'=>['enabled'=>true,'order'=>60,'title'=>'Fale com a clínica','text'=>'Entre em contato para informações e agendamentos.'],
      'footer'=>['enabled'=>true,'order'=>70,'title'=>'','text'=>'']
    ],'professionals'=>[],'specialties'=>[],'reviews'=>[]];
}
function site_read($eid) {
    $d=site_default($eid);$s=db()->prepare("SELECT valor FROM configuracoes WHERE empresa_id=? AND chave='website_config'");$s->execute([$eid]);$raw=$s->fetchColumn();
    if($raw!==false){$v=json_decode($raw,true);if(!is_array($v))throw new RuntimeException('Configuração da página inválida.');$d=array_replace_recursive($d,$v);}
    return $d;
}
function site_revision($eid) {
    $s=db()->prepare("SELECT valor FROM configuracoes WHERE empresa_id=? AND chave='website_config'");$s->execute([$eid]);return hash('sha256',(string)$s->fetchColumn());
}
function site_text($value,$limit,$required=false) {
    if(!is_scalar($value)&&$value!==null)throw new RuntimeException('Conteúdo inválido.');$value=trim((string)$value);
    if(mb_strlen($value)>$limit)throw new RuntimeException('Um texto excede o limite de '.$limit.' caracteres.');
    if($required&&$value==='')throw new RuntimeException('Preencha os campos obrigatórios.');return $value;
}
function site_image_name($name,$eid) {
    $name=site_text($name,80);if($name==='')return '';
    if(!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/',$name)||!is_file(APP_PATH.'/storage/site/'.(int)$eid.'/'.$name))throw new RuntimeException('Imagem inválida para esta empresa.');return $name;
}
function site_upload($field,$eid) {
    $f=$_FILES[$field]??null;if(!$f||($f['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return '';
    if(($f['error']??1)!==UPLOAD_ERR_OK||!is_uploaded_file($f['tmp_name'])||$f['size']>2*1024*1024)throw new RuntimeException('Imagem inválida ou maior que 2 MB.');
    $size=@getimagesize($f['tmp_name']);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!$size||!isset($types[$mime])||($size['mime']??'')!==$mime||$size[0]>5000||$size[1]>5000)throw new RuntimeException('Use PNG, JPEG ou WebP válidos, com até 5000 pixels por lado.');
    $dir=APP_PATH.'/storage/site/'.(int)$eid;if(!is_dir($dir)&&!mkdir($dir,0775,true))throw new RuntimeException('Não foi possível criar a pasta de imagens.');
    $name=bin2hex(random_bytes(16)).'.'.$types[$mime];if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Não foi possível salvar a imagem.');return $name;
}
function site_collect($eid,$old) {
    $out=site_default($eid);$out['published']=!empty($_POST['published']);
    $out['example_content']=!empty($old['example_content'])&&empty($_POST['reviewed_example_content']);
    // Legacy clients retain the professional entry unless explicitly submitting the new settings.
    $out['entry_staff']=isset($_POST['entry_settings'])?!empty($_POST['entry_staff']):($old['entry_staff']??true);
    $out['entry_patient']=isset($_POST['entry_settings'])?!empty($_POST['entry_patient']):($old['entry_patient']??false);
    if(!$out['published']&&!$out['entry_staff']&&!$out['entry_patient'])throw new RuntimeException('Selecione pelo menos um acesso para a página inicial desativada.');
    if($out['entry_patient']&&!portal_enabled($eid))throw new RuntimeException('Habilite a Área do Paciente em Empresa antes de exibir esse acesso.');
    $registered=site_registered_professionals($eid);
    foreach(['name'=>140,'tagline'=>220,'seo_title'=>160,'seo_description'=>300,'phone'=>30,'whatsapp'=>30,'email'=>150,'address'=>500,'hours'=>500,'cta_label'=>60,'footer_text'=>300] as $key=>$limit)$out[$key]=site_text($_POST[$key]??'',$limit,in_array($key,['name','cta_label'],true));
    $out['slug']=strtolower(site_text($_POST['slug']??'',80,true));if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$out['slug']))throw new RuntimeException('Endereço deve conter letras minúsculas sem acentos, números e hífens.');
    foreach(['color','background'] as $key){$out[$key]=site_text($_POST[$key]??'',7);if(!preg_match('/^#[a-fA-F0-9]{6}$/',$out[$key]))throw new RuntimeException('Cor inválida.');}
    foreach(['cta_type'=>['portal','whatsapp','phone','contact'],'layout'=>['cards','list']] as $k=>$choices){$out[$k]=site_text($_POST[$k]??'',20);if(!in_array($out[$k],$choices,true))throw new RuntimeException('Opção visual inválida.');}
    $out['instagram']=site_text($_POST['instagram']??'',250);
    if($out['instagram']!==''&&(!filter_var($out['instagram'],FILTER_VALIDATE_URL)||!preg_match('~^https://(?:www\.)?instagram\.com/[a-zA-Z0-9_.]+/?$~',$out['instagram'])))throw new RuntimeException('Use o endereço HTTPS do perfil no Instagram.');
    if($out['email']!==''&&!filter_var($out['email'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('E-mail inválido.');
    if($out['whatsapp']!==''&&!preg_match('/^(?:55)?[1-9][0-9]{9,10}$/',preg_replace('/\D/','',$out['whatsapp'])))throw new RuntimeException('WhatsApp deve incluir DDD.');
    foreach(['logo','hero_image'] as $key){$out[$key]=!empty($_POST['remove_'.$key])?'':site_image_name($old[$key]??'',$eid);$new=site_upload($key.'_upload',$eid);if($new)$out[$key]=$new;}
    $sections=$_POST['sections']??[];if(!is_array($sections))throw new RuntimeException('Seções inválidas.');
    foreach($out['sections'] as $key=>$base){$sec=$sections[$key]??[];if(!is_array($sec))throw new RuntimeException('Seção inválida.');$order=filter_var($sec['order']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>99]]);if($order===false)throw new RuntimeException('Ordem das seções deve ficar entre 1 e 99.');$out['sections'][$key]=['enabled'=>!empty($sec['enabled']),'order'=>$order,'title'=>site_text($sec['title']??'',160),'text'=>site_text($sec['text']??'',5000)];}
    foreach(['professionals'=>24,'specialties'=>30,'reviews'=>30] as $kind=>$max){
      $rows=$_POST[$kind]??[];if(!is_array($rows)||count($rows)>$max)throw new RuntimeException('Quantidade de cartões excedida.');$out[$kind]=[];
      foreach($rows as $i=>$row){if(!is_array($row)||!ctype_digit((string)$i))throw new RuntimeException('Cartão inválido.');
        $uid=filter_var($row['user_id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        if($kind==='professionals'&&($uid===false||($uid&&!isset($registered[$uid]))))throw new RuntimeException('Selecione um profissional ativo desta clínica com especialidade habilitada.');
        $name=site_text($row['name']??'',140);
        if($kind==='professionals'&&$uid&&$name==='')$name=$registered[$uid]['nome'];
        if($name==='')continue;
        $card=['name'=>$name,'enabled'=>!empty($row['enabled']),'text'=>site_text($row['text']??'',2000)];
        if($kind==='professionals'){$card['user_id']=(int)$uid;$card['role']=site_text($row['role']??'',160);$card['registration']=site_text($row['registration']??'',80);$card['image']=site_image_name($row['image']??'',$eid);if(!empty($row['remove_image']))$card['image']='';$img=site_upload('professional_'.$i,$eid);if($img)$card['image']=$img;}
        if($kind==='specialties'){$theme=site_text($row['theme']??'',60);if(!in_array($theme,allowed_theme_keys(),true))throw new RuntimeException('Especialidade inválida.');$card['theme']=$theme;}
        if($kind==='reviews'){$rating=filter_var($row['rating']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>5]]);if(!$rating)throw new RuntimeException('Nota deve ficar entre 1 e 5.');$card['rating']=$rating;$card['authorized']=!empty($row['authorized']);if($card['enabled']&&!$card['authorized'])throw new RuntimeException('Confirme autorização antes de publicar uma avaliação.');}
        if($kind==='professionals'&&$uid){
          if(in_array((int)$uid,array_column($out[$kind],'user_id'),true))throw new RuntimeException('Este profissional já foi adicionado.');
          if($card['role']==='')$card['role']=$registered[$uid]['especialidade']?:$registered[$uid]['theme_label'];
          if($card['registration']==='')$card['registration']=$registered[$uid]['registro_profissional']??'';
        }
        if($kind==='reviews')foreach(($old['reviews']??[]) as $previous){
          if(!empty($previous['example_demo'])&&$previous['name']===$card['name']&&$previous['text']===$card['text']){$card['example_demo']=true;$card['enabled']=false;$card['authorized']=false;break;}
        }
        $out[$kind][]=$card;
      }
    }
    if($out['published']&&!in_array(true,array_column($out['sections'],'enabled'),true))throw new RuntimeException('Habilite pelo menos uma seção antes de publicar.');
    return $out;
}
function site_save($eid,$data,$revision) {
    $s=db()->query("SELECT GET_LOCK('clinicapp_website_config',5)");if((int)$s->fetchColumn()!==1)throw new RuntimeException('Outro administrador está salvando. Tente novamente.');
    try{
      if(!hash_equals(site_revision($eid),(string)$revision))throw new RuntimeException('A página foi alterada em outra aba. Recarregue antes de salvar.');
      $s=db()->prepare("SELECT empresa_id FROM configuracoes WHERE chave='website_slug' AND valor=? AND empresa_id<>? LIMIT 1");$s->execute([$data['slug'],$eid]);if($s->fetchColumn())throw new RuntimeException('Este endereço já pertence a outra clínica. Escolha outro.');
      db()->beginTransaction();if(!app_config_set('website_config',json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$eid)||!app_config_set('website_slug',$data['slug'],$eid))throw new RuntimeException('Falha ao salvar a página.');log_action('pagina_web_salvar','empresas',$eid,$data['published']?'Página pública publicada/atualizada.':'Página da clínica salva como rascunho.');db()->commit();
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}finally{db()->query("SELECT RELEASE_LOCK('clinicapp_website_config')");}
}
function site_published() {
    $s=db()->query("SELECT e.id,e.nome_fantasia,e.razao_social,e.dominio_principal,c.valor FROM empresas e JOIN configuracoes c ON c.empresa_id=e.id AND c.chave='website_config' ORDER BY e.id");$out=[];
    foreach($s->fetchAll() as $row){$v=json_decode($row['valor'],true);if(is_array($v)&&!empty($v['published'])&&license_is_valid($row['id'])){$row['site']=$v;unset($row['valor']);$out[]=$row;}}
    return $out;
}
function site_find($slug){foreach(site_published() as $e)if(($e['site']['slug']??'')===$slug)return $e;return null;}
function site_media_url($s,$name,$preview=false){return APP_URL.'/site_media.php?clinica='.rawurlencode($s['slug']).'&img='.rawurlencode($name).($preview?'&preview=1':'');}
function site_visible_cards($s,$kind,$eid) {
    if($kind==='professionals'){
        $registered=site_registered_professionals($eid);
        return array_values(array_filter($s[$kind]??[],static function($r)use($registered){return !empty($r['enabled'])&&(empty($r['user_id'])||isset($registered[(int)$r['user_id']]));}));
    }
    return array_values(array_filter($s[$kind]??[],static function($r)use($kind,$eid){return !empty($r['enabled'])&&($kind!=='reviews'||(!empty($r['authorized'])&&empty($r['example_demo'])))&&($kind!=='specialties'||clinic_specialty_enabled($r['theme']??'', $eid));}));
}
function site_cta($s,$eid){
    if($s['cta_type']==='portal'&&function_exists('portal_enabled')&&portal_enabled($eid))return APP_URL.'/portal/index.php?empresa_id='.(int)$eid;
    if($s['cta_type']==='whatsapp'&&$s['whatsapp']){$n=preg_replace('/\D/','',$s['whatsapp']);if(strlen($n)<=11)$n='55'.$n;return 'https://wa.me/'.$n;}
    if($s['cta_type']==='phone'&&$s['phone'])return 'tel:'.preg_replace('/[^+0-9]/','',$s['phone']);
    return !empty($s['sections']['contact']['enabled'])?'#contact':'';
}

// Public-facing fields only. No email, phone, password, or private profile data.
function site_registered_professionals($eid) {
    $q=db()->prepare("SELECT id,nome,especialidade,registro_profissional,tema_preferido FROM usuarios WHERE empresa_id=? AND perfil='profissional' AND ativo=1 ORDER BY nome,id");
    $q->execute([(int)$eid]);$out=[];$catalog=clinic_theme_catalog();
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $p){$theme=normalize_theme_choice($p['tema_preferido']??'medico');
        if(!clinic_specialty_enabled($theme,$eid))continue;
        $p['theme_label']=$catalog[$theme]['nome']??$theme;$out[(int)$p['id']]=$p;
    }return $out;
}
function site_entry_companies() {
    $q=db()->query("SELECT e.id,e.nome_fantasia,e.razao_social,e.dominio_principal,c.valor FROM empresas e LEFT JOIN configuracoes c ON c.empresa_id=e.id AND c.chave='website_config' ORDER BY e.id");$out=[];
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $e){if(!license_is_valid($e['id']))continue;
        $e['configured']=$e['valor']!==null;$e['site']=site_read((int)$e['id']);unset($e['valor']);$out[]=$e;
    }return $out;
}
