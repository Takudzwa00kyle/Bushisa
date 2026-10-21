<?php
declare(strict_types=1);
namespace Bushisa\EvidenceFixture;
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/disposable_database.php';
require_once __DIR__.'/../../php/evidence_recovery.php';
use PDO;
use function Bushisa\Evidence\{add,identity,query,now,transaction,register_case};

function connect(?array $credentials=null): PDO
{
    $s=\bushisa_require_disposable_test_database('L03a synthetic fixture');
    \Bushisa\Evidence\synthetic_guard();
    $p=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$s['host'],$s['port'],$s['name']),$credentials['user']??$s['user'],$credentials['password']??$s['password'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_STRINGIFY_FETCHES=>true]);
    $p->exec("SET time_zone='+00:00'"); return $p;
}
function install(PDO $root): void
{
    $root->exec('CREATE TABLE l03a_fixture_clock (singleton INT PRIMARY KEY,clock_at DATETIME(6) NOT NULL) ENGINE=InnoDB');
    $root->exec('INSERT INTO l03a_fixture_clock VALUES (1,"2026-01-01 00:00:00.000000")');
    $root->exec('CREATE TABLE l03a_fixture_authority (proof_hash CHAR(64) PRIMARY KEY,actor_id INT NOT NULL,case_id BIGINT NOT NULL,bound_version INT NOT NULL,
        capabilities_json TEXT NOT NULL,expires_at DATETIME(6) NOT NULL,revoked_at DATETIME(6) NULL) ENGINE=InnoDB');
    if (\operational_table_exists($root,'evidence_recovery_state')) {
        query($root,'INSERT INTO evidence_recovery_state (singleton,journal_namespace,ready) VALUES (1,?,0)',[(string)getenv('BUSHISA_L03A_RECOVERY_NAMESPACE')]);
    }
}
function clock(PDO $root,string $time): void { query($root,'UPDATE l03a_fixture_clock SET clock_at=? WHERE singleton=1',[$time]); }
function advance(PDO $root,string $interval): void { clock($root,add(now($root),$interval)); }
function user(PDO $root,string $tag,string $role='user'): int
{
    query($root,'INSERT INTO users (nust_student_id,email,password_hash,role,is_verified,is_active,account_status,session_version) VALUES (?,?,?, ?,1,1,"active",3)',
        ['L03a'.$tag,'l03a'.$tag.'@example.invalid',password_hash('Synthet1c!Fixture',PASSWORD_DEFAULT),$role]);
    return (int)$root->lastInsertId();
}
function authority(PDO $root,int $case,int $actor,array $caps): array
{
    $secret=bin2hex(random_bytes(32));
    query($root,'INSERT INTO l03a_fixture_authority (proof_hash,actor_id,case_id,bound_version,capabilities_json,expires_at) VALUES (?,?,?,3,?,"2099-01-01 00:00:00.000000")',
        [identity($secret),$actor,$case,json_encode($caps,JSON_THROW_ON_ERROR)]);
    query($root,'INSERT IGNORE INTO moderation_case_assignments (case_id,assigned_user_id,assigned_by) VALUES (?,?,?)',[$case,$actor,$actor]);
    return ['user_id'=>$actor,'session_version'=>3,'evidence_authority_assertion'=>$secret];
}
function scenario(PDO $root,string $tag): array
{
    $a=user($root,$tag.'a'); $b=user($root,$tag.'b'); $reviewer=user($root,$tag.'r','moderator'); $lead=user($root,$tag.'l','admin'); $privacy=user($root,$tag.'p','moderator');
    query($root,'INSERT INTO matches (user_a_id,user_b_id,matched_at) VALUES (?,?,UTC_TIMESTAMP())',[$a,$b]); $match=(int)$root->lastInsertId();
    query($root,'INSERT INTO messages (match_id,sender_id,body,report_segment_key) VALUES (?, ?,"Synthetic ordinary source NOT an evidence copy",?)',[$match,$b,identity('segment',$tag)]); $message=(int)$root->lastInsertId();
    query($root,'INSERT INTO moderation_cases (reporter_id,target_type,target_id,reported_user_id,reason,intake_contract_version,created_at) VALUES (?,"message",?,?,"Synthetic allegation",1,?)',[$a,$message,$b,now($root)]); $case=(int)$root->lastInsertId();
    $receipt=bin2hex(random_bytes(32));
    query($root,'INSERT INTO report_incidents (reporter_id,anchor_hash,receipt_id,case_id,key_id,created_at) VALUES (?,?,?,?,"synthetic",?)',[$a,identity('incident',$tag),$receipt,$case,now($root)]); $incident=(int)$root->lastInsertId();
    query($root,'INSERT INTO report_submissions (submission_ref,incident_id,reporter_id,reported_actor_id,information_hash,reason_code,explanation,is_new_report,received_at) VALUES (?,?,?,?,?,"harassment","Synthetic private explanation",1,?)',
        [bin2hex(random_bytes(32)),$incident,$a,$b,identity('info',$tag),now($root)]); $submission=(int)$root->lastInsertId();
    query($root,'INSERT INTO report_operations (reporter_id,key_hash,key_id,action_name,contract_version,intent_hash,submission_id,created_at) VALUES (?, ?,"synthetic","report",1,?,?,?)',[$a,identity('op',$tag),identity('intent',$tag),$submission,now($root)]);
    $evidence=[];
    for($n=1;$n<=2;$n++) {
        $text='Synthetic evidence '.$tag.' '.$n;
        query($root,'INSERT INTO moderation_evidence (case_id,source_type,source_id,subject_user_id,evidence_excerpt,evidence_hash,captured_at,report_source_version) VALUES (?,"message",?,?,?,?,?,?)',
            [$case,$message+$n,$b,$text,hash('sha256',$text),now($root),identity('revision',$tag,(string)$n)]);
        $evidence[]=(int)$root->lastInsertId();
    }
    transaction($root,fn()=>register_case($root,$case));
    $auth=[
        'reviewer'=>authority($root,$case,$reviewer,['assigned_review','effective_notice','appeal_intake']),
        'lead'=>authority($root,$case,$lead,['assigned_review','safety_lead']),
        'privacy'=>authority($root,$case,$privacy,['assigned_review','privacy_approve','appeal_review']),
    ];
    return compact('a','b','case','match','message','receipt','incident','submission','evidence','reviewer','lead','privacy','auth');
}
function item(PDO $db,array $f,int $which=0): string
{ return (string)query($db,'SELECT item_key FROM evidence_lifecycle_items WHERE item_kind="evidence" AND source_id=?',[$f['evidence'][$which]])->fetchColumn(); }
function worker_user(PDO $root,string $database): array
{
    $user='l03a_exec_'.bin2hex(random_bytes(4)); $password=bin2hex(random_bytes(24));
    $host=(string)$root->query("SELECT SUBSTRING_INDEX(USER(),'@',-1)")->fetchColumn();
    if (!preg_match('/\A[a-zA-Z0-9_.:-]{1,255}\z/D',$host)) { throw new \RuntimeException('Unsafe synthetic client host'); }
    $account="'$user'@'$host'"; $db=\bushisa_quote_disposable_identifier($database); query($root,"CREATE USER $account IDENTIFIED BY ?",[$password]);
    $root->exec("GRANT SELECT ON $db.* TO $account");
    $tables=['evidence_case_clocks','evidence_lifecycle_items','evidence_lifecycle_holds','evidence_lifecycle_operations','evidence_lifecycle_events',
        'evidence_purge_obligations','evidence_lifecycle_alerts','evidence_recovery_outbox','evidence_recovery_state','evidence_recovery_overrides','evidence_cleanup_cursor'];
    foreach($tables as $table) { $root->exec("GRANT INSERT,UPDATE ON $db.$table TO $account"); }
    foreach(['moderation_cases','moderation_evidence','report_submissions','report_operations','report_incidents'] as $table) { $root->exec("GRANT UPDATE ON $db.$table TO $account"); }
    return compact('user','password','account','tables');
}

/** Compatibility regression ONLY: an explicitly labelled root-created fixture.
 * This is not a trusted historical ingestion API or operational notice proof. */
function expiry_fixture(PDO $db,int $case,string $final,string $classification='ordinary'): void
{
    \bushisa_require_disposable_test_database('L03a regression anchor fixture');
    $notice=add($final,'-14 days'); $due=add($final,$classification==='ordinary'?'+180 days':'+30 days');
    query($db,'INSERT INTO evidence_case_clocks (case_id,domain,state,notice_at,notice_event,resolution_class,appeal_until,final_at,content_expires_at,decision_expires_at,observed_at)
        VALUES (?,"synth_fixture","final",?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE domain="synth_fixture",state="final",notice_at=?,notice_event=?,resolution_class=?,appeal_until=?,final_at=?,content_expires_at=?,decision_expires_at=?',
        [$case,$notice,identity('synthetic-notice',(string)$case),$classification,$final,$final,$due,add($final,'+365 days'),\bushisa_report_now($db),
            $notice,identity('synthetic-notice',(string)$case),$classification,$final,$final,$due,add($final,'+365 days')]);
    transaction($db,fn()=>register_case($db,$case));
}
