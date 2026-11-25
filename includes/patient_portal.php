<?php
// Helpers do portal; não atribuem user_id/perfil nem usam o contexto de empresa da equipe.
function portal_ready() { return db_table_exists('portal_solicitacoes') && db_table_exists('portal_acesso_limites'); }
function portal_config($eid, $key, $default = '') {
    $s=db()->prepare('SELECT valor FROM configuracoes WHERE empresa_id=? AND chave=?');
    $s->execute([(int)$eid,$key]); $v=$s->fetchColumn(); return $v===false?$default:(string)$v;
}
function portal_enabled($eid) {
    return portal_ready() && portal_config($eid,'portal_paciente_enabled','0')==='1' && license_is_valid($eid);
}
function portal_companies() {
    if (!portal_ready()) return [];
    $s=db()->query("SELECT e.id,e.nome_fantasia,e.razao_social FROM empresas e JOIN configuracoes c ON c.empresa_id=e.id AND c.chave='portal_paciente_enabled' AND c.valor='1' ORDER BY e.nome_fantasia,e.razao_social");
    return array_values(array_filter($s->fetchAll(),static function($e){return license_is_valid($e['id']);}));
}
function portal_phone($v) {
    $v=preg_replace('/\D/','',(string)$v);
    if ((strlen($v)===12||strlen($v)===13) && substr($v,0,2)==='55') $v=substr($v,2);
    return in_array(strlen($v),[10,11],true)?$v:'';
}
function portal_fingerprint($p) {
    return hash('sha256',($p['cpf_cnpj']??'').'|'.($p['telefone']??'').'|'.($p['celular']??''));
}
function portal_rate($eid,$cpf) {
    $secret=portal_config($eid,'portal_paciente_secret');
    if (strlen($secret)<32) return false; // Configuração incompleta: falhar fechado.
    foreach (['ip:'.($_SERVER['REMOTE_ADDR']??'unknown')=>30,'cpf:'.$cpf=>8] as $raw=>$max) {
        $key=hash_hmac('sha256',$raw,$secret);
        $s=db()->prepare('INSERT INTO portal_acesso_limites(chave,tentativas,janela) VALUES(?,1,NOW()) ON DUPLICATE KEY UPDATE tentativas=IF(janela<DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,tentativas+1),janela=IF(janela<DATE_SUB(NOW(),INTERVAL 15 MINUTE),NOW(),janela)');
        $s->execute([$key]);
        $s=db()->prepare('SELECT tentativas FROM portal_acesso_limites WHERE chave=?');$s->execute([$key]);
        if ((int)$s->fetchColumn()>$max) return false;
    }
    if (random_int(1,100)===1) db()->exec('DELETE FROM portal_acesso_limites WHERE janela<DATE_SUB(NOW(),INTERVAL 1 DAY) LIMIT 500');
    return true;
}
function portal_authenticate($eid,$cpf,$phone) {
    $cpf=preg_replace('/\D/','',(string)$cpf); $phone=portal_phone($phone);
    if (!portal_enabled($eid) || !portal_rate($eid,$cpf)) return false;
    if (strlen($cpf)!==11 || !$phone) return false;
    // Normalização também funciona em bancos MySQL sem REGEXP_REPLACE.
    $expr="REPLACE(REPLACE(REPLACE(REPLACE(cpf_cnpj,'.',''),'-',''),' ',''),'/','')";
    $s=db()->prepare("SELECT id,empresa_id,cpf_cnpj,telefone,celular FROM pacientes WHERE empresa_id=? AND ativo=1 AND tipo_pessoa='F' AND $expr=? LIMIT 3");
    $s->execute([(int)$eid,$cpf]);$rows=$s->fetchAll();
    // CPF duplicado é ambíguo: solicitar regularização pela recepção, nunca escolher arbitrariamente.
    if (count($rows)!==1) return false;
    $p=$rows[0];
    if (!hash_equals(portal_phone($p['telefone']),$phone) && !hash_equals(portal_phone($p['celular']),$phone)) return false;
    session_regenerate_id(true);
    $_SESSION['patient_portal']=['id'=>(int)$p['id'],'empresa_id'=>(int)$eid,'fingerprint'=>portal_fingerprint($p),'last'=>time(),'created'=>time()];
    unset($_SESSION[CSRF_TOKEN_NAME]);
    return true;
}
function portal_patient() {
    $a=$_SESSION['patient_portal']??[];
    if (!$a || time()-($a['last']??0)>1200 || time()-($a['created']??0)>7200 || !portal_enabled((int)($a['empresa_id']??0))) {
        unset($_SESSION['patient_portal']);return null;
    }
    $s=db()->prepare('SELECT id,empresa_id,nome,cpf_cnpj,telefone,celular FROM pacientes WHERE id=? AND empresa_id=? AND ativo=1');
    $s->execute([$a['id'],$a['empresa_id']]);$p=$s->fetch();
    if (!$p || !hash_equals($a['fingerprint'],portal_fingerprint($p))) {unset($_SESSION['patient_portal']);return null;}
    $_SESSION['patient_portal']['last']=time();return $p;
}
function portal_professionals($eid,$theme) {
    if (!in_array($theme,clinic_enabled_specialties($eid),true)) return [];
    $s=db()->prepare("SELECT id,nome,especialidade FROM usuarios WHERE empresa_id=? AND ativo=1 AND perfil IN ('profissional','admin') AND especialidade IS NOT NULL AND especialidade<>'' ORDER BY nome");
    $s->execute([(int)$eid]);
    return array_values(array_filter($s->fetchAll(),static function($u)use($theme){return specialty_theme($u['especialidade'])===$theme;}));
}
function portal_hours($eid) {
    $days=json_decode(portal_config($eid,'portal_paciente_dias','[1,2,3,4,5]'),true);
    $start=portal_config($eid,'portal_paciente_inicio','08:00');$end=portal_config($eid,'portal_paciente_fim','18:00');
    $duration=(int)portal_config($eid,'portal_paciente_duracao','30');
    if (!is_array($days) || !preg_match('/^\d{2}:\d{2}$/',$start) || !preg_match('/^\d{2}:\d{2}$/',$end) || $end<=$start || !in_array($duration,[15,20,30,40,60],true)) throw new RuntimeException('Horários do portal não configurados corretamente.');
    return [$days,$start,$end,$duration];
}
function portal_slots($eid,$theme,$uid,$date) {
    if (!portal_enabled($eid)) return [];
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',(string)$date);
    if (!$d || $d->format('Y-m-d')!==$date || $date<date('Y-m-d') || $date>date('Y-m-d',strtotime('+90 days'))) return [];
    $profs=portal_professionals($eid,$theme);
    if (!in_array((int)$uid,array_map('intval',array_column($profs,'id')),true)) return [];
    [$days,$start,$end,$duration]=portal_hours($eid);
    if (!in_array((int)$d->format('N'),array_map('intval',$days),true)) return [];
    $s=db()->prepare("SELECT data_hora_inicio AS ini,data_hora_fim AS fim FROM agendamentos WHERE empresa_id=? AND profissional_id=? AND status NOT IN ('cancelado','faltou') AND data_hora_inicio<? AND data_hora_fim>? UNION ALL SELECT data_inicio,data_fim FROM bloqueios_horario WHERE empresa_id=? AND (profissional_id=? OR profissional_id IS NULL) AND data_inicio<? AND data_fim>?");
    $s->execute([$eid,$uid,$date.' 23:59:59',$date.' 00:00:00',$eid,$uid,$date.' 23:59:59',$date.' 00:00:00']);$busy=$s->fetchAll();
    $slots=[];$limit=strtotime($date.' '.$end);
    for($t=strtotime($date.' '.$start);$t+$duration*60<=$limit;$t+=$duration*60){
        $finish=$t+$duration*60;if($t<time()+3600) continue;$ok=true;
        foreach($busy as $b)if(strtotime($b['ini'])<$finish && strtotime($b['fim'])>$t){$ok=false;break;}
        if($ok)$slots[]=date('H:i',$t);
    }
    return $slots;
}
function portal_request($p,$theme,$uid,$date,$time) {
    $eid=(int)$p['empresa_id'];
    if (!in_array($time,portal_slots($eid,$theme,$uid,$date),true)) throw new RuntimeException('Horário indisponível. Atualize a consulta de horários.');
    db()->beginTransaction();
    try{
        $s=db()->prepare('SELECT id FROM pacientes WHERE id=? AND empresa_id=? AND ativo=1 FOR UPDATE');$s->execute([$p['id'],$eid]);
        if(!$s->fetchColumn())throw new RuntimeException('Cadastro indisponível.');
        $s=db()->prepare("SELECT COUNT(*) FROM portal_solicitacoes WHERE empresa_id=? AND paciente_id=? AND status='pendente'");$s->execute([$eid,$p['id']]);
        if((int)$s->fetchColumn()>=5)throw new RuntimeException('Você já possui 5 solicitações pendentes. Aguarde a clínica.');
        [$days,$start,$end,$duration]=portal_hours($eid);$ini=$date.' '.$time.':00';$fim=date('Y-m-d H:i:s',strtotime($ini)+$duration*60);
        $s=db()->prepare("SELECT COUNT(*) FROM portal_solicitacoes WHERE empresa_id=? AND paciente_id=? AND status='pendente' AND data_inicio<? AND data_fim>?");$s->execute([$eid,$p['id'],$fim,$ini]);
        if($s->fetchColumn())throw new RuntimeException('Já existe uma solicitação sua nesse horário.');
        $s=db()->prepare('INSERT INTO portal_solicitacoes(empresa_id,paciente_id,profissional_id,especialidade,data_inicio,data_fim) VALUES(?,?,?,?,?,?)');$s->execute([$eid,$p['id'],$uid,$theme,$ini,$fim]);
        db()->commit();
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
function portal_review($eid,$id,$action) {
    if(!in_array($action,['confirmar','recusar'],true))throw new RuntimeException('Ação inválida.');
    db()->beginTransaction();
    try{
        $s=db()->prepare('SELECT * FROM portal_solicitacoes WHERE id=? AND empresa_id=? FOR UPDATE');$s->execute([$id,$eid]);$r=$s->fetch();
        if(!$r || $r['status']!=='pendente')throw new RuntimeException('Solicitação indisponível ou já analisada.');
        $agid=null;
        if($action==='confirmar'){
            $s=db()->prepare('SELECT id FROM usuarios WHERE id=? AND empresa_id=? FOR UPDATE');$s->execute([$r['profissional_id'],$eid]);
            $lockPatient=db()->prepare('SELECT id FROM pacientes WHERE id=? AND empresa_id=? FOR UPDATE');$lockPatient->execute([$r['paciente_id'],$eid]);
            $patient=clinic_company_record('pacientes',$r['paciente_id']);
            if(!(int)$patient['ativo'])throw new RuntimeException('Paciente inativo.');
            $hours=portal_hours($eid);
            if(strtotime($r['data_fim'])-strtotime($r['data_inicio'])!==$hours[3]*60)throw new RuntimeException('A duração do expediente mudou. Recuse este pedido e solicite uma nova escolha de horário.');
            if(!in_array(substr($r['data_inicio'],11,5),portal_slots($eid,$r['especialidade'],$r['profissional_id'],substr($r['data_inicio'],0,10)),true))throw new RuntimeException('Horário não está mais disponível ou a especialidade foi desabilitada.');
            $s=db()->prepare("SELECT COUNT(*) FROM agendamentos WHERE empresa_id=? AND paciente_id=? AND status NOT IN ('cancelado','faltou') AND data_hora_inicio<? AND data_hora_fim>?");$s->execute([$eid,$r['paciente_id'],$r['data_fim'],$r['data_inicio']]);
            if($s->fetchColumn())throw new RuntimeException('Paciente já possui outro agendamento neste horário.');
            $s=db()->prepare("INSERT INTO agendamentos(empresa_id,paciente_id,profissional_id,data_hora_inicio,data_hora_fim,status,confirmacao_status,observacoes,criado_por) VALUES(?,?,?,?,?,'agendado','pendente','Solicitação recebida pelo portal do paciente.',?)");
            $s->execute([$eid,$r['paciente_id'],$r['profissional_id'],$r['data_inicio'],$r['data_fim'],$_SESSION['user_id']]);$agid=(int)db()->lastInsertId();
        }
        $s=db()->prepare('UPDATE portal_solicitacoes SET status=?,agendamento_id=? WHERE id=? AND empresa_id=?');$s->execute([$action==='confirmar'?'confirmada':'recusada',$agid,$id,$eid]);
        log_action('portal_'.$action,'portal_solicitacoes',$id);db()->commit();
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
