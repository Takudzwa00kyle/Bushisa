<?php
declare(strict_types=1);

// Disposable fixture profiles, not a production grant installer. The caller
// must validate its local database/server identity before calling this helper.
function rr_role(PDO $root, array $settings, string $kind): array
{
    $guard = bushisa_require_disposable_test_database('Release role fixture');
    if ($guard['name'] !== $settings['name']
        || !str_starts_with($guard['name'], 'bushisa_test_release_observed_roles_')
        || !in_array($guard['host'], ['127.0.0.1','localhost','::1'], true)
        || getenv('BUSHISA_RELEASE_ROLE_TEST') !== 'true'
        || $root->query('SELECT DATABASE()')->fetchColumn() !== $guard['name']
        || !hash_equals((string)getenv('BUSHISA_ROLE_SERVER_UUID'), (string)$root->query('SELECT @@server_uuid')->fetchColumn())) {
        throw new RuntimeException('RELEASE_ROLE_TARGET_REFUSED');
    }
    if (!in_array($kind, ['migration', 'runtime', 'worker', 'missing'], true)) {
        throw new InvalidArgumentException('Unknown fixture role');
    }
    $user = 'brr_' . $kind . '_' . bin2hex(random_bytes(4));
    $password = bin2hex(random_bytes(24));
    $account = "'$user'@'%'";
    $database = obs_id($settings['name']);
    $root->exec('CREATE USER ' . $account . ' IDENTIFIED BY ' . $root->quote($password));
    $GLOBALS['rr_accounts'][] = $account;
    if ($kind === 'migration') {
        $root->exec('GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,ALTER,INDEX,REFERENCES,TRIGGER,CREATE ROUTINE,ALTER ROUTINE,EXECUTE ON ' . $database . '.* TO ' . $account);
    } elseif ($kind === 'runtime') {
        // Implemented HTTP writers only. This is a disposable review profile,
        // not a live GRANT installer or permission to operate cleanup consumers.
        $rights = [
            'SELECT,INSERT,UPDATE' => ['profiles','preferences','matches','swipes','message_requests','messages','confessions','blocks','reports','rate_limits','notifications','notification_preferences','confession_views','confession_references','consent_records','find_match_answers','profile_images','pair_policy_history','pair_request_counts','pair_request_events','pair_chat_segments','pair_decline_restrictions','report_block_links','student_email_approval_codes','mail_outbox','signup_mail_jobs','signup_lifecycle','verification_retention','password_reset_state','password_reset_challenges','password_reset_mail_jobs','password_reset_rate_subjects'],
            'SELECT,INSERT' => ['audit_log','pair_policy_operations','message_request_sends','moderation_cases','moderation_evidence','report_incidents','report_submissions','report_operations','report_probation_periods','report_case_links','report_action_audit','protective_replay_events','evidence_case_clocks','evidence_lifecycle_items','evidence_lifecycle_alerts','signup_attempt_identities','signup_lifecycle_alerts','signup_expiry_obligations','signup_attempt_erasure_jobs','session_copy_obligations','verification_cleanup_alerts','verification_late_results','verification_recovery_obligations','password_reset_rate_events','evidence_access_audit'],
            'SELECT' => ['pair_account_genesis','pair_ledger_epoch','moderation_case_assignments','report_evidence_holds','session_copy_coverage','session_bindings'],
            'DELETE' => ['rate_limits','swipes','notifications','blocks','preferences','find_match_answers','profile_images','password_reset_state'],
        ];
        foreach ($rights as $right => $tables) {
            foreach ($tables as $table) $root->exec('GRANT ' . $right . ' ON ' . $database . '.' . obs_id($table) . ' TO ' . $account);
        }
        $root->exec('GRANT SELECT ON '.$database.'.users TO '.$account);
        $root->exec('GRANT INSERT (nust_student_id,email,password_hash,is_verified,is_suspended,is_active,account_status,session_version) ON '.$database.'.users TO '.$account);
        // No role, creation-clock or ledger-genesis UPDATE. Deletion currently
        // anonymises identity fields and suspends its own account; app checks
        // still enforce subject/transition authorization (grants are not rows).
        $root->exec('GRANT UPDATE (password_hash,email,nust_student_id,is_verified,is_suspended,is_active,account_status,session_version,deleted_at,signup_attempt_key) ON '.$database.'.users TO '.$account);
        $root->exec('GRANT SELECT,INSERT ON '.$database.'.cleanup_outbox TO '.$account);
        $root->exec('GRANT UPDATE (payload_json,status,run_after,last_error) ON '.$database.'.cleanup_outbox TO '.$account);
        $root->exec('GRANT UPDATE (unresolved_code) ON '.$database.'.evidence_case_clocks TO '.$account);
        foreach (['bushisa_pair_trigger_health','bushisa_attempt_schema_health','bushisa_verification_schema_health'] as $procedure) {
            $root->exec('GRANT EXECUTE ON PROCEDURE '.$database.'.'.obs_id($procedure).' TO '.$account);
        }
    } else {
        // Neither table-wide users UPDATE nor users.password_hash SELECT.
        $root->exec('GRANT SELECT (id,email,created_at,is_verified,is_suspended,is_active,account_status,deleted_at,session_version,signup_attempt_key), UPDATE (account_status,is_active,is_verified,session_version,deleted_at) ON ' . $database . '.users TO ' . $account);
        $root->exec('GRANT SELECT (user_id,date_of_birth) ON ' . $database . '.profiles TO ' . $account);
        $rights = [
            'SELECT,UPDATE' => ['student_email_approval_codes','mail_outbox','signup_mail_jobs','session_bindings','password_reset_mail_jobs','password_reset_challenges'],
            'SELECT,INSERT,UPDATE' => ['signup_lifecycle','verification_retention'],
            'SELECT,INSERT' => ['signup_lifecycle_alerts','signup_expiry_obligations','signup_attempt_erasure_jobs','session_copy_obligations','verification_cleanup_alerts','verification_late_results','verification_recovery_obligations'],
            'SELECT' => ['signup_attempt_identities','session_copy_coverage','password_reset_state'],
        ];
        foreach ($rights as $right => $tables) {
            foreach ($tables as $table) {
                $effective = $kind === 'missing' && $table === 'mail_outbox' ? 'SELECT' : $right;
                $root->exec('GRANT ' . $effective . ' ON ' . $database . '.' . obs_id($table) . ' TO ' . $account);
            }
        }
        $root->exec('GRANT SELECT,INSERT ON ' . $database . '.cleanup_outbox TO ' . $account);
        $root->exec('GRANT UPDATE (payload_json,status,run_after,last_error) ON ' . $database . '.cleanup_outbox TO ' . $account);
        foreach (['bushisa_pair_trigger_health','bushisa_attempt_schema_health','bushisa_verification_schema_health'] as $procedure) {
            $root->exec('GRANT EXECUTE ON PROCEDURE ' . $database . '.' . obs_id($procedure) . ' TO ' . $account);
        }
    }
    $grants = $root->query('SHOW GRANTS FOR ' . $account)->fetchAll(PDO::FETCH_COLUMN);
    $grants = array_map(fn($grant) => str_replace('`'.$user.'`', '`fixture_'.$kind.'`', $grant), $grants);
    return compact('user', 'password', 'account', 'grants');
}
