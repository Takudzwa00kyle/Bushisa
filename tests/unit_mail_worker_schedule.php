<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$repo=dirname(__DIR__);$script=str_replace("\r\n","\n",file_get_contents($repo.'/deploy/mail-webjob/run.sh'));
if(str_contains($script,'cleanup_policy.php') || !str_contains($script,'php bin/cleanup_mail.php') || !str_contains($script,'php bin/dispatch_mail.php'))throw new RuntimeException('Unexpected worker boundary');
if(PHP_OS_FAMILY==='Windows'){echo "DR17 shell execution NOT RUN on native Windows; Linux variant required\n";exit;}
$root=$repo.'/.tmp/release-worker-'.bin2hex(random_bytes(8));mkdir($root,0700,true);
file_put_contents($root.'/php',"#!/bin/bash\nprintf '%s\\n' \"\$1\" >> \"\$WORKER_FIXTURE/log\"\nif [[ \"\$1\" == bin/cleanup_mail.php ]]; then exit \"\$FIXTURE_CLEANUP_EXIT\"; fi\nexit \"\$FIXTURE_DISPATCH_EXIT\"\n");chmod($root.'/php',0700);
// Only substitute the fixed working directory in an owned copy of the script.
file_put_contents($root.'/run.sh',str_replace('cd /home/site/wwwroot','cd "$WORKER_FIXTURE"',$script));
foreach([[0,0,0],[77,0,77],[0,13,13],[77,13,13]] as $n=>[$cleanup,$dispatch,$expected]){
    @unlink($root.'/log');$pipes=[];$env=array_replace(getenv(),['PATH'=>$root.':'.getenv('PATH'),'WORKER_FIXTURE'=>$root,'FIXTURE_CLEANUP_EXIT'=>(string)$cleanup,'FIXTURE_DISPATCH_EXIT'=>(string)$dispatch]);
    $p=proc_open(['bash',$root.'/run.sh'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$repo,$env);fclose($pipes[0]);stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($p);
    if($exit!==$expected || file_get_contents($root.'/log')!=="bin/cleanup_mail.php\nbin/dispatch_mail.php\n")throw new RuntimeException('Worker order/failure propagation');
    echo 'DR17.'.($n+1)." PASS\n";
}
