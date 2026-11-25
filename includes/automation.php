<?php
/**
 * Recursos adicionais de comunicação, lembretes, Google Calendar, PWA e relatórios.
 */

if (!function_exists('company_feature_settings')) {
    function company_feature_settings($empresaId = null) {
        $empresaId = (int) ($empresaId ?: active_company_id());
        return [
            'google_calendar_enabled' => app_config_get('google_calendar_enabled', '1', $empresaId) === '1',
            'reminder_email_enabled' => app_config_get('reminder_email_enabled', '1', $empresaId) === '1',
            'reminder_push_enabled' => app_config_get('reminder_push_enabled', '1', $empresaId) === '1',
            'pwa_install_enabled' => app_config_get('pwa_install_enabled', '1', $empresaId) === '1',
            'reminder_hours_before' => max(1, min(168, (int) app_config_get('reminder_hours_before', '24', $empresaId))),
            'push_minutes_before' => max(5, min(1440, (int) app_config_get('push_minutes_before', '60', $empresaId))),
            'reminder_window_minutes' => max(5, min(180, (int) app_config_get('reminder_window_minutes', '30', $empresaId))),
            'smtp_ready' => smtp_is_configured() && phpmailer_is_available(),
        ];
    }
}

if (!function_exists('company_data_by_id')) {
    function company_data_by_id($empresaId) {
        $stmt = db()->prepare("SELECT * FROM empresas WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $empresaId]);
        return $stmt->fetch() ?: [];
    }
}

if (!function_exists('clinic_mail_layout')) {
    function clinic_mail_layout($title, $intro, $bodyHtml, $footerHtml = '', $companyName = null) {
        $empresaNome = trim((string) ($companyName ?? ''));
        if ($empresaNome === '') {
            $empresa = current_empresa();
            $empresaNome = trim((string) ($empresa['nome_fantasia'] ?? $empresa['razao_social'] ?? APP_NAME));
        }
        $footerHtml = $footerHtml !== '' ? '<div style="margin-top:18px;font-size:12px;color:#64748b">' . $footerHtml . '</div>' : '';

        return '<div style="font-family:Arial,Helvetica,sans-serif;background:#f8fafc;padding:24px">'
            . '<div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden">'
            . '<div style="padding:20px 24px;background:linear-gradient(135deg,#0ea5e9,#1d4ed8);color:#fff">'
            . '<div style="font-size:13px;opacity:.9">' . e($empresaNome) . '</div>'
            . '<h1 style="margin:8px 0 0;font-size:22px;line-height:1.3">' . e($title) . '</h1>'
            . '</div>'
            . '<div style="padding:24px">'
            . '<p style="margin:0 0 16px;color:#334155;font-size:15px;line-height:1.6">' . $intro . '</p>'
            . '<div style="color:#0f172a;font-size:14px;line-height:1.7">' . $bodyHtml . '</div>'
            . '<hr style="border:none;border-top:1px solid #e2e8f0;margin:22px 0">'
            . '<div style="font-size:12px;color:#64748b">Mensagem automática enviada por ' . e(APP_NAME) . ' em ' . date('d/m/Y H:i') . '.</div>'
            . $footerHtml
            . '</div></div></div>';
    }
}

if (!function_exists('appointment_fetch_full')) {
    function appointment_fetch_full($appointmentId, $empresaId = null) {
        $empresaId = (int) ($empresaId ?: active_company_id());
        $stmt = db()->prepare("SELECT a.*, p.nome AS paciente_nome, p.email AS paciente_email, p.celular AS paciente_celular,
            u.nome AS prof_nome, u.email AS prof_email, pr.nome AS proc_nome,
            e.razao_social AS empresa_razao_social, e.nome_fantasia AS empresa_nome_fantasia,
            t.id AS teleconsulta_id, t.sala_id, t.link_paciente, t.link_profissional
            FROM agendamentos a
            JOIN pacientes p ON p.id = a.paciente_id
            JOIN usuarios u ON u.id = a.profissional_id
            JOIN empresas e ON e.id = a.empresa_id
            LEFT JOIN procedimentos pr ON pr.id = a.procedimento_id
            LEFT JOIN teleconsultas t ON t.agendamento_id = a.id
            WHERE a.id = ? AND a.empresa_id = ?
            LIMIT 1");
        $stmt->execute([(int) $appointmentId, $empresaId]);
        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('appointment_google_calendar_link')) {
    function appointment_google_calendar_link(array $appointment) {
        return google_calendar_event_url(appointment_calendar_event($appointment));
    }
}

if (!function_exists('appointment_ics_attachment')) {
    function appointment_ics_attachment(array $appointment, $method = 'REQUEST') {
        $event = appointment_calendar_event($appointment);
        $event['method'] = strtoupper(trim((string) $method) ?: 'REQUEST');
        return [
            'string' => build_ics_event($event),
            'name' => 'consulta-' . (int) ($appointment['id'] ?? 0) . '.ics',
            'type' => 'text/calendar; charset=utf-8; method=' . $event['method'],
        ];
    }
}

if (!function_exists('smtp_auth_hint')) {
    function smtp_auth_hint() {
        return 'Se o e-mail usa 2FA no provedor, gere uma senha de aplicativo específica para SMTP. Em Hostinger/Gmail/Outlook isso costuma resolver falhas de autenticação.';
    }
}

if (!function_exists('send_login_2fa_code_email')) {
    function send_login_2fa_code_email(array $user, $codigo) {
        $codigo = trim((string) $codigo);
        $empresa = !empty($user['empresa_id']) ? company_data_by_id((int) $user['empresa_id']) : [];
        $empresaNome = trim((string) ($empresa['nome_fantasia'] ?? $empresa['razao_social'] ?? APP_NAME));
        $bodyHtml = clinic_mail_layout(
            'Código de verificação',
            'Recebemos uma tentativa de login com verificação em duas etapas.',
            '<p>Olá <strong>' . e($user['nome'] ?? 'usuário') . '</strong>,</p>'
            . '<p>Use o código abaixo para concluir seu acesso ao sistema:</p>'
            . '<div style="margin:18px 0;padding:18px;border:1px dashed #93c5fd;border-radius:14px;background:#eff6ff;text-align:center">'
            . '<div style="font-size:32px;letter-spacing:8px;font-weight:700;color:#1d4ed8">' . e($codigo) . '</div>'
            . '<div style="margin-top:8px;font-size:12px;color:#475569">Válido por 10 minutos</div>'
            . '</div>'
            . '<p>Se você não iniciou este login, altere sua senha e revise o método 2FA configurado.</p>',
            e(smtp_auth_hint()),
            $empresaNome
        );
        $bodyText = "Seu código de verificação ClinicApp: {$codigo}\n\nVálido por 10 minutos.\n\n" . smtp_auth_hint();

        $result = send_email((string) ($user['email'] ?? ''), 'Código de Acesso - ' . APP_NAME, $bodyHtml, [
            'to_name' => $user['nome'] ?? '',
            'from_name' => SMTP_FROM_NAME,
            'is_html' => true,
            'alt_body' => $bodyText,
        ]);

        if (!$result['ok'] && !empty($result['error'])) {
            $result['error'] .= ' ' . smtp_auth_hint();
        }
        return $result;
    }
}

if (!function_exists('appointment_reminder_copy')) {
    function appointment_reminder_copy(array $appointment) {
        $empresaNome = trim((string) ($appointment['empresa_nome_fantasia'] ?? $appointment['empresa_razao_social'] ?? APP_NAME));
        $event = appointment_calendar_event($appointment);
        $googleUrl = appointment_google_calendar_link($appointment);
        $isTele = ($appointment['modalidade'] ?? 'presencial') === 'telemedicina';
        $dataHora = format_date($appointment['data_hora_inicio'] ?? '', true);

        $summary = $isTele ? 'Lembrete de teleconsulta' : 'Lembrete de consulta';
        $subject = ($isTele ? 'Lembrete de Teleconsulta' : 'Lembrete de Consulta') . ' - ' . APP_NAME;

        $bodyHtml = '<p>Olá <strong>' . e($appointment['paciente_nome'] ?? $appointment['pn'] ?? 'Paciente') . '</strong>,</p>'
            . '<p>Este é um lembrete do seu atendimento agendado para <strong>' . e($dataHora) . '</strong>.</p>'
            . '<div style="margin:18px 0;padding:16px;border-radius:14px;background:#f8fafc;border:1px solid #e2e8f0">'
            . '<div><strong>Profissional:</strong> ' . e($appointment['prof_nome'] ?? 'Profissional') . '</div>'
            . '<div><strong>Procedimento:</strong> ' . e($appointment['proc_nome'] ?? 'Consulta') . '</div>'
            . '<div><strong>Modalidade:</strong> ' . e(ucfirst((string) ($appointment['modalidade'] ?? 'presencial'))) . '</div>'
            . '</div>';

        if ($isTele && !empty($appointment['link_paciente'])) {
            $bodyHtml .= '<p><a href="' . e($appointment['link_paciente']) . '" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#16a34a;color:#fff;text-decoration:none;font-weight:700">Entrar na sala da teleconsulta</a></p>';
        }

        $bodyHtml .= '<p><a href="' . e($googleUrl) . '" style="display:inline-block;padding:10px 16px;border-radius:10px;background:#0ea5e9;color:#fff;text-decoration:none;font-weight:600">Adicionar ao Google Calendar</a></p>'
            . '<p>Também enviamos um arquivo <strong>.ics</strong> em anexo para importar em outros calendários.</p>'
            . '<p>Atenciosamente,<br><strong>' . e($empresaNome) . '</strong></p>';

        $bodyText = $summary . "\n\n"
            . 'Paciente: ' . ($appointment['paciente_nome'] ?? $appointment['pn'] ?? 'Paciente') . "\n"
            . 'Data/Hora: ' . $dataHora . "\n"
            . 'Profissional: ' . ($appointment['prof_nome'] ?? 'Profissional') . "\n"
            . 'Procedimento: ' . ($appointment['proc_nome'] ?? 'Consulta') . "\n"
            . 'Modalidade: ' . ucfirst((string) ($appointment['modalidade'] ?? 'presencial')) . "\n"
            . (!empty($appointment['link_paciente']) ? ('Link: ' . $appointment['link_paciente'] . "\n") : '')
            . 'Google Calendar: ' . $googleUrl . "\n";

        return [
            'subject' => $subject,
            'summary' => $summary,
            'body_html' => clinic_mail_layout($summary, 'Organize seu horário com antecedência e confirme os detalhes do atendimento.', $bodyHtml, '', $empresaNome),
            'body_text' => $bodyText,
            'google_url' => $googleUrl,
            'event' => $event,
        ];
    }
}

if (!function_exists('send_appointment_reminder_email')) {
    function send_appointment_reminder_email(array $appointment, array $options = []) {
        $copy = appointment_reminder_copy($appointment);
        $attachments = $options['attachments'] ?? [];
        $attachments[] = appointment_ics_attachment($appointment, 'REQUEST');

        return send_email((string) ($appointment['paciente_email'] ?? $appointment['email'] ?? ''), $copy['subject'], $copy['body_html'], [
            'to_name' => $appointment['paciente_nome'] ?? $appointment['pn'] ?? '',
            'from_name' => SMTP_FROM_NAME,
            'is_html' => true,
            'alt_body' => $copy['body_text'],
            'attachments' => $attachments,
        ]);
    }
}

if (!function_exists('send_telemedicine_link_email')) {
    function send_telemedicine_link_email(array $appointment) {
        $copy = appointment_reminder_copy($appointment);
        $copy['subject'] = 'Link da sua Teleconsulta - ' . APP_NAME;
        return send_email((string) ($appointment['paciente_email'] ?? $appointment['email'] ?? ''), $copy['subject'], $copy['body_html'], [
            'to_name' => $appointment['paciente_nome'] ?? $appointment['pn'] ?? '',
            'from_name' => SMTP_FROM_NAME,
            'is_html' => true,
            'alt_body' => $copy['body_text'],
            'attachments' => [appointment_ics_attachment($appointment, 'REQUEST')],
        ]);
    }
}

if (!function_exists('queue_system_notification')) {
    function queue_system_notification(array $data) {
        if (!db_table_exists('notificacoes_sistema')) {
            return false;
        }

        $stmt = db()->prepare("INSERT INTO notificacoes_sistema
            (empresa_id, usuario_id, tipo, titulo, mensagem, url, referencia_tabela, referencia_id, dedupe_key, agendar_para, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), mensagem = VALUES(mensagem), url = VALUES(url), agendar_para = VALUES(agendar_para)");
        return $stmt->execute([
            (int) ($data['empresa_id'] ?? active_company_id()),
            isset($data['usuario_id']) ? (int) $data['usuario_id'] : null,
            trim((string) ($data['tipo'] ?? 'lembrete')),
            trim((string) ($data['titulo'] ?? 'Lembrete')),
            trim((string) ($data['mensagem'] ?? '')),
            trim((string) ($data['url'] ?? '')),
            trim((string) ($data['referencia_tabela'] ?? 'agendamentos')),
            (int) ($data['referencia_id'] ?? 0),
            trim((string) ($data['dedupe_key'] ?? bin2hex(random_bytes(8)))),
            trim((string) ($data['agendar_para'] ?? date('Y-m-d H:i:s'))),
        ]);
    }
}

if (!function_exists('consume_due_system_notifications')) {
    function consume_due_system_notifications($usuarioId, $empresaId = null, $limit = 10) {
        if (!db_table_exists('notificacoes_sistema')) {
            return [];
        }

        $empresaId = (int) ($empresaId ?: active_company_id());
        $usuarioId = (int) $usuarioId;
        $limit = max(1, min(20, (int) $limit));

        $stmt = db()->prepare("SELECT * FROM notificacoes_sistema
            WHERE empresa_id = ?
              AND (usuario_id IS NULL OR usuario_id = ?)
              AND agendar_para <= NOW()
              AND entregue_em IS NULL
            ORDER BY agendar_para ASC
            LIMIT {$limit}");
        $stmt->execute([$empresaId, $usuarioId]);
        $items = $stmt->fetchAll() ?: [];
        if (!$items) {
            return [];
        }

        $ids = array_map(static function ($item) { return (int) $item['id']; }, $items);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        db()->prepare("UPDATE notificacoes_sistema SET entregue_em = NOW() WHERE id IN ({$placeholders})")
            ->execute($ids);

        return array_map(static function ($item) {
            return [
                'id' => (int) $item['id'],
                'title' => (string) $item['titulo'],
                'body' => (string) $item['mensagem'],
                'url' => (string) $item['url'],
                'type' => (string) $item['tipo'],
                'scheduled_at' => (string) $item['agendar_para'],
            ];
        }, $items);
    }
}

if (!function_exists('cron_dispatch_due_reminders')) {
    function cron_dispatch_due_reminders() {
        $stats = [
            'emails_sent' => 0,
            'emails_failed' => 0,
            'push_queued' => 0,
            'checked_companies' => 0,
        ];

        $empresaIds = db()->query("SELECT DISTINCT empresa_id FROM agendamentos ORDER BY empresa_id")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($empresaIds as $empresaId) {
            $empresaId = (int) $empresaId;
            $settings = company_feature_settings($empresaId);
            $stats['checked_companies']++;

            $emailStart = date('Y-m-d H:i:s', strtotime('+' . (int) $settings['reminder_hours_before'] . ' hours'));
            $emailEnd = date('Y-m-d H:i:s', strtotime($emailStart . ' +' . (int) $settings['reminder_window_minutes'] . ' minutes'));
            $pushStart = date('Y-m-d H:i:s', strtotime('+' . (int) $settings['push_minutes_before'] . ' minutes'));
            $pushEnd = date('Y-m-d H:i:s', strtotime($pushStart . ' +15 minutes'));

            if ($settings['reminder_email_enabled']) {
                $stmt = db()->prepare("SELECT a.id FROM agendamentos a
                    WHERE a.empresa_id = ?
                      AND a.status IN ('agendado','confirmado')
                      AND a.data_hora_inicio BETWEEN ? AND ?
                      AND COALESCE(a.lembrete_email_enviado, 0) = 0");
                $stmt->execute([$empresaId, $emailStart, $emailEnd]);
                $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                foreach ($ids as $id) {
                    $appointment = appointment_fetch_full((int) $id, $empresaId);
                    if (!$appointment || empty($appointment['paciente_email'])) {
                        continue;
                    }
                    $result = send_appointment_reminder_email($appointment);
                    if ($result['ok']) {
                        db()->prepare("UPDATE agendamentos SET lembrete_email_enviado = 1, ultimo_lembrete_em = NOW() WHERE id = ?")->execute([(int) $id]);
                        log_action('agenda_lembrete_email', 'agendamentos', (int) $id, 'Lembrete automático enviado por e-mail.');
                        $stats['emails_sent']++;
                    } else {
                        $stats['emails_failed']++;
                        log_action('cron_email_falha', 'agendamentos', (int) $id, (string) ($result['error'] ?? 'Falha ao enviar lembrete por e-mail.'));
                    }
                }
            }

            if ($settings['reminder_push_enabled']) {
                $stmt = db()->prepare("SELECT a.id, a.profissional_id FROM agendamentos a
                    WHERE a.empresa_id = ?
                      AND a.status IN ('agendado','confirmado')
                      AND a.data_hora_inicio BETWEEN ? AND ?");
                $stmt->execute([$empresaId, $pushStart, $pushEnd]);
                $rows = $stmt->fetchAll() ?: [];
                foreach ($rows as $row) {
                    $appointment = appointment_fetch_full((int) $row['id'], $empresaId);
                    if (!$appointment) {
                        continue;
                    }
                    $message = 'Consulta de ' . ($appointment['paciente_nome'] ?? 'Paciente') . ' às ' . date('H:i', strtotime((string) $appointment['data_hora_inicio'])) . '.';
                    $ok = queue_system_notification([
                        'empresa_id' => $empresaId,
                        'usuario_id' => (int) ($appointment['profissional_id'] ?? 0),
                        'tipo' => (($appointment['modalidade'] ?? 'presencial') === 'telemedicina' ? 'telemedicina' : 'agenda'),
                        'titulo' => (($appointment['modalidade'] ?? 'presencial') === 'telemedicina' ? 'Teleconsulta próxima' : 'Consulta próxima'),
                        'mensagem' => $message,
                        'url' => APP_URL . '/modules/agenda/ver.php?id=' . (int) $appointment['id'],
                        'referencia_tabela' => 'agendamentos',
                        'referencia_id' => (int) $appointment['id'],
                        'dedupe_key' => 'push-' . $empresaId . '-' . (int) $appointment['id'] . '-' . date('YmdHi', strtotime($pushStart)),
                        'agendar_para' => $pushStart,
                    ]);
                    if ($ok) {
                        $stats['push_queued']++;
                    }
                }
            }
        }

        return $stats;
    }
}

if (!function_exists('monthly_financial_report')) {
    function monthly_financial_report($empresaId, $month) {
        $empresaId = (int) $empresaId;
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $month) ? $month : date('Y-m');
        $ini = $month . '-01';
        $fim = date('Y-m-t', strtotime($ini));

        $stmt = db()->prepare("SELECT
            SUM(CASE WHEN tipo='receita' AND status='pago' THEN valor ELSE 0 END) AS receitas,
            SUM(CASE WHEN tipo='despesa' AND status='pago' THEN valor ELSE 0 END) AS despesas,
            SUM(CASE WHEN tipo='receita' AND status IN ('pendente','atrasado') THEN valor ELSE 0 END) AS receitas_em_aberto,
            SUM(CASE WHEN tipo='despesa' AND status IN ('pendente','atrasado') THEN valor ELSE 0 END) AS despesas_em_aberto,
            COUNT(CASE WHEN tipo='receita' THEN 1 END) AS lancamentos_receita,
            COUNT(CASE WHEN tipo='despesa' THEN 1 END) AS lancamentos_despesa
            FROM contas_financeiras
            WHERE empresa_id = ? AND data_vencimento BETWEEN ? AND ?");
        $stmt->execute([$empresaId, $ini, $fim]);
        $totals = $stmt->fetch() ?: [];

        $cats = db()->prepare("SELECT c.tipo, COALESCE(cat.nome, 'Sem categoria') AS categoria, SUM(c.valor) AS total
            FROM contas_financeiras c
            LEFT JOIN categorias_financeiras cat ON cat.id = c.categoria_id
            WHERE c.empresa_id = ? AND c.data_vencimento BETWEEN ? AND ? AND c.status <> 'cancelado'
            GROUP BY c.tipo, categoria
            ORDER BY total DESC");
        $cats->execute([$empresaId, $ini, $fim]);

        $timeline = db()->prepare("SELECT DATE_FORMAT(data_vencimento, '%Y-%m-%d') AS dia,
            SUM(CASE WHEN tipo='receita' THEN valor ELSE 0 END) AS receitas,
            SUM(CASE WHEN tipo='despesa' THEN valor ELSE 0 END) AS despesas
            FROM contas_financeiras
            WHERE empresa_id = ? AND data_vencimento BETWEEN ? AND ?
            GROUP BY dia ORDER BY dia");
        $timeline->execute([$empresaId, $ini, $fim]);

        return [
            'month' => $month,
            'start' => $ini,
            'end' => $fim,
            'totals' => $totals,
            'categories' => $cats->fetchAll() ?: [],
            'timeline' => $timeline->fetchAll() ?: [],
        ];
    }
}
