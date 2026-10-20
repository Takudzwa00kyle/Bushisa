<?php
declare(strict_types=1);
namespace Bushisa\ImportRaceFixture;
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/synthetic_import_fixture.php';
use PDO;
use stdClass;
use function Bushisa\LegacyHistory\{clock,decode,encode,fact_id};
use function Bushisa\SyntheticImport\{query,sql_time};
use function Bushisa\SyntheticImport\Fixture\{bind,save,seed_pair};

/** Root is fixture/migration/monitor only; neither HTTP nor importer uses it. */
function web_user(PDO $root,string $database): array
{
    $user='l02e_http_'.bin2hex(random_bytes(5));$password=bin2hex(random_bytes(24));
    $host=(string)$root->query("SELECT SUBSTRING_INDEX(USER(),'@',-1)")->fetchColumn();
    if(!preg_match('/\A[a-zA-Z0-9_.:-]{1,255}\z/D',$host))throw new \RuntimeException('Unsafe test account host');
    $account="'$user'@'$host'";$db=\bushisa_quote_disposable_identifier($database);
    query($root,"CREATE USER $account IDENTIFIED BY ?",[$password]);$grants=[];
    foreach($root->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table){
        if(str_starts_with($table,'synthetic_import_'))continue;
        $root->exec("GRANT SELECT ON $db.".\bushisa_quote_disposable_identifier($table)." TO $account");$grants[$table]=['SELECT'];
    }
    $write=[
        'INSERT,UPDATE'=>['pair_policy_history','pair_request_counts','pair_request_events','pair_chat_segments','message_requests','pair_decline_restrictions','matches','notifications','report_block_links'],
        'INSERT'=>['pair_policy_operations','message_request_sends','blocks','moderation_cases','moderation_evidence','report_incidents','report_submissions','report_operations','report_probation_periods','report_case_links','report_action_audit','protective_replay_events','cleanup_outbox','audit_log','evidence_case_clocks','evidence_lifecycle_alerts','evidence_lifecycle_items'],
        'UPDATE'=>['users','profiles','consent_records','confessions','confession_references','student_email_approval_codes','mail_outbox','messages'],
        'DELETE'=>['swipes','notifications','blocks','preferences','find_match_answers','profile_images'],
    ];
    foreach($write as $privileges=>$tables)foreach($tables as $table){
        $root->exec("GRANT $privileges ON $db.".\bushisa_quote_disposable_identifier($table)." TO $account");$grants[$table]=array_values(array_unique(array_merge($grants[$table],explode(',',$privileges))));
    }
    // Required by the fixed ON DUPLICATE KEY clause in bushisa_enqueue_cleanup;
    // no table-wide UPDATE, cleanup execution, deletion or schema permission.
    $root->exec("GRANT UPDATE (payload_json,status,run_after,last_error) ON $db.cleanup_outbox TO $account");
    $grants['cleanup_outbox'][]='UPDATE(payload_json,status,run_after,last_error)';
    // Current L06 account-deletion cancellation statements. These are HTTP
    // fixture grants only; importer grants and production configuration stay
    // untouched. No issuance, password change, purge, DDL or authority grants.
    foreach([
        'password_reset_challenges'=>['invalidated_at','token_hash','secret_purge_due_at','version'],
        'password_reset_mail_jobs'=>['cancel_requested_at','encrypted_payload','payload_purge_due_at','terminal_at','reason_code','account_id','account_generation','recipient_hash','challenge_id','state','next_attempt_at','version'],
    ] as $table=>$columns){
        $root->exec('GRANT UPDATE (`'.implode('`,`',$columns).'`) ON '.$db.'.'.$table.' TO '.$account);
        $grants[$table][]='UPDATE('.implode(',',$columns).')';
    }
    $root->exec('GRANT DELETE ON '.$db.'.password_reset_state TO '.$account);$grants['password_reset_state'][]='DELETE';
    $root->exec("GRANT LOCK TABLES ON $db.* TO $account");
    $root->exec("GRANT EXECUTE ON PROCEDURE $db.bushisa_pair_trigger_health TO $account");
    $grants['routine:bushisa_pair_trigger_health']=['EXECUTE'];
    return ['user'=>$user,'password'=>$password,'account'=>$account,'grants'=>$grants,'lock_tables_database_only'=>true];
}

function moderator(PDO $root,string $tag): int
{
    query($root,'INSERT INTO users (nust_student_id,email,password_hash,role,is_verified,is_active,account_status,session_version) VALUES (?,?,?,"moderator",1,1,"active",3)',
        ['L02eMod'.$tag,'moderator'.$tag.'@example.invalid',password_hash('Synthet1c!Test',PASSWORD_DEFAULT)]);
    return (int)$root->lastInsertId();
}

function scenario(PDO $root,string $run,stdClass $permit,string $tag,string $kind): array
{
    $blocked=in_array($kind,['review','report'],true);$f=seed_pair($root,$run,$permit,$tag,!$blocked);
    if(in_array($kind,['cancel','end','unmatch','block','delete'],true)){
        $e=$f['s']->native_events[0];
        query($root,'INSERT INTO message_requests (match_id,requester_id,recipient_id,status,requested_at,expires_at,generation_key) VALUES (?,?,?,"pending",?,?,"synthetic")',
            [$f['match'],$f['low'],$f['high'],sql_time(clock($e->time)),sql_time(clock($e->expires_at))]);
        $request=(int)$root->lastInsertId();query($root,'UPDATE pair_request_events SET request_id=? WHERE match_id=?',[$request,$f['match']]);
        $f['s']->state->current_request=(object)['event_ref'=>fact_id($e),'status'=>'pending','expires_at'=>decode(encode($e->expires_at))];
        if($kind!=='cancel'){
            $approval=decode(encode($e));$approval->identity->native_key='syn:approval-'.$tag;$approval->type='request_approved';$approval->actor=$f['b']->accounts[1]->source_ref;
            $approval->recipient=$f['b']->accounts[0]->source_ref;$approval->position=122;$approval->request_ref=fact_id($e);$approval->expires_at=null;
            $approval->time=\Bushisa\LegacyHistory\Fixture\time((new \DateTimeImmutable(clock($e->time)))->modify('+1 hour')->format('Y-m-d\TH:i:s.u\Z'));
            $f['b']->events[]=$approval;$f['s']->native_events[]=decode(encode($approval));
            $f['b']->observations[]=(object)['request_ref'=>fact_id($e),'position'=>122,'status'=>'approved','source_ref'=>$e->source_ref];
            $f['s']->state->current_request->status='approved';$f['s']->state->chat->grant=true;$f['s']->state->chat->suppressed=false;
            query($root,'UPDATE message_requests SET status="approved",responded_at=? WHERE id=?',[sql_time(clock($approval->time)),$request]);
            query($root,'UPDATE pair_request_events SET status="approved",purge_due_at=NULL WHERE match_id=?',[$f['match']]);
            $segment=hash('sha256','l02e-active-'.$tag);
            query($root,'INSERT INTO pair_chat_segments (segment_key,match_id,opened_at,grant_source,request_event_key) VALUES (?,?,?,"legacy_observed",?)',[$segment,$f['match'],sql_time(clock($approval->time)),fact_id($e)]);
            query($root,'UPDATE pair_policy_history SET chat_suppressed=0 WHERE match_id=?',[$f['match']]);
            query($root,'INSERT INTO messages (match_id,sender_id,body,sent_at,report_segment_key) VALUES (?,?,"Synthetic existing approved text",?,?)',[$f['match'],$f['low'],sql_time(clock($approval->time)),$segment]);
        }
    }
    if($kind==='review'){
        $f['reviewer']=moderator($root,$tag);$f['block']=(int)query($root,'SELECT id FROM blocks WHERE blocker_id=? AND blocked_id=?',[$f['low'],$f['high']])->fetchColumn();
        $f['case']=(int)query($root,'SELECT case_id FROM report_block_links WHERE block_id=?',[$f['block']])->fetchColumn();
        query($root,'INSERT INTO moderation_case_assignments (case_id,assigned_user_id,assigned_by,assigned_at) VALUES (?,?,?,UTC_TIMESTAMP(6))',[$f['case'],$f['reviewer'],$f['reviewer']]);
    }
    if($kind==='new_request'){
        // A separate synthetic post-epoch original pair shares the sender's
        // global allowance with the imported legacy pair. Only this new pair
        // has positive native genesis; the import target stays incomplete.
        query($root,'INSERT INTO users (nust_student_id,email,password_hash,is_verified,is_active,account_status,session_version) VALUES (?,?,?,1,1,"active",3)',
            ['L02eBuddy'.$tag,'buddy'.$tag.'@example.invalid',password_hash('Synthet1c!Test',PASSWORD_DEFAULT)]);$buddy=(int)$root->lastInsertId();
        query($root,'INSERT INTO profiles (user_id,display_name,anon_handle,gender,date_of_birth) VALUES (?,?,?,"male","2000-01-01")',[$buddy,'Synthetic buddy','buddy_'.$tag]);
        query($root,'INSERT INTO preferences (user_id,preferred_gender) VALUES (?,"female")',[$buddy]);
        query($root,'INSERT INTO matches (user_a_id,user_b_id,match_status,matched_at) VALUES (?, ?,"active",UTC_TIMESTAMP(6))',[$f['low'],$buddy]);$f['native_match']=(int)$root->lastInsertId();
        query($root,'INSERT INTO pair_policy_history (user_low,user_high,match_id,history_state,genesis_account_id,version,generation,created_at) VALUES (?,?,?,"complete_from_genesis",?,0,1,UTC_TIMESTAMP(6))',[$f['low'],$buddy,$f['native_match'],$buddy]);
        query($root,'INSERT INTO pair_request_counts (requester_id,recipient_id,request_count) VALUES (?,?,0),(?,?,0)',[$f['low'],$buddy,$buddy,$f['low']]);$f['buddy']=$buddy;
    }
    bind($root,$f);save($root,$f);return $f;
}
