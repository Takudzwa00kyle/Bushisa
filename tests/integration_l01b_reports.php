<?php
declare(strict_types=1);

// CLI only: never expose fixture/session creation through the web server.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/support/disposable_database.php';
$settings = bushisa_require_disposable_test_database('integration_l01b_reports');
if (!in_array($settings['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new RuntimeException('L01b runner is local-only');
}
putenv('MAIL_TRANSPORT=fake');
putenv('MAIL_PROVIDER=fake');
putenv('BUSHISA_TEST_HTTP_OVERLAP=true');
putenv('REPORT_CONTEXT_KEYS=' . json_encode(['test-v1' => base64_encode(random_bytes(32)), 'test-v2' => base64_encode(random_bytes(32))]));
putenv('REPORT_CONTEXT_ACTIVE_KEY=test-v1');
$root = dirname(__DIR__);
$runDir = $root . '/.tmp/l01b-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3));
mkdir($runDir, 0700, true);
mkdir($runDir . '/sessions', 0700);
$checks = [];
$overlaps = [];
$servers = [];
$started = gmdate('c');

function check_l01(bool $ok, string $name): void {
    global $checks;
    $checks[] = ['check' => $name, 'result' => $ok ? 'PASS' : 'FAIL'];
    if (!$ok) { throw new RuntimeException($name); }
}
function cli_l01(array $args): string {
    $pipes = [];
    $process = proc_open(array_merge([PHP_BINARY], $args), [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes, dirname(__DIR__));
    if (!is_resource($process)) { throw new RuntimeException('CLI process failed'); }
    fclose($pipes[0]); $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) { throw new RuntimeException('CLI failed: ' . $err . $out); }
    return $out;
}
function sql_l01(string $sql, array $args = []): PDOStatement {
    global $pdo; $stmt = $pdo->prepare($sql); $stmt->execute($args); return $stmt;
}
function count_l01(string $table, string $where = '1=1', array $args = []): int {
    return (int) sql_l01('SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $where, $args)->fetchColumn();
}
function cleanup_text_l01(): array {
    global $pdo;
    // L03a now drains bounded units. This synthetic compatibility fixture walks
    // a full scan; production scheduler installation/cadence is not inferred.
    $totals=['intake_copies_purged'=>0,'text_evidence_purged'=>0];
    $batches=(int)ceil(count_l01('evidence_lifecycle_items','purged_at IS NULL')/25)+2;
    for($n=0;$n<$batches;$n++) {
        $r=bushisa_report_transaction($pdo,fn()=>bushisa_report_cleanup($pdo));
        foreach($totals as $k=>$v) { $totals[$k]+=$r[$k]??0; }
    }
    return $totals;
}
function user_l01(int $n, string $gender = 'male', string $role = 'user'): int {
    sql_l01('INSERT INTO users (nust_student_id,email,password_hash,role,is_verified,is_suspended,is_active,account_status,session_version)
        VALUES (?, ?, ?, ?, 1, 0, 1, "active", 0)',
        ['synthetic-' . $n, 'synthetic' . $n . '@example.invalid', password_hash('Synthet1c!Test', PASSWORD_DEFAULT), $role]);
    global $pdo; $id = (int) $pdo->lastInsertId();
    sql_l01('INSERT INTO profiles (user_id,display_name,anon_handle,gender,date_of_birth) VALUES (?, ?, ?, ?, "2000-01-01")',
        [$id, 'Synthetic ' . $n, 'synthetic_' . $n, $gender]);
    sql_l01('INSERT INTO preferences (user_id,preferred_gender) VALUES (?, ?)', [$id, $gender === 'male' ? 'female' : 'male']);
    return $id;
}
function pair_l01(int $a, int $b, string $status = 'approved'): int {
    global $pdo;
    sql_l01('INSERT INTO matches (user_a_id,user_b_id,matched_at) VALUES (?, ?, UTC_TIMESTAMP())', [min($a,$b),max($a,$b)]);
    $id = (int) $pdo->lastInsertId();
    if ($status !== 'none') {
        sql_l01('INSERT INTO message_requests (match_id,requester_id,recipient_id,status,requested_at,responded_at,expires_at,generation_key)
            VALUES (?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(),INTERVAL 7 DAY), ?)',
            [$id,$a,$b,$status,'match:' . $id]);
    }
    // Only this guarded fresh-synthetic fixture can attest an empty original
    // pair history here. Migration never attests incomplete real legacy data.
    $hasLedger=(bool)$pdo->query("SHOW TABLES LIKE 'pair_request_counts'")->fetchColumn();
    if ($hasLedger) {
        sql_l01('INSERT INTO pair_request_counts (requester_id,recipient_id,request_count,history_verified_at)
            VALUES (?, ?, ?, UTC_TIMESTAMP(6)), (?, ?, 0, UTC_TIMESTAMP(6))',[$a,$b,$status==='none'?0:1,$b,$a]);
        if ($status !== 'none') {
            $requestId=(int)sql_l01('SELECT id FROM message_requests WHERE match_id=?',[$id])->fetchColumn();
            sql_l01('INSERT INTO message_request_sends (requester_id,recipient_id,request_id,created_at) VALUES (?, ?, ?, UTC_TIMESTAMP(6))',[$a,$b,$requestId]);
        }
    }
    if ($pdo->query("SHOW TABLES LIKE 'pair_policy_history'")->fetchColumn()) {
        // Complete fresh synthetic fixture only; this is not a legacy import path.
        sql_l01('INSERT INTO pair_policy_history (user_low,user_high,match_id,history_state,genesis_account_id,generation,created_at) VALUES (?,?,?,"complete_from_genesis",?,1,UTC_TIMESTAMP(6))',[min($a,$b),max($a,$b),$id,$a]);
    }
    return $id;
}
function message_l01(int $pair, int $author, string $body = 'Synthetic reported content'): int {
    global $pdo;
    sql_l01('INSERT INTO messages (match_id,sender_id,body,sent_at,report_segment_key) VALUES (?, ?, ?, UTC_TIMESTAMP(), ?)',
        [$pair, $author, $body, hash('sha256','synthetic-segment:' . $pair)]);
    return (int) $pdo->lastInsertId();
}
function post_l01(?int $author, string $body = 'Synthetic confession'): int {
    global $pdo; sql_l01('INSERT INTO confessions (author_id,body) VALUES (?, ?)', [$author,$body]); return (int) $pdo->lastInsertId();
}
function session_l01(int $actor, bool $mfa = false): array {
    global $runDir;
    ini_set('session.save_path', $runDir . '/sessions');
    ini_set('session.use_cookies','0'); session_cache_limiter('');
    session_id(bin2hex(random_bytes(24))); session_start();
    $csrf = bin2hex(random_bytes(32));
    $_SESSION = ['user_id'=>$actor,'session_version'=>0,'last_activity'=>time(),'csrf_token'=>$csrf,
        'mfa_verified'=>$mfa,'mfa_user_id'=>$actor,'mfa_session_version'=>0];
    $id = session_id(); session_write_close();
    return ['cookie'=>'PHPSESSID=' . $id, 'csrf'=>$csrf];
}
function curl_l01(int $port, string $path, array $auth, ?array $payload = null, array $headers = []): CurlHandle {
    // L02a API requires exact current pair intent on state-changing requests.
    if ($payload !== null && !isset($payload['pair_version']) && ($path === '/api/message_requests.php' || ($path === '/api/safety.php' && in_array($payload['action']??'', ['block','unblock'], true)))) {
        $lookup=isset($payload['match_id'])?'match_id='.$payload['match_id']:'user_id='.$payload['user_id'];
        $prepared=http_l01('/api/pairs.php?'.$lookup,$auth,null,$port);
        if ($prepared['code']===200) {
            $payload['pair_version']=$prepared['json']['data']['pair_version'];
            $payload['idempotency_key']=bin2hex(random_bytes(32));
            if (($payload['action']??'')==='unblock') { $payload['block_id']=$prepared['json']['data']['own_block_id']; }
        }
    }
    $ch = curl_init('http://127.0.0.1:' . $port . $path);
    $base = ['Accept: application/json', 'Cookie: ' . ($auth['cookie'] ?? '')];
    if ($payload !== null) {
        $base[] = 'Content-Type: application/json';
        if (isset($auth['csrf'])) { $base[] = 'X-CSRF-Token: ' . $auth['csrf']; }
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
    }
    curl_setopt_array($ch, [CURLOPT_HTTPHEADER=>array_merge($base,$headers),CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false]);
    return $ch;
}
function result_l01(CurlHandle $ch, string|false $body): array {
    if ($body === false) { throw new RuntimeException('HTTP transport error: ' . curl_error($ch)); }
    return ['code'=>curl_getinfo($ch,CURLINFO_RESPONSE_CODE),'json'=>json_decode($body,true)];
}
function http_l01(string $path, array $auth, ?array $payload = null, int $port = 8097): array {
    $ch = curl_l01($port,$path,$auth,$payload); return result_l01($ch,curl_exec($ch));
}
function ok_l01(array $r, string $name, int $code = 200): array {
    check_l01($r['code'] === $code && is_array($r['json']), $name . ' HTTP ' . $code . ' (actual ' . $r['code'] . ')');
    return $r['json']['data'] ?? [];
}
function proof_message_l01(int $pair, int $message, array $auth): string {
    $data = ok_l01(http_l01('/api/messages.php?match_id=' . $pair,$auth),'authorized message reader');
    foreach ($data as $row) { if ((int)$row['id'] === $message) { return (string)$row['report_context']; } }
    throw new RuntimeException('Returned message proof missing');
}
function report_l01(string $type, int $id, string $context, array $extra = []): array {
    return array_merge(['action'=>'report','target_type'=>$type,'target_id'=>$id,'context'=>$context,
        'idempotency_key'=>bin2hex(random_bytes(32)),'reason'=>'harassment','explanation'=>'Synthetic allegation'], $extra);
}
function concurrent_l01(array $a, array $b, string $point = 'report_before_transaction', ?callable $afterStarted = null): array {
    global $overlaps;
    $id = 'l01b-' . bin2hex(random_bytes(12));
    $headers = ['X-Bushisa-Test-Barrier: ' . $id,'X-Bushisa-Test-Barrier-Expected: 2','X-Bushisa-Test-Barrier-Timeout-Ms: 10000'];
    $multi = curl_multi_init(); $handles = [];
    foreach ([[$a,8097],[$b,8098]] as [$request,$port]) {
        [$path,$auth,$payload] = $request;
        $ch = curl_l01($port,$path,$auth,$payload,$headers); curl_multi_add_handle($multi,$ch); $handles[] = $ch;
    }
    if ($afterStarted !== null) { $afterStarted($id, $multi); }
    do { curl_multi_exec($multi,$running); if ($running) { curl_multi_select($multi,0.1); } } while ($running);
    $results = [];
    foreach ($handles as $ch) { $results[] = result_l01($ch,curl_multi_getcontent($ch)); curl_multi_remove_handle($multi,$ch); }
    curl_multi_close($multi);
    $files = glob(sys_get_temp_dir() . '/bushisa-http-barriers/' . $id . '_' . $point . '-*.json') ?: [];
    $participants = array_map(static fn($f)=>json_decode(file_get_contents($f),true),$files);
    $pids = array_unique(array_column($participants,'pid'));
    check_l01(count($participants)===2 && count($pids)===2, 'distinct HTTP workers reached ' . $point);
    $overlaps[] = ['barrier'=>$id,'point'=>$point,'workers'=>array_values($pids),'results'=>array_column($results,'code')];
    return $results;
}

try {
    // Fresh install, then real 001-005 ->006 upgrade with retained synthetic rows.
    bushisa_bootstrap_disposable_database($root . '/database/bushisa.sql');
    $fresh = cli_l01([$root . '/bin/migrate.php']);
    check_l01(str_contains($fresh,'Applying 006_report_intake.sql'), 'M01 fresh migrations 001-006');
    cli_l01([$root.'/tests/support/activate_pair_ledger.php','--writer-ref=synthetic:l01b-regression']);
    check_l01(str_contains(cli_l01([$root . '/bin/migrate.php']),'Skipping 006_report_intake.sql'), 'M02 migration rerun skips 006');
    $baseName = $settings['name'];
    putenv('DB_NAME=' . $baseName . '_upgrade');
    bushisa_require_disposable_test_database('L01b upgrade');
    bushisa_bootstrap_disposable_database($root . '/database/bushisa.sql');
    check_l01(!str_contains(cli_l01([$root . '/bin/migrate.php','--through=005']),'006_report_intake'), 'M03 real pre-006 schema');
    require $root . '/config/db.php';
    $ua = user_l01(901); $ub = user_l01(902,'female'); $up = pair_l01($ua,$ub);
    sql_l01('INSERT INTO messages (match_id,sender_id,body,sent_at) VALUES (?, ?, "Upgrade retained synthetic", UTC_TIMESTAMP())',[$up,$ub]);
    $upMsg = (int)$pdo->lastInsertId();
    sql_l01('INSERT INTO moderation_cases (reporter_id,target_type,target_id,reported_user_id,reason,status,evidence_purge_due_at)
        VALUES (?, "message", ?, ?, "harassment", "pending", DATE_ADD(UTC_TIMESTAMP(),INTERVAL 24 MONTH))',[$ua,$upMsg,$ub]);
    $legacyCase=(int)$pdo->lastInsertId();
    $legacyBefore=sql_l01('SELECT id,reporter_id,target_type,target_id,reported_user_id,reason,status,evidence_purge_due_at FROM moderation_cases WHERE id=?',[$legacyCase])->fetch();
    $before = $pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();
    cli_l01([$root . '/bin/migrate.php']);
    $upgradedUsers=$pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();
    check_l01($before === array_map(static fn($u)=>array_diff_key($u,['signup_attempt_key'=>0]),$upgradedUsers)
        && count(array_filter($upgradedUsers,static fn($u)=>($u['signup_attempt_key']??null)!==null))===0, 'M04 upgrade preserves all original user values; no generation backfill');
    check_l01(sql_l01('SELECT body FROM messages WHERE id=?',[$upMsg])->fetchColumn()==='Upgrade retained synthetic', 'M05 upgrade preserves message body');
    check_l01(count_l01('message_request_sends')===1, 'M06 existing request event backfilled once');
    check_l01(count_l01('pair_request_counts','history_verified_at IS NOT NULL')===0,'M06 migration never certifies unknown older pair history');
    check_l01($legacyBefore===sql_l01('SELECT id,reporter_id,target_type,target_id,reported_user_id,reason,status,evidence_purge_due_at FROM moderation_cases WHERE id=?',[$legacyCase])->fetch()
        && count_l01('report_probation_periods')===0,'M06 legacy case values preserved without retrospective probation');
    cli_l01([$root . '/bin/migrate.php']);
    check_l01(count_l01('message_request_sends')===1, 'M07 repeat migration does not repeat backfill');
    sql_l01('UPDATE messages SET body="Changed synthetic upgrade" WHERE id=?',[$upMsg]);
    check_l01((int)sql_l01('SELECT report_revision FROM messages WHERE id=?',[$upMsg])->fetchColumn()===2, 'M08 source revision trigger catches direct edits');
    $pdo = null;
    putenv('DB_NAME=' . $baseName);
    require $root . '/config/db.php';
    require_once $root . '/php/report_intake.php';
    require_once $root . '/php/block_handler.php';

    $A=user_l01(1,'female'); $B=user_l01(2); $C=user_l01(3,'female'); $D=user_l01(4);
    $M=user_l01(5,'male','moderator'); $N=user_l01(6,'female','admin');
    $E=user_l01(7);
    $a=session_l01($A); $b=session_l01($B); $c=session_l01($C); $d=session_l01($D);
    $m=session_l01($M,true); $mNo=session_l01($M); $n=session_l01($N,true);
    $pair=pair_l01($A,$B); $msg=message_l01($pair,$B); $own=message_l01($pair,$A,'Synthetic own context');
    $privatePair=pair_l01($C,$D); $secret=message_l01($privatePair,$D,'Unrelated synthetic private content');

    foreach ([8097,8098] as $port) {
        $existing=@fsockopen('127.0.0.1',$port,$errno,$error,0.1);
        if ($existing) { fclose($existing); throw new RuntimeException('Refusing occupied HTTP port ' . $port); }
        putenv('REPORT_CONTEXT_ACTIVE_KEY=' . ($port === 8097 ? 'test-v1' : 'test-v2'));
        $log=$runDir . '/http-' . $port . '.log';
        $pipes=[];
        $process=proc_open([PHP_BINARY,'-d','session.save_path=' . $runDir . '/sessions','-S','127.0.0.1:' . $port,'-t',$root],
            [0=>['pipe','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,$root);
        if (!is_resource($process)) { throw new RuntimeException('HTTP worker start failed'); }
        fclose($pipes[0]); $servers[]=$process;
    }
    putenv('REPORT_CONTEXT_ACTIVE_KEY=test-v1');
    for ($i=0;$i<40;$i++) { $s=@fsockopen('127.0.0.1',8098,$errno,$error,0.05); if ($s) { fclose($s); break; } usleep(50000); }
    $proof=proof_message_l01($pair,$msg,$a);
    $discovery=ok_l01(http_l01('/api/discover.php?limit=50',$a),'A01 authorized discovery')['results'];
    $card=array_values(array_filter($discovery,fn($row)=>(int)$row['user_id']===$E))[0] ?? null;
    check_l01(is_array($card) && is_string($card['report_context']),'A01 proof belongs to actually returned card');
    $accountReceipt=ok_l01(http_l01('/api/safety.php',$a,report_l01('user',$E,$card['report_context'],['new_incident'=>true])),'A01 account report without prior match');
    check_l01(count_l01('matches','(user_a_id=? AND user_b_id=?) OR (user_a_id=? AND user_b_id=?)',[$A,$E,$E,$A])===0,'A01 report grants no relationship');
    $ownProof=proof_message_l01($pair,$own,$a);
    $input=report_l01('message',$msg,$proof,['context_tokens'=>[$ownProof]]);
    $receipt=ok_l01(http_l01('/api/safety.php',$a,$input),'A02 authorized message with own context');
    check_l01(count_l01('moderation_cases')===2 && count_l01('moderation_evidence')===2,'A02/A39 exact bounded evidence');
    check_l01(array_keys($receipt)===['receipt_id','submission_id','received_at','status','operation_expires_at'],'A02 safe opaque receipt only');
    check_l01(count_l01('report_probation_periods','actor_id=?',[$B])===1,'A31 actual author probation');
    check_l01((int)sql_l01('SELECT TIMESTAMPDIFF(SECOND,starts_at,expires_at) FROM report_probation_periods WHERE actor_id=?',[$B])->fetchColumn()===604800,'A31 fixed 168 hours');
    check_l01(sql_l01('SELECT status FROM moderation_cases')->fetchColumn()==='pending' && count_l01('users','is_suspended=1')===0,'A31 allegation is not a finding/ban');

    $retry=ok_l01(http_l01('/api/safety.php',$a,$input),'A23 exact retry');
    check_l01($receipt===$retry && count_l01('report_operations')===2,'A23 same receipt no repeated effect');
    check_l01(ok_l01(http_l01('/api/safety.php',$a,$input,8098),'A23 active key rotation on distinct worker')===$receipt,'A23 old key digest remains resolvable');
    $changed=$input; $changed['explanation']='Different intent';
    ok_l01(http_l01('/api/safety.php',$a,$changed),'A25 changed same-key intent',409);
    $changed=$input; $changed['action']='block_and_report';
    ok_l01(http_l01('/api/safety.php',$a,$changed),'A25 operation namespace cannot reuse key',409);
    $equiv=$input; $equiv['idempotency_key']=bin2hex(random_bytes(32));
    $equiv['context']=proof_message_l01($pair,$msg,$a); $equiv['context_tokens']=[proof_message_l01($pair,$own,$a)];
    check_l01(ok_l01(http_l01('/api/safety.php',$a,$equiv),'A26 equivalent context')===$receipt && count_l01('report_submissions')===2,'A26 equivalent envelopes deduplicate information');

    $unauthorized=report_l01('message',$secret,$proof);
    $u1=http_l01('/api/safety.php',$a,$unauthorized);
    $unauthorized['target_id']=999999;
    $u2=http_l01('/api/safety.php',$a,$unauthorized);
    check_l01($u1===$u2 && $u1['code']===404,'A11 inaccessible and absent targets share unavailable projection');
    ok_l01(http_l01('/api/safety.php',$c,$input),'A12 cross-reporter proof',404);
    ok_l01(http_l01('/api/safety.php',$a,array_merge($input,['reported_user_id'=>$D])),'A12 forged authority field',400);
    ok_l01(http_l01('/api/safety.php',$c,['action'=>'report_receipt','receipt_id'=>$receipt['receipt_id']]),'A14 other receipt',404);
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$msg,'')),'A15 raw ID without proof',404);
    ok_l01(http_l01('/api/safety.php',['cookie'=>$a['cookie']],$input),'A10 CSRF required',403);
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$own,$ownProof)),'A22/A39 self primary',400);
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$msg,$proof,['explanation'=>str_repeat('é',2001)])),'A22 Unicode length without truncation',400);
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$msg,$proof,['evidence_path'=>'https://example.invalid/private'])),'A22 unsupported image/URL authority',400);

    $wrongGeneration=bushisa_report_open($proof,$A,bushisa_report_keys()); $wrongGeneration['container']='another-segment';
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$msg,bushisa_report_seal($wrongGeneration))),'A13 wrong generation fails closed',404);
    $otherSegment=message_l01($pair,$A,'Different synthetic segment');
    sql_l01('UPDATE messages SET report_segment_key=? WHERE id=?',[hash('sha256','other-segment'),$otherSegment]);
    $otherProof=proof_message_l01($pair,$otherSegment,$a);
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$msg,$proof,['context_tokens'=>[$otherProof]])),'A21 cross-segment context denied',404);
    $contextTokens=[];
    for($i=0;$i<11;$i++) {
        $contextId=message_l01($pair,$A,'Synthetic preceding context '.$i);
        sql_l01('UPDATE messages SET sent_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 2 MINUTE) WHERE id=?',[$contextId]);
        $contextTokens[]=proof_message_l01($pair,$contextId,$a);
    }
    $contextCount=count_l01('moderation_evidence');
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$msg,$proof,['context_tokens'=>$contextTokens])),'A21 eleven preceding items denied',400);
    check_l01(count_l01('moderation_evidence')===$contextCount,'A21 excess context rolls back all new evidence');

    $oldDeadline=sql_l01('SELECT starts_at,expires_at FROM report_probation_periods WHERE actor_id=?',[$B])->fetch();
    $next=message_l01($pair,$B,'Second synthetic event');
    $nextProof=proof_message_l01($pair,$next,$a);
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$next,$nextProof)),'A32 new active-period report');
    check_l01(count_l01('report_probation_periods','actor_id=?',[$B])===1 &&
        $oldDeadline===sql_l01('SELECT starts_at,expires_at FROM report_probation_periods WHERE actor_id=?',[$B])->fetch(),'A32 active period never resets/extends');

    $stale=message_l01($pair,$B,'Originally exposed synthetic');
    $staleProof=proof_message_l01($pair,$stale,$a);
    sql_l01('UPDATE messages SET body="Later unseen synthetic version" WHERE id=?',[$stale]);
    $beforeEvidence=count_l01('moderation_evidence');
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$stale,$staleProof)),'A16 stale version metadata');
    check_l01(count_l01('moderation_evidence')===$beforeEvidence,'A16 later version not captured');
    $unsent=message_l01($pair,$B,'Synthetic unsend first');
    $unsentProof=proof_message_l01($pair,$unsent,$a);
    ok_l01(http_l01('/api/messages.php',$b,['action'=>'unsend','match_id'=>$pair,'message_id'=>$unsent]),'A08 actual HTTP unsend');
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$unsent,$unsentProof)),'A08 metadata report after unsend');
    check_l01(count_l01('moderation_evidence','source_id=? AND source_type="message"',[$unsent])===0,'A08 no erased-original recapture');
    $reportFirst=message_l01($pair,$B,'Report-first preserved synthetic');
    $reportFirstProof=proof_message_l01($pair,$reportFirst,$a);
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$reportFirst,$reportFirstProof)),'A19 report-first serial order');
    ok_l01(http_l01('/api/messages.php',$b,['action'=>'unsend','match_id'=>$pair,'message_id'=>$reportFirst]),'A19 unsend after report');
    check_l01(sql_l01('SELECT body FROM messages WHERE id=?',[$reportFirst])->fetchColumn()==='' &&
        count_l01('moderation_evidence','source_type="message" AND source_id=? AND evidence_excerpt IS NOT NULL',[$reportFirst])===1,'A19 only pre-existing evidence survives unsend');
    $overlapSource=message_l01($pair,$B,'Overlapping synthetic source'); $overlapProof=proof_message_l01($pair,$overlapSource,$a);
    $overlap=concurrent_l01(['/api/safety.php',$a,report_l01('message',$overlapSource,$overlapProof)],
        ['/api/messages.php',$b,['action'=>'unsend','match_id'=>$pair,'message_id'=>$overlapSource]]);
    ok_l01($overlap[0],'A19 report/unsend overlap report'); ok_l01($overlap[1],'A19 report/unsend overlap unsend');
    check_l01(sql_l01('SELECT body FROM messages WHERE id=?',[$overlapSource])->fetchColumn()==='' &&
        count_l01('moderation_evidence','source_type="message" AND source_id=?',[$overlapSource])<=1,'A19 overlap has one serialized capture or metadata-only result');
    $rows=ok_l01(http_l01('/api/messages.php?match_id='.$pair,$a),'A08 ordinary reader after unsend');
    check_l01(!in_array($unsent,array_column($rows,'id'),true),'A08 unsent entry disappears');
    ok_l01(http_l01('/api/safety.php',$a,$input),'A18 old operation remains receipt-only');
    $originalCase=(int)sql_l01('SELECT case_id FROM report_incidents WHERE receipt_id=?',[$receipt['receipt_id']])->fetchColumn();
    check_l01(count_l01('moderation_evidence','case_id<>? AND source_id=? AND source_type="message"',[$originalCase,$msg])===0,'A18 another case cannot select existing evidence');

    $expiry=message_l01($pair,$B,'Synthetic expired source');
    $expiryProof=proof_message_l01($pair,$expiry,$a);
    sql_l01('UPDATE messages SET evidence_purge_due_at=UTC_TIMESTAMP() WHERE id=?',[$expiry]);
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$expiry,$expiryProof)),'A17 source deadline applies before cleanup',404);
    $p=bushisa_report_open($proof,$A,bushisa_report_keys()); $p['expires']=bushisa_report_now($pdo);
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$msg,bushisa_report_seal($p))),'A17 proof at expiry',404);
    $missing=message_l01($pair,$B,'Synthetic physically erased'); $missingProof=proof_message_l01($pair,$missing,$a);
    sql_l01('DELETE FROM messages WHERE id=?',[$missing]);
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$missing,$missingProof)),'A09 unavailable physical target',404);
    $pairRead=ok_l01(http_l01('/api/message_requests.php?match_id='.$pair,$a),'A03 own request event');
    ok_l01(http_l01('/api/safety.php',$a,report_l01('match',$pair,$pairRead['report_context'],['conduct_actor_id'=>$B])),'A03/A09 explicit pair fallback');
    $pendingPair=pair_l01($A,$D,'pending');
    bushisa_report_transaction($pdo,fn()=>bushisa_close_match_between($pdo,$A,$D,'unmatched'));
    $pendingState=ok_l01(http_l01('/api/message_requests.php?match_id='.$pendingPair,$d),'A03 unmatched inbound pending request');
    $pendingExpiry=$pendingState['row']['expires_at'];
    ok_l01(http_l01('/api/safety.php',$d,report_l01('match',$pendingPair,$pendingState['report_context'],['conduct_actor_id'=>$A])),'A03 report inbound pending event while unmatched');
    ok_l01(http_l01('/api/message_requests.php',$d,['action'=>'approve','match_id'=>$pendingPair]),'A03 no unmatched approval',409);
    ok_l01(http_l01('/api/message_requests.php',$d,['action'=>'decline','match_id'=>$pendingPair]),'A03 terminal declined event');
    $terminalState=ok_l01(http_l01('/api/message_requests.php?match_id='.$pendingPair,$d),'A03 retained terminal request');
    ok_l01(http_l01('/api/safety.php',$d,report_l01('match',$pendingPair,$terminalState['report_context'],['conduct_actor_id'=>$A])),'A27 distinct request-state event');
    check_l01($terminalState['row']['expires_at']===$pendingExpiry,'A03 reporting does not renew request expiry');

    // Independent reporters can see a public confession, without its private author.
    $post=post_l01($D); $source=post_l01($B,'Source erased'); $quote=post_l01($D,'Independent quoting post');
    sql_l01('INSERT INTO confession_references (confession_id,referenced_confession_id,created_at) VALUES (?, ?, UTC_TIMESTAMP())',[$quote,$source]);
    $reference=(int)$pdo->lastInsertId();
    sql_l01('UPDATE confessions SET body="", deleted_at=UTC_TIMESTAMP() WHERE id=?',[$source]);
    $boardA=ok_l01(http_l01('/api/confessions.php',$a),'A04 board response')['confessions'];
    $boardC=ok_l01(http_l01('/api/confessions.php',$c),'A04 independent board response')['confessions'];
    $postA=array_values(array_filter($boardA,fn($r)=>$r['id']===$post))[0];
    $postC=array_values(array_filter($boardC,fn($r)=>$r['id']===$post))[0];
    check_l01(!isset($postA['author_id']),'A04 no private author in returned post');
    $rr=concurrent_l01(['/api/safety.php',$a,report_l01('confession',$post,$postA['report_context'])],
        ['/api/safety.php',$c,report_l01('confession',$post,$postC['report_context'])]);
    $r1=ok_l01($rr[0],'A28 reporter A'); $r2=ok_l01($rr[1],'A28 reporter C');
    check_l01($r1['receipt_id']!==$r2['receipt_id'] && count_l01('report_probation_periods','actor_id=?',[$D])===1,'A28 private independent cases one initial period');
    check_l01((int)sql_l01('SELECT is_flagged FROM confessions WHERE id=?',[$post])->fetchColumn()===0,'A04 filing never sets global finding/flag');
    $quoteRow=array_values(array_filter($boardA,fn($r)=>$r['id']===$quote))[0]['reference'];
    $qr=ok_l01(http_l01('/api/safety.php',$a,report_l01('confession_reference',$reference,$quoteRow['report_context'])),'A05 actual reference with deleted source');
    $qc=(int)sql_l01('SELECT case_id FROM report_incidents WHERE receipt_id=?',[$qr['receipt_id']])->fetchColumn();
    check_l01((int)sql_l01('SELECT reported_user_id FROM moderation_cases WHERE id=?',[$qc])->fetchColumn()===$D,'A37 quote author, never original source author');
    check_l01(count_l01('moderation_evidence','case_id=? AND evidence_excerpt="Source erased"',[$qc])===0,'A05 source never reconstructed');

    $amb=post_l01(null,'Synthetic unknown author');
    $board=ok_l01(http_l01('/api/confessions.php',$a),'A38 anonymous-attribution response')['confessions'];
    $ambRow=array_values(array_filter($board,fn($r)=>$r['id']===$amb))[0];
    $periodCount=count_l01('report_probation_periods');
    ok_l01(http_l01('/api/safety.php',$a,report_l01('confession',$amb,$ambRow['report_context'])),'A38 accepted ambiguous attribution');
    check_l01(count_l01('report_probation_periods')===$periodCount,'A38 no guessed actor probation');
    $ambPair=ok_l01(http_l01('/api/message_requests.php?match_id='.$pair,$a),'A38 own pair proof')['report_context'];
    ok_l01(http_l01('/api/safety.php',$a,report_l01('match',$pair,$ambPair)),'A38 ambiguous conduct participant');
    check_l01(count_l01('report_probation_periods')===$periodCount,'A38 never penalize both pair participants');
    $changedAuthor=post_l01($B,'Synthetic disputed attribution');
    $changedBoard=ok_l01(http_l01('/api/confessions.php?limit=50',$a),'A38 attribution exposure')['confessions'];
    $changedAuthorProof=array_values(array_filter($changedBoard,fn($r)=>$r['id']===$changedAuthor))[0]['report_context'];
    sql_l01('UPDATE confessions SET author_id=? WHERE id=?',[$E,$changedAuthor]);
    $beforePeriods=count_l01('report_probation_periods');
    ok_l01(http_l01('/api/safety.php',$a,report_l01('confession',$changedAuthor,$changedAuthorProof)),'A38 disputed attribution accepted for review');
    check_l01(count_l01('report_probation_periods')===$beforePeriods &&
        count_l01('moderation_evidence','source_type="confession" AND source_id=?',[$changedAuthor])===0,'A38 no guessed sanction or unseen revised capture');

    // Real HTTP receipt-before-expiry, activation-after-expiry ordering.
    // The fixture clock is advanced only on this disposable database.
    $waitPost1=post_l01($D,'Delayed validation one'); $waitPost2=post_l01($D,'Delayed validation two');
    $waitBoardA=ok_l01(http_l01('/api/confessions.php?limit=50',$a),'A40 reporter A context')['confessions'];
    $waitBoardC=ok_l01(http_l01('/api/confessions.php?limit=50',$c),'A40 reporter C context')['confessions'];
    $wait1=array_values(array_filter($waitBoardA,fn($r)=>$r['id']===$waitPost1))[0]['report_context'];
    $wait2=array_values(array_filter($waitBoardC,fn($r)=>$r['id']===$waitPost2))[0]['report_context'];
    sql_l01('UPDATE report_probation_periods SET expires_at=DATE_ADD(UTC_TIMESTAMP(6),INTERVAL 2 SECOND) WHERE actor_id=?',[$D]);
    $end=(string)sql_l01('SELECT MAX(expires_at) FROM report_probation_periods WHERE actor_id=?',[$D])->fetchColumn();
    $lockName='bushisa-report:'.substr(hash('sha256',$baseName),0,32);
    check_l01((int)sql_l01('SELECT GET_LOCK(?, 1)',[$lockName])->fetchColumn()===1,'A40 controlled validation wait lock');
    $periodsBefore=count_l01('report_probation_periods','actor_id=?',[$D]);
    $rr=concurrent_l01(['/api/safety.php',$a,report_l01('confession',$waitPost1,$wait1)],
        ['/api/safety.php',$c,report_l01('confession',$waitPost2,$wait2)],'report_before_transaction',
        function(string $id, CurlMultiHandle $multi) use ($end,$lockName): void {
            $deadline=microtime(true)+4;
            do {
                curl_multi_exec($multi,$running);
                $files=glob(sys_get_temp_dir().'/bushisa-http-barriers/'.$id.'_report_before_transaction-*.json')?:[];
                if(count($files)===2){break;}
                usleep(10000);
            } while(microtime(true)<$deadline);
            check_l01(count($files)===2,'A40 both actual HTTP receipts precede controlled release');
            while(bushisa_report_now($GLOBALS['pdo'])<=$end){usleep(20000);}
            sql_l01('SELECT RELEASE_LOCK(?)',[$lockName]);
        });
    $waitReceipt1=ok_l01($rr[0],'A40 delayed worker A'); $waitReceipt2=ok_l01($rr[1],'A40 delayed worker C');
    check_l01(bushisa_report_time($waitReceipt1['received_at'])<bushisa_report_time($end)
        && bushisa_report_time($waitReceipt2['received_at'])<bushisa_report_time($end),'A40 stored original UTC receipts precede expiry');
    check_l01(count_l01('report_probation_periods','actor_id=?',[$D])===$periodsBefore,'A40 receipt during active period never renews after worker delay');

    $renew1=post_l01($D,'New post-expiry report one'); $renew2=post_l01($D,'New post-expiry report two');
    $renewBoardA=ok_l01(http_l01('/api/confessions.php?limit=50',$a),'A45 reporter A context')['confessions'];
    $renewBoardC=ok_l01(http_l01('/api/confessions.php?limit=50',$c),'A45 reporter C context')['confessions'];
    $renewProof1=array_values(array_filter($renewBoardA,fn($r)=>$r['id']===$renew1))[0]['report_context'];
    $renewProof2=array_values(array_filter($renewBoardC,fn($r)=>$r['id']===$renew2))[0]['report_context'];
    $rr=concurrent_l01(['/api/safety.php',$a,report_l01('confession',$renew1,$renewProof1)],
        ['/api/safety.php',$c,report_l01('confession',$renew2,$renewProof2)]);
    $renewReceipt1=ok_l01($rr[0],'A45 renewal worker A'); $renewReceipt2=ok_l01($rr[1],'A45 renewal worker C');
    check_l01($renewReceipt1['receipt_id']!==$renewReceipt2['receipt_id'] &&
        count_l01('report_probation_periods','actor_id=?',[$D])===$periodsBefore+1,'A45 two new qualifying reports create one fresh fixed period');

    $retryPost=post_l01($D,'Synthetic serialization retry across expiry');
    $retryBoard=ok_l01(http_l01('/api/confessions.php?limit=50',$a),'A40 retry exposure')['confessions'];
    $retryProof=array_values(array_filter($retryBoard,fn($r)=>$r['id']===$retryPost))[0]['report_context'];
    sql_l01('UPDATE report_probation_periods SET expires_at=DATE_ADD(UTC_TIMESTAMP(6),INTERVAL 1 SECOND)
        WHERE actor_id=? AND expires_at>UTC_TIMESTAMP(6)',[$D]);
    $retryEnd=(string)sql_l01('SELECT MAX(expires_at) FROM report_probation_periods WHERE actor_id=?',[$D])->fetchColumn();
    $retryPeriods=count_l01('report_probation_periods','actor_id=?',[$D]);
    $pdo->exec('CREATE TRIGGER l01b_synthetic_retry BEFORE INSERT ON report_operations FOR EACH ROW BEGIN
        IF @l01b_retry_once IS NULL THEN SET @l01b_retry_once=1; DO SLEEP(2);
        SIGNAL SQLSTATE "40001" SET MESSAGE_TEXT="synthetic serialization fault"; END IF; END');
    $retryReceipt=ok_l01(http_l01('/api/safety.php',$a,report_l01('confession',$retryPost,$retryProof)),'A24/A40 bounded forced-40001 retry');
    $pdo->exec('DROP TRIGGER l01b_synthetic_retry');
    check_l01(bushisa_report_time($retryReceipt['received_at'])<bushisa_report_time($retryEnd) &&
        count_l01('report_probation_periods','actor_id=?',[$D])===$retryPeriods &&
        count_l01('moderation_evidence','source_type="confession" AND source_id=?',[$retryPost])===1,'A24/A40 retry preserves original receipt, one capture, no expiry renewal');

    // A fault after block/evidence/initial probation writes rolls them all back.
    $F=user_l01(8); $faultPost=post_l01($F,'Synthetic late write rollback');
    $faultBoard=ok_l01(http_l01('/api/confessions.php?limit=50',$a),'A35 late-fault exposure')['confessions'];
    $faultProof=array_values(array_filter($faultBoard,fn($r)=>$r['id']===$faultPost))[0]['report_context'];
    ok_l01(http_l01('/api/safety.php',$a,['action'=>'block','user_id'=>$F]),'A35 pre-existing ordinary block');
    $faultInput=report_l01('confession',$faultPost,$faultProof,['action'=>'block_and_report']);
    $faultCounts=[count_l01('moderation_cases'),count_l01('moderation_evidence'),count_l01('report_submissions'),count_l01('report_operations'),count_l01('report_block_links')];
    $pdo->exec('CREATE TRIGGER l01b_synthetic_failure BEFORE INSERT ON report_operations FOR EACH ROW
        SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT="synthetic final write failure"');
    ok_l01(http_l01('/api/safety.php',$a,$faultInput),'A35 final write failure',503);
    $pdo->exec('DROP TRIGGER l01b_synthetic_failure');
    check_l01($faultCounts===[count_l01('moderation_cases'),count_l01('moderation_evidence'),count_l01('report_submissions'),count_l01('report_operations'),count_l01('report_block_links')]
        && count_l01('report_probation_periods','actor_id=?',[$F])===0
        && count_l01('blocks','blocker_id=? AND blocked_id=?',[$A,$F])===1,'A35 full rollback preserves only the pre-existing block');
    ok_l01(http_l01('/api/safety.php',$a,$faultInput),'A24 retry after rolled-back final failure');
    check_l01(count_l01('report_probation_periods','actor_id=?',[$F])===1,'A24 initial probation created once after successful retry');

    // Concurrent retries use two processes, not one session/worker mutex.
    $race=message_l01($pair,$B,'Concurrent synthetic report');
    $raceProof=proof_message_l01($pair,$race,$a);
    $raceInput=report_l01('message',$race,$raceProof);
    $casesBefore=count_l01('moderation_cases');
    $rr=concurrent_l01(['/api/safety.php',$a,$raceInput],['/api/safety.php',$a,$raceInput]);
    check_l01(ok_l01($rr[0],'A24 worker 1')===ok_l01($rr[1],'A24 worker 2') &&
        count_l01('moderation_cases')===$casesBefore+1,'A24 same-key once-only atomic result');
    $race2=message_l01($pair,$B,'Different keys same incident');
    $rp2=proof_message_l01($pair,$race2,$a); $caseCount=count_l01('moderation_cases'); $subCount=count_l01('report_submissions');
    $rr=concurrent_l01(['/api/safety.php',$a,report_l01('message',$race2,$rp2)],
        ['/api/safety.php',$a,report_l01('message',$race2,$rp2)]);
    check_l01(ok_l01($rr[0],'A26 worker 1')===ok_l01($rr[1],'A26 worker 2') &&
        count_l01('moderation_cases')===$caseCount+1 && count_l01('report_submissions')===$subCount+1,'A26 concurrent incident and info dedup');

    // Roll back after evidence/probation writes when a supplied block is unauthorized.
    $bad=message_l01($pair,$B,'Synthetic rollback target'); $badProof=proof_message_l01($pair,$bad,$a);
    $counts=[count_l01('moderation_cases'),count_l01('moderation_evidence'),count_l01('report_operations')];
    $badInput=report_l01('message',$bad,$badProof,['block_id'=>999999]);
    ok_l01(http_l01('/api/safety.php',$a,$badInput),'A35 failed block attachment',404);
    check_l01($counts===[count_l01('moderation_cases'),count_l01('moderation_evidence'),count_l01('report_operations')],'A35 no orphan case/evidence/outcome');
    unset($badInput['block_id']);
    ok_l01(http_l01('/api/safety.php',$a,$badInput),'A24 same key usable after full rollback');

    // An old accepted event cannot reactivate after expiry; a new event can.
    sql_l01('UPDATE report_probation_periods SET starts_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 8 DAY),
        expires_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 1 DAY) WHERE actor_id=?',[$B]);
    $oldCount=count_l01('report_probation_periods','actor_id=?',[$B]);
    ok_l01(http_l01('/api/safety.php',$a,$input),'A33 old trigger replay after expiry');
    check_l01(count_l01('report_probation_periods','actor_id=?',[$B])===$oldCount,'A33 retries cannot renew');
    $newEvent=message_l01($pair,$B,'Post-expiry independent event');
    $newProof=proof_message_l01($pair,$newEvent,$a);
    $beforeCases=count_l01('moderation_cases');
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$newEvent,$newProof,
        ['action'=>'report_addendum','receipt_id'=>$receipt['receipt_id']])),'A33/A44 new qualifying event linked to existing case');
    check_l01(count_l01('report_probation_periods','actor_id=?',[$B])===$oldCount+1,'A33 fresh period starts once');
    check_l01(count_l01('moderation_cases')===$beforeCases,'A44 case reuse does not suppress new qualifying event');
    $periodsBeforeAdd=count_l01('report_probation_periods');
    ok_l01(http_l01('/api/safety.php',$a,['action'=>'report_addendum','receipt_id'=>$receipt['receipt_id'],
        'idempotency_key'=>bin2hex(random_bytes(32)),'reason'=>'harassment','explanation'=>'Additional explanation only']), 'A29 own narrative addendum');
    check_l01(count_l01('report_probation_periods')===$periodsBeforeAdd,'A44 narrative update is not a new sanction');
    $t='2026-01-01 00:00:00.000000';
    check_l01(!bushisa_report_should_activate($t,'2025-12-31 23:59:59.999999','2026-01-01 00:00:01.000000')
        && bushisa_report_should_activate($t,$t,$t)
        && bushisa_report_should_activate($t,'2026-01-01 00:00:00.000001','2026-01-01 00:00:00.000002'),'A40 exact boundary and delayed active-period validation');

    // Current request allowance uses successful events, not report counts.
    $freshTargets=[];
    for($i=10;$i<14;$i++) { $u=user_l01($i,'female'); $freshTargets[]=pair_l01($B,$u,'none'); }
    $unknownRecipient=(int)sql_l01('SELECT user_b_id FROM matches WHERE id=?',[$freshTargets[0]])->fetchColumn();
    sql_l01('UPDATE pair_policy_history SET history_state="legacy_incomplete" WHERE match_id=?',[$freshTargets[0]]);
    ok_l01(http_l01('/api/message_requests.php',$b,['action'=>'request','match_id'=>$freshTargets[0]]),'A31 missing history proof cannot be treated as a fresh allowance',503);
    sql_l01('UPDATE pair_policy_history SET history_state="complete_from_genesis" WHERE match_id=?',[$freshTargets[0]]);
    for($i=0;$i<2;$i++) { ok_l01(http_l01('/api/message_requests.php',$b,['action'=>'request','match_id'=>$freshTargets[$i]]),'A31 probation successful send ' . $i); }
    $rr=concurrent_l01(['/api/message_requests.php',$b,['action'=>'request','match_id'=>$freshTargets[2]]],
        ['/api/message_requests.php',$b,['action'=>'request','match_id'=>$freshTargets[3]]],'message_request_before_mutation');
    $sendCodes=array_column($rr,'code'); sort($sendCodes);
    check_l01($sendCodes===[200,429],'A31 concurrent send attempts cannot overspend remaining allowance');
    $deniedTarget=$rr[0]['code']===429?$freshTargets[2]:$freshTargets[3];
    ok_l01(http_l01('/api/message_requests.php',$b,['action'=>'request','match_id'=>$deniedTarget]),'A31 fourth probation send denied',429);
    sql_l01('UPDATE message_request_sends SET created_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 13 HOUR) WHERE requester_id=?',[$B]);
    ok_l01(http_l01('/api/message_requests.php',$b,['action'=>'request','match_id'=>$deniedTarget]),'A31 active probation counts prior 24 hours',429);
    sql_l01('UPDATE report_probation_periods SET expires_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 1 SECOND) WHERE actor_id=?',[$B]);
    ok_l01(http_l01('/api/message_requests.php',$b,['action'=>'request','match_id'=>$deniedTarget]),'A31 normal 12-hour allowance after actual expiry');
    check_l01(count_l01('message_request_sends','requester_id=?',[$B])===4,'A31 no lifetime send reset');
    sql_l01('UPDATE users SET is_suspended=1 WHERE id=?',[$B]);
    ok_l01(http_l01('/api/message_requests.php',$b,['action'=>'request','match_id'=>$freshTargets[3]]),'A10 restrictions dominate unused allowance',401);
    sql_l01('UPDATE users SET is_suspended=0 WHERE id=?',[$B]); $b=session_l01($B);

    // Preserve the independent incoming and lifetime ceilings while integrating
    // probation. Every claimed overlapping attempt reaches a different worker.
    $receiver=user_l01(14,'female');
    for($i=20;$i<29;$i++) {
        $sender=user_l01($i); $senderAuth=session_l01($sender); $sendPair=pair_l01($sender,$receiver,'none');
        ok_l01(http_l01('/api/message_requests.php',$senderAuth,['action'=>'request','match_id'=>$sendPair]),'A31 incoming-budget setup successful send '.$i);
    }
    $senderX=user_l01(29); $senderY=user_l01(30); $sx=session_l01($senderX); $sy=session_l01($senderY);
    $pairX=pair_l01($senderX,$receiver,'none'); $pairY=pair_l01($senderY,$receiver,'none');
    $rr=concurrent_l01(['/api/message_requests.php',$sx,['action'=>'request','match_id'=>$pairX]],
        ['/api/message_requests.php',$sy,['action'=>'request','match_id'=>$pairY]],'message_request_before_mutation');
    $codes=array_column($rr,'code'); sort($codes);
    check_l01($codes===[200,429] && count_l01('message_request_sends','recipient_id=?',[$receiver])===10,'A31 concurrent incoming ceiling remains ten per 12 hours');
    $pairRecipient=user_l01(31,'female'); $lifetimePair=pair_l01($senderX,$pairRecipient,'none');
    for($i=0;$i<3;$i++) {
        sql_l01('UPDATE message_request_sends SET created_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 25 HOUR) WHERE requester_id=?',[$senderX]);
        ok_l01(http_l01('/api/message_requests.php',$sx,['action'=>'request','match_id'=>$lifetimePair]),'A31 lifetime setup send '.$i);
        ok_l01(http_l01('/api/message_requests.php',$sx,['action'=>'cancel','match_id'=>$lifetimePair]),'A31 cancel without lifetime reset '.$i);
        sql_l01('UPDATE message_requests SET closed_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 13 HOUR) WHERE match_id=?',[$lifetimePair]);
        // Advance the independent synthetic cooldown clock as well.
        sql_l01('UPDATE pair_request_counts c JOIN message_requests mr ON mr.requester_id=c.requester_id AND mr.recipient_id=c.recipient_id SET c.retry_not_before=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 1 HOUR) WHERE mr.match_id=?',[$lifetimePair]);
    }
    sql_l01('UPDATE message_request_sends SET created_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 25 HOUR) WHERE requester_id=?',[$senderX]);
    bushisa_report_transaction($pdo,fn()=>bushisa_report_cleanup($pdo));
    check_l01(count_l01('message_request_sends','requester_id=?',[$senderX])===0 &&
        (int)sql_l01('SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',[$senderX,$pairRecipient])->fetchColumn()===3,'A31 rolling timestamp cleanup preserves minimal lifetime count');
    ok_l01(http_l01('/api/message_requests.php',$sx,['action'=>'request','match_id'=>$lifetimePair]),'A31 lifetime fourth send denied after rolling cleanup',429);
    $declineRecipient=user_l01(32,'female'); $dr=session_l01($declineRecipient);
    $declinePair=pair_l01($senderY,$declineRecipient,'none');
    ok_l01(http_l01('/api/message_requests.php',$sy,['action'=>'request','match_id'=>$declinePair]),'A31 directional decline setup');
    ok_l01(http_l01('/api/message_requests.php',$dr,['action'=>'decline','match_id'=>$declinePair]),'A31 recipient records decline');
    ok_l01(http_l01('/api/message_requests.php',$dr,['action'=>'request','match_id'=>$declinePair]),'A31 approved reverse direction remains available');
    ok_l01(http_l01('/api/message_requests.php',$dr,['action'=>'cancel','match_id'=>$declinePair]),'A31 reverse requester may cancel');
    sql_l01('UPDATE message_requests SET closed_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 13 HOUR) WHERE match_id=?',[$declinePair]);
    // Advance the independent synthetic cooldown clock as well.
    sql_l01('UPDATE pair_request_counts c JOIN message_requests mr ON mr.requester_id=c.requester_id AND mr.recipient_id=c.recipient_id SET c.retry_not_before=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 1 HOUR) WHERE mr.match_id=?',[$declinePair]);
    ok_l01(http_l01('/api/message_requests.php',$sy,['action'=>'request','match_id'=>$declinePair]),'A31 reverse request cannot erase original directional decline',409);

    // Unmatch alone preserves approval; reporting makes no relationship mutation.
    bushisa_report_transaction($pdo,fn()=>bushisa_close_match_between($pdo,$A,$B,'unmatched'));
    check_l01(sql_l01('SELECT status FROM message_requests WHERE match_id=?',[$pair])->fetchColumn()==='approved','A06 unmatch preserves chat grant');
    ok_l01(http_l01('/api/messages.php?match_id='.$pair,$a),'A06 approved chat stays readable');
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$msg,$proof,['explanation'=>'Additional historical detail'])),'A06 report after unmatch');
    check_l01(sql_l01('SELECT match_status FROM matches WHERE id=?',[$pair])->fetchColumn()==='unmatched','A06 report does not undo unmatch');

    // Attach report to ordinary personal block, require assigned MFA clearance,
    // then still require the actual blocker's separate choice.
    ok_l01(http_l01('/api/safety.php',$a,['action'=>'block','user_id'=>$B]),'A07 ordinary block');
    $block=(int)sql_l01('SELECT id FROM blocks WHERE blocker_id=? AND blocked_id=?',[$A,$B])->fetchColumn();
    $attached=ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$next,$nextProof,['block_id'=>$block])),'A07 report attached after block');
    $case=(int)sql_l01('SELECT case_id FROM report_incidents WHERE receipt_id=?',[$attached['receipt_id']])->fetchColumn();
    ok_l01(http_l01('/api/messages.php?match_id='.$pair,$a),'A07 report does not restore chat',403);
    ok_l01(http_l01('/api/safety.php',$a,['action'=>'unblock','user_id'=>$B]),'A41 creator cannot bypass clearance',409);
    $clear=['action'=>'clear_block_report','case_id'=>$case,'block_id'=>$block];
    ok_l01(http_l01('/api/safety.php',$n,$clear),'A41 unassigned admin cannot clear',403);
    sql_l01('INSERT INTO moderation_case_assignments (case_id,assigned_user_id,assigned_by) VALUES (?, ?, ?)',[$case,$M,$N]);
    ok_l01(http_l01('/api/safety.php',$mNo,$clear),'A10/A41 MFA required',403);
    $clear['review_version']=ok_l01(http_l01('/api/safety.php',$m,['action'=>'block_report_review','case_id'=>$case,'block_id'=>$block]),'A41 assigned reviewer current scope')['review_version'];
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$next,$nextProof,['block_id'=>$block,'explanation'=>'Synthetic new information after review opened'])),'A41 new information after review');
    ok_l01(http_l01('/api/safety.php',$m,$clear),'A41 stale clearance cannot approve unseen attachment',409);
    ok_l01(http_l01('/api/safety.php',$a,['action'=>'unblock','user_id'=>$B]),'A41 new attachment remains uncleared',409);
    $clear['review_version']=ok_l01(http_l01('/api/safety.php',$m,['action'=>'block_report_review','case_id'=>$case,'block_id'=>$block]),'A41 refreshed reviewer scope')['review_version'];
    ok_l01(http_l01('/api/safety.php',$m,$clear),'A41 assigned MFA reviewer clearance');
    check_l01(count_l01('blocks','id=?',[$block])===1,'A41 clearance alone keeps personal block');
    ok_l01(http_l01('/api/safety.php',$b,['action'=>'unblock','user_id'=>$A]),'A41 other participant cannot remove creator block');
    check_l01(count_l01('blocks','id=?',[$block])===1,'A41 wrong creator changes no block');
    ok_l01(http_l01('/api/safety.php',$b,['action'=>'block','user_id'=>$A]),'A41 reverse block is independent');
    ok_l01(http_l01('/api/safety.php',$a,['action'=>'unblock','user_id'=>$B]),'A41 creator explicitly removes cleared block');
    check_l01(count_l01('blocks','id=?',[$block])===0,'A41 eligible creator removal');
    check_l01(count_l01('blocks','blocker_id=? AND blocked_id=?',[$B,$A])===1,'A41 another personal block remains');
    ok_l01(http_l01('/api/messages.php?match_id='.$pair,$a),'A41 reverse block still denies chat',403);
    ok_l01(http_l01('/api/safety.php',$b,['action'=>'unblock','user_id'=>$A]),'A41 reverse creator removes own ordinary block');
    $combined=report_l01('message',$msg,$proof,['action'=>'block_and_report']);
    $comboReceipt=ok_l01(http_l01('/api/safety.php',$a,$combined),'A34 combined existing incident');
    $comboBlock=(int)sql_l01('SELECT id FROM blocks WHERE blocker_id=? AND blocked_id=?',[$A,$B])->fetchColumn();
    $comboCase=(int)sql_l01('SELECT case_id FROM report_incidents WHERE receipt_id=?',[$comboReceipt['receipt_id']])->fetchColumn();
    sql_l01('INSERT INTO moderation_case_assignments (case_id,assigned_user_id,assigned_by) VALUES (?, ?, ?)',[$comboCase,$M,$N]);
    $comboVersion=ok_l01(http_l01('/api/safety.php',$m,['action'=>'block_report_review','case_id'=>$comboCase,'block_id'=>$comboBlock]),'A34 combo review scope')['review_version'];
    ok_l01(http_l01('/api/safety.php',$m,['action'=>'clear_block_report','case_id'=>$comboCase,'block_id'=>$comboBlock,'review_version'=>$comboVersion]),'A34 combo clearance');
    ok_l01(http_l01('/api/safety.php',$a,['action'=>'unblock','user_id'=>$B]),'A34 later deliberate unblock');
    check_l01(ok_l01(http_l01('/api/safety.php',$a,$combined),'A34 completed combined retry')===$comboReceipt &&
        count_l01('blocks','blocker_id=? AND blocked_id=?',[$A,$B])===0,'A34 retry never reblocks');

    // Add an already anchored target to another own case: link for review only.
    $linkCount=count_l01('report_case_links'); $periodCount=count_l01('report_probation_periods');
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',$reportFirst,$reportFirstProof,
        ['action'=>'report_addendum','receipt_id'=>$receipt['receipt_id']])),'A18/A29 already anchored evidence link');
    check_l01(count_l01('report_case_links')===$linkCount+1 && count_l01('report_probation_periods')===$periodCount &&
        count_l01('moderation_evidence','source_type="message" AND source_id=?',[$reportFirst])===1,'A18/A29 no duplicate capture or effect from case link');

    // A revoked actor cannot use an old report operation as authentication.
    sql_l01('UPDATE users SET session_version=session_version+1 WHERE id=?',[$A]);
    ok_l01(http_l01('/api/safety.php',$a,$input),'A10/A30 revoked session overrides retained receipt',401);
    sql_l01('UPDATE users SET session_version=0 WHERE id=?',[$A]); $a=session_l01($A);

    // New evidence review never inherits the old admin/reason exception.
    $reviewAuth=['user_id'=>$M,'session_version'=>0,'mfa_verified'=>true,'mfa_user_id'=>$M,'mfa_session_version'=>0];
    $unassignedAuth=['user_id'=>$N,'session_version'=>0,'mfa_verified'=>true,'mfa_user_id'=>$N,'mfa_session_version'=>0];
    check_l01(bushisa_report_review_evidence($pdo,$comboCase,$N,$unassignedAuth)===[],'A10 unassigned privileged evidence read denied');
    check_l01(count(bushisa_report_review_evidence($pdo,$comboCase,$M,$reviewAuth))>0,'A10 assigned MFA evidence read is scoped and audited');
    sql_l01('UPDATE moderation_case_assignments SET revoked_at=UTC_TIMESTAMP() WHERE case_id=? AND assigned_user_id=?',[$comboCase,$M]);
    check_l01(bushisa_report_review_evidence($pdo,$comboCase,$M,$reviewAuth)===[],'A10 removed assignment immediately denies evidence');

    // Fully committed receipts survive source erasure/rotation, never reload it.
    $rotateMsg=message_l01($privatePair,$D,'Synthetic retry after physical erasure');
    $rotateProof=proof_message_l01($privatePair,$rotateMsg,$c);
    $rotateInput=report_l01('message',$rotateMsg,$rotateProof);
    $rotateReceipt=ok_l01(http_l01('/api/safety.php',$c,$rotateInput),'A23 initial erasure-retry receipt');
    sql_l01('DELETE FROM messages WHERE id=?',[$rotateMsg]);
    check_l01(ok_l01(http_l01('/api/safety.php',$c,$rotateInput,8098),'A23 erased source and rotated active key')===$rotateReceipt,'A23 replay is receipt-only after source erasure');
    $opHash=bushisa_report_mac('operation-key',$rotateInput['idempotency_key'],bushisa_report_keys()['keys']['test-v1']);
    sql_l01('UPDATE report_operations SET contract_version=2 WHERE reporter_id=? AND key_hash=?',[$C,$opHash]);
    ok_l01(http_l01('/api/safety.php',$c,$rotateInput),'A23 unsupported stored contract cannot create fresh operation',404);
    sql_l01('UPDATE report_operations SET contract_version=1 WHERE reporter_id=? AND key_hash=?',[$C,$opHash]);

    // Receipt/capture expiry is independent of delayed physical jobs.
    $expCase=(int)sql_l01('SELECT case_id FROM report_incidents WHERE receipt_id=?',[$receipt['receipt_id']])->fetchColumn();
    sql_l01('UPDATE moderation_cases SET final_resolution_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 181 DAY) WHERE id=?',[$expCase]);
    require_once __DIR__.'/support/l03a_fixture.php';
    // Explicit synthetic anchor setup only. A raw old final_resolution_at is no
    // longer destructive authority; real notice/appeal proof remains gated.
    \Bushisa\EvidenceFixture\expiry_fixture($pdo,$expCase,(string)sql_l01('SELECT final_resolution_at FROM moderation_cases WHERE id=?',[$expCase])->fetchColumn());
    sql_l01('UPDATE moderation_case_assignments SET revoked_at=NULL WHERE case_id=? AND assigned_user_id=?',[$expCase,$M]);
    $heldEvidence=(int)sql_l01('SELECT id FROM moderation_evidence WHERE case_id=? ORDER BY id LIMIT 1',[$expCase])->fetchColumn();
    sql_l01('INSERT INTO report_evidence_holds (case_id,evidence_id,authorized_record_ref,starts_at,expires_at)
        VALUES (?, ?, "synthetic-hold-fixture-only", UTC_TIMESTAMP(6), DATE_ADD(UTC_TIMESTAMP(6),INTERVAL 1 HOUR))',[$expCase,$heldEvidence]);
    ok_l01(http_l01('/api/safety.php',$a,$input),'A30 expired receipt unavailable',404);
    $purged=cleanup_text_l01();
    check_l01($purged['intake_copies_purged']>0 &&
        count_l01('report_submissions','incident_id IN (SELECT id FROM report_incidents WHERE case_id=?) AND explanation IS NOT NULL',[$expCase])===0,'A30 intake copies physically purged at final-resolution bound');
    check_l01($purged['text_evidence_purged']>0 &&
        count_l01('moderation_evidence','case_id=? AND evidence_excerpt IS NOT NULL',[$expCase])===1,'A36 only specifically held text survives ordinary purge');
    check_l01(bushisa_report_review_evidence($pdo,$expCase,$M,$reviewAuth)===[],'A36 hold does not restore ordinary expired evidence access');
    sql_l01('UPDATE report_evidence_holds SET released_at=UTC_TIMESTAMP(6) WHERE case_id=?',[$expCase]);
    cleanup_text_l01();
    check_l01(count_l01('moderation_evidence','case_id=? AND evidence_excerpt IS NOT NULL',[$expCase])===0,'A36 released already-expired evidence purged without a new clock');
    $beforeExpiry=sql_l01('SELECT expires_at FROM report_incidents WHERE case_id=? LIMIT 1',[$expCase])->fetchColumn();
    cleanup_text_l01();
    check_l01(sql_l01('SELECT expires_at FROM report_incidents WHERE case_id=? LIMIT 1',[$expCase])->fetchColumn()===$beforeExpiry,'A29/A30 cleanup does not renew expiry');

    $accidental=(int)sql_l01('SELECT case_id FROM report_incidents WHERE receipt_id=?',[$qr['receipt_id']])->fetchColumn();
    sql_l01('UPDATE moderation_cases SET status="dismissed", report_resolution_class="accidental", final_resolution_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 31 DAY) WHERE id=?',[$accidental]);
    \Bushisa\EvidenceFixture\expiry_fixture($pdo,$accidental,(string)sql_l01('SELECT final_resolution_at FROM moderation_cases WHERE id=?',[$accidental])->fetchColumn(),'accidental');
    cleanup_text_l01();
    check_l01(count_l01('moderation_evidence','case_id=? AND evidence_excerpt IS NOT NULL',[$accidental])===0,'A30 final accidental case uses 30-day evidence deadline');

    check_l01(count_l01('mail_outbox')===0,'No real or synthetic mail queued');
    $result='PASS';
} catch (Throwable $e) {
    $result='FAIL'; $failure=$e->getMessage(); fwrite(STDERR,'FAIL: ' . $failure . PHP_EOL);
} finally {
    foreach ($servers as $server) { if (is_resource($server)) { proc_terminate($server); proc_close($server); } }
    $evidence=['suite'=>'L01b guarded synthetic integration','result'=>$result??'FAIL','started_utc'=>$started,'finished_utc'=>gmdate('c'),
        'php'=>PHP_VERSION,'os'=>PHP_OS_FAMILY,'mysql'=>isset($pdo)&&$pdo instanceof PDO?$pdo->getAttribute(PDO::ATTR_SERVER_VERSION):null,
        'database'=>$settings['name'],'host'=>$settings['host'],'port'=>$settings['port'],'http_topology'=>'Two separate loopback PHP built-in server processes, ports 8097/8098',
        'checks'=>$checks,'overlaps'=>$overlaps,'failure'=>$failure??null];
    $manifestPaths=['api/safety.php','api/confessions.php','api/discover.php','api/messages.php','api/message_requests.php',
        'bin/migrate.php','bin/cleanup_policy.php','database/migrations/006_report_intake.sql','php/report_intake.php',
        'php/report_migration.php','php/block_handler.php','php/report_handler.php','php/policy_safeguards.php','php/operational.php',
        'php/evidence_lifecycle.php','php/evidence_recovery.php','database/migrations/010_text_evidence_lifecycle.sql','tests/support/l03a_fixture.php',
        'js/confessions.js','tests/integration_l01b_reports.php'];
    foreach($manifestPaths as $path) {
        $evidence['sources_lf_sha256'][$path]=hash('sha256',str_replace("\r\n","\n",file_get_contents($root.'/'.$path)));
    }
    file_put_contents($runDir.'/results.json',json_encode($evidence,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL);
    fwrite(STDOUT,json_encode(['result'=>$result??'FAIL','checks'=>count($checks),'overlaps'=>count($overlaps),'evidence'=>$runDir.'/results.json']) . PHP_EOL);
}
exit(($result??'FAIL')==='PASS'?0:1);
