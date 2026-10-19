<?php
declare(strict_types=1);
require_once __DIR__.'/pair_history.php';
require_once __DIR__.'/rate_limit.php';
require_once __DIR__.'/sanitize.php';

/** Same locked authorization for API and server-rendered forms/readers. */
function bushisa_pair_messages(PDO $pdo,int $actor,int $sessionVersion,int $match,string $action,array $input=[]): array
{
    return bushisa_report_transaction($pdo,function() use($pdo,$actor,$sessionVersion,$match,$action,$input):array {
        if (!bushisa_report_actor_ok($pdo,$actor,$sessionVersion)) { throw new BushisaReportError(401,'Your session is no longer valid'); }
        $auth=bushisa_authorize_match_action($pdo,$match,$actor,true,true,$action!=='send');
        if (!$auth['ok']) { throw new BushisaReportError(403,'Conversation unavailable'); }
        $cols=operational_message_columns($pdo); $bodyColumn=$cols['body']; $timeColumn=$cols['created_at'];
        if ($action==='read') {
            $after=max(0,(int)($input['after_id']??0));
            $stmt=$pdo->prepare('SELECT id,sender_id,'.$bodyColumn.' AS body,'.$timeColumn.' AS sent_at,is_read,unsent_at,unsent_by
                FROM messages WHERE match_id=? AND id>? AND unsent_at IS NULL AND ordinary_body_purged_at IS NULL
                AND (evidence_purge_due_at IS NULL OR evidence_purge_due_at>UTC_TIMESTAMP(6)) ORDER BY id FOR UPDATE');
            $stmt->execute([$match,$after]); $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) { $row['is_unsent']=false; $row['report_context']=bushisa_report_context_for_response($pdo,$actor,'message',(int)$row['id']); }
            unset($row);
            $pdo->prepare('UPDATE messages SET is_read=1 WHERE match_id=? AND sender_id<>? AND id>? AND unsent_at IS NULL AND ordinary_body_purged_at IS NULL AND (evidence_purge_due_at IS NULL OR evidence_purge_due_at>UTC_TIMESTAMP(6))')->execute([$match,$actor,$after]);
            return $rows;
        }
        if ($action==='unsend') {
            $id=filter_var($input['message_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if ($id===false) { throw new BushisaReportError(400,'Invalid message ID'); }
            $stmt=$pdo->prepare('UPDATE messages SET '.$bodyColumn.'="",moderation_body=NULL,unsent_at=UTC_TIMESTAMP(6),unsent_by=?,unsent_reason="sender_unsend",ordinary_body_purged_at=UTC_TIMESTAMP(6)
                WHERE id=? AND match_id=? AND sender_id=? AND unsent_at IS NULL AND '.$timeColumn.'>DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 15 MINUTE)');
            $stmt->execute([$actor,$id,$match,$actor]);
            if ($stmt->rowCount()!==1) {
                $old=$pdo->prepare('SELECT id FROM messages WHERE id=? AND match_id=? AND sender_id=? AND unsent_at IS NOT NULL'); $old->execute([$id,$match,$actor]);
                if (!$old->fetchColumn()) { throw new BushisaReportError(403,'Message cannot be unsent'); }
            }
            return ['id'=>$id,'is_unsent'=>true];
        }
        if ($action!=='send') { throw new BushisaReportError(400,'Invalid message action'); }
        $body=is_string($input['body']??null)?trim(clean($input['body'])):'';
        if ($body==='') { throw new BushisaReportError(400,'Message cannot be empty'); }
        $body=mb_substr($body,0,1000);
        if (!check_rate_limit($pdo,'message',(string)$actor,30,60)) { throw new BushisaReportError(429,'Rate limit exceeded'); }
        record_attempt($pdo,'message',(string)$actor);
        $segment=bushisa_pair_segment($pdo,$match)['segment_key']??null;
        if ($segment===null) {
            $grant=$pdo->prepare('SELECT id,responded_at FROM message_requests WHERE match_id=? AND status="approved" AND revoked_at IS NULL ORDER BY id DESC LIMIT 1'); $grant->execute([$match]); $legacy=$grant->fetch(PDO::FETCH_ASSOC);
            if (is_array($legacy) && $legacy['responded_at']!==null) { $segment=bushisa_pair_legacy_segment($match,$legacy); }
        }
        $pdo->prepare('INSERT INTO messages (match_id,sender_id,'.$bodyColumn.','.$timeColumn.',is_read,report_segment_key) VALUES (?,?,?,UTC_TIMESTAMP(6),0,?)')->execute([$match,$actor,$body,$segment]); $id=(int)$pdo->lastInsertId();
        $stmt=$pdo->prepare('SELECT id,sender_id,'.$bodyColumn.' AS body,'.$timeColumn.' AS sent_at,is_read,unsent_at,unsent_by FROM messages WHERE id=?'); $stmt->execute([$id]); return $stmt->fetch(PDO::FETCH_ASSOC);
    });
}

/** No denied profile/preview is loaded, even by old server-rendered readers. */
function bushisa_pair_match_list(PDO $pdo,int $actor,int $sessionVersion): array
{
    return bushisa_report_transaction($pdo,function() use($pdo,$actor,$sessionVersion):array {
        if (!bushisa_report_actor_ok($pdo,$actor,$sessionVersion)) { throw new BushisaReportError(401,'Your session is no longer valid'); }
        $mc=operational_match_columns($pdo); $cols=operational_message_columns($pdo);
        $stmt=$pdo->prepare(sprintf('SELECT id,%1$s AS a,%2$s AS b,%3$s AS matched_at,match_status FROM matches WHERE %1$s=? OR %2$s=? ORDER BY id DESC',$mc['user_a'],$mc['user_b'],$mc['created_at'])); $stmt->execute([$actor,$actor]); $out=[];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $other=(int)((int)$row['a']===$actor?$row['b']:$row['a']);
            if (!bushisa_account_is_chat_eligible($pdo,$other)||operational_is_blocked($pdo,$actor,$other)||$row['match_status']==='deleted_cleanup') { continue; }
            $authorization=bushisa_authorize_match_action($pdo,(int)$row['id'],$actor,true,false,true);
            if ($row['match_status']!=='active' && !$authorization['ok']) { continue; }
            $profile=$pdo->prepare('SELECT display_name,profile_photo_path,faculty FROM profiles WHERE user_id=?'); $profile->execute([$other]); $p=$profile->fetch(PDO::FETCH_ASSOC)?:[];
            $item=['match_id'=>(int)$row['id'],'other_user_id'=>$other,'matched_at'=>$row['matched_at'],'last_message'=>null,'last_message_time'=>null,'unread_count'=>0]+$p;
            $item['photo']=$p['profile_photo_path']??null;
            if ($authorization['ok']) {
                $latest=$pdo->prepare('SELECT '.$cols['body'].' AS body,'.$cols['created_at'].' AS sent_at FROM messages WHERE match_id=? AND unsent_at IS NULL AND ordinary_body_purged_at IS NULL AND (evidence_purge_due_at IS NULL OR evidence_purge_due_at>UTC_TIMESTAMP(6)) ORDER BY id DESC LIMIT 1'); $latest->execute([$row['id']]); $last=$latest->fetch(PDO::FETCH_ASSOC);
                if ($last) { $item['last_message']=$last['body']; $item['last_message_time']=$last['sent_at']; }
            }
            $out[]=$item;
        }
        return $out;
    });
}

function bushisa_pair_chat_profile(PDO $pdo,int $actor,int $sessionVersion,int $match): array
{
    return bushisa_report_transaction($pdo,function() use($pdo,$actor,$sessionVersion,$match):array {
        if (!bushisa_report_actor_ok($pdo,$actor,$sessionVersion)) { throw new BushisaReportError(401,'Your session is no longer valid'); }
        $members=bushisa_pair_members($pdo,$match,$actor);
        $other=(int)((int)$members['a']===$actor?$members['b']:$members['a']);
        bushisa_pair_check_contact($pdo,$actor,$other);
        if ($members['match_status']!=='active' && !bushisa_authorize_match_action($pdo,$match,$actor,true,true,true)['ok']) {
            throw new BushisaReportError(403,'Conversation unavailable');
        }
        $stmt=$pdo->prepare('SELECT user_id,display_name,profile_photo_path,faculty FROM profiles WHERE user_id=?');
        $stmt->execute([$other]); $row=$stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) { throw new BushisaReportError(403,'Conversation unavailable'); }
        return $row;
    });
}
