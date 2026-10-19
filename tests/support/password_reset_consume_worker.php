<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/disposable_database.php';
$settings = bushisa_require_disposable_test_database('L06 reset worker');
putenv('BUSHISA_PASSWORD_RESET_SYNTHETIC=true');
$pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $settings['host'], $settings['port'], $settings['name']), $settings['user'], $settings['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
$pdo->exec("SET time_zone='+00:00'");
require_once __DIR__ . '/../../php/password_reset.php';
$input = json_decode((string) stream_get_contents(STDIN), true, 8, JSON_THROW_ON_ERROR);
if (isset($input['ready_path'],$input['gate_path'])) {
    $root=realpath(__DIR__.'/../..'); $temp=realpath($root.'/.tmp');
    $ready=(string)$input['ready_path']; $gate=(string)$input['gate_path'];
    $normalize=static fn(string $path):string=>strtolower(str_replace(['/', '\\'],DIRECTORY_SEPARATOR,$path));
    if (!$root || !$temp || !str_starts_with($normalize($ready),$normalize($temp).DIRECTORY_SEPARATOR)
        || !str_starts_with($normalize($gate),$normalize($temp).DIRECTORY_SEPARATOR)) throw new RuntimeException('Unsafe test barrier path');
    file_put_contents($ready,json_encode(['pid'=>getmypid(),'connection_id'=>(int)$pdo->query('SELECT CONNECTION_ID()')->fetchColumn()],JSON_THROW_ON_ERROR),LOCK_EX);
    $until=microtime(true)+20; while(!is_file($gate)&&microtime(true)<$until)usleep(10000);
    if(!is_file($gate))throw new RuntimeException('Test gate timed out');
}
$ok = bushisa_reset_consume($pdo, (string)($input['token'] ?? ''), (string)($input['password'] ?? ''));
fwrite(STDOUT, json_encode(['success'=>$ok], JSON_THROW_ON_ERROR) . PHP_EOL);
