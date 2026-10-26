-- Owner-run read-only SUPPLEMENT to the DOCX capture. Do not rerun the complete
-- 227-column/122-index inventory or retrieve user rows, codes, hashes or bodies.
-- Use the existing authorized certificate-verifying metadata connection.
START TRANSACTION READ ONLY;

-- The 13 captured rules omit child/referenced column mappings.
SELECT TABLE_NAME,CONSTRAINT_NAME,COLUMN_NAME,ORDINAL_POSITION,
       POSITION_IN_UNIQUE_CONSTRAINT,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION;

-- Only the previously omitted index attributes, not another full index export.
SELECT TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX,COLLATION AS sort_direction,
       IS_VISIBLE,EXPRESSION
FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE()
ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX;

-- ASCII table output cannot distinguish SQL NULL from a literal string NULL.
-- Resolve that ambiguity without returning any default contents.
SELECT TABLE_NAME,COLUMN_NAME,(COLUMN_DEFAULT IS NULL) AS default_is_sql_null
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE() AND CHARACTER_SET_NAME IS NOT NULL
  AND (COLUMN_DEFAULT IS NULL OR COLUMN_DEFAULT='NULL')
ORDER BY TABLE_NAME,ORDINAL_POSITION;

-- Check constraints were not part of the original query.
SELECT tc.TABLE_NAME,tc.CONSTRAINT_NAME,cc.CHECK_CLAUSE,tc.ENFORCED
FROM information_schema.TABLE_CONSTRAINTS tc
JOIN information_schema.CHECK_CONSTRAINTS cc
  ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME
WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.CONSTRAINT_TYPE='CHECK'
ORDER BY tc.TABLE_NAME,tc.CONSTRAINT_NAME;

-- Physical table options/partitioning omitted from the captured engine/collation.
-- No row estimates, auto-increment counters or user values are requested.
SELECT TABLE_NAME,ROW_FORMAT,CREATE_OPTIONS
FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()
ORDER BY TABLE_NAME;
SELECT TABLE_NAME,PARTITION_NAME,PARTITION_METHOD,PARTITION_EXPRESSION,
       SUBPARTITION_METHOD,SUBPARTITION_EXPRESSION
FROM information_schema.PARTITIONS
WHERE TABLE_SCHEMA=DATABASE() AND PARTITION_NAME IS NOT NULL
ORDER BY TABLE_NAME,PARTITION_ORDINAL_POSITION,SUBPARTITION_ORDINAL_POSITION;
ROLLBACK;

-- Only if these results contradict the reconstruction, obtain SHOW CREATE TABLE
-- for the affected named table(s). Do not dump all data or all table definitions.
