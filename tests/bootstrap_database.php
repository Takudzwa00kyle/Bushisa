<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/support/disposable_database.php';

bushisa_bootstrap_disposable_database(__DIR__ . '/../database/bushisa.sql');
fwrite(STDOUT, "Disposable database bootstrapped\n");
