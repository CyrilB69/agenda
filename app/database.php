<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $driver = app_config('database.driver', 'sqlite');

    if ($driver === 'mysql') {
        $host = app_config('database.host', 'localhost');
        $port = app_config('database.port', '3306');
        $name = app_config('database.name', '');
        $user = app_config('database.user', '');
        $password = app_config('database.password', '');
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $password);
    } else {
        $path = app_config('database.path', __DIR__ . '/../storage/agenda.sqlite');
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $path);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 10000');
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}

function is_mysql(): bool
{
    return app_config('database.driver', 'sqlite') === 'mysql';
}

function reservation_lock_name(): string
{
    $identifier = app_config('database.driver', 'sqlite') . ':'
        . app_config('database.name', '')
        . app_config('database.path', '');

    return 'agenda_reservations_' . substr(hash('sha256', $identifier), 0, 32);
}

function acquire_reservation_lock(int $timeoutSeconds = 10): bool
{
    if (!is_mysql()) {
        return true;
    }

    $stmt = db()->prepare('SELECT GET_LOCK(:lock_name, :timeout_seconds)');
    $stmt->bindValue(':lock_name', reservation_lock_name());
    $stmt->bindValue(':timeout_seconds', $timeoutSeconds, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn() === 1;
}

function release_reservation_lock(): void
{
    if (!is_mysql()) {
        return;
    }

    try {
        $stmt = db()->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $stmt->execute([':lock_name' => reservation_lock_name()]);
    } catch (Throwable) {
        // Le verrou sera libéré automatiquement à la fermeture de la connexion.
    }
}

function begin_reservation_critical_section(int $timeoutSeconds = 10): bool
{
    $pdo = db();
    if ($pdo->inTransaction()) {
        return false;
    }

    if (is_mysql()) {
        if (!acquire_reservation_lock($timeoutSeconds)) {
            return false;
        }

        try {
            $pdo->beginTransaction();
            return true;
        } catch (Throwable $exception) {
            release_reservation_lock();
            throw $exception;
        }
    }

    try {
        $pdo->exec('BEGIN IMMEDIATE');
        return true;
    } catch (Throwable) {
        return false;
    }
}

function commit_reservation_critical_section(): void
{
    $pdo = db();
    try {
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    } finally {
        release_reservation_lock();
    }
}

function rollback_reservation_critical_section(): void
{
    $pdo = db();
    try {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    } finally {
        release_reservation_lock();
    }
}

function migrate_database(): void
{
    $id = is_mysql()
        ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY'
        : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $bool = is_mysql() ? 'TINYINT(1)' : 'INTEGER';

    db()->exec("
        CREATE TABLE IF NOT EXISTS users (
            id {$id},
            name VARCHAR(160) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'association',
            active {$bool} NOT NULL DEFAULT 1,
            registration_status VARCHAR(20) NOT NULL DEFAULT 'approved',
            registration_notes TEXT NULL,
            reviewed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS rooms (
            id {$id},
            name VARCHAR(160) NOT NULL,
            description TEXT NULL,
            capacity INT NULL,
            color VARCHAR(7) NOT NULL DEFAULT '#2563eb',
            active {$bool} NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS reservations (
            id {$id},
            room_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            title VARCHAR(180) NOT NULL,
            notes TEXT NULL,
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'approved',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id {$id},
            user_id INT UNSIGNED NOT NULL,
            token_hash VARCHAR(255) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id {$id},
            email_hash VARCHAR(64) NULL,
            ip_address VARCHAR(64) NULL,
            successful {$bool} NOT NULL DEFAULT 0,
            attempted_at DATETIME NOT NULL
        )
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS charter_acceptances (
            id {$id},
            user_id INT UNSIGNED NOT NULL,
            reservation_id INT UNSIGNED NULL,
            charter_path VARCHAR(255) NOT NULL,
            accepted_at DATETIME NOT NULL,
            ip_address VARCHAR(64) NULL,
            user_agent TEXT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
        )
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS blackout_periods (
            id {$id},
            room_id INT UNSIGNED NULL,
            title VARCHAR(180) NOT NULL,
            notes TEXT NULL,
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NOT NULL,
            active {$bool} NOT NULL DEFAULT 1,
            created_by_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS material_items (
            id {$id},
            name VARCHAR(160) NOT NULL,
            category VARCHAR(80) NOT NULL DEFAULT 'autre',
            description TEXT NULL,
            quantity_total INT NOT NULL DEFAULT 0,
            active {$bool} NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS material_requests (
            id {$id},
            user_id INT UNSIGNED NOT NULL,
            event_title VARCHAR(180) NOT NULL,
            event_location VARCHAR(180) NULL,
            notes TEXT NULL,
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            admin_notes TEXT NULL,
            validated_by_id INT UNSIGNED NULL,
            validated_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (validated_by_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS material_request_items (
            request_id INT UNSIGNED NOT NULL,
            material_item_id INT UNSIGNED NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            PRIMARY KEY (request_id, material_item_id),
            FOREIGN KEY (request_id) REFERENCES material_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (material_item_id) REFERENCES material_items(id) ON DELETE RESTRICT
        )
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id {$id},
            user_id INT UNSIGNED NULL,
            action VARCHAR(80) NOT NULL,
            entity_type VARCHAR(80) NULL,
            entity_id INT UNSIGNED NULL,
            message TEXT NULL,
            details TEXT NULL,
            ip_address VARCHAR(64) NULL,
            user_agent TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
            setting_value TEXT NULL
        )
    ");

    ensure_user_registration_columns();
    db()->exec("UPDATE users SET role = 'association' WHERE role = 'user'");
    seed_default_rooms();
    seed_default_material_items();
}

function table_has_column(string $table, string $column): bool
{
    if (is_mysql()) {
        $stmt = db()->prepare('
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
              AND COLUMN_NAME = :column
        ');
        $stmt->execute([':table' => $table, ':column' => $column]);

        return (int) $stmt->fetchColumn() > 0;
    }

    $stmt = db()->query('PRAGMA table_info(' . $table . ')');
    foreach ($stmt->fetchAll() as $row) {
        if (($row['name'] ?? '') === $column) {
            return true;
        }
    }

    return false;
}

function add_column_if_missing(string $table, string $column, string $definition): void
{
    if (table_has_column($table, $column)) {
        return;
    }

    try {
        db()->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    } catch (PDOException $exception) {
        $message = strtolower($exception->getMessage());
        if (str_contains($message, 'duplicate column') || str_contains($message, 'already exists')) {
            return;
        }

        throw $exception;
    }
}

function ensure_user_registration_columns(): void
{
    add_column_if_missing('users', 'registration_status', "VARCHAR(20) NOT NULL DEFAULT 'approved'");
    add_column_if_missing('users', 'registration_notes', 'TEXT NULL');
    add_column_if_missing('users', 'reviewed_at', 'DATETIME NULL');
    db()->exec("UPDATE users SET registration_status = 'approved' WHERE registration_status IS NULL OR registration_status = ''");
}

function seed_default_rooms(): void
{
    $count = (int) db()->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $rooms = [
        ['Salle polyvalente', 'Salle principale pour événements et réunions publiques.', 120, '#2563eb'],
        ['Salle des fêtes', 'Grande salle communale avec accès traiteur.', 200, '#0f766e'],
        ['Salle de réunion', 'Petite salle adaptée aux associations et commissions.', 30, '#b45309'],
    ];

    $stmt = db()->prepare('INSERT INTO rooms (name, description, capacity, color) VALUES (:name, :description, :capacity, :color)');
    foreach ($rooms as [$name, $description, $capacity, $color]) {
        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':capacity' => $capacity,
            ':color' => $color,
        ]);
    }
}

function seed_default_material_items(): void
{
    $count = (int) db()->query('SELECT COUNT(*) FROM material_items')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $items = [
        ['Tables pliantes', 'tables_bancs', 'Tables pour événements associatifs.', 0],
        ['Bancs', 'tables_bancs', 'Bancs pour manifestations communales.', 0],
        ['Barnum 3x3', 'barnums', 'Barnum pliant.', 0],
        ['Grilles d’exposition', 'grilles', 'Grilles pour affichage ou exposition.', 0],
        ['Barrières Vauban', 'barrieres', 'Barrières de sécurité.', 0],
    ];

    $stmt = db()->prepare('
        INSERT INTO material_items (name, category, description, quantity_total, active)
        VALUES (:name, :category, :description, :quantity_total, 0)
    ');

    foreach ($items as [$name, $category, $description, $quantity]) {
        $stmt->execute([
            ':name' => $name,
            ':category' => normalize_material_category($category),
            ':description' => $description,
            ':quantity_total' => $quantity,
        ]);
    }
}

function first_admin_exists(): bool
{
    $stmt = db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");

    return (int) $stmt->fetchColumn() > 0;
}

function admin_count(bool $activeOnly = false, ?int $excludingId = null): int
{
    $sql = "SELECT COUNT(*) FROM users WHERE role = 'admin'";
    $params = [];

    if ($activeOnly) {
        $sql .= ' AND active = 1';
    }

    if ($excludingId !== null) {
        $sql .= ' AND id <> :id';
        $params[':id'] = $excludingId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function create_user(
    string $name,
    string $email,
    string $password,
    string $role = 'association',
    bool $active = true,
    string $registrationStatus = 'approved',
    ?string $registrationNotes = null
): int
{
    $stmt = db()->prepare('
        INSERT INTO users (name, email, password_hash, role, active, registration_status, registration_notes, reviewed_at)
        VALUES (:name, :email, :password_hash, :role, :active, :registration_status, :registration_notes, :reviewed_at)
    ');
    $registrationStatus = normalize_registration_status($registrationStatus);
    $stmt->execute([
        ':name' => $name,
        ':email' => strtolower($email),
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => normalize_user_role($role),
        ':active' => $active ? 1 : 0,
        ':registration_status' => $registrationStatus,
        ':registration_notes' => $registrationNotes,
        ':reviewed_at' => $registrationStatus === 'pending' ? null : db_datetime(new DateTimeImmutable()),
    ]);

    return (int) db()->lastInsertId();
}

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => strtolower($email)]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function login_attempt_email_hash(string $email): ?string
{
    $email = strtolower(trim($email));

    return $email !== '' ? hash('sha256', $email) : null;
}

function login_attempt_ip(): ?string
{
    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);

    return $ip !== '' ? $ip : null;
}

function login_throttle_settings(): array
{
    return [
        'max_attempts' => max(1, (int) app_config('security.login_max_attempts', 5)),
        'window_minutes' => max(1, (int) app_config('security.login_window_minutes', 15)),
        'lock_minutes' => max(1, (int) app_config('security.login_lock_minutes', 15)),
    ];
}

function purge_old_login_attempts(): void
{
    $settings = login_throttle_settings();
    $keepMinutes = max($settings['window_minutes'] + $settings['lock_minutes'], 60);
    $cutoff = db_datetime((new DateTimeImmutable())->modify('-' . $keepMinutes . ' minutes'));
    $stmt = db()->prepare('DELETE FROM login_attempts WHERE attempted_at < :cutoff');
    $stmt->execute([':cutoff' => $cutoff]);
}

function login_throttle_status(string $email): array
{
    purge_old_login_attempts();

    $settings = login_throttle_settings();
    $emailHash = login_attempt_email_hash($email);
    $ip = login_attempt_ip();
    $conditions = [];
    $params = [
        ':cutoff' => db_datetime((new DateTimeImmutable())->modify('-' . $settings['window_minutes'] . ' minutes')),
    ];

    if ($emailHash !== null) {
        $conditions[] = 'email_hash = :email_hash';
        $params[':email_hash'] = $emailHash;
    }

    if ($ip !== null) {
        $conditions[] = 'ip_address = :ip_address';
        $params[':ip_address'] = $ip;
    }

    if (!$conditions) {
        return ['locked' => false, 'remaining_seconds' => 0, 'attempt_count' => 0];
    }

    $stmt = db()->prepare('
        SELECT COUNT(*) AS attempt_count, MAX(attempted_at) AS last_attempt_at
        FROM login_attempts
        WHERE successful = 0
          AND attempted_at >= :cutoff
          AND (' . implode(' OR ', $conditions) . ')
    ');
    $stmt->execute($params);
    $row = $stmt->fetch() ?: [];
    $attemptCount = (int) ($row['attempt_count'] ?? 0);

    if ($attemptCount < $settings['max_attempts'] || empty($row['last_attempt_at'])) {
        return ['locked' => false, 'remaining_seconds' => 0, 'attempt_count' => $attemptCount];
    }

    $now = new DateTimeImmutable();
    $lockedUntil = local_datetime((string) $row['last_attempt_at'])->modify('+' . $settings['lock_minutes'] . ' minutes');
    $remainingSeconds = max(0, $lockedUntil->getTimestamp() - $now->getTimestamp());

    return [
        'locked' => $remainingSeconds > 0,
        'remaining_seconds' => $remainingSeconds,
        'attempt_count' => $attemptCount,
    ];
}

function record_login_attempt(string $email, bool $successful): void
{
    purge_old_login_attempts();

    $emailHash = login_attempt_email_hash($email);
    $ip = login_attempt_ip();
    $stmt = db()->prepare('
        INSERT INTO login_attempts (email_hash, ip_address, successful, attempted_at)
        VALUES (:email_hash, :ip_address, :successful, :attempted_at)
    ');
    $stmt->execute([
        ':email_hash' => $emailHash,
        ':ip_address' => $ip,
        ':successful' => $successful ? 1 : 0,
        ':attempted_at' => db_datetime(new DateTimeImmutable()),
    ]);

    if ($successful) {
        clear_failed_login_attempts($email);
    }
}

function clear_failed_login_attempts(string $email): void
{
    $emailHash = login_attempt_email_hash($email);
    $ip = login_attempt_ip();
    $params = [];

    if ($emailHash !== null) {
        $condition = 'email_hash = :email_hash';
        $params[':email_hash'] = $emailHash;
    } elseif ($ip !== null) {
        $condition = 'ip_address = :ip_address';
        $params[':ip_address'] = $ip;
    } else {
        return;
    }

    $stmt = db()->prepare('DELETE FROM login_attempts WHERE successful = 0 AND ' . $condition);
    $stmt->execute($params);
}

function find_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function authenticate_user(string $email, string $password): ?array
{
    $user = find_user_by_email($email);

    if (!$user || (int) $user['active'] !== 1) {
        return null;
    }

    return password_verify($password, $user['password_hash']) ? $user : null;
}

function all_users(): array
{
    return users_for_admin();
}

function users_for_admin(string $search = '', string $role = ''): array
{
    $sql = "
        SELECT *
        FROM users
        WHERE 1 = 1
    ";
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (name LIKE :search OR email LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    if (in_array($role, allowed_user_roles(), true)) {
        $sql .= ' AND role = :role';
        $params[':role'] = $role;
    }

    $sql .= "
        ORDER BY
            CASE role
                WHEN 'admin' THEN 0
                WHEN 'association' THEN 1
                ELSE 2
            END,
            active DESC,
            name ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function pending_registration_users(): array
{
    return db()->query("
        SELECT *
        FROM users
        WHERE role = 'association'
          AND registration_status = 'pending'
        ORDER BY created_at ASC, name ASC
    ")->fetchAll();
}

function active_admin_users(): array
{
    return db()->query("
        SELECT id, name, email
        FROM users
        WHERE role = 'admin'
          AND active = 1
        ORDER BY name ASC
    ")->fetchAll();
}

function active_association_users(): array
{
    return db()->query("
        SELECT id, name, email
        FROM users
        WHERE role = 'association'
          AND active = 1
        ORDER BY name ASC
    ")->fetchAll();
}

function update_user_admin_fields(int $id, string $role, bool $active): void
{
    $sql = 'UPDATE users SET role = :role, active = :active';
    $params = [
        ':id' => $id,
        ':role' => normalize_user_role($role),
        ':active' => $active ? 1 : 0,
    ];

    if ($active) {
        $sql .= ", registration_status = 'approved', reviewed_at = :reviewed_at";
        $params[':reviewed_at'] = db_datetime(new DateTimeImmutable());
    }

    $sql .= ' WHERE id = :id';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
}

function update_user_registration_status(int $id, string $status): void
{
    $status = normalize_registration_status($status);
    if (!in_array($status, ['approved', 'rejected'], true)) {
        return;
    }

    $stmt = db()->prepare('
        UPDATE users
        SET registration_status = :status,
            active = :active,
            reviewed_at = :reviewed_at
        WHERE id = :id
    ');
    $stmt->execute([
        ':id' => $id,
        ':status' => $status,
        ':active' => $status === 'approved' ? 1 : 0,
        ':reviewed_at' => db_datetime(new DateTimeImmutable()),
    ]);
}

function update_user_password(int $id, string $password): void
{
    $stmt = db()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
}

function create_password_reset_token(int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $validMinutes = max(10, (int) app_config('security.password_reset_minutes', 60));
    $expiresAt = db_datetime(new DateTimeImmutable('+' . $validMinutes . ' minutes'));

    delete_expired_password_reset_tokens();

    $stmt = db()->prepare('
        INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at)
        VALUES (:user_id, :token_hash, :expires_at, :created_at)
    ');
    $stmt->execute([
        ':user_id' => $userId,
        ':token_hash' => hash('sha256', $token),
        ':expires_at' => $expiresAt,
        ':created_at' => db_datetime(new DateTimeImmutable()),
    ]);

    return $token;
}

function password_reset_token_record(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    $stmt = db()->prepare('
        SELECT prt.*, u.email, u.name, u.role, u.active
        FROM password_reset_tokens prt
        JOIN users u ON u.id = prt.user_id
        WHERE prt.token_hash = :token_hash
          AND prt.used_at IS NULL
          AND prt.expires_at > :now
          AND u.active = 1
        LIMIT 1
    ');
    $stmt->execute([
        ':token_hash' => hash('sha256', $token),
        ':now' => db_datetime(new DateTimeImmutable()),
    ]);
    $record = $stmt->fetch();

    return $record ?: null;
}

function mark_password_reset_token_used(int $tokenId): void
{
    $stmt = db()->prepare('UPDATE password_reset_tokens SET used_at = :used_at WHERE id = :id');
    $stmt->execute([
        ':id' => $tokenId,
        ':used_at' => db_datetime(new DateTimeImmutable()),
    ]);
}

function delete_expired_password_reset_tokens(): void
{
    $stmt = db()->prepare('DELETE FROM password_reset_tokens WHERE used_at IS NOT NULL OR expires_at <= :now');
    $stmt->execute([':now' => db_datetime(new DateTimeImmutable())]);
}

function record_charter_acceptance(int $userId, int $reservationId, string $charterPath): void
{
    $stmt = db()->prepare('
        INSERT INTO charter_acceptances (user_id, reservation_id, charter_path, accepted_at, ip_address, user_agent)
        VALUES (:user_id, :reservation_id, :charter_path, :accepted_at, :ip_address, :user_agent)
    ');
    $stmt->execute([
        ':user_id' => $userId,
        ':reservation_id' => $reservationId,
        ':charter_path' => $charterPath,
        ':accepted_at' => db_datetime(new DateTimeImmutable()),
        ':ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64) ?: null,
        ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500) ?: null,
    ]);
}

function audit_log(
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    string $message = '',
    array $details = [],
    ?int $userId = null
): void {
    try {
        if ($userId === null && function_exists('current_user')) {
            $user = current_user();
            $userId = $user ? (int) $user['id'] : null;
        }

        $encodedDetails = $details
            ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        $stmt = db()->prepare('
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, message, details, ip_address, user_agent, created_at)
            VALUES (:user_id, :action, :entity_type, :entity_id, :message, :details, :ip_address, :user_agent, :created_at)
        ');
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => substr($action, 0, 80),
            ':entity_type' => $entityType !== null ? substr($entityType, 0, 80) : null,
            ':entity_id' => $entityId,
            ':message' => $message,
            ':details' => $encodedDetails,
            ':ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64) ?: null,
            ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500) ?: null,
            ':created_at' => db_datetime(new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get()))),
        ]);
    } catch (Throwable $exception) {
        // Le journal ne doit jamais bloquer l'action principale.
    }
}

function audit_logs(int $limit = 300): array
{
    $limit = max(1, min(10000, $limit));

    return db()->query("
        SELECT al.*, users.name AS user_name, users.email AS user_email
        FROM audit_logs al
        LEFT JOIN users ON users.id = al.user_id
        ORDER BY al.created_at DESC, al.id DESC
        LIMIT {$limit}
    ")->fetchAll();
}

function clear_audit_logs(): void
{
    db()->exec('DELETE FROM audit_logs');
}

function delete_user_by_id(int $id): void
{
    $stmt = db()->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function rooms(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM rooms';
    if (!$includeInactive) {
        $sql .= ' WHERE active = 1';
    }
    $sql .= ' ORDER BY active DESC, name ASC';

    return db()->query($sql)->fetchAll();
}

function find_room(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM rooms WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $room = $stmt->fetch();

    return $room ?: null;
}

function save_room(?int $id, string $name, ?string $description, ?int $capacity, string $color, bool $active = true): void
{
    if ($id) {
        $stmt = db()->prepare('
            UPDATE rooms
            SET name = :name, description = :description, capacity = :capacity, color = :color, active = :active
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':description' => $description,
            ':capacity' => $capacity,
            ':color' => valid_color($color),
            ':active' => $active ? 1 : 0,
        ]);
        return;
    }

    $stmt = db()->prepare('
        INSERT INTO rooms (name, description, capacity, color, active)
        VALUES (:name, :description, :capacity, :color, :active)
    ');
    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':capacity' => $capacity,
        ':color' => valid_color($color),
        ':active' => $active ? 1 : 0,
    ]);
}

function material_items(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM material_items';
    if (!$includeInactive) {
        $sql .= ' WHERE active = 1 AND quantity_total > 0';
    }
    $sql .= '
        ORDER BY
            CASE category
                WHEN \'tables_bancs\' THEN 0
                WHEN \'barnums\' THEN 1
                WHEN \'grilles\' THEN 2
                WHEN \'barrieres\' THEN 3
                ELSE 4
            END,
            active DESC,
            name ASC
    ';

    return db()->query($sql)->fetchAll();
}

function find_material_item(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM material_items WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();

    return $item ?: null;
}

function save_material_item(?int $id, string $name, string $category, ?string $description, int $quantityTotal, bool $active): void
{
    $quantityTotal = max(0, $quantityTotal);

    if ($id) {
        $stmt = db()->prepare('
            UPDATE material_items
            SET name = :name, category = :category, description = :description, quantity_total = :quantity_total, active = :active
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':category' => normalize_material_category($category),
            ':description' => $description,
            ':quantity_total' => $quantityTotal,
            ':active' => $active ? 1 : 0,
        ]);
        return;
    }

    $stmt = db()->prepare('
        INSERT INTO material_items (name, category, description, quantity_total, active)
        VALUES (:name, :category, :description, :quantity_total, :active)
    ');
    $stmt->execute([
        ':name' => $name,
        ':category' => normalize_material_category($category),
        ':description' => $description,
        ':quantity_total' => $quantityTotal,
        ':active' => $active ? 1 : 0,
    ]);
}

function create_material_request_record(
    int $userId,
    string $eventTitle,
    ?string $eventLocation,
    ?string $notes,
    string $startsAt,
    string $endsAt,
    array $items
): int {
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('
            INSERT INTO material_requests (user_id, event_title, event_location, notes, starts_at, ends_at, status)
            VALUES (:user_id, :event_title, :event_location, :notes, :starts_at, :ends_at, \'pending\')
        ');
        $stmt->execute([
            ':user_id' => $userId,
            ':event_title' => $eventTitle,
            ':event_location' => $eventLocation,
            ':notes' => $notes,
            ':starts_at' => $startsAt,
            ':ends_at' => $endsAt,
        ]);

        $requestId = (int) $pdo->lastInsertId();
        $itemStmt = $pdo->prepare('
            INSERT INTO material_request_items (request_id, material_item_id, quantity)
            VALUES (:request_id, :material_item_id, :quantity)
        ');

        foreach ($items as $materialItemId => $quantity) {
            $itemStmt->execute([
                ':request_id' => $requestId,
                ':material_item_id' => (int) $materialItemId,
                ':quantity' => (int) $quantity,
            ]);
        }

        $pdo->commit();
        return $requestId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function material_request_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM material_requests WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $request = $stmt->fetch();

    return $request ?: null;
}

function material_request_with_context_by_id(int $id): ?array
{
    $stmt = db()->prepare("
        SELECT mr.*, users.name AS user_name, users.email AS user_email, validator.name AS validated_by_name
        FROM material_requests mr
        JOIN users ON users.id = mr.user_id
        LEFT JOIN users validator ON validator.id = mr.validated_by_id
        WHERE mr.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $request = $stmt->fetch();

    return $request ?: null;
}

function material_request_items(int $requestId): array
{
    $stmt = db()->prepare("
        SELECT mri.*, mi.name, mi.category, mi.description, mi.quantity_total, mi.active
        FROM material_request_items mri
        JOIN material_items mi ON mi.id = mri.material_item_id
        WHERE mri.request_id = :request_id
        ORDER BY
            CASE mi.category
                WHEN 'tables_bancs' THEN 0
                WHEN 'barnums' THEN 1
                WHEN 'grilles' THEN 2
                WHEN 'barrieres' THEN 3
                ELSE 4
            END,
            mi.name ASC
    ");
    $stmt->execute([':request_id' => $requestId]);

    return $stmt->fetchAll();
}

function material_requests_for_user(int $userId): array
{
    $stmt = db()->prepare("
        SELECT mr.*, users.name AS user_name, users.email AS user_email, validator.name AS validated_by_name
        FROM material_requests mr
        JOIN users ON users.id = mr.user_id
        LEFT JOIN users validator ON validator.id = mr.validated_by_id
        WHERE mr.user_id = :user_id
        ORDER BY mr.starts_at DESC
        LIMIT 100
    ");
    $stmt->execute([':user_id' => $userId]);

    return $stmt->fetchAll();
}

function all_material_requests(int $limit = 200): array
{
    $limit = max(1, min(1000, $limit));

    return db()->query("
        SELECT mr.*, users.name AS user_name, users.email AS user_email, validator.name AS validated_by_name
        FROM material_requests mr
        JOIN users ON users.id = mr.user_id
        LEFT JOIN users validator ON validator.id = mr.validated_by_id
        ORDER BY
            CASE mr.status
                WHEN 'pending' THEN 0
                WHEN 'approved' THEN 1
                WHEN 'rejected' THEN 2
                ELSE 3
            END,
            mr.starts_at DESC
        LIMIT {$limit}
    ")->fetchAll();
}

function material_unavailable_quantity(int $materialItemId, string $startsAt, string $endsAt, ?int $excludingRequestId = null): int
{
    $sql = "
        SELECT COALESCE(SUM(mri.quantity), 0)
        FROM material_request_items mri
        JOIN material_requests mr ON mr.id = mri.request_id
        WHERE mri.material_item_id = :material_item_id
          AND mr.status = 'approved'
          AND mr.starts_at < :ends_at
          AND mr.ends_at > :starts_at
    ";
    $params = [
        ':material_item_id' => $materialItemId,
        ':starts_at' => $startsAt,
        ':ends_at' => $endsAt,
    ];

    if ($excludingRequestId !== null) {
        $sql .= ' AND mr.id <> :excluding_request_id';
        $params[':excluding_request_id'] = $excludingRequestId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function material_available_quantity(int $materialItemId, string $startsAt, string $endsAt, ?int $excludingRequestId = null): int
{
    $item = find_material_item($materialItemId);
    if (!$item || (int) $item['active'] !== 1) {
        return 0;
    }

    return max(0, (int) $item['quantity_total'] - material_unavailable_quantity($materialItemId, $startsAt, $endsAt, $excludingRequestId));
}

function material_request_availability_errors(int $requestId): array
{
    $request = material_request_by_id($requestId);
    if (!$request) {
        return ['Demande de matériel introuvable.'];
    }

    $errors = [];
    foreach (material_request_items($requestId) as $item) {
        $available = material_available_quantity(
            (int) $item['material_item_id'],
            $request['starts_at'],
            $request['ends_at'],
            $requestId
        );

        if ((int) $item['active'] !== 1) {
            $errors[] = $item['name'] . ' n’est plus disponible à la réservation.';
            continue;
        }

        if ((int) $item['quantity'] > $available) {
            $errors[] = $item['name'] . ' : ' . $available . ' disponible(s) sur ce créneau, ' . (int) $item['quantity'] . ' demandé(s).';
        }
    }

    return $errors;
}

function update_material_request_status(int $id, string $status, ?string $adminNotes, int $adminId): void
{
    $allowed = ['pending', 'approved', 'rejected', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        return;
    }

    $validatedAt = in_array($status, ['approved', 'rejected'], true)
        ? db_datetime(new DateTimeImmutable())
        : null;
    $validatedById = in_array($status, ['approved', 'rejected'], true)
        ? $adminId
        : null;

    $stmt = db()->prepare('
        UPDATE material_requests
        SET status = :status,
            admin_notes = :admin_notes,
            validated_by_id = :validated_by_id,
            validated_at = :validated_at
        WHERE id = :id
    ');
    $stmt->execute([
        ':id' => $id,
        ':status' => $status,
        ':admin_notes' => $adminNotes,
        ':validated_by_id' => $validatedById,
        ':validated_at' => $validatedAt,
    ]);
}

function delete_material_request(int $id): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('DELETE FROM material_request_items WHERE request_id = :id');
        $stmt->execute([':id' => $id]);

        $stmt = $pdo->prepare('DELETE FROM material_requests WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}

function reservation_overlaps(int $roomId, string $startsAt, string $endsAt): bool
{
    $stmt = db()->prepare("
        SELECT COUNT(*)
        FROM reservations
        WHERE room_id = :room_id
          AND status IN ('pending', 'approved')
          AND starts_at < :ends_at
          AND ends_at > :starts_at
    ");
    $stmt->execute([
        ':room_id' => $roomId,
        ':starts_at' => $startsAt,
        ':ends_at' => $endsAt,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function reservation_overlaps_except(int $reservationId, int $roomId, string $startsAt, string $endsAt): bool
{
    $stmt = db()->prepare("
        SELECT COUNT(*)
        FROM reservations
        WHERE id <> :reservation_id
          AND room_id = :room_id
          AND status IN ('pending', 'approved')
          AND starts_at < :ends_at
          AND ends_at > :starts_at
    ");
    $stmt->execute([
        ':reservation_id' => $reservationId,
        ':room_id' => $roomId,
        ':starts_at' => $startsAt,
        ':ends_at' => $endsAt,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function blackout_overlaps_room(int $roomId, string $startsAt, string $endsAt): bool
{
    $stmt = db()->prepare("
        SELECT COUNT(*)
        FROM blackout_periods
        WHERE active = 1
          AND (room_id = :room_id OR room_id IS NULL)
          AND starts_at < :ends_at
          AND ends_at > :starts_at
    ");
    $stmt->execute([
        ':room_id' => $roomId,
        ':starts_at' => $startsAt,
        ':ends_at' => $endsAt,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function create_blackout_period(?int $roomId, string $title, ?string $notes, string $startsAt, string $endsAt, int $createdById): int
{
    $stmt = db()->prepare('
        INSERT INTO blackout_periods (room_id, title, notes, starts_at, ends_at, created_by_id)
        VALUES (:room_id, :title, :notes, :starts_at, :ends_at, :created_by_id)
    ');
    $stmt->execute([
        ':room_id' => $roomId,
        ':title' => $title,
        ':notes' => $notes,
        ':starts_at' => $startsAt,
        ':ends_at' => $endsAt,
        ':created_by_id' => $createdById,
    ]);

    return (int) db()->lastInsertId();
}

function blackout_periods_between(DateTimeImmutable $start, DateTimeImmutable $end, bool $includeInactive = false): array
{
    $sql = "
        SELECT b.*, rooms.name AS room_name, users.name AS created_by_name
        FROM blackout_periods b
        LEFT JOIN rooms ON rooms.id = b.room_id
        LEFT JOIN users ON users.id = b.created_by_id
        WHERE b.starts_at < :end
          AND b.ends_at > :start
    ";
    if (!$includeInactive) {
        $sql .= ' AND b.active = 1';
    }
    $sql .= ' ORDER BY b.starts_at ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':start' => db_datetime($start),
        ':end' => db_datetime($end),
    ]);

    return $stmt->fetchAll();
}

function all_blackout_periods(int $limit = 100): array
{
    $limit = max(1, min(500, $limit));

    return db()->query("
        SELECT b.*, rooms.name AS room_name, users.name AS created_by_name
        FROM blackout_periods b
        LEFT JOIN rooms ON rooms.id = b.room_id
        LEFT JOIN users ON users.id = b.created_by_id
        ORDER BY b.starts_at DESC
        LIMIT {$limit}
    ")->fetchAll();
}

function update_blackout_status(int $id, bool $active): void
{
    $stmt = db()->prepare('UPDATE blackout_periods SET active = :active WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':active' => $active ? 1 : 0,
    ]);
}

function create_reservation_record(int $roomId, int $userId, string $title, ?string $notes, string $startsAt, string $endsAt, string $status): int
{
    $stmt = db()->prepare('
        INSERT INTO reservations (room_id, user_id, title, notes, starts_at, ends_at, status)
        VALUES (:room_id, :user_id, :title, :notes, :starts_at, :ends_at, :status)
    ');
    $stmt->execute([
        ':room_id' => $roomId,
        ':user_id' => $userId,
        ':title' => $title,
        ':notes' => $notes,
        ':starts_at' => $startsAt,
        ':ends_at' => $endsAt,
        ':status' => $status,
    ]);

    return (int) db()->lastInsertId();
}

function reservations_between(DateTimeImmutable $start, DateTimeImmutable $end, ?int $roomId = null): array
{
    $sql = "
        SELECT r.*, rooms.name AS room_name, rooms.color AS room_color, users.name AS user_name
        FROM reservations r
        JOIN rooms ON rooms.id = r.room_id
        JOIN users ON users.id = r.user_id
        WHERE r.starts_at < :end
          AND r.ends_at > :start
          AND r.status IN ('pending', 'approved')
    ";
    $params = [
        ':start' => db_datetime($start),
        ':end' => db_datetime($end),
    ];

    if ($roomId !== null) {
        $sql .= ' AND r.room_id = :room_id';
        $params[':room_id'] = $roomId;
    }

    $sql .= ' ORDER BY r.starts_at ASC, rooms.name ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function reservations_for_user(int $userId): array
{
    $stmt = db()->prepare("
        SELECT r.*, rooms.name AS room_name, rooms.color AS room_color
        FROM reservations r
        JOIN rooms ON rooms.id = r.room_id
        WHERE r.user_id = :user_id
        ORDER BY r.starts_at DESC
        LIMIT 100
    ");
    $stmt->execute([':user_id' => $userId]);

    return $stmt->fetchAll();
}

function reservation_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM reservations WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $reservation = $stmt->fetch();

    return $reservation ?: null;
}

function reservation_with_context_by_id(int $id): ?array
{
    $stmt = db()->prepare("
        SELECT r.*, rooms.name AS room_name, rooms.color AS room_color, users.name AS user_name, users.email AS user_email
        FROM reservations r
        JOIN rooms ON rooms.id = r.room_id
        JOIN users ON users.id = r.user_id
        WHERE r.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $reservation = $stmt->fetch();

    return $reservation ?: null;
}

function all_reservations(int $limit = 200): array
{
    $limit = max(1, min(1000, $limit));

    return db()->query("
        SELECT r.*, rooms.name AS room_name, rooms.color AS room_color, users.name AS user_name, users.email AS user_email
        FROM reservations r
        JOIN rooms ON rooms.id = r.room_id
        JOIN users ON users.id = r.user_id
        ORDER BY r.starts_at DESC
        LIMIT {$limit}
    ")->fetchAll();
}

function pending_reservations(int $limit = 200): array
{
    $limit = max(1, min(1000, $limit));

    return db()->query("
        SELECT r.*, rooms.name AS room_name, rooms.color AS room_color, users.name AS user_name, users.email AS user_email
        FROM reservations r
        JOIN rooms ON rooms.id = r.room_id
        JOIN users ON users.id = r.user_id
        WHERE r.status = 'pending'
        ORDER BY r.starts_at ASC
        LIMIT {$limit}
    ")->fetchAll();
}

function reservations_for_export(DateTimeImmutable $start, DateTimeImmutable $end, array $roomIds, array $statuses): array
{
    $allowedStatuses = ['pending', 'approved', 'rejected', 'cancelled'];
    $statuses = array_values(array_intersect($allowedStatuses, $statuses));
    if (!$statuses) {
        $statuses = ['pending', 'approved'];
    }

    $roomIds = array_values(array_unique(array_filter(array_map('intval', $roomIds), static fn (int $id): bool => $id > 0)));
    $params = [
        ':start' => db_datetime($start),
        ':end' => db_datetime($end),
    ];

    $statusPlaceholders = [];
    foreach ($statuses as $index => $status) {
        $placeholder = ':status_' . $index;
        $statusPlaceholders[] = $placeholder;
        $params[$placeholder] = $status;
    }

    $sql = "
        SELECT r.*, rooms.name AS room_name, rooms.color AS room_color, users.name AS user_name, users.email AS user_email
        FROM reservations r
        JOIN rooms ON rooms.id = r.room_id
        JOIN users ON users.id = r.user_id
        WHERE r.starts_at < :end
          AND r.ends_at > :start
          AND r.status IN (" . implode(', ', $statusPlaceholders) . ")
    ";

    if ($roomIds) {
        $roomPlaceholders = [];
        foreach ($roomIds as $index => $roomId) {
            $placeholder = ':room_' . $index;
            $roomPlaceholders[] = $placeholder;
            $params[$placeholder] = $roomId;
        }
        $sql .= ' AND r.room_id IN (' . implode(', ', $roomPlaceholders) . ')';
    }

    $sql .= ' ORDER BY r.starts_at ASC, rooms.name ASC, users.name ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function update_reservation_status(int $id, string $status): void
{
    $allowed = ['pending', 'approved', 'rejected', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        return;
    }

    $stmt = db()->prepare('UPDATE reservations SET status = :status WHERE id = :id');
    $stmt->execute([':id' => $id, ':status' => $status]);
}

function update_reservation_details(int $id, int $roomId, string $title, ?string $notes, string $startsAt, string $endsAt, string $status): void
{
    $allowed = ['pending', 'approved', 'rejected', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        $status = 'pending';
    }

    $stmt = db()->prepare('
        UPDATE reservations
        SET room_id = :room_id,
            title = :title,
            notes = :notes,
            starts_at = :starts_at,
            ends_at = :ends_at,
            status = :status
        WHERE id = :id
    ');
    $stmt->execute([
        ':id' => $id,
        ':room_id' => $roomId,
        ':title' => $title,
        ':notes' => $notes,
        ':starts_at' => $startsAt,
        ':ends_at' => $endsAt,
        ':status' => $status,
    ]);
}

function dashboard_counts(): array
{
    return [
        'rooms' => (int) db()->query('SELECT COUNT(*) FROM rooms WHERE active = 1')->fetchColumn(),
        'associations' => (int) db()->query("SELECT COUNT(*) FROM users WHERE active = 1 AND role = 'association'")->fetchColumn(),
        'account_pending' => (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'association' AND registration_status = 'pending'")->fetchColumn(),
        'pending' => (int) db()->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn(),
        'material_pending' => (int) db()->query("SELECT COUNT(*) FROM material_requests WHERE status = 'pending'")->fetchColumn(),
        'blackouts' => (int) db()->query('SELECT COUNT(*) FROM blackout_periods WHERE active = 1')->fetchColumn(),
        'audit_logs' => (int) db()->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn(),
    ];
}

function setting_value(string $key, string $default = ''): string
{
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
    $stmt->execute([':key' => $key]);
    $value = $stmt->fetchColumn();

    return $value === false ? $default : (string) $value;
}

function save_setting(string $key, string $value): void
{
    if (is_mysql()) {
        $stmt = db()->prepare('
            INSERT INTO settings (setting_key, setting_value)
            VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ');
    } else {
        $stmt = db()->prepare('
            INSERT INTO settings (setting_key, setting_value)
            VALUES (:key, :value)
            ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value
        ');
    }

    $stmt->execute([
        ':key' => $key,
        ':value' => $value,
    ]);
}
