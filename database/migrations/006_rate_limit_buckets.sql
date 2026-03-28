BEGIN;

CREATE TABLE IF NOT EXISTS rate_limit_buckets (
    key CHAR(64) PRIMARY KEY,
    attempt_count INTEGER NOT NULL DEFAULT 0 CHECK (attempt_count >= 0),
    first_attempt_at TIMESTAMPTZ NOT NULL,
    blocked_until TIMESTAMPTZ NULL,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_rate_limit_buckets_updated_at
    ON rate_limit_buckets(updated_at DESC);

COMMIT;
