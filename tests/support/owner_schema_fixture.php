<?php
declare(strict_types=1);

// Fixed owner-metadata fixture support. No transcript SQL is read or evaluated.
require_once __DIR__.'/disposable_database.php';

function obs_fixture(): array
{
    $path=__DIR__.'/../fixtures/owner-observed-schema-20261020.json';
    if(filesize($path)>524288)throw new RuntimeException('Observed fixture too large');
    $f=json_decode(file_get_contents($path),true,24,JSON_THROW_ON_ERROR);
    if(($f['format']??'')!=='bushisa.owner-observed-schema.v1' || array_map('count',[$f['columns'],$f['indexes'],$f['foreign_key_rules'],$f['tables']])!==[227,122,13,29])throw new RuntimeException('Observed fixture version/count mismatch');
    return $f;
}
function obs_id(string $v): string
{ if(!preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]{0,100}\z/D',$v))throw new RuntimeException('Unsafe fixture identifier');return '`'.$v.'`'; }
function obs_connect(array $s,?array $user=null,bool $select=true): PDO
{
    $p=new PDO(sprintf('mysql:host=%s;port=%s;%scharset=utf8mb4',$s['host'],$s['port'],$select?'dbname='.$s['name'].';':''),$user['user']??$s['user'],$user['password']??$s['password'],bushisa_disposable_database_pdo_options($s));
    $p->exec("SET time_zone='+00:00'");return $p;
}
function obs_fks(): array
{
    // These mappings are from pinned 0bf311f/database/bushisa.sql, NOT captured
    // by the owner query. The remaining metadata gate is explicit in evidence.
    return ['audit_log_ibfk_1'=>['user_id','id'],'confessions_ibfk_1'=>['author_id','id'],
        'consent_records_ibfk_1'=>['user_id','id'],'matches_ibfk_1'=>['user_a_id','id'],'matches_ibfk_2'=>['user_b_id','id'],
        'messages_ibfk_1'=>['match_id','id'],'messages_ibfk_2'=>['sender_id','id'],'preferences_ibfk_1'=>['user_id','id'],
        'profiles_ibfk_1'=>['user_id','id'],'reports_ibfk_1'=>['reporter_id','id'],'reports_ibfk_2'=>['reported_user_id','id'],
        'swipes_ibfk_1'=>['swiper_id','id'],'swipes_ibfk_2'=>['swiped_id','id']];
}
function obs_construct(PDO $p,array $s,array $f): void
{
    // Guards precede connection in the caller; repeat before any destructive DDL.
    $guard=bushisa_require_disposable_test_database('Observed reconstruction');
    if($guard['name']!==$s['name'] || !str_starts_with($s['name'],'bushisa_test_release_observed_') || !in_array($s['host'],['127.0.0.1','localhost','::1'],true))throw new RuntimeException('Observed target refused');
    $db=obs_id($s['name']);$p->exec('DROP DATABASE IF EXISTS '.$db);$p->exec('CREATE DATABASE '.$db.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$p->exec('USE '.$db);
    foreach($f['tables'] as $t){
        $table=$t['table_name'];if($t['engine']!=='InnoDB'||$t['table_collation']!=='utf8mb4_unicode_ci')throw new RuntimeException('Unsupported observed table');
        $defs=[];
        foreach($f['columns'] as $c){if($c['table_name']!==$table)continue;
            $type=$c['column_type'];
            if(!preg_match('/\A(?:(?:bigint|int|smallint|tinyint) unsigned|tinyint\(1\)|(?:var)?char\([1-9][0-9]{0,2}\)|date|timestamp|json|text|enum\(\x27[A-Za-z0-9 _]+\x27(?:,\x27[A-Za-z0-9 _]+\x27)*\))\z/D',$type))throw new RuntimeException('Unsupported observed type');
            $d=obs_id($c['column_name']).' '.$type;
            if($c['character_set_name']!=='NULL'){if($c['character_set_name']!=='utf8mb4'||$c['collation_name']!=='utf8mb4_unicode_ci')throw new RuntimeException('Unsupported column collation');$d.=' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';}
            if(!in_array($c['is_nullable'],['YES','NO'],true))throw new RuntimeException('Invalid nullable flag');
            $d.=$c['is_nullable']==='YES'?' NULL':' NOT NULL';$default=$c['column_default'];
            // ASCII client NULL is ambiguous; SQL-NULL interpretation is a
            // recorded reconstruction assumption, not a new observation.
            if($default==='NULL'){if($c['is_nullable']==='YES')$d.=' DEFAULT NULL';}
            elseif($default==='CURRENT_TIMESTAMP' && str_contains($c['extra'],'DEFAULT_GENERATED') && $type==='timestamp'){$d.=' DEFAULT CURRENT_TIMESTAMP';}
            else{$d.=' DEFAULT '.$p->quote($default);}
            if(!in_array($c['extra'],['','auto_increment','DEFAULT_GENERATED','DEFAULT_GENERATED on update CURRENT_TIMESTAMP'],true))throw new RuntimeException('Unsupported column extra');
            if($c['extra']==='auto_increment')$d.=' AUTO_INCREMENT';
            if($c['extra']==='DEFAULT_GENERATED on update CURRENT_TIMESTAMP')$d.=' ON UPDATE CURRENT_TIMESTAMP';
            $defs[]=$d;
        }
        $indexes=[];foreach($f['indexes'] as $i)if($i['table_name']===$table)$indexes[$i['index_name']][]=$i;
        foreach($indexes as $name=>$parts){usort($parts,fn($a,$b)=>(int)$a['seq_in_index']<=>(int)$b['seq_in_index']);$keys=[];
            foreach($parts as $i){if($i['index_type']!=='BTREE'||!in_array($i['non_unique'],['0','1'],true))throw new RuntimeException('Unsupported index');$part=obs_id($i['column_name']);if($i['sub_part']!=='NULL'){if(!ctype_digit($i['sub_part']))throw new RuntimeException('Invalid prefix');$part.='('.$i['sub_part'].')';}$keys[]=$part;}
            $defs[]=($name==='PRIMARY'?'PRIMARY KEY':($parts[0]['non_unique']==='0'?'UNIQUE KEY ':'KEY ').obs_id($name)).' ('.implode(',',$keys).')';
        }
        $p->exec('CREATE TABLE '.obs_id($table).' ('.implode(',',$defs).') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
    $map=obs_fks();foreach($f['foreign_key_rules'] as $fk){if(!isset($map[$fk['constraint_name']])||$fk['update_rule']!=='NO ACTION'||!in_array($fk['delete_rule'],['CASCADE','SET NULL'],true))throw new RuntimeException('Unmapped FK');[$child,$parent]=$map[$fk['constraint_name']];
        $p->exec('ALTER TABLE '.obs_id($fk['table_name']).' ADD CONSTRAINT '.obs_id($fk['constraint_name']).' FOREIGN KEY ('.obs_id($child).') REFERENCES '.obs_id($fk['referenced_table_name']).' ('.obs_id($parent).') ON UPDATE NO ACTION ON DELETE '.$fk['delete_rule']);}
    $q=$p->prepare('INSERT INTO schema_migrations(version,applied_at) VALUES (?,?)');foreach($f['migration_ledger'] as $r)$q->execute([$r['version'],$r['applied_at']]);
}
function obs_snapshot(PDO $p): array
{
    return [
        'columns'=>$p->query('SELECT table_name,column_name,ordinal_position,column_type,is_nullable,column_default,extra,character_set_name,collation_name FROM information_schema.columns WHERE table_schema=DATABASE() ORDER BY table_name,ordinal_position')->fetchAll(PDO::FETCH_ASSOC),
        'indexes'=>$p->query('SELECT table_name,index_name,non_unique,seq_in_index,column_name,sub_part,index_type FROM information_schema.statistics WHERE table_schema=DATABASE() ORDER BY table_name,index_name,seq_in_index')->fetchAll(PDO::FETCH_ASSOC),
        'foreign_key_rules'=>$p->query('SELECT table_name,constraint_name,referenced_table_name,update_rule,delete_rule FROM information_schema.referential_constraints WHERE constraint_schema=DATABASE() ORDER BY table_name,constraint_name')->fetchAll(PDO::FETCH_ASSOC),
        'tables'=>$p->query('SELECT table_name,engine,table_collation FROM information_schema.tables WHERE table_schema=DATABASE() ORDER BY table_name')->fetchAll(PDO::FETCH_ASSOC),
    ];
}
function obs_compare(array $f,array $snapshot): array
{
    $keys=['columns'=>['table_name','column_name'],'indexes'=>['table_name','index_name','seq_in_index'],'foreign_key_rules'=>['table_name','constraint_name'],'tables'=>['table_name']];$result=[];
    foreach($keys as $kind=>$fields){$actual=[];foreach($snapshot[$kind] as $row){$row=array_change_key_case($row);$id=implode('.',array_map(fn($k)=>(string)$row[$k],$fields));$actual[$id]=$row;}
        foreach($f[$kind] as $e){$id=implode('.',array_map(fn($k)=>(string)$e[$k],$fields));$a=$actual[$id]??[];$diff=[];
            foreach($e as $k=>$value){$v=$a[$k]??null;$v=$v===null?'NULL':(string)$v;
                // Normalize only server expression presentation, never string defaults.
                if($k==='column_default' && ($e['column_type']??'')==='timestamp' && str_contains($e['extra']??'','DEFAULT_GENERATED')){$v=strtoupper(str_replace('()','',$v));}
                if($v!==$value)$diff[$k]=['observed'=>$value,'local'=>$v];}
            $result[$kind][]=['id'=>$id,'status'=>$diff?'DIFFERENT':'MATCH','differences'=>$diff];
        }
        $result[$kind.'_extra_rows']=count($snapshot[$kind])-count($f[$kind]);
    }
    return $result;
}
