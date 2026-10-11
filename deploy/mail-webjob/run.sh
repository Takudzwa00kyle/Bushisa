#!/bin/bash
set -euo pipefail
cd /home/site/wwwroot
php bin/cleanup_policy.php
php bin/dispatch_mail.php
