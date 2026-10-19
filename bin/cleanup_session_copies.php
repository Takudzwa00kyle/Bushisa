<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404);exit; }
require_once __DIR__.'/../php/session_copy_cleanup.php';
try {
    \Bushisa\SessionCopies\guard(); // Includes store/destination checks BEFORE connection.
    $options=getopt('',['limit:','steps:','event:','status:','recovery-after:']);
    $limit=$options['limit']??'10';$steps=$options['steps']??'25';
    if(!is_string($limit)||!ctype_digit($limit)||(int)$limit<1||(int)$limit>25||!is_string($steps)||!ctype_digit($steps)||(int)$steps<1||(int)$steps>25)throw new \Bushisa\SessionCopies\Failure('SESSION_BATCH_LIMIT_REFUSED');
    foreach(['event','status'] as $name) { if(isset($options[$name])&&(!is_string($options[$name])||!\Bushisa\SessionCopies\key_ok($options[$name])))throw new \Bushisa\SessionCopies\Failure('SESSION_EVENT_REFUSED'); }
    if(isset($options['recovery-after'])&&(!is_string($options['recovery-after'])||($options['recovery-after']!==''&&!\Bushisa\SessionCopies\key_ok($options['recovery-after']))))throw new \Bushisa\SessionCopies\Failure('SESSION_RECOVERY_BOUND_REFUSED');
    $p=\Bushisa\SessionCopies\connection();
    $result=isset($options['status'])?['status'=>\Bushisa\SessionCopies\status($p,$options['status'])]
        :(isset($options['recovery-after'])?['recovery'=>\Bushisa\SessionCopies\recovery_instructions($p,$options['recovery-after'],(int)$limit)]
        :(isset($options['event'])?['result'=>\Bushisa\SessionCopies\attempt($p,$options['event'],(int)$steps)]:['counts'=>\Bushisa\SessionCopies\run($p,(int)$limit,(int)$steps)]));
    if(($result['result']??null)==='busy') { $result['diagnostic']='SESSION_CLEANUP_BUSY';$result['action']='retry after the active local worker exits; ownership was not transferred'; }
    echo json_encode($result,JSON_THROW_ON_ERROR).PHP_EOL;
} catch(Throwable $e) { fwrite(STDERR,json_encode(['error'=>\Bushisa\SessionCopies\reason($e)]).PHP_EOL);exit(1); }
