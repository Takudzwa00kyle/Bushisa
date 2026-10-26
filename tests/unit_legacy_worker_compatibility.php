<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (PHP_OS_FAMILY === 'Windows') { echo "WC01 NOT RUN: native Bash unavailable; use Linux profile\n"; exit; }
$repo=dirname(__DIR__);$base='0bf311fb6d6a298712fd4cd16d0722305516edd4';$pipes=[];
$p=proc_open(['git','show',$base.':deploy/mail-webjob/run.sh'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$repo);
if(!is_resource($p))throw new RuntimeException('FIXTURE_SOURCE_UNAVAILABLE');
fclose($pipes[0]);$old=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
if(proc_close($p)!==0||hash('sha256',$old)!=='8549c2b452b03a883dffbb0752ff33b35c6f86aa137f85255a8d42ce72fa6015')throw new RuntimeException('FIXTURE_SOURCE_MISMATCH');
$dir=$repo.'/.tmp/worker-compatibility-'.bin2hex(random_bytes(6));mkdir($dir,0700,true);
// The shell executes an owned shim: actual production guard under PHP -n,
// then a dispatch marker. No dispatch CLI, SQL, SMTP or discovered code runs.
file_put_contents($dir.'/php',"#!/bin/bash\nif [[ \"\$1\" == bin/cleanup_policy.php ]]; then exec \"\$WC_PHP\" -n \"\$WC_REPO/bin/cleanup_policy.php\"; fi\nprintf 'dispatch-marker\\n' >> \"\$WC_DIR/marker\"\nexit 0\n");chmod($dir.'/php',0700);
file_put_contents($dir.'/old.sh',str_replace('cd /home/site/wwwroot','cd "$WC_DIR"',$old));
$env=array_replace(getenv(),['PATH'=>$dir.':'.getenv('PATH'),'WC_PHP'=>PHP_BINARY,'WC_REPO'=>$repo,'WC_DIR'=>$dir,'APP_ENV'=>'production']);
$p=proc_open(['bash',$dir.'/old.sh'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$repo,$env);
fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($p);
if($exit!==78||file_exists($dir.'/marker')||!str_contains($err.$out,'POLICY_CLEANUP_GATED'))throw new RuntimeException('WORKER_INCOMPATIBILITY_NOT_REPRODUCED');
echo "WC01 PASS: installed-byte comparator exits 78 at actual candidate guard; dispatch marker absent; no PDO driver or SMTP\n";
