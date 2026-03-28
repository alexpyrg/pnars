BEGIN;

CREATE EXTENSION IF NOT EXISTS pg_trgm;

-- Accidents: optimize common active-record filters and ordering
CREATE INDEX IF NOT EXISTS idx_accidents_active_datetime_created
    ON accidents(accident_datetime DESC, created_at DESC)
    WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_accidents_active_created_by_datetime
    ON accidents(created_by, accident_datetime DESC)
    WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_accidents_active_status_datetime
    ON accidents(status_lookup_id, accident_datetime DESC)
    WHERE deleted_at IS NULL;

-- Trigram indexes for ILIKE-based search fields
CREATE INDEX IF NOT EXISTS idx_accidents_case_number_trgm
    ON accidents USING GIN (case_number gin_trgm_ops)
    WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_accidents_incident_identifier_trgm
    ON accidents USING GIN (incident_identifier gin_trgm_ops)
    WHERE deleted_at IS NULL AND incident_identifier IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_vehicles_plate_number_trgm_active
    ON vehicles USING GIN (plate_number gin_trgm_ops)
    WHERE deleted_at IS NULL AND plate_number IS NOT NULL;

-- Vehicles: speed up accident + type existence checks
CREATE INDEX IF NOT EXISTS idx_vehicles_active_accident_type
    ON vehicles(accident_id, vehicle_type_lookup_id)
    WHERE deleted_at IS NULL;

-- Flags: open-flag checks appear in list filters and badges
CREATE INDEX IF NOT EXISTS idx_accident_flags_open_only
    ON accident_flags(accident_id)
    WHERE is_open = TRUE;

-- Audit log browsing on time-desc order and date filtering
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at_desc
    ON audit_logs(created_at DESC);

COMMIT;
