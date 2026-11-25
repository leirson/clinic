<?php $u = $u ?? []; $is_edit = !empty($u['id']); $hasRepasse = db_column_exists('usuarios', 'percentual_repasse'); ?>
<form method="POST" class="card p-6 space-y-4">
  <?= csrf_field() ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div><label class="text-sm">Nome Completo *</label><input name="nome" required value="<?= e($u['nome'] ?? '') ?>" class="form-input"></div>
    <div><label class="text-sm">E-mail *</label><input name="email" type="email" required value="<?= e($u['email'] ?? '') ?>" class="form-input"></div>
    <div><label class="text-sm">Senha <?= $is_edit ? '(deixe em branco para manter)' : '*' ?></label><input name="senha" type="password" <?= $is_edit ? '' : 'required' ?> class="form-input"></div>
    <div>
      <label class="text-sm">Perfil *</label>
      <select name="perfil" class="form-select" required>
        <option value="admin" <?= ($u['perfil'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
        <option value="profissional" <?= ($u['perfil'] ?? 'profissional') === 'profissional' ? 'selected' : '' ?>>Profissional</option>
        <option value="recepcao" <?= ($u['perfil'] ?? '') === 'recepcao' ? 'selected' : '' ?>>Recepção</option>
      </select>
      <p class="text-xs text-slate-400 mt-1">O perfil desenvolvedor é reservado ao módulo interno de licenças e não aparece nesta tela.</p>
    </div>
    <div><label class="text-sm">Especialidade</label><input name="especialidade" value="<?= e($u['especialidade'] ?? '') ?>" class="form-input" placeholder="Ex: Clínico Geral, Ortodontia..."></div>
    <div><label class="text-sm">Registro Profissional (CRM/CRO/CRP)</label><input name="registro_profissional" value="<?= e($u['registro_profissional'] ?? '') ?>" class="form-input"></div>
    <div><label class="text-sm">Telefone</label><input name="telefone" data-mask="(##) #####-####" value="<?= e($u['telefone'] ?? '') ?>" class="form-input"></div>
    <?php if ($hasRepasse): ?>
      <div>
        <label class="text-sm">Repasse (%)</label>
        <input name="percentual_repasse" type="number" min="0" max="100" step="0.01" value="<?= e(number_format((float) ($u['percentual_repasse'] ?? 0), 2, '.', '')) ?>" class="form-input">
        <p class="text-xs text-slate-400 mt-1">Usado no BI para projeção de repasse por profissional.</p>
      </div>
    <?php endif; ?>
    <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
      <div class="font-medium text-slate-700"><i class="bi bi-palette mr-1"></i> Especialidades do usuário</div>
      <p class="mt-1">A alteração da especialidade visual fica disponível apenas na aba <strong>Especialidades</strong> e no <strong>perfil do próprio usuário</strong>.</p>
    </div>
    <?php if ($is_edit): ?>
      <div class="flex items-center pt-6"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ativo" value="1" <?= ($u['ativo'] ?? 1) ? 'checked' : '' ?>> Usuário ativo</label></div>
    <?php endif; ?>
  </div>
  <div class="flex gap-2 justify-end pt-3 border-t">
    <a href="index.php" class="btn bg-slate-200 text-slate-700">Cancelar</a>
    <button class="btn btn-primary"><i class="bi bi-check"></i> Salvar</button>
  </div>
</form>
