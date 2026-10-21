<?php
declare(strict_types=1);
// No production activation tool is supplied by this bounded implementation.
// This fixture attests only the guarded synthetic writer cohort exercised here.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/disposable_database.php';
$settings=bushisa_require_disposable_test_database('synthetic pair writer activation');
if (!in_array($settings['host'],['127.0.0.1','localhost','::1'],true)) { throw new RuntimeException('Local fixtures only'); }
$ref=getopt('',['writer-ref:'])['writer-ref']??'';
if (!is_string($ref)||!preg_match('/\Asynthetic:[A-Za-z0-9_.-]{1,100}\z/',$ref)) { throw new RuntimeException('Explicit synthetic writer reference required'); }
require __DIR__.'/../../config/db.php';
$pdo->beginTransaction();
$pdo->prepare('UPDATE pair_ledger_epoch SET activated_at=UTC_TIMESTAMP(6),writer_reference=? WHERE singleton=1 AND activated_at IS NULL')->execute([$ref]);
$epoch=$pdo->query('SELECT * FROM pair_ledger_epoch WHERE singleton=1')->fetch(PDO::FETCH_ASSOC);
if (!is_array($epoch)||$epoch['writer_reference']!==$ref) { $pdo->rollBack(); throw new RuntimeException('A writer epoch cannot be replaced'); }
$pdo->commit();
fwrite(STDOUT,json_encode(['synthetic_epoch'=>$epoch['activated_at'],'writer_reference'=>$ref]).PHP_EOL);
