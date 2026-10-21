# Owner-observed schema and direct-release rehearsal

This continues `4e76a7de361f32a17062ea8189e875844ee10660` on
`codex/direct-production-release-prep-20261020`, preserving draft PR 8. It is
release preparation, **NO-GO for production execution**. Managed staging remains
DEFERRED with AZS01-AZS18 NOT RUN; SC26 production storage/topology stays PARTIAL.
No production SQL, grants, configuration, cleanup or mail was executed.

## Observation provenance and reconciliation

The owner supplied `MigrationHistoryWithActualSchemaData.docx`. A bounded,
DTD-disabled read of `word/document.xml` extracted 632 paragraphs; its snapshot
SHA-256 is `90ca70375d3fb2a3844bb80064cd1f6620224411d36eb8b490e94937cbede233`
(22,178 bytes). **SQL printed in the document was not executed.** The
[observation ledger](evidence/owner-production-schema-2026-10-20.json) and
[data-only schema fixture](../../tests/fixtures/owner-observed-schema-20261020.json)
retain the captured attributes without user records or credentials.

These are owner-collected observations, not independent agent production
execution. Settings capture: **2026-10-20 15:28:56.241928 UTC / 17:28:56.241928
Africa/Harare**; file fingerprints: **14:34:57 UTC / 16:34:57 Africa/Harare**.
Exact separate schema-query times were not captured. The repeated fingerprint
block is a duplicate, not another capture. These timestamps do not invent an
owner policy-acceptance time or reopen existing approvals.

| Observed field | Reconciliation and limit |
|---|---|
| PHP CLI 8.5.11; MySQL 8.0.46-azure | Replaces prior patch UNKNOWN. Web-effective PHP/session settings remain unverified. Local upstream 8.0.46 is not the Azure build. |
| Session/global UTC; REPEATABLE-READ; exact SQL mode | Both zones `+00:00`; `ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO`. No inferred application timezone overrides. |
| Server utf8mb4_0900_ai_ci; all 29 captured tables utf8mb4_unicode_ci/InnoDB | Compare column collations individually too; server collation does not replace table definitions. |
| Secure transport, binary log, function-creator trust enabled | Values all 1; no live setting changed to run migrations. |
| Certificate-verifying MariaDB-client connection | Owner observed TLSv1.3 / TLS_AES_256_GCM_SHA384. This does not prove production PHP PDO certificate verification or effective connection configuration. |
| Configured DB_USER has global administration and WITH GRANT OPTION | Confirmed excessive runtime privilege; separate runtime/worker/migration identities remain required. Account identifier is redacted in committed evidence. |
| Ledger 001-004 only | All four stored applied times `2026-10-11 17:14:33`; this records ledger contents, not complete physical-schema identity. Neither 005 nor 016 is declared production-applied. |
| 227 columns, 122 index entries, 13 FK rules, no captured triggers/routines | Attribute comparison below is exhaustive for the captured fields. Missing physical attributes remain a supplement gate. |
| protective_replay_events timestamps | `occurred_at` and `replayed_at` remain plain TIMESTAMP; pending 005 must widen precision. |

The test actually builds the pinned repository baseline
`0bf311fb6d6a298712fd4cd16d0722305516edd4` through 004 and compares **every**
captured column name/order/type/nullability/default/extra/charset/collation,
index name/uniqueness/sequence/column/prefix/type, FK name/parent/update/delete
rule, and table engine/collation. Result: **227/227, 122/122, 13/13 and 29/29
MATCH**, with no extra rows in those baseline comparisons. A separately
constructed metadata fixture matches the same counts. The
[local report](evidence/owner-schema-rehearsal-local-2026-10-20.json) records
each row and each candidate difference, rather than inferring equality from
four migration markers.

Candidate comparison intentionally differs: later migrations add stores/indexes
and widen nine captured timestamp fields (six request clocks, message evidence
purge, two protective replay clocks) to microseconds. `requested_at` remains
nullable, including its unknown legacy values. Original captured fields and
synthetic row values are preserved; widening `.000000` presentation is normalized
only for value comparison. Twelve new migration markers are expected; the
original four marker times are retained. Extra candidate rows are additive
schema metadata, not evidence of missing production events.

### Sampled application fingerprints

All **nine supplied SHA-256 values match raw Git bytes at `0bf311f`**, as well
as any identical blobs listed in the observation ledger. LF/CRLF variants were
checked; **no newline conversion was required**. `index.php` is also unchanged
in the candidate; other matching histories are individually recorded. The
sample covers config/db.php, operational/signup/mail helpers, migration and
cleanup/dispatch CLIs and the repository's WebJob script. There is no unmatched
supplied file hash. `signin.php` was absent in that historical source too:
the actual sign-in route is **login.php**; the earlier probe named the wrong
file. Absence is not evidence of a deleted deployed sign-in journey.

The [241-path full comparison](evidence/owner-schema-change-inventory-2026-10-21.json)
pins every old/new Git blob from known comparator `0bf311f` to implementation
`3dbcb50bbb8e3401e2d7688143c4d406dbf0d426`. It includes the entire accumulated
change, not just this repair. Evidence-only follow-up files do not alter that
application comparison; complete deployed identity is still unestablished.

This is a strong baseline lead, **not complete app identity**. Identical sampled
files cannot exclude mixed/untracked deployment files. A script under
`wwwroot/deploy/mail-webjob` does not attest the independently installed
`bushisa-mail` package. Obtain a privately preserved immutable whole code
artifact/manifest and actual installed worker script/package identity, excluding
secrets, sessions, uploads and user files. Do not repeat the nine sufficient
fingerprints or retrieve user data. Until then, the full deployed-to-candidate
delta and actual previous-artifact compatibility remain unverified.

### Minimal metadata supplement

Run only [supplemental-schema-metadata.sql](../../deploy/release/supplemental-schema-metadata.sql)
through the owner's existing verified metadata connection; it is prepared,
**not executed on production**. It requests the omitted FK column mappings,
index direction/visibility/expression, SQL-NULL default disambiguation, CHECK
constraints and omitted table options/partitioning. No user rows, token hashes
or mail bodies are requested. Use SHOW
CREATE TABLE only for affected named tables if that supplement disagrees.

Reconstruction currently uses baseline-derived FK columns, ordinary ascending
visible indexes, no unobserved CHECK constraints and SQL NULL for ambiguous
ASCII `NULL` defaults. Those are explicit assumptions. Auto-increment next
values are not needed for synthetic preservation and are not inferred from
production rows. Full physical-schema fidelity remains **PARTIAL** until the
supplement is reconciled; matching captured fields alone cannot close DR-P01.

## Reproduced defects and focused repairs

1. The unmodified pending 006 seed attempted to insert a surviving request's
   NULL `requested_at` into a NOT NULL timed-send field: synthetic reproduction
   returned **SQLSTATE 23000 / MySQL 1048**. Current 006 records timed sends only
   where their time is known, counts remaining surviving projections as a known
   minimum, and preserves higher existing counts on retries. It never invents
   a timestamp, verifies completeness or zeroes a lifetime budget.
2. Migration 007 had tightened the captured nullable timestamp to NOT NULL.
   Its helper now preserves nullability/defaults while widening precision;
   unknown-time projections cannot be converted into timed pair events. Initial
   repair testing exposed uppercase MySQL metadata keys; normalizing those keys
   fixed the actual OS05 failure. It was not a NULL application-value failure.
3. The interrupted PowerShell probe produced NullArray after Docker inspection
   failed (a sandbox/CLI failure), before a migration command ran. That error is
   **not** SQL 1048 or OS05. The local wrapper now refuses explicitly if Docker
   returns no valid owned-container object. The successful rerun uses actual
   MySQL/PHP results, not the failed probe as evidence.
4. A restricted mail identity could not pass a generic all-feature schema
   visibility check. Mail CLIs and bounded challenge cleanup now check their
   signup-purpose stores, leaving actual missing-field queries fail-closed.
   They no longer need to inspect unrelated private D03 tables just to dispatch.
   The [initial failure record](evidence/owner-schema-worker-failure-2026-10-20.json)
   is retained; no privileges were broadened to make that check succeed.
5. WebJob processes inherited the web runtime's DB credentials. The two mail
   CLIs now require a complete dedicated `MAIL_WORKER_DB_USER` /
   `MAIL_WORKER_DB_PASSWORD` pair in production, validate it before database
   loading and bind both atomically. Missing/partial/same-user/root/unresolved
   bindings fail closed. **This is a rollout prerequisite:** do not unquiesce the
   new worker until reviewed identities/settings are available. Existing sender,
   SMTP target, TLS, mail-purpose checks and default-off reset remain unchanged.

Only pending migration code/helpers are repaired for the observed 001-004
target. No new migration marker, destructive down-migration, marker deletion,
live backfill or immutable-protection bypass was introduced. Other environments
with applied markers must be reconciled independently, not forced to replay.

## Executed synthetic acceptance

Command: `powershell -NoProfile -File .tmp/run-owner-schema.ps1`, invoking
`php tests/integration_owner_schema.php` with existing disposable guards,
loopback `bushisa_test_release_observed_local`, and explicit
`BUSHISA_OBSERVED_TEST_ENGINE_SETUP=true`, `OBSERVED_TLS_REQUIRED=true`.
The ignored wrapper verifies the owned container image/label/health before
retrieving its synthetic credential. CI invokes the committed PHP test with
its separately guarded `bushisa_test_release_observed_ci` profile.

Executed local working tree based on `4e76a7d`, **29 PASS / 0 FAIL** at the UTC
instant in the report. Profile: Linux PHP **8.5.11**, upstream MySQL **8.0.46**,
exact owner SQL mode/UTC/RR/charset/collations, binary log/trust/secure transport
all 1. Private synthetic CA and hostname-verified PDO negotiated TLSv1.3 /
TLS_AES_256_GCM_SHA384. Image digest:
`mysql:8.0.46@sha256:7dcddc01f13bab2f15cde676d44d01f61fc9f99fe7785e86196dfc07d358ae2b`.
This does not reproduce Azure patches, service topology, actual production
grants or effective PHP TLS/session configuration.

| Exact cases | Executed assertion |
|---|---|
| OS01, OS01.TLS | Exact tested engine settings and verified local PDO TLS; CI's ordinary loopback profile does not claim OS01.TLS. |
| OS02.bootstrap, OS02, OS03, OS04 | Pinned historical bootstrap and all captured-field comparisons; independent reconstruction, four original markers/no triggers. |
| OS04.partial005, OS04.partial006, OS05, OS06 | Restricted migration identity, interrupted 006 SQL before its marker, safe resume through 016, all 16 skipped on rerun. |
| OS07, OS08 | Preserve all original synthetic rows/old marker times; replay precision widened without invented times. |
| OS09, OS09.null_history | Epoch inactive/genesis absent/legacy incomplete; NULL request time preserved, minimum count retained, no fabricated timed send. |
| OS10, OS11 | Approved chat survives unmatch; missing signup anchors remain unknown, active/pending identities not reborn. |
| OS12, OS13, OS14 | Untracked old SMTP envelope discarded before carrier; missing UPDATE grant rolls claim back; new anchored envelope fake-delivered once with empty terminal body and reset off. |
| OS14.CLI, OS14.cleanupCLI | Real CLIs with dedicated restricted worker, no work and socket functions disabled; broad policy cleanup stays gated. No SMTP connection/delivery. |
| OS15.immutable, OS15.worker, OS15.runtime | Immutable identity rewrite rejected; runtime and worker cannot CREATE TABLE. |
| OS16 | Real candidate login/API refuses request for incomplete legacy history (503, no request created). |
| OS17.counterexample | Real historical baseline HTTP API accepts the same request without pair_request_events: expected counterexample reproduced, **rollback INCOMPATIBLE**. |
| OS18, OS19 | Current and baseline native authenticated sessions lose API access after session-version bump; normal current login still works. This is not physical Azure session-copy proof. |
| OS20.restore | Owned fixture globals restored after the test. |

Affected lifecycle regressions: command
`powershell -NoProfile -File .tmp/run-owner-regressions.ps1 -Group lifecycle`;
Q11 **74/74**, Q22 **75/75**, attempt erasure **58/58** assertions PASS. Worker
guard unit command `php -n tests/unit_mail_worker_config.php`: **MW01-MW12 PASS**.
The [regression record](evidence/owner-schema-regressions-2026-10-20.json)
retains exact case/check results. These older regression connectors use
loopback without TLS; only the owned fixture's transport requirement was
temporarily disabled, then restored. They do not inherit OS01.TLS's claim.
Pair/importer command
`powershell -NoProfile -File .tmp/run-owner-regressions.ps1 -Group pair`:
L01b **245 checks / 8 overlaps**, L02a **396 / 11**, L02d **100 assertions**,
L02e **181 assertions / 17 races / 4 engine trials**, all PASS. The
[pair regression record](evidence/owner-schema-pair-regressions-2026-10-20.json)
retains case results and distinct process/connection evidence. Local checks
precede a new clean-candidate hosted run; historical `4e76a7d` CI does not
certify these changes. Existing A/LB/LC/QS/QE/QM/SC
matrix results are not relabelled by these OS tests. No independent recovery,
real legacy provenance, actual previous artifact or managed Azure acceptance
was executed.

## Identity/configuration changes prepared for review

No live grants are supplied for automatic execution. A qualified security/DB
review must choose real names, host restrictions, secret delivery, rotation,
definer retention and final per-table grants. The fixture proves the following
specific separation without SUPER, global admin or WITH GRANT OPTION:

| Identity | Tested boundary / required review |
|---|---|
| Fixture/bootstrap | Disposable-only schema/fixture/user setup, separate from all application execution. Never proposed as a live runtime identity. |
| Controlled migrator | Schema-scoped SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES, TRIGGER, CREATE ROUTINE, ALTER ROUTINE, EXECUTE; no DROP/global administration/GRANT OPTION. Actual 005-016 succeeds with binlog/trust=1. Inspect and preserve routine/trigger definers before disabling credentials; do not drop a required definer account. |
| Web runtime | No DDL. Tested original D03-table reads, selected login/profile/pair DML and fixed health-procedure EXECUTE. **Not a completed full-D03 write-grant audit.** Report, confession, media, evidence/recovery and reset paths require their exact approved table/column rights and negative checks before release. |
| Minute mail worker | Purpose-limited signup/challenge/mail/lifecycle/retention/receipt DML, three fixed health-procedure EXECUTEs, constrained cleanup_outbox column UPDATE. No ordinary chat/evidence browsing, schema DDL or account administration. Exact tested SHOW GRANTS are in the synthetic report. Additional jobs need separate reviewed rights, never wildcard grants to suppress an error. |

**21 October clarification after capture 09:** the worker description above
states the intended operational scope, not a proven account-administration
denial. `os_role` actually grants table-wide SELECT/UPDATE on `users`; OS13/OS15
did not test password/suspension-column denial. Preserve the historical results
and exact grants, but do not use them as least-privilege completion evidence.
The [current handover review](direct-release-handover-review-2026-10-21.md)
specifies the proposed column-contract/negative-path work, HR01–HR04 NOT RUN.
| Missing-privilege fixture | Deliberately lacks mail_outbox UPDATE; proves atomic claim failure before fake carrier. Not a proposed operational identity. |

Web requests retain `DB_USER` / `DB_PASSWORD` (reviewed runtime values). The
mail-only processes use the two new `MAIL_WORKER_DB_*` values while retaining
the same reviewed DB_HOST/DB_NAME/DB_SSL_CA. Migration credentials are private
release-process overrides, unavailable to runtime/WebJob. Merely configuring
different names does not prove their grants are least privilege. No secret
value belongs in Git, command output or this document.

The local fixture establishes process binding and SQL grant behavior, **not
secret isolation**. Review whether the selected worker-secret delivery also
makes credentials visible to web/SCM processes in the shared App Service.
Do not describe two app-setting names as a separate security boundary or
silently broaden either identity to compensate. Actual secret placement,
effective access and definer lifecycle remain configuration/security gates.

## Exact implementation-candidate hosted CI

Implementation `3dbcb50bbb8e3401e2d7688143c4d406dbf0d426` passed all six jobs in
[feature push 39504794381](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39504794381)
and [draft PR run 39504799952](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39504799952).
PR 8 test checkout is `e67b43137a971c939d83e09737f1744d631bec0a`; its parents
and tree relationship, clean report attribution and downloaded artifact
digests are verified in the [hosted record](evidence/owner-schema-hosted-ci-2026-10-21.json).
Both reports show **28 owner-schema PASS** plus **16 generic release PASS**,
PHP 8.5.11/upstream MySQL 8.0.46. The hosted ordinary-loopback profile does
**not** execute OS01.TLS; the local 29-case report alone covers verified PDO TLS.
The remaining jobs cover lint/units, SMTP/outbox/lifecycle/report/pair/import
and session regressions, rendered route overlap, L06 browser/HTTP and guarded
stage implementation. Those stage implementation checks are not AZS execution.

The follow-up commit records this implementation evidence and configuration
limits only. Its own head/run attribution belongs to the matching draft PR;
the committed implementation record must never be relabelled to that later
SHA. Main's deploy/manual workflow was inspected and unchanged; only this
feature branch was pushed. No production or managed-staging acceptance follows.

## Legacy transitions and rollback stop conditions

**Pair ledger:** 006 preserves known minimum counts/declines. 007 marks existing
pairs incomplete; missing records never mean zero prior requests. The epoch
stays inactive; no real-data enabling importer exists. Unknown all-writer
cutover affects future eligible pairs too. Requests/restarts requiring complete
history remain fail-closed. Need authentic prior-event/cutover provenance and
an approved bounded recovery/enablement design; never invent events or activate
completeness as a release shortcut. Existing approved chats can continue subject
to independent account, ban, block and revocation restrictions.

**Signup/mail:** untracked old envelopes are not rewritten into new proofs or
delivered under fabricated anchors. Drain only still-valid work using the old
worker before the quiet window, then stop it and reconcile claimed SMTP work.
Obtain trusted creation/first-approval evidence for affected unfinished accounts
or retain an explicit unresolved continuation obligation. A new issuance must
not reset unknown Q11 clocks. Existing active accounts remain active. Inspect
only aggregate impact counts/statuses if needed; never export addresses, code
hashes or bodies. New worker activation requires its dedicated credentials and
reviewed pending-attempt continuation; no production resend/backfill was run.

**Rollback:** OS17 is a concrete reason to reject an unrestricted code-only
rollback to `0bf311f` on the forward schema. On failure, keep ingress, all old/new
writers and workers stopped. Restoring an artifact for private diagnosis must
not reopen traffic. Proceed only with a separately verified forward repair or
authorized data recovery preserving post-cutoff protective actions. The actual
previous bundle is unknown and DR-P02 stays NOT RUN for that artifact; do not
translate a known unsafe comparator into a claim about all deployed files.
Fresh backup/media/config/independent replay and witnessed recovery remain
separate DR-P03 requirements. Code restoration does not reverse schema changes.

Smallest remaining owner actions: return the metadata supplement and complete
app/installed-WebJob artifact identity; identify accepted security/DB/operator
primary and backup for the reviewed grants/maintenance/recovery set; provide
trusted legacy continuation/cutover evidence where it exists. Missing evidence
is reported as missing, not a request to approve invented history. Engineering
can finish grant-path negatives and a bounded legacy continuation design using
settled decisions; no new 48-question policy review is needed. Reset activation
throttles/Q40 choices, delivery/support/coverage, qualified reviews, NGINX paths,
full D03 journeys and real recovery remain their existing separate gates.
