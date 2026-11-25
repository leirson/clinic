<?php
require_once __DIR__ . '/functions.php';
require_login();
$user = current_user();
$user = is_array($user) ? $user : [];
$empresa = current_empresa();
$empresa = is_array($empresa) ? $empresa : [];
$tema = theme_config();
$tema = is_array($tema) ? $tema : [];
$tema = array_merge(specialty_theme_meta('medico'), $tema);
$currentTheme = normalize_theme_choice(get_theme());
$currentPath = str_replace('\\', '/', (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
$isThemesPage = strpos($currentPath, '/modules/temas/') !== false;
$page_title = $page_title ?? 'ClinicApp';
$isDev = is_developer();
$licStatus = license_status_label();
$userNome = trim((string) ($user['nome'] ?? 'Usuário'));
$userEmail = trim((string) ($user['email'] ?? ''));
$userPerfil = trim((string) ($user['perfil'] ?? ($_SESSION['perfil'] ?? 'usuario')));
$userInicial = strtoupper(mb_substr($userNome !== '' ? $userNome : 'U', 0, 1));
audit_page_access();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?> - <?= APP_NAME ?></title>
<link rel="manifest" href="<?= APP_URL ?>/manifest.json">
<meta name="theme-color" content="<?= $tema['cor_primaria'] ?>">
<link rel="icon" type="image/png" href="<?= APP_URL ?>/assets/img/icon-192.png">
<link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/img/icon-192.png">
<?= clinic_asset_styles(['resources/entries/app.js']) ?>
<style>
:root {
  --cor-primaria: <?= e($tema['accent_primary'] ?? $tema['cor_primaria']) ?>;
  --cor-secundaria: <?= e($tema['accent_secondary'] ?? $tema['cor_secundaria']) ?>;
  --cor-primaria-rgb: <?= e(clinic_hex_to_rgb_channels($tema['accent_primary'] ?? $tema['cor_primaria'], '14 165 233')) ?>;
  --cor-secundaria-rgb: <?= e(clinic_hex_to_rgb_channels($tema['accent_secondary'] ?? $tema['cor_secundaria'], '29 78 216')) ?>;
  --tema-primaria: <?= e($tema['cor_primaria']) ?>;
  --tema-secundaria: <?= e($tema['cor_secundaria']) ?>;
}
</style>
</head>
<body class="bg-slate-50 min-h-screen">
<div class="flex min-h-screen">
  <aside id="sidebar" class="sidebar bg-gradient-to-b <?= $tema['gradient_from'] ?> <?= $tema['gradient_to'] ?> text-white w-64 fixed h-full overflow-y-auto z-40 transition-transform shadow-xl">
    <div class="p-5 border-b border-white/10">
      <div class="flex items-center gap-3">
        <div class="text-3xl"><?= $tema['icone'] ?></div>
        <div>
          <div class="font-bold text-lg leading-tight"><?= APP_NAME ?></div>
          <div class="text-xs opacity-80"><?= e($tema['nome']) ?></div>
          <?php if (!empty($empresa['nome_fantasia']) || !empty($empresa['razao_social'])): ?>
            <div class="text-[11px] opacity-70 mt-1 truncate"><?= e($empresa['nome_fantasia'] ?: $empresa['razao_social']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <nav class="p-3 space-y-1 text-sm">
      <a href="<?= APP_URL ?>/dashboard.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
        <i class="bi bi-grid-1x2-fill text-lg"></i> Dashboard
      </a>
      <a href="<?= APP_URL ?>/modules/pacientes/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
        <i class="bi bi-people-fill text-lg"></i> Pacientes
      </a>
      <a href="<?= APP_URL ?>/modules/agenda/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
        <i class="bi bi-calendar-week-fill text-lg"></i> Agenda
      </a>
      <a href="<?= APP_URL ?>/modules/prontuario/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
        <i class="bi bi-journal-medical text-lg"></i> Prontuário
      </a>

      <?php if ($isDev): ?>
        <div class="px-3 text-xs uppercase opacity-60 mt-2 mb-1">Acesso completo</div>
        <a href="<?= APP_URL ?>/modules/exames/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-clipboard2-pulse text-lg"></i> Solicitação de Exames
        </a>
        <a href="<?= APP_URL ?>/modules/telemedicina/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-camera-video text-lg"></i> Telemedicina
        </a>
        <?php if (clinic_specialty_enabled('dentista')): ?>
        <a href="<?= APP_URL ?>/modules/prontuario/odontograma.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-emoji-smile text-lg"></i> Odontograma
        </a>
        <a href="<?= APP_URL ?>/modules/planos/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-clipboard2-check text-lg"></i> Planos de Tratamento
        </a>
        <?php endif; if (clinic_specialty_enabled('psicologia')): ?>
        <a href="<?= APP_URL ?>/modules/psicologia/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-flower1 text-lg"></i> Psicologia
        </a>
        <a href="<?= APP_URL ?>/modules/psicologia/escalas.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-clipboard-data text-lg"></i> Escalas Clínicas
        </a>
        <a href="<?= APP_URL ?>/modules/psicologia/planos.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-bullseye text-lg"></i> Plano Terapêutico
        </a>
        <?php endif; ?>
        <?php foreach (custom_specialty_themes() as $devTheme): if (!clinic_specialty_enabled($devTheme)) continue; $devMeta = specialty_theme_meta($devTheme); ?>
          <a href="<?= specialty_module_url($devTheme) ?>" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
            <i class="bi bi-stars text-lg"></i> <?= e($devMeta['nome']) ?>
          </a>
        <?php endforeach; ?>
      <?php elseif (odontograma_enabled()): ?>
        <a href="<?= APP_URL ?>/modules/prontuario/odontograma.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-emoji-smile text-lg"></i> Odontograma
        </a>
        <a href="<?= APP_URL ?>/modules/planos/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-clipboard2-check text-lg"></i> Planos de Tratamento
        </a>
      <?php elseif ($currentTheme === 'psicologia'): ?>
        <a href="<?= APP_URL ?>/modules/psicologia/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-flower1 text-lg"></i> Psicologia
        </a>
        <a href="<?= APP_URL ?>/modules/psicologia/escalas.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-clipboard-data text-lg"></i> Escalas Clínicas
        </a>
        <a href="<?= APP_URL ?>/modules/psicologia/planos.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-bullseye text-lg"></i> Plano Terapêutico
        </a>
        <a href="<?= APP_URL ?>/modules/telemedicina/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-camera-video text-lg"></i> Teleatendimento
        </a>
      <?php elseif (is_custom_specialty_theme($currentTheme)): ?>
        <a href="<?= specialty_module_url($currentTheme) ?>" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-stars text-lg"></i> <?= e(specialty_theme_meta($currentTheme)['nome']) ?>
        </a>
        <?php foreach (specialty_theme_resources($currentTheme) as $resource): ?>
          <a href="<?= e($resource['url']) ?>" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
            <i class="bi <?= e($resource['icon']) ?> text-lg"></i> <?= e($resource['label']) ?>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <a href="<?= APP_URL ?>/modules/exames/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-clipboard2-pulse text-lg"></i> Solicitação de Exames
        </a>
        <a href="<?= APP_URL ?>/modules/telemedicina/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-camera-video text-lg"></i> Telemedicina
        </a>
      <?php endif; ?>

      <a href="<?= APP_URL ?>/modules/financeiro/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
        <i class="bi bi-cash-stack text-lg"></i> Financeiro
      </a>
      <a href="<?= APP_URL ?>/modules/nfse/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
        <i class="bi bi-receipt text-lg"></i> NFSe
      </a>
      <a href="<?= APP_URL ?>/modules/relatorios/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
        <i class="bi bi-bar-chart-fill text-lg"></i> Relatórios BI
      </a>
      <a href="<?= APP_URL ?>/modules/protocolos/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
        <i class="bi bi-journal-check text-lg"></i> Protocolos
      </a>
      <a href="<?= APP_URL ?>/modules/modelos_clinicos/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
        <i class="bi bi-ui-checks-grid text-lg"></i> Modelos Clínicos
      </a>
      <a href="<?= APP_URL ?>/modules/cuidados/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
        <i class="bi bi-clipboard2-heart text-lg"></i> Cuidados e Alertas
      </a>

      <div class="border-t border-white/10 my-3"></div>
      <div class="px-3 text-xs uppercase opacity-60 mb-1">Configurações</div>

      <?php if ($userPerfil === 'admin'): ?>
        <a href="<?= APP_URL ?>/modules/pagina_web/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-globe2 text-lg"></i> Página da Web
        </a>
      <?php endif; ?>
      <?php if ($userPerfil === 'admin' || $isDev): ?>
        <a href="<?= APP_URL ?>/modules/empresa/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-building text-lg"></i> Empresa
        </a>
        <a href="<?= APP_URL ?>/modules/usuarios/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-person-gear text-lg"></i> Usuários
        </a>
        <a href="<?= APP_URL ?>/modules/importar/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-cloud-upload text-lg"></i> Importação
        </a>
        <?php if ($isDev): ?>
        <a href="<?= APP_URL ?>/modules/backup/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-shield-check text-lg"></i> Backup
        </a>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($isDev): ?>
        <a href="<?= APP_URL ?>/modules/licencas/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-key-fill text-lg"></i> Licenças
        </a>
        <a href="<?= APP_URL ?>/modules/desenvolvedor/index.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
          <i class="bi bi-code-slash text-lg"></i> Desenvolvedor
        </a>
      <?php endif; ?>

      <a href="<?= APP_URL ?>/modules/temas/index.php" class="nav-item <?= $isThemesPage ? 'nav-item-active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition">
        <i class="bi bi-palette-fill text-lg"></i> Especialidades
      </a>
    </nav>
  </aside>

  <div class="flex-1 ml-0 lg:ml-64 transition-all">
    <header class="bg-white shadow-sm sticky top-0 z-30">
      <div class="flex items-center justify-between px-4 lg:px-6 min-h-16 py-3 gap-3">
        <div class="flex items-center gap-3">
          <button onclick="toggleSidebar()" class="lg:hidden text-2xl text-slate-600">
            <i class="bi bi-list"></i>
          </button>
          <div class="hidden lg:block">
            <div class="font-semibold text-slate-700"><?= e($page_title) ?></div>
            <?php if (!empty($empresa)): ?>
              <div class="text-xs text-slate-500">
                Empresa ativa: <span class="font-medium text-slate-700"><?= e($empresa['nome_fantasia'] ?: $empresa['razao_social']) ?></span>
                <?php if ($isDev): ?>
                  <span class="ml-1 text-slate-400">#<?= (int) active_company_id() ?></span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap justify-end">
          <?php if (!$isDev): ?>
            <span class="hidden md:inline text-sm text-slate-600"><?= e($empresa['nome_fantasia'] ?: $empresa['razao_social'] ?? '') ?></span>
          <?php endif; ?>

          <span class="badge <?= e($licStatus['class']) ?>">
            <i class="bi bi-key mr-1"></i><?= e($licStatus['label']) ?>
          </span>

          <?php if ($isDev): ?>
            <a href="<?= APP_URL ?>/modules/desenvolvedor/index.php" class="btn bg-slate-100 text-slate-700 text-xs px-3 py-2">
              <i class="bi bi-building-check"></i> Trocar empresa
            </a>
          <?php endif; ?>

          <div class="relative">
            <button onclick="document.getElementById('userMenu').classList.toggle('hidden')" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition">
              <div class="w-9 h-9 rounded-full bg-gradient-to-br <?= $tema['gradient_from'] ?> <?= $tema['gradient_to'] ?> text-white flex items-center justify-center font-bold">
                <?= e($userInicial) ?>
              </div>
              <span class="hidden md:inline text-sm font-medium text-slate-700"><?= e($userNome) ?></span>
              <i class="bi bi-chevron-down text-xs text-slate-500"></i>
            </button>
            <div id="userMenu" class="hidden absolute right-0 mt-2 w-64 bg-white border rounded-lg shadow-lg py-2">
              <div class="px-4 py-2 border-b">
                <div class="text-sm font-semibold"><?= e($userNome) ?></div>
                <div class="text-xs text-slate-500"><?= e($userEmail) ?></div>
                <div class="text-xs text-slate-400 mt-1 capitalize"><?= e($userPerfil) ?></div>
              </div>
              <?php if ($isDev): ?>
                <a href="<?= APP_URL ?>/modules/desenvolvedor/index.php" class="block px-4 py-2 text-sm hover:bg-slate-50">
                  <i class="bi bi-code-square mr-2"></i>Painel Desenvolvedor
                </a>
                <a href="<?= APP_URL ?>/modules/licencas/index.php" class="block px-4 py-2 text-sm hover:bg-slate-50">
                  <i class="bi bi-key mr-2"></i>Gerenciar Licenças
                </a>
                <a href="<?= APP_URL ?>/modules/desenvolvedor/senha.php" class="block px-4 py-2 text-sm hover:bg-slate-50">
                  <i class="bi bi-lock mr-2"></i>Alterar Minha Senha
                </a>
              <?php else: ?>
                <a href="<?= APP_URL ?>/modules/usuarios/perfil.php" class="block px-4 py-2 text-sm hover:bg-slate-50">
                  <i class="bi bi-person mr-2"></i>Meu Perfil
                </a>
                <a href="<?= APP_URL ?>/modules/usuarios/seguranca.php" class="block px-4 py-2 text-sm hover:bg-slate-50">
                  <i class="bi bi-shield-lock mr-2"></i>Segurança (2FA)
                </a>
              <?php endif; ?>
              <a href="<?= APP_URL ?>/modules/temas/index.php" class="block px-4 py-2 text-sm hover:bg-slate-50">
                <i class="bi bi-palette mr-2"></i>Alterar Especialidades
              </a>
              <a href="<?= APP_URL ?>/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                <i class="bi bi-box-arrow-right mr-2"></i>Sair
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="p-4 lg:p-6 max-w-7xl mx-auto">
      <?= flash_render() ?>
