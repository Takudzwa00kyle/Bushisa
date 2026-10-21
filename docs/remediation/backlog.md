# Bushisa P0-P2 Remediation Backlog

Updated: 2026-10-21, Africa/Harare.

## Current release path: controlled direct update, staging deferred

All 01-09 files and the original DOCX are now
[reconciled](completed-owner-evidence-reconciliation-2026-10-21.md). No repeated
SQL/hash collection or passed acceptance-suite rerun was needed. The six
physical-schema assumptions are resolved: 13 FK mappings, 122 index attributes,
51 SQL-null checks, 29 table options and no CHECK/partitioning all match.
**No more SHOW CREATE TABLE is needed from this capture.** Historical report
labels remain attributed to their tested commits. DR-P01 stays PARTIAL for
actual Azure restricted grants/definers/PHP PDO/service acceptance.

The 75 captured Git-tracked application files all match raw baseline `0bf311f`
bytes; 45 also match candidate `888a2c2`. The 54 MISSING paths comprise 53
candidate additions and one dependency target, none baseline-tracked. Of 18
dependency hashes, 17 match explicit local vendor files. The installed.php hash
is now accounted for by generated
root-reference metadata matching baseline `0bf311f`; no new production read of
that file is needed. Capture 08 has 154 file names/12 top directories: all 93
known hashes are represented; 61 extra paths classify as Playwright (1), expected
PHPMailer package paths (59) and unclassified hostingstart.html (1).
[Detailed classification](deployed-code-inventory-reconciliation-2026-10-21.md)
keeps deploy/sql/database/extension/private exclusions explicit. Remaining
content/build provenance and non-atomic capture prevent a complete hosted
source SHA claim. The installed worker is now located and its old `run.sh`
identified; whole package identity remains unresolved.

The preserved worker ZIP has matching `run.sh` and **nonmatching settings**.
Capture 07 establishes exactly `{"schedule":"0 * * * * *"}`, semantically
equivalent to ZIP settings. Local compact JSON matches the installed digest;
the ZIP bytes remain different. Exact ZIP deployment provenance is unverified. **No preserved application deployment archive was found** in the
inspected locations. CI-results ZIPs are not rollback bundles.

[Capture 09 is reconciled](code-gap-reconciliation-2026-10-21.md): all 65 new
hashes match (six Git baseline, 59 locked PHPMailer); three missing later audit
helpers do not belong to the baseline runtime. All 13 new names are accounted
for. Captures 05/09 cover 158 distinct hashed paths: 81 baseline Git and 77
dependency paths, including the generated installed.php metadata comparison.
The deployed source settings copy matches the ZIP while installed App_Data
bytes remain different and semantically equivalent. No exact ZIP provenance
or complete application artifact identity is claimed. The bounded collection
is complete: do not repeat captures or request bodies/hashes of the eight
documentation/licence/version names. hostingstart stays unclassified; resolve
its retain/serve or candidate-exclusion/routing disposition for release rather
than request another broad audit. Private/external/non-atomic coverage limits
remain explicit. No worker or discovered code was run.

The [local handover review packet](direct-release-handover-review-2026-10-21.md)
prepares worker compatibility, role separation, all-writer cutover and recovery.
HR01–HR04 are proposed engineering checks, all NOT RUN. Existing fixture
table-wide users UPDATE does not establish worker password/suspension denial;
derive/test a minimum column contract and full-D03 runtime negatives next.
No synthetic role or no-work dispatch result establishes live acceptance.

**Mail rollout blocker:** the installed `set -e` wrapper would stop at the
candidate production-gated `cleanup_policy.php` before dispatch. A separately
approved release must quiesce the old job and install the reviewed mail-only
wrapper with dedicated accepted worker credentials. Keep the guard; no live
change is made now. Review separate runtime/worker/migration grants, definer
lifecycle and secret delivery; different settings in a shared app do not prove
credential isolation. Full-D03 grant-path negatives remain engineering work.

**DR-P02 NOT RUN for the unknown complete previous bundle.** OS17's baseline
API counterexample still proves unrestricted old-code rollback can omit pair
provenance. Keep all writers closed during separately approved fallback; only
a tested compatible artifact or forward repair/recovery preserving restrictions
can reopen traffic. Authentic pair all-writer cutover, incomplete-history
enablement design and legacy signup/mail/anchor continuation remain blockers;
no invented history, reset clocks or implicit ledger activation is allowed.
Fresh DB/media/config/worker/session recovery coverage, independent replay and
witnessed recovery remain DR-P03/open operational requirements.

The [observed-schema rehearsal](owner-schema-release-rehearsal-2026-10-20.md)
has 29 local PASS on PHP 8.5.11/upstream MySQL 8.0.46, including local verified
PDO TLS. It is not rerun or relabelled by this documentation checkpoint.
Implementation `3dbcb50` retains its
[separate exact CI](evidence/owner-schema-hosted-ci-2026-10-21.json), 28 hosted
owner-schema plus 16 generic release cases per run; local TLS is not hosted.
Latest tested source `888a2c2` passed all six jobs in
[push 39506098991](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39506098991)
and [PR 39506110044](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39506110044),
merge `2bdd01a214d0665ddd446a823d4dda61988006ca`. This is historical candidate CI,
not CI for later documentation heads. PR 8 remains draft, open and unmerged at
source `888a2c2`. Earlier `803a03a`/`4e76a7d` CI records remain unchanged.

The owner chose the [controlled direct update](direct-production-release-preparation-2026-10-20.md)
on existing resources; no second paid environment is required for preparation.
Reset stays default-off, stage/fake/test controls stay absent in production,
and no broad cleanup is activated. AZS01-AZS18 remain **NOT RUN / DEFERRED**;
SC26 production storage/topology **PARTIAL**. Existing A/LB/LC/QS/QE/QM/SC and
P0/P1 results/gates and full D03 scope are unchanged. Actual role acceptance,
NGINX/private paths, sessions/storage, delivery/support/coverage, qualified
review and recovery remain open. Next independent engineering work: the packet's
mail-only column-grant/negative-path and full-D03 runtime checks, then a
bounded legacy continuation/cutover design, using settled decisions. Next owner
action: name accepted DB/security reviewer and maintenance/recovery primary plus
backup, and the existing-resource recovery method/capacity they can validate;
no additional production file collection is requested. Current release
recommendation: **NO-GO**.

Whole-roadmap reconciliation at candidate `d381e56`: [P0–P2 status and critical path](p0-p2-status-2026-10-18.md). This is a documentation audit; it preserves historical evidence and leaves the existing acceptance matrices and release gates unchanged.

SC26 follow-up on `codex/sc26-physical-io-fence-20261018`, based on audit
`774547cf`: the local synthetic physical-I/O race is now reproduced and
fail-closed under a process fence. See the [SC consumer evidence](session-copy-consumer-evidence-2026-10-18.md)
and [roadmap addendum](p0-p2-status-2026-10-18.md#sc26-follow-up-after-774547cf).

## Azure compatibility and staging-plan checkpoint

Implementation follow-up: [guarded stage origin, private fake carrier and
managed-stage driver](guarded-staging-implementation-2026-10-20.md) supply the
separate default-deny configuration path, actual UUID/principal/marker/grant
checks, explicit fixture preparation and non-destructive execution driver.
The new SG matrix records local implementation tests separately from managed
AZS01-AZS18, which remain **NOT RUN**. Production SMTP/origin/default-off reset,
old loopback guards, Q47/Q40 decisions and SC26 production **PARTIAL** remain.
No funded/accepted Azure stage manifest or operator appointment is invented.
See the follow-up for exact source, versions, commands and hosted CI;
historical `7326716` is not new implementation evidence.

Implementation candidate `a0289c1` now has clean local **33 integration / 10
driver PASS**, **51 pure guard PASS**, and **five hosted jobs successful in both
feature-push and PR runs**. [Exact CI/source/merge evidence](evidence/guarded-staging-hosted-ci-2026-10-20.json)
is separate from the historical candidate. Local PHP 8.5.11/MySQL 8.0.45 and
hosted stage PHP 8.5.11/MySQL 8.0.46 are synthetic profiles; actual Azure patch,
deployed artifact/schema, sessions and storage remain unverified. Draft PR 7
is not merged. The metadata-only follow-up's exact-head CI is reported on that
PR; it does not alter the implementation-run attribution.

The [readiness audit and two-lane staging plan](azure-compatibility-staging-plan-2026-10-19.md)
continues from tested `7326716` on a separate documentation branch. Read-only
GitHub metadata reconfirms both four-job CI successes and PR merge checkout
`3dd1318`; no passed tests were rerun. The independent-access failure at
`0187c43` remains in its [historical capture record](evidence/azure-compatibility-metadata-2026-10-19.json).
The [owner-provided observations](evidence/azure-owner-observations-2026-10-20.json)
now record Azure Portal screenshots and application-container CLI output
collected **19-20 October 2026, Africa/Harare**, transcribed by the owner, not
independently captured by the agent. They establish observed Basic B1/one
instance/zero slots, configured PHP 8.5 and CLI 8.5.11, private MySQL 8.0/configured
TLS, seven-day backups and one successful no-work `bushisa-mail` run. Current
app/worker source SHA, deployed migrations, exact MySQL patch, SQL grants/isolation,
web-effective sessions, actual storage/fencing/recovery and backup redundancy
remain UNKNOWN. OneDeploy UUID is not a Git SHA; zero counters are not delivery,
erasure or lifecycle-coverage evidence. No cloud/DB/settings/mail operation
occurred in this update.

Managed staging is **GATED** by the actual accepted destination/identity,
configured guarded origin/carrier/driver, storage/recovery and operational
evidence. STG-I01/STG-I02 have the linked bounded implementation; production
origin/fake-mail protections and existing proof/deadline rules remain. The
existing loopback harness is a different procedure, never managed Azure
acceptance. AZS01-AZS18 are all **NOT RUN**, separate from existing matrices.
SC26 remains PARTIAL for production/shared-store topology. Q47 production
throttles, Q40 configuration, funded stage topology, accepted operators and
qualified reviews remain open. The exact observed
`APPLICATIONINSIGHTS_CONNECTIONS_STRING` has no consumer in the scoped candidate
code search; no value, correction or activation was attempted and D10 stays
GATED. The [final bounded engineering brief](azure-compatibility-staging-plan-2026-10-19.md#41-final-bounded-engineering-brief-stg-i01--stg-i02--managed-driver)
defines the implemented scope: default-deny stage destination/origin admission,
separate CLI fake carrier and guarded managed-stage driver, with disposable
execution evidence in the follow-up. Owner action before managed
execution: accepted primary/backup, funded isolated app/compute (B1 has no slot),
destination manifest, redacted app/worker provenance and the unread NGINX
advisory. Remaining metadata should be collected selectively, without repeating
supplied facts. D03 scope and existing policy approvals remain unchanged.

## Current Q11 synthetic physical session-copy consumer checkpoint

Continued from `5e7c5a5` on
`codex/q11-synthetic-session-copy-consumer-20261018`. The
[implementation/evidence](session-copy-consumer-evidence-2026-10-18.md),
[binding/write-fence foundation](session-binding-write-fence-evidence-2026-10-18.md)
and [SC01-SC40 contract](account-session-copy-contract.md#7-planned-acceptance-matrix)
record only the Q11 `session_copies_v1` physical consumer for positively covered
new attempts in an isolated synthetic filesystem store. It uses the original
Q11 expiry +24h boundary and exact generation/version manifests. Bounded stages
seal, capture, persist per-object intent, delete and verify exact absence, then
write a scoped receipt. Retries reconcile missing objects; unknown/changed
objects and incomplete coverage fail closed. Filesystem and SQL receipt updates
are separate failure domains.

Current SC status: **15 PASS / 19 PARTIAL / 6 NOT RUN**. Only the local SC26
physical-I/O subcase advanced to PASS; SC26 remains PARTIAL overall for
production/shared-store fencing and handler evidence. The repair consumer ran
37/37 on Linux and 36 PASS / 1 NOT RUN on Windows (symlink/reparse creation).
Windows regressions: binding 104/104, Q11 74/74, Q22 75/75, erasure 57/57;
Linux: 105/105, 74/74, 75/75, 58/58 respectively. Exact commands/times and
two-process ordering are linked in the evidence document. Hosted CI is
**NOT RUN**. Historical A/LB/LC/QS/QE/QM
results and candidate CI attribution remain unchanged.

No anonymous, legacy, Q14 or production copies are consumed. The production
handler, scheduler, independent recovery publisher and witnessed restore remain
gated. Real handler/root/instance inventory, cutover/provenance, qualified
privacy/security/safety review, accepted operators/backup/alerts, recovery
durability and witnessed restore remain open. No real data, production database,
email or production session store was accessed; no production scheduling, push,
merge or deployment occurred.

Current bounded checkpoint: **L06 reset challenge, outbox and sessions** is
implemented locally on `codex/l06-password-reset-20261019`; the
[rendered browser and hosted-CI follow-up](l06-browser-hosted-ci-evidence-2026-10-19.md)
records six local browser PASS, 25 corrected HTTP PASS and all four hosted CI
jobs passing on implementation candidate `e7501fe`, with exact source/PR-merge
attribution and historical failures preserved. See the case-level
[validation record](evidence/l06-password-reset-validation-2026-10-19.md) and
[machine-readable results](evidence/l06-password-reset-local.json). The next
existing roadmap task is **P0-03/P0-08 candidate configuration and staging
acceptance**, after the required authorized read-only environment capture and
named reviewers/operators are available. Actual device/manual witnesses and
staged mail/recovery acceptance remain open; production access or enablement is not implied. L06
does not close L03's remaining production/recovery scope.

Exact historical owner-acceptance UTC/Harare timestamps were not captured. No
drafting or run time is substituted as an approval time; settled decisions and
unrelated release gates remain unchanged.

## Previous checkpoint: new-attempt session binding / write-fence

Continued from `077b8c78b0b556c887b241b9666ce24efc1eebfe` on
`codex/session-binding-write-fence-20261018`. The
[focused implementation/evidence](session-binding-write-fence-evidence-2026-10-18.md)
and [SC01–SC40 contract](account-session-copy-contract.md#7-planned-acceptance-matrix)
record trusted generation/version admission, minimized indexed copies,
reservation-before-I/O, explicit/lazy/implicit write fences, regeneration and
one-use verification/onboarding handoffs. The Q11 component retains its original
receipt/expiry+24h deadline; neither QE nor Q22 completion means session erasure.

SC status: **12 PASS / 18 PARTIAL / 10 NOT RUN**, limited to the supported
synthetic adapter. Local native/Linux results and exact commands are linked;
hosted candidate CI is **NOT RUN**. A/LB/LC/QS/QE/QM and historical CI attribution
remain unchanged. No legacy mappings, production handler, physical consumer or
operational recovery provider are enabled.

Next bounded engineering: **Q11-only `session_copies_v1` physical consumer for
the guarded synthetic namespace**. Add bounded durable claims and exact
generation/copy/epoch fences, unpublished-intent/alias reconciliation,
conditional absence verification, retries/component receipts and minimal
recovery ordering. Execute the remaining physical/crash/duplicate-worker cases;
preserve newer generations and original deadlines. Existing Q11 approval is
sufficient; no new owner decision is needed for that bounded slice.

P01 production handler/topology/proposed numerical Q40 settings and P02 general
nondeletion/anonymous/mapping physical retention remain pending for their wider
scope. Actual roots/permissions/instances, writer cutover/legacy provenance,
key custody, ban/reset/privileged integration, independent current protection
publication/replay and witnessed restore remain engineering/evidence gates.
Qualified reviewers and operators/backup/alert coverage remain unassigned or
unverified. No production/session-store access, real mail, production scheduling,
push, merge or deployment occurred.

## Historical account/session-copy contract checkpoint

Continued from `9053428ad9622feddb7fe5b4d955cfe947a1f313` on
`codex/account-session-copy-contract-20261018`. The
[focused inventory and contract](account-session-copy-contract.md) maps tracked
session/authentication writers and guards, pending/privileged contexts,
account generations versus authorization/copy versions, late-write fencing,
scoped physical cleanup and restore protections. Design is ready for engineering
review; no implementation, independent review, store inspection or functional
test was performed. **SC01–SC40 are all NOT RUN.** A/LB/LC/QS/QE/QM evidence,
approval status and partial/external gates remain unchanged; no new CI run.

Concrete gaps: no generation-bound session index or managed commit fence;
email is persisted; polling refreshes idle state; suspension lacks a durable
session-version revocation; logout-all/reset and operational MFA/authority
integration remain absent. Production handler/root/instance/copy coverage and
independent replay/durability/witnessed restore are UNVERIFIED. Q40's proposed
settings and unresolved nondeletion/anonymous physical-retention periods remain
explicit; no settled Q11/Q14 clock needs reapproval.

First bounded implementation: **new-attempt session binding and write-fence
foundation**, with a guarded synthetic managed-handler adapter, current
generation/version checks, explicit/implicit write and handoff coverage, and
original Q11 session-copy obligations. Keep production handler activation,
legacy mapping/sweeps, broad physical cleanup, pending settings and external
acceptance gated. No database/session-store access, real data/mail, scheduling,
push, merge or deployment is authorized by this documentation checkpoint.

## Historical Q22 terminal challenge/mail checkpoint

Continued from `1c3f5874d4e2e5e832275b916bbda245ab5d31db` on
`codex/q22-terminal-mail-cleanup-20261018`. The
[focused contract and evidence](q22-terminal-mail-cleanup-evidence-2026-10-18.md)
record original terminal anchors, immediate secret/body erasure, <=24h challenge
detail cleanup and seven-day code-free mail metadata removal. Migration 013
binds only actual new writer events; no legacy anchor/generation is invented.
Bounded per-record transactions, immutable source identity, durable progress,
minimal receipts and late-result reconciliation preserve Q11/QE safety,
rate-limit, account/onboarding and recovery obligations.

The separate QM01–QM32 local matrix is **32 PASS / 0 PARTIAL / 0 NOT RUN**
(75 assertions/six overlaps per profile); this does not relabel
A/LB/LC/QS/QE or close their partial/external variants. Operational cleanup
remains fail-closed without a real recovery provider. Unknown carrier outcomes
remain explicit, and fixture adapters do not prove independent storage,
qualified review, named coverage or witnessed restore. Candidate CI is NOT RUN.

Next bounded engineering at that checkpoint: **account/generation-to-PHP-session copy inventory and
cleanup/revocation contract**, addressing the explicit remaining Q11 live-copy
gap while preserving unrelated sessions and proposed session-setting boundaries.
Existing erasure decisions need no reapproval. Actual handler/copy inventory,
legacy provenance, named operations and recovery/review requirements stay open.
No production scheduling, real mail/data, push, merge or deployment occurred.

## Historical ordinary attempt-erasure checkpoint

Continued from `455f1fc78c03f86b72bf076f786267337904a7ac` on
`codex/q11-attempt-erasure-20261018`. The
[bounded consumer and evidence](q11-attempt-erasure-evidence-2026-10-18.md)
define `erase_attempt_v1`: generation-bound ordinary identity/credential/profile/
preference/answer/settings erasure, linked challenge/outbox contact removal and
staged fixture-object deletion. The protected account ID/status is kept; no
`users` DELETE/cascade, safety/consent/history reset or general account deletion.
Migration 012 binds only actual new registrations, never legacy rows.

Separate **QE01–QE28 = 27 PASS / 1 PARTIAL / 0 wholly NOT RUN** for the stated
local variants. The Windows symlink-creation variant is NOT RUN; Linux symlink
and both hardlink checks pass. Durable claims/cursors, actual-worker overlaps,
rollback/crash/retry, in-flight mail and stale onboarding work are recorded there.
Operational erasure fails closed while trusted recovery/storage providers are
unavailable. SQL receipts and isolated fixture storage are not independent
durability, private-media authorization or witnessed restore proof.

Next independent engineering at that checkpoint: **Q22 original terminal anchors and residual
challenge/code-free mail metadata cleanup**, using the already-approved <=24h/
7d rules, preserving in-flight receipts and recovery protection. Actual legacy
provenance, named operators/coverage, independent copy bounds and qualified
reviews remain gates. No production scheduling, real data/mail, push, merge or
deployment occurred; A/LB/LC and historic QS evidence remain unchanged.

## Historical Q11 signup-expiry checkpoint

Continued from `be91e06004552004311f5bc6c312f20dbad04091` on
`codex/q11-signup-onboarding-expiry-20261017`. The
[focused implementation/evidence](q11-signup-expiry-evidence-2026-10-18.md)
records new-account creation +72h and first-email-approval +7d anchors,
account-locked expiry/session revocation, atomic challenge/mail cancellation,
explicit delivery claims/in-flight outcomes and pending expiry +24h live-erasure
obligations. Migration 011 is additive and does not backfill legacy clocks.
Unknown/contradictory anchors cannot authorize automatic deletion or continuation.

Separate **QS01–QS24 = 24 PASS / 0 PARTIAL / 0 NOT RUN** for the stated local
variants: both established profiles pass 74 assertions/seven observed overlaps,
fresh/010-upgrade/repeat migrations and affected regressions. These checks do not
complete account erasure, SMTP/inbox acceptance, independent recovery or review.
A/LB/LC results and qualified-review/staffing gates remain unchanged. No live
database, real email, production scheduling, push, merge or deployment occurred.

At that checkpoint the next bounded implementation was the **trusted Q11 attempt-data cleanup
consumer**: map covered ordinary identity/credential/profile/preference copies and
FK effects, erase them against the original expiry +24h obligation, preserve
scoped evidence/protective history, and record verified purge/recovery receipts.
It can proceed under existing approvals. Actual legacy provenance, named
operators/coverage, independent durability/copy bounds and qualified sign-offs
remain external evidence gates. Do not extend into proposed retention categories.

## Historical L03a text-evidence lifecycle checkpoint

Continued from `b521b678e307e06097b2b60c7bbeb333e93bf32e` on
`codex/l03a-text-evidence-lifecycle-20261017`, preserving a clean starting tree/index.
The [focused implementation/evidence](l03a-text-evidence-lifecycle-evidence-2026-10-17.md)
adds immutable trusted-event case/item clocks, scoped urgent/joint hold/release,
bounded atomic text purge/receipts and guarded recovery interfaces. Operational
MFA/notice/appeal authority and independent journal providers are unavailable;
their production operations stay fail-closed. Eleven additive tables do not
backfill old timestamps, certify legacy history or authorize production cleanup.

Separate **LC01–LC18 = 13 PASS / 5 PARTIAL / 0 wholly NOT RUN**. Both local
profiles pass 122 lifecycle assertions/seven observed overlaps, 13 preconnection
guards, report regressions of 245 assertions/eight HTTP overlaps and pair regressions of 396 assertions/eleven
HTTP overlaps. The real engine1205/three-attempt trial is not a deadlock claim.
A01–A45 **24/19/2** and LB01–LB33 **21/12/0** remain unchanged. Partial variants,
qualified review/staffing, actual copy inventory/independent durability and
witnessed restore stay open; local JSON/SQL receipts are not operational proof.

At that checkpoint the next independent bounded slice was **Q11 incomplete-signup/onboarding anchors
and secret cancellation**: original creation/first-approval deadlines, access
denial and atomic challenge/mail cancellation without resend/read renewal,
unknown historical anchors explicit and guarded synthetic tests. Keep existing
immediate terminal erasure/settings. Do not adopt proposed Q22 defaults, Q12
anti-replay, Q16/Q23/Q26 schedules or a guessed inactivity warning. Broader
account/media erasure, safe import compaction and production recovery integration
remain separate. Existing approvals permit this engineering; no P1–P6 reapproval
or additional 48-question review is needed.

## Historical L02e mixed-writer and compatibility checkpoint

Continued from `734cc34d6be7eff697fb675761d447aa1d20e6a0` on
`codex/l02e-mixed-import-races-20261017`, preserving a clean starting tree/index.
The [focused implementation/evidence](l02e-mixed-races-compatibility-evidence-2026-10-17.md)
records **17 mixed HTTP/importer races and four genuine engine trials per profile**
on Windows PHP 8.5.11/MySQL 9.6.0 and Linux PHP 8.4.26/MySQL 8.0.45. Distinct PIDs,
SQL connections and engine-observed waits prove overlap. Real 1213 deadlocks,
1205 row timeouts and advisory waits are distinguished; bounded retries roll back
atomically and leave duplicate-free receipts/events. Restricted-role checks and
additive migration 009 fix live trigger-health visibility without runtime DDL.
The offline planner stays offline and synthetic imports never enable legacy pairs.

Current separate LB01–LB33 totals: **21 PASS, 12 PARTIAL, 0 wholly NOT RUN**.
Only LB13/LB29 become PASS; remaining variants stay individually open. A01–A45
remains **24 PASS, 19 PARTIAL, 2 NOT RUN**. Pair/report regressions pass on both
profiles; exact final-source runs, counts, grants and validation are linked above.
No known defect remains in the bounded supported L02 synthetic path. Next is
the independent **L03 cleanup and recovery replay** inventory/implementation;
authentic sources/cutover, actual production runtime/roles/TLS, qualified review,
staffing and independent restore proof remain external gates. No settled owner
decision needs reapproval; no whole D-number, production migration or real import
is accepted by this checkpoint.

## Historical L02d synthetic transaction checkpoint

Continued from `12b8ff70301fbad8c8eb8da20bd0f7982a7ba5ae` on
`codex/l02d-synthetic-import-20261016`, preserving a clean starting tree/index.
The [focused implementation/evidence](l02d-synthetic-import-evidence-2026-10-17.md)
records **100 import assertions PASS**, five distinct CLI/process/connection
overlaps, **34 pure guard assertions PASS**, **105 unchanged offline assertions
PASS**, affected pair/report regressions **396/245 PASS** with 11/8 separate HTTP
overlaps, and fresh/upgrade/repeat additive migration 008. All are local synthetic
evidence with fake mail, not remote CI or production/runtime/provenance acceptance.

The executor is separate, CLI-only/default-deny, bound to an explicitly authorized
local disposable fixture. Private facts, exact monotone floors, receipts and keys
commit pair-atomically; current state/expiry, retry/conflict, rollback and lost-result
checks preserve restrictions. It never certifies legacy history, grants access,
modifies sessions or clears blocks/bans. The offline planner remains offline.

Historical L02d LB01–LB33 totals: **19 PASS, 14 PARTIAL, 0 wholly NOT RUN**;
PARTIAL rows explicitly retain unexecuted variants. The A01–A45 matrix remains
**24 PASS, 19 PARTIAL, 2 NOT RUN**. L02e subsequently executes mixed HTTP/importer
concurrency, genuine deadlocks and lock/privilege compatibility checks. Authentic
sources/observed writer cutover, qualified review/named roles and full L03 lifecycle/
independent recovery remain separate gates. No settled owner answer needs reapproval.

## Historical L02c offline implementation checkpoint

Continued from `b4a041b067606437f43caa485eedb33768500d83` on
`codex/l02c-offline-history-planner-20261016`, preserving a clean starting tree.
The [offline validator/planner and exact evidence](l02c-offline-history-planner.md)
record **105 synthetic assertions PASS**. The distinct LB01–LB33 matrix is
**13 PASS, 13 PARTIAL, 7 NOT RUN**; application, actual provenance/cutover and
recovery variants stay open. Earlier A01–A45 totals remain 24/19/2 unchanged.

The strict synthetic schema/parser, scoped fixture catalogue, immutable identity/
observation checks and checkpoint/native-tail planner have no database adapter,
apply mode or runtime permission effect. Actual CLI isolation, input hashes and
repeat determinism prove the offline path; they are not SQL read-only-role,
concurrency or production-authenticity evidence. No policy choice is reopened.
Qualified review, actual source/cutover capture, retention/access and L03 recovery
remain gates; no database, production, mail, merge or deployment operation occurred.

## Historical L02b contract checkpoint

Continued from `600149761d43af10e69944b8390796387e68a688` on
`codex/l02b-legacy-history-contract-20261016` with a clean starting tree/index.
The [legacy-history evidence/import contract](l02b-legacy-history-import-contract.md)
was ready for engineering review: admissible provenance/completeness, all-writer
snapshot/cutover, guarded read-only planning, stable fact identity, pair-atomic
non-enabling merge/recovery and 33 synthetic acceptance cases, all **NOT RUN at b4a041b**.
No importer/schema was implemented, database accessed, history imported or pair
enabled. Actual production cutover and legacy provenance remain UNVERIFIED.

L02a's [historical local evidence](l02a-implementation-evidence-2026-10-16.md)
remains **24 PASS, 19 PARTIAL, 2 NOT RUN** for A01–A45. Its assertions/worker runs
and previous documentation checks do not execute LB01–LB33; only the separately
recorded L02c run updates their applicable scopes. Settled policy is
unchanged; qualified review, retention, recovery and operational gates stay open.

## Historical L01b local implementation checkpoint

Continued f7e9f8c on codex/l01b-report-intake-20261016 with a clean starting tree.
The [implementation/evidence record](l01b-implementation-evidence-2026-10-16.md)
records authorized text-target proof, actual/ambiguous attribution, idempotent
receipts/addenda, atomic fixed probation/renewal, request allowance integration
and report-linked clearance plus creator removal. Forward migration 006 preserves
existing rows; no old report gets retrospective probation. Local verification:
**245 assertions, eight distinct-worker overlap scenarios, fresh/upgrade migrations**.
Current A01–A45 statuses distinguish PASS, PARTIAL and NOT RUN. Old CI and the
38-link documentation check are not evidence for this implementation.

No P1–P6 owner answer is outstanding. Qualified privacy/security/safety review,
real MFA, named operators/backups, human recovery, sanctions/appeals, coverage,
L02 legacy pair-history/grant alignment and full L03 cleanup/replay remain gates.
No production configuration, real mail, live database, push, merge or deployment.

## Latest P1-P6 owner decisions

The [register approval record](decisions.md#p1-p6-explicit-owner-decisions) now
accepts all six choices as D04 Decisions 28-33: valid-attribution probation,
post-expiry renewal, review-gated report-attached blocks, self-primary rejection
with human recovery, severity bans and staffing-conditional review targets.
Policy acceptance does not implement, independently review or staff a workflow.
No exact acceptance UTC/Harare timestamp was captured; none is invented.
D04/D05/D12 and operational release gates stay open.

## Earlier explicit owner amendments: restoration and Reply-To

[The two-answer record](owner-conflict-decisions-2026-10-16.md) approves Decision
27 (fresh agreement from both before restoring an existing match) and temporary
unset Reply-To until a real monitored inbox is verified. These supersede only
the recorded no-agreement clause and unconditional interim Reply-To requirement.
Own-unmatch control, rolling successful-restoration cap, approved chat, expiry
and lifetime safety limits remain. L02/L06/L07 alignment and evidence are open;
L01a reporting still requires no match restoration or email.

## Current owner-policy checkpoint

Owner Q01-Q48 answers have been reconciled in
[owner-policy-reconciliation-2026-10-15.md](owner-policy-reconciliation-2026-10-15.md)
and incorporated into [decisions.md](decisions.md). P1-P6's express later
approvals now supersede only their earlier proposed/unresolved scope in that
48-answer record. Reviewer: Takudzwa Hondova;
exact actual acceptance UTC/Harare date/time remains CONFIRM, with timezone
confirmed only. D03 stays approved full scope; D04/D05/D12 have partial product
selections but remain pending overall, D06-D10 pending, D11 partial. No choice
is treated as implemented or deployed by documentation.

The [inventory/retention matrix](data-inventory-retention-matrix.md) and
[read-only configuration/costing checklist](configuration-impact-checklist-2026-10-15.md)
record category clocks, roles, evidence and real-world gaps. Preserve approved
own-unmatch authority with Decision 27's newly approved fresh agreement from
both before restoration; permit temporary unset Reply-To until monitored-inbox
verification under the new D11 amendment. Both named conflicts are resolved;
never broaden decline-only recreated-match clearance. New active-chat/copy/metadata/default values introduced as proposals
remain proposed. See L01-L07 below for later work; no code/configuration work
was authorized or performed in that owner-policy documentation checkpoint; the separately authorized L01b continuation is recorded above.

## Historical initial finding snapshot

The initial table below predates the later dated execution records. It is not
a statement that current HEAD still equals the original audit or that earlier
test blockers remain the latest result. Current checkout is the owner-review
branch at `6d3ea7b0f884251ce89ae1cef253f81a7232c0f1` before this documentation
checkpoint; later evidence retains its original commit/environment attribution.

| Finding | Current state | Evidence / files |
|---|---|---|
| B00/B01 | Historical initial baseline was audit commit `ecf6217ceda74cc257d75483f253862b932dfe9d`; use later checkpoints for current repair/evidence state. | Historical baseline and current branch/HEAD inspection; this backlog. |
| F01 | Partial. Registration now creates pending accounts, validates approved NUST student email/student number, queues fake approval-code mail and routes to email approval plus DOB onboarding. Unit and DB integration tests pass locally. Real ACS sender remains pending. `mail_outbox.body` is fake-only plaintext capture; non-fake providers throw until an encrypted provider/outbox is implemented. | `php/auth.php`, `php/signup_verification.php`, `verify_email.php`, `onboarding.php`, `mail_outbox`, `tests/integration_p0_flows.php`. |
| F02 | Partial. Known duplicate named parameters in active match/chat/message/safety paths were repaired for native PDO. Local DB integration covers reciprocal candidate retrieval, mutual-match uniqueness, chat approval/message writes and block cleanup through shared helpers/SQL. Synthetic overlapping DB-process tests now cover duplicate match creation and duplicate same-direction swipe insertion. Prompt 02 adds reciprocal-match locking and a real HTTP overlap spec; Linux Docker execution with `PHP_CLI_SERVER_WORKERS=4` proved overlapping reciprocal swipes create one active match without duplicate approval rows. Broader message/block/delete overlap remains pending. | `api/swipe.php`, `php/http_test_barrier.php`, `api/matches.php`, `api/messages.php`, `api/message_requests.php`, `api/safety.php`, `chat.php`, `php/block_handler.php`, `tests/integration_core_p0_flows.php`, `tests/overlap_db_p0_flows.php`, `tests/browser/prompt02-http-overlap.spec.js`. |
| F03 | Partial. Operational migration and runner added. Direct SQL migration passes once against the current base schema; `bin/migrate.php` is idempotent for partially upgraded schemas. Runtime DDL can be disabled in production with `APP_ENV=production`; full legacy upgrade rehearsals pending. | `database/migrations/001_operational_contract.sql`, `bin/migrate.php`, `php/operational.php`. |
| F04 | Partial. `require_login()` reloads current account, role and session version, and gates pending email/onboarding states. Rendered browser tests now cover pending-email and pending-onboarding restrictions. Prompt 02 browser evidence now proves two independent sessions lose page/API access after account revocation; broader role/removal/deletion matrices remain pending. | `php/session_manager.php`, `php/auth.php`, `tests/browser/p0-rendered-flows.spec.js`, `tests/browser/prompt02-session-routes.spec.js`. |
| F05 | Partial. Confession reports can use nullable `reported_user_id` after migration and resolve confession owners server-side; flagged referenced confession bodies are hidden; block writes/match cleanup are transactional. Atomic safety-service policy still pending D04/D05. | `api/confessions.php`, `api/safety.php`, migration. |
| F06 | Partial. Native-compatible anonymiser metadata probes and session-version/delete status fields added. Local DB integration verifies anonymisation completes and marks the user deleted/inactive. Full lifecycle inventory and backup tombstone replay remain pending D05. | `php/anonymiser.php`, `tests/integration_core_p0_flows.php`. |
| F07 | Partial. Production DB config fails safely for root/empty password and real CI lint/unit workflow replaces placeholder. Web-root/server/Azure artifacts remain pending. | `config/db.php`, `.github/workflows/ci.yml`. |
| F08 | Partial. Discover API no longer overrides cursor with default offset. Stable keyset/snapshot pagination and representative 5,000-account tests pending. | `api/discover.php`. |
| U01 | Partial. API create responses now return DB-stored UTC confession time; rendered page emits UTC ISO in `datetime`. Synthetic browser test verifies an older persisted confession timestamp does not render as current after reload. Fake-clock boundary tests and H09 human comparison remain pending. | `api/confessions.php`, `confessions.php`, `php/function.php`, `tests/browser/p0-rendered-flows.spec.js`. |
| U02 | Pending. D03 now includes geographic matching in the full feature scope for the next production-ready release. Geographic matching still requires D12 before implementation can be certified. | `docs/remediation/decisions.md`, `docs/remediation/geographic-matching.md`. |

## 2026-10-12 Continuation Notes

| Finding | Current state | Evidence / files |
|---|---|---|
| B01 | Improved. Fixture-writing PHP suites, the DB race worker, synthetic seed script and browser reset now require a shared disposable-database guard before connecting or dropping/importing schema. Local unit and syntax checks pass; full DB-backed execution is still blocked by missing authorized local MySQL credentials. | `tests/support/disposable_database.php`, `tests/support/disposable-database.js`, `tests/bootstrap_database.php`, `tests/unit_disposable_database_guard.php`, `tests/browser/p0-rendered-flows.spec.js`, `docs/remediation/d04-d05-implementation-evidence.md`. |
| F07 | Improved. CI now uses `bushisa_test_ci`, bootstraps through the guarded runner instead of an inline unguarded PDO import, and includes the previously omitted core and DB-overlap regressions. Browser CI and production-version execution remain pending. | `.github/workflows/ci.yml`. |
| Prompt 02 | Started. Browser harness startup now fails closed unless explicit disposable DB, fake mail, non-debug and runtime-DDL-disabled settings are present before server launch. A two-context session revocation browser test is prepared but NOT RUN here. Actual rendered browser, broader route matrix and multi-worker HTTP-overlap evidence remains NOT RUN/BLOCKED. | `playwright.config.js`, `tests/unit_playwright_config_guard.js`, `tests/browser/prompt02-session-routes.spec.js`, `docs/remediation/prompt02-route-session-evidence-2026-10-12.md`. |

## 2026-10-13 Prompt 02 execution

| Finding | Current state | Evidence / files |
|---|---|---|
| Prompt 02 | Improved. Local disposable MySQL `9.6.0` on `127.0.0.1:3318` executed the rendered browser suite and two-session revocation route check successfully. Guard/unit/PHP DB/mail regressions passed. Genuine HTTP reciprocal-swipe overlap passed in a Linux Playwright container using `PHP_CLI_SERVER_WORKERS=4` and the same host-owned disposable MySQL listener through `host.docker.internal`. Native Windows remains unsuitable for overlap; CI confirmation is still useful but no longer the first execution blocker. | `docs/remediation/prompt02-route-session-evidence-2026-10-12.md`, `.github/workflows/ci.yml`, `playwright.config.js`, `tests/browser/p0-rendered-flows.spec.js`, `tests/browser/prompt02-session-routes.spec.js`, `tests/browser/prompt02-http-overlap.spec.js`, `api/swipe.php`, `php/http_test_barrier.php`. |
| Prompt 02 route/session/race completion | Improved. Actual browser/API route tests now cover request/approve/send/read/unsend/report/revoke, forged ownership, missing CSRF, block cleanup, account deletion stale-session denial and moderator role removal. Linux Docker multi-worker HTTP tests now cover reciprocal swipe overlap, approve-vs-revoke and send-vs-block with two distinct PHP worker PIDs at guarded barriers. Candidate CI remains pending until branch push/PR run. | `docs/remediation/prompt02-route-session-evidence-2026-10-12.md`, `tests/browser/prompt02-chat-routes.spec.js`, `tests/browser/prompt02-chat-overlap.spec.js`, `tests/browser/prompt02-http-overlap.spec.js`, `api/message_requests.php`, `api/messages.php`, `api/safety.php`, `php/policy_safeguards.php`, `php/http_test_barrier.php`. |

## 2026-10-14 Prompt 02 account deletion CI repair

| Finding | Current state | Evidence / files |
|---|---|---|
| Prompt 02 account deletion replay collision | Repaired locally and independently confirmed by accessible candidate CI. The original `P02-CHAT-02` 500 was reproduced on a disposable old schema as a `protective_replay_events.uq_replay_event` duplicate for two same-match `match_closed` events in one second. Replay timestamps now use `TIMESTAMP(6)`, protective writes use `UTC_TIMESTAMP(6)`, and new forward migration `005_protective_replay_event_precision.sql` upgrades already migrated databases. Fresh bootstrap and pre-applied `001`-`004` upgrade rehearsals passed on disposable DBs. The repaired deletion case, full chat-route spec, rendered/session regressions, PHP P0/policy/integration regressions, and Linux four-worker overlap specs passed. The `push` and `pull_request` CI runs for head `e30dcf844abaf499bcc657172c2c9aa1f4b4986d` passed all three jobs, but this still does not apply migration `005` to production, approve policy, prove production compatibility or establish an actual isolated restore. | `database/migrations/002_policy_safeguards.sql`, `database/migrations/005_protective_replay_event_precision.sql`, `php/operational.php`, `php/policy_safeguards.php`, `tests/browser/prompt02-chat-routes.spec.js`, `docs/remediation/prompt02-route-session-evidence-2026-10-12.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`. |
| Prompt 03 owner decision packet | Updated. D03 is recorded as approved full feature scope for the next production-ready release by reviewer Takudzwa Hondova on 2026-10-14. D04, D05 and D12 remain pending and are the next owner-review focus; D06-D11 remain in their recorded states. | `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/decisions.md`, `docs/remediation/geographic-matching.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |


## 2026-10-15 Prompt 03 owner review

| Finding | Current state | Evidence / files |
|---|---|---|
| Continuation checkpoint | Resumed on `codex/prompt03-owner-decisions-20261014` at HEAD `6d3ea7b0f884251ce89ae1cef253f81a7232c0f1`. Existing uncommitted remediation-document changes were preserved. | Local branch, HEAD and working-tree inspection. |
| D04 Decision 6 | Reviewer Takudzwa Hondova approved that only the person who ended an approved chat may send a fresh restart request; the other participant must approve before chat access resumes. The restart cooldown was pending at Decision 6 and is resolved separately by Decision 7 below; the second-end cutoff is resolved by Decision 8 below. D03 and D04 Decisions 1-5 are preserved; D04, D05 and D12 remain pending overall. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 7 | Reviewer Takudzwa Hondova approved a 12-hour UTC cooldown from ending/revoking the approved chat before the fresh restart request may be sent. Decision 6 still limits restart to the person who ended it and requires the other participant's fresh approval. Existing request budgets and restrictions remain unchanged; the second-end cutoff was pending at this step and is resolved by Decision 8 below. D04, D05 and D12 remain pending overall. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 8 | Reviewer Takudzwa Hondova approved that a second approved-chat end terminates re-request privileges for the rest of that match's lifetime, even if another 12 hours pass or request budget remains. Earlier approvals are preserved. Rematch carryover was pending at Decision 8 and is resolved by Decision 9 below; Decision 5 resolves decline clearance only. D04, D05 and D12 remain pending overall. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 9 | Reviewer Takudzwa Hondova approved that the second-ending cutoff carries across rematches between the same two users. Unmatching and matching again must not restore chat-request privileges after the second approved-chat ending, even if request budget remains. Decision 5 clears decline restrictions only and does not clear this cutoff. Prior approvals are preserved; D04, D05 and D12 remain pending overall. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 10 | Reviewer Takudzwa Hondova approved that the blocker's unblock automatically gives the pair chat privileges immediately in the reviewed scenario without the second-ending cutoff, without a fresh mutual match or new chat-request approval. Prior-approval eligibility was pending at Decision 10 and is resolved by Decision 11 below; blocking's exclusion from the ending cutoff is resolved by Decision 12 below. Prior approvals and the cutoff are preserved; D04, D05 and D12 remain pending overall. Current code still requires alignment. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 11 | Reviewer Takudzwa Hondova approved immediate, automatic chat privileges on blocker unblock also when chat was never approved; prior chat approval is not required. The second-ending cutoff, other active block/account restrictions and earlier approvals are preserved. Blocking's contribution to the ending cutoff was pending at Decision 11 and is resolved by Decision 12 below. D04, D05 and D12 remain pending overall; implementation alignment remains pending. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 12 | Reviewer Takudzwa Hondova approved that blocking an approved chat does not count toward the two-ending cutoff; repeated block/unblock cycles do not trigger it. Immediate chat on blocker unblock is preserved, including never-approved chat. Separate approved-chat endings and any already-triggered cutoff remain governed by earlier approvals. Approved-chat continuation after unmatch was pending at Decision 12 and is resolved by Decision 13 below; other unspecified details remain pending. D04, D05 and D12 remain pending overall. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 13 | Reviewer Takudzwa Hondova approved that a pair with an approved chat may keep chatting after unmatching. Unmatching alone does not close/revoke that chat, require a new match/request/cooldown or count as an approved-chat ending. Active block/account restrictions, separate chat endings and prior approvals remain preserved. The documented active-match guard and close/revoke design require alignment. Other unmatch details, retention and D04/D05/D12 overall acceptance remain pending. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 14 | Reviewer Takudzwa Hondova approved keeping existing approved chats available to both participants during discovery/profile pause. Pause alone does not revoke chat, add an approved-chat ending or require a new request/cooldown to continue it. Other pause effects remain unspecified; active block/account restrictions, separate chat endings and earlier approvals are preserved. D04, D05 and D12 remain pending overall; implementation and regression evidence remain pending. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 15 | Reviewer Takudzwa Hondova approved deleting unsent original text unless already preserved as evidence for an existing safety report, and rejected retaining every unsent original for moderation. Evidence access, retention deadlines and backup deletion remain pending under D05. Prior approvals are preserved; no product unsend window, numerical retention deadline, backup purge or implementation is approved by this choice. D04, D05 and D12 remain pending overall. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 16 | Reviewer Takudzwa Hondova approved a 15-minute unsend window measured from the message's server-recorded sending time; after that window, unsending is not allowed. Decision 15's deletion model and all earlier approvals are preserved. Evidence access, retention deadlines and backup deletion remain pending under D05. This action window does not select an evidence-retention period. Documentation only; implementation verification, evidence and D04/D05/D12 overall acceptance remain pending. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 17 | Reviewer Takudzwa Hondova approved that, after successful unsend, the message entry disappears entirely from both participants' ordinary chat views, without a `Message unsent` placeholder. Decision 15's original-text deletion rule and already-preserved safety-report evidence exception, Decision 16's 15-minute server-time window and all earlier approvals are preserved. Evidence access, retention deadlines and backup deletion remain pending under D05. Documentation only; current placeholder behavior requires implementation alignment and regression evidence. D04, D05 and D12 remain pending overall. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 18 | Reviewer Takudzwa Hondova approved that discovery pause must not affect matching, requesting, approving or chatting functionality. Eligible matching, sending/receiving chat requests, recipient approval and starting/continuing approved chats remain available while paused, subject to normal independent eligibility, block/account restrictions, budgets, cooldowns, decline rules and the second-ending cutoff. Pause does not automatically approve requests or reset/bypass those rules. Decisions 1-17 are preserved; exact discovery presentation and unrelated pause details remain unselected. Documentation only; alignment verification and regression evidence remain pending. D04, D05 and D12 remain pending overall, including D05 evidence access, retention deadlines and backup deletion. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 19 | Reviewer Takudzwa Hondova approved that unmatching does not cancel a pending chat request, but the recipient may approve that same request only after the pair is matched again and if it is still pending. Decision 1's original seven-day UTC expiry continues unchanged; unmatch/restoration does not pause/reset it or revive an expired request. All earlier approvals, budgets, cooldowns, decline restrictions, blocks/account restrictions and the second-ending cutoff remain applicable; Decision 13 preserves already-approved chat after unmatch. Match-restoration actors/mechanics remain pending. Documentation only; alignment verification, regression evidence and D04/D05/D12 overall acceptance remain pending, including D05 evidence access, retention deadlines and backup deletion. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 20 | Reviewer Takudzwa Hondova approved that the person who unmatched may restore the match herself without renewed agreement from the other participant. In Decision 19's pending-request scenario, the recipient must still approve the same still-pending chat request; restoration alone does not approve it. Original expiry and independent eligibility, block/account restrictions, budgets, cooldowns, decline rules and the second-ending cutoff remain applicable. All earlier approvals are preserved. Other restoration actors/cases and remaining mechanics/timing/caps remain pending. Documentation only; alignment, regression evidence and D04/D05/D12 overall acceptance remain pending, including D05 evidence access, retention deadlines and backup deletion. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 21 | Reviewer Takudzwa Hondova approved that only the person who unmatched may restore in the reviewed case: if Alice unmatched Brian, only Alice can restore; Brian cannot restore Alice's unmatch. Decision 20's lack of renewed match agreement, Decision 19's recipient approval of the preserved still-pending chat request and its unchanged original expiry remain applicable. All earlier approvals and contact restrictions are preserved. Behavior when both users independently unmatch, and remaining mechanics/timing/caps, remain pending. Documentation only; actor authorization/denial evidence and D04/D05/D12 overall acceptance remain pending, including D05 evidence access, retention deadlines and backup deletion. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 22 | Reviewer Takudzwa Hondova approved that, when both participants unmatch, each must undo their own unmatch before the match can return. One person's undo is insufficient while the other's unmatch remains; neither may undo the other's unmatch. All earlier approvals remain intact, including continued already-approved chat while unmatched and recipient approval of the preserved still-pending request only while matched and before original expiry. Existing contact restrictions remain applicable; remaining restoration details and unspecified cooldowns/caps remain pending. Documentation only; alignment, regression evidence and D04/D05/D12 overall acceptance remain pending, including D05 evidence access, retention deadlines and backup deletion. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 23 | Reviewer Takudzwa Hondova selected a combined `Block and report` option for reporting with blocking, clearing pending chat requests between the pair and initially providing no unblock privilege. The clarified choice allows unblocking after safety review; permanent loss of that privilege is not selected. Review outcome/authority/timing, later unblock/chat effects and reporting outside this combined flow remain unresolved. Ordinary block/unblock and unmatch approvals, request budgets and fixed reported-sender probation are preserved. Request terminal-state/storage/physical deletion and D05 evidence access, retention deadlines and backup deletion remain pending. Documentation only; alignment, evidence and D04/D05/D12 overall acceptance remain pending. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 24 | Reviewer Takudzwa Hondova approved that a safety reviewer must explicitly approve unblocking after `Block and report`; review completion alone is insufficient. Safety reviewers may ban users from the app for policy or ethical violations. The clarified choice allows the reviewer to leave the pair blocked without a ban; refusal to approve unblocking does not automatically require an app ban. All prior approvals, cleared pending pair requests, budgets and fixed sender probation remain unchanged. Substantive criteria, definitions, named role assignments, ban duration/appeals, review timing and actual unblock/later chat effects remain pending. Documentation only; alignment, evidence, D05 evidence access/retention/physical deletion/backup deletion and D04/D05/D12 overall acceptance remain pending. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 25 | Reviewer Takudzwa Hondova approved that, after the safety reviewer's explicit approval, the blocker chooses whether to press `Unblock`. Approval restores the option but leaves the block in place; Alice may keep Brian blocked. Only her own block is affected by her later choice to unblock. Subsequent chat effects for the combined block/report flow remain pending; ordinary block/unblock approvals, cleared pending requests, independent restrictions, fixed sender probation and all prior approvals remain unchanged. Documentation only; alignment, regression evidence, D05 evidence access/retention/physical deletion/backup deletion and D04/D05/D12 overall acceptance remain pending. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| D04 Decision 26 | Reviewer Takudzwa Hondova approved immediate chat availability after explicit safety-reviewer approval and the blocker's actual choice to unblock in the combined block/report flow. No fresh request or further chat approval is required. Cleared pending requests remain cleared; budgets, independent blocks/account restrictions, the second-ending cutoff and fixed sender probation remain unchanged. All earlier approvals are preserved. Documentation only; alignment, focused evidence, D05 evidence access/retention/physical deletion/backup deletion and D04/D05/D12 overall acceptance remain pending. | `docs/remediation/decisions.md`, `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, `docs/remediation/policy-proposals-d04-d05.md`, `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`. |
| Remaining owner questions | Owner requested all remaining questions at once. Complete pending questionnaire Q01-Q48 in the Prompt 03 packet covers remaining D04 messaging/safety, D05 lifecycle/retention, D12 geography and D06-D11 release/operations choices. Existing approvals are not reopened, and unanswered questions remain pending. | `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`, Prioritized Owner Questions. |
| Execution boundary | Documentation only. Policy implementation, regression evidence and release acceptance remain pending. | No code, merge, deployment, live-database or real-email action. |

## Prompt 01 local DB execution - 2026-10-12

Selected signup, core, policy, SMTP-outbox and DB-overlap suites passed
on the disposable database. These results supersede the earlier local
execution blockers.

[Full execution checkpoint](prompt01-local-db-execution-2026-10-12.md).

Tested baseline: 6ed126d, with uncommitted changes now preserved on
codex/prompt01-db-validation-20261012.

Browser/HTTP/session, migration-role, production-version and CI evidence
remain incomplete. D04/D05 approval and P0/P1 gates remain open.

## Q01-Q48 implementation backlog

These began as **later bounded prompts** in the owner-policy documentation
turn; L01b progress below now has separately authorized local evidence. L01-L07 avoid the existing B00/B01 baseline task identifiers. Product
reviewer is Takudzwa Hondova; every named operational/qualified implementer,
reviewer and backup remains UNASSIGNED until actually accepted. Public service
deadlines stay gated by ownership, overdue alerts and measured capacity.

| Task | Policy dependency / scope | Current gap and later bounded output | Evidence needed / release gate |
|---|---|---|---|
| L01 Report routes and roles | Decisions 28–33 and Q01–Q05/Q18–Q20; service targets conditional | L01b text/API resolver, actual/ambiguous actor attribution, self-primary rejection, deduplicated private receipts/addenda, atomic probation/renewal and block clearance are implemented locally. See exact evidence. Human recovery, full sanctions/appeals, real MFA and staffed queues remain later slices. | [Current A01–A45 results](l02a-implementation-evidence-2026-10-16.md#a01a45-current-status): PASS/PARTIAL/NOT RUN, not whole acceptance. Qualified review, legacy pair-state/replay, real roles/capacity, H03/H10 and release gates remain open. |
| L02 Terminal pair state, undo and projections | Existing D04 Decisions 1–33, including decline-only new-match clearance and Decision 27 fresh mutual restoration | [L02a](l02a-pair-history-contract.md) is implemented locally: canonical original-pair ledger, positive post-cutover genesis versus incomplete legacy history, retained request events, original-ender 12h restart, second-ending cutoff, fresh mutual restoration/own undos/rolling cap and eligible creator-unblock chat. Shared route/page/preview authorization and immutable closed-text segmentation are integrated. New requests/grants remain fail-closed without necessary provenance; migration never certifies old accounts. [L02b](l02b-legacy-history-import-contract.md), [L02c offline planning](l02c-offline-history-planner.md) and [L02d guarded synthetic transactions](l02d-synthetic-import-evidence-2026-10-17.md) define/test the non-enabling evidence/import path. [L02e mixed-writer/restricted-role evidence](l02e-mixed-races-compatibility-evidence-2026-10-17.md) closes the bounded synthetic concurrency/engine checks and adds migration 009 read-only trigger health. No legacy eligibility adapter or real-data importer exists. | [A01–A45 evidence](l02a-implementation-evidence-2026-10-16.md): 24 PASS, 19 PARTIAL, 2 NOT RUN unchanged. Separate LB01–LB33 current totals: 21 PASS, 12 PARTIAL, 0 wholly NOT RUN, with listed NOT RUN variants. Bounded local L02 is complete; actual source/cutover evidence, qualified review/named roles, UI/manual variants and full L03 lifecycle/independent replay stay open. No production activation or whole D04 acceptance. |
| L03 Cleanup, evidence clocks and independent replay | Q10-Q27/Q32; D05 partial; Q12 anti-replay/Q16 active chat/Q23 metadata/Q26 copy settings still proposed | [Baseline inventory](l03-category-clock-writer-inventory.md) maps 32 categories/25 writers/G01–G14. [L03a](l03a-text-evidence-lifecycle-evidence-2026-10-17.md) implements new-intake immutable text clocks, item/version holds, release obligations, bounded atomic purge/receipts and synthetic publication/replay interfaces. Production authority/journal integration remains unavailable/fail-closed. No broad account/media/legacy/import cleanup or scheduling is enabled. [Q11](q11-signup-expiry-evidence-2026-10-18.md) now adds original attempt/onboarding clocks and atomic challenge/mail cancellation (QS01–QS24: 24 local PASS). [The bounded attempt consumer](q11-attempt-erasure-evidence-2026-10-18.md) now implements that local scope (QE01–QE28: 27 PASS/1 PARTIAL); operational providers remain unavailable. [Q22 terminal cleanup](q22-terminal-mail-cleanup-evidence-2026-10-18.md) now implements original new-writer detail/payload/seven-day metadata clocks, bounded SQL cleanup and late carrier receipts. Operational recovery integration is still unavailable. [The session-copy inventory/contract](account-session-copy-contract.md) now records SC01–SC40 at 15 PASS/19 PARTIAL/6 NOT RUN, including the Q11-only synthetic physical consumer; production handler integration and independent recovery remain gated. | Current LC01–LC18: 13 PASS/5 PARTIAL/0 wholly NOT RUN; exact per-profile assertions and unexecuted variants linked in L03a. Historical A/LB unchanged. Real copy inventory/durability, startup/mail replay and witnessed restore, lawful purpose/qualified review and named operators remain open. Latest actual copy +7d; conditional7+35+7=49d, never universal. |
| L04 Consent and coarse geography | Q28-Q34; D12 partial; source combination/catalogue widths/bonus bands still proposed; D03 full geography scope preserved | Build reviewed catalogue contract without invented points; current coarse area/version/time/consent only; optional opt-in/refusal/withdrawal; 7d freshness/24h purge; >=10-user cohort suppression; atomic 2 changes/24h; <=5-point soft ranking with zero fallback; cursor invalidation/live safety checks. `profiles.campus_zone` is legacy and authoritative matcher currently lacks geographic ranking. | Local catalogue/point acceptance, normalization/fairness/probing/query/load evidence and Android/iOS grant/refuse/withdraw/expiry/paging witnesses. Privacy/H13, final notice and actual catalogue reviewers UNASSIGNED; no production collection before acceptance. |
| L05 Telemetry allowlist | Q13/Q23/Q43-Q44; provider/sampling/metadata defaults and funding remain proposed | The owner reports the exact name `APPLICATIONINSIGHTS_CONNECTIONS_STRING`; the audit finds no scoped code consumer and makes no correction/activation. D10 remains GATED. Map every server/browser/provider destination. Prepare pre-transmission fixed-field redaction/allowlist, optional browser consent/withdrawal gate, random expiring visitor reference, separate app-side totals and suppressed aggregates. Inventory table minimums/export/archive paths against retention; no session replay, message/location/answer/token collection. | Later consent/redaction/retention/source-count tests, priced region/configuration review and named privacy/security acceptance. D10 stays pending; US$5 is not an approved spend. |
| L06 Reset challenge, outbox and sessions | Q11/Q22/Q40/Q45-Q47; approved sender/15m controls; production throttle values and Q40 redesign pending; temporary unset Reply-To approved | Migration 016 and guarded routes/outbox/session-version flow are implemented locally. [25 corrected HTTP cases](evidence/l06-password-reset-local.json) and [six rendered browser cases](l06-browser-hosted-ci-evidence-2026-10-19.md) PASS, including same-document fragment repair, no-JavaScript manual recovery, no-store/scanner behavior, genuine two-session page/API revocation, storage/diagnostic checks and purpose separation. The earlier AUTH-02 evidence limitation is explicitly preserved. Dedicated CI executes L06 independently of the existing regression jobs. Issuance remains off by default. | All four CI jobs passed on implementation candidate `e7501fe` in both feature-push and PR-merge runs; exact attribution is in the linked evidence. Actual device/manual witnesses, staging engine/grants/migration/worker compatibility, qualified review, named operators, monitored support and actual inbox/header acceptance remain open. The [Azure audit](azure-compatibility-staging-plan-2026-10-19.md) additionally gates managed-stage reset on proposed STG-I01/STG-I02 and its guarded driver. Resolve production throttles separately before enabling. No production configuration or mail is touched. |
| L07 Monitoring and configuration hardening | Q05/Q24/Q26/Q35-Q44/Q46/Q48; appointment/budget/SLO/RPO/runtime/session/security proposals not adopted | The [release reconciliation](completed-owner-evidence-reconciliation-2026-10-21.md) accepts owner observations through 08: PHP CLI 8.5.11, MySQL 8.0.46-azure/UTC/RR, captured schema and ledger 001-004, broad configured-user grants, 75 baseline source matches, actual installed wrapper and established minute-schedule semantics. Full app/dependency/worker-package provenance, web-effective sessions, new restricted identities, storage/recovery and backup redundancy remain unverified. The [capture-08 classification](deployed-code-inventory-reconciliation-2026-10-21.md) accounts for added paths and generated metadata; the [consolidated delta](remaining-code-evidence-collection-2026-10-21.md) prepares only remaining justified hashes/names; do not repeat completed metadata or hash collection. Preserve the old-wrapper/candidate-cleanup dispatch stop and reviewed mail-only replacement prerequisites. Continue grant/secret/definer, monitoring/alert/queue/capacity, private paths, support and operator preparation without live changes. | Full deployed-code coverage, authentic legacy continuation/cutover, compatible forward repair or previous-artifact acceptance, accepted reviewers/coverage, restricted-role/service proof and independent recovery remain required for the controlled existing-resource release. Managed staging stays DEFERRED, all 18 cases NOT RUN; SC26 production topology PARTIAL. No production migration or restore is claimed. |

### L01a documentation checkpoint

**Historical f7e9f8c checkpoint:** the documentation-only/NOT RUN statements in
this section describe that checkpoint. Current L01b evidence is linked above.

**Ready for review. Design and owner-policy recording are documentation only;
implementation and independent review/sign-off have not occurred.** This
continuation from `237b7d1` updates only the register, contract and backlog.

The [contract](l01a-report-route-target-contract.md) retains actor-bound proof
for all five approved families, immutable target/event/segment identity,
post-unmatch/block reporting without restored reads and metadata-only handling
for proven erased targets. Missing filing proof still denies private lookup;
valid proof with ambiguous attribution now yields an accepted human-review case
without guessed probation. Known own-content primary reports are rejected;
relevant own-message context and a separate human compromise/recovery route
are approved. Actual monitored recovery authorization/workflow is not supplied.

[All P1-P6 choices are now recorded](l01a-report-route-target-contract.md#p1-p6-owner-decisions-recorded)
as D04 Decisions 28-33. Accepted authorized non-self reports with clear attribution
trigger fixed probation; new qualifying reports received after expiry start a
fresh seven-day period. Active-period reports never extend/restart it, retries
never renew, and no early restoration or request/lifetime-budget reset is allowed.
Report attachment adds reviewer clearance to an existing ordinary block's removal
requirements; clearance alone leaves it blocked and creator choice is still
required. Severity ban defaults are approved with human findings and independent
appeals/permanent-reversal review. Service targets are conditionally adopted;
real staffing/coverage/capacity evidence remains an open operational gate.

The engineering retry/proof/incident design remains ready for review. It must
resolve immutable report identity versus informational update, receipt/activation
ordering, concurrent renewals, source-erasure locking, exact case/block/clearance
linkage and separate human recovery authority. No raw submitted actor ID can
supply attribution, and no case reuse/key change can multiply sanctions.
Proof/category retention, privacy/security/safety review, MFA and real named
operators/reviewers/backups remain open; they are not unanswered P1-P6 choices.

[Acceptance matrix A01-A45](l01a-report-route-target-contract.md#acceptance-test-matrix-for-later-execution)
adds adopted-rule attribution, ambiguity, self/recovery, renewal-boundary,
block-clearance, sanction/review-target and concurrent-renewal cases. Every
case remains NOT RUN. No old candidate CI is reused as evidence for these rules.
The source route map remains an observation from `e0a0066`, unchanged at the
continuation baseline, not a live behavior test.

Exact acceptance UTC/Harare timestamps were not captured; no drafting time is
substituted and no approved decision is reopened. Decision 27, temporary unset
Reply-To, retention/copy proposals and unrelated release gates remain unchanged.

### Next bounded task

**L02a local checkpoint:** [implementation/evidence](l02a-implementation-evidence-2026-10-16.md) and [pair contract](l02a-pair-history-contract.md). Approved policy is unchanged. The migration leaves production writer certification inactive and every real legacy pair incomplete until actual evidence establishes provenance. No local fixture attests production history.

**L02b/L02c/L02d/L02e checkpoint:** the [evidence/import contract](l02b-legacy-history-import-contract.md), [offline implementation](l02c-offline-history-planner.md), [synthetic transactions](l02d-synthetic-import-evidence-2026-10-17.md) and [mixed-writer/engine evidence](l02e-mixed-races-compatibility-evidence-2026-10-17.md) record 21 PASS, 12 PARTIAL, 0 wholly NOT RUN for the separate LB01–LB33 matrix. Listed NOT RUN variants stay open; no A-case changes. Synthetic private fact import never confers runtime eligibility. No real-data importer or legacy activation adapter exists.

**L03 inventory checkpoint:** the [category/clock/writer inventory](l03-category-clock-writer-inventory.md) is documentation only, with 32 categories, 25 identified writers/schedulers and G01–G14. It preserves approved clocks and distinguishes proposed values, planned stores and unverified external evidence. No cleanup implementation or functional testing occurred.

**L03a continuation:** [implementation and current LC01–LC18 results](l03a-text-evidence-lifecycle-evidence-2026-10-17.md) records 13 PASS/5 PARTIAL/0 wholly NOT RUN for the bounded text/hold/recovery-interface work. Production authority integration and independent durability remain unavailable; no real restore/scheduling is enabled. That checkpoint selected Q11 anchors/cancellation; the current Q11 evidence and next consumer slice are linked above. Proposed retention values, actual purposes/reviewers/coverage, copy inventory, full account/media erasure and safe import-frontier compaction remain separate gates, with no settled approval reopened.

No push, merge, deployment, live DB, production writer activation or real mail is authorized by this checkpoint.

### Evidence boundaries for this checkpoint

Documentation validation is recorded in the acceptance supplement/checkpoint.
No DB/browser suites are run solely for Markdown changes. Preserve the exact
Prompt 02 candidate head `e30dcf844abaf499bcc657172c2c9aa1f4b4986d`, push
run `38546846225` and PR run `38546851035`; they are not tests of this policy
checkpoint. Historical 11 October SMTP/inbox observations remain operator/user
reports. Migration 005 is not declared production-applied; actual isolated
restore remains NOT RUN, and no policy/operations gate is cleared by these docs.
