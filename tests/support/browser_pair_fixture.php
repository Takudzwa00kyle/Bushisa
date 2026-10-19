<?php
declare(strict_types=1);
// Synthetic initial relationship setup only; never certify an existing pair.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/disposable_database.php';
$settings=bushisa_require_disposable_test_database('browser pair fixture');
if(getenv('MAIL_PROVIDER')!=='fake' || !in_array($settings['host'],['127.0.0.1','localhost','::1'],true))throw new RuntimeException('Local synthetic fixture required');
$left=filter_var($argv[1]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$right=filter_var($argv[2]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$left||!$right||$left===$right)throw new RuntimeException('Invalid fixture participants');
require __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../php/pair_history.php';
$match=bushisa_report_transaction($pdo,function()use($pdo,$left,$right):int{
    bushisa_pair_check_contact($pdo,$left,$right);
    $pair=bushisa_pair_ensure($pdo,$left,$right);
    bushisa_pair_complete($pdo,$pair);
    if($pair['match_id']!==null || (int)$pair['generation']!==0)throw new RuntimeException('Existing relationship cannot be fabricated');
    return bushisa_pair_match($pdo,$pair);
});
fwrite(STDOUT,json_encode(['match_id'=>$match],JSON_THROW_ON_ERROR).PHP_EOL);
