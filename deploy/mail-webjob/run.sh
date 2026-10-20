#!/bin/bash
set -euo pipefail
cd /home/site/wwwroot
# Keep the existing mail schedule from activating broader lifecycle consumers.
# A cleanup failure must be visible without suppressing otherwise valid mail.
cleanup_status=0
php bin/cleanup_mail.php || cleanup_status=$?
php bin/dispatch_mail.php
exit "$cleanup_status"
