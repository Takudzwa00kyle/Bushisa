<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../php/synthetic_legacy_import.php';

use Bushisa\LegacyHistory\InvalidInput;
use function Bushisa\LegacyHistory\{encode, read_artifact};
use function Bushisa\SyntheticImport\{execute, guards, status};

try {
    $mode=$argv[1]??'';
    if (!in_array($mode,['import','status'],true)) { throw new InvalidInput('SYNTHETIC_IMPORT_OR_STATUS_ONLY'); }
    $options=[];
    foreach(array_slice($argv,2) as $arg) {
        if(!preg_match('/\A--(permit|bundle|snapshot|catalog|plan|key|pair|fault|barrier)=(.+)\z/D',$arg,$m)||isset($options[$m[1]])) { throw new InvalidInput('CLI_OPTION'); }
        $options[$m[1]]=$m[2];
    }
    $required=$mode==='import'?['permit','bundle','snapshot','catalog','plan','key']:['permit','pair','key'];
    $allowed=$mode==='import'?array_merge($required,['fault','barrier']):$required;
    if(array_diff($required,array_keys($options))!==[]||array_diff(array_keys($options),$allowed)!==[]) { throw new InvalidInput('CLI_REQUIRED_OPTIONS'); }
    $permit=read_artifact($options['permit'])['value']; guards($permit); // Before reading any connecting application configuration (none is loaded).
    if($mode==='status') { $result=status($permit,$options['pair'],$options['key']); }
    else {
        $test=array_intersect_key($options,array_flip(['fault','barrier']));
        if($test!==[] && (!$permit->test_controls || getenv('BUSHISA_SYNTHETIC_IMPORT_TEST_CONTROLS')!=='true')) { throw new InvalidInput('TEST_CONTROLS_DISABLED'); }
        if(isset($test['fault'])&&!in_array($test['fault'],['after_facts','after_counts','after_receipt','after_key','after_commit_exit','retry_once_after_facts'],true)) { throw new InvalidInput('TEST_FAULT_NAME'); }
        if(isset($test['barrier'])&&!preg_match('/\Al02d-[a-f0-9]{32}\z/',$test['barrier'])) { throw new InvalidInput('TEST_BARRIER_ID'); }
        $bundle=read_artifact($options['bundle']); $snapshot=read_artifact($options['snapshot']); $catalog=read_artifact($options['catalog']); $plan=read_artifact($options['plan']);
        foreach([$bundle,$snapshot,$catalog,$plan] as $artifact) { if(!$artifact['value'] instanceof stdClass) { throw new InvalidInput('SCHEMA_OBJECT'); } }
        $result=execute($permit,$bundle['value'],$snapshot['value'],$catalog['value'],$plan['value'],$options['key'],['bundle'=>$bundle['raw_sha256'],'snapshot'=>$snapshot['raw_sha256']],$test);
        if(($test['fault']??'')==='after_commit_exit') { exit(75); } // Deliberately lose the response after the real commit.
    }
    fwrite(STDOUT,encode($result)."\n"); exit(0);
} catch(InvalidInput $e) {
    fwrite(STDOUT,encode(['outcome'=>$e->outcome,'reason'=>$e->reason,'grants_chat'=>false,'changes_sessions'=>false,'production_authenticity'=>'NOT_VERIFIED'])."\n"); exit(2);
} catch(Throwable) {
    // An uncertain commit is resolved by the separate status command, never by blind replay.
    fwrite(STDOUT,encode(['outcome'=>'ERROR_CHECK_RECEIPT','reason'=>'IMPORT_FAILED_CHECK_RECEIPT','grants_chat'=>false,'changes_sessions'=>false])."\n"); exit(3);
}
