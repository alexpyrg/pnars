BEGIN;

CREATE OR REPLACE VIEW vw_accident_overview AS
SELECT
    a.id,
    a.case_number,
    a.accident_datetime,
    a.created_at,
    a.entry_completed,
    a.longitude,
    a.latitude,
    creator.full_name AS registrar_name,
    creator.id AS registrar_id,
    status_lv.code AS status_code,
    status_lv.label_el AS status_label,
    severity_lv.code AS severity_code,
    severity_lv.label_el AS severity_label,
    source_lv.code AS source_code,
    source_lv.label_el AS source_label,
    confidence_lv.code AS confidence_code,
    confidence_lv.label_el AS confidence_label,
    EXISTS (
        SELECT 1
        FROM accident_flags af
        WHERE af.accident_id = a.id AND af.is_open = TRUE
    ) AS has_open_flag
FROM accidents a
JOIN users creator ON creator.id = a.created_by
LEFT JOIN lookup_values status_lv ON status_lv.id = a.status_lookup_id
LEFT JOIN lookup_values severity_lv ON severity_lv.id = a.severity_lookup_id
LEFT JOIN lookup_values source_lv ON source_lv.id = a.information_source_lookup_id
LEFT JOIN lookup_values confidence_lv ON confidence_lv.id = a.confidence_level_lookup_id
WHERE a.deleted_at IS NULL;

CREATE OR REPLACE VIEW vw_accidents_over_time AS
SELECT
    date_trunc('day', accident_datetime)::date AS day,
    COUNT(*) AS total_accidents,
    COUNT(*) FILTER (WHERE has_open_flag) AS flagged_accidents
FROM vw_accident_overview
GROUP BY 1
ORDER BY 1;

CREATE OR REPLACE VIEW vw_accident_weather AS
SELECT
    weather_lv.code AS weather_code,
    weather_lv.label_el AS weather_label,
    COUNT(DISTINCT ar.accident_id) AS accidents_count
FROM accident_roads ar
JOIN roads r ON r.id = ar.road_id
LEFT JOIN lookup_values weather_lv ON weather_lv.id = r.weather_condition_lookup_id
GROUP BY weather_lv.code, weather_lv.label_el
ORDER BY accidents_count DESC;

CREATE OR REPLACE VIEW vw_vehicle_type_distribution AS
SELECT
    vt.code AS vehicle_type_code,
    vt.label_el AS vehicle_type_label,
    COUNT(*) AS vehicles_count
FROM vehicles v
LEFT JOIN lookup_values vt ON vt.id = v.vehicle_type_lookup_id
WHERE v.deleted_at IS NULL
GROUP BY vt.code, vt.label_el
ORDER BY vehicles_count DESC;

CREATE OR REPLACE VIEW vw_registrar_activity AS
SELECT
    u.id AS registrar_id,
    u.full_name AS registrar_name,
    COUNT(a.id) AS accidents_created,
    COUNT(a.id) FILTER (WHERE status_lv.code = 'submitted') AS submitted_count,
    COUNT(a.id) FILTER (WHERE EXISTS (
        SELECT 1 FROM accident_flags af WHERE af.accident_id = a.id AND af.is_open = TRUE
    )) AS currently_flagged_count
FROM users u
LEFT JOIN accidents a ON a.created_by = u.id AND a.deleted_at IS NULL
LEFT JOIN lookup_values status_lv ON status_lv.id = a.status_lookup_id
GROUP BY u.id, u.full_name;

COMMIT;
