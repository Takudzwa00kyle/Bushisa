<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }

function bushisa_migrate_session_copy_consumer(PDO $p,string $sql): void
{
    foreach(['session_copy_manifests'=>[
        'scope_class'=>'VARCHAR(16) NULL','account_id'=>'INT NULL',
        'account_key'=>'CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL',
        'auth_version'=>'INT UNSIGNED NULL',
        'object_generation'=>'CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL'],
        'session_copy_obligations'=>['account_version'=>'INT UNSIGNED NULL','clock_version'=>'BIGINT UNSIGNED NULL','registered_at'=>'DATETIME(6) NULL']] as $table=>$columns) {
        foreach($columns as $column=>$definition) {
            if (!operational_column_exists($p,$table,$column)) { $p->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition"); }
        }
    }
    $p->exec($sql);
    foreach(['session_copy_manifests'=>['session_scope_copies',['account_id','account_key','copy_ref']],
        'session_bindings'=>['session_scope_refs',['account_id','account_key','session_ref']]] as $table=>[$index,$columns]) {
        $rows=$p->query("SHOW INDEX FROM `$table` WHERE Key_name=".$p->quote($index))->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) { $p->exec("CREATE INDEX `$index` ON `$table` (`".implode('`,`',$columns)."`)"); }
        elseif(array_column($rows,'Column_name')!==$columns) { throw new RuntimeException('Unexpected session-copy index'); }
    }
    $immutable=[
        'session_copy_manifests'=>['copy_ref','session_ref','binding_epoch','expected_revision','namespace_id','adapter_version','instance_id','created_at','scope_class','account_id','account_key','auth_version','object_generation'],
        'session_copy_obligations'=>['event_ref','account_id','account_key','source_receipt','effective_at','purge_due_at','policy_ref','component','account_version','clock_version','registered_at'],
        'session_copy_coverage'=>['account_key','namespace_id','account_id','registered_at','root_version','writer_contract','recorded_at'],
        'session_copy_cleanup_jobs'=>['event_ref','namespace_id','root_version','created_at'],
        'session_copy_purge_items'=>['event_ref','copy_ref','session_ref','binding_epoch','object_generation'],
        'session_copy_purge_receipts'=>['event_ref','source_receipt','account_key','component','scope_version','namespace_id','root_version','copy_count','effective_at','purge_due_at','verified_at'],
        'session_copy_recovery'=>['event_ref','account_id','account_key','auth_floor','action_name','effective_at','purge_due_at','fenced_at'],
        'session_copy_cleanup_alerts'=>['event_ref','reason_code','deadline_at','first_at','escalate_at']];
    foreach($immutable as $table=>$columns) {
        $test=implode(' OR ',array_map(static fn($c)=>"NOT (NEW.`$c` <=> OLD.`$c`)",$columns));
        if($table==='session_copy_coverage') { $test.=' OR (OLD.sealed_at IS NOT NULL AND NOT (OLD.sealed_at <=> NEW.sealed_at))'; }
        if($table==='session_copy_purge_items') { $test.=' OR (OLD.expected_identity IS NOT NULL AND NOT (OLD.expected_identity <=> NEW.expected_identity))'; }
        if($table==='session_copy_recovery') { $test.=' OR (OLD.live_purged_at IS NOT NULL AND NOT (OLD.live_purged_at <=> NEW.live_purged_at))'; }
        $body="BEGIN IF $test THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Immutable session copy identity'; END IF; END";
        $name='bushisa_sc_'.substr(hash('sha256',$table),0,16);
        $old=$p->prepare('SELECT ACTION_STATEMENT FROM information_schema.triggers WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?');$old->execute([$name]);$existing=$old->fetchColumn();
        $normal=static fn($s)=>trim(str_replace("\r\n","\n",$s)," \t\r\n;");
        if($existing!==false && $normal($existing)!==$normal($body)) { throw new RuntimeException('Unexpected session-copy identity trigger'); }
        if($existing===false) { $p->exec("CREATE TRIGGER `$name` BEFORE UPDATE ON `$table` FOR EACH ROW $body"); }
    }
}
