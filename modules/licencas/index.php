<?php
require_once __DIR__ . '/../../includes/functions.php';
require_developer();
$page_title = 'Licenças & Multiempresa';

$editId = (int) input('edit');
$formEmpresa = [
    'id' => 0,
    'razao_social' => '',
    'nome_fantasia' => '',
    'cnpj' => '',
    'email' => '',
    'telefone' => '',
    'tema_padrao' => 'medico',
    'dominio_principal' => '',
    'ativo' => 1,
];
$formLicenca = [
    'id' => 0,
    'plano' => 'profissional',
    'status' => 'ativa',
    'chave_licenca' => strtoupper(bin2hex(random_bytes(8))),
    'validade_inicio' => date('Y-m-d'),
    'validade_fim' => date('Y-m-d', strtotime('+1 year')),
    'max_usuarios' => 10,
    'max_pacientes' => 1000,
    'observacoes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    try {
        $empresaId = (int) input('empresa_id');
        $licencaId = (int) input('licenca_id');
        $razao = trim(input('razao_social'));
        $fantasia = trim(input('nome_fantasia'));
        $cnpj = format_cpf_cnpj(only_numbers(input('cnpj')));
        $email = trim(input('email'));
        $telefone = only_numbers(input('telefone'));
        $tema = 'medico';
        if ($empresaId > 0) {
            $stmtTema = db()->prepare("SELECT tema_padrao FROM empresas WHERE id=? LIMIT 1");
            $stmtTema->execute([$empresaId]);
            $tema = normalize_theme_choice($stmtTema->fetchColumn() ?: 'medico');
        }
        $dominio = trim(input('dominio_principal'));
        $ativo = input('ativo') ? 1 : 0;

        if ($razao === '' || $cnpj === '') {
            throw new Exception('Razão social e CNPJ são obrigatórios.');
        }

        db()->beginTransaction();

        if ($empresaId > 0) {
            $stmt = db()->prepare("UPDATE empresas SET razao_social=?, nome_fantasia=?, cnpj=?, email=?, telefone=?, dominio_principal=?, ativo=? WHERE id=?");
            $stmt->execute([$razao, $fantasia, $cnpj, $email, $telefone, $dominio, $ativo, $empresaId]);
        } else {
            $stmt = db()->prepare("INSERT INTO empresas (razao_social, nome_fantasia, cnpj, email, telefone, tema_padrao, dominio_principal, ativo, asten_ambiente) VALUES (?,?,?,?,?,?,?,?, 'homologacao')");
            $stmt->execute([$razao, $fantasia, $cnpj, $email, $telefone, $tema, $dominio, $ativo]);
            $empresaId = (int) db()->lastInsertId();

            $adminNome = trim(input('admin_nome'));
            $adminEmail = trim(input('admin_email'));
            $adminSenha = input('admin_senha');
            if ($adminNome === '' || $adminEmail === '' || strlen($adminSenha) < 6) {
                throw new Exception('Ao criar uma empresa, informe administrador inicial com senha de ao menos 6 caracteres.');
            }
            $stmt = db()->prepare("INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, tema_preferido, cor_tema, ativo) VALUES (?,?,?,?, 'admin', ?, 'blue', 1)");
            $stmt->execute([
                $empresaId,
                $adminNome,
                $adminEmail,
                password_hash($adminSenha, PASSWORD_BCRYPT, ['cost' => HASH_COST]),
                $tema,
            ]);
        }

        $plano = input('plano', 'profissional');
        $status = input('status', 'ativa');
        $chave = strtoupper(trim(input('chave_licenca')));
        $inicio = input('validade_inicio') ?: date('Y-m-d');
        $fim = input('validade_fim') ?: null;
        $maxUsuarios = max(1, (int) input('max_usuarios', 10));
        $maxPacientes = max(100, (int) input('max_pacientes', 1000));
        $obs = trim(input('observacoes'));

        if ($licencaId > 0) {
            $stmt = db()->prepare("UPDATE licencas SET plano=?, status=?, chave_licenca=?, validade_inicio=?, validade_fim=?, max_usuarios=?, max_pacientes=?, observacoes=?, atualizado_por=?, atualizado_em=NOW() WHERE id=? AND empresa_id=?");
            $stmt->execute([$plano, $status, $chave, $inicio, $fim, $maxUsuarios, $maxPacientes, $obs, $_SESSION['user_id'], $licencaId, $empresaId]);
        } else {
            $stmt = db()->prepare("INSERT INTO licencas (empresa_id, plano, status, chave_licenca, validade_inicio, validade_fim, max_usuarios, max_pacientes, observacoes, criado_por, atualizado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$empresaId, $plano, $status, $chave, $inicio, $fim, $maxUsuarios, $maxPacientes, $obs, $_SESSION['user_id'], $_SESSION['user_id']]);
        }

        db()->commit();
        flash('success', $editId ? 'Empresa/licença atualizada com sucesso.' : 'Empresa criada e licenciada com sucesso.');
        redirect(APP_URL . '/modules/licencas/index.php?edit=' . $empresaId);
    } catch (Exception $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash('error', 'Erro: ' . $e->getMessage());
    }
}

if ($editId > 0) {
    $stmt = db()->prepare("SELECT e.*, l.id AS licenca_id, l.plano, l.status, l.chave_licenca, l.validade_inicio, l.validade_fim, l.max_usuarios, l.max_pacientes, l.observacoes
                          FROM empresas e
                          LEFT JOIN licencas l ON l.id = (SELECT ll.id FROM licencas ll WHERE ll.empresa_id = e.id ORDER BY ll.id DESC LIMIT 1)
                          WHERE e.id = ?");
    $stmt->execute([$editId]);
    if ($row = $stmt->fetch()) {
        $formEmpresa = array_merge($formEmpresa, $row);
        $formLicenca = array_merge($formLicenca, [
            'id' => $row['licenca_id'] ?? 0,
            'plano' => $row['plano'] ?? 'profissional',
            'status' => $row['status'] ?? 'ativa',
            'chave_licenca' => $row['chave_licenca'] ?? $formLicenca['chave_licenca'],
            'validade_inicio' => $row['validade_inicio'] ?? $formLicenca['validade_inicio'],
            'validade_fim' => $row['validade_fim'] ?? $formLicenca['validade_fim'],
            'max_usuarios' => $row['max_usuarios'] ?? $formLicenca['max_usuarios'],
            'max_pacientes' => $row['max_pacientes'] ?? $formLicenca['max_pacientes'],
            'observacoes' => $row['observacoes'] ?? '',
        ]);
    }
}

$empresas = db()->query("SELECT e.*, 
    l.id AS licenca_id, l.plano, l.status AS licenca_status, l.validade_fim, l.max_usuarios, l.max_pacientes,
    (SELECT COUNT(*) FROM usuarios u WHERE u.empresa_id = e.id AND u.ativo = 1 AND u.perfil <> 'desenvolvedor') AS total_usuarios,
    (SELECT COUNT(*) FROM pacientes p WHERE p.empresa_id = e.id AND p.ativo = 1) AS total_pacientes
    FROM empresas e
    LEFT JOIN licencas l ON l.id = (SELECT ll.id FROM licencas ll WHERE ll.empresa_id = e.id ORDER BY ll.id DESC LIMIT 1)
    ORDER BY e.nome_fantasia, e.razao_social")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="xl:col-span-1 space-y-6">
    <div class="card p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-800"><i class="bi bi-key-fill text-primary"></i> Licenças</h1>
          <p class="text-sm text-slate-500">Multiempresa, planos e ativação por clínica.</p>
        </div>
        <?php if ($editId): ?>
          <a href="index.php" class="btn bg-slate-100 text-slate-700"><i class="bi bi-plus-lg"></i> Nova</a>
        <?php endif; ?>
      </div>

      <form method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="empresa_id" value="<?= (int) ($formEmpresa['id'] ?? 0) ?>">
        <input type="hidden" name="licenca_id" value="<?= (int) ($formLicenca['id'] ?? 0) ?>">

        <div class="grid grid-cols-1 gap-4">
          <div>
            <label class="text-sm font-medium">Razão social *</label>
            <input name="razao_social" required value="<?= e($formEmpresa['razao_social']) ?>" class="form-input">
          </div>
          <div>
            <label class="text-sm font-medium">Nome fantasia</label>
            <input name="nome_fantasia" value="<?= e($formEmpresa['nome_fantasia']) ?>" class="form-input">
          </div>
          <div>
            <label class="text-sm font-medium">CNPJ *</label>
            <input name="cnpj" required value="<?= e($formEmpresa['cnpj']) ?>" class="form-input" data-mask="##.###.###/####-##">
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium">E-mail</label>
              <input name="email" type="email" value="<?= e($formEmpresa['email']) ?>" class="form-input">
            </div>
            <div>
              <label class="text-sm font-medium">Telefone</label>
              <input name="telefone" value="<?= e($formEmpresa['telefone']) ?>" class="form-input" data-mask="(##) #####-####">
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
              <div class="font-medium text-slate-700">Especialidade padrão atual</div>
              <p class="mt-1"><?= e(theme_option_label($formEmpresa['tema_padrao'] ?? 'medico')) ?></p>
              <p class="mt-2 text-xs text-slate-500">A alteração foi centralizada na aba <strong>Especialidades</strong> e no <strong>perfil do usuário</strong>.</p>
            </div>
            <div>
              <label class="text-sm font-medium">Domínio principal</label>
              <input name="dominio_principal" value="<?= e($formEmpresa['dominio_principal'] ?? '') ?>" class="form-input" placeholder="ex: clinica.exemplo.com">
            </div>
          </div>
          <div class="flex items-center gap-2">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ativo" value="1" <?= ($formEmpresa['ativo'] ?? 1) ? 'checked' : '' ?>> Empresa ativa</label>
          </div>
        </div>

        <?php if (empty($formEmpresa['id'])): ?>
          <div class="border-t pt-4">
            <h3 class="font-semibold text-slate-700 mb-3">Administrador inicial da empresa</h3>
            <div class="grid grid-cols-1 gap-4">
              <input name="admin_nome" class="form-input" placeholder="Nome do administrador" required>
              <input name="admin_email" type="email" class="form-input" placeholder="admin@empresa.com" required>
              <input name="admin_senha" type="password" class="form-input" placeholder="Senha inicial" required>
            </div>
          </div>
        <?php endif; ?>

        <div class="border-t pt-4 space-y-4">
          <h3 class="font-semibold text-slate-700">Licença</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium">Plano</label>
              <select name="plano" class="form-select">
                <option value="starter" <?= $formLicenca['plano'] === 'starter' ? 'selected' : '' ?>>Starter</option>
                <option value="profissional" <?= $formLicenca['plano'] === 'profissional' ? 'selected' : '' ?>>Profissional</option>
                <option value="premium" <?= $formLicenca['plano'] === 'premium' ? 'selected' : '' ?>>Premium</option>
                <option value="enterprise" <?= $formLicenca['plano'] === 'enterprise' ? 'selected' : '' ?>>Enterprise</option>
              </select>
            </div>
            <div>
              <label class="text-sm font-medium">Status</label>
              <select name="status" class="form-select">
                <?php foreach (['ativa','teste','pendente','expirada','bloqueada','cancelada'] as $st): ?>
                  <option value="<?= $st ?>" <?= $formLicenca['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="text-sm font-medium">Chave da licença</label>
              <input name="chave_licenca" value="<?= e($formLicenca['chave_licenca']) ?>" class="form-input">
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium">Início</label>
                <input type="date" name="validade_inicio" value="<?= e($formLicenca['validade_inicio']) ?>" class="form-input">
              </div>
              <div>
                <label class="text-sm font-medium">Validade</label>
                <input type="date" name="validade_fim" value="<?= e($formLicenca['validade_fim']) ?>" class="form-input">
              </div>
            </div>
            <div>
              <label class="text-sm font-medium">Máx. usuários</label>
              <input type="number" min="1" name="max_usuarios" value="<?= (int) $formLicenca['max_usuarios'] ?>" class="form-input">
            </div>
            <div>
              <label class="text-sm font-medium">Máx. pacientes</label>
              <input type="number" min="100" name="max_pacientes" value="<?= (int) $formLicenca['max_pacientes'] ?>" class="form-input">
            </div>
          </div>
          <div>
            <label class="text-sm font-medium">Observações</label>
            <textarea name="observacoes" rows="4" class="form-textarea"><?= e($formLicenca['observacoes']) ?></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t">
          <button class="btn btn-primary"><i class="bi bi-save"></i> Salvar empresa e licença</button>
        </div>
      </form>
    </div>
  </div>

  <div class="xl:col-span-2">
    <div class="card overflow-hidden">
      <div class="px-6 py-4 border-b flex items-center justify-between gap-3">
        <div>
          <h2 class="font-bold text-slate-800">Empresas licenciadas</h2>
          <p class="text-sm text-slate-500">Controle centralizado de multiempresa, limite de usuários e vigência.</p>
        </div>
        <a href="<?= APP_URL ?>/modules/desenvolvedor/index.php" class="btn bg-slate-100 text-slate-700"><i class="bi bi-code-square"></i> Painel Dev</a>
      </div>
      <div class="overflow-x-auto">
        <table class="table-modern">
          <thead>
            <tr>
              <th>Empresa</th>
              <th>Plano</th>
              <th>Licença</th>
              <th>Validade</th>
              <th>Uso</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$empresas): ?>
              <tr><td colspan="6" class="text-center py-10 text-slate-400">Nenhuma empresa cadastrada.</td></tr>
            <?php else: foreach ($empresas as $empresaItem):
                $statusInfo = license_status_label($empresaItem['id']);
            ?>
              <tr>
                <td>
                  <div class="font-semibold text-slate-800"><?= e($empresaItem['nome_fantasia'] ?: $empresaItem['razao_social']) ?></div>
                  <div class="text-xs text-slate-500"><?= e($empresaItem['cnpj']) ?><?php if (!empty($empresaItem['dominio_principal'])): ?> · <?= e($empresaItem['dominio_principal']) ?><?php endif; ?></div>
                </td>
                <td><span class="badge badge-info"><?= e($empresaItem['plano'] ?: 'sem plano') ?></span></td>
                <td><span class="badge <?= e($statusInfo['class']) ?>"><?= e($statusInfo['label']) ?></span></td>
                <td class="text-sm"><?= $empresaItem['validade_fim'] ? format_date($empresaItem['validade_fim']) : 'Sem vencimento' ?></td>
                <td class="text-sm text-slate-600">
                  <?= (int) $empresaItem['total_usuarios'] ?>/<?= (int) ($empresaItem['max_usuarios'] ?: 0) ?> usuários<br>
                  <?= (int) $empresaItem['total_pacientes'] ?>/<?= (int) ($empresaItem['max_pacientes'] ?: 0) ?> pacientes
                </td>
                <td class="text-right whitespace-nowrap">
                  <a href="?edit=<?= (int) $empresaItem['id'] ?>" class="text-amber-600 mx-1" title="Editar"><i class="bi bi-pencil-square"></i></a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
