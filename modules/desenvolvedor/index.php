<?php
require_once __DIR__ . '/../../includes/functions.php';
require_developer();
$page_title = 'Painel Desenvolvedor';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $empresaId = (int) input('empresa_id');
    $check = db()->prepare("SELECT id FROM empresas WHERE id = ?");
    $check->execute([$empresaId]);
    if ($check->fetchColumn()) {
        $_SESSION['active_empresa_id'] = $empresaId;
        flash('success', 'Contexto de empresa alterado com sucesso.');
    } else {
        flash('error', 'Empresa inválida para alternância.');
    }
    redirect(APP_URL . '/modules/desenvolvedor/index.php');
}

$totalEmpresas = (int) db()->query("SELECT COUNT(*) FROM empresas")->fetchColumn();
$totalAtivas = (int) db()->query("SELECT COUNT(*) FROM licencas WHERE status IN ('ativa','teste')")->fetchColumn();
$vencendo = (int) db()->query("SELECT COUNT(*) FROM licencas WHERE validade_fim IS NOT NULL AND validade_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
$totalBloqueadas = (int) db()->query("SELECT COUNT(*) FROM licencas WHERE status IN ('bloqueada','expirada','cancelada')")->fetchColumn();

$empresas = db()->query("SELECT e.id, e.razao_social, e.nome_fantasia, e.tema_padrao, e.dominio_principal,
    l.plano, l.status, l.validade_fim,
    (SELECT COUNT(*) FROM usuarios u WHERE u.empresa_id = e.id AND u.ativo = 1 AND u.perfil <> 'desenvolvedor') AS total_usuarios,
    (SELECT COUNT(*) FROM pacientes p WHERE p.empresa_id = e.id AND p.ativo = 1) AS total_pacientes
    FROM empresas e
    LEFT JOIN licencas l ON l.id = (SELECT ll.id FROM licencas ll WHERE ll.empresa_id = e.id ORDER BY ll.id DESC LIMIT 1)
    ORDER BY e.nome_fantasia, e.razao_social")->fetchAll();

$empresaAtivaId = active_company_id();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="space-y-6">
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-code-square text-primary"></i> Painel do Desenvolvedor</h1>
      <p class="text-sm text-slate-500">Gerencie o ambiente multiempresa, licenças e troque o contexto operacional da clínica.</p>
    </div>
    <div class="flex gap-2 flex-wrap">
      <a href="<?= APP_URL ?>/modules/desenvolvedor/especialidades.php" class="btn btn-primary"><i class="bi bi-toggles"></i> Gerenciar Especialidades</a>
      <a href="<?= APP_URL ?>/modules/licencas/index.php" class="btn btn-primary"><i class="bi bi-key-fill"></i> Gerenciar Licenças</a>
      <a href="<?= APP_URL ?>/modules/desenvolvedor/senha.php" class="btn bg-slate-100 text-slate-700"><i class="bi bi-lock"></i> Minha Senha</a>
    </div>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="card p-5"><div class="text-xs uppercase text-slate-500">Empresas</div><div class="text-3xl font-bold mt-1"><?= $totalEmpresas ?></div></div>
    <div class="card p-5"><div class="text-xs uppercase text-slate-500">Licenças ativas</div><div class="text-3xl font-bold mt-1 text-green-600"><?= $totalAtivas ?></div></div>
    <div class="card p-5"><div class="text-xs uppercase text-slate-500">Vencendo em 30 dias</div><div class="text-3xl font-bold mt-1 text-amber-600"><?= $vencendo ?></div></div>
    <div class="card p-5"><div class="text-xs uppercase text-slate-500">Bloqueadas/expiradas</div><div class="text-3xl font-bold mt-1 text-red-600"><?= $totalBloqueadas ?></div></div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="card p-6 xl:col-span-1">
      <h2 class="font-bold text-slate-800 mb-2">Empresa ativa no sistema</h2>
      <p class="text-sm text-slate-500 mb-4">Use este seletor para navegar pela aplicação no contexto da empresa desejada.</p>
      <form method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <div>
          <label class="text-sm font-medium">Selecionar empresa</label>
          <select name="empresa_id" class="form-select">
            <?php foreach ($empresas as $empresa): ?>
              <option value="<?= (int) $empresa['id'] ?>" <?= $empresaAtivaId === (int) $empresa['id'] ? 'selected' : '' ?>>
                <?= e($empresa['nome_fantasia'] ?: $empresa['razao_social']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary w-full"><i class="bi bi-arrow-repeat"></i> Ativar contexto</button>
      </form>
    </div>

    <div class="card overflow-hidden xl:col-span-2">
      <div class="px-6 py-4 border-b">
        <h2 class="font-bold text-slate-800">Empresas cadastradas</h2>
        <p class="text-sm text-slate-500">Acesso rápido para alternar o contexto e acompanhar o consumo de cada licença.</p>
      </div>
      <div class="overflow-x-auto">
        <table class="table-modern">
          <thead>
            <tr>
              <th>Empresa</th>
              <th>Tema</th>
              <th>Plano</th>
              <th>Licença</th>
              <th>Uso</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($empresas as $empresa): 
              $statusInfo = license_status_label($empresa['id']);
            ?>
              <tr>
                <td>
                  <div class="font-semibold"><?= e($empresa['nome_fantasia'] ?: $empresa['razao_social']) ?></div>
                  <div class="text-xs text-slate-500"><?= e($empresa['dominio_principal'] ?: 'Sem domínio') ?></div>
                </td>
                <td><span class="badge badge-info"><?= e($empresa['tema_padrao']) ?></span></td>
                <td><span class="badge badge-secondary"><?= e($empresa['plano'] ?: 'sem plano') ?></span></td>
                <td>
                  <span class="badge <?= e($statusInfo['class']) ?>"><?= e($statusInfo['label']) ?></span>
                  <div class="text-xs text-slate-500 mt-1">Validade: <?= $empresa['validade_fim'] ? format_date($empresa['validade_fim']) : 'Indeterminada' ?></div>
                </td>
                <td class="text-sm text-slate-600"><?= (int) $empresa['total_usuarios'] ?> usuários<br><?= (int) $empresa['total_pacientes'] ?> pacientes</td>
                <td class="text-right whitespace-nowrap">
                  <?php if ($empresaAtivaId !== (int) $empresa['id']): ?>
                    <form method="POST" class="inline-block mr-2">
                      <?= csrf_field() ?>
                      <input type="hidden" name="empresa_id" value="<?= (int) $empresa['id'] ?>">
                      <button class="text-sky-600"><i class="bi bi-box-arrow-in-right"></i></button>
                    </form>
                  <?php else: ?>
                    <span class="text-xs text-green-600 font-semibold mr-2">Ativa</span>
                  <?php endif; ?>
                  <a href="<?= APP_URL ?>/modules/desenvolvedor/especialidades.php?empresa_id=<?= (int) $empresa['id'] ?>" class="text-sky-600 mr-2">Especialidades</a>
                  <a href="<?= APP_URL ?>/modules/licencas/index.php?edit=<?= (int) $empresa['id'] ?>" class="text-amber-600"><i class="bi bi-pencil-square"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
