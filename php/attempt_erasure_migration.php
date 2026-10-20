<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }

function bushisa_migrate_attempt_erasure(PDO $pdo,string $sql): void
{
    if (!operational_column_exists($pdo,'users','signup_attempt_key')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN signup_attempt_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL');
    }
    $column=$pdo->query("SHOW FULL COLUMNS FROM users WHERE Field='signup_attempt_key'")->fetch(PDO::FETCH_ASSOC);
    if ($column['Type']!=='char(64)' || $column['Null']!=='YES' || $column['Collation']!=='ascii_bin') { throw new RuntimeException('Unexpected attempt-generation column'); }
    $pdo->exec($sql);
    // Keep account-bound address paging and evidence-reference checks indexed.
    foreach(['student_email_approval_codes'=>['idx_attempt_codes_owner',['user_id','id']],
        'mail_outbox'=>['idx_attempt_mail_owner',['approval_code_id','id']],
        'reports'=>['idx_attempt_evidence_path',['evidence_path']]] as $table=>[$index,$fields]) {
        $rows=$pdo->query("SHOW INDEX FROM `$table` WHERE Key_name=".$pdo->quote($index))->fetchAll(PDO::FETCH_ASSOC);
        if(!$rows){$pdo->exec("CREATE INDEX `$index` ON `$table` (`".implode('`,`',$fields)."`)");}
        elseif(array_column($rows,'Column_name')!==$fields){throw new RuntimeException('Unexpected attempt-cleanup index');}
    }
    $schema=$pdo->quote((string)$pdo->query('SELECT DATABASE()')->fetchColumn());
    $body="BEGIN IF OLD.signup_attempt_key IS NOT NULL AND NOT (NEW.signup_attempt_key <=> OLD.signup_attempt_key) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Immutable signup attempt'; END IF; END";
    $canonical=static fn(string $s):string=>trim(str_replace("\r\n","\n",$s)," \t\r\n;");
    $q=$pdo->prepare('SELECT ACTION_STATEMENT FROM information_schema.triggers WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?');$q->execute(['bushisa_attempt_key_immutable']);$old=$q->fetchColumn();
    if ($old!==false && $canonical($old)!==$canonical($body)) { throw new RuntimeException('Unexpected attempt-key trigger'); }
    if ($old===false) { $pdo->exec('CREATE TRIGGER bushisa_attempt_key_immutable BEFORE UPDATE ON users FOR EACH ROW '.$body); }
    $allowed=[
        'users'=>['id','nust_student_id','email','password_hash','is_verified','is_suspended','created_at','role','is_active','account_status','session_version','deleted_at','signup_attempt_key'],
        'profiles'=>['user_id','display_name','anon_handle','bio','gender','date_of_birth','faculty','year_of_study','profile_photo_path'],
        'preferences'=>['user_id','preferred_gender','age_range_min','age_range_max','preferred_faculty'],
        'find_match_answers'=>['user_id','question_key','answer_value','updated_at','catalog_version','answer_json','importance','fit_mode','accepted_options_json','needs_review'],
        'notification_preferences'=>['user_id','category','is_enabled','updated_at'],
        'profile_images'=>['id','user_id','slot','path','mime_type','byte_size','created_at','updated_at'],
    ];
    $conditions=[];
    foreach($allowed as $table=>$cols) { $conditions[]='(TABLE_NAME='.$pdo->quote($table).' AND COLUMN_NAME NOT IN ('.implode(',',array_map([$pdo,'quote'],$cols)).'))'; }
    $tables=implode(',',array_map([$pdo,'quote'],array_keys($allowed)));
    $children=implode(',',array_map([$pdo,'quote'],array_values(array_diff(array_keys($allowed),['users']))));
    $digest=hash('sha256',$body);
    $routine="SELECT 'attempt-schema-v1' AS contract,
        (SELECT COUNT(*) FROM information_schema.columns WHERE TABLE_SCHEMA=$schema AND (".implode(' OR ',$conditions).")) AS unknown_columns,
        (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE CONSTRAINT_SCHEMA=$schema AND REFERENCED_TABLE_NAME IN ($children)) AS child_cascades,
        (SELECT COUNT(*) FROM information_schema.triggers WHERE TRIGGER_SCHEMA=$schema AND EVENT_OBJECT_TABLE IN ($tables) AND EVENT_MANIPULATION IN ('UPDATE','DELETE') AND NOT (TRIGGER_NAME='bushisa_attempt_key_immutable' AND EVENT_OBJECT_TABLE='users' AND ACTION_TIMING='BEFORE' AND EVENT_MANIPULATION='UPDATE' AND SHA2(REPLACE(ACTION_STATEMENT,CHAR(13),''),256)='$digest')) AS unknown_triggers,
        (SELECT COUNT(*) FROM information_schema.triggers WHERE TRIGGER_SCHEMA=$schema AND TRIGGER_NAME='bushisa_attempt_key_immutable' AND SHA2(REPLACE(ACTION_STATEMENT,CHAR(13),''),256)='$digest') AS generation_guard";
    $q=$pdo->prepare('SELECT ROUTINE_DEFINITION,SECURITY_TYPE,SQL_DATA_ACCESS,DEFINER FROM information_schema.routines WHERE ROUTINE_SCHEMA=DATABASE() AND ROUTINE_NAME=?');$q->execute(['bushisa_attempt_schema_health']);$old=$q->fetch(PDO::FETCH_ASSOC);
    if ($old && ($canonical($old['ROUTINE_DEFINITION'])!==$canonical($routine) || $old['SECURITY_TYPE']!=='DEFINER' || $old['SQL_DATA_ACCESS']!=='READS SQL DATA' || $old['DEFINER']!==$pdo->query('SELECT CURRENT_USER()')->fetchColumn())) { throw new RuntimeException('Unexpected attempt-schema routine'); }
    if (!$old) { $pdo->exec('CREATE PROCEDURE bushisa_attempt_schema_health() SQL SECURITY DEFINER READS SQL DATA '.$routine); }
}
