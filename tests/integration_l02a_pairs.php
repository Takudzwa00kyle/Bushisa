<?php
declare(strict_types=1);

// CLI only: never expose fixture/session creation through the web server.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/support/disposable_database.php';
$settings = bushisa_require_disposable_test_database('integration_l02a_reports');
if (!in_array($settings['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new RuntimeException('L02a runner is local-only');
}
putenv('MAIL_TRANSPORT=fake');
putenv('MAIL_PROVIDER=fake');
putenv('BUSHISA_TEST_HTTP_OVERLAP=true');
putenv('REPORT_CONTEXT_KEYS=' . json_encode(['test-v1' => base64_encode(random_bytes(32)), 'test-v2' => base64_encode(random_bytes(32))]));
putenv('REPORT_CONTEXT_ACTIVE_KEY=test-v1');
$root = dirname(__DIR__);
$runDir = $root . '/.tmp/l02a-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3));
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
function http_l01(string $path, array $auth, ?array $payload = null, int $port = 8107): array {
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
    $id = 'l02a-' . bin2hex(random_bytes(12));
    $headers = ['X-Bushisa-Test-Barrier: ' . $id,'X-Bushisa-Test-Barrier-Point: ' . $point,'X-Bushisa-Test-Barrier-Expected: 2','X-Bushisa-Test-Barrier-Timeout-Ms: 10000'];
    $multi = curl_multi_init(); $handles = [];
    foreach ([[$a,8107],[$b,8108]] as [$request,$port]) {
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

function prepare_l02(int $match, array $auth): array {
    return ok_l01(http_l01('/api/pairs.php?match_id='.$match,$auth),'L02 own pair state');
}
function intent_l02(int $match,array $auth,string $action,array $extra=[]): array {
    return $extra+['action'=>$action,'match_id'=>$match,'pair_version'=>prepare_l02($match,$auth)['pair_version'],'idempotency_key'=>bin2hex(random_bytes(32))];
}
function request_l02(int $match,array $auth,string $action,int $code=200): array {
    return ok_l01(http_l01('/api/message_requests.php',$auth,intent_l02($match,$auth,$action)),'L02 request '.$action,$code);
}
function relationship_l02(int $match,array $auth,string $action,int $code=200): array {
    return ok_l01(http_l01('/api/pairs.php',$auth,intent_l02($match,$auth,$action)),'L02 relationship '.$action,$code);
}
function swipe_l02(int $other,array $auth,int $code=200): array {
    $state=ok_l01(http_l01('/api/pairs.php?user_id='.$other,$auth),'L02 swipe prepare');
    return ok_l01(http_l01('/api/swipe.php',$auth,['target_id'=>$other,'direction'=>'like','pair_version'=>$state['pair_version'],'idempotency_key'=>bin2hex(random_bytes(32))]),'L02 actual mutual-like route',$code);
}
function fresh_l02(int $n): array {
    $A=user_l01($n,'female'); $B=user_l01($n+1); $a=session_l01($A); $b=session_l01($B);
    swipe_l02($B,$a); $state=swipe_l02($A,$b); $match=(int)$state['match_id'];
    check_l01(prepare_l02($match,$a)['history_state']==='complete_from_genesis','L02 new account genesis establishes complete original pair');
    return [$A,$B,$a,$b,$match];
}
function block_l02(int $match,int $other,array $auth,string $action='block',int $code=200,?array $fixed=null): array {
    $state=prepare_l02($match,$auth);
    $input=$fixed??['action'=>$action,'user_id'=>$other,'pair_version'=>$state['pair_version'],'idempotency_key'=>bin2hex(random_bytes(32)),'block_id'=>$state['own_block_id']];
    ok_l01(http_l01('/api/safety.php',$auth,$input),'L02 safety '.$action,$code); return $input;
}

try {
    bushisa_bootstrap_disposable_database($root.'/database/bushisa.sql');
    check_l01(str_contains(cli_l01([$root.'/bin/migrate.php']),'Applying 007_pair_history.sql'),'M01 fresh 001-007');
    check_l01(str_contains(cli_l01([$root.'/bin/migrate.php']),'Skipping 007_pair_history.sql'),'M02 fresh second migration run');
    require $root.'/config/db.php';
    $pre=user_l01(999);
    check_l01(count_l01('pair_account_genesis')===0,'M02 migration alone never attests all application writers');
    cli_l01([$root.'/tests/support/activate_pair_ledger.php','--writer-ref=synthetic:l02a']);
    $epoch=sql_l01('SELECT activated_at FROM pair_ledger_epoch WHERE singleton=1')->fetchColumn();
    cli_l01([$root.'/tests/support/activate_pair_ledger.php','--writer-ref=synthetic:l02a']);
    check_l01(sql_l01('SELECT activated_at FROM pair_ledger_epoch WHERE singleton=1')->fetchColumn()===$epoch && count_l01('pair_account_genesis','account_id=?',[$pre])===0,'M02 writer epoch rerun immutable, no retroactive account certification');
    $base=$settings['name']; putenv('DB_NAME='.$base.'_upgrade');
    bushisa_require_disposable_test_database('L02a upgrade'); bushisa_bootstrap_disposable_database($root.'/database/bushisa.sql');
    cli_l01([$root.'/bin/migrate.php','--through=006']); require $root.'/config/db.php';
    $oldA=user_l01(900,'female'); $oldB=user_l01(901); $legacy=pair_l01($oldA,$oldB,'approved');
    sql_l01('INSERT INTO messages (match_id,sender_id,body,sent_at) VALUES (?,?,"Retained legacy synthetic",UTC_TIMESTAMP(6))',[$legacy,$oldB]);
    $before=$pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();
    $requestEpoch=(float)sql_l01('SELECT UNIX_TIMESTAMP(requested_at) FROM message_requests WHERE match_id=?',[$legacy])->fetchColumn();
    cli_l01([$root.'/bin/migrate.php']);
    check_l01($before===$pdo->query('SELECT * FROM users ORDER BY id')->fetchAll(),'M03 upgrade preserves user values');
    check_l01(count_l01('messages','body="Retained legacy synthetic"')===1,'M04 upgrade preserves ordinary text');
    check_l01((float)sql_l01('SELECT UNIX_TIMESTAMP(requested_at) FROM message_requests WHERE match_id=?',[$legacy])->fetchColumn()===$requestEpoch,'M04 precision widening preserves the existing request instant');
    check_l01(count_l01('pair_policy_history','history_state="legacy_incomplete"')===1 && count_l01('pair_account_genesis')===0,'M05 old accounts/pairs never certified, including old verified marker');
    cli_l01([$root.'/bin/migrate.php']);
    check_l01(count_l01('pair_policy_history')===1 && count_l01('pair_account_genesis')===0,'M06 upgrade second run no duplicate or retroactive genesis');
    $pdo=null; putenv('DB_NAME='.$base); require $root.'/config/db.php';
    require_once $root.'/php/pair_messages.php'; require_once $root.'/php/block_handler.php';
    // Model unknown pre-cutover history for HTTP checks. The separate upgrade
    // database above contains actual rows created before applying migration 007.
    $L=user_l01(902,'female'); $K=user_l01(903); sql_l01('DELETE FROM pair_account_genesis WHERE account_id IN (?,?)',[$L,$K]);
    $legacy=pair_l01($L,$K,'none'); $l=session_l01($L); $k=session_l01($K);

    foreach ([8107,8108] as $port) {
        $s=@fsockopen('127.0.0.1',$port,$errno,$error,0.1); if ($s) { fclose($s); throw new RuntimeException('Refusing occupied port'); }
        $pipes=[]; $process=proc_open([PHP_BINARY,'-d','session.save_path='.$runDir.'/sessions','-S','127.0.0.1:'.$port,'-t',$root],
            [0=>['pipe','r'],1=>['file',$runDir.'/http-'.$port.'.log','a'],2=>['file',$runDir.'/http-'.$port.'.log','a']],$pipes,$root);
        if (!is_resource($process)) { throw new RuntimeException('HTTP worker start failed'); } fclose($pipes[0]); $servers[]=$process;
    }
    for ($i=0;$i<40;$i++) { $s=@fsockopen('127.0.0.1',8108,$errno,$error,0.05); if ($s) { fclose($s); break; } usleep(50000); }
    check_l01(prepare_l02($legacy,$l)['history_state']==='legacy_incomplete','L01 legacy status explicit');
    request_l02($legacy,$l,'request',503);
    check_l01(count_l01('message_request_sends','requester_id=?',[$L])===0,'L02 missing legacy history cannot spend allowance');
    sql_l01('DELETE FROM pair_policy_history WHERE match_id=?',[$legacy]);
    request_l02($legacy,$l,'request',503);
    check_l01(prepare_l02($legacy,$l)['history_state']==='legacy_incomplete','L03 missing ledger/old attestation cannot fabricate clean history');
    sql_l01('INSERT INTO matches (user_a_id,user_b_id,matched_at) VALUES (?,?,UTC_TIMESTAMP(6))',[$K,$L]); $alias=(int)$pdo->lastInsertId();
    sql_l01('INSERT INTO message_requests (match_id,requester_id,recipient_id,status,requested_at,responded_at) VALUES (?,?,?,"approved",UTC_TIMESTAMP(6),UTC_TIMESTAMP(6))',[$alias,$L,$K]);
    ok_l01(http_l01('/api/messages.php?match_id='.$alias,$l),'L09 observed approved legacy alias remains readable before a block');
    block_l02($legacy,$K,$l); block_l02($legacy,$K,$l,'unblock');
    ok_l01(http_l01('/api/messages.php?match_id='.$alias,$l),'L10 duplicate legacy match cannot bypass pair-scoped post-block history gate',403);

    [$A,$B,$a,$b,$pair]=fresh_l02(1);
    $request=intent_l02($pair,$a,'request');
    $rr=concurrent_l01(['/api/message_requests.php',$a,$request],['/api/message_requests.php',$a,$request],'pair_transition_before_transaction');
    check_l01(array_column($rr,'code')===[200,200],'C01 same-key HTTP concurrent requests share committed outcome');
    check_l01(count_l01('message_request_sends','requester_id=? AND recipient_id=?',[$A,$B])===1 && count_l01('pair_request_events','match_id=?',[$pair])===1,'C02 one send, lifetime increment and immutable identity');
    check_l01((int)sql_l01('SELECT TIMESTAMPDIFF(SECOND,requested_at,expires_at) FROM message_requests WHERE match_id=?',[$pair])->fetchColumn()===604800,'B01 original seven-day request expiry');
    $pending=ok_l01(http_l01('/api/message_requests.php?match_id='.$pair,$b),'A03 request report proof'); $pendingProof=$pending['report_context'];
    relationship_l02($pair,$a,'unmatch');
    ok_l01(http_l01('/api/safety.php',$b,report_l01('match',$pair,$pendingProof,['conduct_actor_id'=>$A])),'A03 pending request report while unmatched');
    request_l02($pair,$b,'approve',409);
    $expiry=sql_l01('SELECT expires_at FROM message_requests WHERE match_id=?',[$pair])->fetchColumn();
    relationship_l02($pair,$b,'restore');
    check_l01(sql_l01('SELECT match_status FROM matches WHERE id=?',[$pair])->fetchColumn()==='unmatched','R01 other participant cannot undo creator unmatch alone');
    relationship_l02($pair,$a,'restore');
    check_l01(sql_l01('SELECT expires_at FROM message_requests WHERE match_id=?',[$pair])->fetchColumn()===$expiry,'R02 mutual restore leaves original pending expiry');
    request_l02($pair,$b,'approve');
    $sent=ok_l01(http_l01('/api/messages.php',$b,['match_id'=>$pair,'body'=>'Before unmatch']),'A06 active send');
    $proof=proof_message_l01($pair,(int)$sent['id'],$a);
    relationship_l02($pair,$a,'unmatch');
    ok_l01(http_l01('/api/messages.php',$a,['match_id'=>$pair,'body'=>'After unmatch']),'A06 chat survives unmatch send');
    check_l01(http_l01('/chat.php?match_id='.$pair,$a)['code']===200,'A06 server-rendered chat remains available after unmatch');
    $grantCount=count_l01('pair_chat_segments','match_id=?',[$pair]);
    ok_l01(http_l01('/api/safety.php',$a,report_l01('message',(int)$sent['id'],$proof)),'A06 post-unmatch report');
    check_l01(count_l01('pair_chat_segments','match_id=?',[$pair])===$grantCount && prepare_l02($pair,$a)['ending_count']===0,'A06 no new grant or ending from unmatch/report');
    relationship_l02($pair,$b,'restore'); relationship_l02($pair,$a,'restore',429);
    check_l01(sql_l01('SELECT match_status FROM matches WHERE id=?',[$pair])->fetchColumn()==='unmatched','R03 rolling restore cap atomically leaves pair unmatched');
    // Synthetic elapsed-time boundary fixture: one full rolling day, not midnight.
    sql_l01('UPDATE pair_policy_history SET last_restored_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 24 HOUR) WHERE match_id=?',[$pair]);
    relationship_l02($pair,$a,'restore');

    $end=intent_l02($pair,$a,'revoke');
    $rr=concurrent_l01(['/api/message_requests.php',$a,$end],['/api/message_requests.php',$a,$end],'pair_transition_before_transaction');
    check_l01(array_column($rr,'code')===[200,200] && prepare_l02($pair,$a)['ending_count']===1,'C03 overlapping ending retries count once');
    request_l02($pair,$b,'request',403); request_l02($pair,$a,'request',409);
    ok_l01(http_l01('/api/messages.php?match_id='.$pair,$a),'B02 explicitly ended history read-only for seven days');
    ok_l01(http_l01('/api/messages.php',$a,['match_id'=>$pair,'body'=>'Must not send']),'B03 ending disables send',403);
    sql_l01('UPDATE pair_policy_history SET last_ended_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 12 HOUR) WHERE match_id=?',[$pair]);
    request_l02($pair,$a,'request'); request_l02($pair,$b,'approve');
    $new=ok_l01(http_l01('/api/messages.php',$b,['match_id'=>$pair,'body'=>'New session text']),'B04 resumed session send');
    check_l01(sql_l01('SELECT report_segment_key FROM messages WHERE id=?',[$sent['id']])->fetchColumn()!==sql_l01('SELECT report_segment_key FROM messages WHERE id=?',[$new['id']])->fetchColumn(),'A27 old/new sessions have distinct immutable segments');
    $oldDue=sql_l01('SELECT evidence_purge_due_at FROM messages WHERE id=?',[$sent['id']])->fetchColumn();
    check_l01($oldDue!==null && sql_l01('SELECT evidence_purge_due_at FROM messages WHERE id=?',[$new['id']])->fetchColumn()===null,'B05 restart does not renew old history or schedule new messages for old cleanup');
    ok_l01(http_l01('/api/safety.php',$b,report_l01('match',$pair,$pendingProof,['conduct_actor_id'=>$A])),'A03 overwritten old request proof remains attributable');
    $historical=sql_l01('SELECT event_key FROM pair_request_events WHERE match_id=? ORDER BY requested_at LIMIT 1',[$pair])->fetchColumn();
    $oldEvent=ok_l01(http_l01('/api/message_requests.php?match_id='.$pair.'&event_key='.$historical,$b),'A03 retained event selector');
    check_l01(is_string($oldEvent['report_context']),'A03 own selected event proof returned without restoring relationship');
    request_l02($pair,$b,'revoke');
    check_l01(prepare_l02($pair,$a)['ending_count']===2,'B06 second explicit ending records original-pair cutoff');
    block_l02($pair,$B,$a); block_l02($pair,$B,$a,'unblock');
    ok_l01(http_l01('/api/messages.php',$a,['match_id'=>$pair,'body'=>'Cutoff bypass']),'B07 unblock cannot bypass ending cutoff',403);
    swipe_l02($B,$a); swipe_l02($A,$b);
    request_l02($pair,$b,'request',403);
    check_l01(prepare_l02($pair,$a)['ending_count']===2,'B08 genuine rematch never resets cutoff');

    [$C,$D,$c,$d,$p2]=fresh_l02(10);
    $block=block_l02($p2,$D,$c); block_l02($p2,$D,$c,'unblock');
    ok_l01(http_l01('/api/messages.php',$c,['match_id'=>$p2,'body'=>'Never-approved eligible unblock grant']),'A34 immediate chat on eligible unblock without prior approval');
    check_l01(count_l01('message_request_sends','(requester_id=? AND recipient_id=?) OR (requester_id=? AND recipient_id=?)',[$C,$D,$D,$C])===0 && prepare_l02($p2,$c)['ending_count']===0,'B09 block cycles consume neither request nor explicit ending');
    block_l02($p2,$D,$c); $unblockState=prepare_l02($p2,$c);
    $unblock=['action'=>'unblock','user_id'=>$D,'pair_version'=>$unblockState['pair_version'],'idempotency_key'=>bin2hex(random_bytes(32)),'block_id'=>$unblockState['own_block_id']];
    block_l02($p2,$D,$c,'unblock',200,$unblock); block_l02($p2,$D,$c);
    block_l02($p2,$D,$c,'unblock',200,$unblock);
    check_l01(count_l01('blocks','blocker_id=? AND blocked_id=?',[$C,$D])===1,'C04 committed old unblock retry cannot remove a later block');
    $stale=$unblock; $stale['idempotency_key']=bin2hex(random_bytes(32)); block_l02($p2,$D,$c,'unblock',409,$stale);
    block_l02($p2,$D,$c,'unblock');

    [$E,$F,$e,$f,$p3]=fresh_l02(20);
    request_l02($p3,$e,'request'); request_l02($p3,$f,'decline');
    relationship_l02($p3,$e,'unmatch'); relationship_l02($p3,$f,'restore'); relationship_l02($p3,$e,'restore');
    request_l02($p3,$e,'request',409);
    relationship_l02($p3,$e,'unmatch');
    $gen=prepare_l02($p3,$e)['generation']; swipe_l02($F,$e); swipe_l02($E,$f);
    check_l01(prepare_l02($p3,$e)['generation']===$gen+1,'R04 fresh mutual likes advance generation, undo did not');
    request_l02($p3,$e,'request'); request_l02($p3,$e,'cancel'); request_l02($p3,$e,'request',409);
    sql_l01('UPDATE pair_request_counts SET retry_not_before=UTC_TIMESTAMP(6) WHERE requester_id=? AND recipient_id=?',[$E,$F]);
    request_l02($p3,$e,'request'); request_l02($p3,$f,'decline');
    relationship_l02($p3,$e,'unmatch'); swipe_l02($F,$e); swipe_l02($E,$f);
    request_l02($p3,$e,'request',429);
    check_l01((int)sql_l01('SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',[$E,$F])->fetchColumn()===3,'B10 three lifetime directional requests survive undo/rematches');
    request_l02($p3,$f,'request');
    check_l01((int)sql_l01('SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',[$F,$E])->fetchColumn()===1,'B11 reverse direction has its separate approved budget');

    // Incoming ordinary block retains the reporter's prior proof, never access.
    [$G,$H,$g,$h,$p4]=fresh_l02(30); request_l02($p4,$g,'request'); request_l02($p4,$h,'approve');
    $item=ok_l01(http_l01('/api/messages.php',$h,['match_id'=>$p4,'body'=>'Pre-block synthetic']),'A07 source');
    $prior=proof_message_l01($p4,(int)$item['id'],$g);
    block_l02($p4,$G,$h);
    sql_l01('UPDATE messages SET body="Unseen later private revision" WHERE id=?',[$item['id']]);
    $reported=ok_l01(http_l01('/api/safety.php',$g,report_l01('message',(int)$item['id'],$prior)),'A07 report after incoming block');
    $cid=(int)sql_l01('SELECT case_id FROM report_incidents WHERE receipt_id=?',[$reported['receipt_id']])->fetchColumn();
    check_l01(count_l01('moderation_evidence','case_id=? AND evidence_excerpt IS NOT NULL',[$cid])===0,'A07 no unseen post-block version capture');
    ok_l01(http_l01('/api/messages.php?match_id='.$p4,$g),'A07 incoming block remains effective',403);
    $list=ok_l01(http_l01('/api/matches.php',$g),'L04 protected preview list');
    check_l01(array_filter($list,fn($row)=>(int)$row['match_id']===$p4)===[],'L05 blocked profile and preview absent');
    $html=http_l01('/chat.php?match_id='.$p4,$g);
    check_l01(in_array($html['code'],[302,303],true),'L06 old chat page cannot expose blocked profile/text');
    block_l02($p4,$H,$g); block_l02($p4,$G,$h,'unblock');
    ok_l01(http_l01('/api/messages.php?match_id='.$p4,$g),'A41 own block independently remains',403);
    block_l02($p4,$H,$g,'unblock');

    // Combined pending clearance and versioned reviewer scope, using trusted
    // synthetic MFA only; this is not real MFA enrollment or named coverage.
    [$I,$J,$i,$j,$p5]=fresh_l02(40); request_l02($p5,$i,'request');
    $proof5=ok_l01(http_l01('/api/message_requests.php?match_id='.$p5,$i),'A34 pending proof')['report_context'];
    $combined=report_l01('match',$p5,$proof5,['action'=>'block_and_report','conduct_actor_id'=>$J]);
    $receipt=ok_l01(http_l01('/api/safety.php',$i,$combined),'A34 combined report');
    check_l01(sql_l01('SELECT status FROM message_requests WHERE match_id=?',[$p5])->fetchColumn()==='revoked' && prepare_l02($p5,$i)['ending_count']===0,'A34 pending cleared atomically without explicit chat ending');
    $case=(int)sql_l01('SELECT case_id FROM report_incidents WHERE receipt_id=?',[$receipt['receipt_id']])->fetchColumn();
    $blockId=prepare_l02($p5,$i)['own_block_id'];
    block_l02($p5,$J,$i,'unblock',409);
    $M=user_l01(49,'male','moderator'); $m=session_l01($M,true);
    sql_l01('INSERT INTO moderation_case_assignments (case_id,assigned_user_id) VALUES (?,?)',[$case,$M]);
    $scope=ok_l01(http_l01('/api/safety.php',$m,['action'=>'block_report_review','case_id'=>$case,'block_id'=>$blockId]),'A41 assigned review scope');
    ok_l01(http_l01('/api/safety.php',$m,['action'=>'clear_block_report','case_id'=>$case,'block_id'=>$blockId,'review_version'=>$scope['review_version']]),'A41 exact reviewer clearance');
    check_l01(count_l01('blocks','id=?',[$blockId])===1,'A41 reviewer clearance alone never unblocks');
    block_l02($p5,$J,$i,'unblock');
    ok_l01(http_l01('/api/messages.php',$i,['match_id'=>$p5,'body'=>'Cleared creator unblock']),'A34 approved immediate chat after combined review and creator action');
    $period=sql_l01('SELECT starts_at,expires_at FROM report_probation_periods WHERE actor_id=?',[$J])->fetch();
    ok_l01(http_l01('/api/safety.php',$i,$combined),'A34 committed combined retry after unblock');
    check_l01(count_l01('blocks','blocker_id=? AND blocked_id=?',[$I,$J])===0 && count_l01('pair_chat_segments','match_id=?',[$p5])===1
        && sql_l01('SELECT starts_at,expires_at FROM report_probation_periods WHERE actor_id=?',[$J])->fetch()===$period,'A34 retry neither reblocks nor duplicates grant/probation');
    block_l02($p5,$J,$i); sql_l01('UPDATE users SET is_suspended=1,account_status="suspended" WHERE id=?',[$J]);
    block_l02($p5,$J,$i,'unblock');
    ok_l01(http_l01('/api/messages.php?match_id='.$p5,$i),'A41 suspended target stays denied after creator removal',403);
    sql_l01('UPDATE users SET is_suspended=0,account_status="active" WHERE id=?',[$J]);

    // Two distinct intents with one version: one winner, no duplicate ending.
    [$Q,$R,$q,$r,$p6]=fresh_l02(50); request_l02($p6,$q,'request'); request_l02($p6,$r,'approve');
    $old6=ok_l01(http_l01('/api/messages.php',$q,['match_id'=>$p6,'body'=>'Old closed session']),'B12 closed-session source');
    $first=intent_l02($p6,$q,'revoke'); $second=intent_l02($p6,$r,'revoke');
    $rr=concurrent_l01(['/api/message_requests.php',$q,$first],['/api/message_requests.php',$r,$second],'pair_transition_before_transaction');
    $codes=array_column($rr,'code'); sort($codes);
    check_l01($codes===[200,409] && prepare_l02($p6,$q)['ending_count']===1,'C05 concurrent different ending intents cannot double-count');
    $reused=$first; $reused['action']='request';
    if ($rr[0]['code']===200) { ok_l01(http_l01('/api/message_requests.php',$q,$reused),'C06 same committed key changed intent conflicts',409); }
    else { $reused=$second; $reused['action']='request'; ok_l01(http_l01('/api/message_requests.php',$r,$reused),'C06 same committed key changed intent conflicts',409); }
    relationship_l02($p6,$q,'unmatch'); relationship_l02($p6,$r,'unmatch');
    $restQ=intent_l02($p6,$q,'restore'); $restR=intent_l02($p6,$r,'restore');
    $rr=concurrent_l01(['/api/pairs.php',$q,$restQ],['/api/pairs.php',$r,$restR],'pair_transition_before_transaction');
    $codes=array_column($rr,'code'); sort($codes); check_l01($codes===[200,409],'C07 concurrent restore agreements serialize by version');
    check_l01(sql_l01('SELECT match_status FROM matches WHERE id=?',[$p6])->fetchColumn()==='unmatched','R05 neither participant undoes the other unmatch');
    relationship_l02($p6,$rr[0]['code']===409?$q:$r,'restore');
    check_l01(sql_l01('SELECT match_status FROM matches WHERE id=?',[$p6])->fetchColumn()==='active','R06 both explicit own undos and fresh agreements complete restoration');
    block_l02($p6,$R,$q); block_l02($p6,$R,$q,'unblock');
    $new6=ok_l01(http_l01('/api/messages.php',$q,['match_id'=>$p6,'body'=>'New grant survives cleanup']),'B13 unblock after first ending creates separate grant');
    sql_l01('UPDATE messages SET evidence_purge_due_at=UTC_TIMESTAMP(6) WHERE id=?',[$old6['id']]);
    sql_l01('UPDATE pair_chat_segments SET read_until=UTC_TIMESTAMP(6) WHERE match_id=? AND ended_at IS NOT NULL',[$p6]);
    $remaining=ok_l01(http_l01('/api/messages.php?match_id='.$p6,$q),'B14 old deadline enforced before physical purge');
    check_l01(array_column($remaining,'id')===[$new6['id']],'B15 new grant never renews old history access');
    bushisa_report_transaction($pdo,fn()=>bushisa_pair_cleanup($pdo));
    check_l01(count_l01('messages','id=?',[$old6['id']])===0 && count_l01('messages','id=?',[$new6['id']])===1,'B16 actual old-session cleanup preserves later messages');

    // Proof issuance and block/unmatch share the same locked decision ordering.
    [$S,$T,$s,$t,$p7]=fresh_l02(60); request_l02($p7,$s,'request'); request_l02($p7,$t,'approve');
    $message=ok_l01(http_l01('/api/messages.php',$t,['match_id'=>$p7,'body'=>'Overlap synthetic']),'A20 source');
    $oldProof=proof_message_l01($p7,(int)$message['id'],$s);
    $state=prepare_l02($p7,$t);
    $blockIntent=['action'=>'block','user_id'=>$S,'pair_version'=>$state['pair_version'],'idempotency_key'=>bin2hex(random_bytes(32))];
    $rr=concurrent_l01(['/api/messages.php?match_id='.$p7,$s,null],['/api/safety.php',$t,$blockIntent],'pair_transition_before_transaction');
    check_l01(in_array($rr[0]['code'],[200,403],true) && $rr[1]['code']===200,'A20 proof issuance/block observes one permitted ordering');
    ok_l01(http_l01('/api/messages.php?match_id='.$p7,$s),'A20 subsequent reads denied by block',403);
    ok_l01(http_l01('/api/safety.php',$s,report_l01('message',(int)$message['id'],$oldProof)),'A20 pre-block proof filing retains no access grant');
    block_l02($p7,$S,$t,'unblock');
    $unmatch=intent_l02($p7,$s,'unmatch');
    $rr=concurrent_l01(['/api/messages.php?match_id='.$p7,$s,null],['/api/pairs.php',$s,$unmatch],'pair_transition_before_transaction');
    check_l01(array_column($rr,'code')===[200,200] && prepare_l02($p7,$s)['ending_count']===0,'A20 unmatch/proof overlap preserves approved chat without ending');
    $unmatch=intent_l02($p7,$t,'unmatch');
    $rr=concurrent_l01(['/api/safety.php',$s,report_l01('message',(int)$message['id'],$oldProof,['explanation'=>'Overlap context'])],['/api/pairs.php',$t,$unmatch],'pair_transition_before_transaction');
    check_l01(array_column($rr,'code')===[200,200] && count_l01('pair_chat_segments','match_id=?',[$p7])===1,'A20 filing/unmatch overlap never restores a relationship or duplicates grant');

    // Hold the exact shared DB advisory lock while workers rendezvous, then
    // revoke the synthetic session before either authorization can proceed.
    $lockName='bushisa-report:'.substr(hash('sha256',(string)$pdo->query('SELECT DATABASE()')->fetchColumn()),0,32);
    sql_l01('SELECT GET_LOCK(?,5)',[$lockName]);
    $rr=concurrent_l01(['/api/messages.php?match_id='.$p7,$s,null],['/api/safety.php',$s,report_l01('message',(int)$message['id'],$oldProof,['explanation'=>'Revoked session'])],
        'pair_transition_before_transaction',function($id,$multi) use($S,$lockName):void {
            $until=microtime(true)+5;
            do { curl_multi_exec($multi,$running); $files=glob(sys_get_temp_dir().'/bushisa-http-barriers/'.$id.'_pair_transition_before_transaction-*.json')?:[]; if(count($files)===2){break;} curl_multi_select($multi,0.01); } while(microtime(true)<$until);
            check_l01(count($files)===2,'A20 both actual workers wait before session revocation');
            sql_l01('UPDATE users SET session_version=session_version+1 WHERE id=?',[$S]); sql_l01('SELECT RELEASE_LOCK(?)',[$lockName]);
        });
    check_l01($rr[0]['code']===401 && $rr[1]['code']===401,'A20 locked current-session recheck denies proof issuance and filing after revocation');

    // Loss of positive provenance never turns an old marker into permission.
    [$U,$V,$u,$v,$p8]=fresh_l02(70);
    sql_l01('DELETE FROM pair_account_genesis WHERE account_id IN (?,?)',[$U,$V]);
    request_l02($p8,$u,'request',503);
    block_l02($p8,$V,$u); block_l02($p8,$V,$u,'unblock');
    ok_l01(http_l01('/api/messages.php',$u,['match_id'=>$p8,'body'=>'Unknown history bypass']),'L07 unblock cannot manufacture an unproved grant',403);

    // Event cutoff precedes physical cleanup; immutable history survives a
    // mutable-row purge, then becomes unavailable at its own terminal deadline.
    $eventKey=sql_l01('SELECT event_key FROM pair_request_events WHERE match_id=? ORDER BY requested_at LIMIT 1',[$p3])->fetchColumn();
    sql_l01('DELETE FROM message_requests WHERE match_id=?',[$p3]);
    $eventView=ok_l01(http_l01('/api/message_requests.php?match_id='.$p3.'&event_key='.$eventKey,$f),'A03 actual purged current row uses retained event');
    ok_l01(http_l01('/api/safety.php',$f,report_l01('match',$p3,$eventView['report_context'],['conduct_actor_id'=>$E])),'A27 distinct old event report');
    sql_l01('UPDATE pair_request_events SET purge_due_at=UTC_TIMESTAMP(6) WHERE event_key=?',[$eventKey]);
    ok_l01(http_l01('/api/message_requests.php?match_id='.$p3.'&event_key='.$eventKey,$f),'A17 unpurged event deadline denies proof',404);
    $beforeCount=(int)sql_l01('SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',[$E,$F])->fetchColumn();
    bushisa_report_transaction($pdo,fn()=>bushisa_pair_cleanup($pdo));
    check_l01(count_l01('pair_request_events','event_key=?',[$eventKey])===0 && (int)sql_l01('SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',[$E,$F])->fetchColumn()===$beforeCount,'L08 cleanup removes expired event but preserves lifetime ledger');

    [$W,$X,$w,$x,$p9]=fresh_l02(80); request_l02($p9,$w,'request');
    request_l02($p9,$x,'revoke',403);
    sql_l01('UPDATE message_requests SET expires_at=UTC_TIMESTAMP(6) WHERE match_id=?',[$p9]);
    request_l02($p9,$x,'approve',409); request_l02($p9,$w,'request',409);
    request_l02($p9,$x,'request');
    check_l01((int)sql_l01('SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',[$W,$X])->fetchColumn()===1,'B17 expiry does not spend a second request or extend the old pending clock');
    request_l02($p9,$x,'cancel');
    request_l02($p9,$w,'request',409);
    sql_l01('UPDATE pair_request_counts SET retry_not_before=UTC_TIMESTAMP(6) WHERE requester_id=? AND recipient_id=?',[$W,$X]);
    request_l02($p9,$w,'request'); request_l02($p9,$w,'cancel');
    sql_l01('DELETE FROM message_requests WHERE match_id=?',[$p9]);
    request_l02($p9,$w,'request',409);
    check_l01(count_l01('pair_request_counts','requester_id=? AND recipient_id=? AND retry_not_before>UTC_TIMESTAMP(6)',[$W,$X])===1,'B18 mutable-row deletion cannot erase cancellation cooldown');

    // Independent simultaneous request intents still spend once.
    [$A2,$B2,$a2,$b2,$p10]=fresh_l02(90);
    $one=intent_l02($p10,$a2,'request'); $two=intent_l02($p10,$a2,'request');
    $rr=concurrent_l01(['/api/message_requests.php',$a2,$one],['/api/message_requests.php',$a2,$two],'pair_transition_before_transaction');
    $codes=array_column($rr,'code'); sort($codes);
    check_l01($codes===[200,409] && count_l01('pair_request_events','match_id=?',[$p10])===1,'C08 different-key request overlap cannot spend twice');
    request_l02($p10,$b2,'approve');
    $it=ok_l01(http_l01('/api/messages.php',$b2,['match_id'=>$p10,'body'=>'Clearance race synthetic']),'C09 clearance race source');
    $pf=proof_message_l01($p10,(int)$it['id'],$a2);
    block_l02($p10,$B2,$a2);
    $bid=prepare_l02($p10,$a2)['own_block_id'];
    $initial=report_l01('message',(int)$it['id'],$pf,['block_id'=>$bid]);
    $rec=ok_l01(http_l01('/api/safety.php',$a2,$initial),'C09 first report attaches block');
    $case2=(int)sql_l01('SELECT case_id FROM report_incidents WHERE receipt_id=?',[$rec['receipt_id']])->fetchColumn();
    sql_l01('INSERT INTO moderation_case_assignments (case_id,assigned_user_id) VALUES (?,?)',[$case2,$M]);
    $sc=ok_l01(http_l01('/api/safety.php',$m,['action'=>'block_report_review','case_id'=>$case2,'block_id'=>$bid]),'C09 exact pre-race review scope');
    ok_l01(http_l01('/api/safety.php',$m,['action'=>'clear_block_report','case_id'=>$case2,'block_id'=>$bid,'review_version'=>$sc['review_version']]),'C09 initial clearance');
    $pv=prepare_l02($p10,$a2)['pair_version'];
    $un=['action'=>'unblock','user_id'=>$B2,'block_id'=>$bid,'pair_version'=>$pv,'idempotency_key'=>bin2hex(random_bytes(32))];
    $newReport=report_l01('message',(int)$it['id'],$pf,['block_id'=>$bid,'explanation'=>'New relevant information']);
    $rr=concurrent_l01(['/api/safety.php',$a2,$un],['/api/safety.php',$a2,$newReport],'pair_transition_before_transaction');
    check_l01(($rr[0]['code']===409 && $rr[1]['code']===200 && count_l01('blocks','id=?',[$bid])===1)
        || ($rr[0]['code']===200 && in_array($rr[1]['code'],[404,409],true) && count_l01('blocks','id=?',[$bid])===0),'A41 concurrent new attachment cannot be bypassed by stale clearance');
    check_l01(count_l01('report_block_links','block_id=? AND cleared_at IS NULL',[$bid])===($rr[1]['code']===200?1:0),'C10 block/report race is one atomic order, no orphan uncleared link');

    $oldUnmatch=intent_l02($p6,$q,'unmatch');
    ok_l01(http_l01('/api/pairs.php',$q,$oldUnmatch),'C11 original unmatch');
    $generation=prepare_l02($p6,$q)['generation'];
    $v6=prepare_l02($p6,$q)['pair_version'];
    $qLike=['target_id'=>$R,'direction'=>'like','pair_version'=>$v6,'idempotency_key'=>bin2hex(random_bytes(32))];
    $rLike=['target_id'=>$Q,'direction'=>'like','pair_version'=>$v6,'idempotency_key'=>bin2hex(random_bytes(32))];
    $rr=concurrent_l01(['/api/swipe.php',$q,$qLike],['/api/swipe.php',$r,$rLike],'pair_transition_before_transaction');
    $codes=array_column($rr,'code'); sort($codes); check_l01($codes===[200,409],'C12 fresh mutual likes serialize against one original-pair version');
    if($rr[0]['code']===409){swipe_l02($R,$q);}else{swipe_l02($Q,$r);}
    check_l01(prepare_l02($p6,$q)['generation']===$generation+1 && prepare_l02($p6,$q)['ending_count']===1,'C13 one recreated match, no reset of earlier ending');
    ok_l01(http_l01('/api/pairs.php',$q,$oldUnmatch),'C14 committed old unmatch retry');
    check_l01(sql_l01('SELECT match_status FROM matches WHERE id=?',[$p6])->fetchColumn()==='active','C15 old unmatch retry cannot revoke newly restored consent');

    [$A3,$B3,$a3,$b3,$p11]=fresh_l02(100);
    $oldRequest=intent_l02($p11,$a3,'request');
    ok_l01(http_l01('/api/message_requests.php',$a3,$oldRequest),'L11 expiry cleanup source request');
    // Synthetic elapsed-clock fixture, not a claim to have waited 38 days.
    sql_l01('UPDATE message_requests SET requested_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 38 DAY),expires_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 31 DAY) WHERE match_id=?',[$p11]);
    sql_l01('UPDATE pair_request_events SET requested_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 38 DAY),request_expires_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 31 DAY) WHERE match_id=?',[$p11]);
    sql_l01('UPDATE pair_policy_operations SET created_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 38 DAY),expires_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 8 DAY) WHERE match_id=?',[$p11]);
    ok_l01(http_l01('/api/message_requests.php',$a3,$oldRequest),'L12 expired receipt cannot reactivate even before purge',409);
    bushisa_report_transaction($pdo,fn()=>bushisa_pair_cleanup($pdo));
    check_l01(count_l01('message_requests','match_id=?',[$p11])===0 && count_l01('pair_request_events','match_id=?',[$p11])===0,'L13 cleanup terminalizes from stored expiry and erases overdue pending metadata');
    ok_l01(http_l01('/api/message_requests.php',$a3,$oldRequest),'L14 old version remains closed after receipt/event purge',409);
    request_l02($p11,$a3,'request');
    check_l01((int)sql_l01('SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',[$A3,$B3])->fetchColumn()===2,'L15 valid next request after purge continues original lifetime count');

    check_l01(count_l01('mail_outbox')===0,'No real or fake mail queued');
    $result='PASS';
} catch (Throwable $e) { $result='FAIL'; $failure=$e->getMessage(); fwrite(STDERR,'FAIL: '.$failure.PHP_EOL); }
finally {
    foreach ($servers as $server) { if (is_resource($server)) { proc_terminate($server); proc_close($server); } }
    $evidence=['suite'=>'L02a guarded synthetic pair integration','result'=>$result??'FAIL','started_utc'=>$started,'finished_utc'=>gmdate('c'),
        'php'=>PHP_VERSION,'os'=>PHP_OS_FAMILY,'mysql'=>isset($pdo)&&$pdo instanceof PDO?$pdo->getAttribute(PDO::ATTR_SERVER_VERSION):null,
        'database'=>$settings['name'],'host'=>$settings['host'],'port'=>$settings['port'],'http_topology'=>'Two separate loopback PHP workers ports 8107/8108',
        'checks'=>$checks,'overlaps'=>$overlaps,'failure'=>$failure??null];
    foreach (['api/pairs.php','api/swipe.php','api/message_requests.php','api/messages.php','api/matches.php','api/safety.php','php/pair_history.php','php/pair_messages.php','php/pair_migration.php','php/report_intake.php','php/block_handler.php','php/policy_safeguards.php','php/operational.php','php/http_test_barrier.php','php/matching/service.php','bin/migrate.php','bin/cleanup_policy.php','chat.php','matches.php','js/discover.js','database/migrations/007_pair_history.sql','tests/integration_l02a_pairs.php','tests/support/activate_pair_ledger.php','tests/integration_l01b_reports.php','tests/policy_safeguards_p0_flows.php'] as $path) {
        $evidence['sources_lf_sha256'][$path]=hash('sha256',str_replace("\r\n","\n",file_get_contents($root.'/'.$path)));
    }
    file_put_contents($runDir.'/results.json',json_encode($evidence,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);
    fwrite(STDOUT,json_encode(['result'=>$result??'FAIL','checks'=>count($checks),'overlaps'=>count($overlaps),'evidence'=>$runDir.'/results.json']).PHP_EOL);
}
exit(($result??'FAIL')==='PASS'?0:1);
