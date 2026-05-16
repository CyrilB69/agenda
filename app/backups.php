<?php
declare(strict_types=1);

function backup_table_names(): array
{
    return [
        'users',
        'rooms',
        'material_items',
        'reservations',
        'charter_acceptances',
        'blackout_periods',
        'material_requests',
        'material_request_items',
        'audit_logs',
        'settings',
    ];
}

function backup_payload(): array
{
    $tables = [];

    foreach (backup_table_names() as $table) {
        $tables[$table] = db()->query('SELECT * FROM ' . $table . ' ORDER BY 1 ASC')->fetchAll();
    }

    return [
        'meta' => [
            'app' => 'agenda-communal',
            'version' => 5,
            'created_at' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'database_driver' => app_config('database.driver', 'sqlite'),
        ],
        'tables' => $tables,
        'files' => backup_managed_files(),
    ];
}

function backup_document(): array
{
    $json = json_encode(backup_payload(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('La sauvegarde n’a pas pu être générée.');
    }

    return [
        'filename' => 'agenda-backup-' . date('Ymd-His') . '.json',
        'content' => $json,
    ];
}

function backup_managed_files(): array
{
    $files = [];
    $locations = [
        'uploads/branding/' => __DIR__ . '/../public/uploads/branding',
        'storage/charters/' => __DIR__ . '/../storage/charters',
    ];

    foreach ($locations as $prefix => $directoryPath) {
        $directory = realpath($directoryPath);
        if (!$directory) {
            continue;
        }

        foreach (glob($directory . '/*') ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }

            $name = basename($file);
            if ($name === '.gitkeep') {
                continue;
            }

            $data = file_get_contents($file);
            if ($data !== false) {
                $files[$prefix . $name] = base64_encode($data);
            }
        }
    }

    return $files;
}

function send_backup_download(): never
{
    try {
        $backup = backup_document();
    } catch (Throwable) {
        flash('error', 'La sauvegarde n’a pas pu être générée.');
        redirect('admin_backup');
    }

    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
    header('Content-Length: ' . strlen($backup['content']));
    echo $backup['content'];
    exit;
}

function automatic_backup_frequency_options(): array
{
    return [
        'daily' => 'Tous les jours',
        'weekly' => 'Chaque semaine',
        'monthly' => 'Chaque mois',
    ];
}

function normalize_automatic_backup_frequency(string $frequency): string
{
    return array_key_exists($frequency, automatic_backup_frequency_options()) ? $frequency : 'daily';
}

function automatic_backup_default_recipients(): string
{
    $configured = app_config('backup.auto_recipients', []);

    return is_array($configured) ? implode("\n", $configured) : (string) $configured;
}

function automatic_backup_settings(bool $ensureToken = true): array
{
    $token = setting_value('backup.auto.cron_token', (string) app_config('backup.cron_token', ''));

    if ($ensureToken && $token === '') {
        $token = bin2hex(random_bytes(24));
        save_setting('backup.auto.cron_token', $token);
    }

    return [
        'enabled' => setting_value('backup.auto.enabled', app_config('backup.auto_enabled', false) ? '1' : '0') === '1',
        'recipients' => setting_value('backup.auto.recipients', automatic_backup_default_recipients()),
        'frequency' => normalize_automatic_backup_frequency(setting_value('backup.auto.frequency', (string) app_config('backup.auto_frequency', 'daily'))),
        'cron_token' => $token,
        'last_run_at' => setting_value('backup.auto.last_run_at', ''),
        'last_attempt_at' => setting_value('backup.auto.last_attempt_at', ''),
        'last_status' => setting_value('backup.auto.last_status', ''),
        'last_error' => setting_value('backup.auto.last_error', ''),
    ];
}

function automatic_backup_recipients(array $settings): array
{
    return notification_recipients(preg_split('/[\s,;]+/', (string) $settings['recipients']) ?: []);
}

function automatic_backup_next_due_at(array $settings): ?DateTimeImmutable
{
    if ((string) $settings['last_run_at'] === '') {
        return null;
    }

    $lastRun = local_datetime((string) $settings['last_run_at']);
    $interval = [
        'daily' => '+1 day',
        'weekly' => '+1 week',
        'monthly' => '+1 month',
    ][$settings['frequency']] ?? '+1 day';

    return $lastRun->modify($interval);
}

function automatic_backup_is_due(array $settings): bool
{
    $nextDueAt = automatic_backup_next_due_at($settings);

    return $nextDueAt === null || $nextDueAt <= new DateTimeImmutable();
}

function save_automatic_backup_result(string $status, string $error = ''): void
{
    $now = db_datetime(new DateTimeImmutable());
    save_setting('backup.auto.last_attempt_at', $now);
    save_setting('backup.auto.last_status', $status);
    save_setting('backup.auto.last_error', $error);

    if ($status === 'success') {
        save_setting('backup.auto.last_run_at', $now);
    }
}

function automatic_backup_token_is_valid(string $token): bool
{
    $settings = automatic_backup_settings(false);
    $expected = (string) $settings['cron_token'];

    return $expected !== '' && hash_equals($expected, $token);
}

function run_automatic_backup(bool $force = false): array
{
    $settings = automatic_backup_settings(false);

    if (!$settings['enabled']) {
        return ['status' => 'disabled', 'sent' => false, 'message' => 'La sauvegarde automatique est désactivée.'];
    }

    if (!$force && !automatic_backup_is_due($settings)) {
        $nextDueAt = automatic_backup_next_due_at($settings);

        return [
            'status' => 'skipped',
            'sent' => false,
            'message' => 'Aucune sauvegarde à envoyer avant ' . ($nextDueAt ? human_datetime($nextDueAt) : 'la prochaine exécution') . '.',
        ];
    }

    if (!mail_notifications_enabled()) {
        $message = 'Les notifications e-mail sont désactivées.';
        save_automatic_backup_result('failed', $message);
        audit_log('automatic_backup_failed', 'backup', null, 'Sauvegarde automatique échouée.', ['error' => $message]);

        return ['status' => 'failed', 'sent' => false, 'message' => $message];
    }

    $recipients = automatic_backup_recipients($settings);
    if (!$recipients) {
        $message = 'Aucun destinataire valide pour la sauvegarde automatique.';
        save_automatic_backup_result('failed', $message);
        audit_log('automatic_backup_failed', 'backup', null, 'Sauvegarde automatique échouée.', ['error' => $message]);

        return ['status' => 'failed', 'sent' => false, 'message' => $message];
    }

    try {
        $backup = backup_document();
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        save_automatic_backup_result('failed', $message);
        audit_log('automatic_backup_failed', 'backup', null, 'Sauvegarde automatique échouée.', ['error' => $message]);

        return ['status' => 'failed', 'sent' => false, 'message' => $message];
    }

    $appName = branding_settings()['entity_name'];
    $subject = '[' . $appName . '] Sauvegarde automatique du ' . date('d/m/Y H:i');
    $body = "Bonjour,\n\nVeuillez trouver en pièce jointe la sauvegarde automatique de l’agenda.\n\n"
        . 'Fichier : ' . $backup['filename'] . "\n"
        . 'Date : ' . date('d/m/Y H:i') . "\n\n"
        . "Cordialement,\n" . $appName;

    $sent = send_notification_email_with_attachments($recipients, $subject, $body, [[
        'filename' => $backup['filename'],
        'content_type' => 'application/json',
        'content' => $backup['content'],
    ]]);

    if (!$sent) {
        $message = 'L’e-mail de sauvegarde n’a pas pu être envoyé.';
        save_automatic_backup_result('failed', $message);
        audit_log('automatic_backup_failed', 'backup', null, 'Sauvegarde automatique échouée.', [
            'error' => $message,
            'recipients' => $recipients,
            'filename' => $backup['filename'],
        ]);

        return ['status' => 'failed', 'sent' => false, 'message' => $message];
    }

    save_automatic_backup_result('success');
    audit_log('automatic_backup_sent', 'backup', null, 'Sauvegarde automatique envoyée.', [
        'recipients' => $recipients,
        'filename' => $backup['filename'],
    ]);

    return ['status' => 'success', 'sent' => true, 'message' => 'Sauvegarde automatique envoyée.'];
}

function restore_backup_from_upload(string $field): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return ['Aucun fichier de sauvegarde reçu.'];
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['Le fichier de sauvegarde n’a pas pu être téléversé.'];
    }

    if ((int) ($file['size'] ?? 0) > 20 * 1024 * 1024) {
        return ['Le fichier de sauvegarde ne doit pas dépasser 20 Mo.'];
    }

    $json = file_get_contents((string) $file['tmp_name']);
    if ($json === false) {
        return ['Le fichier de sauvegarde est illisible.'];
    }

    $payload = json_decode($json, true);
    if (!is_array($payload) || ($payload['meta']['app'] ?? '') !== 'agenda-communal' || !isset($payload['tables'])) {
        return ['Le fichier ne correspond pas à une sauvegarde de cet agenda.'];
    }

    return restore_backup_payload($payload);
}

function restore_backup_payload(array $payload): array
{
    $tables = $payload['tables'];
    foreach (backup_table_names() as $table) {
        if (!isset($tables[$table]) || !is_array($tables[$table])) {
            if (in_array($table, ['material_items', 'material_requests', 'material_request_items', 'audit_logs', 'charter_acceptances'], true)) {
                $tables[$table] = [];
                continue;
            }

            return ["La table {$table} est absente de la sauvegarde."];
        }
    }

    $pdo = db();
    set_foreign_key_checks(false);
    $pdo->beginTransaction();

    try {
        foreach (array_reverse(backup_table_names()) as $table) {
            $pdo->exec('DELETE FROM ' . $table);
        }

        foreach (backup_table_names() as $table) {
            restore_table_rows($table, $tables[$table]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_foreign_key_checks(true);
        return ['La restauration a échoué. Vérifiez que le fichier correspond à la version actuelle.'];
    }

    set_foreign_key_checks(true);
    restore_managed_files(is_array($payload['files'] ?? null) ? $payload['files'] : []);

    return [];
}

function restore_table_rows(string $table, array $rows): void
{
    if (!$rows) {
        return;
    }

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $allowedColumns = backup_table_columns($table);
        $row = array_intersect_key($row, array_flip($allowedColumns));
        if (!$row) {
            continue;
        }

        $columns = array_keys($row);
        $columnList = implode(', ', array_map(static fn (string $column): string => $column, $columns));
        $placeholders = implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns));
        $stmt = db()->prepare("INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})");

        $params = [];
        foreach ($row as $column => $value) {
            $params[':' . $column] = $value;
        }
        $stmt->execute($params);
    }
}

function backup_table_columns(string $table): array
{
    return [
        'users' => ['id', 'name', 'email', 'password_hash', 'role', 'active', 'registration_status', 'registration_notes', 'reviewed_at', 'created_at'],
        'rooms' => ['id', 'name', 'description', 'capacity', 'color', 'active', 'created_at'],
        'material_items' => ['id', 'name', 'category', 'description', 'quantity_total', 'active', 'created_at'],
        'reservations' => ['id', 'room_id', 'user_id', 'title', 'notes', 'starts_at', 'ends_at', 'status', 'created_at'],
        'charter_acceptances' => ['id', 'user_id', 'reservation_id', 'charter_path', 'accepted_at', 'ip_address', 'user_agent'],
        'blackout_periods' => ['id', 'room_id', 'title', 'notes', 'starts_at', 'ends_at', 'active', 'created_by_id', 'created_at'],
        'material_requests' => ['id', 'user_id', 'event_title', 'event_location', 'notes', 'starts_at', 'ends_at', 'status', 'admin_notes', 'validated_by_id', 'validated_at', 'created_at'],
        'material_request_items' => ['request_id', 'material_item_id', 'quantity'],
        'audit_logs' => ['id', 'user_id', 'action', 'entity_type', 'entity_id', 'message', 'details', 'ip_address', 'user_agent', 'created_at'],
        'settings' => ['setting_key', 'setting_value'],
    ][$table] ?? [];
}

function restore_managed_files(array $files): void
{
    foreach ($files as $path => $encoded) {
        $path = (string) $path;
        if (str_starts_with($path, 'uploads/branding/')) {
            $directory = __DIR__ . '/../public/uploads/branding';
        } elseif (str_starts_with($path, 'storage/charters/')) {
            $directory = __DIR__ . '/../storage/charters';
        } else {
            continue;
        }

        $name = basename($path);
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $name)) {
            continue;
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $data = base64_decode((string) $encoded, true);
        if ($data !== false) {
            file_put_contents($directory . '/' . $name, $data);
        }
    }
}

function set_foreign_key_checks(bool $enabled): void
{
    if (is_mysql()) {
        db()->exec('SET FOREIGN_KEY_CHECKS = ' . ($enabled ? '1' : '0'));
        return;
    }

    db()->exec('PRAGMA foreign_keys = ' . ($enabled ? 'ON' : 'OFF'));
}
