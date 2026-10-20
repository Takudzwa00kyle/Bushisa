<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../php/staging_mail.php';
try {
    $context=\Bushisa\Staging\context('dispatch');
    $input=\Bushisa\Staging\input();
    $pipe=\Bushisa\Staging\private_pipe($input); // Refuse a missing private runner before connecting.
    \Bushisa\Staging\demand(is_array($input['jobs']??null),'BATCH');
    $pdo=\Bushisa\Staging\connect($context);
    $private=[];
    $counts=\Bushisa\Staging\dispatch($pdo,$input['jobs'],static function(array $proof)use(&$private):void { $private[]=$proof; });
    $frame=json_encode(['deliveries'=>$private],JSON_THROW_ON_ERROR);
    if (fwrite($pipe,$frame)!==strlen($frame)) \Bushisa\Staging\deny('PIPE_RESULT_LOST');
    fclose($pipe); unset($private,$frame);
    fwrite(STDOUT,json_encode(['status'=>'SYNTHETIC_ONLY','counts'=>$counts],JSON_THROW_ON_ERROR).PHP_EOL);
    exit($counts['failed']>0?1:0);
} catch (Throwable) {
    fwrite(STDERR,"Synthetic stage dispatch refused or failed; verify guards, identities, private pipe and job state.\n"); exit(1);
}
