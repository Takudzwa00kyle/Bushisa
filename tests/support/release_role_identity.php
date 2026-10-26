<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/owner_schema_fixture.php';
$settings=bushisa_require_disposable_test_database('Release role identity');
if (!str_starts_with($settings['name'],'bushisa_test_release_observed_roles_')
    || !in_array($settings['host'],['127.0.0.1','localhost','::1'],true)
    || getenv('BUSHISA_RELEASE_ROLE_TEST')!=='true') throw new RuntimeException('ROLE_IDENTITY_TARGET_REFUSED');
// Read-only metadata only, before fixture setup. Trusted CI service or local
// Docker label/network inspection establishes ownership outside this helper.
$pdo=obs_connect($settings,null,false);
$uuid=(string)$pdo->query('SELECT @@server_uuid')->fetchColumn();
if (!preg_match('/\A[0-9a-f-]{36}\z/D',$uuid)) throw new RuntimeException('ROLE_IDENTITY_UNAVAILABLE');
echo $uuid.PHP_EOL;
