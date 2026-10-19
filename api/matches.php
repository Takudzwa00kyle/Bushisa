<?php
declare(strict_types=1);
require_once __DIR__.'/../php/security_headers.php';
require_once __DIR__.'/../php/session_manager.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../php/function.php';
require_once __DIR__.'/../php/pair_messages.php';
if ($_SERVER['REQUEST_METHOD']!=='GET') { json_response(['error'=>'Method not allowed'],405); }
require_login();
$actor=(int)$_SESSION['user_id']; $version=(int)$_SESSION['session_version']; session_write_close();
try { json_response(['success'=>true,'data'=>bushisa_pair_match_list($pdo,$actor,$version)]); }
catch (BushisaReportError $e) { json_response(['error'=>$e->getMessage()],$e->status); }
catch (Throwable $e) { log_to_file('Match list failed: '.get_class($e),'ERROR'); json_response(['error'=>'Matches unavailable'],503); }
