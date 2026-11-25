<?php
require_once __DIR__ . '/specialty_definitions.php';

function normalize_theme_choice($theme, $fallback = 'medico') {
    $theme = trim((string) $theme);
    $fallback = trim((string) $fallback) ?: 'medico';

    $aliases = [
        'odontologia' => 'dentista',
        'odonto' => 'dentista',
        'odontologico' => 'dentista',
        'odontológica' => 'dentista',
    ];
    if (isset($aliases[$theme])) {
        $theme = $aliases[$theme];
    }

    $allowed = allowed_theme_keys();
    return in_array($theme, $allowed, true) ? $theme : $fallback;
}

function clinic_theme_catalog() {
    static $catalog = null;
    if ($catalog !== null) {
        return $catalog;
    }

    $catalog = [
        'medico' => [
            'icone' => '🩺',
            'nome' => 'Clínica Médica',
            'descricao' => 'Fluxo clínico geral com prontuário, exames, receita e telemedicina.',
            'cor_primaria' => '#0ea5e9',
            'cor_secundaria' => '#1d4ed8',
            'gradient_from' => 'from-sky-600',
            'gradient_to' => 'to-blue-800',
            'accent' => 'sky',
            'summary' => 'Base generalista para especialidades médicas.',
        ],
        'dentista' => [
            'icone' => '🦷',
            'nome' => 'Odontologia',
            'descricao' => 'Interface odontológica com odontograma e planos de tratamento.',
            'cor_primaria' => '#06b6d4',
            'cor_secundaria' => '#0f766e',
            'gradient_from' => 'from-cyan-500',
            'gradient_to' => 'to-teal-700',
            'accent' => 'cyan',
            'summary' => 'Fluxo visual orientado à prática odontológica.',
        ],
        'psicologia' => [
            'icone' => '🧠',
            'nome' => 'Psicologia',
            'descricao' => 'Prontuário ampliado com escalas clínicas, plano terapêutico e consentimentos.',
            'cor_primaria' => '#8b5cf6',
            'cor_secundaria' => '#c026d3',
            'gradient_from' => 'from-violet-500',
            'gradient_to' => 'to-fuchsia-700',
            'accent' => 'violet',
            'summary' => 'Módulos estruturados para psicologia clínica.',
        ],
        'psiquiatria' => [
            'icone' => '🫂',
            'nome' => 'Psiquiatria',
            'descricao' => 'Acolhimento em saúde mental com risco, psicofármacos e seguimento psiquiátrico.',
            'cor_primaria' => '#7c3aed',
            'cor_secundaria' => '#4c1d95',
            'gradient_from' => 'from-violet-600',
            'gradient_to' => 'to-purple-900',
            'accent' => 'violet',
            'summary' => 'Estrutura voltada para história psiquiátrica e estado mental.',
        ],
        'pediatria' => [
            'icone' => '🧸',
            'nome' => 'Pediatria',
            'descricao' => 'Puericultura, crescimento, desenvolvimento e segurança do paciente pediátrico.',
            'cor_primaria' => '#0ea5e9',
            'cor_secundaria' => '#0284c7',
            'gradient_from' => 'from-sky-400',
            'gradient_to' => 'to-cyan-700',
            'accent' => 'sky',
            'summary' => 'Campos para responsável, vacinação e acompanhamento infantil.',
        ],
        'ginecologia_obstetricia' => [
            'icone' => '🤰',
            'nome' => 'Ginecologia e Obstetrícia',
            'descricao' => 'Avaliação ginecológica, obstétrica e pré-natal com sinais de risco.',
            'cor_primaria' => '#ec4899',
            'cor_secundaria' => '#be185d',
            'gradient_from' => 'from-pink-500',
            'gradient_to' => 'to-rose-700',
            'accent' => 'pink',
            'summary' => 'Fluxo focado em saúde da mulher, gestação e rastreios.',
        ],
        'ortopedia_traumatologia' => [
            'icone' => '🦴',
            'nome' => 'Ortopedia e Traumatologia',
            'descricao' => 'Dor, trauma, mobilidade, imagem e reabilitação musculoesquelética.',
            'cor_primaria' => '#f59e0b',
            'cor_secundaria' => '#b45309',
            'gradient_from' => 'from-amber-500',
            'gradient_to' => 'to-orange-700',
            'accent' => 'amber',
            'summary' => 'Prontuário estruturado para lesões, dor e função.',
        ],
        'cardiologia' => [
            'icone' => '❤️',
            'nome' => 'Cardiologia',
            'descricao' => 'Risco cardiovascular, sintomas, exames cardiológicos e metas terapêuticas.',
            'cor_primaria' => '#ef4444',
            'cor_secundaria' => '#b91c1c',
            'gradient_from' => 'from-red-500',
            'gradient_to' => 'to-rose-800',
            'accent' => 'red',
            'summary' => 'Campos para dor torácica, dispneia, ECG e estratificação.',
        ],
        'oftalmologia' => [
            'icone' => '👁️',
            'nome' => 'Oftalmologia',
            'descricao' => 'Acuidade visual, tonometria, biomicroscopia e conduta oftalmológica.',
            'cor_primaria' => '#6366f1',
            'cor_secundaria' => '#3730a3',
            'gradient_from' => 'from-indigo-500',
            'gradient_to' => 'to-blue-800',
            'accent' => 'indigo',
            'summary' => 'Rotina clínica visual com dados por olho e exames complementares.',
        ],
        'neurologia' => [
            'icone' => '🧠',
            'nome' => 'Neurologia',
            'descricao' => 'Exame neurológico focal, crises, imagem e seguimento neurológico.',
            'cor_primaria' => '#14b8a6',
            'cor_secundaria' => '#0f766e',
            'gradient_from' => 'from-teal-500',
            'gradient_to' => 'to-emerald-800',
            'accent' => 'teal',
            'summary' => 'Estrutura para sintomas neurológicos e exame focal.',
        ],
    ];

    return $catalog;
}

function allowed_theme_keys() {
    return array_keys(clinic_theme_catalog());
}

function custom_specialty_themes() {
    return ['psiquiatria', 'pediatria', 'ginecologia_obstetricia', 'ortopedia_traumatologia', 'cardiologia', 'oftalmologia', 'neurologia'];
}

function structured_specialty_themes() {
    return array_merge(['psicologia'], custom_specialty_themes());
}

function is_custom_specialty_theme($theme) {
    return in_array(normalize_theme_choice($theme), custom_specialty_themes(), true);
}

function theme_option_label($theme) {
    $meta = specialty_theme_meta($theme);
    return ($meta['icone'] ?? '•') . ' ' . ($meta['nome'] ?? ucfirst((string) $theme));
}

function theme_select_choices($include_medico = true) {
    $choices = [];
    foreach (clinic_enabled_specialties() as $theme) {
        if (!$include_medico && $theme === 'medico') {
            continue;
        }
        $choices[$theme] = theme_option_label($theme);
    }
    return $choices;
}

function specialty_theme_meta($theme = null) {
    $theme = normalize_theme_choice($theme ?: (function_exists('get_theme') ? get_theme() : 'medico'));
    $catalog = clinic_theme_catalog();
    return $catalog[$theme] ?? $catalog['medico'];
}

function specialty_module_url($theme = null) {
    $theme = normalize_theme_choice($theme ?: (function_exists('get_theme') ? get_theme() : 'medico'));
    if ($theme === 'psicologia') {
        return APP_URL . '/modules/psicologia/index.php';
    }
    if (is_custom_specialty_theme($theme)) {
        return APP_URL . '/modules/especialidades/index.php?tema=' . urlencode($theme);
    }
    if ($theme === 'dentista') {
        return APP_URL . '/modules/planos/index.php';
    }
    return APP_URL . '/modules/prontuario/index.php';
}

function specialty_theme_resources($theme = null, $pacienteId = 0) {
    $theme = normalize_theme_choice($theme ?: (function_exists('get_theme') ? get_theme() : 'medico'));
    $qs = $pacienteId > 0 ? '?paciente_id=' . (int) $pacienteId : '';
    $base = APP_URL . '/modules/prontuario';
    $resources = [
        'psiquiatria' => [
            ['label' => 'Nova avaliação psiquiátrica', 'url' => $base . '/novo.php' . $qs, 'icon' => 'bi-clipboard2-pulse', 'class' => 'bg-violet-50 text-violet-700', 'description' => 'Registrar anamnese psiquiátrica e risco.'],
            ['label' => 'Histórico de prontuários', 'url' => $base . '/index.php' . $qs, 'icon' => 'bi-journal-text', 'class' => 'bg-white text-slate-700', 'description' => 'Consultar evoluções anteriores.'],
        ],
        'pediatria' => [
            ['label' => 'Nova consulta pediátrica', 'url' => $base . '/novo.php' . $qs, 'icon' => 'bi-heart-pulse', 'class' => 'bg-sky-50 text-sky-700', 'description' => 'Preencher consulta aguda ou puericultura.'],
            ['label' => 'Agenda pediátrica', 'url' => APP_URL . '/modules/agenda/index.php', 'icon' => 'bi-calendar-heart', 'class' => 'bg-white text-slate-700', 'description' => 'Organizar retornos e revisão de vacinas.'],
        ],
        'ginecologia_obstetricia' => [
            ['label' => 'Nova evolução gineco-obstétrica', 'url' => $base . '/novo.php' . $qs, 'icon' => 'bi-clipboard-heart', 'class' => 'bg-pink-50 text-pink-700', 'description' => 'Registrar consulta ginecológica ou pré-natal.'],
            ['label' => 'Telemedicina', 'url' => APP_URL . '/modules/telemedicina/index.php', 'icon' => 'bi-camera-video', 'class' => 'bg-white text-slate-700', 'description' => 'Acompanhamento remoto quando aplicável.'],
        ],
        'ortopedia_traumatologia' => [
            ['label' => 'Novo atendimento ortopédico', 'url' => $base . '/novo.php' . $qs, 'icon' => 'bi-bandaid', 'class' => 'bg-amber-50 text-amber-700', 'description' => 'Registrar dor, trauma e exame físico.'],
            ['label' => 'Solicitação de exames', 'url' => APP_URL . '/modules/exames/index.php', 'icon' => 'bi-clipboard2-pulse', 'class' => 'bg-white text-slate-700', 'description' => 'Acompanhar raio-X, RM e outros exames.'],
        ],
        'cardiologia' => [
            ['label' => 'Nova consulta cardiológica', 'url' => $base . '/novo.php' . $qs, 'icon' => 'bi-heart', 'class' => 'bg-red-50 text-red-700', 'description' => 'Registrar sintomas, risco e exames cardíacos.'],
            ['label' => 'Agenda de retornos', 'url' => APP_URL . '/modules/agenda/index.php', 'icon' => 'bi-calendar2-check', 'class' => 'bg-white text-slate-700', 'description' => 'Controlar seguimento e reavaliações.'],
        ],
        'oftalmologia' => [
            ['label' => 'Nova consulta oftalmológica', 'url' => $base . '/novo.php' . $qs, 'icon' => 'bi-eye', 'class' => 'bg-indigo-50 text-indigo-700', 'description' => 'Documentar AV, exame ocular e conduta.'],
            ['label' => 'Receitas / laudos', 'url' => $base . '/index.php' . $qs, 'icon' => 'bi-file-earmark-text', 'class' => 'bg-white text-slate-700', 'description' => 'Consultar prescrições e históricos.'],
        ],
        'neurologia' => [
            ['label' => 'Nova consulta neurológica', 'url' => $base . '/novo.php' . $qs, 'icon' => 'bi-activity', 'class' => 'bg-teal-50 text-teal-700', 'description' => 'Registrar sintomas neurológicos e exame focal.'],
            ['label' => 'Exames e seguimento', 'url' => APP_URL . '/modules/exames/index.php', 'icon' => 'bi-file-medical', 'class' => 'bg-white text-slate-700', 'description' => 'Acompanhar EEG, RM, ENMG e outros.'],
        ],
    ];

    $extraQuery = $pacienteId > 0
        ? '?paciente_id=' . (int) $pacienteId
        : '?tema=' . urlencode($theme);

    $common = [
        ['label' => 'Protocolos da especialidade', 'url' => APP_URL . '/modules/protocolos/index.php?tema=' . urlencode($theme), 'icon' => 'bi-journal-check', 'class' => 'bg-white text-slate-700', 'description' => 'Checklist, fluxos e sinais de alerta padronizados.'],
        ['label' => 'Modelos clínicos', 'url' => APP_URL . '/modules/modelos_clinicos/index.php?tema=' . urlencode($theme), 'icon' => 'bi-ui-checks-grid', 'class' => 'bg-white text-slate-700', 'description' => 'Textos prontos para evolução, retorno e orientações.'],
        ['label' => 'Cuidados e alertas', 'url' => APP_URL . '/modules/cuidados/index.php' . $extraQuery, 'icon' => 'bi-clipboard2-heart', 'class' => 'bg-white text-slate-700', 'description' => 'Planos de cuidado, retorno e alertas por paciente.'],
    ];

    return array_merge($resources[$theme] ?? [], $common);
}

function specialty_detail_table($theme) {
    $map = [
        'psiquiatria' => 'psiquiatria_prontuario',
        'pediatria' => 'pediatria_prontuario',
        'ginecologia_obstetricia' => 'ginecologia_obstetricia_prontuario',
        'ortopedia_traumatologia' => 'ortopedia_traumatologia_prontuario',
        'cardiologia' => 'cardiologia_prontuario',
        'oftalmologia' => 'oftalmologia_prontuario',
        'neurologia' => 'neurologia_prontuario',
    ];
    $theme = normalize_theme_choice($theme);
    return $map[$theme] ?? null;
}

function specialty_form_definitions($theme) {
    $theme = normalize_theme_choice($theme);
    $catalog = clinic_specialty_form_definitions_catalog();
    return $catalog[$theme] ?? null;
}

function specialty_form_fields($theme) {
    $definition = specialty_form_definitions($theme);
    if (!$definition) {
        return [];
    }
    $fields = [];
    foreach (($definition['sections'] ?? []) as $section) {
        foreach (($section['fields'] ?? []) as $field) {
            $fields[] = $field;
        }
    }
    return $fields;
}

function specialty_record_defaults($theme) {
    $defaults = [
        'id' => 0,
        'empresa_id' => function_exists('active_company_id') ? active_company_id() : 1,
        'prontuario_id' => 0,
        'paciente_id' => 0,
        'profissional_id' => $_SESSION['user_id'] ?? 0,
    ];
    foreach (specialty_form_fields($theme) as $field) {
        $defaults[$field['name']] = $field['default'] ?? '';
    }
    return $defaults;
}

function specialty_field_label($theme, $name) {
    foreach (specialty_form_fields($theme) as $field) {
        if (($field['name'] ?? '') === $name) {
            return $field['label'] ?? $name;
        }
    }
    return $name;
}

function specialty_condition_matches($rule, $data) {
    if (empty($rule['field'])) {
        return false;
    }
    $current = (string) ($data[$rule['field']] ?? '');
    return in_array($current, array_map('strval', $rule['values'] ?? []), true);
}

function specialty_validate_payload($theme, $payload) {
    $errors = [];
    foreach (specialty_form_fields($theme) as $field) {
        $name = $field['name'];
        $label = $field['label'] ?? $name;
        $value = trim((string) ($payload[$name] ?? ''));
        $required = !empty($field['required']) || (!empty($field['required_if']) && specialty_condition_matches($field['required_if'], $payload));

        if ($required && $value === '') {
            $errors[] = 'Preencha o campo obrigatório: ' . $label . '.';
            continue;
        }

        if ($value !== '' && isset($field['min_length']) && mb_strlen($value, 'UTF-8') < (int) $field['min_length']) {
            $errors[] = $label . ' deve conter ao menos ' . (int) $field['min_length'] . ' caracteres.';
        }

        if ($value !== '' && ($field['type'] ?? '') === 'number') {
            if (!is_numeric($value)) {
                $errors[] = $label . ' deve ser numérico.';
                continue;
            }
            $numeric = (float) $value;
            if (isset($field['min']) && $numeric < (float) $field['min']) {
                $errors[] = $label . ' deve ser maior ou igual a ' . $field['min'] . '.';
            }
            if (isset($field['max']) && $numeric > (float) $field['max']) {
                $errors[] = $label . ' deve ser menor ou igual a ' . $field['max'] . '.';
            }
        }
    }

    switch (normalize_theme_choice($theme)) {
        case 'psiquiatria':
            if (($payload['psiq_risco_suicidio'] ?? '') === 'alto' && trim((string) ($payload['psiq_conduta_urgente'] ?? '')) === '') {
                $errors[] = 'Informe a conduta urgente / internação para risco de suicídio alto.';
            }
            break;
        case 'pediatria':
            if (($payload['ped_febre'] ?? '') === 'sim' && trim((string) ($payload['ped_tempo_febre'] ?? '')) === '') {
                $errors[] = 'Informe o tempo de febre.';
            }
            if (in_array(($payload['ped_vacinacao_em_dia'] ?? ''), ['nao', 'incompleta'], true) && trim((string) ($payload['ped_vacinas_pendentes'] ?? '')) === '') {
                $errors[] = 'Descreva as vacinas pendentes.';
            }
            break;
        case 'ginecologia_obstetricia':
            if (in_array(($payload['go_tipo_atendimento'] ?? ''), ['obstetricia', 'pre_natal', 'puerperio'], true) && trim((string) ($payload['go_idade_gestacional'] ?? '')) === '') {
                $errors[] = 'Informe a idade gestacional para atendimento obstétrico / pré-natal.';
            }
            break;
        case 'ortopedia_traumatologia':
            if (($payload['orto_trauma_recente'] ?? '') === 'sim' && trim((string) ($payload['orto_neurovascular'] ?? '')) === '') {
                $errors[] = 'Descreva o estado neurovascular distal nos casos de trauma recente.';
            }
            break;
        case 'neurologia':
            if (($payload['neuro_crises_convulsivas'] ?? '') === 'sim' && trim((string) ($payload['neuro_descricao_crise'] ?? '')) === '') {
                $errors[] = 'Descreva as crises convulsivas.';
            }
            if (($payload['neuro_fraqueza'] ?? '') === 'sim' && trim((string) ($payload['neuro_deficit_focal'] ?? '')) === '') {
                $errors[] = 'Descreva o déficit focal associado à fraqueza.';
            }
            break;
    }

    return array_values(array_unique($errors));
}

function specialty_record_load($theme, $prontuarioId) {
    $defaults = specialty_record_defaults($theme);
    $table = specialty_detail_table($theme);
    if (!$table || !$prontuarioId) {
        return $defaults;
    }

    try {
        if (function_exists('db_table_exists') && !db_table_exists($table)) {
            return $defaults;
        }
        $stmt = db()->prepare("SELECT * FROM {$table} WHERE prontuario_id = ? AND empresa_id = ? LIMIT 1");
        $stmt->execute([(int) $prontuarioId, active_company_id()]);
        $row = $stmt->fetch();
        return $row ? array_merge($defaults, $row) : $defaults;
    } catch (Throwable $e) {
        return $defaults;
    }
}

function specialty_request_payload($theme) {
    $payload = [];
    foreach (specialty_form_fields($theme) as $field) {
        $name = $field['name'];
        if (function_exists('input')) {
            $payload[$name] = input($name);
        } else {
            $payload[$name] = $_POST[$name] ?? null;
        }
    }
    return $payload;
}

function specialty_save_prontuario_detail($theme, $payload) {
    $table = specialty_detail_table($theme);
    if (!$table) {
        throw new Exception('Especialidade sem tabela de detalhe configurada.');
    }
    if (function_exists('db_table_exists') && !db_table_exists($table)) {
        throw new Exception('A tabela da especialidade ainda não existe no banco de dados: ' . $table . '.');
    }

    $data = array_merge(specialty_record_defaults($theme), $payload);
    $data['empresa_id'] = active_company_id();
    clinic_validate_clinical_links((int) $data['paciente_id'], (int) $data['prontuario_id']);
    clinic_company_record('usuarios', (int) $data['profissional_id']);
    $errors = specialty_validate_payload($theme, $data);
    if ($errors) {
        throw new Exception(implode(' ', $errors));
    }

    $columns = ['empresa_id', 'prontuario_id', 'paciente_id', 'profissional_id'];
    foreach (specialty_form_fields($theme) as $field) {
        $columns[] = $field['name'];
    }
    $columns = array_values(array_unique($columns));

    $stmt = db()->prepare("SELECT id FROM {$table} WHERE prontuario_id = ? AND empresa_id = ? LIMIT 1");
    $stmt->execute([(int) $data['prontuario_id'], active_company_id()]);
    $existingId = (int) ($stmt->fetchColumn() ?: 0);

    if ($existingId > 0) {
        $set = [];
        $values = [];
        foreach ($columns as $column) {
            $set[] = "{$column} = ?";
            $values[] = $data[$column] ?? null;
        }
        $values[] = $existingId;
        $values[] = active_company_id();
        db()->prepare("UPDATE {$table} SET " . implode(', ', $set) . ", atualizado_em = CURRENT_TIMESTAMP WHERE id = ? AND empresa_id = ?")
            ->execute($values);
        return $existingId;
    }

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $values = [];
    foreach ($columns as $column) {
        $values[] = $data[$column] ?? null;
    }
    db()->prepare("INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})")
        ->execute($values);
    return (int) db()->lastInsertId();
}

function specialty_required_labels($theme) {
    $labels = [];
    foreach (specialty_form_fields($theme) as $field) {
        if (!empty($field['required'])) {
            $labels[] = $field['label'] ?? $field['name'];
        }
    }
    return $labels;
}

function specialty_module_stats($theme) {
    $table = specialty_detail_table($theme);
    $stats = [
        'records_month' => 0,
        'patients_month' => 0,
        'required_fields' => count(specialty_required_labels($theme)),
    ];

    if (!$table) {
        return $stats;
    }

    try {
        if (function_exists('db_table_exists') && !db_table_exists($table)) {
            return $stats;
        }
        $stmt = db()->prepare("SELECT COUNT(*) AS total, COUNT(DISTINCT paciente_id) AS pacientes FROM {$table} WHERE empresa_id = ? AND DATE_FORMAT(COALESCE(atualizado_em, criado_em), '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $stmt->execute([function_exists('active_company_id') ? active_company_id() : 1]);
        $row = $stmt->fetch() ?: [];
        $stats['records_month'] = (int) ($row['total'] ?? 0);
        $stats['patients_month'] = (int) ($row['pacientes'] ?? 0);
    } catch (Throwable $e) {
    }

    return $stats;
}
