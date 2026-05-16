<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Ce script doit être exécuté en ligne de commande.\n";
    exit(1);
}

require __DIR__ . '/../app/bootstrap.php';

$result = run_automatic_backup(false);
echo $result['message'] . PHP_EOL;

exit($result['status'] === 'failed' ? 1 : 0);
