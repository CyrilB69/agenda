<?php
declare(strict_types=1);

return [
    'app_name' => 'Agenda des salles communales',
    'timezone' => 'Europe/Paris',
    'session_name' => 'AGENDA_COMMUNAL',

    'site' => [
        // Public URL used in notification e-mails.
        'public_url' => getenv('SITE_PUBLIC_URL') ?: 'https://votre-domaine.fr/agenda/public/',
    ],

    'branding' => [
        'entity_name' => getenv('BRANDING_ENTITY_NAME') ?: 'Agenda des salles communales',
        'logo_path' => '',
        'primary_color' => '#155e75',
        'accent_color' => '#d97706',
        'background_color' => '#f6f7f3',
    ],

    'ui' => [
        'login_help_text' => getenv('LOGIN_HELP_TEXT') ?: 'Vous n’avez pas encore de compte ? Contactez la mairie pour demander un accès.',
    ],

    'security' => [
        'password_reset_minutes' => 60,
        'login_max_attempts' => 5,
        'login_window_minutes' => 15,
        'login_lock_minutes' => 15,
    ],

    'database' => [
        // SQLite is the simplest option for a small communal agenda.
        // For an OVH database, change driver to mysql and fill in the fields below.
        'driver' => getenv('DB_DRIVER') ?: 'sqlite',
        'path' => __DIR__ . '/../storage/agenda.sqlite',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: '',
        'user' => getenv('DB_USER') ?: '',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],

    'reservations' => [
        // If true, user reservations are created as "pending" until an admin approves them.
        'require_approval' => true,
        'max_duration_hours' => 24,
    ],

    'mail' => [
        // Enable after setting a valid sender address for your domain.
        'enabled' => false,
        'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'no-reply@votre-domaine.fr',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Agenda des salles communales',

        // Leave empty to notify every active administrator account.
        'admin_recipients' => [],
    ],

    'backup' => [
        'auto_enabled' => false,
        'auto_frequency' => 'daily',
        'auto_recipients' => [],
        'cron_token' => getenv('BACKUP_CRON_TOKEN') ?: '',
    ],
];
