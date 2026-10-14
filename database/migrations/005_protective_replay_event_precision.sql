-- Upgrade replay event timestamps to microsecond precision so distinct
-- protective events for the same subject cannot collide within one second.
ALTER TABLE protective_replay_events
    MODIFY occurred_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    MODIFY replayed_at TIMESTAMP(6) NULL;
