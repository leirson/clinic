<?php
$page_title = 'Bloqueios de Horário';
require_once __DIR__ . '/../../includes/header.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    if (input('acao')==='excluir') {
        db()->prepare("DELETE FROM bloqueios_horario WHERE id=? AND empresa_id=?")->execute([(int)input('id'), active_company_id()]);
        flash('success','Bloqueio removido.');
    } else {
        $stmt = db()->prepare("INSERT INTO bloqueios_horario (empresa_id, profissional_id, data_inicio, data_fim, motivo, tipo) VALUES (?,?,?,?,?,?)");
        $stmt->execute([
          active_company_id(),
          (int)input('profissional_id') ?: null,
          input('data_inicio').' '.input('hora_inicio').':00',
          input('data_fim').' '.input('hora_fim').':00',
          input('motivo'), input('tipo')
        ]);
        flash('success','Bloqueio criado!');
    }
    redirect('bloqueios.php');
}

$bloqueios = db()->prepare("SELECT b.*, u.nome AS prof FROM bloqueios_horario b LEFT JOIN usuarios u ON u.id=b.profissional_id WHERE b.empresa_id=? ORDER BY data_inicio DESC");
$bloqueios->execute([active_company_id()]);
$bloqueios = $bloqueios->fetchAll();

$profs = db()->prepare("SELECT id, nome FROM usuarios WHERE empresa_id=? AND ativo=1");
$profs->execute([active_company_id()]); $profs = $profs->fetchAll();
?>
<div class="mb-5"><a href="index.php" class="text-sm text-slate-500"><i class="bi bi-arrow-left"></i> Voltar</a><h1 class="text-2xl font-bold"><i class="bi bi-lock"></i> Bloqueios de Horário</h1></div>

<form method="POST" class="card p-4 mb-5 grid grid-cols-1 md:grid-cols-7 gap-3 items-end">
  <?= csrf_field() ?>
  <div><label class="text-xs">Profissional</label><select name="profissional_id" class="form-select"><option value="">Todos</option><?php foreach($profs as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nome']) ?></option><?php endforeach; ?></select></div>
  <div><label class="text-xs">Tipo</label><select name="tipo" class="form-select"><option value="feriado">Feriado</option><option value="almoco">Almoço</option><option value="folga">Folga</option><option value="outro">Outro</option></select></div>
  <div><label class="text-xs">Data Início</label><input type="date" name="data_inicio" required class="form-input"></div>
  <div><label class="text-xs">Hora</label><input type="time" name="hora_inicio" required value="08:00" class="form-input"></div>
  <div><label class="text-xs">Data Fim</label><input type="date" name="data_fim" required class="form-input"></div>
  <div><label class="text-xs">Hora</label><input type="time" name="hora_fim" required value="18:00" class="form-input"></div>
  <button class="btn btn-primary">+ Bloquear</button>
  <div class="md:col-span-7"><label class="text-xs">Motivo</label><input name="motivo" class="form-input"></div>
</form>

<div class="card overflow-hidden">
  <table class="table-modern">
    <thead><tr><th>Período</th><th>Profissional</th><th>Tipo</th><th>Motivo</th><th></th></tr></thead>
    <tbody>
      <?php if (!$bloqueios): ?><tr><td colspan="5" class="text-center py-8 text-slate-400">Nenhum bloqueio.</td></tr>
      <?php else: foreach($bloqueios as $b): ?>
      <tr>
        <td><?= format_date($b['data_inicio'], true) ?> → <?= format_date($b['data_fim'], true) ?></td>
        <td><?= e($b['prof'] ?: '— Todos —') ?></td>
        <td><span class="badge badge-warning"><?= e($b['tipo']) ?></span></td>
        <td><?= e($b['motivo']) ?></td>
        <td class="text-right">
          <form method="POST" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="id" value="<?= $b['id'] ?>">
            <button data-confirm="Remover bloqueio?" class="text-red-600"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
