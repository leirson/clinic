    </main>
    <footer class="text-center text-xs text-slate-400 py-6">
      © <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?> — Sistema de Gestão para Clínicas
    </footer>
  </div>
</div>
<script>
window.ClinicAppBootstrap = {
  appUrl: <?= json_encode(APP_URL, JSON_UNESCAPED_SLASHES) ?>,
  currentUserId: <?= json_encode((int) ($_SESSION['user_id'] ?? 0)) ?>,
  featureSettings: <?= json_encode(function_exists('company_feature_settings') ? company_feature_settings() : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
</script>
<?= clinic_asset_scripts(['resources/entries/app.js']) ?>
</body>
</html>
