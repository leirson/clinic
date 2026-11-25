<?php
require_once __DIR__ . '/../../includes/functions.php';
require_developer();
$page_title = 'Backup';

$backup_dir = APP_PATH . '/storage/backups';
if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_validate();
    $acao = input('acao');

    if ($acao === 'gerar') {
        try {
            $nome = 'backup_' . date('Ymd_His') . '.sql';
            $caminho = $backup_dir . '/' . $nome;

            $ins = db()->prepare("INSERT INTO backups (tipo, arquivo, status) VALUES ('manual', ?, 'em_andamento')");
            $ins->execute([$nome]);
            $bid = db()->lastInsertId();

            // Dump manual (compatível com hosts sem mysqldump)
            $pdo = db();
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $sql = "-- ClinicApp Backup " . date('Y-m-d H:i:s') . "\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            foreach ($tables as $t) {
                $sql .= "DROP TABLE IF EXISTS `$t`;\n";
                $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM);
                $sql .= $create[1] . ";\n\n";
                $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    $cols = '`' . implode('`,`', array_keys($r)) . '`';
                    $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($r));
                    $sql .= "INSERT INTO `$t` ($cols) VALUES (" . implode(',', $vals) . ");\n";
                }
                $sql .= "\n";
            }
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            file_put_contents($caminho, $sql);
            // Comprime
            if (function_exists('gzopen')) {
                $gz = gzopen($caminho . '.gz', 'wb9');
                gzwrite($gz, $sql);
                gzclose($gz);
                unlink($caminho);
                $nome .= '.gz';
                $caminho .= '.gz';
            }

            $tam = filesize($caminho);
            db()->prepare("UPDATE backups SET status='concluido', arquivo=?, tamanho_bytes=?, concluido_em=NOW() WHERE id=?")
                ->execute([$nome, $tam, $bid]);
            flash('success', 'Backup gerado com sucesso! Tamanho: ' . round($tam/1024,2) . ' KB');
        } catch (Exception $e) {
            db()->prepare("UPDATE backups SET status='erro', erro=? WHERE id=?")->execute([$e->getMessage(), $bid ?? 0]);
            flash('error','Erro: '.$e->getMessage());
        }
        redirect('index.php');
    }

    if ($acao === 'excluir') {
        $id = (int)input('id');
        $b = db()->prepare("SELECT * FROM backups WHERE id=?");
        $b->execute([$id]);
        $b = $b->fetch();
        if ($b) {
            @unlink($backup_dir . '/' . $b['arquivo']);
            db()->prepare("DELETE FROM backups WHERE id=?")->execute([$id]);
        }
        flash('success','Backup removido.');
        redirect('index.php');
    }
}

$backups = db()->query("SELECT * FROM backups ORDER BY criado_em DESC LIMIT 50")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex justify-between mb-5">
  <div>
    <h1 class="text-2xl font-bold"><i class="bi bi-shield-check text-primary"></i> Backup</h1>
    <p class="text-sm text-slate-500">Cópia de segurança do banco de dados</p>
  </div>
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="gerar">
    <button class="btn btn-primary" data-confirm="Gerar backup completo agora?"><i class="bi bi-cloud-download"></i> Gerar Backup</button>
  </form>
</div>

<div class="card p-4 mb-5 bg-blue-50 border border-blue-200">
  <p class="text-sm text-blue-800"><i class="bi bi-info-circle"></i> <strong>Automatização:</strong> Configure um cron job no Hostinger apontando para <code>/cron/backup_auto.php</code> diariamente.</p>
  <p class="text-xs text-blue-700 mt-1">Os backups ficam em <code>storage/backups/</code> e podem ser baixados.</p>
</div>

<div class="card overflow-hidden">
<table class="table-modern">
<thead><tr><th>Data</th><th>Arquivo</th><th>Tipo</th><th>Tamanho</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php if (!$backups): ?><tr><td colspan="6" class="text-center py-10 text-slate-400">Nenhum backup ainda.</td></tr>
<?php else: foreach($backups as $b): ?>
<tr>
  <td><?= format_date($b['criado_em'], true) ?></td>
  <td class="font-mono text-xs"><?= e($b['arquivo']) ?></td>
  <td><span class="badge badge-<?= $b['tipo']==='automatico'?'info':'secondary' ?>"><?= e($b['tipo']) ?></span></td>
  <td><?= round($b['tamanho_bytes']/1024, 2) ?> KB</td>
  <td><span class="badge badge-<?= $b['status']==='concluido'?'success':($b['status']==='erro'?'danger':'warning') ?>"><?= e($b['status']) ?></span></td>
  <td class="text-right whitespace-nowrap">
    <?php if ($b['status']==='concluido'): ?>
      <a href="download.php?id=<?= $b['id'] ?>" class="text-primary mx-1"><i class="bi bi-download"></i></a>
    <?php endif; ?>
    <form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= $b['id'] ?>"><button class="text-red-600" data-confirm="Excluir?"><i class="bi bi-trash"></i></button></form>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
