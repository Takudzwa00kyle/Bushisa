-- Run through bin/migrate.php, which binds the fixed schema literal below.
-- Runtime receives EXECUTE on this fixed read-only procedure, never TRIGGER/DDL.
-- The definer must retain metadata visibility; missing routine/definer fails closed.
CREATE PROCEDURE bushisa_pair_trigger_health()
SQL SECURITY DEFINER
READS SQL DATA
SELECT COUNT(*) AS genesis_trigger_present
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = '__BUSHISA_CURRENT_SCHEMA__'
  AND TRIGGER_NAME = 'bushisa_pair_account_genesis'
  AND EVENT_MANIPULATION = 'INSERT'
  AND EVENT_OBJECT_TABLE = 'users'
  AND ACTION_TIMING = 'AFTER';
