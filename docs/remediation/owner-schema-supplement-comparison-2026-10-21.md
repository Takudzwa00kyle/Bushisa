# Owner schema supplement: comparison result

**MATCH — no schema changes are needed from these observations.** The supplied
path ending `02-schema-content.txt` was absent; the adjacent
`02-schema-supplement.txt` and `01-schema-context.txt` are the matching prepared
collection outputs. No other adjacent documents were opened. This continues
local preparation checkpoint `7ba16da` and tested application candidate
`888a2c2`; no application code or historical CI record is changed.

Source: owner-collected MySQL prompt output, SHA-256
`c69868ae261f6b47693eba07b0da2021452720f482a5e4502540bf9c1d77d693`,
29,255 bytes. Context selects `bushi-database` at **2026-10-20 23:11:13.709855
UTC / 2026-10-21 01:11:13.709855 Africa/Harare**. Separate supplement-query
instants were not recorded; the context time is not assigned to every query.
Filesystem save times are not substituted as capture or approval timestamps.

The agent parsed the text as data; **no recorded SQL was executed**. All six
expected result sets and the READ ONLY / ROLLBACK results are present, without
a recorded SQL error. The comparison also read only information_schema metadata
from the existing guarded **disposable** reconstruction, upstream MySQL 8.0.46.
It did not connect to production, select user records or rerun migrations.

| Previously missing attribute | Owner observation | Comparison with reconstruction |
|---|---|---|
| FK column mapping/positions | 13 single-column constraints; child columns, parent columns and ordinal/unique positions | **13/13 MATCH**, including both match participants, message sender/match, report reporter/reported person and swipe directions. |
| Index direction/visibility/expression | 122 entries: A (ascending), YES (visible), expression NULL | **122/122 MATCH**, with exact table/index/sequence coverage. |
| Ambiguous character defaults | 51 COLUMN_DEFAULT values are SQL NULL, not literal text `NULL` | **51/51 MATCH**. For 13 nullable columns this is a NULL default; 38 NOT NULL columns have no explicit default. Do not change the latter to DEFAULT NULL. |
| CHECK constraints | Empty set | **MATCH**: none on the 29 original reconstructed tables. |
| Table options | All 29 ROW_FORMAT Dynamic; CREATE_OPTIONS empty | **29/29 MATCH**. |
| Partitions/subpartitions | Empty set | **MATCH**: no partitioning on the original reconstructed tables. |

The local database is already upgraded through 016. Comparison deliberately
filters the **original captured key sets**: later additive tables/indexes are
not misclassified as drift. The separate original 227-column/default/type and
index/FK-rule comparison, preservation/NULL-history tests and migration/rerun
results remain attributed to their original executions. This metadata read is
not a new upgrade, restore rehearsal or functional acceptance run.

Exact per-key observations, comparisons, source/context hashes and local-read
time are in the [machine-readable comparison](evidence/owner-schema-supplement-comparison-2026-10-21.json).
The offline comparison command was
`D:/cygwin64/bin/python3.9.exe .tmp/compare-schema-supplement.py`; the local
metadata-only read used
`powershell -NoProfile -File .tmp/run-owner-schema.ps1 -Suite .tmp/read-reconstructed-physical.php`.
Those ignored local helpers are not production tools or authorization to query
the live database. The owner transcript remains outside Git; the committed
comparison contains schema metadata only.

The **six requested physical-metadata assumptions are now confirmed** by owner
observations. No repeat audit or targeted SHOW CREATE TABLE is needed from
these results. The historical fixture/report's unobserved/PARTIAL labels are
retained as correct statements of what was known at their tested commits.
DR-P01's broader actual-Azure restricted-grant/definer/PDO compatibility gate
remains PARTIAL; no whole release gate is closed by this comparison.

Remaining requirements are complete deployed application and separately
installed WebJob identities, actual role/secret configuration, legacy history
and signup continuation/cutover, safe forward-repair/rollback authorization,
independent protective replay and witnessed recovery. A multi-query metadata
capture is not proof of an atomic production snapshot or artifact identity.
OS17's unsafe baseline rollback remains INCOMPATIBLE; actual previous artifact
compatibility/recovery remain NOT RUN. Managed Azure staging remains DEFERRED,
all 18 cases NOT RUN, and SC26 production storage/topology remains PARTIAL.

**Subsequent collection received:** all 01-08 files, including the actual
installed-worker capture and application manifest, are now
[reconciled](completed-owner-evidence-reconciliation-2026-10-21.md). Do not
repeat schema steps 1-2 or installed-worker/application hashing. The recorded
script is identified and 07 establishes installed/ZIP schedule equivalence.
Whole artifact identity, dependency coverage and recovery remain separate gaps.
Capture 08 is classified locally; the linked record advances to one consolidated
code-gap delta. No repeated settings, schema or accepted hash collection is requested. PR 8 remains draft; no merge, deployment,
live grant/settings change, cleanup or real email occurred.

**Current follow-up:** [capture 09](code-gap-reconciliation-2026-10-21.md) is now
accepted; the prior code-gap collection pointer above is completed. No repeated
schema/file collection is requested. Continue the [handover review](direct-release-handover-review-2026-10-21.md).
