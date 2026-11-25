<?php
// Formulário compartilhado para novo/editar paciente
$p = $p ?? [];
?>
<form method="POST" class="card p-6 space-y-6">
  <?= csrf_field() ?>

  <div>
    <h3 class="font-bold text-slate-700 mb-3 pb-2 border-b"><i class="bi bi-person"></i> Dados Pessoais</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Nome Completo *</label>
        <input name="nome" required class="form-input" value="<?= e($p['nome'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Tipo</label>
        <select name="tipo_pessoa" class="form-select">
          <option value="F" <?= ($p['tipo_pessoa'] ?? 'F')==='F'?'selected':'' ?>>Pessoa Física</option>
          <option value="J" <?= ($p['tipo_pessoa'] ?? '')==='J'?'selected':'' ?>>Pessoa Jurídica</option>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">CPF/CNPJ</label>
        <input name="cpf_cnpj" class="form-input" value="<?= e($p['cpf_cnpj'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">RG</label>
        <input name="rg" class="form-input" value="<?= e($p['rg'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Data de Nascimento</label>
        <input type="date" name="data_nascimento" class="form-input" value="<?= e($p['data_nascimento'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Sexo</label>
        <select name="sexo" class="form-select">
          <option value="">--</option>
          <option value="M" <?= ($p['sexo'] ?? '')==='M'?'selected':'' ?>>Masculino</option>
          <option value="F" <?= ($p['sexo'] ?? '')==='F'?'selected':'' ?>>Feminino</option>
          <option value="O" <?= ($p['sexo'] ?? '')==='O'?'selected':'' ?>>Outro</option>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Estado Civil</label>
        <input name="estado_civil" class="form-input" value="<?= e($p['estado_civil'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Profissão</label>
        <input name="profissao" class="form-input" value="<?= e($p['profissao'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">E-mail</label>
        <input type="email" name="email" class="form-input" value="<?= e($p['email'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Telefone</label>
        <input name="telefone" data-mask="(##) ####-####" class="form-input" value="<?= e($p['telefone'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Celular / WhatsApp</label>
        <input name="celular" data-mask="(##) #####-####" class="form-input" value="<?= e($p['celular'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Convênio</label>
        <input name="convenio" class="form-input" value="<?= e($p['convenio'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Nº Carteirinha</label>
        <input name="numero_carteirinha" class="form-input" value="<?= e($p['numero_carteirinha'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div>
    <h3 class="font-bold text-slate-700 mb-3 pb-2 border-b"><i class="bi bi-geo-alt"></i> Endereço (necessário para NFSe)</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <label class="text-sm font-medium">CEP</label>
        <input name="cep" data-mask="#####-###" data-cep="" class="form-input" value="<?= e($p['cep'] ?? '') ?>">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Logradouro</label>
        <input name="logradouro" class="form-input" value="<?= e($p['logradouro'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Número</label>
        <input name="numero" class="form-input" value="<?= e($p['numero'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Complemento</label>
        <input name="complemento" class="form-input" value="<?= e($p['complemento'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Bairro</label>
        <input name="bairro" class="form-input" value="<?= e($p['bairro'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Cidade</label>
        <input name="cidade" class="form-input" value="<?= e($p['cidade'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">UF</label>
        <input name="uf" maxlength="2" class="form-input uppercase" value="<?= e($p['uf'] ?? '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium">Cód. Município (IBGE)</label>
        <input name="codigo_municipio" class="form-input" value="<?= e($p['codigo_municipio'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div>
    <h3 class="font-bold text-slate-700 mb-3 pb-2 border-b"><i class="bi bi-heart-pulse"></i> Dados Clínicos</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium">Tipo Sanguíneo</label>
        <select name="tipo_sanguineo" class="form-select">
          <option value="">--</option>
          <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $t): ?>
            <option <?= ($p['tipo_sanguineo'] ?? '')===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div></div>
      <div>
        <label class="text-sm font-medium">Alergias</label>
        <textarea name="alergias" rows="2" class="form-textarea"><?= e($p['alergias'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Medicamentos em uso</label>
        <textarea name="medicamentos_uso" rows="2" class="form-textarea"><?= e($p['medicamentos_uso'] ?? '') ?></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Histórico Familiar</label>
        <textarea name="historico_familiar" rows="2" class="form-textarea"><?= e($p['historico_familiar'] ?? '') ?></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Observações Gerais</label>
        <textarea name="observacoes" rows="3" class="form-textarea"><?= e($p['observacoes'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <div class="flex gap-2 justify-end pt-4 border-t">
    <a href="index.php" class="btn bg-slate-200 text-slate-700">Cancelar</a>
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
  </div>
</form>
