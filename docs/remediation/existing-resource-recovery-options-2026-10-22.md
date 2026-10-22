# Recovery using existing resources: preparation and limits

Prepared for the controlled direct-production approach, from the accepted
[owner evidence](completed-owner-evidence-reconciliation-2026-10-21.md) and
[handover review](direct-release-handover-review-2026-10-21.md). No production
operation, backup, restore or acceptance was performed. Managed staging remains
deferred. Real reviewer/operator assignments remain PENDING; they do not stop
local engineering. An affordable recovery path is still required before release.

## What the existing resources can support

Owner observations establish one Linux B1 app with no slots and one MySQL
8.0.46-azure Burstable B1ms server (1 vCore/2 GiB, 20 GiB provisioned storage),
seven-day backup retention and no demonstrated restore. They do **not** establish
free disk, current data size, App Service quota/free space, local protected disk
capacity, recovery time, independent protection or spare operator capacity.
Provisioned space is not free space; autogrow is not an approved cost commitment.

| Option | Fit with existing resources | Limits, capacity and potential cost | Evidence needed |
|---|---|---|---|
| Tested forward repair / compatible app replacement on the existing app and forward schema | Avoids provisioning another app/server; first candidate for an application-only defect. | Requires a securely preserved actual artifact, repair artifact and temporary unpacking headroom. Does not repair corrupted/lost DB/media. Ordinary old-code rollback is unsafe: OS17 omits pair history. Quiescence and failed-state preservation still required. | Exact repair/fallback rehearsal on upgraded synthetic schema, then actual artifact/config/worker preservation and accepted maintenance/smoke evidence. DR-P02 remains NOT RUN for the missing actual previous bundle. |
| Logical recovery into a separate database on the **existing MySQL server** | Could avoid a second paid server if verified headroom and privileges permit it. A database is not a separate failure domain or staging environment. | During recovery the old/failed and restored data plus indexes/temp work/binlogs and backup growth coexist. A 1-vCore/2-GiB server may make restore slow and compete with other databases. Private encrypted dump storage and transfer capacity are needed. Space/IOPS/autogrow/backup usage may incur cost; zero extra cost and RTO are unproved. | Synthetic dump/restore plus protective replay and schema/grant/definer/queue checks; actual capacity/permissions and protected-backup coverage accepted separately. No live schema creation or dump is authorized here. |
| In-place logical replacement of the original database | Can reduce simultaneous database footprint if no spare schema fits. | Destructive, longer downtime; failure can leave no working original. Still needs independent verified backup, failed-state preservation, scratch capacity, complete restore plan and safe replay. Not the default fallback and not authorized. | A separate explicit destructive-recovery decision after a successful representative rehearsal, protected artifact coverage and measured capacity. No down-migration is proposed. |
| Isolated local Docker recovery rehearsal on the existing laptop | Available now for **synthetic** engineering validation without paid Azure resources. | Needs local disk/RAM and isolation. It cannot prove Azure IAM/TLS/definers/storage behavior, cloud recovery duration or disaster independence. Production exports to the laptop are not authorized or required for this step. A local DB cannot simply replace the privately networked production server. | Execute synthetic reconstruction/dump/restore/replay and record exact scope/timing. Do not call this witnessed production recovery acceptance. |
| Azure point-in-time recovery | Uses configured native backup recovery points, but restores to a **new server**, not over the current database. | Outside the no-new-paid-resource plan unless separately funded: server/storage/quota/private-network capacity and charges must be accepted. No budget or provision is inferred from existing backup availability. | Separate cost/capacity decision and authorized isolated service rehearsal. Currently NOT RUN; not a requirement to buy managed staging. |

Microsoft documents logical dump/restore into an empty database, including a
different database name. That supports the same-server option in principle;
whether this particular server has enough capacity is an engineering question,
not an observed fact. [Microsoft dump/restore guidance](https://learn.microsoft.com/en-us/azure/mysql/flexible-server/concepts-migrate-dump-restore).

Native Flexible Server restore creates a new server; its backups cannot be
exported as ordinary files. Backup storage above the included allowance can be
billable. Neither the observed seven-day setting nor a successful backup proves
the restore path has been exercised. [Microsoft backup/restore guidance](https://learn.microsoft.com/en-us/azure/mysql/flexible-server/concepts-backup-restore).

## Capacity worksheet before choosing a live method

Prepare, without inventing values: actual DB data/index allocation, largest
table/index, encrypted logical export size, retained failed-state copy, restored
schema footprint, temporary/index build/binlog/backup growth, media/version size,
app/worker/config artifact size and protected session/replay records. Record
source, UTC capture time, margin rationale, existing free quota/disk and measured
restore/replay time. Use metadata/metrics rather than user-content exports to
estimate capacity. No new collection is demanded while independent tests run.

For same-server recovery, budget concurrent footprints rather than assuming
`2 × compressed dump size` is sufficient. A compressed dump is not the size of
InnoDB tables/indexes or temporary work. For app recovery, preserve the actual
installed worker separately from the repository source. The worker ZIP's matching
run.sh and equivalent settings do not prove an app archive exists or identify
the exact deployment. A Git export is not the missing historical artifact.

Any new storage/autogrow/egress/temporary server cost needs an actual quoted
configuration and owner funding decision; no dollar estimate is adopted here.
If existing capacity cannot support a safe method, release waits for an explicit
capacity or recovery decision. It does not silently waive recovery or require
a second paid staging environment.

## Stop, restore/repair, replay, then reopen

Before a separately authorized recovery, stop ingress and every writer, including
lazy GET mutations, signup/onboarding/reset, admin/support scripts, mail/cleanup,
imports, direct SQL and restore automation. Drain or explicitly account for
in-flight transactions and SMTP outcomes. A database rollback cannot recall
SMTP-accepted mail. Preserve the failed state privately for diagnosis.

Recovery must restore consistent DB/media/app/worker/configuration dependencies,
validate schema/triggers/definers and restricted roles, and apply the latest
independent deletion/block/consent/revocation instructions before reads or mail.
Restore must not lower pair budgets, reactivate old chat grants or invalidated
sessions, renew expiry clocks or resend stale authentication mail. Verify current
UTC/session settings, not just global server settings. Never disable immutable
guards to import data that contradicts them.

A fresh backup is not recovery acceptance. Reopening needs an executed recovery
or compatible forward-repair rehearsal with preservation/replay/authorization
assertions, named witnesses and recorded stop/reopen authority. DR-P03 remains
NOT RUN. Independent durability and witnessed restore remain open. Retain minimal
recovery instructions until the latest possible surviving copy expiry plus seven
days, using the actual inventory; do not substitute a universal 49-day value.

## Work that proceeds without appointments

Local role allow/deny checks, worker incompatibility reproduction, synthetic
legacy-cohort cases and a non-enabling cutover worksheet proceed using existing
approvals. The next recovery engineering slice is a **guarded synthetic logical
dump/restore plus protective-state replay rehearsal**, initially into another
database on the owned local MySQL container. It can measure local storage/time
and detect missing triggers/definers/state; it cannot close Azure capacity,
independent durability or witnessed acceptance. This document does not claim
that rehearsal has run or authorize a production copy/export.
