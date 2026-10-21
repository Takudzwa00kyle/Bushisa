<?php
declare(strict_types=1);
require_once __DIR__.'/../../php/managed_session.php';
try { \Bushisa\ManagedSession\configuration(); } catch(Throwable) { http_response_code(404); exit; }
require_once __DIR__.'/../../php/session_manager.php';
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../php/csrf.php';
$action=$_GET['action']??'read';
if ($action==='rotate') { regenerate_session(); }
if ($action==='write') { $_SESSION['last_activity']=time(); }
header('Content-Type: application/json');
echo json_encode(['authenticated'=>isset($_SESSION['user_id']),'user_id'=>$_SESSION['user_id']??null,'csrf'=>generate_csrf_token()],JSON_THROW_ON_ERROR);
