<?php

declare(strict_types=1);

function bushisa_http_test_barrier(string $point): void
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
    $written = file_put_contents($participant, json_encode([
        'pid' => getmypid(),
        'point' => $point,
        'entered_at' => gmdate('c'),
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
    ], JSON_UNESCAPED_SLASHES));
    if ($written === false) {
        throw new RuntimeException('Unable to write HTTP test barrier participant.');
    }

    do {
        $participants = glob($directory . DIRECTORY_SEPARATOR . $safeKey . '-*.json') ?: [];
        if (count($participants) >= $expected) {
            return;
        }
        usleep(25000);
    } while (((microtime(true) - $startedAt) * 1000) < $timeoutMs);

    throw new RuntimeException('HTTP test barrier timed out before all participants arrived.');
}
