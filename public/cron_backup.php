<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');

$token = trim((string) ($_GET['token'] ?? ''));
if (!automatic_backup_token_is_valid($token)) {
    http_response_code(403);
    echo "Accès refusé.\n";
    exit;
}

$result = run_automatic_backup(false);
if ($result['status'] === 'failed') {
    http_response_code(500);
}

echo $result['message'] . "\n";
