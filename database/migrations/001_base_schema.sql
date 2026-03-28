BEGIN;

CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS citext;

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TABLE IF NOT EXISTS roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(50) NOT NULL UNIQUE,
    label_el VARCHAR(120) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email CITEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    full_name VARCHAR(180) NOT NULL,
    role_id UUID NOT NULL REFERENCES roles(id),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at TIMESTAMPTZ NULL,
    created_by UUID NULL REFERENCES users(id) ON DELETE SET NULL,
    updated_by UUID NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS invitations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email CITEXT NOT NULL,
    role_id UUID NOT NULL REFERENCES roles(id),
    token_hash CHAR(64) NOT NULL UNIQUE,
    status VARCHAR(30) NOT NULL CHECK (status IN ('pending', 'accepted', 'expired', 'revoked')),
    expires_at TIMESTAMPTZ NOT NULL,
    accepted_at TIMESTAMPTZ NULL,
    invited_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS lookup_import_runs (
    id BIGSERIAL PRIMARY KEY,
    source_path TEXT NOT NULL,
    checksum CHAR(64) NOT NULL,
    status VARCHAR(30) NOT NULL CHECK (status IN ('running', 'completed', 'failed')),
    notes TEXT NULL,
    imported_by UUID NULL REFERENCES users(id) ON DELETE SET NULL,
    imported_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS lookup_domains (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    label_el VARCHAR(180) NOT NULL,
    source_scope VARCHAR(50) NOT NULL CHECK (source_scope IN ('accident', 'road', 'vehicle', 'system')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS lookup_values (
    id BIGSERIAL PRIMARY KEY,
    domain_id BIGINT NOT NULL REFERENCES lookup_domains(id) ON DELETE CASCADE,
    code VARCHAR(120) NOT NULL,
    label_el TEXT NOT NULL,
    description_el TEXT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (domain_id, code)
);

CREATE TABLE IF NOT EXISTS vehicle_manufacturers (
    id BIGSERIAL PRIMARY KEY,
    external_code INTEGER NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS vehicle_models (
    id BIGSERIAL PRIMARY KEY,
    external_code INTEGER NOT NULL UNIQUE,
    manufacturer_id BIGINT NOT NULL REFERENCES vehicle_manufacturers(id) ON DELETE RESTRICT,
    name VARCHAR(180) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (manufacturer_id, name)
);

CREATE TABLE IF NOT EXISTS accidents (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    case_number VARCHAR(120) NOT NULL UNIQUE,
    entry_completed BOOLEAN NOT NULL DEFAULT FALSE,
    accident_datetime TIMESTAMPTZ NOT NULL,
    accident_day_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    expert_arrival_datetime TIMESTAMPTZ NULL,
    longitude NUMERIC(10, 7) NULL CHECK (longitude BETWEEN -180 AND 180),
    latitude NUMERIC(10, 7) NULL CHECK (latitude BETWEEN -90 AND 90),
    incident_identifier VARCHAR(120) NULL,
    severity_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    drugs_involved_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    alcohol_involved_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    hit_and_run_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    animal_collision_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    separate_events_count INTEGER NOT NULL DEFAULT 1 CHECK (separate_events_count >= 0),
    gdv_type_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    gadas_type_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    sequence_of_events TEXT NULL,
    first_harmful_event_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    most_harmful_event_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    participants_total INTEGER NOT NULL DEFAULT 0 CHECK (participants_total >= 0),
    summary TEXT NULL,
    information_source_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    confidence_level_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    confidence_description TEXT NULL,
    investigation_method_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    investigation_confidence_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    investigation_confidence_description TEXT NULL,
    status_lookup_id BIGINT NOT NULL REFERENCES lookup_values(id),
    created_by UUID NOT NULL REFERENCES users(id),
    updated_by UUID NULL REFERENCES users(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ NULL
);

CREATE TABLE IF NOT EXISTS accident_factors (
    accident_id UUID NOT NULL REFERENCES accidents(id) ON DELETE CASCADE,
    factor_lookup_id BIGINT NOT NULL REFERENCES lookup_values(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by UUID NULL REFERENCES users(id),
    PRIMARY KEY (accident_id, factor_lookup_id)
);

CREATE TABLE IF NOT EXISTS accident_participant_counts (
    accident_id UUID NOT NULL REFERENCES accidents(id) ON DELETE CASCADE,
    participant_category_lookup_id BIGINT NOT NULL REFERENCES lookup_values(id),
    participant_count INTEGER NOT NULL DEFAULT 0 CHECK (participant_count >= 0),
    PRIMARY KEY (accident_id, participant_category_lookup_id)
);

CREATE TABLE IF NOT EXISTS roads (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    traffic_flow_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    lanes_count INTEGER NULL CHECK (lanes_count >= 0),
    surface_type_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    speed_limit_kmh INTEGER NULL CHECK (speed_limit_kmh BETWEEN 0 AND 300),
    speed_limit_type_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    intersection_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    local_area_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    road_alignment_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    construction_zone_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    traffic_control_signs_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    traffic_signal_operation_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    road_surface_condition_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    pedestrian_infrastructure_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    bicycle_infrastructure_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    lighting_condition_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    weather_condition_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    strong_winds_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    fog_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    conditions_comments TEXT NULL,
    road_defects_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    temporary_factors_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    signaling_related_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    speed_restriction_infrastructure_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    speed_restriction_contributed_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    possible_causes_comments TEXT NULL,
    additional_comments TEXT NULL,
    information_source_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    confidence_level_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    confidence_description TEXT NULL,
    investigation_method_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    investigation_confidence_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    investigation_confidence_description TEXT NULL,
    created_by UUID NOT NULL REFERENCES users(id),
    updated_by UUID NULL REFERENCES users(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ NULL
);

CREATE TABLE IF NOT EXISTS accident_roads (
    accident_id UUID NOT NULL REFERENCES accidents(id) ON DELETE CASCADE,
    road_id UUID NOT NULL REFERENCES roads(id) ON DELETE CASCADE,
    road_order SMALLINT NOT NULL CHECK (road_order IN (1, 2)),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by UUID NULL REFERENCES users(id),
    PRIMARY KEY (accident_id, road_id),
    UNIQUE (accident_id, road_order)
);

CREATE TABLE IF NOT EXISTS vehicles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    accident_id UUID NOT NULL REFERENCES accidents(id) ON DELETE CASCADE,
    plate_number VARCHAR(20) NULL,
    vehicle_type_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    vehicle_make_id BIGINT NULL REFERENCES vehicle_manufacturers(id),
    vehicle_model_id BIGINT NULL REFERENCES vehicle_models(id),
    vehicle_color_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    drive_wheels_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    steering_position_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    length_mm INTEGER NULL CHECK (length_mm >= 0),
    width_mm INTEGER NULL CHECK (width_mm >= 0),
    road_alignment_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    towing_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    engine_power_kw INTEGER NULL CHECK (engine_power_kw >= 0),
    manufacturing_year INTEGER NULL CHECK (manufacturing_year BETWEEN 1900 AND 2100),
    curb_weight_kg INTEGER NULL CHECK (curb_weight_kg >= 0),
    axles_count INTEGER NULL CHECK (axles_count >= 0),
    general_comments TEXT NULL,
    passengers_count INTEGER NULL CHECK (passengers_count >= 0),
    defects_caused_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    defects_comments TEXT NULL,
    technical_inspection_passed_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    maneuver_before_accident_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    dangerous_load_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    dangerous_load_dispersion_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    collisions_count INTEGER NULL CHECK (collisions_count >= 0),
    damage_comments TEXT NULL,
    cdc3_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    cdc4_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    on_fire_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    firefighting_material_used_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    collision_offroad_object_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    collision_type_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    abs_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    esp_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    tcs_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    acs_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    ldw_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    css_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    safety_systems_comments TEXT NULL,
    information_source_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    confidence_level_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    confidence_description TEXT NULL,
    investigation_method_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    investigation_confidence_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    investigation_confidence_description TEXT NULL,
    created_by UUID NOT NULL REFERENCES users(id),
    updated_by UUID NULL REFERENCES users(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ NULL
);

CREATE TABLE IF NOT EXISTS attachments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    accident_id UUID NULL REFERENCES accidents(id) ON DELETE CASCADE,
    road_id UUID NULL REFERENCES roads(id) ON DELETE CASCADE,
    vehicle_id UUID NULL REFERENCES vehicles(id) ON DELETE CASCADE,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size_bytes BIGINT NOT NULL CHECK (file_size_bytes > 0),
    storage_path TEXT NOT NULL,
    uploaded_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ NULL,
    deleted_by UUID NULL REFERENCES users(id),
    CHECK (((accident_id IS NOT NULL)::integer + (road_id IS NOT NULL)::integer + (vehicle_id IS NOT NULL)::integer) = 1)
);

CREATE TABLE IF NOT EXISTS accident_flags (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    accident_id UUID NOT NULL REFERENCES accidents(id) ON DELETE CASCADE,
    flag_type_lookup_id BIGINT NOT NULL REFERENCES lookup_values(id),
    note TEXT NULL,
    is_open BOOLEAN NOT NULL DEFAULT TRUE,
    created_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    resolved_by UUID NULL REFERENCES users(id),
    resolved_at TIMESTAMPTZ NULL,
    resolution_note TEXT NULL,
    CHECK (
        (is_open = TRUE AND resolved_by IS NULL AND resolved_at IS NULL)
        OR
        (is_open = FALSE AND resolved_by IS NOT NULL AND resolved_at IS NOT NULL)
    )
);

CREATE TABLE IF NOT EXISTS accident_status_history (
    id BIGSERIAL PRIMARY KEY,
    accident_id UUID NOT NULL REFERENCES accidents(id) ON DELETE CASCADE,
    from_status_lookup_id BIGINT NULL REFERENCES lookup_values(id),
    to_status_lookup_id BIGINT NOT NULL REFERENCES lookup_values(id),
    changed_by UUID NOT NULL REFERENCES users(id),
    changed_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    note TEXT NULL
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGSERIAL PRIMARY KEY,
    actor_user_id UUID NULL REFERENCES users(id) ON DELETE SET NULL,
    action_type VARCHAR(80) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id VARCHAR(100) NULL,
    summary TEXT NOT NULL,
    before_data JSONB NULL,
    after_data JSONB NULL,
    ip_address INET NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

DROP TRIGGER IF EXISTS roles_set_updated_at ON roles;
CREATE TRIGGER roles_set_updated_at BEFORE UPDATE ON roles FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS users_set_updated_at ON users;
CREATE TRIGGER users_set_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS invitations_set_updated_at ON invitations;
CREATE TRIGGER invitations_set_updated_at BEFORE UPDATE ON invitations FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS lookup_domains_set_updated_at ON lookup_domains;
CREATE TRIGGER lookup_domains_set_updated_at BEFORE UPDATE ON lookup_domains FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS lookup_values_set_updated_at ON lookup_values;
CREATE TRIGGER lookup_values_set_updated_at BEFORE UPDATE ON lookup_values FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS vehicle_manufacturers_set_updated_at ON vehicle_manufacturers;
CREATE TRIGGER vehicle_manufacturers_set_updated_at BEFORE UPDATE ON vehicle_manufacturers FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS vehicle_models_set_updated_at ON vehicle_models;
CREATE TRIGGER vehicle_models_set_updated_at BEFORE UPDATE ON vehicle_models FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS accidents_set_updated_at ON accidents;
CREATE TRIGGER accidents_set_updated_at BEFORE UPDATE ON accidents FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS roads_set_updated_at ON roads;
CREATE TRIGGER roads_set_updated_at BEFORE UPDATE ON roads FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS vehicles_set_updated_at ON vehicles;
CREATE TRIGGER vehicles_set_updated_at BEFORE UPDATE ON vehicles FOR EACH ROW EXECUTE FUNCTION set_updated_at();

COMMIT;
