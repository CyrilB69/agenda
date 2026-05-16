<?php
declare(strict_types=1);

function mail_notifications_enabled(): bool
{
    return mail_settings()['enabled'];
}

function mail_settings(): array
{
    $configuredRecipients = app_config('mail.admin_recipients', []);
    $defaultRecipients = is_array($configuredRecipients)
        ? implode("\n", $configuredRecipients)
        : (string) $configuredRecipients;

    return [
        'enabled' => setting_value('mail.enabled', app_config('mail.enabled', false) ? '1' : '0') === '1',
        'from_email' => setting_value('mail.from_email', (string) app_config('mail.from_email', '')),
        'from_name' => setting_value('mail.from_name', (string) app_config('mail.from_name', app_config('app_name', 'Agenda communal'))),
        'admin_recipients' => setting_value('mail.admin_recipients', $defaultRecipients),
        'public_url' => setting_value('site.public_url', (string) app_config('site.public_url', '')),
    ];
}

function encoded_mail_header(string $value): string
{
    $value = preg_replace('/[\r\n]+/', ' ', trim($value)) ?? '';

    if (preg_match('/[^\x20-\x7E]/', $value) !== 1) {
        return $value;
    }

    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function notification_recipients(array|string $emails): array
{
    $emails = is_array($emails) ? $emails : [$emails];
    $valid = [];

    foreach ($emails as $email) {
        $email = trim((string) $email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $valid[strtolower($email)] = $email;
        }
    }

    return array_values($valid);
}

function send_notification_email(array|string $to, string $subject, string $body): bool
{
    return send_notification_email_with_attachments($to, $subject, $body);
}

function send_notification_email_with_attachments(array|string $to, string $subject, string $body, array $attachments = []): bool
{
    if (!mail_notifications_enabled()) {
        return true;
    }

    $recipients = notification_recipients($to);
    if (!$recipients) {
        return false;
    }

    $settings = mail_settings();
    $fromEmail = (string) $settings['from_email'];
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $fromName = (string) $settings['from_name'];
    $normalizedBody = normalize_mail_body($body);

    if ($attachments) {
        $boundary = 'agenda-' . bin2hex(random_bytes(16));
        $headers = [
            'From: ' . encoded_mail_header($fromName) . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
            'X-Mailer: PHP/' . PHP_VERSION,
        ];

        $message = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $normalizedBody . "\r\n";

        foreach ($attachments as $attachment) {
            $filename = preg_replace('/[^a-zA-Z0-9._-]+/', '-', (string) ($attachment['filename'] ?? 'document.pdf')) ?: 'document.pdf';
            $contentType = (string) ($attachment['content_type'] ?? 'application/octet-stream');
            $content = (string) ($attachment['content'] ?? '');
            $message .= "--{$boundary}\r\n"
                . 'Content-Type: ' . $contentType . '; name="' . $filename . "\"\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . 'Content-Disposition: attachment; filename="' . $filename . "\"\r\n\r\n"
                . chunk_split(base64_encode($content)) . "\r\n";
        }

        $message .= "--{$boundary}--\r\n";

        return mail(
            implode(', ', $recipients),
            encoded_mail_header($subject),
            $message,
            implode("\r\n", $headers)
        );
    }

    $headers = [
        'From: ' . encoded_mail_header($fromName) . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    return mail(
        implode(', ', $recipients),
        encoded_mail_header($subject),
        $normalizedBody,
        implode("\r\n", $headers)
    );
}

function normalize_mail_body(string $body): string
{
    return str_replace(["\r\n", "\r"], "\n", trim($body)) . "\n";
}

function admin_notification_emails(): array
{
    $configured = (string) mail_settings()['admin_recipients'];
    $recipients = notification_recipients(preg_split('/[\s,;]+/', $configured) ?: []);

    if ($recipients) {
        return $recipients;
    }

    return array_column(active_admin_users(), 'email');
}

function user_email_summary(array $user): string
{
    $lines = [
        'Nom : ' . $user['name'],
        'E-mail de connexion : ' . $user['email'],
        'Type de compte : ' . role_label($user['role']),
        'Statut : ' . ((int) $user['active'] === 1 ? 'Actif' : 'Inactif'),
    ];

    if (isset($user['registration_status'])) {
        $lines[] = 'Validation : ' . registration_status_label((string) $user['registration_status']);
    }

    return implode("\n", $lines);
}

function notify_account_created(int $userId, string $temporaryPassword): array
{
    $result = ['enabled' => mail_notifications_enabled(), 'account' => false, 'admins' => false];
    $user = find_user_by_id($userId);
    if (!$user) {
        return $result;
    }

    if (!$result['enabled']) {
        return $result;
    }

    $appName = branding_settings()['entity_name'];
    $loginUrl = absolute_url_for('login');

    $subject = '[' . $appName . '] Création de votre compte';
    $body = "Bonjour,\n\nUn compte vient d’être créé pour vous sur l’agenda en ligne.\n\n"
        . user_email_summary($user) . "\n"
        . 'Mot de passe provisoire : ' . $temporaryPassword . "\n"
        . 'Adresse de connexion : ' . $loginUrl . "\n\n"
        . "Cordialement,\n" . $appName;

    $result['account'] = send_notification_email($user['email'], $subject, $body);

    $adminSubject = '[' . $appName . '] Nouveau compte ' . strtolower(role_label($user['role']));
    $adminBody = "Bonjour,\n\nUn nouveau compte a été créé.\n\n"
        . user_email_summary($user) . "\n"
        . 'Adresse de connexion : ' . $loginUrl . "\n\n"
        . "Cordialement,\n" . $appName;

    $result['admins'] = send_notification_email(admin_notification_emails(), $adminSubject, $adminBody);

    return $result;
}

function notify_registration_requested(int $userId): array
{
    $result = ['enabled' => mail_notifications_enabled(), 'account' => false, 'admins' => false];
    $user = find_user_by_id($userId);
    if (!$user) {
        return $result;
    }

    if (!$result['enabled']) {
        return $result;
    }

    $appName = branding_settings()['entity_name'];
    $requestsUrl = absolute_url_for('admin_requests');

    $subject = '[' . $appName . '] Demande de création de compte';
    $body = "Bonjour,\n\nVotre demande de compte association a bien été reçue. Elle doit être validée par un administrateur avant que vous puissiez vous connecter.\n\n"
        . user_email_summary($user) . "\n\n"
        . "Cordialement,\n" . $appName;
    $result['account'] = send_notification_email($user['email'], $subject, $body);

    $adminSubject = '[' . $appName . '] Compte association à valider';
    $adminBody = "Bonjour,\n\nUne association demande la création d’un compte.\n\n"
        . user_email_summary($user) . "\n";
    if (!empty($user['registration_notes'])) {
        $adminBody .= "\nMessage :\n" . $user['registration_notes'] . "\n";
    }
    $adminBody .= "\nPage de validation : " . $requestsUrl . "\n\n"
        . "Cordialement,\n" . $appName;

    $result['admins'] = send_notification_email(admin_notification_emails(), $adminSubject, $adminBody);

    return $result;
}

function notify_registration_status_changed(int $userId, string $status): array
{
    $result = ['enabled' => mail_notifications_enabled(), 'account' => false];
    $user = find_user_by_id($userId);
    if (!$user) {
        return $result;
    }

    if (!$result['enabled']) {
        return $result;
    }

    $appName = branding_settings()['entity_name'];
    $loginUrl = absolute_url_for('login');
    $approved = $status === 'approved';
    $subject = '[' . $appName . '] Demande de compte ' . ($approved ? 'validée' : 'refusée');
    $body = "Bonjour,\n\n";
    $body .= $approved
        ? "Votre demande de compte association a été validée. Vous pouvez maintenant vous connecter avec l’e-mail et le mot de passe saisis lors de votre demande.\n\n"
        : "Votre demande de compte association a été refusée. Pour plus d’informations, contactez un administrateur.\n\n";
    $body .= user_email_summary($user) . "\n";
    if ($approved) {
        $body .= 'Adresse de connexion : ' . $loginUrl . "\n";
    }
    $body .= "\nCordialement,\n" . $appName;

    $result['account'] = send_notification_email($user['email'], $subject, $body);

    return $result;
}

function notify_password_changed(int $userId, string $newPassword, bool $changedByAdmin = false): array
{
    $result = ['enabled' => mail_notifications_enabled(), 'account' => false];
    $user = find_user_by_id($userId);
    if (!$user) {
        return $result;
    }

    if (!$result['enabled']) {
        return $result;
    }

    $appName = branding_settings()['entity_name'];
    $loginUrl = absolute_url_for('login');
    $subject = '[' . $appName . '] Mot de passe mis à jour';
    $intro = $changedByAdmin
        ? 'Votre mot de passe vient d’être réinitialisé par un administrateur.'
        : 'Votre mot de passe vient d’être modifié.';

    $body = "Bonjour,\n\n{$intro}\n\n"
        . user_email_summary($user) . "\n"
        . 'Nouveau mot de passe : ' . $newPassword . "\n"
        . 'Adresse de connexion : ' . $loginUrl . "\n\n"
        . "Cordialement,\n" . $appName;

    $result['account'] = send_notification_email($user['email'], $subject, $body);

    return $result;
}

function notify_password_reset_requested(int $userId, string $token): array
{
    $result = ['enabled' => mail_notifications_enabled(), 'account' => false];
    $user = find_user_by_id($userId);
    if (!$user) {
        return $result;
    }

    if (!$result['enabled']) {
        return $result;
    }

    $appName = branding_settings()['entity_name'];
    $resetUrl = absolute_url_for('password_reset', ['token' => $token]);
    $validMinutes = max(10, (int) app_config('security.password_reset_minutes', 60));
    $subject = '[' . $appName . '] Réinitialisation de votre mot de passe';
    $body = "Bonjour,\n\nUne demande de réinitialisation de mot de passe a été faite pour votre compte.\n\n"
        . user_email_summary($user) . "\n"
        . 'Lien de réinitialisation : ' . $resetUrl . "\n"
        . 'Ce lien est valable ' . $validMinutes . " minutes et ne peut être utilisé qu’une seule fois.\n\n"
        . "Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet e-mail.\n\n"
        . "Cordialement,\n" . $appName;

    $result['account'] = send_notification_email($user['email'], $subject, $body);

    return $result;
}

function notify_password_reset_completed(int $userId): array
{
    $result = ['enabled' => mail_notifications_enabled(), 'account' => false];
    $user = find_user_by_id($userId);
    if (!$user) {
        return $result;
    }

    if (!$result['enabled']) {
        return $result;
    }

    $appName = branding_settings()['entity_name'];
    $loginUrl = absolute_url_for('login');
    $subject = '[' . $appName . '] Mot de passe réinitialisé';
    $body = "Bonjour,\n\nVotre mot de passe vient d’être réinitialisé depuis le lien sécurisé envoyé par e-mail.\n\n"
        . user_email_summary($user) . "\n"
        . 'Adresse de connexion : ' . $loginUrl . "\n\n"
        . "Si vous n’êtes pas à l’origine de cette action, contactez rapidement un administrateur.\n\n"
        . "Cordialement,\n" . $appName;

    $result['account'] = send_notification_email($user['email'], $subject, $body);

    return $result;
}

function reservation_email_summary(array $reservation): string
{
    $lines = [
        'Salle : ' . $reservation['room_name'],
        'Date : ' . human_datetime($reservation['starts_at']),
        'Horaire : ' . time_range($reservation['starts_at'], $reservation['ends_at']),
        'Raison : ' . $reservation['title'],
        'Statut : ' . status_label($reservation['status']),
        'Demandeur : ' . $reservation['user_name'] . ' <' . $reservation['user_email'] . '>',
    ];

    if (!empty($reservation['notes'])) {
        $lines[] = '';
        $lines[] = 'Notes :';
        $lines[] = $reservation['notes'];
    }

    return implode("\n", $lines);
}

function notify_reservation_created(int $reservationId): void
{
    $reservation = reservation_with_context_by_id($reservationId);
    if (!$reservation) {
        return;
    }

    $appName = branding_settings()['entity_name'];
    $subject = '[' . $appName . '] Réservation ' . strtolower(status_label($reservation['status']));
    $body = "Bonjour,\n\nVotre réservation a bien été enregistrée.\n\n"
        . reservation_email_summary($reservation)
        . "\n\nCordialement,\n" . $appName;

    send_notification_email($reservation['user_email'], $subject, $body);

    $adminSubject = '[' . $appName . '] Nouvelle réservation - ' . $reservation['room_name'];
    $adminBody = "Bonjour,\n\nUne nouvelle réservation a été créée.\n\n"
        . reservation_email_summary($reservation)
        . "\n\nCordialement,\n" . $appName;

    send_notification_email(admin_notification_emails(), $adminSubject, $adminBody);
}

function notify_reservation_status_changed(int $reservationId, string $previousStatus): void
{
    $reservation = reservation_with_context_by_id($reservationId);
    if (!$reservation || $previousStatus === $reservation['status']) {
        return;
    }

    $appName = branding_settings()['entity_name'];
    $subject = '[' . $appName . '] Réservation ' . strtolower(status_label($reservation['status']));
    $body = "Bonjour,\n\nLe statut de votre réservation a été mis à jour.\n\n"
        . 'Ancien statut : ' . status_label($previousStatus) . "\n"
        . reservation_email_summary($reservation)
        . "\n\nCordialement,\n" . $appName;

    send_notification_email($reservation['user_email'], $subject, $body);
}

function material_request_email_summary(array $request, array $items): string
{
    $lines = [
        'Événement : ' . $request['event_title'],
        'Date : ' . human_datetime($request['starts_at']),
        'Horaire : ' . time_range($request['starts_at'], $request['ends_at']),
        'Lieu : ' . ($request['event_location'] ?: 'Non précisé'),
        'Statut : ' . status_label($request['status']),
        'Demandeur : ' . $request['user_name'] . ' <' . $request['user_email'] . '>',
        '',
        'Matériel demandé :',
    ];

    foreach ($items as $item) {
        $lines[] = '- ' . $item['name'] . ' (' . material_category_label($item['category']) . ') : ' . (int) $item['quantity'];
    }

    if (!empty($request['notes'])) {
        $lines[] = '';
        $lines[] = 'Notes du demandeur :';
        $lines[] = $request['notes'];
    }

    if (!empty($request['admin_notes'])) {
        $lines[] = '';
        $lines[] = 'Notes administrateur :';
        $lines[] = $request['admin_notes'];
    }

    return implode("\n", $lines);
}

function notify_material_request_created(int $requestId): void
{
    $request = material_request_with_context_by_id($requestId);
    if (!$request) {
        return;
    }

    $items = material_request_items($requestId);
    $appName = branding_settings()['entity_name'];
    $subject = '[' . $appName . '] Nouvelle demande de matériel';
    $body = "Bonjour,\n\nUne nouvelle demande de matériel est en attente de validation.\n\n"
        . material_request_email_summary($request, $items)
        . "\n\nCordialement,\n" . $appName;

    send_notification_email(admin_notification_emails(), $subject, $body);
}

function notify_material_request_status_changed(int $requestId, string $previousStatus): array
{
    $result = ['enabled' => mail_notifications_enabled(), 'requester' => false, 'admins' => false];
    $request = material_request_with_context_by_id($requestId);
    if (!$request || $previousStatus === $request['status']) {
        return $result;
    }

    $items = material_request_items($requestId);
    $appName = branding_settings()['entity_name'];
    $summary = material_request_email_summary($request, $items);
    $attachments = [];

    if ($request['status'] === 'approved') {
        $attachments[] = [
            'filename' => material_request_pdf_filename($request),
            'content_type' => 'application/pdf',
            'content' => material_request_pdf($request, $items),
        ];
    }

    $subject = '[' . $appName . '] Demande de matériel ' . strtolower(status_label($request['status']));
    $body = "Bonjour,\n\nLe statut de votre demande de matériel a été mis à jour.\n\n"
        . 'Ancien statut : ' . status_label($previousStatus) . "\n"
        . $summary
        . "\n\nCordialement,\n" . $appName;

    $result['requester'] = send_notification_email_with_attachments($request['user_email'], $subject, $body, $attachments);

    if ($request['status'] === 'approved') {
        $adminSubject = '[' . $appName . '] Demande de matériel validée';
        $adminBody = "Bonjour,\n\nUne demande de matériel vient d’être validée.\n\n"
            . $summary
            . "\n\nCordialement,\n" . $appName;
        $result['admins'] = send_notification_email_with_attachments(admin_notification_emails(), $adminSubject, $adminBody, $attachments);
    } else {
        $result['admins'] = true;
    }

    return $result;
}

function material_request_pdf_filename(array $request): string
{
    $date = 'sans-date';
    if (!empty($request['starts_at'])) {
        try {
            $date = (new DateTimeImmutable((string) $request['starts_at']))->format('Ymd');
        } catch (Exception) {
            $date = 'sans-date';
        }
    }

    return 'demande-materiel-' . $date . '-' . (int) ($request['id'] ?? 0) . '.pdf';
}

function material_request_pdf(array $request, array $items): string
{
    $appName = branding_settings()['entity_name'];
    $lines = [
        $appName,
        'Demande de matériel validée',
        '',
        'Référence : demande #' . (int) $request['id'],
        'Événement : ' . $request['event_title'],
        'Demandeur : ' . $request['user_name'],
        'E-mail : ' . $request['user_email'],
        'Date : ' . human_datetime($request['starts_at']),
        'Horaire : ' . time_range($request['starts_at'], $request['ends_at']),
        'Lieu : ' . ($request['event_location'] ?: 'Non précisé'),
        'Validation : ' . (!empty($request['validated_at']) ? human_datetime($request['validated_at']) : date('d/m/Y H:i')),
        '',
        'Matériel emprunté',
    ];

    if ($items) {
        $lines[] = [
            'type' => 'table',
            'headers' => ['Désignation', 'Quantité'],
            'rows' => array_map(
                static fn (array $item): array => [
                    (string) $item['name'],
                    (string) (int) $item['quantity'],
                ],
                $items
            ),
        ];
    } else {
        $lines[] = 'Aucun matériel renseigné.';
    }

    if (!empty($request['notes'])) {
        $lines[] = '';
        $lines[] = 'Notes du demandeur';
        foreach (explode("\n", (string) $request['notes']) as $line) {
            $lines[] = $line;
        }
    }

    if (!empty($request['admin_notes'])) {
        $lines[] = '';
        $lines[] = 'Notes administrateur';
        foreach (explode("\n", (string) $request['admin_notes']) as $line) {
            $lines[] = $line;
        }
    }

    $lines[] = '';
    $lines[] = 'Document à imprimer et à joindre au dossier de sortie du matériel.';

    return simple_pdf($lines);
}

function reservations_export_pdf(array $reservations, DateTimeImmutable $start, DateTimeImmutable $endInclusive, array $rooms, array $statuses): string
{
    $appName = branding_settings()['entity_name'];
    $roomNames = $rooms ? array_column($rooms, 'name') : ['Toutes les salles'];
    $statusLabels = array_map(static fn (string $status): string => status_label($status), $statuses);
    $lines = [
        $appName,
        'Export des réservations',
        '',
        'Période : ' . $start->format('d/m/Y') . ' au ' . $endInclusive->format('d/m/Y'),
        'Salles : ' . implode(', ', $roomNames),
        'Statuts : ' . implode(', ', $statusLabels),
        'Nombre de réservations : ' . count($reservations),
        '',
    ];

    if (!$reservations) {
        $lines[] = 'Aucune réservation ne correspond aux critères sélectionnés.';
        return simple_pdf($lines);
    }

    foreach ($reservations as $reservation) {
        $lines[] = human_date($reservation['starts_at']) . ' / ' . time_range($reservation['starts_at'], $reservation['ends_at']);
        $lines[] = 'Salle : ' . $reservation['room_name'];
        $lines[] = 'Association : ' . $reservation['user_name'] . ' <' . $reservation['user_email'] . '>';
        $lines[] = 'Raison : ' . $reservation['title'];
        $lines[] = 'Statut : ' . status_label($reservation['status']);

        if (!empty($reservation['notes'])) {
            $lines[] = 'Notes : ' . $reservation['notes'];
        }

        $lines[] = '';
    }

    return simple_pdf($lines);
}

function simple_pdf(array $lines): string
{
    $pageWidth = 595;
    $pageHeight = 842;
    $marginX = 48;
    $marginY = 50;
    $bottomMargin = 48;
    $lineHeight = 15.0;
    $logoImage = pdf_branding_logo_image();
    $pages = [[]];
    $cursorY = 0.0;

    $append = static function (string $operation) use (&$pages): void {
        $pages[array_key_last($pages)][] = $operation;
    };

    $startPage = static function (bool $create = false) use (&$pages, &$cursorY, $pageWidth, $pageHeight, $marginX, $marginY, $logoImage, $append): void {
        if ($create) {
            $pages[] = [];
        }

        $cursorY = $pageHeight - $marginY;
        if (!$logoImage) {
            return;
        }

        $maxWidth = 110.0;
        $maxHeight = 46.0;
        $scale = min($maxWidth / $logoImage['width'], $maxHeight / $logoImage['height'], 1.0);
        $drawWidth = $logoImage['width'] * $scale;
        $drawHeight = $logoImage['height'] * $scale;
        $x = $marginX;
        $y = $pageHeight - 34 - $drawHeight;
        $lineY = $y - 14;

        $append(
            "q\n"
            . pdf_num($drawWidth) . ' 0 0 ' . pdf_num($drawHeight) . ' ' . pdf_num($x) . ' ' . pdf_num($y)
            . " cm\n/Logo Do\nQ\n"
        );
        $append("0.78 0.83 0.86 RG\n" . pdf_num($marginX) . ' ' . pdf_num($lineY) . ' m ' . pdf_num($pageWidth - $marginX) . ' ' . pdf_num($lineY) . " l S\n0 G\n");
        $cursorY = $lineY - 24;
    };

    $newPage = static function () use ($startPage): void {
        $startPage(true);
    };

    $ensureSpace = static function (float $height) use (&$cursorY, $bottomMargin, $newPage): void {
        if ($cursorY - $height < $bottomMargin) {
            $newPage();
        }
    };

    $drawText = static function (string $text, float $x, float $y, string $font = 'F1', int $size = 11) use ($append): void {
        $append(
            "BT\n/{$font} {$size} Tf\n1 0 0 1 "
            . pdf_num($x) . ' ' . pdf_num($y)
            . " Tm\n(" . pdf_escape_text($text) . ") Tj\nET\n"
        );
    };

    $drawTextLine = static function (string $line) use (&$cursorY, $marginX, $lineHeight, $ensureSpace, $drawText): void {
        $ensureSpace($lineHeight);
        $drawText($line, $marginX, $cursorY);
        $cursorY -= $lineHeight;
    };

    $drawTableHeader = static function () use (&$cursorY, $marginX, $append, $drawText): void {
        $tableWidth = 499.0;
        $designationWidth = 392.0;
        $quantityWidth = $tableWidth - $designationWidth;
        $height = 24.0;
        $y = $cursorY - $height;

        $append("0.94 0.96 0.97 rg\n" . pdf_num($marginX) . ' ' . pdf_num($y) . ' ' . pdf_num($tableWidth) . ' ' . pdf_num($height) . " re f\n0 g\n");
        $append("0.78 0.83 0.86 RG\n" . pdf_num($marginX) . ' ' . pdf_num($y) . ' ' . pdf_num($tableWidth) . ' ' . pdf_num($height) . " re S\n");
        $append(pdf_num($marginX + $designationWidth) . ' ' . pdf_num($y) . ' m ' . pdf_num($marginX + $designationWidth) . ' ' . pdf_num($y + $height) . " l S\n0 G\n");
        $drawText('Désignation', $marginX + 8, $y + 8, 'F2', 11);
        $drawText('Quantité', $marginX + $designationWidth + 8, $y + 8, 'F2', 11);
        $cursorY -= $height;
    };

    $drawTable = static function (array $rows) use (&$cursorY, $marginX, $bottomMargin, $append, $newPage, $drawText, $drawTableHeader): void {
        $tableWidth = 499.0;
        $designationWidth = 392.0;
        $quantityWidth = $tableWidth - $designationWidth;
        $lineHeight = 14.0;

        if ($cursorY - 52 < $bottomMargin) {
            $newPage();
        }
        $drawTableHeader();

        foreach ($rows as $row) {
            $designation = (string) ($row[0] ?? '');
            $quantity = (string) ($row[1] ?? '');
            $designationLines = pdf_wrap_line($designation, 58);
            $rowHeight = max(24.0, (count($designationLines) * $lineHeight) + 10.0);

            if ($cursorY - $rowHeight < $bottomMargin) {
                $newPage();
                $drawTableHeader();
            }

            $y = $cursorY - $rowHeight;
            $append("0.78 0.83 0.86 RG\n" . pdf_num($marginX) . ' ' . pdf_num($y) . ' ' . pdf_num($tableWidth) . ' ' . pdf_num($rowHeight) . " re S\n");
            $append(pdf_num($marginX + $designationWidth) . ' ' . pdf_num($y) . ' m ' . pdf_num($marginX + $designationWidth) . ' ' . pdf_num($y + $rowHeight) . " l S\n0 G\n");

            $textY = $cursorY - 16;
            foreach ($designationLines as $line) {
                $drawText($line, $marginX + 8, $textY);
                $textY -= $lineHeight;
            }
            $drawText($quantity, $marginX + $designationWidth + 8, $cursorY - 16);
            $cursorY -= $rowHeight;
        }

        $cursorY -= 8;
    };

    $startPage();

    foreach ($lines as $block) {
        if (is_array($block) && ($block['type'] ?? '') === 'table') {
            $drawTable((array) ($block['rows'] ?? []));
            continue;
        }

        foreach (pdf_wrap_line((string) $block, 92) as $wrappedLine) {
            $drawTextLine($wrappedLine);
        }
    }

    $objects = [
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
    ];
    $pageStartId = 5;
    $logoObjectId = null;
    if ($logoImage) {
        $logoObjectId = 5;
        $objects[$logoObjectId] = "<< /Type /XObject /Subtype /Image /Width " . (int) $logoImage['width']
            . " /Height " . (int) $logoImage['height']
            . " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($logoImage['data'])
            . " >>\nstream\n" . $logoImage['data'] . "\nendstream";
        $pageStartId = 6;
    }
    $pageIds = [];

    foreach ($pages as $index => $pageOperations) {
        $pageId = $pageStartId + ($index * 2);
        $contentId = $pageId + 1;
        $pageIds[] = $pageId . ' 0 R';
        $content = implode('', $pageOperations);
        $xObjectResource = $logoObjectId !== null ? ' /XObject << /Logo ' . $logoObjectId . ' 0 R >>' : '';

        $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>{$xObjectResource} >> /Contents {$contentId} 0 R >>";
        $objects[$contentId] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream";
    }

    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageIds) . '] /Count ' . count($pageIds) . ' >>';
    ksort($objects);

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0 => 0];

    foreach ($objects as $id => $object) {
        $offsets[$id] = strlen($pdf);
        $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    foreach (array_keys($objects) as $id) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

    return $pdf;
}

function pdf_num(float|int $value): string
{
    return rtrim(rtrim(sprintf('%.2F', $value), '0'), '.');
}

function pdf_branding_logo_image(): ?array
{
    $logoPath = branding_settings()['logo_path'] ?? '';
    if ($logoPath === '' || !str_starts_with($logoPath, 'uploads/branding/')) {
        return null;
    }

    $base = realpath(__DIR__ . '/../public/uploads/branding');
    $path = realpath(__DIR__ . '/../public/' . $logoPath);
    if (!$base || !$path || !str_starts_with($path, $base . DIRECTORY_SEPARATOR)) {
        return null;
    }

    return pdf_image_from_file($path);
}

function pdf_image_from_file(string $path): ?array
{
    if (!function_exists('getimagesize') || !function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
        return null;
    }

    $info = @getimagesize($path);
    if (!$info || empty($info['mime']) || empty($info[0]) || empty($info[1])) {
        return null;
    }

    $loader = match ((string) $info['mime']) {
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png' => 'imagecreatefrompng',
        'image/gif' => 'imagecreatefromgif',
        'image/webp' => 'imagecreatefromwebp',
        default => null,
    };
    if (!$loader || !function_exists($loader)) {
        return null;
    }

    $source = @$loader($path);
    if (!$source) {
        return null;
    }

    $width = imagesx($source);
    $height = imagesy($source);
    $canvas = imagecreatetruecolor($width, $height);
    if (!$canvas) {
        return null;
    }

    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
    imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

    ob_start();
    $ok = imagejpeg($canvas, null, 90);
    $data = $ok ? (string) ob_get_clean() : '';
    if (!$ok && ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!$ok || $data === '') {
        return null;
    }

    return [
        'width' => $width,
        'height' => $height,
        'data' => $data,
    ];
}

function pdf_wrap_line(string $line, int $maxLength): array
{
    if ($line === '') {
        return [''];
    }

    $words = preg_split('/\s+/', $line) ?: [];
    $lines = [];
    $current = '';

    foreach ($words as $word) {
        if ($current === '') {
            $current = $word;
            continue;
        }

        if (strlen($current . ' ' . $word) <= $maxLength) {
            $current .= ' ' . $word;
            continue;
        }

        $lines[] = $current;
        $current = $word;
    }

    if ($current !== '') {
        $lines[] = $current;
    }

    return $lines ?: [''];
}

function pdf_escape_text(string $text): string
{
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    } else {
        $text = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }

    $text = preg_replace('/[^\x09\x0A\x0D\x20-\xFF]/', '?', $text) ?? $text;

    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}
