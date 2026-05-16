<?php
declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    global $config;

    if ($key === null) {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url_for(string $page = 'home', array $params = []): string
{
    $query = $params;
    if ($page !== 'home') {
        $query = ['page' => $page] + $query;
    }

    return 'index.php' . ($query ? '?' . http_build_query($query) : '');
}

function redirect(string $page = 'home', array $params = []): never
{
    header('Location: ' . url_for($page, $params));
    exit;
}

function asset_url(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

function public_url(string $path): string
{
    return ltrim($path, '/');
}

function absolute_url_for(string $page = 'home', array $params = []): string
{
    $configuredPublicUrl = configured_public_url();
    if ($configuredPublicUrl !== '') {
        return $configuredPublicUrl . url_for($page, $params);
    }

    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return url_for($page, $params);
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $secure ? 'https' : 'http';
    $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
    $base = $scheme . '://' . $host . ($scriptDir === '' || $scriptDir === '/' ? '' : $scriptDir);

    return $base . '/' . url_for($page, $params);
}

function configured_public_url(): string
{
    $configured = function_exists('setting_value')
        ? setting_value('site.public_url', (string) app_config('site.public_url', ''))
        : (string) app_config('site.public_url', '');

    $configured = trim($configured);
    if ($configured === '' || filter_var($configured, FILTER_VALIDATE_URL) === false) {
        return '';
    }

    $scheme = parse_url($configured, PHP_URL_SCHEME);
    if (!in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
        return '';
    }

    return rtrim($configured, '/') . '/';
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post_string(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function ensure_csrf(): void
{
    $token = (string) ($_POST['_csrf'] ?? '');
    if (!hash_equals(csrf_token(), $token)) {
        flash('error', 'La session a expiré. Merci de réessayer.');
        redirect('home');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flashes'][] = ['type' => $type, 'message' => $message];
}

function consume_flashes(): array
{
    $flashes = $_SESSION['_flashes'] ?? [];
    unset($_SESSION['_flashes']);

    return $flashes;
}

function current_user(): ?array
{
    static $cached = null;
    static $cachedId = null;

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        return null;
    }

    if ($cached !== null && $cachedId === $userId) {
        return $cached;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || (int) $user['active'] !== 1) {
        unset($_SESSION['user_id']);
        return null;
    }

    $cached = $user;
    $cachedId = $userId;

    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();

    return $user !== null && $user['role'] === 'admin';
}

function allowed_user_roles(): array
{
    return ['admin', 'association'];
}

function normalize_user_role(string $role): string
{
    if ($role === 'user') {
        return 'association';
    }

    return in_array($role, allowed_user_roles(), true) ? $role : 'association';
}

function role_label(string $role): string
{
    return [
        'admin' => 'Administrateur',
        'association' => 'Association',
        'user' => 'Association',
    ][$role] ?? 'Association';
}

function registration_status_label(string $status): string
{
    return [
        'pending' => 'Demande en attente',
        'approved' => 'Validée',
        'rejected' => 'Refusée',
    ][$status] ?? 'Validée';
}

function normalize_registration_status(string $status): string
{
    return in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : 'approved';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Connectez-vous pour accéder aux réservations.');
        redirect('login');
    }
}

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        render_header('Accès refusé');
        echo '<section class="panel"><h1>Accès refusé</h1><p>Cette page est réservée aux administrateurs.</p></section>';
        render_footer();
        exit;
    }
}

function parse_local_datetime(string $value): ?DateTimeImmutable
{
    if ($value === '') {
        return null;
    }

    $timezone = new DateTimeZone(date_default_timezone_get());
    $formats = ['Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d H:i:s'];

    foreach ($formats as $format) {
        $dt = DateTimeImmutable::createFromFormat('!' . $format, $value, $timezone);
        $errors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        if ($dt instanceof DateTimeImmutable && !$hasErrors) {
            return $dt;
        }
    }

    return null;
}

function parse_local_date(string $value): ?DateTimeImmutable
{
    if ($value === '') {
        return null;
    }

    $timezone = new DateTimeZone(date_default_timezone_get());
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $timezone);
    $errors = DateTimeImmutable::getLastErrors();
    $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

    return $dt instanceof DateTimeImmutable && !$hasErrors ? $dt : null;
}

function db_datetime(DateTimeImmutable $date): string
{
    return $date->format('Y-m-d H:i:s');
}

function human_date(DateTimeImmutable|string $date): string
{
    $dt = local_datetime($date);
    $days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

    return ucfirst($days[(int) $dt->format('w')]) . ' ' . $dt->format('d/m/Y');
}

function human_datetime(DateTimeImmutable|string $date): string
{
    $dt = local_datetime($date);

    return human_date($dt) . ' à ' . $dt->format('H:i');
}

function local_datetime(DateTimeImmutable|string $date): DateTimeImmutable
{
    $timezone = new DateTimeZone(date_default_timezone_get());
    if ($date instanceof DateTimeImmutable) {
        return $date->setTimezone($timezone);
    }

    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date, $timezone);
    if ($parsed instanceof DateTimeImmutable) {
        return $parsed;
    }

    return (new DateTimeImmutable($date, $timezone))->setTimezone($timezone);
}

function time_range(string $start, string $end): string
{
    $startsAt = new DateTimeImmutable($start);
    $endsAt = new DateTimeImmutable($end);

    return $startsAt->format('H:i') . ' - ' . $endsAt->format('H:i');
}

function status_label(string $status): string
{
    return [
        'pending' => 'En attente',
        'approved' => 'Validée',
        'rejected' => 'Refusée',
        'cancelled' => 'Annulée',
    ][$status] ?? $status;
}

function status_class(string $status): string
{
    return [
        'pending' => 'status-pending',
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        'cancelled' => 'status-cancelled',
    ][$status] ?? 'status-muted';
}

function material_categories(): array
{
    return [
        'tables_bancs' => 'Tables et bancs',
        'barnums' => 'Barnums',
        'grilles' => 'Grilles',
        'barrieres' => 'Barrières',
        'autre' => 'Autre matériel',
    ];
}

function normalize_material_category(string $category): string
{
    return array_key_exists($category, material_categories()) ? $category : 'autre';
}

function material_category_label(string $category): string
{
    return material_categories()[$category] ?? material_categories()['autre'];
}

function valid_color(string $value): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : '#2563eb';
}

function branding_settings(): array
{
    $logoPath = setting_value('branding.logo_path', app_config('branding.logo_path', ''));
    if ($logoPath !== '' && !str_starts_with($logoPath, 'uploads/branding/')) {
        $logoPath = '';
    }

    return [
        'entity_name' => setting_value('branding.entity_name', app_config('branding.entity_name', app_config('app_name', 'Agenda communal'))),
        'logo_path' => $logoPath,
        'primary_color' => valid_color(setting_value('branding.primary_color', app_config('branding.primary_color', '#155e75'))),
        'accent_color' => valid_color(setting_value('branding.accent_color', app_config('branding.accent_color', '#d97706'))),
        'background_color' => valid_color(setting_value('branding.background_color', app_config('branding.background_color', '#f6f7f3'))),
    ];
}

function login_help_text(): string
{
    return setting_value(
        'ui.login_help_text',
        app_config('ui.login_help_text', 'Vous n’avez pas encore de compte ? Contactez la mairie pour demander un accès.')
    );
}

function charter_settings(): array
{
    $path = setting_value('charter.file_path', '');
    if ($path !== '' && !str_starts_with($path, 'storage/charters/')) {
        $path = '';
    }

    $absolutePath = $path !== '' ? realpath(__DIR__ . '/../' . $path) : false;
    $storageRoot = realpath(__DIR__ . '/../storage/charters');
    $exists = $absolutePath && $storageRoot && str_starts_with($absolutePath, $storageRoot) && is_file($absolutePath);

    if (!$exists) {
        $path = '';
        $absolutePath = '';
    }

    return [
        'file_path' => $path,
        'absolute_path' => (string) $absolutePath,
        'original_name' => setting_value('charter.original_name', ''),
        'uploaded_at' => setting_value('charter.uploaded_at', ''),
    ];
}

function charter_is_available(): bool
{
    return charter_settings()['file_path'] !== '';
}

function branding_inline_style(array $branding): string
{
    $primary = valid_color((string) $branding['primary_color']);
    $accent = valid_color((string) $branding['accent_color']);
    $background = valid_color((string) $branding['background_color']);

    return '--primary: ' . $primary . '; --primary-strong: ' . $primary . '; --accent: ' . $accent . '; --bg: ' . $background . ';';
}
