<?php
declare(strict_types=1);

function dispatch_request(): void
{
    $page = (string) ($_GET['page'] ?? 'home');

    if (!first_admin_exists() && $page !== 'setup') {
        redirect('setup');
    }

    $routes = [
        'home' => 'page_home',
        'setup' => 'page_setup',
        'login' => 'page_login',
        'register' => 'page_register',
        'forgot_password' => 'page_forgot_password',
        'password_reset' => 'page_password_reset',
        'logout' => 'page_logout',
        'account' => 'page_account',
        'account_password_update' => 'page_account_password_update',
        'dashboard' => 'page_dashboard',
        'reservation' => 'page_reservation_detail',
        'reservation_update' => 'page_reservation_update',
        'reserve' => 'page_reserve',
        'cancel_reservation' => 'page_cancel_reservation',
        'charter_download' => 'page_charter_download',
        'material' => 'page_material',
        'material_request_create' => 'page_material_request_create',
        'material_request_cancel' => 'page_material_request_cancel',
        'admin' => 'page_admin',
        'admin_requests' => 'page_admin_requests',
        'admin_appearance' => 'page_admin_appearance',
        'admin_accounts' => 'page_admin_accounts',
        'admin_material' => 'page_admin_material',
        'admin_activity' => 'page_admin_activity',
        'admin_activity_download' => 'page_admin_activity_download',
        'admin_activity_clear' => 'page_admin_activity_clear',
        'admin_export' => 'page_admin_export',
        'admin_export_download' => 'page_admin_export_download',
        'admin_backup' => 'page_admin_backup',
        'admin_backup_settings_save' => 'page_admin_backup_settings_save',
        'admin_backup_run' => 'page_admin_backup_run',
        'admin_backup_download' => 'page_admin_backup_download',
        'admin_backup_restore' => 'page_admin_backup_restore',
        'admin_room_save' => 'page_admin_room_save',
        'admin_user_create' => 'page_admin_user_create',
        'admin_user_update' => 'page_admin_user_update',
        'admin_user_delete' => 'page_admin_user_delete',
        'admin_registration_status' => 'page_admin_registration_status',
        'admin_material_item_save' => 'page_admin_material_item_save',
        'admin_material_items_save' => 'page_admin_material_items_save',
        'admin_material_request_status' => 'page_admin_material_request_status',
        'admin_material_request_delete' => 'page_admin_material_request_delete',
        'admin_reservation_create' => 'page_admin_reservation_create',
        'admin_reservation_status' => 'page_admin_reservation_status',
        'admin_blackout_save' => 'page_admin_blackout_save',
        'admin_blackout_toggle' => 'page_admin_blackout_toggle',
        'admin_branding_save' => 'page_admin_branding_save',
        'admin_charter_save' => 'page_admin_charter_save',
        'admin_mail_save' => 'page_admin_mail_save',
    ];

    if (!isset($routes[$page])) {
        http_response_code(404);
        render_header('Page introuvable');
        echo '<section class="panel"><h1>Page introuvable</h1><p>La page demandée n’existe pas.</p></section>';
        render_footer();
        return;
    }

    $routes[$page]();
}

function render_header(string $title): void
{
    $user = current_user();
    $branding = branding_settings();
    $appName = $branding['entity_name'];
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> - <?= e($appName) ?></title>
        <?php if ($branding['logo_path'] !== ''): ?>
            <link rel="icon" href="<?= e(public_url($branding['logo_path'])) ?>">
            <link rel="apple-touch-icon" href="<?= e(public_url($branding['logo_path'])) ?>">
        <?php endif; ?>
        <link rel="stylesheet" href="<?= e(asset_url('app.css')) ?>?v=20260515-1">
    </head>
    <body style="<?= e(branding_inline_style($branding)) ?>">
        <header class="topbar">
            <a class="brand" href="<?= e(url_for('home')) ?>">
                <?php if ($branding['logo_path'] !== ''): ?>
                    <img class="brand-logo" src="<?= e(public_url($branding['logo_path'])) ?>" alt="">
                <?php else: ?>
                    <span class="brand-mark" aria-hidden="true"></span>
                <?php endif; ?>
                <span><?= e($appName) ?></span>
            </a>
            <nav class="nav">
                <a href="<?= e(url_for('home')) ?>">Agenda</a>
                <?php if ($user): ?>
                    <a href="<?= e(url_for('dashboard')) ?>">Réserver</a>
                    <a href="<?= e(url_for('material')) ?>">Matériel</a>
                    <a href="<?= e(url_for('account')) ?>">Mon compte</a>
                    <?php if ($user['role'] === 'admin'): ?>
                        <details class="nav-group">
                            <summary>Administration</summary>
                            <div class="nav-dropdown">
                                <a href="<?= e(url_for('admin')) ?>">Tableau de bord</a>
                                <a href="<?= e(url_for('admin_requests')) ?>">Demandes à valider</a>
                                <a href="<?= e(url_for('admin_accounts')) ?>">Comptes</a>
                                <a href="<?= e(url_for('admin_material')) ?>">Matériel</a>
                                <a href="<?= e(url_for('admin_appearance')) ?>">Apparence</a>
                                <a href="<?= e(url_for('admin_export')) ?>">Exports PDF</a>
                                <a href="<?= e(url_for('admin_activity')) ?>">Journal</a>
                                <a href="<?= e(url_for('admin_backup')) ?>">Sauvegardes</a>
                            </div>
                        </details>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url_for('logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="link-button" type="submit">Déconnexion</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url_for('login')) ?>">Connexion</a>
                <?php endif; ?>
            </nav>
        </header>

        <main class="page">
            <?php render_flashes(); ?>
    <?php
}

function render_footer(): void
{
    ?>
        </main>
        <script src="<?= e(asset_url('app.js')) ?>?v=20260515-1" defer></script>
    </body>
    </html>
    <?php
}

function render_flashes(): void
{
    foreach (consume_flashes() as $flash) {
        $type = $flash['type'] === 'error' ? 'error' : 'success';
        echo '<div class="flash ' . e($type) . '">' . e($flash['message']) . '</div>';
    }
}

function page_home(): void
{
    if (!is_logged_in()) {
        redirect('login');
    }

    $weekStart = week_start_from_query((string) ($_GET['week'] ?? ''));
    $weekEnd = $weekStart->modify('+7 days');
    $allRooms = rooms(false);
    $reservations = reservations_between($weekStart, $weekEnd);
    $blackouts = blackout_periods_between($weekStart, $weekEnd);

    render_header('Agenda partagé');
    ?>
    <section class="page-heading compact">
        <div>
            <p class="eyebrow">Espace connecté</p>
            <h1>Agenda des salles communales</h1>
            <p>Consultez les disponibilités des salles et réservez un créneau depuis votre espace.</p>
        </div>
        <div class="heading-actions">
            <a class="button primary" href="<?= e(url_for('dashboard')) ?>">Réserver un créneau</a>
        </div>
    </section>
    <?php
    render_calendar($allRooms, $reservations, $weekStart, 'home', $blackouts);
    render_footer();
}

function page_setup(): void
{
    if (first_admin_exists()) {
        redirect('home');
    }

    $errors = [];
    if (is_post()) {
        ensure_csrf();
        $name = post_string('name');
        $email = post_string('email');
        $password = post_string('password');

        if ($name === '') {
            $errors[] = 'Le nom est obligatoire.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L’adresse e-mail est invalide.';
        }
        if (strlen($password) < 10) {
            $errors[] = 'Le mot de passe doit contenir au moins 10 caractères.';
        }

        if (!$errors) {
            $adminId = create_user($name, $email, $password, 'admin', true);
            audit_log('setup_admin_created', 'user', $adminId, 'Premier compte administrateur créé.', ['email' => $email], $adminId);
            flash('success', 'Compte administrateur créé. Vous pouvez vous connecter.');
            redirect('login');
        }
    }

    render_header('Initialisation');
    ?>
    <section class="auth-card">
        <p class="eyebrow">Première utilisation</p>
        <h1>Créer le compte administrateur</h1>
        <p>Ce formulaire disparaît dès que le premier administrateur existe.</p>

        <?php render_errors($errors); ?>

        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>Nom complet
                <input name="name" autocomplete="name" required value="<?= e($_POST['name'] ?? '') ?>">
            </label>
            <label>E-mail
                <input type="email" name="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </label>
            <label>Mot de passe
                <input type="password" name="password" autocomplete="new-password" minlength="10" required>
            </label>
            <button class="button primary" type="submit">Créer l’administrateur</button>
        </form>
    </section>
    <?php
    render_footer();
}

function page_login(): void
{
    if (is_logged_in()) {
        redirect(is_admin() ? 'admin' : 'dashboard');
    }

    $errors = [];
    $loginHelpText = login_help_text();
    if (is_post()) {
        ensure_csrf();
        $email = post_string('email');
        $password = post_string('password');
        $throttle = login_throttle_status($email);

        if ($throttle['locked']) {
            $remainingMinutes = max(1, (int) ceil((int) $throttle['remaining_seconds'] / 60));
            audit_log('login_blocked', 'user', null, 'Connexion bloquée par la protection anti brute force.', [
                'email' => $email,
                'remaining_minutes' => $remainingMinutes,
                'attempt_count' => $throttle['attempt_count'],
            ], null);
            $errors[] = 'Trop de tentatives de connexion. Réessayez dans environ ' . $remainingMinutes . ' minute(s).';
        } else {
            $user = authenticate_user($email, $password);
            record_login_attempt($email, $user !== null);

            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                audit_log('login_success', 'user', (int) $user['id'], 'Connexion réussie.', ['email' => $email], (int) $user['id']);
                flash('success', 'Connexion réussie.');
                redirect($user['role'] === 'admin' ? 'admin' : 'dashboard');
            }

            audit_log('login_failed', 'user', null, 'Tentative de connexion échouée.', ['email' => $email], null);
            $errors[] = 'Identifiants incorrects ou compte désactivé.';
        }
    }

    render_header('Connexion');
    ?>
    <section class="auth-card">
        <p class="eyebrow">Accès réservé</p>
        <h1>Connexion</h1>
        <p>Connectez-vous pour créer, suivre ou administrer les réservations.</p>

        <?php render_errors($errors); ?>

        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>E-mail
                <input type="email" name="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </label>
            <label>Mot de passe
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <a class="form-link" href="<?= e(url_for('forgot_password')) ?>">Mot de passe oublié ?</a>
            <a class="form-link" href="<?= e(url_for('register')) ?>">Demander un compte association</a>
            <button class="button primary" type="submit">Se connecter</button>
        </form>

        <?php if ($loginHelpText !== ''): ?>
            <div class="auth-help"><?= nl2br(e($loginHelpText)) ?></div>
        <?php endif; ?>
    </section>
    <?php
    render_footer();
}

function page_register(): void
{
    if (is_logged_in()) {
        redirect(is_admin() ? 'admin' : 'dashboard');
    }

    $errors = [];
    if (is_post()) {
        ensure_csrf();
        $name = post_string('name');
        $email = post_string('email');
        $password = post_string('password');
        $confirmPassword = post_string('confirm_password');
        $notes = post_string('registration_notes');

        if ($name === '') {
            $errors[] = 'Le nom de l’association est obligatoire.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L’adresse e-mail est invalide.';
        }
        if (strlen($password) < 10) {
            $errors[] = 'Le mot de passe doit contenir au moins 10 caractères.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'La confirmation du mot de passe ne correspond pas.';
        }
        if (strlen($notes) > 1000) {
            $errors[] = 'Le message ne doit pas dépasser 1000 caractères.';
        }

        if (!$errors) {
            try {
                $userId = create_user($name, $email, $password, 'association', false, 'pending', $notes ?: null);
                $mailStatus = notify_registration_requested($userId);
                audit_log('registration_requested', 'user', $userId, 'Demande de compte association créée.', [
                    'email' => $email,
                    'mail_enabled' => $mailStatus['enabled'],
                    'admin_mail_sent' => $mailStatus['admins'],
                ], $userId);
                flash('success', 'Votre demande de compte a été transmise. Un administrateur doit la valider avant la première connexion.');
                redirect('login');
            } catch (PDOException $exception) {
                audit_log('registration_request_failed', 'user', null, 'Demande de compte échouée.', ['email' => $email], null);
                $errors[] = 'Impossible d’enregistrer cette demande. Cette adresse e-mail est peut-être déjà utilisée.';
            }
        }
    }

    render_header('Demande de compte');
    ?>
    <section class="auth-card">
        <p class="eyebrow">Association</p>
        <h1>Demander un compte</h1>
        <p>Votre accès sera activé uniquement après validation par un administrateur.</p>

        <?php render_errors($errors); ?>

        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>Nom de l’association
                <input name="name" required maxlength="160" value="<?= e($_POST['name'] ?? '') ?>">
            </label>
            <label>E-mail de connexion
                <input type="email" name="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </label>
            <label>Mot de passe souhaité
                <input type="password" name="password" autocomplete="new-password" minlength="10" required>
            </label>
            <label>Confirmation
                <input type="password" name="confirm_password" autocomplete="new-password" minlength="10" required>
            </label>
            <label>Message pour la mairie
                <textarea name="registration_notes" rows="3" maxlength="1000" placeholder="Contact, téléphone, précision utile..."><?= e($_POST['registration_notes'] ?? '') ?></textarea>
            </label>
            <button class="button primary" type="submit">Envoyer la demande</button>
            <a class="form-link" href="<?= e(url_for('login')) ?>">Retour à la connexion</a>
        </form>
    </section>
    <?php
    render_footer();
}

function page_forgot_password(): void
{
    if (is_logged_in()) {
        redirect('account');
    }

    $errors = [];
    if (is_post()) {
        ensure_csrf();
        $email = post_string('email');

        if ($email === '') {
            $errors[] = 'Saisissez votre adresse e-mail.';
        } else {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user = find_user_by_email($email);
                if ($user && (int) $user['active'] === 1) {
                    $token = create_password_reset_token((int) $user['id']);
                    $mailStatus = notify_password_reset_requested((int) $user['id'], $token);
                    audit_log('password_reset_requested', 'user', (int) $user['id'], 'Lien de réinitialisation demandé.', [
                        'mail_enabled' => $mailStatus['enabled'],
                        'mail_sent' => $mailStatus['account'],
                    ], (int) $user['id']);
                } else {
                    audit_log('password_reset_requested_unknown', 'user', null, 'Demande de réinitialisation pour une adresse inconnue.', ['email' => $email], null);
                }
            }

            flash('success', 'Si un compte actif correspond à cette adresse, un lien de réinitialisation vient d’être envoyé.');
            redirect('login');
        }
    }

    render_header('Mot de passe oublié');
    ?>
    <section class="auth-card">
        <p class="eyebrow">Accès réservé</p>
        <h1>Mot de passe oublié</h1>
        <p>Indiquez l’adresse e-mail de votre compte pour recevoir un lien temporaire de réinitialisation.</p>

        <?php render_errors($errors); ?>

        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>E-mail
                <input type="email" name="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </label>
            <button class="button primary" type="submit">Recevoir un lien</button>
            <a class="form-link" href="<?= e(url_for('login')) ?>">Retour à la connexion</a>
        </form>
    </section>
    <?php
    render_footer();
}

function page_password_reset(): void
{
    if (is_logged_in()) {
        redirect('account');
    }

    $token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
    $record = password_reset_token_record($token);
    $errors = [];

    if (!$record) {
        render_header('Lien expiré');
        ?>
        <section class="auth-card">
            <p class="eyebrow">Sécurité</p>
            <h1>Lien invalide</h1>
            <p>Ce lien de réinitialisation est invalide, expiré ou déjà utilisé.</p>
            <a class="button primary" href="<?= e(url_for('forgot_password')) ?>">Demander un nouveau lien</a>
        </section>
        <?php
        render_footer();
        return;
    }

    if (is_post()) {
        ensure_csrf();
        $newPassword = post_string('new_password');
        $confirmPassword = post_string('confirm_password');

        if (strlen($newPassword) < 10) {
            $errors[] = 'Le nouveau mot de passe doit contenir au moins 10 caractères.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'La confirmation du mot de passe ne correspond pas.';
        }

        if (!$errors) {
            $pdo = db();
            $pdo->beginTransaction();

            try {
                update_user_password((int) $record['user_id'], $newPassword);
                mark_password_reset_token_used((int) $record['id']);
                $pdo->commit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Le mot de passe n’a pas pu être réinitialisé.';
            }

            if (!$errors) {
                $mailStatus = notify_password_reset_completed((int) $record['user_id']);
                audit_log('password_reset_completed', 'user', (int) $record['user_id'], 'Mot de passe réinitialisé par lien sécurisé.', [
                    'mail_enabled' => $mailStatus['enabled'],
                    'mail_sent' => $mailStatus['account'],
                ], (int) $record['user_id']);
                flash('success', 'Mot de passe réinitialisé. Vous pouvez maintenant vous connecter.');
                redirect('login');
            }
        }
    }

    render_header('Réinitialiser le mot de passe');
    ?>
    <section class="auth-card">
        <p class="eyebrow">Sécurité</p>
        <h1>Nouveau mot de passe</h1>
        <p>Choisissez un nouveau mot de passe pour le compte <?= e($record['email']) ?>.</p>

        <?php render_errors($errors); ?>

        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label>Nouveau mot de passe
                <input type="password" name="new_password" autocomplete="new-password" minlength="10" required>
            </label>
            <label>Confirmation
                <input type="password" name="confirm_password" autocomplete="new-password" minlength="10" required>
            </label>
            <button class="button primary" type="submit">Réinitialiser le mot de passe</button>
        </form>
    </section>
    <?php
    render_footer();
}

function page_logout(): void
{
    ensure_csrf();
    $user = current_user();
    if ($user) {
        audit_log('logout', 'user', (int) $user['id'], 'Déconnexion.');
    }
    $_SESSION = [];
    session_destroy();
    redirect('home');
}

function page_account(): void
{
    require_login();

    $user = current_user();

    render_header('Mon compte');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Compte utilisateur</p>
            <h1>Mon compte</h1>
            <p>Consultez votre accès et modifiez votre mot de passe.</p>
        </div>
    </section>

    <div class="split-layout">
        <section class="panel">
            <h2>Informations</h2>
            <div class="account-summary">
                <p><strong><?= e($user['name']) ?></strong></p>
                <p><?= e($user['email']) ?></p>
                <p><span class="role-badge"><?= e(role_label($user['role'])) ?></span></p>
            </div>
        </section>
        <section class="panel">
            <h2>Modifier le mot de passe</h2>
            <?php render_account_password_form(); ?>
        </section>
    </div>
    <?php
    render_footer();
}

function page_account_password_update(): void
{
    require_login();

    if (!is_post()) {
        redirect('account');
    }

    ensure_csrf();
    $user = current_user();
    $currentPassword = post_string('current_password');
    $newPassword = post_string('new_password');
    $confirmPassword = post_string('confirm_password');
    $errors = [];

    if (!password_verify($currentPassword, $user['password_hash'])) {
        $errors[] = 'Le mot de passe actuel est incorrect.';
    }
    if (strlen($newPassword) < 10) {
        $errors[] = 'Le nouveau mot de passe doit contenir au moins 10 caractères.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'La confirmation du mot de passe ne correspond pas.';
    }

    if ($errors) {
        foreach ($errors as $error) {
            flash('error', $error);
        }
        redirect('account');
    }

    update_user_password((int) $user['id'], $newPassword);
    $mailStatus = notify_password_changed((int) $user['id'], $newPassword, false);
    audit_log('password_changed', 'user', (int) $user['id'], 'Mot de passe changé par l’utilisateur.');
    session_regenerate_id(true);
    flash('success', 'Mot de passe mis à jour.');

    if (!$mailStatus['enabled']) {
        flash('error', 'Notifications e-mail désactivées : le nouveau mot de passe n’a pas été envoyé par mail.');
    } elseif (!$mailStatus['account']) {
        flash('error', 'Le mot de passe a été changé, mais l’e-mail n’a pas pu être envoyé. Vérifiez la configuration mail.');
    } else {
        flash('success', 'Le nouveau mot de passe a été envoyé par e-mail.');
    }

    redirect('account');
}

function page_dashboard(): void
{
    require_login();

    $weekStart = week_start_from_query((string) ($_GET['week'] ?? ''));
    $weekEnd = $weekStart->modify('+7 days');
    $allRooms = rooms(false);
    $reservations = reservations_between($weekStart, $weekEnd);
    $blackouts = blackout_periods_between($weekStart, $weekEnd);
    $mine = reservations_for_user((int) current_user()['id']);

    render_header('Réserver');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Espace réservé</p>
            <h1>Réserver une salle</h1>
            <p>Choisissez une salle, un horaire et indiquez la raison de la réservation. Les créneaux déjà pris sont bloqués automatiquement.</p>
        </div>
    </section>

    <div class="split-layout">
        <section class="panel">
            <h2>Nouveau créneau</h2>
            <?php render_reservation_form(url_for('reserve'), $allRooms); ?>
        </section>
        <section class="panel">
            <h2>Mes réservations</h2>
            <?php render_reservations_table($mine, false); ?>
        </section>
    </div>
    <?php
    render_calendar($allRooms, $reservations, $weekStart, 'dashboard', $blackouts);
    render_footer();
}

function page_reserve(): void
{
    require_login();

    if (!is_post()) {
        redirect('dashboard');
    }

    ensure_csrf();
    $result = create_reservation_from_post((int) current_user()['id']);
    $errors = $result['errors'];

    if ($errors) {
        foreach ($errors as $error) {
            flash('error', $error);
        }
    } else {
        notify_reservation_created((int) $result['reservation_id']);
        audit_log('reservation_created', 'reservation', (int) $result['reservation_id'], 'Réservation créée.');
        if (!empty($result['charter_accepted'])) {
            audit_log('charter_accepted', 'reservation', (int) $result['reservation_id'], 'Charte de réservation acceptée.');
        }
        $approval = app_config('reservations.require_approval', false);
        flash('success', $approval && !is_admin()
            ? 'Réservation enregistrée. Elle est en attente de validation.'
            : 'Réservation enregistrée.');
    }

    redirect(is_admin() ? 'admin' : 'dashboard');
}

function page_cancel_reservation(): void
{
    require_login();

    if (!is_post()) {
        redirect('dashboard');
    }

    ensure_csrf();
    $reservationId = (int) post_string('reservation_id');
    $reservation = reservation_by_id($reservationId);

    if (!$reservation) {
        flash('error', 'Réservation introuvable.');
        redirect('dashboard');
    }

    $user = current_user();
    if (!is_admin() && (int) $reservation['user_id'] !== (int) $user['id']) {
        flash('error', 'Vous ne pouvez pas modifier cette réservation.');
        redirect('dashboard');
    }

    $previousStatus = $reservation['status'];
    update_reservation_status($reservationId, 'cancelled');
    notify_reservation_status_changed($reservationId, $previousStatus);
    audit_log('reservation_cancelled', 'reservation', $reservationId, 'Réservation annulée.', ['previous_status' => $previousStatus]);
    flash('success', 'Réservation annulée.');
    redirect(is_admin() ? 'admin' : 'dashboard');
}

function page_reservation_detail(): void
{
    require_login();

    $reservationId = (int) ($_GET['id'] ?? 0);
    $reservation = reservation_with_context_by_id($reservationId);

    if (!$reservation || !can_access_reservation($reservation)) {
        flash('error', 'Réservation introuvable.');
        redirect(is_admin() ? 'admin' : 'dashboard');
    }

    $roomsList = reservation_edit_rooms($reservation);

    render_header('Réservation');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Réservation</p>
            <h1><?= e($reservation['title']) ?></h1>
            <p><?= e($reservation['room_name']) ?> - <?= e(human_datetime($reservation['starts_at'])) ?></p>
        </div>
        <div class="heading-actions">
            <a class="button secondary" href="<?= e(is_admin() ? url_for('admin') : url_for('dashboard')) ?>">Retour</a>
        </div>
    </section>

    <div class="split-layout">
        <section class="panel">
            <h2>Détails</h2>
            <div class="detail-grid">
                <div>
                    <span class="detail-label">Salle</span>
                    <strong><?= e($reservation['room_name']) ?></strong>
                </div>
                <div>
                    <span class="detail-label">Créneau</span>
                    <strong><?= e(human_datetime($reservation['starts_at'])) ?></strong>
                    <span class="muted"><?= e(time_range($reservation['starts_at'], $reservation['ends_at'])) ?></span>
                </div>
                <div>
                    <span class="detail-label">Statut</span>
                    <span class="status <?= e(status_class($reservation['status'])) ?>"><?= e(status_label($reservation['status'])) ?></span>
                </div>
                <?php if (can_manage_reservation($reservation)): ?>
                    <div>
                        <span class="detail-label">Demandeur</span>
                        <strong><?= e($reservation['user_name']) ?></strong>
                        <span class="muted"><?= e($reservation['user_email']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (can_manage_reservation($reservation) && !empty($reservation['notes'])): ?>
                    <div>
                        <span class="detail-label">Notes</span>
                        <span><?= nl2br(e($reservation['notes'])) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <h2>Modifier</h2>
            <?php if (can_edit_reservation($reservation)): ?>
                <?php render_reservation_edit_form($reservation, $roomsList); ?>
            <?php else: ?>
                <div class="empty-state">Cette réservation ne peut plus être modifiée directement.</div>
            <?php endif; ?>
        </section>
    </div>
    <?php
    render_footer();
}

function page_reservation_update(): void
{
    require_login();

    if (!is_post()) {
        redirect('dashboard');
    }

    ensure_csrf();
    $reservationId = (int) post_string('reservation_id');
    $reservation = reservation_with_context_by_id($reservationId);

    if (!$reservation || !can_edit_reservation($reservation)) {
        flash('error', 'Vous ne pouvez pas modifier cette réservation.');
        redirect(is_admin() ? 'admin' : 'dashboard');
    }

    $roomId = (int) post_string('room_id');
    $title = post_string('title');
    $notes = post_string('notes');
    $startsAt = parse_local_datetime(post_string('starts_at'));
    $endsAt = parse_local_datetime(post_string('ends_at'));
    $status = is_admin()
        ? post_string('status', (string) $reservation['status'])
        : (app_config('reservations.require_approval', false) ? 'pending' : 'approved');
    $errors = [];
    $room = find_room($roomId);

    if (!$room || (!is_admin() && (int) $room['active'] !== 1)) {
        $errors[] = 'La salle sélectionnée n’est pas disponible.';
    }
    if (strlen($title) < 3) {
        $errors[] = 'La raison de la réservation doit contenir au moins 3 caractères.';
    }
    if (!$startsAt || !$endsAt) {
        $errors[] = 'Les dates de début et de fin sont obligatoires.';
    }
    if (!in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
        $errors[] = 'Statut de réservation invalide.';
    }

    if ($startsAt && $endsAt) {
        $duration = ($endsAt->getTimestamp() - $startsAt->getTimestamp()) / 3600;
        $maxDuration = (int) app_config('reservations.max_duration_hours', 24);

        if ($duration <= 0) {
            $errors[] = 'L’heure de fin doit être après l’heure de début.';
        }
        if ($duration > $maxDuration) {
            $errors[] = "La durée maximale est de {$maxDuration} heures.";
        }
        if (!is_admin() && $startsAt < new DateTimeImmutable('-5 minutes')) {
            $errors[] = 'Un créneau passé ne peut pas être réservé.';
        }
    }

    if (!$errors && $startsAt && $endsAt) {
        $startsAtDb = db_datetime($startsAt);
        $endsAtDb = db_datetime($endsAt);

        if (!is_admin() && blackout_overlaps_room($roomId, $startsAtDb, $endsAtDb)) {
            $errors[] = 'Ce créneau est dans une plage non réservable par les associations.';
        }
        if (in_array($status, ['pending', 'approved'], true)
            && reservation_overlaps_except($reservationId, $roomId, $startsAtDb, $endsAtDb)
        ) {
            $errors[] = 'Ce créneau chevauche déjà une réservation existante.';
        }
    }

    if ($errors) {
        foreach ($errors as $error) {
            flash('error', $error);
        }
        redirect('reservation', ['id' => $reservationId]);
    }

    $startsAtDb = db_datetime($startsAt);
    $endsAtDb = db_datetime($endsAt);

    if (!begin_reservation_critical_section()) {
        flash('error', 'Le système de réservation est occupé. Réessayez dans quelques secondes.');
        redirect('reservation', ['id' => $reservationId]);
    }

    try {
        $lockedReservation = reservation_with_context_by_id($reservationId);
        $lockedErrors = [];

        if (!$lockedReservation || !can_edit_reservation($lockedReservation)) {
            $lockedErrors[] = 'Vous ne pouvez pas modifier cette réservation.';
        }
        if (!is_admin() && blackout_overlaps_room($roomId, $startsAtDb, $endsAtDb)) {
            $lockedErrors[] = 'Ce créneau est dans une plage non réservable par les associations.';
        }
        if (in_array($status, ['pending', 'approved'], true)
            && reservation_overlaps_except($reservationId, $roomId, $startsAtDb, $endsAtDb)
        ) {
            $lockedErrors[] = 'Ce créneau chevauche déjà une réservation existante.';
        }

        if ($lockedErrors) {
            rollback_reservation_critical_section();
            foreach ($lockedErrors as $error) {
                flash('error', $error);
            }
            redirect('reservation', ['id' => $reservationId]);
        }

        $previousStatus = (string) $lockedReservation['status'];
        update_reservation_details($reservationId, $roomId, $title, $notes ?: null, $startsAtDb, $endsAtDb, $status);
        commit_reservation_critical_section();
    } catch (Throwable) {
        rollback_reservation_critical_section();
        flash('error', 'La réservation n’a pas pu être mise à jour.');
        redirect('reservation', ['id' => $reservationId]);
    }

    if ($previousStatus !== $status) {
        notify_reservation_status_changed($reservationId, $previousStatus);
    }
    audit_log('reservation_updated', 'reservation', $reservationId, 'Réservation modifiée.', [
        'previous_status' => $previousStatus,
        'new_status' => $status,
        'room_id' => $roomId,
        'starts_at' => db_datetime($startsAt),
        'ends_at' => db_datetime($endsAt),
    ]);

    flash('success', 'Réservation mise à jour.');
    if (!is_admin() && app_config('reservations.require_approval', false)) {
        flash('success', 'La réservation repasse en attente de validation.');
    }
    redirect('reservation', ['id' => $reservationId]);
}

function page_charter_download(): void
{
    require_login();

    $charter = charter_settings();
    if ($charter['file_path'] === '' || $charter['absolute_path'] === '') {
        http_response_code(404);
        render_header('Charte introuvable');
        echo '<section class="panel"><h1>Charte introuvable</h1><p>Aucune charte PDF n’est disponible pour le moment.</p></section>';
        render_footer();
        return;
    }

    $filename = $charter['original_name'] !== '' ? $charter['original_name'] : 'charte-reservation.pdf';
    $filename = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $filename) ?: 'charte-reservation.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($charter['absolute_path']));
    readfile($charter['absolute_path']);
    exit;
}

function page_material(): void
{
    require_login();

    $items = material_items(false);
    $requests = material_requests_for_user((int) current_user()['id']);

    render_header('Demande de matériel');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Evénement</p>
            <h1>Demander du matériel communal</h1>
            <p>Indiquez votre événement, choisissez les quantités souhaitées et suivez la validation de votre demande.</p>
        </div>
    </section>

    <div class="split-layout">
        <section class="panel">
            <h2>Nouvelle demande</h2>
            <?php render_material_request_form($items); ?>
        </section>
        <section class="panel">
            <h2>Mes demandes</h2>
            <?php render_material_requests_table($requests, false); ?>
        </section>
    </div>
    <?php
    render_footer();
}

function page_material_request_create(): void
{
    require_login();

    if (!is_post()) {
        redirect('material');
    }

    ensure_csrf();
    $result = create_material_request_from_post((int) current_user()['id']);

    if ($result['errors']) {
        foreach ($result['errors'] as $error) {
            flash('error', $error);
        }
    } else {
        notify_material_request_created((int) $result['request_id']);
        audit_log('material_request_created', 'material_request', (int) $result['request_id'], 'Demande de matériel créée.');
        flash('success', 'Demande de matériel enregistrée. Elle est en attente de validation.');
    }

    redirect('material');
}

function page_material_request_cancel(): void
{
    require_login();

    if (!is_post()) {
        redirect('material');
    }

    ensure_csrf();
    $requestId = (int) post_string('request_id');
    $request = material_request_by_id($requestId);
    $user = current_user();

    if (!$request || (!is_admin() && (int) $request['user_id'] !== (int) $user['id'])) {
        flash('error', 'Demande de matériel introuvable.');
        redirect('material');
    }

    if ($request['status'] !== 'pending') {
        flash('error', 'Seule une demande en attente peut être annulée directement.');
        redirect(is_admin() ? 'admin_material' : 'material');
    }

    update_material_request_status($requestId, 'cancelled', null, (int) $user['id']);
    audit_log('material_request_cancelled', 'material_request', $requestId, 'Demande de matériel annulée.');
    flash('success', 'Demande de matériel annulée.');
    redirect(is_admin() ? 'admin_material' : 'material');
}

function page_admin(): void
{
    require_admin();

    $weekStart = week_start_from_query((string) ($_GET['week'] ?? ''));
    $weekEnd = $weekStart->modify('+7 days');
    $counts = dashboard_counts();
    $allRooms = rooms(true);
    $activeRooms = rooms(false);
    $associations = active_association_users();
    $reservations = all_reservations();
    $calendarReservations = reservations_between($weekStart, $weekEnd);
    $calendarBlackouts = blackout_periods_between($weekStart, $weekEnd);
    $blackouts = all_blackout_periods();
    $mailSettings = mail_settings();

    render_header('Administration');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Administration générale</p>
            <h1>Piloter les salles et les accès</h1>
            <p>Gérez les salles, les comptes associations et le statut des réservations.</p>
        </div>
        <div class="heading-actions">
            <a class="button secondary" href="<?= e(url_for('admin_requests')) ?>">Demandes à valider</a>
            <a class="button secondary" href="<?= e(url_for('admin_appearance')) ?>">Personnaliser l’interface</a>
            <a class="button secondary" href="<?= e(url_for('admin_accounts')) ?>">Gérer les comptes</a>
            <a class="button secondary" href="<?= e(url_for('admin_material')) ?>">Gérer le matériel</a>
            <a class="button secondary" href="<?= e(url_for('admin_activity')) ?>">Voir le journal</a>
            <a class="button secondary" href="<?= e(url_for('admin_backup')) ?>">Sauvegardes</a>
        </div>
    </section>

    <div class="stats-grid">
        <a class="stat" href="#admin-rooms"><strong><?= e($counts['rooms']) ?></strong><span>Salles actives</span></a>
        <a class="stat" href="<?= e(url_for('admin_accounts')) ?>"><strong><?= e($counts['associations']) ?></strong><span>Associations actives</span></a>
        <a class="stat" href="<?= e(url_for('admin_requests')) ?>#account-requests"><strong><?= e($counts['account_pending']) ?></strong><span>Comptes à valider</span></a>
        <a class="stat" href="<?= e(url_for('admin_requests')) ?>#reservation-requests"><strong><?= e($counts['pending']) ?></strong><span>Réservations en attente</span></a>
        <a class="stat" href="<?= e(url_for('admin_material')) ?>#material-requests"><strong><?= e($counts['material_pending']) ?></strong><span>Matériel en attente</span></a>
        <a class="stat" href="#admin-blackouts"><strong><?= e($counts['blackouts']) ?></strong><span>Plages bloquées</span></a>
        <a class="stat" href="<?= e(url_for('admin_activity')) ?>#activity-log"><strong><?= e($counts['audit_logs']) ?></strong><span>Activités journalisées</span></a>
    </div>

    <div class="split-layout">
        <section class="panel">
            <h2>Bloquer pour une association</h2>
            <?php render_admin_reservation_form($activeRooms, $associations); ?>
        </section>
        <section class="panel">
            <h2>Plage non réservable</h2>
            <?php render_blackout_form($activeRooms); ?>
        </section>
    </div>

    <section class="panel">
        <h2>Notifications e-mail</h2>
        <?php render_mail_settings_form($mailSettings); ?>
    </section>

    <div class="split-layout">
        <section class="panel">
            <h2>Ajouter une salle</h2>
            <?php render_room_form(); ?>
        </section>
        <section class="panel">
            <h2>Comptes associations</h2>
            <div class="empty-state">
                <p>La gestion complète des comptes est disponible sur une page dédiée.</p>
                <a class="button secondary" href="<?= e(url_for('admin_accounts')) ?>">Ouvrir les comptes</a>
            </div>
        </section>
    </div>

    <section class="panel" id="admin-rooms">
        <h2>Salles</h2>
        <div class="admin-list">
            <?php foreach ($allRooms as $room): ?>
                <?php render_room_form($room); ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel" id="admin-reservations">
        <h2>Réservations</h2>
        <?php render_reservations_table($reservations, true); ?>
    </section>

    <section class="panel" id="admin-blackouts">
        <h2>Plages non réservables</h2>
        <?php render_blackouts_table($blackouts); ?>
    </section>

    <?php render_calendar($activeRooms, $calendarReservations, $weekStart, 'admin', $calendarBlackouts); ?>
    <?php
    render_footer();
}

function page_admin_requests(): void
{
    require_admin();

    $accountRequests = pending_registration_users();
    $reservationRequests = pending_reservations();

    render_header('Demandes');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Validation</p>
            <h1>Demandes à traiter</h1>
            <p>Validez les créations de compte association et les réservations en attente.</p>
        </div>
        <div class="heading-actions">
            <a class="button secondary" href="<?= e(url_for('admin')) ?>">Retour administration</a>
            <a class="button secondary" href="<?= e(url_for('admin_accounts')) ?>">Tous les comptes</a>
        </div>
    </section>

    <div class="split-layout">
        <section class="panel" id="account-requests">
            <h2>Comptes à valider</h2>
            <?php render_registration_requests_table($accountRequests); ?>
        </section>
        <section class="panel" id="reservation-requests">
            <h2>Réservations à valider</h2>
            <?php render_reservations_table($reservationRequests, true, 'admin_requests'); ?>
        </section>
    </div>
    <?php
    render_footer();
}

function page_admin_appearance(): void
{
    require_admin();

    $branding = branding_settings();
    $loginHelpText = login_help_text();
    $charter = charter_settings();

    render_header('Apparence');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Paramètres d’interface</p>
            <h1>Personnaliser l’apparence</h1>
            <p>Regroupez ici l’identité visuelle et le message visible sur la page de connexion.</p>
        </div>
        <div class="heading-actions">
            <a class="button secondary" href="<?= e(url_for('admin')) ?>">Retour administration</a>
        </div>
    </section>

    <div class="split-layout">
        <section class="panel">
            <h2>Interface</h2>
            <?php render_branding_form($branding, $loginHelpText); ?>
        </section>
        <section class="panel">
            <h2>Charte de réservation</h2>
            <?php render_charter_form($charter); ?>
        </section>
    </div>
    <?php
    render_footer();
}

function page_admin_accounts(): void
{
    require_admin();

    $search = trim((string) ($_GET['q'] ?? ''));
    $role = (string) ($_GET['role'] ?? '');
    $users = users_for_admin($search, $role);

    render_header('Comptes');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Comptes associations</h1>
            <p>Créez, recherchez et activez les accès des associations et des administrateurs.</p>
        </div>
        <div class="heading-actions">
            <a class="button secondary" href="<?= e(url_for('admin_requests')) ?>">Demandes à valider</a>
            <a class="button secondary" href="<?= e(url_for('admin')) ?>">Retour administration</a>
        </div>
    </section>

    <section class="panel">
        <h2>Créer un compte</h2>
        <?php render_user_create_form(); ?>
    </section>

    <section class="panel">
        <h2>Rechercher</h2>
        <?php render_accounts_search_form($search, $role); ?>
    </section>

    <section class="panel">
        <h2>Liste des comptes</h2>
        <?php render_accounts_list($users); ?>
    </section>
    <?php
    render_footer();
}

function page_admin_material(): void
{
    require_admin();

    $items = material_items(true);
    $requests = all_material_requests();

    render_header('Matériel');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Matériel communal</h1>
            <p>Gérez le stock réservable et validez les demandes de matériel des associations.</p>
        </div>
        <div class="heading-actions">
            <a class="button secondary" href="<?= e(url_for('admin')) ?>">Retour administration</a>
        </div>
    </section>

    <section class="panel" id="material-requests">
        <h2>Demandes de matériel</h2>
        <?php render_material_requests_table($requests, true); ?>
    </section>

    <section class="panel">
        <h2>Ajouter du matériel</h2>
        <?php render_material_item_form(); ?>
    </section>

    <section class="panel">
        <h2>Inventaire</h2>
        <?php render_material_inventory_form($items); ?>
    </section>
    <?php
    render_footer();
}

function page_admin_activity(): void
{
    require_admin();

    $logs = audit_logs();

    render_header('Journal');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Sécurité</p>
            <h1>Journal d’activité</h1>
            <p>Consultez les dernières actions enregistrées en heure locale <?= e(date_default_timezone_get()) ?> pour diagnostiquer un incident ou retrouver une modification.</p>
        </div>
        <div class="heading-actions">
            <form method="post" action="<?= e(url_for('admin_activity_download')) ?>">
                <?= csrf_field() ?>
                <button class="button secondary" type="submit">Télécharger CSV</button>
            </form>
            <form method="post" action="<?= e(url_for('admin_activity_clear')) ?>">
                <?= csrf_field() ?>
                <button class="button danger" type="submit" data-confirm="Vider le journal d’activité ? Les anciennes entrées seront supprimées.">Vider le journal</button>
            </form>
            <a class="button secondary" href="<?= e(url_for('admin')) ?>">Retour administration</a>
        </div>
    </section>

    <section class="panel" id="activity-log">
        <h2>Dernières activités</h2>
        <?php render_audit_logs_table($logs); ?>
    </section>
    <?php
    render_footer();
}

function page_admin_activity_download(): void
{
    require_admin();
    ensure_csrf();

    audit_log('audit_downloaded', 'audit_log', null, 'Journal d’activité téléchargé.');
    send_audit_logs_download();
}

function page_admin_activity_clear(): void
{
    require_admin();
    ensure_csrf();

    $user = current_user();
    clear_audit_logs();
    audit_log('audit_cleared', 'audit_log', null, 'Journal d’activité vidé.', [], $user ? (int) $user['id'] : null);
    flash('success', 'Journal d’activité vidé. Une trace de cette action a été conservée.');
    redirect('admin_activity');
}

function page_admin_export(): void
{
    require_admin();

    $roomsList = rooms(true);
    render_header('Exports PDF');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Documents</p>
            <h1>Exporter les réservations</h1>
            <p>Générez un PDF imprimable sur une période, pour une ou plusieurs salles.</p>
        </div>
        <div class="heading-actions">
            <a class="button secondary" href="<?= e(url_for('admin')) ?>">Retour administration</a>
        </div>
    </section>

    <section class="panel">
        <h2>Critères d’export</h2>
        <?php render_reservation_export_form($roomsList); ?>
    </section>
    <?php
    render_footer();
}

function page_admin_export_download(): void
{
    require_admin();
    ensure_csrf();

    $startsOn = parse_local_date(post_string('starts_on'));
    $endsOn = parse_local_date(post_string('ends_on'));
    $allRoomsSelected = post_string('all_rooms') === '1';
    $roomIds = $allRoomsSelected
        ? []
        : array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['room_ids'] ?? [])), static fn (int $id): bool => $id > 0)));
    $allowedStatuses = reservation_export_statuses();
    $statuses = array_values(array_intersect(array_keys($allowedStatuses), array_map('strval', (array) ($_POST['statuses'] ?? []))));
    $errors = [];

    if (!$startsOn || !$endsOn) {
        $errors[] = 'Choisissez une date de début et une date de fin.';
    }
    if ($startsOn && $endsOn && $endsOn < $startsOn) {
        $errors[] = 'La date de fin doit être après la date de début.';
    }
    if ($startsOn && $endsOn && $endsOn->diff($startsOn)->days > 370) {
        $errors[] = 'La période exportée ne doit pas dépasser un an.';
    }
    if (!$statuses) {
        $errors[] = 'Sélectionnez au moins un statut.';
    }
    $allRooms = rooms(true);
    $knownRoomIds = array_map(static fn (array $room): int => (int) $room['id'], $allRooms);
    $roomIds = array_values(array_intersect($roomIds, $knownRoomIds));
    if (!$allRoomsSelected && !$roomIds) {
        $errors[] = 'Sélectionnez au moins une salle ou toutes les salles.';
    }

    if ($errors) {
        foreach ($errors as $error) {
            flash('error', $error);
        }
        redirect('admin_export');
    }

    $endExclusive = $endsOn->modify('+1 day');
    $selectedRooms = $roomIds
        ? array_values(array_filter($allRooms, static fn (array $room): bool => in_array((int) $room['id'], $roomIds, true)))
        : [];
    $reservations = reservations_for_export($startsOn, $endExclusive, $roomIds, $statuses);
    $pdf = reservations_export_pdf($reservations, $startsOn, $endsOn, $selectedRooms, $statuses);
    $filename = 'reservations-' . $startsOn->format('Ymd') . '-' . $endsOn->format('Ymd') . '.pdf';

    audit_log('reservations_pdf_exported', 'reservation', null, 'Export PDF des réservations téléchargé.', [
        'starts_on' => $startsOn->format('Y-m-d'),
        'ends_on' => $endsOn->format('Y-m-d'),
        'room_ids' => $roomIds,
        'statuses' => $statuses,
        'reservation_count' => count($reservations),
    ]);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

function page_admin_backup(): void
{
    require_admin();

    $backupSettings = automatic_backup_settings();

    render_header('Sauvegardes');
    ?>
    <section class="page-heading">
        <div>
            <p class="eyebrow">Sécurité</p>
            <h1>Sauvegarde et restauration</h1>
            <p>Exportez un fichier complet de l’agenda ou restaurez une sauvegarde après incident.</p>
        </div>
        <div class="heading-actions">
            <a class="button secondary" href="<?= e(url_for('admin')) ?>">Retour administration</a>
        </div>
    </section>

    <div class="split-layout">
        <section class="panel">
            <h2>Créer une sauvegarde</h2>
            <p class="muted">Le fichier JSON contient les comptes, salles, réservations, matériel, demandes, plages bloquées, paramètres, logos et charte téléversés.</p>
            <form method="post" action="<?= e(url_for('admin_backup_download')) ?>" class="form-stack">
                <?= csrf_field() ?>
                <button class="button primary" type="submit">Télécharger une sauvegarde</button>
            </form>
        </section>

        <section class="panel">
            <h2>Sauvegarde automatique</h2>
            <?php render_automatic_backup_form($backupSettings); ?>
        </section>
    </div>

    <section class="panel">
            <h2>Restaurer</h2>
            <p class="muted">La restauration remplace les données actuelles par celles du fichier. Faites une sauvegarde avant de restaurer.</p>
            <form method="post" action="<?= e(url_for('admin_backup_restore')) ?>" class="form-stack" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <label>Fichier de sauvegarde
                    <input type="file" name="backup_file" accept="application/json,.json" required>
                </label>
                <label>Confirmation
                    <input name="confirm_restore" required placeholder="Tapez RESTAURER pour confirmer">
                </label>
                <button class="button danger" type="submit">Restaurer la sauvegarde</button>
            </form>
    </section>
    <?php
    render_footer();
}

function page_admin_backup_settings_save(): void
{
    require_admin();
    ensure_csrf();

    $enabled = post_string('enabled') === '1';
    $recipients = post_string('recipients');
    $frequency = normalize_automatic_backup_frequency(post_string('frequency'));
    $cronToken = post_string('cron_token');
    $regenerateToken = post_string('regenerate_token') === '1';
    $errors = [];

    if ($regenerateToken || $cronToken === '') {
        $cronToken = bin2hex(random_bytes(24));
    } elseif (!preg_match('/^[a-zA-Z0-9_-]{24,128}$/', $cronToken)) {
        $errors[] = 'La clé cron doit contenir entre 24 et 128 caractères alphanumériques.';
    }

    $recipientCandidates = preg_split('/[\s,;]+/', $recipients) ?: [];
    $validRecipients = notification_recipients($recipientCandidates);
    foreach ($recipientCandidates as $recipient) {
        $recipient = trim($recipient);
        if ($recipient !== '' && !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Adresse de sauvegarde invalide : {$recipient}";
        }
    }

    if ($enabled && !$validRecipients) {
        $errors[] = 'Indiquez au moins un destinataire valide pour la sauvegarde automatique.';
    }

    if ($errors) {
        foreach ($errors as $error) {
            flash('error', $error);
        }
        redirect('admin_backup');
    }

    save_setting('backup.auto.enabled', $enabled ? '1' : '0');
    save_setting('backup.auto.recipients', $recipients);
    save_setting('backup.auto.frequency', $frequency);
    save_setting('backup.auto.cron_token', $cronToken);

    audit_log('automatic_backup_settings_updated', 'settings', null, 'Configuration de sauvegarde automatique enregistrée.', [
        'enabled' => $enabled,
        'frequency' => $frequency,
        'recipient_count' => count($validRecipients),
        'token_regenerated' => $regenerateToken,
    ]);

    flash('success', 'Configuration de sauvegarde automatique enregistrée.');
    if ($enabled && !mail_notifications_enabled()) {
        flash('error', 'Attention : les notifications e-mail sont désactivées, la sauvegarde automatique ne pourra pas être envoyée.');
    }
    redirect('admin_backup');
}

function page_admin_backup_run(): void
{
    require_admin();
    ensure_csrf();

    $result = run_automatic_backup(true);
    if ($result['sent']) {
        flash('success', $result['message']);
    } else {
        flash('error', $result['message']);
    }

    redirect('admin_backup');
}

function page_admin_backup_download(): void
{
    require_admin();
    ensure_csrf();
    audit_log('backup_downloaded', 'backup', null, 'Sauvegarde téléchargée.');
    send_backup_download();
}

function page_admin_backup_restore(): void
{
    require_admin();
    ensure_csrf();

    if (post_string('confirm_restore') !== 'RESTAURER') {
        flash('error', 'Tapez RESTAURER pour confirmer la restauration.');
        redirect('admin_backup');
    }

    $errors = restore_backup_from_upload('backup_file');
    if ($errors) {
        foreach ($errors as $error) {
            flash('error', $error);
        }
        audit_log('backup_restore_failed', 'backup', null, 'Restauration échouée.', ['errors' => $errors]);
    } else {
        audit_log('backup_restored', 'backup', null, 'Sauvegarde restaurée.');
        flash('success', 'Sauvegarde restaurée.');
    }

    redirect('admin_backup');
}

function page_admin_room_save(): void
{
    require_admin();
    ensure_csrf();

    $id = post_string('room_id') !== '' ? (int) post_string('room_id') : null;
    $name = post_string('name');
    $description = post_string('description');
    $capacity = post_string('capacity') !== '' ? max(0, (int) post_string('capacity')) : null;
    $color = valid_color(post_string('color', '#2563eb'));
    $active = post_string('active') === '1';

    if ($name === '') {
        flash('error', 'Le nom de la salle est obligatoire.');
        redirect('admin');
    }

    save_room($id, $name, $description ?: null, $capacity, $color, $active);
    audit_log($id ? 'room_updated' : 'room_created', 'room', $id, $id ? 'Salle mise à jour.' : 'Salle ajoutée.', ['name' => $name, 'active' => $active]);
    flash('success', $id ? 'Salle mise à jour.' : 'Salle ajoutée.');
    redirect('admin');
}

function page_admin_user_create(): void
{
    require_admin();
    ensure_csrf();

    $name = post_string('name');
    $email = post_string('email');
    $password = post_string('password');
    $role = post_string('role', 'association');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
        flash('error', 'Vérifiez le nom de l’association, l’e-mail de connexion et le mot de passe.');
        redirect('admin_accounts');
    }

    try {
        $userId = create_user($name, $email, $password, $role, true);
        $mailStatus = notify_account_created($userId, $password);
        audit_log('user_created', 'user', $userId, 'Compte créé depuis l’administration.', ['email' => $email, 'role' => normalize_user_role($role)]);
        flash('success', 'Compte créé. L’association pourra se connecter avec son e-mail.');

        if (!$mailStatus['enabled']) {
            flash('error', 'Notifications e-mail désactivées : aucun mail de création de compte n’a été envoyé.');
        } elseif (!$mailStatus['account'] || !$mailStatus['admins']) {
            flash('error', 'Compte créé, mais au moins un e-mail n’a pas pu être envoyé. Vérifiez la configuration mail.');
        } else {
            flash('success', 'Notifications de création de compte envoyées.');
        }
    } catch (PDOException $exception) {
        audit_log('user_create_failed', 'user', null, 'Création de compte échouée.', ['email' => $email]);
        flash('error', 'Impossible de créer ce compte. L’e-mail existe peut-être déjà.');
    }

    redirect('admin_accounts');
}

function page_admin_user_update(): void
{
    require_admin();
    ensure_csrf();

    $id = (int) post_string('user_id');
    $active = post_string('active') === '1';
    $role = post_string('role', 'association');
    $newPassword = post_string('new_password');
    $managedUser = find_user_by_id($id);

    if (!$managedUser) {
        flash('error', 'Compte introuvable.');
        redirect('admin_accounts');
    }

    if ($id === (int) current_user()['id'] && (!$active || $role !== 'admin')) {
        flash('error', 'Vous ne pouvez pas retirer votre propre accès administrateur.');
        redirect('admin_accounts');
    }

    if ($newPassword !== '' && strlen($newPassword) < 10) {
        flash('error', 'Le nouveau mot de passe doit contenir au moins 10 caractères.');
        redirect('admin_accounts');
    }

    $previousRegistrationStatus = (string) ($managedUser['registration_status'] ?? 'approved');
    update_user_admin_fields($id, $role, $active);
    $mailStatus = null;
    $registrationMailStatus = null;
    if ($previousRegistrationStatus === 'pending' && $active) {
        $registrationMailStatus = notify_registration_status_changed($id, 'approved');
    }
    if ($newPassword !== '') {
        update_user_password($id, $newPassword);
        $mailStatus = notify_password_changed($id, $newPassword, true);
    }
    audit_log('user_updated', 'user', $id, 'Compte mis à jour depuis l’administration.', [
        'email' => $managedUser['email'],
        'role' => normalize_user_role($role),
        'active' => $active,
        'registration_status_changed' => $previousRegistrationStatus === 'pending' && $active,
        'password_changed' => $newPassword !== '',
    ]);

    flash('success', $newPassword !== '' ? 'Compte et mot de passe mis à jour.' : 'Compte mis à jour.');

    if ($mailStatus !== null) {
        if (!$mailStatus['enabled']) {
            flash('error', 'Notifications e-mail désactivées : le nouveau mot de passe n’a pas été envoyé par mail.');
        } elseif (!$mailStatus['account']) {
            flash('error', 'Le mot de passe a été changé, mais l’e-mail n’a pas pu être envoyé. Vérifiez la configuration mail.');
        } else {
            flash('success', 'Le nouveau mot de passe a été envoyé à l’adresse du compte.');
        }
    }
    if ($registrationMailStatus !== null && !$registrationMailStatus['account']) {
        flash('error', 'Le compte a été validé, mais l’e-mail de validation n’a pas pu être envoyé.');
    }

    redirect('admin_accounts');
}

function page_admin_user_delete(): void
{
    require_admin();
    ensure_csrf();

    $id = (int) post_string('user_id');
    $managedUser = find_user_by_id($id);

    if (!$managedUser) {
        flash('error', 'Compte introuvable.');
        redirect('admin_accounts');
    }

    if ($managedUser['role'] === 'admin' && admin_count(true, $id) < 1) {
        flash('error', 'Impossible de supprimer ce compte : il doit rester au moins un administrateur actif.');
        redirect('admin_accounts');
    }

    $isCurrentUser = $id === (int) current_user()['id'];
    audit_log('user_deleted', 'user', $id, 'Compte supprimé depuis l’administration.', [
        'email' => $managedUser['email'],
        'role' => $managedUser['role'],
    ]);
    delete_user_by_id($id);

    if ($isCurrentUser) {
        $_SESSION = [];
        session_destroy();
        redirect('login');
    }

    flash('success', 'Compte supprimé.');
    redirect('admin_accounts');
}

function page_admin_registration_status(): void
{
    require_admin();
    ensure_csrf();

    $id = (int) post_string('user_id');
    $status = post_string('status');
    $managedUser = find_user_by_id($id);

    if (!$managedUser || normalize_user_role((string) $managedUser['role']) !== 'association') {
        flash('error', 'Demande de compte introuvable.');
        redirect('admin_requests');
    }

    if (!in_array($status, ['approved', 'rejected'], true)) {
        flash('error', 'Statut de demande invalide.');
        redirect('admin_requests');
    }

    update_user_registration_status($id, $status);
    $mailStatus = notify_registration_status_changed($id, $status);
    audit_log('registration_status_changed', 'user', $id, 'Statut de demande de compte modifié.', [
        'email' => $managedUser['email'],
        'previous_status' => $managedUser['registration_status'] ?? 'approved',
        'new_status' => $status,
    ]);

    flash('success', $status === 'approved' ? 'Compte association validé.' : 'Demande de compte refusée.');
    if (!$mailStatus['enabled']) {
        flash('error', 'Notifications e-mail désactivées : l’association n’a pas été prévenue.');
    } elseif (!$mailStatus['account']) {
        flash('error', 'Le statut a été mis à jour, mais l’e-mail n’a pas pu être envoyé.');
    }

    redirect('admin_requests');
}

function page_admin_material_item_save(): void
{
    require_admin();
    ensure_csrf();

    $id = post_string('material_item_id') !== '' ? (int) post_string('material_item_id') : null;
    $name = post_string('name');
    $category = post_string('category', 'autre');
    $description = post_string('description');
    $quantityTotal = post_string('quantity_total') !== '' ? (int) post_string('quantity_total') : 0;
    $active = post_string('active') === '1';

    if ($name === '') {
        flash('error', 'Le nom du matériel est obligatoire.');
        redirect('admin_material');
    }

    save_material_item($id, $name, $category, $description ?: null, $quantityTotal, $active);
    audit_log($id ? 'material_item_updated' : 'material_item_created', 'material_item', $id, $id ? 'Matériel mis à jour.' : 'Matériel ajouté.', [
        'name' => $name,
        'category' => normalize_material_category($category),
        'quantity_total' => $quantityTotal,
        'active' => $active,
    ]);
    flash('success', $id ? 'Matériel mis à jour.' : 'Matériel ajouté.');
    redirect('admin_material');
}

function page_admin_material_items_save(): void
{
    require_admin();
    ensure_csrf();

    $knownItems = material_items(true);
    $postedItems = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];
    $validatedItems = [];
    $errors = [];

    foreach ($knownItems as $item) {
        $id = (int) $item['id'];
        $posted = is_array($postedItems[$id] ?? null) ? $postedItems[$id] : [];
        $name = trim((string) ($posted['name'] ?? ''));
        $category = normalize_material_category((string) ($posted['category'] ?? 'autre'));
        $quantityTotal = max(0, (int) ($posted['quantity_total'] ?? 0));
        $description = trim((string) ($posted['description'] ?? ''));
        $active = isset($posted['active']) && (string) $posted['active'] === '1';

        if ($name === '') {
            $errors[] = 'Le nom du matériel #' . $id . ' est obligatoire.';
        }

        $validatedItems[] = [
            'id' => $id,
            'name' => $name,
            'category' => $category,
            'quantity_total' => $quantityTotal,
            'description' => $description !== '' ? $description : null,
            'active' => $active,
        ];
    }

    if ($errors) {
        foreach ($errors as $error) {
            flash('error', $error);
        }
        redirect('admin_material');
    }

    foreach ($validatedItems as $item) {
        save_material_item(
            $item['id'],
            $item['name'],
            $item['category'],
            $item['description'],
            $item['quantity_total'],
            $item['active']
        );
    }

    audit_log('material_inventory_updated', 'material_item', null, 'Inventaire matériel mis à jour.', [
        'updated_count' => count($validatedItems),
        'items' => array_map(
            static fn (array $item): array => [
                'id' => $item['id'],
                'name' => $item['name'],
                'category' => $item['category'],
                'quantity_total' => $item['quantity_total'],
                'active' => $item['active'],
            ],
            $validatedItems
        ),
    ]);

    flash('success', 'Inventaire matériel enregistré.');
    redirect('admin_material');
}

function page_admin_material_request_status(): void
{
    require_admin();
    ensure_csrf();

    $requestId = (int) post_string('request_id');
    $status = post_string('status');
    $adminNotes = post_string('admin_notes');
    $request = material_request_by_id($requestId);

    if (!$request) {
        flash('error', 'Demande de matériel introuvable.');
        redirect('admin_material');
    }

    if (!in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
        flash('error', 'Statut de demande invalide.');
        redirect('admin_material');
    }

    if ($status === 'approved') {
        $availabilityErrors = material_request_availability_errors($requestId);
        if ($availabilityErrors) {
            foreach ($availabilityErrors as $error) {
                flash('error', $error);
            }
            redirect('admin_material');
        }
    }

    $previousStatus = $request['status'];
    update_material_request_status($requestId, $status, $adminNotes ?: null, (int) current_user()['id']);
    $mailStatus = notify_material_request_status_changed($requestId, $previousStatus);
    audit_log('material_request_status_changed', 'material_request', $requestId, 'Statut de demande de matériel modifié.', [
        'previous_status' => $previousStatus,
        'new_status' => $status,
    ]);

    flash('success', 'Statut de demande de matériel mis à jour.');
    if ($status === 'approved' && $previousStatus !== $status) {
        if (!$mailStatus['enabled']) {
            flash('error', 'Demande validée, mais les notifications e-mail sont désactivées : aucun PDF n’a été envoyé.');
        } elseif (!$mailStatus['requester'] || !$mailStatus['admins']) {
            flash('error', 'Demande validée, mais au moins un e-mail avec PDF n’a pas pu être envoyé. Vérifiez la configuration mail.');
        } else {
            flash('success', 'E-mails récapitulatifs avec PDF envoyés.');
        }
    }

    redirect('admin_material');
}

function page_admin_material_request_delete(): void
{
    require_admin();
    ensure_csrf();

    $requestId = (int) post_string('request_id');
    $request = material_request_with_context_by_id($requestId);

    if (!$request) {
        flash('error', 'Demande de matériel introuvable.');
        redirect('admin_material');
    }

    if (!material_request_can_be_deleted($request)) {
        flash('error', 'Cette demande ne peut être supprimée que si elle est annulée, refusée ou passée.');
        redirect('admin_material');
    }

    $items = material_request_items($requestId);
    audit_log('material_request_deleted', 'material_request', $requestId, 'Demande de matériel supprimée depuis l’administration.', [
        'event_title' => $request['event_title'],
        'status' => $request['status'],
        'starts_at' => $request['starts_at'],
        'ends_at' => $request['ends_at'],
        'user_email' => $request['user_email'] ?? '',
        'items' => array_map(
            static fn (array $item): array => [
                'name' => $item['name'],
                'quantity' => (int) $item['quantity'],
            ],
            $items
        ),
    ]);

    delete_material_request($requestId);
    flash('success', 'Demande de matériel supprimée.');
    redirect('admin_material');
}

function page_admin_reservation_create(): void
{
    require_admin();
    ensure_csrf();

    $associationId = (int) post_string('user_id');
    $association = find_user_by_id($associationId);

    if (!$association || normalize_user_role($association['role']) !== 'association' || (int) $association['active'] !== 1) {
        flash('error', 'Choisissez une association active.');
        redirect('admin');
    }

    $result = create_reservation_from_post($associationId, [
        'force_status' => 'approved',
        'respect_blackouts' => false,
    ]);

    if ($result['errors']) {
        foreach ($result['errors'] as $error) {
            flash('error', $error);
        }
        redirect('admin');
    }

    notify_reservation_created((int) $result['reservation_id']);
    audit_log('admin_reservation_created', 'reservation', (int) $result['reservation_id'], 'Créneau bloqué par un administrateur.', ['association_id' => $associationId]);
    flash('success', 'Créneau bloqué au nom de l’association.');
    redirect('admin');
}

function page_admin_reservation_status(): void
{
    require_admin();
    ensure_csrf();

    $reservationId = (int) post_string('reservation_id');
    $status = post_string('status');
    $returnTo = post_string('return_to') === 'admin_requests' ? 'admin_requests' : 'admin';
    $reservation = reservation_by_id($reservationId);

    if (!$reservation) {
        flash('error', 'Réservation introuvable.');
        redirect($returnTo);
    }

    if (!in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
        flash('error', 'Statut de réservation invalide.');
        redirect($returnTo);
    }

    if (!begin_reservation_critical_section()) {
        flash('error', 'Le système de réservation est occupé. Réessayez dans quelques secondes.');
        redirect($returnTo);
    }

    try {
        $lockedReservation = reservation_by_id($reservationId);
        if (!$lockedReservation) {
            rollback_reservation_critical_section();
            flash('error', 'Réservation introuvable.');
            redirect($returnTo);
        }

        if (in_array($status, ['pending', 'approved'], true)
            && reservation_overlaps_except(
                $reservationId,
                (int) $lockedReservation['room_id'],
                $lockedReservation['starts_at'],
                $lockedReservation['ends_at']
            )
        ) {
            rollback_reservation_critical_section();
            flash('error', 'Ce statut créerait un chevauchement avec un autre créneau.');
            redirect($returnTo);
        }

        $previousStatus = $lockedReservation['status'];
        update_reservation_status($reservationId, $status);
        commit_reservation_critical_section();
    } catch (Throwable) {
        rollback_reservation_critical_section();
        flash('error', 'Le statut de réservation n’a pas pu être mis à jour.');
        redirect($returnTo);
    }

    notify_reservation_status_changed($reservationId, $previousStatus);
    audit_log('reservation_status_changed', 'reservation', $reservationId, 'Statut de réservation modifié.', [
        'previous_status' => $previousStatus,
        'new_status' => $status,
    ]);
    flash('success', 'Statut de réservation mis à jour.');
    redirect($returnTo);
}

function page_admin_blackout_save(): void
{
    require_admin();
    ensure_csrf();

    $roomIdValue = post_string('room_id');
    $roomId = $roomIdValue === '' ? null : (int) $roomIdValue;
    $title = post_string('title');
    $notes = post_string('notes');
    $startsAt = parse_local_datetime(post_string('starts_at'));
    $endsAt = parse_local_datetime(post_string('ends_at'));
    $errors = [];

    $room = $roomId !== null ? find_room($roomId) : null;
    if ($roomId !== null && (!$room || (int) $room['active'] !== 1)) {
        $errors[] = 'La salle sélectionnée est introuvable.';
    }
    if (strlen($title) < 3) {
        $errors[] = 'La raison de la plage non réservable doit contenir au moins 3 caractères.';
    }
    if (!$startsAt || !$endsAt) {
        $errors[] = 'Les dates de début et de fin sont obligatoires.';
    }
    if ($startsAt && $endsAt && $endsAt <= $startsAt) {
        $errors[] = 'L’heure de fin doit être après l’heure de début.';
    }

    if ($errors) {
        foreach ($errors as $error) {
            flash('error', $error);
        }
        redirect('admin');
    }

    create_blackout_period(
        $roomId,
        $title,
        $notes ?: null,
        db_datetime($startsAt),
        db_datetime($endsAt),
        (int) current_user()['id']
    );
    audit_log('blackout_created', 'blackout_period', null, 'Plage non réservable ajoutée.', ['room_id' => $roomId, 'title' => $title]);
    flash('success', 'Plage non réservable ajoutée.');
    redirect('admin');
}

function page_admin_blackout_toggle(): void
{
    require_admin();
    ensure_csrf();

    $blackoutId = (int) post_string('blackout_id');
    $active = post_string('active') === '1';
    update_blackout_status($blackoutId, $active);
    audit_log('blackout_status_changed', 'blackout_period', $blackoutId, $active ? 'Plage réactivée.' : 'Plage désactivée.', ['active' => $active]);
    flash('success', $active ? 'Plage réactivée.' : 'Plage désactivée.');
    redirect('admin');
}

function page_admin_branding_save(): void
{
    require_admin();
    ensure_csrf();

    $entityName = post_string('entity_name');
    $loginHelpText = post_string('login_help_text');
    if ($entityName === '') {
        flash('error', 'Le nom affiché est obligatoire.');
        redirect('admin_appearance');
    }
    if (strlen($loginHelpText) > 2000) {
        flash('error', 'Le texte de la page de connexion est trop long.');
        redirect('admin_appearance');
    }

    $branding = branding_settings();
    $logoPath = $branding['logo_path'];

    if (post_string('remove_logo') === '1') {
        delete_public_upload($logoPath);
        $logoPath = '';
    }

    $upload = handle_logo_upload('logo');
    if ($upload['error'] !== null) {
        flash('error', $upload['error']);
        redirect('admin_appearance');
    }

    if ($upload['path'] !== null) {
        delete_public_upload($logoPath);
        $logoPath = $upload['path'];
    }

    save_setting('branding.entity_name', $entityName);
    save_setting('branding.logo_path', $logoPath);
    save_setting('branding.primary_color', valid_color(post_string('primary_color')));
    save_setting('branding.accent_color', valid_color(post_string('accent_color')));
    save_setting('branding.background_color', valid_color(post_string('background_color')));
    save_setting('ui.login_help_text', $loginHelpText);

    audit_log('branding_updated', 'settings', null, 'Personnalisation enregistrée.', [
        'entity_name' => $entityName,
        'login_help_text_enabled' => $loginHelpText !== '',
    ]);
    flash('success', 'Personnalisation enregistrée.');
    redirect('admin_appearance');
}

function page_admin_charter_save(): void
{
    require_admin();
    ensure_csrf();

    $charter = charter_settings();
    $filePath = $charter['file_path'];
    $originalName = $charter['original_name'];
    $uploadedAt = $charter['uploaded_at'];

    if (post_string('remove_charter') === '1') {
        delete_storage_upload($filePath);
        $filePath = '';
        $originalName = '';
        $uploadedAt = '';
    }

    $upload = handle_charter_upload('charter_file');
    if ($upload['error'] !== null) {
        flash('error', $upload['error']);
        redirect('admin_appearance');
    }

    if ($upload['path'] !== null) {
        delete_storage_upload($filePath);
        $filePath = $upload['path'];
        $originalName = $upload['original_name'] ?? 'charte-reservation.pdf';
        $uploadedAt = db_datetime(new DateTimeImmutable());
    }

    save_setting('charter.file_path', $filePath);
    save_setting('charter.original_name', $originalName);
    save_setting('charter.uploaded_at', $uploadedAt);

    audit_log('charter_updated', 'settings', null, $filePath !== '' ? 'Charte de réservation enregistrée.' : 'Charte de réservation retirée.', [
        'file_name' => $originalName,
        'enabled' => $filePath !== '',
    ]);
    flash('success', $filePath !== '' ? 'Charte de réservation enregistrée.' : 'Charte de réservation retirée.');
    redirect('admin_appearance');
}

function page_admin_mail_save(): void
{
    require_admin();
    ensure_csrf();

    $enabled = post_string('enabled') === '1';
    $fromEmail = post_string('from_email');
    $fromName = post_string('from_name');
    $adminRecipients = post_string('admin_recipients');
    $publicUrl = post_string('public_url');
    $errors = [];

    if ($enabled && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L’adresse e-mail d’expédition est invalide.';
    }
    if ($enabled && $fromName === '') {
        $errors[] = 'Le nom d’expéditeur est obligatoire.';
    }

    $recipientCandidates = preg_split('/[\s,;]+/', $adminRecipients) ?: [];
    foreach ($recipientCandidates as $recipient) {
        $recipient = trim($recipient);
        if ($recipient !== '' && !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Adresse administrateur invalide : {$recipient}";
        }
    }

    if ($publicUrl !== '') {
        $scheme = parse_url($publicUrl, PHP_URL_SCHEME);
        if (filter_var($publicUrl, FILTER_VALIDATE_URL) === false || !in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            $errors[] = 'L’URL publique de l’agenda est invalide.';
        } else {
            $publicUrl = rtrim($publicUrl, '/') . '/';
        }
    }

    if ($errors) {
        foreach ($errors as $error) {
            flash('error', $error);
        }
        redirect('admin');
    }

    save_setting('mail.enabled', $enabled ? '1' : '0');
    save_setting('mail.from_email', $fromEmail);
    save_setting('mail.from_name', $fromName);
    save_setting('mail.admin_recipients', $adminRecipients);
    save_setting('site.public_url', $publicUrl);

    audit_log('mail_settings_updated', 'settings', null, 'Configuration e-mail enregistrée.', ['enabled' => $enabled, 'public_url' => $publicUrl]);
    flash('success', 'Configuration e-mail enregistrée.');
    redirect('admin');
}

function create_reservation_from_post(int $userId, array $options = []): array
{
    $result = ['errors' => [], 'reservation_id' => null, 'charter_accepted' => false];
    $errors = [];
    $roomId = (int) post_string('room_id');
    $title = post_string('title');
    $notes = post_string('notes');
    $startsAt = parse_local_datetime(post_string('starts_at'));
    $endsAt = parse_local_datetime(post_string('ends_at'));
    $room = find_room($roomId);
    $respectBlackouts = $options['respect_blackouts'] ?? !is_admin();
    $charter = charter_settings();
    $requiresCharter = ($options['require_charter'] ?? !is_admin()) && $charter['file_path'] !== '';

    if (!$room || (int) $room['active'] !== 1) {
        $errors[] = 'La salle sélectionnée n’est pas disponible.';
    }
    if (strlen($title) < 3) {
        $errors[] = 'La raison de la réservation doit contenir au moins 3 caractères.';
    }
    if (!$startsAt || !$endsAt) {
        $errors[] = 'Les dates de début et de fin sont obligatoires.';
    }
    if ($requiresCharter && post_string('charter_acceptance') !== '1') {
        $errors[] = 'Vous devez accepter la charte de réservation avant d’enregistrer la demande.';
    }

    if ($startsAt && $endsAt) {
        $duration = ($endsAt->getTimestamp() - $startsAt->getTimestamp()) / 3600;
        $maxDuration = (int) app_config('reservations.max_duration_hours', 24);

        if ($duration <= 0) {
            $errors[] = 'L’heure de fin doit être après l’heure de début.';
        }
        if ($duration > $maxDuration) {
            $errors[] = "La durée maximale est de {$maxDuration} heures.";
        }
        if (!is_admin() && $startsAt < new DateTimeImmutable('-5 minutes')) {
            $errors[] = 'Un créneau passé ne peut pas être réservé.';
        }
    }

    if ($errors) {
        return ['errors' => $errors, 'reservation_id' => null];
    }

    $startsAtDb = db_datetime($startsAt);
    $endsAtDb = db_datetime($endsAt);
    $status = $options['force_status'] ?? (app_config('reservations.require_approval', false) && !is_admin() ? 'pending' : 'approved');
    if (!in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
        $status = 'approved';
    }

    if (!begin_reservation_critical_section()) {
        return ['errors' => ['Le système de réservation est occupé. Réessayez dans quelques secondes.'], 'reservation_id' => null];
    }

    try {
        if ($respectBlackouts && blackout_overlaps_room($roomId, $startsAtDb, $endsAtDb)) {
            rollback_reservation_critical_section();
            return ['errors' => ['Ce créneau est dans une plage non réservable par les associations.'], 'reservation_id' => null];
        }

        if (reservation_overlaps($roomId, $startsAtDb, $endsAtDb)) {
            rollback_reservation_critical_section();
            return ['errors' => ['Ce créneau chevauche déjà une réservation existante.'], 'reservation_id' => null];
        }

        $result['reservation_id'] = create_reservation_record($roomId, $userId, $title, $notes ?: null, $startsAtDb, $endsAtDb, $status);
        if ($requiresCharter) {
            record_charter_acceptance($userId, (int) $result['reservation_id'], $charter['file_path']);
            $result['charter_accepted'] = true;
        }
        commit_reservation_critical_section();
    } catch (Throwable $exception) {
        rollback_reservation_critical_section();
        return ['errors' => ['La réservation n’a pas pu être enregistrée.'], 'reservation_id' => null];
    }

    return $result;
}

function create_material_request_from_post(int $userId): array
{
    $result = ['errors' => [], 'request_id' => null];
    $errors = [];
    $eventTitle = post_string('event_title');
    $eventLocation = post_string('event_location');
    $notes = post_string('notes');
    $startsAt = parse_local_datetime(post_string('starts_at'));
    $endsAt = parse_local_datetime(post_string('ends_at'));
    $activeItems = material_items(false);
    $postedItems = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];
    $selectedItems = [];

    if (strlen($eventTitle) < 3) {
        $errors[] = 'Le nom de l’événement doit contenir au moins 3 caractères.';
    }
    if (!$startsAt || !$endsAt) {
        $errors[] = 'Les dates de début et de fin de l’événement sont obligatoires.';
    }
    if ($startsAt && $endsAt && $endsAt <= $startsAt) {
        $errors[] = 'L’heure de fin doit être après l’heure de début.';
    }
    if (!is_admin() && $startsAt && $startsAt < new DateTimeImmutable('-5 minutes')) {
        $errors[] = 'Une demande de matériel ne peut pas commencer dans le passé.';
    }

    foreach ($activeItems as $item) {
        $itemId = (int) $item['id'];
        $quantity = max(0, (int) ($postedItems[$itemId] ?? 0));
        if ($quantity <= 0) {
            continue;
        }

        if ($quantity > (int) $item['quantity_total']) {
            $errors[] = $item['name'] . ' : la quantité demandée dépasse le stock total.';
            continue;
        }

        $selectedItems[$itemId] = $quantity;
    }

    if (!$selectedItems) {
        $errors[] = 'Sélectionnez au moins un matériel et une quantité.';
    }

    if (!$errors && $startsAt && $endsAt) {
        $startsAtDb = db_datetime($startsAt);
        $endsAtDb = db_datetime($endsAt);

        foreach ($selectedItems as $itemId => $quantity) {
            $item = find_material_item((int) $itemId);
            $available = material_available_quantity((int) $itemId, $startsAtDb, $endsAtDb);
            if ($quantity > $available) {
                $errors[] = ($item['name'] ?? 'Matériel') . ' : ' . $available . ' disponible(s) sur ce créneau.';
            }
        }
    }

    if ($errors) {
        return ['errors' => $errors, 'request_id' => null];
    }

    try {
        $result['request_id'] = create_material_request_record(
            $userId,
            $eventTitle,
            $eventLocation ?: null,
            $notes ?: null,
            db_datetime($startsAt),
            db_datetime($endsAt),
            $selectedItems
        );
    } catch (Throwable $exception) {
        return ['errors' => ['La demande de matériel n’a pas pu être enregistrée.'], 'request_id' => null];
    }

    return $result;
}

function render_errors(array $errors): void
{
    if (!$errors) {
        return;
    }

    echo '<div class="form-errors">';
    foreach ($errors as $error) {
        echo '<p>' . e($error) . '</p>';
    }
    echo '</div>';
}

function send_audit_logs_download(): never
{
    $logs = audit_logs(10000);
    $filename = 'journal-activite-' . date('Ymd-His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    if ($output !== false) {
        fputcsv($output, ['Date locale', 'Fuseau', 'Utilisateur', 'E-mail', 'Action', 'Type', 'ID', 'Message', 'Détails', 'IP', 'Navigateur'], ';');

        foreach ($logs as $log) {
            fputcsv($output, [
                human_datetime($log['created_at']),
                date_default_timezone_get(),
                $log['user_name'] ?: 'Visiteur',
                $log['user_email'] ?: '',
                $log['action'],
                $log['entity_type'] ?: '',
                $log['entity_id'] ?: '',
                $log['message'] ?: '',
                $log['details'] ?: '',
                $log['ip_address'] ?: '',
                $log['user_agent'] ?: '',
            ], ';');
        }

        fclose($output);
    }

    exit;
}

function default_datetime_values(): array
{
    $now = new DateTimeImmutable('+1 hour');
    $defaultStartDate = $now->setTime((int) $now->format('H'), 0);

    return [
        $defaultStartDate->format('Y-m-d\TH:i'),
        $defaultStartDate->modify('+1 hour')->format('Y-m-d\TH:i'),
    ];
}

function default_export_dates(): array
{
    $today = new DateTimeImmutable('today');

    return [
        $today->format('Y-m-d'),
        $today->modify('+1 month')->format('Y-m-d'),
    ];
}

function reservation_export_statuses(): array
{
    return [
        'approved' => status_label('approved'),
        'pending' => status_label('pending'),
        'rejected' => status_label('rejected'),
        'cancelled' => status_label('cancelled'),
    ];
}

function material_request_is_past(array $request): bool
{
    if (empty($request['ends_at'])) {
        return false;
    }

    try {
        $endsAt = local_datetime((string) $request['ends_at']);
        $now = new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get()));

        return $endsAt < $now;
    } catch (Exception) {
        return false;
    }
}

function material_request_can_be_deleted(array $request): bool
{
    return in_array((string) ($request['status'] ?? ''), ['rejected', 'cancelled'], true)
        || material_request_is_past($request);
}

function reservation_is_past(array $reservation): bool
{
    if (empty($reservation['ends_at'])) {
        return false;
    }

    try {
        $endsAt = local_datetime((string) $reservation['ends_at']);
        $now = new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get()));

        return $endsAt < $now;
    } catch (Exception) {
        return false;
    }
}

function can_access_reservation(array $reservation): bool
{
    return current_user() !== null;
}

function can_manage_reservation(array $reservation): bool
{
    $user = current_user();

    return $user !== null
        && (is_admin() || (int) ($reservation['user_id'] ?? 0) === (int) $user['id']);
}

function can_edit_reservation(array $reservation): bool
{
    if (!can_manage_reservation($reservation)) {
        return false;
    }

    if (is_admin()) {
        return true;
    }

    return in_array((string) ($reservation['status'] ?? ''), ['pending', 'approved'], true)
        && !reservation_is_past($reservation);
}

function reservation_datetime_input_value(string $value): string
{
    return local_datetime($value)->format('Y-m-d\TH:i');
}

function reservation_edit_rooms(array $reservation): array
{
    $roomsList = rooms(is_admin());
    $currentRoomId = (int) ($reservation['room_id'] ?? 0);

    foreach ($roomsList as $room) {
        if ((int) $room['id'] === $currentRoomId) {
            return $roomsList;
        }
    }

    $currentRoom = find_room($currentRoomId);
    if ($currentRoom) {
        array_unshift($roomsList, $currentRoom);
    }

    return $roomsList;
}

function handle_logo_upload(string $field): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return ['path' => null, 'error' => null];
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Le logo n’a pas pu être téléversé.'];
    }

    if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['path' => null, 'error' => 'Le logo ne doit pas dépasser 2 Mo.'];
    }

    $imageInfo = @getimagesize((string) $file['tmp_name']);
    $allowed = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
    if (!isset($allowed[$mime])) {
        return ['path' => null, 'error' => 'Le logo doit être une image PNG, JPG, WEBP ou GIF.'];
    }

    $directory = __DIR__ . '/../public/uploads/branding';
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    $filename = 'logo-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $target = $directory . '/' . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
        return ['path' => null, 'error' => 'Le logo n’a pas pu être enregistré.'];
    }

    return ['path' => 'uploads/branding/' . $filename, 'error' => null];
}

function handle_charter_upload(string $field): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return ['path' => null, 'original_name' => null, 'error' => null];
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'original_name' => null, 'error' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'original_name' => null, 'error' => 'La charte PDF n’a pas pu être téléversée.'];
    }

    if ((int) ($file['size'] ?? 0) > 10 * 1024 * 1024) {
        return ['path' => null, 'original_name' => null, 'error' => 'La charte PDF ne doit pas dépasser 10 Mo.'];
    }

    $originalName = basename((string) ($file['name'] ?? 'charte-reservation.pdf'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $signature = file_get_contents((string) $file['tmp_name'], false, null, 0, 4);

    if ($extension !== 'pdf' || $signature !== '%PDF') {
        return ['path' => null, 'original_name' => null, 'error' => 'La charte doit être un fichier PDF valide.'];
    }

    $directory = __DIR__ . '/../storage/charters';
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    $filename = 'charte-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.pdf';
    $target = $directory . '/' . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
        return ['path' => null, 'original_name' => null, 'error' => 'La charte PDF n’a pas pu être enregistrée.'];
    }

    return [
        'path' => 'storage/charters/' . $filename,
        'original_name' => $originalName ?: 'charte-reservation.pdf',
        'error' => null,
    ];
}

function delete_public_upload(string $path): void
{
    if (!str_starts_with($path, 'uploads/branding/')) {
        return;
    }

    $file = realpath(__DIR__ . '/../public/' . $path);
    $base = realpath(__DIR__ . '/../public/uploads/branding');

    if ($file && $base && str_starts_with($file, $base) && is_file($file)) {
        unlink($file);
    }
}

function delete_storage_upload(string $path): void
{
    if (!str_starts_with($path, 'storage/charters/')) {
        return;
    }

    $file = realpath(__DIR__ . '/../' . $path);
    $base = realpath(__DIR__ . '/../storage/charters');

    if ($file && $base && str_starts_with($file, $base) && is_file($file)) {
        unlink($file);
    }
}

function render_reservation_form(string $action, array $roomsList): void
{
    [$defaultStart, $defaultEnd] = default_datetime_values();
    $charter = charter_settings();
    $requiresCharter = !is_admin() && $charter['file_path'] !== '';
    ?>
    <form method="post" action="<?= e($action) ?>" class="form-stack reservation-form">
        <?= csrf_field() ?>
        <label>Salle
            <select name="room_id" required>
                <?php foreach ($roomsList as $room): ?>
                    <option value="<?= e($room['id']) ?>"><?= e($room['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Raison de la réservation
            <input name="title" maxlength="180" required placeholder="Réunion, assemblée générale, répétition, événement...">
        </label>
        <div class="form-grid two">
            <label>Début
                <input type="datetime-local" name="starts_at" required value="<?= e($defaultStart) ?>">
            </label>
            <label>Fin
                <input type="datetime-local" name="ends_at" required value="<?= e($defaultEnd) ?>">
            </label>
        </div>
        <label>Notes
            <textarea name="notes" rows="3" placeholder="Informations utiles pour la mairie"></textarea>
        </label>
        <?php if ($requiresCharter): ?>
            <div class="charter-box">
                <a class="form-link" href="<?= e(url_for('charter_download')) ?>" target="_blank" rel="noopener">Lire la charte de réservation PDF</a>
                <label class="switch-line">
                    <input type="checkbox" name="charter_acceptance" value="1" required>
                    J’ai lu et j’accepte la charte de réservation.
                </label>
            </div>
        <?php endif; ?>
        <button class="button primary" type="submit">Enregistrer la réservation</button>
    </form>
    <?php
}

function render_reservation_edit_form(array $reservation, array $roomsList): void
{
    ?>
    <form method="post" action="<?= e(url_for('reservation_update')) ?>" class="form-stack reservation-form">
        <?= csrf_field() ?>
        <input type="hidden" name="reservation_id" value="<?= e($reservation['id']) ?>">
        <label>Salle
            <select name="room_id" required>
                <?php foreach ($roomsList as $room): ?>
                    <option value="<?= e($room['id']) ?>" <?= (int) $room['id'] === (int) $reservation['room_id'] ? 'selected' : '' ?>>
                        <?= e($room['name']) ?><?= (int) ($room['active'] ?? 1) === 1 ? '' : ' (désactivée)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Raison de la réservation
            <input name="title" maxlength="180" required value="<?= e($reservation['title']) ?>">
        </label>
        <div class="form-grid two">
            <label>Début
                <input type="datetime-local" name="starts_at" required value="<?= e(reservation_datetime_input_value($reservation['starts_at'])) ?>">
            </label>
            <label>Fin
                <input type="datetime-local" name="ends_at" required value="<?= e(reservation_datetime_input_value($reservation['ends_at'])) ?>">
            </label>
        </div>
        <label>Notes
            <textarea name="notes" rows="3" placeholder="Informations utiles pour la mairie"><?= e($reservation['notes'] ?? '') ?></textarea>
        </label>
        <?php if (is_admin()): ?>
            <label>Statut
                <select name="status">
                    <?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $reservation['status'] === $status ? 'selected' : '' ?>><?= e(status_label($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <button class="button primary" type="submit">Enregistrer les modifications</button>
    </form>
    <?php
}

function render_admin_reservation_form(array $roomsList, array $associations): void
{
    if (!$roomsList || !$associations) {
        echo '<div class="empty-state">Ajoutez au moins une salle active et un compte association actif.</div>';
        return;
    }

    [$defaultStart, $defaultEnd] = default_datetime_values();
    ?>
    <form method="post" action="<?= e(url_for('admin_reservation_create')) ?>" class="form-stack reservation-form">
        <?= csrf_field() ?>
        <label>Association
            <select name="user_id" required>
                <?php foreach ($associations as $association): ?>
                    <option value="<?= e($association['id']) ?>"><?= e($association['name']) ?> - <?= e($association['email']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Salle
            <select name="room_id" required>
                <?php foreach ($roomsList as $room): ?>
                    <option value="<?= e($room['id']) ?>"><?= e($room['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Raison
            <input name="title" maxlength="180" required placeholder="Réservation mairie pour l’association...">
        </label>
        <div class="form-grid two">
            <label>Début
                <input type="datetime-local" name="starts_at" required value="<?= e($defaultStart) ?>">
            </label>
            <label>Fin
                <input type="datetime-local" name="ends_at" required value="<?= e($defaultEnd) ?>">
            </label>
        </div>
        <label>Notes
            <textarea name="notes" rows="3" placeholder="Information interne ou consigne particulière"></textarea>
        </label>
        <button class="button primary" type="submit">Bloquer ce créneau</button>
    </form>
    <?php
}

function render_blackout_form(array $roomsList): void
{
    [$defaultStart, $defaultEnd] = default_datetime_values();
    ?>
    <form method="post" action="<?= e(url_for('admin_blackout_save')) ?>" class="form-stack reservation-form">
        <?= csrf_field() ?>
        <label>Salle concernée
            <select name="room_id">
                <option value="">Toutes les salles</option>
                <?php foreach ($roomsList as $room): ?>
                    <option value="<?= e($room['id']) ?>"><?= e($room['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Raison du blocage
            <input name="title" maxlength="180" required placeholder="Travaux, maintenance, fermeture exceptionnelle...">
        </label>
        <div class="form-grid two">
            <label>Début
                <input type="datetime-local" name="starts_at" required value="<?= e($defaultStart) ?>">
            </label>
            <label>Fin
                <input type="datetime-local" name="ends_at" required value="<?= e($defaultEnd) ?>">
            </label>
        </div>
        <label>Notes
            <textarea name="notes" rows="3" placeholder="Détail visible dans l’administration"></textarea>
        </label>
        <button class="button secondary" type="submit">Ajouter la plage</button>
    </form>
    <?php
}

function render_material_request_form(array $items): void
{
    if (!$items) {
        echo '<div class="empty-state">Aucun matériel actif avec stock disponible. Un administrateur doit compléter l’inventaire.</div>';
        return;
    }

    [$defaultStart, $defaultEnd] = default_datetime_values();
    $currentCategory = '';
    ?>
    <form method="post" action="<?= e(url_for('material_request_create')) ?>" class="form-stack reservation-form">
        <?= csrf_field() ?>
        <label>Nom de l’événement
            <input name="event_title" maxlength="180" required placeholder="Loto, tournoi, exposition, fête associative...">
        </label>
        <label>Lieu de l’événement
            <input name="event_location" maxlength="180" placeholder="Salle, espace extérieur, adresse...">
        </label>
        <div class="form-grid two">
            <label>Début
                <input type="datetime-local" name="starts_at" required value="<?= e($defaultStart) ?>">
            </label>
            <label>Fin
                <input type="datetime-local" name="ends_at" required value="<?= e($defaultEnd) ?>">
            </label>
        </div>
        <div class="material-picker">
            <?php foreach ($items as $item): ?>
                <?php if ($currentCategory !== $item['category']): ?>
                    <?php $currentCategory = $item['category']; ?>
                    <h3><?= e(material_category_label($currentCategory)) ?></h3>
                <?php endif; ?>
                <label class="material-choice">
                    <span>
                        <strong><?= e($item['name']) ?></strong>
                        <?php if (!empty($item['description'])): ?>
                            <small><?= e($item['description']) ?></small>
                        <?php endif; ?>
                        <small>Stock total : <?= e($item['quantity_total']) ?></small>
                    </span>
                    <input
                        type="number"
                        name="items[<?= e($item['id']) ?>]"
                        min="0"
                        max="<?= e($item['quantity_total']) ?>"
                        value="0"
                    >
                </label>
            <?php endforeach; ?>
        </div>
        <label>Notes
            <textarea name="notes" rows="3" placeholder="Consignes de livraison, retrait, contact sur place..."></textarea>
        </label>
        <button class="button primary" type="submit">Envoyer la demande</button>
    </form>
    <?php
}

function render_material_item_form(?array $item = null): void
{
    $isEdit = $item !== null;
    ?>
    <form method="post" action="<?= e(url_for('admin_material_item_save')) ?>" class="<?= $isEdit ? 'admin-row material-row' : 'form-stack' ?>">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="material_item_id" value="<?= e($item['id']) ?>">
        <?php endif; ?>
        <label>Nom
            <input name="name" required value="<?= e($item['name'] ?? '') ?>" placeholder="Tables pliantes, barnum 3x3...">
        </label>
        <label>Type
            <select name="category">
                <?php foreach (material_categories() as $category => $label): ?>
                    <option value="<?= e($category) ?>" <?= ($item['category'] ?? 'tables_bancs') === $category ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Stock
            <input type="number" name="quantity_total" min="0" value="<?= e($item['quantity_total'] ?? '0') ?>">
        </label>
        <label>Description
            <input name="description" value="<?= e($item['description'] ?? '') ?>">
        </label>
        <label class="switch-line">
            <input type="checkbox" name="active" value="1" <?= !$isEdit || (int) $item['active'] === 1 ? 'checked' : '' ?>>
            Réservable
        </label>
        <button class="button secondary" type="submit"><?= $isEdit ? 'Enregistrer' : 'Ajouter le matériel' ?></button>
    </form>
    <?php
}

function render_material_inventory_form(array $items): void
{
    if (!$items) {
        echo '<div class="empty-state">Aucun matériel enregistré.</div>';
        return;
    }
    ?>
    <form method="post" action="<?= e(url_for('admin_material_items_save')) ?>" class="form-stack material-inventory-form">
        <?= csrf_field() ?>
        <div class="admin-list">
            <?php foreach ($items as $item): ?>
                <?php $id = (int) $item['id']; ?>
                <div class="admin-row material-inventory-row">
                    <label>Nom
                        <input name="items[<?= e($id) ?>][name]" required value="<?= e($item['name']) ?>">
                    </label>
                    <label>Type
                        <select name="items[<?= e($id) ?>][category]">
                            <?php foreach (material_categories() as $category => $label): ?>
                                <option value="<?= e($category) ?>" <?= $item['category'] === $category ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Stock
                        <input type="number" name="items[<?= e($id) ?>][quantity_total]" min="0" value="<?= e($item['quantity_total']) ?>">
                    </label>
                    <label>Description
                        <input name="items[<?= e($id) ?>][description]" value="<?= e($item['description'] ?? '') ?>">
                    </label>
                    <label class="switch-line">
                        <input type="checkbox" name="items[<?= e($id) ?>][active]" value="1" <?= (int) $item['active'] === 1 ? 'checked' : '' ?>>
                        Réservable
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="button primary" type="submit">Enregistrer l’inventaire</button>
    </form>
    <?php
}

function render_branding_form(array $branding, string $loginHelpText): void
{
    ?>
    <form method="post" action="<?= e(url_for('admin_branding_save')) ?>" class="form-stack" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="branding-form">
            <label>Nom affiché
                <input name="entity_name" required value="<?= e($branding['entity_name']) ?>">
            </label>
            <div class="branding-colors">
                <label>Couleur principale
                    <input type="color" name="primary_color" value="<?= e($branding['primary_color']) ?>">
                </label>
                <label>Couleur secondaire
                    <input type="color" name="accent_color" value="<?= e($branding['accent_color']) ?>">
                </label>
                <label>Couleur de fond
                    <input type="color" name="background_color" value="<?= e($branding['background_color']) ?>">
                </label>
            </div>
        </div>
        <div class="branding-logo-field">
            <?php if ($branding['logo_path'] !== ''): ?>
                <img class="branding-preview" src="<?= e(public_url($branding['logo_path'])) ?>" alt="">
                <label class="switch-line">
                    <input type="checkbox" name="remove_logo" value="1">
                    Retirer le logo actuel
                </label>
            <?php endif; ?>
            <label>Logo personnalisé
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif">
            </label>
        </div>
        <label>Texte affiché pour les personnes sans compte
            <textarea name="login_help_text" rows="4" placeholder="Vous n’avez pas encore de compte ? Contactez la mairie pour demander un accès."><?= e($loginHelpText) ?></textarea>
        </label>
        <button class="button secondary" type="submit">Enregistrer l’apparence</button>
    </form>
    <?php
}

function render_charter_form(array $charter): void
{
    $hasCharter = $charter['file_path'] !== '';
    ?>
    <form method="post" action="<?= e(url_for('admin_charter_save')) ?>" class="form-stack" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($hasCharter): ?>
            <div class="document-summary">
                <strong><?= e($charter['original_name'] ?: 'Charte de réservation') ?></strong>
                <?php if ($charter['uploaded_at'] !== ''): ?>
                    <span>Ajoutée le <?= e(human_datetime($charter['uploaded_at'])) ?></span>
                <?php endif; ?>
                <a class="form-link" href="<?= e(url_for('charter_download')) ?>" target="_blank" rel="noopener">Ouvrir le PDF</a>
            </div>
            <label class="switch-line">
                <input type="checkbox" name="remove_charter" value="1">
                Retirer la charte actuelle
            </label>
        <?php else: ?>
            <p class="muted">Aucune charte n’est actuellement demandée avant réservation.</p>
        <?php endif; ?>
        <label>Charte PDF
            <input type="file" name="charter_file" accept="application/pdf,.pdf">
        </label>
        <button class="button secondary" type="submit"><?= $hasCharter ? 'Mettre à jour la charte' : 'Ajouter la charte' ?></button>
    </form>
    <?php
}

function render_mail_settings_form(array $settings): void
{
    ?>
    <form method="post" action="<?= e(url_for('admin_mail_save')) ?>" class="form-stack">
        <?= csrf_field() ?>
        <label class="switch-line">
            <input type="checkbox" name="enabled" value="1" <?= $settings['enabled'] ? 'checked' : '' ?>>
            Activer les notifications
        </label>
        <label>Adresse e-mail d’envoi
            <input type="email" name="from_email" value="<?= e($settings['from_email']) ?>" placeholder="no-reply@votre-domaine.fr">
        </label>
        <label>Nom d’expéditeur
            <input name="from_name" value="<?= e($settings['from_name']) ?>" placeholder="Agenda des salles communales">
        </label>
        <label>URL publique de l’agenda
            <input type="url" name="public_url" value="<?= e($settings['public_url']) ?>" placeholder="https://votre-domaine.fr/agenda/public/">
        </label>
        <label>Destinataires administrateurs
            <textarea name="admin_recipients" rows="4" placeholder="mairie@votre-domaine.fr"><?= e($settings['admin_recipients']) ?></textarea>
        </label>
        <button class="button secondary" type="submit">Enregistrer les e-mails</button>
    </form>
    <?php
}

function render_automatic_backup_form(array $settings): void
{
    $cliScriptPath = realpath(__DIR__ . '/../cron/backup.php') ?: (__DIR__ . '/../cron/backup.php');
    $cronUrl = configured_public_url() !== ''
        ? configured_public_url() . 'cron_backup.php?token=' . rawurlencode((string) $settings['cron_token'])
        : 'cron_backup.php?token=' . rawurlencode((string) $settings['cron_token']);
    $frequencies = automatic_backup_frequency_options();
    $lastRunAt = $settings['last_run_at'] !== '' ? human_datetime((string) $settings['last_run_at']) : 'Jamais';
    $lastAttemptAt = $settings['last_attempt_at'] !== '' ? human_datetime((string) $settings['last_attempt_at']) : 'Jamais';
    $statusLabels = [
        'success' => 'Dernier envoi réussi',
        'failed' => 'Dernier envoi échoué',
        'skipped' => 'Ignoré',
        '' => 'Aucun envoi réalisé',
    ];
    ?>
    <form method="post" action="<?= e(url_for('admin_backup_settings_save')) ?>" class="form-stack">
        <?= csrf_field() ?>
        <label class="switch-line">
            <input type="checkbox" name="enabled" value="1" <?= $settings['enabled'] ? 'checked' : '' ?>>
            Activer l’envoi automatique
        </label>
        <label>Destinataire(s) de la sauvegarde
            <textarea name="recipients" rows="3" placeholder="sauvegarde@votre-domaine.fr"><?= e($settings['recipients']) ?></textarea>
        </label>
        <label>Fréquence
            <select name="frequency">
                <?php foreach ($frequencies as $frequency => $label): ?>
                    <option value="<?= e($frequency) ?>" <?= $settings['frequency'] === $frequency ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Clé pour appel URL externe
            <input name="cron_token" value="<?= e($settings['cron_token']) ?>" minlength="24" maxlength="128">
        </label>
        <div class="form-actions">
            <button class="button secondary" type="submit">Enregistrer l’automatisation</button>
            <button class="button secondary" type="submit" name="regenerate_token" value="1">Renouveler la clé</button>
        </div>
    </form>

    <div class="empty-state compact">
        <p><strong>Dernière sauvegarde envoyée :</strong> <?= e($lastRunAt) ?></p>
        <p><strong>Dernière tentative :</strong> <?= e($lastAttemptAt) ?></p>
        <p><strong>Statut :</strong> <?= e($statusLabels[$settings['last_status']] ?? $settings['last_status']) ?></p>
        <?php if ($settings['last_error'] !== ''): ?>
            <p class="muted"><?= e($settings['last_error']) ?></p>
        <?php endif; ?>
    </div>

    <form method="post" action="<?= e(url_for('admin_backup_run')) ?>" class="form-stack">
        <?= csrf_field() ?>
        <button class="button primary" type="submit">Envoyer une sauvegarde maintenant</button>
    </form>

    <div class="empty-state compact">
        <p><strong>Tâche planifiée OVH</strong></p>
        <p class="muted">Utilisez le script PHP ci-dessous. Aucun token n’est nécessaire dans ce mode.</p>
        <code><?= e($cliScriptPath) ?></code>
        <p class="muted">Si l’interface demande une commande complète :</p>
        <code>php <?= e($cliScriptPath) ?></code>
        <p><strong>Appel URL externe</strong></p>
        <p class="muted">À utiliser seulement avec un service qui appelle une URL publique.</p>
        <code><?= e($cronUrl) ?></code>
    </div>
    <?php
}

function render_reservation_export_form(array $roomsList): void
{
    [$defaultStart, $defaultEnd] = default_export_dates();
    $statuses = reservation_export_statuses();
    ?>
    <form method="post" action="<?= e(url_for('admin_export_download')) ?>" class="form-stack">
        <?= csrf_field() ?>
        <div class="form-grid two">
            <label>Début
                <input type="date" name="starts_on" required value="<?= e($defaultStart) ?>">
            </label>
            <label>Fin
                <input type="date" name="ends_on" required value="<?= e($defaultEnd) ?>">
            </label>
        </div>

        <fieldset class="checkbox-list" data-export-rooms>
            <legend>Salles</legend>
            <label class="switch-line">
                <input type="checkbox" name="all_rooms" value="1" checked data-export-all>
                Toutes les salles
            </label>
            <?php foreach ($roomsList as $room): ?>
                <label class="switch-line">
                    <input type="checkbox" name="room_ids[]" value="<?= e($room['id']) ?>" data-export-room>
                    <?= e($room['name']) ?><?= (int) $room['active'] === 1 ? '' : ' (désactivée)' ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <fieldset class="checkbox-list compact">
            <legend>Statuts</legend>
            <?php foreach ($statuses as $status => $label): ?>
                <label class="switch-line">
                    <input type="checkbox" name="statuses[]" value="<?= e($status) ?>" <?= in_array($status, ['approved', 'pending'], true) ? 'checked' : '' ?>>
                    <?= e($label) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <button class="button primary" type="submit">Télécharger le PDF</button>
    </form>
    <?php
}

function render_room_form(?array $room = null): void
{
    $isEdit = $room !== null;
    ?>
    <form method="post" action="<?= e(url_for('admin_room_save')) ?>" class="<?= $isEdit ? 'admin-row room-row' : 'form-stack' ?>">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="room_id" value="<?= e($room['id']) ?>">
        <?php endif; ?>
        <label>Nom
            <input name="name" required value="<?= e($room['name'] ?? '') ?>">
        </label>
        <label>Capacité
            <input type="number" name="capacity" min="0" value="<?= e($room['capacity'] ?? '') ?>">
        </label>
        <label>Couleur
            <input type="color" name="color" value="<?= e($room['color'] ?? '#2563eb') ?>">
        </label>
        <label>Description
            <input name="description" value="<?= e($room['description'] ?? '') ?>">
        </label>
        <label class="switch-line">
            <input type="checkbox" name="active" value="1" <?= !$isEdit || (int) $room['active'] === 1 ? 'checked' : '' ?>>
            Active
        </label>
        <button class="button secondary" type="submit"><?= $isEdit ? 'Enregistrer' : 'Ajouter la salle' ?></button>
    </form>
    <?php
}

function render_user_create_form(): void
{
    ?>
    <form method="post" action="<?= e(url_for('admin_user_create')) ?>" class="form-stack">
        <?= csrf_field() ?>
        <label>Nom de l’association
            <input name="name" autocomplete="organization" required placeholder="Association sportive, comité des fêtes...">
        </label>
        <label>E-mail de connexion
            <input type="email" name="email" autocomplete="email" required>
        </label>
        <label>Mot de passe provisoire
            <input type="password" name="password" autocomplete="new-password" minlength="10" required>
        </label>
        <label>Type de compte
            <select name="role">
                <option value="association">Association</option>
                <option value="admin">Administrateur</option>
            </select>
        </label>
        <button class="button secondary" type="submit">Créer le compte</button>
    </form>
    <?php
}

function render_account_password_form(): void
{
    ?>
    <form method="post" action="<?= e(url_for('account_password_update')) ?>" class="form-stack">
        <?= csrf_field() ?>
        <label>Mot de passe actuel
            <input type="password" name="current_password" autocomplete="current-password" required>
        </label>
        <label>Nouveau mot de passe
            <input type="password" name="new_password" autocomplete="new-password" minlength="10" required>
        </label>
        <label>Confirmer le nouveau mot de passe
            <input type="password" name="confirm_password" autocomplete="new-password" minlength="10" required>
        </label>
        <button class="button primary" type="submit">Modifier le mot de passe</button>
    </form>
    <?php
}

function render_accounts_search_form(string $search, string $role): void
{
    ?>
    <form method="get" action="index.php" class="account-search">
        <input type="hidden" name="page" value="admin_accounts">
        <label>Nom ou e-mail
            <input name="q" value="<?= e($search) ?>" placeholder="Rechercher une association...">
        </label>
        <label>Type
            <select name="role">
                <option value="">Tous les comptes</option>
                <option value="association" <?= $role === 'association' ? 'selected' : '' ?>>Associations</option>
                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrateurs</option>
            </select>
        </label>
        <button class="button secondary" type="submit">Filtrer</button>
        <a class="button secondary" href="<?= e(url_for('admin_accounts')) ?>">Réinitialiser</a>
    </form>
    <?php
}

function render_accounts_list(array $users): void
{
    if (!$users) {
        echo '<div class="empty-state">Aucun compte ne correspond à cette recherche.</div>';
        return;
    }

    echo '<div class="admin-list">';
    foreach ($users as $managedUser) {
        render_user_admin_form($managedUser);
    }
    echo '</div>';
}

function render_registration_requests_table(array $users): void
{
    if (!$users) {
        echo '<div class="empty-state">Aucune demande de compte en attente.</div>';
        return;
    }
    ?>
    <div class="admin-list">
        <?php foreach ($users as $managedUser): ?>
            <div class="admin-row registration-row">
                <div class="row-main">
                    <strong><?= e($managedUser['name']) ?></strong>
                    <span><?= e($managedUser['email']) ?></span>
                    <span class="status status-pending"><?= e(registration_status_label($managedUser['registration_status'])) ?></span>
                    <?php if (!empty($managedUser['registration_notes'])): ?>
                        <span><?= nl2br(e($managedUser['registration_notes'])) ?></span>
                    <?php endif; ?>
                </div>
                <span class="muted">Demande du <?= e(human_datetime($managedUser['created_at'])) ?></span>
                <form method="post" action="<?= e(url_for('admin_registration_status')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= e($managedUser['id']) ?>">
                    <input type="hidden" name="status" value="approved">
                    <button class="button tiny" type="submit">Valider</button>
                </form>
                <form method="post" action="<?= e(url_for('admin_registration_status')) ?>" class="inline-form" data-confirm="Refuser cette demande de compte ?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= e($managedUser['id']) ?>">
                    <input type="hidden" name="status" value="rejected">
                    <button class="button tiny danger" type="submit">Refuser</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function render_user_admin_form(array $managedUser): void
{
    ?>
    <form method="post" action="<?= e(url_for('admin_user_update')) ?>" class="admin-row user-row">
        <?= csrf_field() ?>
        <input type="hidden" name="user_id" value="<?= e($managedUser['id']) ?>">
        <div class="row-main">
            <strong><?= e($managedUser['name']) ?></strong>
            <span><?= e($managedUser['email']) ?></span>
            <span class="role-badge"><?= e(role_label($managedUser['role'])) ?></span>
            <?php if (($managedUser['registration_status'] ?? 'approved') !== 'approved'): ?>
                <span class="status <?= e(status_class($managedUser['registration_status'] === 'pending' ? 'pending' : 'rejected')) ?>">
                    <?= e(registration_status_label((string) $managedUser['registration_status'])) ?>
                </span>
            <?php endif; ?>
        </div>
        <label>Type de compte
            <select name="role">
                <option value="association" <?= normalize_user_role($managedUser['role']) === 'association' ? 'selected' : '' ?>>Association</option>
                <option value="admin" <?= $managedUser['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
            </select>
        </label>
        <label class="switch-line">
            <input type="checkbox" name="active" value="1" <?= (int) $managedUser['active'] === 1 ? 'checked' : '' ?>>
            Actif
        </label>
        <label>Nouveau mot de passe
            <input type="password" name="new_password" autocomplete="new-password" minlength="10" placeholder="Laisser vide">
        </label>
        <button class="button secondary" type="submit">Mettre à jour</button>
        <button
            class="button danger"
            type="submit"
            formaction="<?= e(url_for('admin_user_delete')) ?>"
            data-confirm="Supprimer ce compte ? Les réservations et demandes de matériel liées à ce compte seront aussi supprimées."
        >Supprimer</button>
    </form>
    <?php
}

function render_reservations_table(array $reservations, bool $adminMode, string $returnPage = ''): void
{
    if (!$reservations) {
        echo '<div class="empty-state">Aucune réservation à afficher.</div>';
        return;
    }
    ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Salle</th>
                    <th>Créneau</th>
                    <th>Raison</th>
                    <?php if ($adminMode): ?><th>Association / demandeur</th><?php endif; ?>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td>
                            <span class="mini-dot" style="--room-color: <?= e($reservation['room_color'] ?? '#2563eb') ?>"></span>
                            <?= e($reservation['room_name']) ?>
                        </td>
                        <td><?= e(human_datetime($reservation['starts_at'])) ?><br><span class="muted"><?= e(time_range($reservation['starts_at'], $reservation['ends_at'])) ?></span></td>
                        <td><?= e($reservation['title']) ?></td>
                        <?php if ($adminMode): ?>
                            <td><?= e($reservation['user_name']) ?><br><span class="muted"><?= e($reservation['user_email']) ?></span></td>
                        <?php endif; ?>
                        <td><span class="status <?= e(status_class($reservation['status'])) ?>"><?= e(status_label($reservation['status'])) ?></span></td>
                        <td>
                            <?php if ($adminMode): ?>
                                <form method="post" action="<?= e(url_for('admin_reservation_status')) ?>" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="reservation_id" value="<?= e($reservation['id']) ?>">
                                    <?php if ($returnPage !== ''): ?>
                                        <input type="hidden" name="return_to" value="<?= e($returnPage) ?>">
                                    <?php endif; ?>
                                    <select name="status">
                                        <?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $status): ?>
                                            <option value="<?= e($status) ?>" <?= $reservation['status'] === $status ? 'selected' : '' ?>><?= e(status_label($status)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="button tiny" type="submit">OK</button>
                                </form>
                            <?php elseif (in_array($reservation['status'], ['pending', 'approved'], true)): ?>
                                <form method="post" action="<?= e(url_for('cancel_reservation')) ?>" data-confirm="Annuler cette réservation ?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="reservation_id" value="<?= e($reservation['id']) ?>">
                                    <button class="button tiny danger" type="submit">Annuler</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function render_material_requests_table(array $requests, bool $adminMode): void
{
    if (!$requests) {
        echo '<div class="empty-state">Aucune demande de matériel à afficher.</div>';
        return;
    }
    ?>
    <div class="material-request-list">
        <?php foreach ($requests as $request): ?>
            <?php
            $items = material_request_items((int) $request['id']);
            $isPast = material_request_is_past($request);
            $canDelete = $adminMode && material_request_can_be_deleted($request);
            ?>
            <article class="material-request-card">
                <div class="material-request-content">
                    <div class="material-request-header">
                        <div>
                            <strong class="material-request-title"><?= e($request['event_title']) ?></strong>
                            <?php if (!empty($request['event_location'])): ?>
                                <span class="muted"><?= e($request['event_location']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="status-stack">
                            <span class="status <?= e(status_class($request['status'])) ?>"><?= e(status_label($request['status'])) ?></span>
                            <?php if ($isPast): ?>
                                <span class="status status-muted">Passée</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="material-request-details">
                        <div>
                            <span class="detail-label">Créneau</span>
                            <strong><?= e(human_datetime($request['starts_at'])) ?></strong>
                            <span class="muted"><?= e(time_range($request['starts_at'], $request['ends_at'])) ?></span>
                        </div>
                        <?php if ($adminMode): ?>
                            <div>
                                <span class="detail-label">Demandeur</span>
                                <strong><?= e($request['user_name']) ?></strong>
                                <span class="muted"><?= e($request['user_email']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($request['notes'])): ?>
                            <div>
                                <span class="detail-label">Note demandeur</span>
                                <span><?= nl2br(e($request['notes'])) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($request['admin_notes'])): ?>
                            <div>
                                <span class="detail-label">Note admin</span>
                                <span><?= nl2br(e($request['admin_notes'])) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <table class="material-items-table">
                        <thead>
                            <tr>
                                <th>Désignation</th>
                                <th>Quantité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($items): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= e($item['name']) ?></td>
                                        <td><?= e($item['quantity']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="muted">Aucun matériel renseigné.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="material-request-actions">
                    <?php if ($adminMode): ?>
                        <form method="post" action="<?= e(url_for('admin_material_request_status')) ?>" class="material-status-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="request_id" value="<?= e($request['id']) ?>">
                            <select name="status">
                                <?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $status): ?>
                                    <option value="<?= e($status) ?>" <?= $request['status'] === $status ? 'selected' : '' ?>><?= e(status_label($status)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="admin_notes" rows="2" placeholder="Note admin"><?= e($request['admin_notes'] ?? '') ?></textarea>
                            <button class="button tiny" type="submit">Mettre à jour</button>
                        </form>
                        <?php if ($canDelete): ?>
                            <form method="post" action="<?= e(url_for('admin_material_request_delete')) ?>" data-confirm="Supprimer définitivement cette demande de matériel ?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="request_id" value="<?= e($request['id']) ?>">
                                <button class="button tiny danger" type="submit">Supprimer</button>
                            </form>
                        <?php endif; ?>
                    <?php elseif ($request['status'] === 'pending'): ?>
                        <form method="post" action="<?= e(url_for('material_request_cancel')) ?>" data-confirm="Annuler cette demande de matériel ?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="request_id" value="<?= e($request['id']) ?>">
                            <button class="button tiny danger" type="submit">Annuler</button>
                        </form>
                    <?php else: ?>
                        <span class="muted">-</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function render_audit_logs_table(array $logs): void
{
    if (!$logs) {
        echo '<div class="empty-state">Aucune activité enregistrée pour le moment.</div>';
        return;
    }
    ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date locale</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Elément</th>
                    <th>Message</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= e(human_datetime($log['created_at'])) ?><br><span class="muted"><?= e(date_default_timezone_get()) ?></span></td>
                        <td>
                            <?= e($log['user_name'] ?: 'Visiteur') ?>
                            <?php if (!empty($log['user_email'])): ?>
                                <br><span class="muted"><?= e($log['user_email']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= e($log['action']) ?></code></td>
                        <td>
                            <?= e($log['entity_type'] ?: '-') ?>
                            <?php if (!empty($log['entity_id'])): ?>
                                <br><span class="muted">#<?= e($log['entity_id']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e($log['message'] ?: '-') ?>
                            <?php if (!empty($log['details'])): ?>
                                <details class="log-details">
                                    <summary>Détails</summary>
                                    <pre><?= e($log['details']) ?></pre>
                                </details>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e($log['ip_address'] ?: '-') ?>
                            <?php if (!empty($log['user_agent'])): ?>
                                <br><span class="muted"><?= e($log['user_agent']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function render_blackouts_table(array $blackouts): void
{
    if (!$blackouts) {
        echo '<div class="empty-state">Aucune plage non réservable à afficher.</div>';
        return;
    }
    ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Portée</th>
                    <th>Créneau</th>
                    <th>Raison</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blackouts as $blackout): ?>
                    <tr>
                        <td><?= e($blackout['room_name'] ?: 'Toutes les salles') ?></td>
                        <td><?= e(human_datetime($blackout['starts_at'])) ?><br><span class="muted"><?= e(time_range($blackout['starts_at'], $blackout['ends_at'])) ?></span></td>
                        <td>
                            <?= e($blackout['title']) ?>
                            <?php if (!empty($blackout['notes'])): ?>
                                <br><span class="muted"><?= e($blackout['notes']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status <?= (int) $blackout['active'] === 1 ? 'status-blackout' : 'status-muted' ?>">
                                <?= (int) $blackout['active'] === 1 ? 'Active' : 'Désactivée' ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" action="<?= e(url_for('admin_blackout_toggle')) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="blackout_id" value="<?= e($blackout['id']) ?>">
                                <input type="hidden" name="active" value="<?= (int) $blackout['active'] === 1 ? '0' : '1' ?>">
                                <button class="button tiny" type="submit"><?= (int) $blackout['active'] === 1 ? 'Désactiver' : 'Réactiver' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
