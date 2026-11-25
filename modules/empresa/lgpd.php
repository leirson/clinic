<?php
$page_title = 'LGPD & Auditoria';
require_once __DIR__ . '/../../includes/header.php';
require_admin();

$eid = active_company_id();
$inicio = input('inicio', date('Y-m-d', strtotime('-30 days')));
$fim = input('fim', date('Y-m-d'));
$usuarioFiltro = (int) input('usuario_id');
$acaoFiltro = trim((string) input('acao'));
$inicioSql = $inicio . ' 00:00:00';
$fimSql = $fim . ' 23:59:59';
$hasConsentimentos = db_table_exists('paciente_consentimentos');

$usuariosStmt = db()->prepare("SELECT id, nome, email, perfil, especialidade, ultimo_login, ativo
  FROM usuarios
  WHERE empresa_id=?
  ORDER BY FIELD(perfil,'admin','profissional','recepcao','desenvolvedor'), nome");
$usuariosStmt->execute([$eid]);
$usuarios = $usuariosStmt->fetchAll() ?: [];
$userIds = array_map(static fn($u) => (int) $u['id'], $usuarios);

$acoesDisponiveis = [
    'login' => 'Login',
    'login_2fa' => 'Login 2FA',
    'acesso_pagina' => 'Acesso de página',
    'acesso_paciente' => 'Acesso à ficha do paciente',
    'acesso_prontuario' => 'Acesso ao prontuário',
    'acesso_agendamento' => 'Acesso ao agendamento',
    'lgpd_exportar_paciente' => 'Exportação LGPD',
    'lgpd_registrar_consentimento' => 'Registro de consentimento',
    'lgpd_revogar_consentimento' => 'Revogação de consentimento',
    'usuarios_criar' => 'Criação de usuário',
    'usuarios_editar' => 'Edição de usuário',
    'agenda_status' => 'Mudança de status da agenda',
    'agenda_encaixe_fila' => 'Encaixe via fila',
    'agenda_reaproveitar_cancelamento' => 'Reaproveitamento de cancelamento',
    'agenda_lembrete_email' => 'Lembrete automático',
    'agenda_lembrete_manual' => 'Lembrete manual',
    'criar' => 'Criação de registro',
    'editar' => 'Edição de registro',
];

$cards = [
    'acessos_paginas' => 0,
    'acessos_sensiveis' => 0,
    'logins' => 0,
    'eventos_lgpd' => 0,
    'consentimentos_ativos' => 0,
    'consentimentos_vencendo' => 0,
];
$trilhaCritica = [];
$hotspots = [];
$acessosSensiveis = [];
$historicoExportacoes = [];
$consentimentos = [];
$consentimentosVencendo = [];

$perfilResumo = [];
foreach ($usuarios as $usuario) {
    $perfil = (string) ($usuario['perfil'] ?? 'indefinido');
    if (!isset($perfilResumo[$perfil])) {
        $perfilResumo[$perfil] = ['total' => 0, 'ativos' => 0, 'ultimo_login' => null];
    }
    $perfilResumo[$perfil]['total']++;
    if (!empty($usuario['ativo'])) {
        $perfilResumo[$perfil]['ativos']++;
    }
    if (!empty($usuario['ultimo_login']) && ($perfilResumo[$perfil]['ultimo_login'] === null || strcmp((string) $usuario['ultimo_login'], (string) $perfilResumo[$perfil]['ultimo_login']) > 0)) {
        $perfilResumo[$perfil]['ultimo_login'] = $usuario['ultimo_login'];
    }
}

if ($userIds) {
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));

    $stmt = db()->prepare("SELECT COUNT(*)
      FROM logs
      WHERE usuario_id IN ({$placeholders}) AND acao='acesso_pagina' AND criado_em BETWEEN ? AND ?");
    $stmt->execute(array_merge($userIds, [$inicioSql, $fimSql]));
    $cards['acessos_paginas'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(*)
      FROM logs
      WHERE usuario_id IN ({$placeholders}) AND acao IN ('acesso_paciente','acesso_prontuario','acesso_agendamento') AND criado_em BETWEEN ? AND ?");
    $stmt->execute(array_merge($userIds, [$inicioSql, $fimSql]));
    $cards['acessos_sensiveis'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(*)
      FROM logs
      WHERE usuario_id IN ({$placeholders}) AND acao IN ('login','login_2fa') AND criado_em BETWEEN ? AND ?");
    $stmt->execute(array_merge($userIds, [$inicioSql, $fimSql]));
    $cards['logins'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(*)
      FROM logs
      WHERE usuario_id IN ({$placeholders}) AND acao IN ('lgpd_exportar_paciente','lgpd_registrar_consentimento','lgpd_revogar_consentimento') AND criado_em BETWEEN ? AND ?");
    $stmt->execute(array_merge($userIds, [$inicioSql, $fimSql]));
    $cards['eventos_lgpd'] = (int) $stmt->fetchColumn();

    $filtrosSql = ["u.empresa_id = ?", "l.criado_em BETWEEN ? AND ?"];
    $filtrosParams = [$eid, $inicioSql, $fimSql];
    if ($usuarioFiltro > 0) {
        $filtrosSql[] = 'l.usuario_id = ?';
        $filtrosParams[] = $usuarioFiltro;
    }
    if ($acaoFiltro !== '') {
        $filtrosSql[] = 'l.acao = ?';
        $filtrosParams[] = $acaoFiltro;
    } else {
        $criticalActions = array_keys($acoesDisponiveis);
        $criticalPlaceholders = implode(',', array_fill(0, count($criticalActions), '?'));
        $filtrosSql[] = "l.acao IN ({$criticalPlaceholders})";
        $filtrosParams = array_merge($filtrosParams, $criticalActions);
    }

    $trailSql = "SELECT l.*, u.nome AS usuario_nome, u.perfil AS usuario_perfil
        FROM logs l
        JOIN usuarios u ON u.id = l.usuario_id
        WHERE " . implode(' AND ', $filtrosSql) . "
        ORDER BY l.criado_em DESC
        LIMIT 150";
    $stmt = db()->prepare($trailSql);
    $stmt->execute($filtrosParams);
    $trilhaCritica = $stmt->fetchAll() ?: [];

    $stmt = db()->prepare("SELECT l.descricao AS rota, COUNT(*) AS qtd
      FROM logs l
      JOIN usuarios u ON u.id = l.usuario_id
      WHERE u.empresa_id=? AND l.acao='acesso_pagina' AND l.criado_em BETWEEN ? AND ?
      GROUP BY l.descricao
      ORDER BY qtd DESC, rota ASC
      LIMIT 8");
    $stmt->execute([$eid, $inicioSql, $fimSql]);
    $hotspots = $stmt->fetchAll() ?: [];

    $stmt = db()->prepare("SELECT l.acao, COUNT(*) AS qtd
      FROM logs l
      JOIN usuarios u ON u.id = l.usuario_id
      WHERE u.empresa_id=? AND l.acao IN ('acesso_paciente','acesso_prontuario','acesso_agendamento') AND l.criado_em BETWEEN ? AND ?
      GROUP BY l.acao
      ORDER BY qtd DESC");
    $stmt->execute([$eid, $inicioSql, $fimSql]);
    $acessosSensiveis = $stmt->fetchAll() ?: [];

    $stmt = db()->prepare("SELECT l.*, u.nome AS usuario_nome
      FROM logs l
      JOIN usuarios u ON u.id = l.usuario_id
      WHERE u.empresa_id=? AND l.acao='lgpd_exportar_paciente' AND l.criado_em BETWEEN ? AND ?
      ORDER BY l.criado_em DESC
      LIMIT 20");
    $stmt->execute([$eid, $inicioSql, $fimSql]);
    $historicoExportacoes = $stmt->fetchAll() ?: [];
}

if ($hasConsentimentos) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM paciente_consentimentos WHERE empresa_id=? AND status='ativo'");
    $stmt->execute([$eid]);
    $cards['consentimentos_ativos'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(*) FROM paciente_consentimentos WHERE empresa_id=? AND status='ativo' AND validade_ate IS NOT NULL AND validade_ate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute([$eid]);
    $cards['consentimentos_vencendo'] = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT pc.*, p.nome AS paciente_nome, u.nome AS autor_nome
      FROM paciente_consentimentos pc
      JOIN pacientes p ON p.id = pc.paciente_id
      LEFT JOIN usuarios u ON u.id = pc.criado_por
      WHERE pc.empresa_id=?
      ORDER BY COALESCE(pc.aceito_em, pc.criado_em) DESC
      LIMIT 50");
    $stmt->execute([$eid]);
    $consentimentos = $stmt->fetchAll() ?: [];

    $stmt = db()->prepare("SELECT pc.*, p.nome AS paciente_nome
      FROM paciente_consentimentos pc
      JOIN pacientes p ON p.id = pc.paciente_id
      WHERE pc.empresa_id=? AND pc.status='ativo' AND pc.validade_ate IS NOT NULL AND pc.validade_ate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
      ORDER BY pc.validade_ate ASC
      LIMIT 20");
    $stmt->execute([$eid]);
    $consentimentosVencendo = $stmt->fetchAll() ?: [];
}
?>
<div class="space-y-5">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <a href="index.php" class="text-sm text-slate-500 hover:text-primary"><i class="bi bi-arrow-left"></i> Voltar para Empresa</a>
      <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-shield-lock text-primary"></i> LGPD & Auditoria</h1>
      <p class="text-sm text-slate-500">Logs de acesso, consentimentos, exportações, perfis e trilha crítica para governança clínica.</p>
    </div>
  </div>

  <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
    <div>
      <label class="text-xs text-slate-500">Início</label>
      <input type="date" name="inicio" value="<?= e($inicio) ?>" class="form-input">
    </div>
    <div>
      <label class="text-xs text-slate-500">Fim</label>
      <input type="date" name="fim" value="<?= e($fim) ?>" class="form-input">
    </div>
    <div>
      <label class="text-xs text-slate-500">Usuário</label>
      <select name="usuario_id" class="form-select">
        <option value="0">Todos</option>
        <?php foreach ($usuarios as $usuario): ?>
          <option value="<?= (int) $usuario['id'] ?>" <?= $usuarioFiltro === (int) $usuario['id'] ? 'selected' : '' ?>><?= e($usuario['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-xs text-slate-500">Ação</label>
      <select name="acao" class="form-select">
        <option value="">Trilha crítica padrão</option>
        <?php foreach ($acoesDisponiveis as $acao => $label): ?>
          <option value="<?= e($acao) ?>" <?= $acaoFiltro === $acao ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex gap-2">
      <button class="btn btn-primary flex-1"><i class="bi bi-funnel"></i> Filtrar</button>
      <a href="lgpd.php" class="btn bg-slate-100 text-slate-700">Limpar</a>
    </div>
  </form>

  <div class="grid grid-cols-2 xl:grid-cols-6 gap-4">
    <div class="card p-4"><div class="text-xs uppercase text-slate-500">Acessos de página</div><div class="text-2xl font-bold text-sky-600"><?= $cards['acessos_paginas'] ?></div><div class="text-xs text-slate-400">Rotas navegadas no período</div></div>
    <div class="card p-4"><div class="text-xs uppercase text-slate-500">Acessos sensíveis</div><div class="text-2xl font-bold text-violet-600"><?= $cards['acessos_sensiveis'] ?></div><div class="text-xs text-slate-400">Pacientes, prontuários e agenda</div></div>
    <div class="card p-4"><div class="text-xs uppercase text-slate-500">Logins</div><div class="text-2xl font-bold text-emerald-600"><?= $cards['logins'] ?></div><div class="text-xs text-slate-400">Login simples e 2FA</div></div>
    <div class="card p-4"><div class="text-xs uppercase text-slate-500">Eventos LGPD</div><div class="text-2xl font-bold text-amber-600"><?= $cards['eventos_lgpd'] ?></div><div class="text-xs text-slate-400">Consentimento e exportação</div></div>
    <div class="card p-4"><div class="text-xs uppercase text-slate-500">Consentimentos ativos</div><div class="text-2xl font-bold text-rose-600"><?= $cards['consentimentos_ativos'] ?></div><div class="text-xs text-slate-400">Bases vigentes registradas</div></div>
    <div class="card p-4"><div class="text-xs uppercase text-slate-500">Vencendo em 30 dias</div><div class="text-2xl font-bold text-fuchsia-600"><?= $cards['consentimentos_vencendo'] ?></div><div class="text-xs text-slate-400">Revisão operacional pendente</div></div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-person-badge"></i> Perfis & acessos</h2>
      <div class="space-y-3 mb-5">
        <?php foreach ($perfilResumo as $perfil => $dados): ?>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
            <div class="flex items-center justify-between gap-2">
              <div class="font-semibold text-slate-800"><?= e(ucfirst($perfil)) ?></div>
              <span class="badge badge-info"><?= (int) $dados['ativos'] ?>/<?= (int) $dados['total'] ?> ativos</span>
            </div>
            <div class="text-xs text-slate-500 mt-1">Último login do perfil: <?= !empty($dados['ultimo_login']) ? format_date($dados['ultimo_login'], true) : '—' ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="space-y-3">
        <?php foreach ($usuarios as $usuario): ?>
          <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm">
            <div class="flex items-center justify-between gap-2">
              <div class="font-semibold text-slate-800"><?= e($usuario['nome']) ?></div>
              <span class="badge <?= $usuario['ativo'] ? 'badge-success' : 'badge-danger' ?>"><?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?></span>
            </div>
            <div class="text-slate-500 mt-1"><?= e($usuario['email']) ?></div>
            <div class="text-xs text-slate-500 mt-1">Perfil: <strong><?= e($usuario['perfil']) ?></strong><?= !empty($usuario['especialidade']) ? ' · ' . e($usuario['especialidade']) : '' ?></div>
            <div class="text-xs text-slate-400 mt-1">Último login: <?= !empty($usuario['ultimo_login']) ? format_date($usuario['ultimo_login'], true) : '—' ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="xl:col-span-2 card p-5">
      <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-list-check"></i> Trilha crítica</h2>
      <?php if (!$trilhaCritica): ?>
        <p class="text-sm text-slate-400">Nenhum evento registrado no período.</p>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="table-modern">
            <thead><tr><th>Data/Hora</th><th>Usuário</th><th>Perfil</th><th>Ação</th><th>Entidade</th><th>Descrição</th><th>IP</th></tr></thead>
            <tbody>
              <?php foreach ($trilhaCritica as $log): ?>
                <tr>
                  <td><?= format_date($log['criado_em'], true) ?></td>
                  <td><?= e($log['usuario_nome']) ?></td>
                  <td><span class="badge badge-info"><?= e($log['usuario_perfil']) ?></span></td>
                  <td><span class="badge <?= str_contains((string) $log['acao'], 'lgpd') ? 'badge-warning' : 'badge-secondary' ?>"><?= e($log['acao']) ?></span></td>
                  <td><?= e($log['entidade'] ?: '—') ?><?= !empty($log['entidade_id']) ? ' #' . (int) $log['entidade_id'] : '' ?></td>
                  <td><?= e(mb_substr((string) ($log['descricao'] ?? ''), 0, 180)) ?></td>
                  <td><?= e($log['ip']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-compass"></i> Hotspots de navegação</h2>
      <?php if (!$hotspots): ?>
        <p class="text-sm text-slate-400">Sem dados para o período selecionado.</p>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($hotspots as $item): ?>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <div class="flex items-center justify-between gap-3">
                <div class="text-sm text-slate-700 break-all"><?= e($item['rota'] ?: 'rota não identificada') ?></div>
                <div class="font-semibold text-sky-700"><?= (int) $item['qtd'] ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-eye"></i> Acessos sensíveis</h2>
      <?php if (!$acessosSensiveis): ?>
        <p class="text-sm text-slate-400">Nenhum acesso sensível registrado.</p>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($acessosSensiveis as $item): ?>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 flex items-center justify-between gap-3">
              <div class="text-sm font-medium text-slate-700"><?= e($acoesDisponiveis[$item['acao']] ?? $item['acao']) ?></div>
              <span class="badge badge-info"><?= (int) $item['qtd'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card p-5">
      <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-download"></i> Exportações LGPD</h2>
      <?php if (!$historicoExportacoes): ?>
        <p class="text-sm text-slate-400">Nenhuma exportação registrada.</p>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($historicoExportacoes as $evento): ?>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
              <div class="font-semibold text-slate-800"><?= e($evento['usuario_nome']) ?></div>
              <div class="text-xs text-slate-500 mt-1"><?= format_date($evento['criado_em'], true) ?> · <?= e($evento['ip']) ?></div>
              <div class="text-xs text-slate-500 mt-1"><?= e($evento['descricao'] ?: 'Exportação LGPD do paciente.') ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($hasConsentimentos): ?>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
      <div class="card p-5 xl:col-span-2">
        <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-shield-check"></i> Consentimentos recentes</h2>
        <?php if (!$consentimentos): ?>
          <p class="text-sm text-slate-400">Nenhum consentimento registrado até o momento.</p>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="table-modern">
              <thead><tr><th>Paciente</th><th>Tipo</th><th>Finalidade</th><th>Status</th><th>Canal</th><th>Validade</th><th>Responsável</th></tr></thead>
              <tbody>
                <?php foreach ($consentimentos as $consentimento): ?>
                  <tr>
                    <td><a href="<?= APP_URL ?>/modules/pacientes/ver.php?id=<?= (int) $consentimento['paciente_id'] ?>#consentimentos" class="text-primary hover:underline font-medium"><?= e($consentimento['paciente_nome']) ?></a></td>
                    <td><?= e($consentimento['tipo']) ?></td>
                    <td><?= e(mb_substr((string) $consentimento['finalidade'], 0, 100)) ?></td>
                    <td><span class="badge <?= status_badge_class($consentimento['status']) ?>"><?= e($consentimento['status']) ?></span></td>
                    <td><?= e($consentimento['canal'] ?: '—') ?></td>
                    <td><?= !empty($consentimento['validade_ate']) ? format_date($consentimento['validade_ate']) : '—' ?></td>
                    <td><?= e($consentimento['autor_nome'] ?: '—') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="card p-5">
        <h2 class="font-bold text-slate-800 mb-4"><i class="bi bi-alarm"></i> Consentimentos vencendo</h2>
        <?php if (!$consentimentosVencendo): ?>
          <p class="text-sm text-slate-400">Nenhum consentimento expira nos próximos 30 dias.</p>
        <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($consentimentosVencendo as $item): ?>
              <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm">
                <div class="font-semibold text-amber-900"><?= e($item['paciente_nome']) ?></div>
                <div class="text-amber-800 mt-1"><?= e(ucfirst($item['tipo'])) ?> · <?= e($item['finalidade']) ?></div>
                <div class="text-xs text-amber-700 mt-1">Validade até <?= format_date($item['validade_ate']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>