BEGIN;

CREATE INDEX IF NOT EXISTS idx_users_role_id ON users(role_id);
CREATE INDEX IF NOT EXISTS idx_users_is_active ON users(is_active);

CREATE INDEX IF NOT EXISTS idx_invitations_email ON invitations(email);
CREATE INDEX IF NOT EXISTS idx_invitations_status ON invitations(status);
CREATE INDEX IF NOT EXISTS idx_invitations_expires_at ON invitations(expires_at);

CREATE INDEX IF NOT EXISTS idx_lookup_values_domain_sort ON lookup_values(domain_id, sort_order);
CREATE INDEX IF NOT EXISTS idx_lookup_values_domain_active ON lookup_values(domain_id, is_active);

CREATE INDEX IF NOT EXISTS idx_accidents_created_by_date ON accidents(created_by, accident_datetime DESC);
CREATE INDEX IF NOT EXISTS idx_accidents_status_date ON accidents(status_lookup_id, accident_datetime DESC);
CREATE INDEX IF NOT EXISTS idx_accidents_severity ON accidents(severity_lookup_id);
CREATE INDEX IF NOT EXISTS idx_accidents_datetime ON accidents(accident_datetime);
CREATE INDEX IF NOT EXISTS idx_accidents_entry_completed ON accidents(entry_completed);
CREATE INDEX IF NOT EXISTS idx_accidents_incident_identifier ON accidents(incident_identifier);
CREATE INDEX IF NOT EXISTS idx_accidents_coordinates ON accidents(latitude, longitude);

CREATE INDEX IF NOT EXISTS idx_accident_factors_factor ON accident_factors(factor_lookup_id);
CREATE INDEX IF NOT EXISTS idx_accident_participants_category ON accident_participant_counts(participant_category_lookup_id);

CREATE INDEX IF NOT EXISTS idx_accident_roads_road ON accident_roads(road_id);

CREATE INDEX IF NOT EXISTS idx_vehicles_accident_id ON vehicles(accident_id);
CREATE INDEX IF NOT EXISTS idx_vehicles_plate_number ON vehicles(plate_number);
CREATE INDEX IF NOT EXISTS idx_vehicles_vehicle_type ON vehicles(vehicle_type_lookup_id);
CREATE INDEX IF NOT EXISTS idx_vehicles_make_model ON vehicles(vehicle_make_id, vehicle_model_id);

CREATE INDEX IF NOT EXISTS idx_attachments_accident_id ON attachments(accident_id);
CREATE INDEX IF NOT EXISTS idx_attachments_road_id ON attachments(road_id);
CREATE INDEX IF NOT EXISTS idx_attachments_vehicle_id ON attachments(vehicle_id);
CREATE INDEX IF NOT EXISTS idx_attachments_uploaded_by ON attachments(uploaded_by);

CREATE INDEX IF NOT EXISTS idx_accident_flags_accident_open ON accident_flags(accident_id, is_open);
CREATE INDEX IF NOT EXISTS idx_accident_flags_created_by ON accident_flags(created_by);

CREATE INDEX IF NOT EXISTS idx_status_history_accident_changed ON accident_status_history(accident_id, changed_at DESC);

CREATE INDEX IF NOT EXISTS idx_audit_logs_actor_created ON audit_logs(actor_user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_action ON audit_logs(action_type);

CREATE INDEX IF NOT EXISTS idx_accidents_summary_fts
    ON accidents USING GIN (to_tsvector('simple', COALESCE(summary, '')));

CREATE INDEX IF NOT EXISTS idx_vehicles_comments_fts
    ON vehicles USING GIN (to_tsvector('simple', COALESCE(general_comments, '') || ' ' || COALESCE(damage_comments, '') || ' ' || COALESCE(defects_comments, '')));

CREATE INDEX IF NOT EXISTS idx_roads_comments_fts
    ON roads USING GIN (to_tsvector('simple', COALESCE(conditions_comments, '') || ' ' || COALESCE(possible_causes_comments, '') || ' ' || COALESCE(additional_comments, '')));

COMMIT;
