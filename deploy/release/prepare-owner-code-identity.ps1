# LOCAL preparation only. Emits a read-only Bash command block; never executes it.
$ErrorActionPreference='Stop'
$refs=@('0bf311fb6d6a298712fd4cd16d0722305516edd4','888a2c273711c1ac5f3d1dc3d26d649f1e636235')
$paths=@()
foreach($ref in $refs){
 $listing=git -c core.quotepath=false ls-tree -r --name-only $ref
 if($LASTEXITCODE -ne 0){throw 'Required comparison source unavailable'}
 foreach($p in $listing){
  if($p -notmatch '\A[A-Za-z0-9_./-]+\z' -or $p -match '(?:\A|/)(?:uploads|sessions|logs|cache|secrets|keys)(?:/|\z)' -or $p -match '(?i)(?:config\.local|credential|secret)'){continue}
  if($p -match '\A(?:[A-Za-z0-9_-]+\.php|composer\.(?:json|lock)|package(?:-lock)?\.json|\.htaccess)\z' -or $p -match '\A(?:admin|api|bin|config|css|js|php|public)/.+\.(?:php|js|css|html|svg)\z' -or $p -match '\Adatabase/migrations/[0-9]{3}_[A-Za-z0-9_]+\.sql\z'){$paths+=$p}
 }
}
# Dependency code/metadata only. Never load the PHP autoloader or application.
$paths+=@('vendor/autoload.php','vendor/composer/installed.json','vendor/composer/installed.php','vendor/composer/ClassLoader.php','vendor/composer/InstalledVersions.php','vendor/composer/autoload_real.php','vendor/composer/autoload_static.php','vendor/composer/autoload_psr4.php','vendor/composer/autoload_namespaces.php','vendor/composer/autoload_classmap.php','vendor/composer/autoload_files.php','vendor/composer/platform_check.php')
foreach($n in @('PHPMailer','SMTP','Exception','OAuth','OAuthTokenProvider','POP3','DSNConfigurator')){$paths+='vendor/phpmailer/phpmailer/src/'+$n+'.php'}
$paths=@($paths|Sort-Object -Unique)
if($paths.Count -gt 500){throw 'Unexpected collection size'}
$start=@'
# Run in AZURE APPLICATION SSH, Bash. No application PHP or worker is invoked.
set -eu
cd -- /home/site/wwwroot
printf 'BEGIN_APPLICATION_CODE_IDENTITY\n'
date -u '+capture_utc=%Y-%m-%dT%H:%M:%SZ'
TZ=Africa/Harare date '+capture_harare=%Y-%m-%dT%H:%M:%S%:z'
printf 'physical_root=%s\n' "$(pwd -P)"
test "$(pwd -P)" = /home/site/wwwroot || { printf 'STOP_ROOT_MAPPING_REVIEW\n'; exit 1; }
for tool in sha256sum stat; do command -v "$tool" >/dev/null || exit 1; done
while IFS= read -r p; do
  q=$p
  while [ "$q" != . ]; do
    test ! -L "$q" || { printf 'STOP_SYMLINK %s\n' "$p"; exit 1; }
    case "$q" in */*) q=${q%/*};; *) q=.;; esac
  done
  if [ ! -e "$p" ]; then printf 'MISSING %s\n' "$p"; continue; fi
  test -f "$p" || { printf 'STOP_NONREGULAR %s\n' "$p"; exit 1; }
  before=$(stat -c '%d:%i:%s:%y:%z' -- "$p")
  result=$(sha256sum -- "$p")
  after=$(stat -c '%d:%i:%s:%y:%z' -- "$p")
  test "$before" = "$after" || { printf 'STOP_CHANGED %s\n' "$p"; exit 1; }
  printf '%s\n' "$result"
done <<'BUSHISA_REVIEWED_CODE_PATHS'
'@
$end=@'
BUSHISA_REVIEWED_CODE_PATHS
date -u '+capture_end_utc=%Y-%m-%dT%H:%M:%SZ'
printf 'END_APPLICATION_CODE_IDENTITY\n'
'@
$scriptText=(@($start)+$paths+@($end)) -join "`n"
Write-Output -NoEnumerate ($scriptText.Replace("`r`n","`n")+"`n")
