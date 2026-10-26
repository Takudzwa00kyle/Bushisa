<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../../php/staging_mail.php';
try {
    $m=\Bushisa\Staging\context('serve');$in=\Bushisa\Staging\input();
    \Bushisa\Staging\demand(preg_match('/\A[a-f0-9]{16}\z/D',(string)($in['barrier']??''))===1,'BARRIER');
    $pdo=\Bushisa\Staging\connect($m);
    file_put_contents($m['storage']['root'].'/ready-'.$in['barrier'],json_encode(['pid'=>getmypid(),'connection_id'=>(int)$pdo->query('SELECT CONNECTION_ID()')->fetchColumn()],JSON_THROW_ON_ERROR));
    $ok=bushisa_reset_consume($pdo,(string)($in['token']??''),(string)($in['password']??''));
    fwrite(STDOUT,json_encode(['success'=>$ok],JSON_THROW_ON_ERROR));
} catch(Throwable) { fwrite(STDERR,"Guarded stage consume worker failed.\n");exit(1); }
