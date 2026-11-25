<?php
/** Controles por empresa. Ausência de configuração mantém compatibilidade: todas ativas. */
function clinic_enabled_specialties($empresaId = null) {
    $empresaId = (int) ($empresaId ?? active_company_id());
    static $cache = [];
    if (isset($cache[$empresaId])) return $cache[$empresaId];
    if (!app_config_uses_company_scope()) return allowed_theme_keys();
    // Não usar app_config_get: seu fallback legado pode ler configurações de outra empresa.
    $stmt = db()->prepare('SELECT valor FROM configuracoes WHERE empresa_id=? AND chave=? LIMIT 1');
    $stmt->execute([$empresaId, 'especialidades_habilitadas']);
    $raw = $stmt->fetchColumn();
    if ($raw === false) return $cache[$empresaId] = allowed_theme_keys();
    $keys = json_decode((string) $raw, true);
    if (!is_array($keys)) throw new RuntimeException('Configuração de especialidades inválida. Contate o desenvolvedor.');
    return $cache[$empresaId] = array_values(array_intersect(allowed_theme_keys(), $keys));
}
function clinic_specialty_enabled($theme, $empresaId = null) {
    return is_string($theme) && in_array($theme, clinic_enabled_specialties($empresaId), true);
}
function clinic_available_theme($theme) {
    if (clinic_specialty_enabled($theme)) return $theme;
    return clinic_enabled_specialties()[0] ?? 'medico'; // Identidade visual neutra quando nenhuma está habilitada.
}
function clinic_require_specialty($theme) {
    if (!clinic_specialty_enabled($theme)) {
        http_response_code(403);
        exit('Especialidade desabilitada para esta empresa. Solicite a habilitação ao desenvolvedor.');
    }
}
function clinic_specialty_route_guard() {
    $path = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    if (strpos($path, '/modules/psicologia/') !== false) clinic_require_specialty('psicologia');
    if (strpos($path, '/modules/planos/') !== false) clinic_require_specialty('dentista');
    if (strpos($path, '/modules/prontuario/odontograma.php') !== false) clinic_require_specialty('dentista');
    if (strpos($path, '/modules/especialidades/') !== false || strpos($path, '/modules/prontuario/novo.php') !== false) {
        clinic_require_specialty(normalize_theme_choice(input('tema', get_theme())));
    }
}
