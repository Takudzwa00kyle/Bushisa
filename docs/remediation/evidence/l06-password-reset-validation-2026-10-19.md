# L06 password-reset validation — 2026-10-19

This record covers only the local L06 implementation on branch
`codex/l06-password-reset-20261019`, based on `6e01d62`. No production database,
user data, SMTP delivery, Azure setting, scheduler or DNS record was accessed or
changed.

## L06 cases

The machine-readable case results are in
[`l06-password-reset-local-77510f9.json`](l06-password-reset-local-77510f9.json): **24 PASS, 0
FAIL**. The run used Windows, PHP 8.5.11 and a guarded disposable MySQL 8.0.45
container at loopback, with synthetic users and fake mail. The integration suite
rehearsed a fresh bootstrap, upgrade through migration 016 and repeat migration.
`L06-RACE-01` used two distinct PHP worker processes and database connections;
the test observed both waiting concurrently behind the lock, then exactly one
successful proof consumption. The two existing authenticated sessions both
lost protected access after the session-version increment, and normal login
with the new password succeeded.

**Historical limitation:** AUTH-02 in this original run accepted a 405 response
from a POST-only API and did not prove authenticated pre-reset access after
session-ID rotation. Its API-revocation claim is insufficient. The subsequent
[browser/CI follow-up](../l06-browser-hosted-ci-evidence-2026-10-19.md) corrects
that test: AUTH-00/02 and rendered B04 verify genuine pre-access and API 401
after reset. Current HTTP results have 25 cases; browser results have six.

Cases cover known/unknown/ineligible existence-neutral request responses;
trusted origin allow/deny; reset-purpose intake and authenticated encrypted envelope; supersession; scanner-safe GET;
CSRF; explicit POST; replay, expiry boundary, changed account state and forged
account ID; notification and transport failure; disabled-issuance dispatch
refusal; cleanup; proof-free diagnostics; session revocation; and concurrent
single consumption. No actual email was sent.

The test-only MySQL container was stopped after the regression runs.

## Related regressions

All used separately guarded disposable database names on the same isolated local
MySQL container, synthetic accounts and fake mail:

| Command | Result | Local evidence |
|---|---:|---|
| `php tests/integration_q11_signup_expiry.php` | 74/74 PASS | `.tmp/q11-20261019-015753-c343b3b8/evidence.json` |
| `php tests/integration_verification_retention.php` | 75/75 PASS | `.tmp/q22-20261019-015857-e19989c9/evidence.json` |
| `php tests/integration_attempt_erasure.php` | 57/57 PASS | `.tmp/qe-20261019-020023-04c04faa/evidence.json` |
| `php tests/integration_smtp_outbox.php` | PASS | stdout; callback-only transport, nested dispatcher check |
| `php tests/unit_mail_configuration.php` | PASS | stdout |
| `php tests/unit_signup_verification.php` | PASS | stdout |
| PHP syntax checks for all changed/new PHP files | PASS | stdout |
| `node --check js/password-reset-token.js` | NOT RUN | Local Node executable unavailable; hosted CI step configured, not executed |
| `git diff --check` | PASS | stdout; line-ending normalization warnings only |

The new documentation links were checked against their local targets and
heading IDs. Browser execution of the fragment autofill script was not run;
the PHP HTTP route and scanner-safe GET behavior were exercised without a
browser.

Q11/Q22/erasure artifacts retain their own suites and have not been folded into
the A01–A45, LB, LC, QS/QE/QM or SC matrices. A complete hosted CI run is
**NOT RUN**. Staging/production runtime, migration grants, sender/inbox/headers,
provider acceptance, operational monitoring, qualified review and rollout
remain **UNVERIFIED / OPEN**.

## Repeatable guarded invocation

The L06 integration entry point is `php tests/integration_l06_password_reset.php`.
It rejects non-disposable database names and non-loopback hosts, bootstraps only
its selected test database, starts a local PHP server, creates synthetic users,
uses fake transport and deletes its isolated temporary session files. The local
MySQL container credentials are deliberately not recorded here. The established
CI workflow has a distinct synthetic L06 database step; adding that step is not
hosted execution evidence.
