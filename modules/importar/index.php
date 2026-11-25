<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$page_title = 'Importação de Dados';

function import_normalize_header($value) {
    $value = (string) $value;
    $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value);
    $value = trim($value);
    $value = mb_strtolower($value, 'UTF-8');
    $map = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
        'é' => 'e', 'ê' => 'e',
        'í' => 'i',
        'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ç' => 'c'
    ];
    $value = strtr($value, $map);
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    return trim($value, '_');
}

function import_first_non_empty($row, $keys, $default = '') {
    foreach ((array) $keys as $key) {
        if (!array_key_exists($key, $row)) {
            continue;
        }
        $value = is_string($row[$key]) ? trim($row[$key]) : $row[$key];
        if ($value !== '' && $value !== null) {
            return $value;
        }
    }
    return $default;
}

function import_detect_separator($file) {
    $line = '';
    $fh = fopen($file, 'r');
    if ($fh) {
        $line = fgets($fh) ?: '';
        fclose($fh);
    }
    $comma = substr_count($line, ',');
    $semi = substr_count($line, ';');
    $tab = substr_count($line, "\t");
    if ($tab > $comma && $tab > $semi) {
        return "\t";
    }
    return $semi > $comma ? ';' : ',';
}

function import_parse_date($value) {
    $value = trim((string) $value);
    if ($value === '') return null;
    $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd.m.Y'];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt && $dt->format($format) === $value) {
            return $dt->format('Y-m-d');
        }
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d', $ts) : null;
}

function import_parse_time($value) {
    $value = trim((string) $value);
    if ($value === '') return null;
    $formats = ['H:i:s', 'H:i'];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt && $dt->format($format) === $value) {
            return $dt->format('H:i:s');
        }
    }
    $ts = strtotime($value);
    return $ts ? date('H:i:s', $ts) : null;
}

function import_clean_rich_text($value) {
    $value = (string) $value;
    if ($value === '') return '';

    if (stripos($value, '{\\rtf') === 0) {
        $value = preg_replace('/\\par[d]?/i', "\n", $value);
        $value = preg_replace('/\\tab/i', "\t", $value);
        $value = preg_replace('/\\[a-z]+-?\d*\s?/i', '', $value);
        $value = str_replace(['{', '}'], '', $value);
    }

    $value = preg_replace('/<\s*br\s*\/?>/i', "\n", $value);
    $value = preg_replace('/<\/(p|div|h[1-6]|li|tr)>/i', "\n", $value);
    $value = preg_replace('/<(li)>/i', '• ', $value);
    $value = strip_tags($value);
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace("/\r\n|\r/", "\n", $value);
    $value = preg_replace("/\n{3,}/", "\n\n", $value);
    return trim($value);
}

function import_row_to_assoc($headers, $row) {
    $data = [];
    foreach ($headers as $index => $header) {
        $data[$header] = isset($row[$index]) ? trim((string) $row[$index]) : '';
    }
    return $data;
}

function import_read_csv_header($file) {
    $separator = import_detect_separator($file);
    $fh = fopen($file, 'r');
    if (!$fh) {
        return [];
    }
    $headers = fgetcsv($fh, 0, $separator);
    fclose($fh);
    if (!$headers) {
        return [];
    }
    return array_map('import_normalize_header', $headers);
}

function import_detect_iclinic_csv_kind($headers) {
    $headers = array_values(array_filter((array) $headers));
    if (!$headers) {
        return null;
    }

    $hasPatientName = in_array('patient_name', $headers, true) || in_array('nome_paciente', $headers, true);
    $hasEventText = in_array('eventblock_text', $headers, true) || in_array('texto', $headers, true) || in_array('conteudo', $headers, true);
    if ($hasPatientName && $hasEventText) {
        return 'records';
    }

    $hasName = in_array('name', $headers, true) || in_array('nome', $headers, true);
    $hasBirth = in_array('birth_date', $headers, true) || in_array('data_nascimento', $headers, true);
    if ($hasName && $hasBirth) {
        return 'patients';
    }

    return null;
}

function import_extract_zip_to_dir($zipFile, $extractDir) {
    if (!class_exists('ZipArchive')) {
        throw new Exception('A extensão ZipArchive não está disponível no servidor PHP. Ative-a na Hostinger antes de usar importação em ZIP.');
    }

    if (!is_dir($extractDir) && !mkdir($extractDir, 0755, true)) {
        throw new Exception('Não foi possível preparar a pasta temporária da importação ZIP.');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) {
        throw new Exception('Não foi possível abrir o arquivo ZIP do iClinic.');
    }

    if (!$zip->extractTo($extractDir)) {
        $zip->close();
        throw new Exception('Falha ao extrair o arquivo ZIP do iClinic.');
    }

    $zip->close();
}

function import_find_iclinic_files_in_dir($dir) {
    $result = [
        'patients' => null,
        'records' => null,
        'csv_files' => []
    ];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        if (strtolower($file->getExtension()) !== 'csv') {
            continue;
        }

        $path = $file->getPathname();
        $result['csv_files'][] = $path;
        $base = mb_strtolower($file->getBasename(), 'UTF-8');

        if (!$result['patients'] && preg_match('/(^|[^a-z])(patient|patients|paciente|pacientes)([^a-z]|$)/u', $base)) {
            $result['patients'] = $path;
            continue;
        }

        if (!$result['records'] && preg_match('/(^|[^a-z])(record|records|prontuario|prontuarios)([^a-z]|$)/u', $base)) {
            $result['records'] = $path;
            continue;
        }
    }

    if (!$result['patients'] || !$result['records']) {
        foreach ($result['csv_files'] as $path) {
            $kind = import_detect_iclinic_csv_kind(import_read_csv_header($path));
            if ($kind === 'patients' && !$result['patients']) {
                $result['patients'] = $path;
            }
            if ($kind === 'records' && !$result['records']) {
                $result['records'] = $path;
            }
        }
    }

    return $result;
}

function import_append_note($original, $line) {
    $original = trim((string) $original);
    $line = trim((string) $line);
    if ($line === '') {
        return $original;
    }
    if ($original === '') {
        return $line;
    }
    if (mb_stripos($original, $line, 0, 'UTF-8') !== false) {
        return $original;
    }
    return $original . "\n" . $line;
}

function import_upsert_iclinic_patient($empresaId, $row) {
    $nome = trim((string) import_first_non_empty($row, ['name', 'patient_name', 'nome', 'nome_paciente', 'paciente']));
    if ($nome === '') {
        throw new Exception('Nome do paciente ausente.');
    }

    $dataNascimento = import_parse_date(import_first_non_empty($row, ['birth_date', 'patient_birth_date', 'data_nascimento', 'nascimento']));
    $cpf = only_numbers(import_first_non_empty($row, ['cpf', 'cpf_cnpj']));
    $email = trim((string) import_first_non_empty($row, ['email', 'email_secondary']));
    $telefone = only_numbers(import_first_non_empty($row, ['home_phone', 'telefone']));
    $celular = only_numbers(import_first_non_empty($row, ['mobile_phone', 'celular', 'whatsapp']));
    $cep = only_numbers(import_first_non_empty($row, ['zip_code', 'cep']));
    $logradouro = trim((string) import_first_non_empty($row, ['address', 'logradouro', 'endereco']));
    $numero = trim((string) import_first_non_empty($row, ['number', 'numero']));
    $complemento = trim((string) import_first_non_empty($row, ['complement', 'complemento']));
    $bairro = trim((string) import_first_non_empty($row, ['neighborhood', 'bairro']));
    $cidade = trim((string) import_first_non_empty($row, ['city', 'cidade']));
    $uf = strtoupper(trim((string) import_first_non_empty($row, ['state', 'uf', 'birth_state'])));
    $profissao = trim((string) import_first_non_empty($row, ['occupation', 'profissao']));
    $sexo = strtoupper(trim((string) import_first_non_empty($row, ['gender', 'sexo'])));
    $sexo = in_array($sexo, ['M', 'F', 'O'], true) ? $sexo : null;
    $patientCode = trim((string) import_first_non_empty($row, ['patient_code']));
    $observacaoOrigem = trim((string) import_first_non_empty($row, ['observation', 'observacoes']));

    $existing = false;
    if ($cpf !== '') {
        $stmt = db()->prepare("SELECT * FROM pacientes WHERE empresa_id=? AND cpf_cnpj=? LIMIT 1");
        $stmt->execute([$empresaId, $cpf]);
        $existing = $stmt->fetch();
    }

    if (!$existing && $dataNascimento) {
        $stmt = db()->prepare("SELECT * FROM pacientes WHERE empresa_id=? AND nome=? AND data_nascimento=? LIMIT 1");
        $stmt->execute([$empresaId, $nome, $dataNascimento]);
        $existing = $stmt->fetch();
    }

    if (!$existing && $email !== '') {
        $stmt = db()->prepare("SELECT * FROM pacientes WHERE empresa_id=? AND email=? LIMIT 1");
        $stmt->execute([$empresaId, $email]);
        $existing = $stmt->fetch();
    }

    if (!$existing) {
        $stmt = db()->prepare("SELECT * FROM pacientes WHERE empresa_id=? AND nome=? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$empresaId, $nome]);
        $existing = $stmt->fetch();
    }

    $observacoes = trim((string) ($existing['observacoes'] ?? ''));
    if ($patientCode !== '') {
        $observacoes = import_append_note($observacoes, 'iClinic patient_code: ' . $patientCode);
    }
    if ($observacaoOrigem !== '') {
        $observacoes = import_append_note($observacoes, 'Observação importada: ' . import_clean_rich_text($observacaoOrigem));
    }

    if ($existing) {
        db()->prepare("UPDATE pacientes SET
            nome=?,
            cpf_cnpj=?,
            email=?,
            telefone=?,
            celular=?,
            data_nascimento=?,
            sexo=?,
            cep=?,
            logradouro=?,
            numero=?,
            complemento=?,
            bairro=?,
            cidade=?,
            uf=?,
            profissao=?,
            observacoes=?,
            ativo=1
            WHERE id=? AND empresa_id=?
        ")->execute([
            $nome,
            $cpf !== '' ? $cpf : ($existing['cpf_cnpj'] ?: null),
            $email !== '' ? $email : ($existing['email'] ?: null),
            $telefone !== '' ? $telefone : ($existing['telefone'] ?: null),
            $celular !== '' ? $celular : ($existing['celular'] ?: null),
            $dataNascimento ?: ($existing['data_nascimento'] ?: null),
            $sexo ?: ($existing['sexo'] ?: null),
            $cep !== '' ? $cep : ($existing['cep'] ?: null),
            $logradouro !== '' ? $logradouro : ($existing['logradouro'] ?: null),
            $numero !== '' ? $numero : ($existing['numero'] ?: null),
            $complemento !== '' ? $complemento : ($existing['complemento'] ?: null),
            $bairro !== '' ? $bairro : ($existing['bairro'] ?: null),
            $cidade !== '' ? $cidade : ($existing['cidade'] ?: null),
            $uf !== '' ? $uf : ($existing['uf'] ?: null),
            $profissao !== '' ? $profissao : ($existing['profissao'] ?: null),
            $observacoes !== '' ? $observacoes : ($existing['observacoes'] ?: null),
            $existing['id'],
            $empresaId
        ]);
        return (int) $existing['id'];
    }

    db()->prepare("INSERT INTO pacientes (
        empresa_id, nome, cpf_cnpj, email, telefone, celular, data_nascimento, sexo, cep, logradouro, numero, complemento, bairro, cidade, uf, profissao, observacoes, ativo
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)")->execute([
        $empresaId,
        $nome,
        $cpf !== '' ? $cpf : null,
        $email !== '' ? $email : null,
        $telefone !== '' ? $telefone : null,
        $celular !== '' ? $celular : null,
        $dataNascimento,
        $sexo,
        $cep !== '' ? $cep : null,
        $logradouro !== '' ? $logradouro : null,
        $numero !== '' ? $numero : null,
        $complemento !== '' ? $complemento : null,
        $bairro !== '' ? $bairro : null,
        $cidade !== '' ? $cidade : null,
        $uf !== '' ? $uf : null,
        $profissao !== '' ? $profissao : null,
        $observacoes !== '' ? $observacoes : null
    ]);
    return (int) db()->lastInsertId();
}

function import_professional_from_iclinic($empresaId, $row, $defaultProfessionalId = 0) {
    $possibleName = trim((string) import_first_non_empty($row, ['physician_name', 'professional_name', 'profissional', 'medico', 'physician']));
    if ($possibleName !== '') {
        $stmt = db()->prepare("SELECT id FROM usuarios WHERE empresa_id=? AND ativo=1 AND nome=? AND perfil IN ('admin','profissional') LIMIT 1");
        $stmt->execute([$empresaId, $possibleName]);
        $found = (int) $stmt->fetchColumn();
        if ($found) return $found;
    }

    $possibleId = (int) import_first_non_empty($row, ['physician_id'], 0);
    if ($possibleId > 0) {
        $stmt = db()->prepare("SELECT id FROM usuarios WHERE empresa_id=? AND id=? AND ativo=1 AND perfil IN ('admin','profissional') LIMIT 1");
        $stmt->execute([$empresaId, $possibleId]);
        $found = (int) $stmt->fetchColumn();
        if ($found) return $found;
    }

    if ($defaultProfessionalId > 0) {
        return $defaultProfessionalId;
    }

    return (int) ($_SESSION['user_id'] ?? 0);
}

function import_map_iclinic_blocks($tabName, $blocks, $rawRow) {
    $result = [
        'queixa_principal' => '',
        'anamnese' => '',
        'exame_fisico' => '',
        'hipotese_diagnostica' => '',
        'conduta' => '',
        'evolucao' => '',
        'observacoes' => ''
    ];

    $append = function ($field, $text) use (&$result) {
        $text = trim((string) $text);
        if ($text === '') return;
        $result[$field] = trim($result[$field] === '' ? $text : ($result[$field] . "\n\n" . $text));
    };

    foreach ($blocks as $block) {
        $name = trim((string) ($block['name'] ?? ''));
        $nameKey = import_normalize_header($name);
        $text = trim((string) ($block['text'] ?? ''));
        if ($text === '') continue;

        if (strpos($nameKey, 'queixa') !== false) {
            $append('queixa_principal', $text);
        } elseif (strpos($nameKey, 'anamnese') !== false || strpos($nameKey, 'historia') !== false) {
            $append('anamnese', $text);
        } elseif (strpos($nameKey, 'exame_fisico') !== false || strpos($nameKey, 'exame_clinico') !== false || $nameKey === 'exame') {
            $append('exame_fisico', $text);
        } elseif (strpos($nameKey, 'hipotese') !== false || strpos($nameKey, 'diagnost') !== false) {
            $append('hipotese_diagnostica', $text);
        } elseif (strpos($nameKey, 'conduta') !== false || strpos($nameKey, 'plano') !== false || strpos($nameKey, 'prescr') !== false || strpos($nameKey, 'orienta') !== false) {
            $append('conduta', $text);
        } elseif (strpos($nameKey, 'evolucao') !== false || strpos($nameKey, 'soap') !== false || strpos($nameKey, 'atendimento') !== false) {
            $append('evolucao', $text);
        } else {
            $title = $name !== '' ? $name : ($tabName !== '' ? $tabName : 'Bloco importado');
            $append('observacoes', $title . ":\n" . $text);
        }
    }

    if ($result['evolucao'] === '' && $result['observacoes'] !== '') {
        $result['evolucao'] = $result['observacoes'];
    }

    $meta = [];
    if (!empty($rawRow['physician_id'])) $meta[] = 'iClinic physician_id: ' . $rawRow['physician_id'];
    if (!empty($rawRow['start_time']) || !empty($rawRow['end_time'])) {
        $meta[] = 'Horário importado: ' . trim(($rawRow['start_time'] ?? '') . ' - ' . ($rawRow['end_time'] ?? ''));
    }
    if ($tabName !== '') $meta[] = 'Origem iClinic / aba: ' . $tabName;
    if (!empty($rawRow['source_file'])) $meta[] = 'Arquivo de origem: ' . $rawRow['source_file'];
    if ($meta) $append('observacoes', implode("\n", $meta));

    return $result;
}

function import_read_csv_rows($file) {
    $separator = import_detect_separator($file);
    $fh = fopen($file, 'r');
    if (!$fh) {
        throw new Exception('Não foi possível ler o arquivo CSV: ' . basename($file));
    }

    $headers = fgetcsv($fh, 0, $separator);
    if (!$headers) {
        fclose($fh);
        throw new Exception('Cabeçalho CSV inválido em ' . basename($file));
    }
    $headers = array_map('import_normalize_header', $headers);

    $rows = [];
    $line = 1;
    while (($row = fgetcsv($fh, 0, $separator)) !== false) {
        $line++;
        if ($row === [null] || $row === false) {
            continue;
        }
        $rows[] = [
            'line' => $line,
            'data' => import_row_to_assoc($headers, $row)
        ];
    }
    fclose($fh);

    return [
        'headers' => $headers,
        'rows' => $rows
    ];
}

function import_run_iclinic_patients($empresaId, $csvFile, &$log, &$totals) {
    $parsed = import_read_csv_rows($csvFile);
    $kind = import_detect_iclinic_csv_kind($parsed['headers']);
    if ($kind !== 'patients') {
        throw new Exception('O arquivo ' . basename($csvFile) . ' não corresponde ao modelo patient.csv do iClinic.');
    }

    foreach ($parsed['rows'] as $entry) {
        $totals['total']++;
        try {
            import_upsert_iclinic_patient($empresaId, $entry['data']);
            $totals['success']++;
        } catch (Exception $e) {
            $totals['errors']++;
            $log[] = 'Pacientes / linha ' . $entry['line'] . ': ' . $e->getMessage();
        }
    }
}

function import_group_iclinic_records($csvFile, &$log, &$totals) {
    $parsed = import_read_csv_rows($csvFile);
    $kind = import_detect_iclinic_csv_kind($parsed['headers']);
    if ($kind !== 'records') {
        throw new Exception('O arquivo ' . basename($csvFile) . ' não corresponde ao modelo records.csv do iClinic.');
    }

    $grouped = [];
    foreach ($parsed['rows'] as $entry) {
        $totals['total']++;
        $data = $entry['data'];
        try {
            $patientName = trim((string) import_first_non_empty($data, ['patient_name', 'nome_paciente', 'paciente']));
            $birthDate = import_parse_date(import_first_non_empty($data, ['patient_birth_date', 'data_nascimento', 'nascimento']));
            $recordDate = import_parse_date(import_first_non_empty($data, ['date', 'data', 'data_atendimento']));
            $startTime = import_parse_time(import_first_non_empty($data, ['start_time', 'hora_inicio']));
            $endTime = import_parse_time(import_first_non_empty($data, ['end_time', 'hora_fim']));
            $tabName = trim((string) import_first_non_empty($data, ['tab_name', 'titulo']));
            $blockName = trim((string) import_first_non_empty($data, ['eventblock_name', 'bloco', 'subtitulo']));
            $blockText = import_clean_rich_text(import_first_non_empty($data, ['eventblock_text', 'texto', 'conteudo']));

            if ($patientName === '' || !$recordDate || $blockText === '') {
                throw new Exception('Linha incompatível com o modelo records.csv do iClinic.');
            }

            $key = md5(implode('|', [
                mb_strtolower($patientName, 'UTF-8'),
                $birthDate ?: '',
                $recordDate,
                $startTime ?: '',
                $endTime ?: '',
                $tabName
            ]));

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'row' => $data + ['source_file' => basename($csvFile)],
                    'patient_name' => $patientName,
                    'patient_birth_date' => $birthDate,
                    'date' => $recordDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'tab_name' => $tabName,
                    'blocks' => []
                ];
            }

            $grouped[$key]['blocks'][] = [
                'name' => $blockName,
                'text' => $blockText
            ];
        } catch (Exception $e) {
            $totals['errors']++;
            $log[] = 'Prontuários / linha ' . $entry['line'] . ': ' . $e->getMessage();
        }
    }

    return $grouped;
}

function import_run_iclinic_records($empresaId, $csvFile, $defaultProfessionalId, &$log, &$totals) {
    $grouped = import_group_iclinic_records($csvFile, $log, $totals);

    foreach ($grouped as $item) {
        try {
            $patientId = import_upsert_iclinic_patient($empresaId, [
                'patient_name' => $item['patient_name'],
                'patient_birth_date' => $item['patient_birth_date']
            ]);
            $profissionalId = import_professional_from_iclinic($empresaId, $item['row'], $defaultProfessionalId);
            $tabName = $item['tab_name'] ?: 'Prontuário importado do iClinic';
            $mapped = import_map_iclinic_blocks($tabName, $item['blocks'], $item['row']);
            $dataHora = $item['date'] . ' ' . ($item['start_time'] ?: '00:00:00');

            $check = db()->prepare("SELECT id FROM prontuarios WHERE empresa_id=? AND paciente_id=? AND profissional_id=? AND data_atendimento=? AND COALESCE(queixa_principal,'')=? LIMIT 1");
            $check->execute([$empresaId, $patientId, $profissionalId, $dataHora, $mapped['queixa_principal'] ?: $tabName]);
            $existingId = (int) $check->fetchColumn();

            if ($existingId > 0) {
                db()->prepare("UPDATE prontuarios SET anamnese=?, queixa_principal=?, exame_fisico=?, hipotese_diagnostica=?, conduta=?, evolucao=?, observacoes=? WHERE id=? AND empresa_id=?")
                    ->execute([
                        $mapped['anamnese'] ?: null,
                        $mapped['queixa_principal'] ?: $tabName,
                        $mapped['exame_fisico'] ?: null,
                        $mapped['hipotese_diagnostica'] ?: null,
                        $mapped['conduta'] ?: null,
                        $mapped['evolucao'] ?: null,
                        $mapped['observacoes'] ?: null,
                        $existingId,
                        $empresaId
                    ]);
            } else {
                db()->prepare("INSERT INTO prontuarios (empresa_id, paciente_id, profissional_id, agendamento_id, data_atendimento, anamnese, queixa_principal, exame_fisico, hipotese_diagnostica, conduta, evolucao, observacoes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([
                        $empresaId,
                        $patientId,
                        $profissionalId,
                        null,
                        $dataHora,
                        $mapped['anamnese'] ?: null,
                        $mapped['queixa_principal'] ?: $tabName,
                        $mapped['exame_fisico'] ?: null,
                        $mapped['hipotese_diagnostica'] ?: null,
                        $mapped['conduta'] ?: null,
                        $mapped['evolucao'] ?: null,
                        $mapped['observacoes'] ?: null
                    ]);
            }

            $totals['success']++;
        } catch (Exception $e) {
            $totals['errors']++;
            $log[] = 'Atendimento ' . ($item['patient_name'] ?? 'sem paciente') . ' em ' . ($item['date'] ?? 'sem data') . ': ' . $e->getMessage();
        }
    }
}

function import_create_job($empresaId, $tipo, $arquivo, $criadoPor) {
    try {
        $stmt = db()->prepare("INSERT INTO importacoes (empresa_id, tipo, arquivo, status, criado_por) VALUES (?,?,?,'processando',?)");
        $stmt->execute([$empresaId, $tipo, $arquivo, $criadoPor]);
        return (int) db()->lastInsertId();
    } catch (Exception $e) {
        $fallbackTipo = in_array($tipo, ['pacientes', 'procedimentos', 'agendamentos'], true) ? $tipo : 'pacientes';
        $stmt = db()->prepare("INSERT INTO importacoes (empresa_id, tipo, arquivo, status, criado_por, log) VALUES (?,?,?,'processando',?,?)");
        $stmt->execute([$empresaId, $fallbackTipo, $arquivo, $criadoPor, 'Tipo original da importação: ' . $tipo]);
        return (int) db()->lastInsertId();
    }
}

function import_finish_job($id, $total, $success, $errors, $log, $status = 'concluido') {
    db()->prepare("UPDATE importacoes SET status=?, total_linhas=?, sucesso=?, erros=?, log=? WHERE id=?")
        ->execute([$status, $total, $success, $errors, $log, $id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $importId = 0;
    try {
        if (empty($_FILES['arquivo']['name'])) {
            throw new Exception('Selecione um arquivo CSV ou ZIP.');
        }

        $tipo = input('tipo', 'pacientes');
        $empresaId = active_company_id();
        $defaultProfessionalId = (int) input('profissional_padrao_id', 0);
        $originalName = $_FILES['arquivo']['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($tipo === 'pacote_iclinic_zip' && $ext !== 'zip') {
            throw new Exception('Para pacote Afya iClinic, envie o arquivo ZIP exportado pelo sistema.');
        }
        if (in_array($tipo, ['pacientes', 'procedimentos', 'pacientes_iclinic', 'prontuarios_iclinic'], true) && $ext !== 'csv') {
            throw new Exception('Para esta opção, envie um arquivo CSV.');
        }

        $impDir = APP_PATH . '/storage/imports';
        if (!is_dir($impDir)) {
            mkdir($impDir, 0755, true);
        }

        $safeName = uniqid('imp_', true) . '.' . $ext;
        $dest = $impDir . '/' . $safeName;
        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $dest)) {
            throw new Exception('Não foi possível salvar o arquivo enviado.');
        }

        $importId = import_create_job($empresaId, $tipo, basename($dest), (int) $_SESSION['user_id']);

        $totals = ['total' => 0, 'success' => 0, 'errors' => 0];
        $log = [];

        db()->beginTransaction();

        if ($tipo === 'pacientes') {
            $parsed = import_read_csv_rows($dest);
            foreach ($parsed['rows'] as $entry) {
                $totals['total']++;
                $data = $entry['data'];
                try {
                    if (empty($data['nome'])) {
                        throw new Exception('Nome vazio.');
                    }
                    db()->prepare("INSERT INTO pacientes (empresa_id, nome, cpf_cnpj, email, telefone, celular, data_nascimento, sexo, cep, logradouro, numero, bairro, cidade, uf) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                        ->execute([
                            $empresaId,
                            $data['nome'],
                            only_numbers($data['cpf_cnpj'] ?? $data['cpf'] ?? ''),
                            $data['email'] ?? null,
                            only_numbers($data['telefone'] ?? ''),
                            only_numbers($data['celular'] ?? $data['whatsapp'] ?? ''),
                            import_parse_date($data['data_nascimento'] ?? ''),
                            $data['sexo'] ?? null,
                            only_numbers($data['cep'] ?? ''),
                            $data['logradouro'] ?? $data['endereco'] ?? null,
                            $data['numero'] ?? null,
                            $data['bairro'] ?? null,
                            $data['cidade'] ?? null,
                            strtoupper($data['uf'] ?? $data['estado'] ?? '')
                        ]);
                    $totals['success']++;
                } catch (Exception $e) {
                    $totals['errors']++;
                    $log[] = 'Pacientes / linha ' . $entry['line'] . ': ' . $e->getMessage();
                }
            }
        } elseif ($tipo === 'procedimentos') {
            $parsed = import_read_csv_rows($dest);
            foreach ($parsed['rows'] as $entry) {
                $totals['total']++;
                $data = $entry['data'];
                try {
                    db()->prepare("INSERT INTO procedimentos (empresa_id, nome, valor, duracao_minutos, categoria) VALUES (?,?,?,?,?)")
                        ->execute([
                            $empresaId,
                            $data['nome'] ?? '',
                            (float) str_replace(',', '.', $data['valor'] ?? '0'),
                            (int) ($data['duracao'] ?? $data['duracao_minutos'] ?? 30),
                            $data['categoria'] ?? 'clinico'
                        ]);
                    $totals['success']++;
                } catch (Exception $e) {
                    $totals['errors']++;
                    $log[] = 'Procedimentos / linha ' . $entry['line'] . ': ' . $e->getMessage();
                }
            }
        } elseif ($tipo === 'pacientes_iclinic') {
            import_run_iclinic_patients($empresaId, $dest, $log, $totals);
        } elseif ($tipo === 'prontuarios_iclinic') {
            import_run_iclinic_records($empresaId, $dest, $defaultProfessionalId, $log, $totals);
        } elseif ($tipo === 'pacote_iclinic_zip') {
            $extractDir = $impDir . '/zip_' . uniqid();
            import_extract_zip_to_dir($dest, $extractDir);
            $files = import_find_iclinic_files_in_dir($extractDir);

            if (!$files['patients'] && !$files['records']) {
                throw new Exception('Nenhum patient.csv ou records.csv foi encontrado dentro do ZIP do iClinic.');
            }

            if ($files['patients']) {
                $log[] = 'Arquivo de pacientes localizado: ' . basename($files['patients']);
                import_run_iclinic_patients($empresaId, $files['patients'], $log, $totals);
            } else {
                $log[] = 'ZIP sem patient.csv: pacientes serão criados minimamente a partir do records.csv.';
            }

            if ($files['records']) {
                $log[] = 'Arquivo de prontuários localizado: ' . basename($files['records']);
                import_run_iclinic_records($empresaId, $files['records'], $defaultProfessionalId, $log, $totals);
            } else {
                $log[] = 'ZIP sem records.csv: apenas pacientes foram importados.';
            }
        }

        db()->commit();

        $resumo = [
            'Resumo da importação',
            'Tipo: ' . $tipo,
            'Arquivo: ' . basename($dest),
            'Total processado: ' . $totals['total'],
            'Sucesso: ' . $totals['success'],
            'Erros: ' . $totals['errors']
        ];
        $fullLog = implode("\n", array_merge($resumo, [''], $log));
        import_finish_job($importId, $totals['total'], $totals['success'], $totals['errors'], $fullLog, 'concluido');

        flash('success', 'Importação concluída: ' . $totals['success'] . ' sucesso(s), ' . $totals['errors'] . ' erro(s).');
        redirect('index.php');
    } catch (Exception $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        if ($importId > 0) {
            import_finish_job($importId, 0, 0, 1, $e->getMessage(), 'erro');
        }
        flash('error', $e->getMessage());
    }
}

$imps = db()->prepare("SELECT * FROM importacoes WHERE empresa_id=? ORDER BY criado_em DESC LIMIT 20");
$imps->execute([active_company_id()]);
$imps = $imps->fetchAll();

$profissionais = db()->prepare("SELECT id, nome, especialidade, perfil FROM usuarios WHERE empresa_id=? AND ativo=1 AND perfil IN ('admin','profissional') ORDER BY nome ASC");
$profissionais->execute([active_company_id()]);
$profissionais = $profissionais->fetchAll();

$typeLabels = [
    'pacientes' => 'Pacientes',
    'procedimentos' => 'Procedimentos',
    'pacientes_iclinic' => 'Afya iClinic · Pacientes CSV',
    'prontuarios_iclinic' => 'Afya iClinic · Prontuários CSV',
    'pacote_iclinic_zip' => 'Afya iClinic · ZIP completo'
];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-5">
  <h1 class="text-2xl font-bold"><i class="bi bi-cloud-upload text-primary"></i> Importação de Dados</h1>
  <p class="text-sm text-slate-500">Importe pacientes, procedimentos e exportações do Afya iClinic com isolamento por empresa.</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

  <div class="card p-6 xl:col-span-2">
    <h3 class="font-bold mb-3">Nova Importação</h3>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <?= csrf_field() ?>

      <div>
        <label class="text-sm font-medium">Tipo de importação *</label>
        <select name="tipo" id="tipoImportacao" required class="form-select" onchange="toggleImportHelp()">
          <option value="pacientes">CSV de Pacientes</option>
          <option value="procedimentos">CSV de Procedimentos</option>
          <option value="pacientes_iclinic">Afya iClinic · patient.csv</option>
          <option value="prontuarios_iclinic">Afya iClinic · records.csv</option>
          <option value="pacote_iclinic_zip" selected>Afya iClinic · ZIP com pacientes + prontuários</option>
        </select>
      </div>

      <div>
        <label class="text-sm font-medium">Arquivo *</label>
        <input type="file" name="arquivo" id="arquivoImportacao" accept=".zip,.csv" required class="form-input">
        <p class="text-xs text-slate-500 mt-1">Para Afya iClinic, use preferencialmente o ZIP completo exportado pelo sistema. O módulo procura automaticamente por <strong>patient.csv</strong> e <strong>records.csv</strong>.</p>
      </div>

      <div>
        <label class="text-sm font-medium">Profissional padrão para prontuários iClinic</label>
        <select name="profissional_padrao_id" class="form-select">
          <option value="0">Usar usuário logado quando não houver correspondência</option>
          <?php foreach ($profissionais as $prof): ?>
            <option value="<?= (int) $prof['id'] ?>">
              <?= e($prof['nome']) ?><?= !empty($prof['especialidade']) ? ' · ' . e($prof['especialidade']) : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500 mt-1">O iClinic usa <code>physician_id</code> no arquivo records.csv. Se esse profissional não existir nesta empresa, o sistema usará o profissional padrão acima.</p>
      </div>

      <div id="helpCsvPacientes" class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600 hidden">
        <strong>CSV de Pacientes:</strong> nome*, cpf_cnpj, email, telefone, celular, data_nascimento, sexo, cep, logradouro, numero, bairro, cidade, uf.
      </div>

      <div id="helpCsvProcedimentos" class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600 hidden">
        <strong>CSV de Procedimentos:</strong> nome*, valor, duracao, categoria.
      </div>

      <div id="helpIclinicPacientes" class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-slate-700 hidden">
        <strong>Afya iClinic · patient.csv:</strong> importa e atualiza pacientes da empresa ativa usando nome, data de nascimento, CPF e e-mail para reconciliação.
      </div>

      <div id="helpIclinicProntuarios" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-slate-700 hidden">
        <strong>Afya iClinic · records.csv:</strong> agrupa múltiplos blocos do mesmo atendimento e cria evoluções no prontuário da empresa ativa. Formato oficial do iClinic: <code>patient_birth_date</code>, <code>patient_name</code>, <code>physician_id</code>, <code>date</code>, <code>start_time</code>, <code>end_time</code>, <code>tab_name</code>, <code>eventblock_name</code>, <code>eventblock_text</code>. [Source](https://docs.iclinic.com.br/records.html)
      </div>

      <div id="helpIclinicZip" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-slate-700">
        <strong>Afya iClinic · ZIP recomendado:</strong> o export oficial do iClinic permite baixar dados em CSV ou XLS e selecionar pacientes e prontuários. O módulo foi preparado para receber o pacote ZIP contendo vários CSVs e localizar automaticamente os arquivos necessários. [Source](https://suporte.iclinic.com.br/pt-br/exportar-dados)
      </div>

      <button class="btn btn-primary w-full"><i class="bi bi-upload"></i> Importar agora</button>
    </form>
  </div>

  <div class="space-y-5">
    <div class="card p-6">
      <h3 class="font-bold mb-3">Templates</h3>
      <div class="space-y-2">
        <a href="template.php?tipo=pacientes" class="btn bg-slate-100 text-slate-700 w-full justify-start"><i class="bi bi-file-earmark-spreadsheet"></i> Template pacientes.csv</a>
        <a href="template.php?tipo=procedimentos" class="btn bg-slate-100 text-slate-700 w-full justify-start"><i class="bi bi-file-earmark-spreadsheet"></i> Template procedimentos.csv</a>
        <a href="template.php?tipo=pacientes_iclinic" class="btn bg-slate-100 text-slate-700 w-full justify-start"><i class="bi bi-file-person"></i> Exemplo patient.csv iClinic</a>
        <a href="template.php?tipo=prontuarios_iclinic" class="btn bg-slate-100 text-slate-700 w-full justify-start"><i class="bi bi-file-earmark-medical"></i> Exemplo records.csv iClinic</a>
      </div>
    </div>

    <div class="card p-6">
      <h3 class="font-bold mb-3">Como exportar no Afya iClinic</h3>
      <ol class="list-decimal ml-5 text-sm text-slate-600 space-y-2">
        <li>Acesse <strong>Configurações da conta → Exportar dados</strong>.</li>
        <li>Escolha profissionais, período e os tipos de dados.</li>
        <li>Selecione CSV como formato recomendado para migração.</li>
        <li>Baixe o arquivo quando o status mudar para <strong>Baixar</strong>.</li>
      </ol>
      <p class="text-xs text-slate-500 mt-3">O próprio suporte do iClinic recomenda CSV para migração e permite exportar pacientes e prontuários. [Source](https://suporte.iclinic.com.br/pt-br/exportar-dados)</p>
    </div>
  </div>
</div>

<div class="card mt-5 overflow-hidden">
  <h3 class="font-bold p-4 border-b">Histórico de Importações</h3>
  <table class="table-modern">
    <thead>
      <tr>
        <th>Data</th>
        <th>Tipo</th>
        <th>Total</th>
        <th>Sucesso</th>
        <th>Erros</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$imps): ?>
        <tr><td colspan="7" class="text-center py-8 text-slate-400">Sem importações.</td></tr>
      <?php else: foreach ($imps as $i): ?>
        <tr>
          <td><?= format_date($i['criado_em'], true) ?></td>
          <td><span class="badge badge-info"><?= e($typeLabels[$i['tipo']] ?? $i['tipo']) ?></span></td>
          <td><?= (int) $i['total_linhas'] ?></td>
          <td class="text-green-600 font-semibold"><?= (int) $i['sucesso'] ?></td>
          <td class="text-red-600 font-semibold"><?= (int) $i['erros'] ?></td>
          <td>
            <span class="badge badge-<?= $i['status'] === 'concluido' ? 'success' : ($i['status'] === 'erro' ? 'danger' : 'warning') ?>">
              <?= e($i['status']) ?>
            </span>
          </td>
          <td>
            <?php if ($i['log']): ?>
              <details class="text-xs">
                <summary class="text-primary cursor-pointer">Ver log</summary>
                <pre class="mt-2 bg-slate-50 p-2 rounded max-h-40 overflow-auto whitespace-pre-wrap"><?= e($i['log']) ?></pre>
              </details>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
function toggleImportHelp() {
  const tipo = document.getElementById('tipoImportacao').value;
  const file = document.getElementById('arquivoImportacao');
  const blocks = {
    helpCsvPacientes: tipo === 'pacientes',
    helpCsvProcedimentos: tipo === 'procedimentos',
    helpIclinicPacientes: tipo === 'pacientes_iclinic',
    helpIclinicProntuarios: tipo === 'prontuarios_iclinic',
    helpIclinicZip: tipo === 'pacote_iclinic_zip'
  };

  Object.keys(blocks).forEach(function(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('hidden', !blocks[id]);
  });

  file.setAttribute('accept', tipo === 'pacote_iclinic_zip' ? '.zip' : '.csv');
}

document.addEventListener('DOMContentLoaded', toggleImportHelp);
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
