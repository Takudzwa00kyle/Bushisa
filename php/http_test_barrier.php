<?php

declare(strict_types=1);

function bushisa_http_test_barrier(string $point, ?PDO $connection = null): void
{
    $barrierId = isset($_SERVER['HTTP_X_BUSHISA_TEST_BARRIER']) && is_string($_SERVER['HTTP_X_BUSHISA_TEST_BARRIER'])
        ? trim($_SERVER['HTTP_X_BUSHISA_TEST_BARRIER'])
        : '';
    if ($barrierId === '') {
        return;
    }

    if ((string) getenv('APP_ENV') !== 'development'
        || (string) getenv('BUSHISA_TEST_DATABASE') !== 'true'
        || (string) getenv('BUSHISA_TEST_HTTP_OVERLAP') !== 'true'
    ) {
        throw new RuntimeException('HTTP test barrier is not enabled for this environment.');
    }
    require_once __DIR__ . '/../tests/support/disposable_database.php';
    $settings = bushisa_require_disposable_test_database('HTTP test barrier');
    // Keep the shared guard's explicit remote-test opt-in for existing
    // Docker-to-host suites. Importer controls separately require loopback.
    $connection ??= ($GLOBALS['pdo'] ?? null);
    if (!$connection instanceof PDO || $connection->query('SELECT DATABASE()')->fetchColumn() !== $settings['name']) {
        throw new RuntimeException('HTTP test barrier connection does not match its guarded target.');
    }
    // Guarded synthetic suites can rendezvous different route kinds at the
    // same pre-transaction point. This is unreachable without all test flags.
    $override=$_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_POINT']??null;
    if (is_string($override) && preg_match('/\A[a-z0-9_]{1,80}\z/',$override)) { $point=$override; }

    $expected = isset($_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_EXPECTED'])
        ? max(2, min(8, (int) $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_EXPECTED']))
        : 2;
    $timeoutMs = isset($_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_TIMEOUT_MS'])
        ? max(500, min(15000, (int) $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_TIMEOUT_MS']))
        : 5000;

    $safeKey = preg_replace('/[^A-Za-z0-9_.-]/', '_', $barrierId . '_' . $point);
    if (!is_string($safeKey) || $safeKey === '') {
        throw new RuntimeException('HTTP test barrier key is invalid.');
    }

    $directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'bushisa-http-barriers';
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create HTTP test barrier directory.');
    }

    $startedAt = microtime(true);
    foreach (glob($directory . DIRECTORY_SEPARATOR . $safeKey . '-*.json') ?: [] as $candidate) {
        if (@filemtime($candidate) !== false && (time() - (int) @filemtime($candidate)) > 60) {
            @unlink($candidate);
        }
    }

    $participant = sprintf(
        '%s%s%s-%s.json',
        $directory,
        DIRECTORY_SEPARATOR,
        $safeKey,
        bin2hex(random_bytes(8))
    );
    bushisa_write_test_record($participant, [
        'pid' => getmypid(),
        'connection_id' => (int) $connection->query('SELECT CONNECTION_ID()')->fetchColumn(),
        'sapi' => PHP_SAPI,
        'point' => $point,
        'entered_at' => gmdate('c'),
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
    ]);

    do {
        $participants = glob($directory . DIRECTORY_SEPARATOR . $safeKey . '-*.json') ?: [];
        if (count($participants) >= $expected) {
            if (($_SERVER['HTTP_X_BUSHISA_TEST_GATE'] ?? '') !== 'true') { return; }
            if (!preg_match('/\Al02e_[a-f0-9]{32}\z/D', $barrierId) || $point !== 'l02e_mixed') {
                throw new RuntimeException('Invalid guarded mixed-test gate.');
            }
            $release = $directory . DIRECTORY_SEPARATOR . $safeKey . '-' . getmypid() . '.release';
            do {
                if (is_file($release)) { return; }
                usleep(10000);
            } while (((microtime(true) - $startedAt) * 1000) < $timeoutMs);
            throw new RuntimeException('Guarded mixed-test gate timed out.');
        }
        usleep(25000);
    } while (((microtime(true) - $startedAt) * 1000) < $timeoutMs);

    throw new RuntimeException('HTTP test barrier timed out before all participants arrived.');
}

/** Publish complete records: readers must never observe a newly opened empty file. */
function bushisa_write_test_record(string $path, array $record): void
{
    $temporary=$path.'.'.bin2hex(random_bytes(8)).'.part';
    $bytes=json_encode($record,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    if(file_put_contents($temporary,$bytes)===false || !rename($temporary,$path)) {
        throw new RuntimeException('Unable to atomically publish test record.');
    }
}
