<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }

function bushisa_migrate_verification_retention(PDO $p,string $sql): void
{
    $p->exec($sql);
    $canonical=static fn(string $s):string=>trim(str_replace("\r\n","\n",$s)," \t\r\n;");
    $digests=[];
    foreach(['student_email_approval_codes'=>['user_id','created_at','expires_at'],
        'mail_outbox'=>['approval_code_id','provider','created_at','expires_at']] as $table=>$fields) {
        if (!operational_column_exists($p,$table,'retention_key')) {
            $p->exec("ALTER TABLE $table ADD COLUMN retention_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL");
        }
        $col=$p->query("SHOW FULL COLUMNS FROM $table WHERE Field='retention_key'")->fetch(PDO::FETCH_ASSOC);
        if ($col['Type']!=='char(64)' || $col['Null']!=='YES' || $col['Collation']!=='ascii_bin') { throw new RuntimeException('Unexpected retention identity column'); }
        $conditions=[];
        foreach(array_merge(['id','retention_key'],$fields) as $f) { $conditions[]="NOT (NEW.$f <=> OLD.$f)"; }
        $body="BEGIN IF OLD.retention_key IS NOT NULL AND (".implode(' OR ',$conditions).") THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Immutable verification record'; END IF; END";
        $name=$table==='mail_outbox'?'bushisa_mail_retention_identity':'bushisa_code_retention_identity';
        $q=$p->prepare('SELECT ACTION_STATEMENT FROM information_schema.triggers WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?');$q->execute([$name]);$old=$q->fetchColumn();
        if ($old!==false && $canonical($old)!==$canonical($body)) { throw new RuntimeException('Unexpected verification identity trigger'); }
        if ($old===false) { $p->exec("CREATE TRIGGER $name BEFORE UPDATE ON $table FOR EACH ROW $body"); }
        $digests[]="(TRIGGER_NAME='$name' AND EVENT_OBJECT_TABLE='$table' AND EVENT_MANIPULATION='UPDATE' AND ACTION_TIMING='BEFORE' AND SHA2(REPLACE(ACTION_STATEMENT,CHAR(13),''),256)='".hash('sha256',$body)."')";
    }
    $schema=$p->quote((string)$p->query('SELECT DATABASE()')->fetchColumn());
    $tables="'student_email_approval_codes','mail_outbox','signup_mail_jobs'";
    $allowed=[
        'student_email_approval_codes'=>['id','user_id','email','code_hash','code_version','expires_at','attempts','consumed_at','created_at','terminal_at','purged_at','retention_key'],
        'mail_outbox'=>['id','recipient_email','subject','body','provider','status','created_at','sent_at','expires_at','payload_erased_at','code_bearing','metadata_json','approval_code_id','attempts','next_attempt_at','retention_key'],
        'signup_mail_jobs'=>['mail_id','account_id','code_id','queued_at','useful_until','state','claim_hash','claimed_at','claim_until','started_at','cancel_requested_at','terminal_at','accepted_at','metadata_expires_at','reason_code','version']
    ];
    $columns=[];
    foreach($allowed as $t=>$fs) { $columns[]="(TABLE_NAME='$t' AND COLUMN_NAME NOT IN (".implode(',',array_map([$p,'quote'],$fs))."))"; }
    $routine="SELECT 'verification-retention-v1' AS contract,
        (SELECT COUNT(*) FROM information_schema.columns WHERE TABLE_SCHEMA=$schema AND (".implode(' OR ',$columns).")) AS unknown_columns,
        (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE CONSTRAINT_SCHEMA=$schema AND REFERENCED_TABLE_NAME IN ($tables)) AS incoming_fks,
        (SELECT COUNT(*) FROM information_schema.triggers WHERE TRIGGER_SCHEMA=$schema AND EVENT_OBJECT_TABLE IN ($tables) AND EVENT_MANIPULATION IN ('UPDATE','DELETE') AND NOT (".implode(' OR ',$digests).")) AS unknown_triggers,
        (SELECT COUNT(*) FROM information_schema.triggers WHERE TRIGGER_SCHEMA=$schema AND (".implode(' OR ',$digests).")) AS identity_guards";
    $q=$p->prepare('SELECT ROUTINE_DEFINITION,SECURITY_TYPE,SQL_DATA_ACCESS,DEFINER FROM information_schema.routines WHERE ROUTINE_SCHEMA=DATABASE() AND ROUTINE_NAME=?');$q->execute(['bushisa_verification_schema_health']);$old=$q->fetch(PDO::FETCH_ASSOC);
    if ($old && ($canonical($old['ROUTINE_DEFINITION'])!==$canonical($routine) || $old['SECURITY_TYPE']!=='DEFINER' || $old['SQL_DATA_ACCESS']!=='READS SQL DATA' || $old['DEFINER']!==$p->query('SELECT CURRENT_USER()')->fetchColumn())) { throw new RuntimeException('Unexpected verification health routine'); }
    if (!$old) { $p->exec('CREATE PROCEDURE bushisa_verification_schema_health() SQL SECURITY DEFINER READS SQL DATA '.$routine); }
}
