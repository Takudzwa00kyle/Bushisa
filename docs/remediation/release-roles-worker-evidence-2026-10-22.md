# Guarded role and worker checks: local evidence

Recovered clean `faa5b7a49f5d1222378f0c8482b06f5e37d1d4de` on
`codex/direct-production-release-prep-20261020`; previous commits preserved.
No applicable AGENTS.md was found in the repository/ancestor inspection.
This checkpoint implements independent local engineering requested after the
[handover review](direct-release-handover-review-2026-10-21.md).
Reviewer/operator assignments remain PENDING and did not block these tests.

## Defects reproduced and focused changes

The first strict worker execution rejected a real signup dispatch with MySQL
1142: `SELECT * FROM users` required reading columns outside the proposed
mailer profile. After narrowing those reads, expiry reached a second real
1142: `SELECT ... FOR UPDATE` on a validation-only session obligation required
mutation authority. These were permission defects under the new profile,
not reasons to retain table-wide users UPDATE or widen grants.

- `signup_mail_jobs` now selects the ten account fields used by delivery/expiry.
- Reset dispatch locks the account first with explicit fields, then reads DOB
  under a shared lock. Its issuance-state read is also shared. It does not need
  password hashes or profile-edit permission.
- Attempt-erasure enqueue reads only account ID/generation; immutable identity
  and expiry-obligation validation use shared current reads. The account and
  lifecycle locks remain first. Physical erasure paths are not activated.
- Session expiry validates its immutable obligation with a shared current read;
  it cannot mutate that record through the proposed worker role.

Shared locking retains a current read and protects the row without granting
UPDATE merely to validate it. MySQL documents the differing privileges of
FOR UPDATE and shared locking reads. [MySQL locking-read documentation](https://dev.mysql.com/doc/refman/8.0/en/innodb-locking-reads.html).

The enforced-TLS regression profile additionally exposed synthetic connections
that ignored configured certificate verification: the Q11 fixture, reset-consumer
worker and guarded synthetic session adapter now use the existing disposable
PDO TLS options. This does not select a production session handler. Unsupported
legacy/production mappings and storage coverage remain gated.

## Profiles and scope

[Fixture grants](../../tests/support/release_roles.php) are installed only after
loopback, disposable-prefix, explicit intent and actual server-UUID checks.
The local runner independently verifies the existing Docker label/image/network.
Wrong identity fails before fixture mutation, including cleanup: refusal cannot
drop a schema. Setup/migration/role teardown use a separate fixture administrator;
operations under test use distinct principals/connections.

| Role | Permitted / observed | Expected denials / remaining limits |
|---|---|---|
| Mail worker | Users SELECT is column-scoped and excludes password hash; UPDATE only `account_status,is_active,is_verified,session_version,deleted_at`. DOB read is limited to eligibility. Purpose-specific challenge/mail/lifecycle/receipt tables and three fixed health procedures. | Password read/write, suspension/email/generation changes, account insertion, private chat/evidence, profile DOB/obligation/identity edits, DDL/DROP, arbitrary routine, foreign schema, global administration and GRANT OPTION denied. Other mail-table column minimization remains reviewable work; this is not whole-system least-privilege acceptance. |
| Runtime | Separate bounded profile, confession and password/session DML checks. No inherited worker grant set. | DDL/DROP, global administration/grants, foreign schema, arbitrary procedure and immutable generation edits denied. Intentionally ungranted mail mutation denied. **Not a complete D03 runtime role**; full real-route grant coverage remains PARTIAL. |
| Controlled migrator | Actual 005–016 upgrade and repeat run; ALTER canary and preserved trigger definers. Schema-scoped DML/DDL/routine rights under test. | DROP, foreign schema, global administration and GRANT OPTION denied. Managed Azure privilege/definer behavior remains unverified. Do not remove a production definer account without a reviewed lifecycle. |

Column grants do not encode the permitted direction or row/purpose of every
update: for example, a principal with is_active UPDATE still needs trustworthy
application controls to restrict it to an eligible expiry. The suite tests those
application transactions and explicit denied columns; it does not prove a
compromised worker principal cannot misuse every allowed column. Stored-purpose
interfaces/remaining table-column minimization and final privilege acceptance
remain reviewable engineering/security work.

## Execution and attribution

Exact results, per-case engine errors, sanitized grants, versions, source-file
digests and regression overlap records are in the
[machine evidence](evidence/release-role-worker-checks-2026-10-22.json).
The role suite's groups are distinct from A01–A45, LB, LC, QS, QE, QM and SC;
no unrelated historical result is overwritten.

Local owned profile: Linux **PHP 8.5.11 / mysqlnd 8.5.11**, upstream
**MySQL 8.0.46**, UTC, REPEATABLE-READ, observed strict SQL mode,
`utf8mb4_0900_ai_ci` server / `utf8mb4_unicode_ci` schema, binlog and
trust-function-creators enabled for the controlled fixture migration.
Certificate-verifying PDO connections use **TLSv1.3**. MySQL is not the
Azure `8.0.46-azure` build; actual cloud roles/definers/storage remain unverified.
Native Windows PHP 8.5.11 syntax checks are separate from Linux DB execution.

Commands (local IntelliJ PowerShell; private synthetic credentials supplied
internally, not printed):

```powershell
powershell -NoProfile -File .tmp/run-release-roles.ps1
powershell -NoProfile -File .tmp/run-release-affected.ps1
docker exec bushisa-observed-php85-20261020 php tests/unit_mail_worker_schedule.php
docker exec bushisa-observed-php85-20261020 php tests/unit_legacy_worker_compatibility.php
docker exec bushisa-observed-php85-20261020 php tests/unit_mail_worker_config.php
git diff --check
```

Ignored runners verify owned containers before connecting; the checked-in
integration fixture and CI step provide a durable execution path with the same
guards. The identity helper performs local read-only metadata capture only;
it is not a production evidence collector. No secrets, proofs or message bodies
are retained in role reports/diagnostics. Carrier success is an injected fake
callback. SMTP-shaped CLI checks use an empty queue with socket carrier functions
disabled; they prove binding/no-work behavior, not live mail delivery.

| Executed group | Result |
|---|---|
| RP guarded role/identity/migration/legacy checks | **58/58 PASS**, including negative targets without a PDO driver, actual identity mismatch without cleanup writes, exact engine denials, linked expiry cancellation, fake purpose-separated delivery and fixture/global restoration. |
| Q11 | **74/74 PASS** after harness flag correction. |
| Q22 | **75/75 PASS**, reused from the initial successful run; later synthetic connection TLS repair does not affect this path. |
| Ordinary attempt erasure | **58/58 PASS** after harness flag correction. |
| Synthetic session fence | **105/105 PASS** after TLS/harness repair. |
| L06 HTTP/password reset | **25/25 PASS**, including distinct consumer processes/connections and one consumption; corrected synthetic TLS connection. |
| Worker shell/configuration | **4 DR17 + 1 WC01 + 12 MW checks PASS**. WC01 proves incompatibility; no real dispatcher or SMTP runs. |
| Native PHP syntax | Ten focused files PASS on Windows PHP 8.5.11. Native Windows DB execution NOT RUN. |

The five affected suites total **337 passing assertions**. Their original case
IDs, repeated boundary variants and concurrency records remain individually
listed in machine evidence; no matrix is declared globally complete.

The initial regression runner omitted `BUSHISA_TEST_HTTP_OVERLAP=true`, producing
Q11/QE barrier timeouts; those failed suites were not called PASS. Q22 already
passed and its result is reused. Initial session/reset concurrency failures
exposed the TLS connection omissions above. After repairs, only affected failed
suites were rerun. Exact initial/final outcomes are preserved in machine evidence.
An executed assertion total is not a count of completed policy/acceptance cases.

## Worker and legacy consequence

WC01 uses the owner-matched old wrapper bytes from Git in an isolated directory,
substitutes only its working directory, and invokes the **actual** candidate
production cleanup guard under PHP without a PDO driver. It observes exit 78
and no dispatch marker. No dispatcher, DB or SMTP runs. This is a successful
reproduction of **incompatibility**, not proof the mixed deployment is safe.
DR17 separately retains the four candidate-wrapper order/failure variants.

The candidate SMTP-shaped mail CLIs can bind the tested strict worker role on
the local fixture; real delivery and every legacy queue shape remain separate.
The old installed job must stay stopped across a separately approved coordinated
app/schema/worker transition. Keep broad consumers and reset default-off in
production; explicit synthetic test throttles do not adopt production values.

RP-L01/L02 preserve unknown anchors, NULL generation and inactive cutover; no
legacy completeness is enabled. The [cutover worksheet](legacy-cutover-preparation-2026-10-22.md)
maps all W01–W12 groups and later reset/session consumers to actual stop/drain
and source-evidence requirements. Its authentic B/W/H/C and enabling adapter
remain unverified. Approved chat survives unmatch and lifetime restrictions stay.

## Status and next work

HR01–HR03 have bounded local execution evidence; HR04 remains PARTIAL for full
D03 runtime route grants. Actual Azure role/secret/TLS/definer acceptance and
qualified review are not supplied by fixture roles. Reviewer/operator names
remain PENDING. Historical CI for `888a2c2` is unchanged and does not certify
this code. The new role/worker checks are wired into the existing direct-release
CI job; hosted execution is reported separately, not assumed green.

Release remains **NO-GO**: actual restricted-role acceptance, full-D03 route
coverage, legacy continuation/all-writer cutover, real ingress/write fencing,
compatible forward repair/fallback and independent/witnessed recovery remain.
DR-P02/03/04 remain NOT RUN; all 18 managed-stage cases remain DEFERRED/NOT RUN;
SC26 production storage/topology remains PARTIAL. No production change or real
mail occurred. PR 8 remains draft.

Next independent work is the actual runtime-route grant contract and bounded
legacy pending-attempt continuation design, plus the
[synthetic recovery rehearsal plan](existing-resource-recovery-options-2026-10-22.md).
No settled owner decision is reopened or new appointment invented.

Final local validation resolved **142 relative documentation links**, checked
eleven tested-source fingerprints and all nine unchanged owner-source digests,
and passed `git diff --check`. Workflow script/artifact references were checked
and the diff reviewed; no local YAML parser was available, so no workflow
parser or hosted-CI PASS is claimed. These checks do not alter acceptance totals.
