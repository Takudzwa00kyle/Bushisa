-- Owner-run ONLY, via an already authorized TLS-verified connection to the
-- selected application schema. No credentials, user rows or challenge bodies.
-- Keep raw output private; review SQL principal names before sharing.
START TRANSACTION READ ONLY;
SELECT UTC_TIMESTAMP(6) AS captured_utc, DATABASE() AS selected_schema,
       VERSION() AS mysql_version, CURRENT_USER() AS effective_principal,
       @@session.time_zone AS session_time_zone, @@global.time_zone AS global_time_zone,
       @@session.sql_mode AS sql_mode, @@session.transaction_isolation AS isolation,
       @@character_set_server AS character_set_server, @@collation_server AS collation_server,
       @@require_secure_transport AS require_secure_transport,
       @@log_bin AS log_bin, @@log_bin_trust_function_creators AS log_bin_trust_function_creators;
SHOW SESSION STATUS WHERE Variable_name IN ('Ssl_cipher','Ssl_version');
-- MySQL 8 SHOW GRANTS omits authentication secrets. Collect separately for the
-- current runtime/worker/migration principals; do not add mysql.user queries.
SHOW GRANTS FOR CURRENT_USER;
SELECT table_name,engine,table_collation
FROM information_schema.tables WHERE table_schema=DATABASE() ORDER BY table_name;
SELECT table_name,column_name,ordinal_position,column_type,is_nullable,
       column_default,extra,character_set_name,collation_name
FROM information_schema.columns WHERE table_schema=DATABASE()
ORDER BY table_name,ordinal_position;
SELECT table_name,index_name,non_unique,seq_in_index,column_name,sub_part,index_type
FROM information_schema.statistics WHERE table_schema=DATABASE()
ORDER BY table_name,index_name,seq_in_index;
SELECT table_name,constraint_name,referenced_table_name,update_rule,delete_rule
FROM information_schema.referential_constraints
WHERE constraint_schema=DATABASE() ORDER BY table_name,constraint_name;
SELECT trigger_name,event_manipulation,event_object_table,action_timing,
       SHA2(REPLACE(action_statement,CHAR(13),''),256) AS body_sha256
FROM information_schema.triggers WHERE trigger_schema=DATABASE() ORDER BY trigger_name;
SELECT routine_name,routine_type,security_type,sql_data_access,
       SHA2(REPLACE(routine_definition,CHAR(13),''),256) AS body_sha256
FROM information_schema.routines WHERE routine_schema=DATABASE() ORDER BY routine_name;
-- Run this final SELECT only if schema_migrations was listed above. Failure
-- means UNKNOWN, never permission to create or mark migration rows.
SELECT version,applied_at FROM schema_migrations ORDER BY version;
ROLLBACK;
