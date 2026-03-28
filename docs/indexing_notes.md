# Σημειώσεις Indexing

## Κρίσιμα queries που καλύπτονται

- Λίστες ατυχημάτων ανά χρήστη/ημερομηνία:
  - `idx_accidents_created_by_date`
- Λίστες ατυχημάτων ανά status:
  - `idx_accidents_status_date`
- Φίλτρα severity, ημερομηνίας, completion:
  - `idx_accidents_severity`, `idx_accidents_datetime`, `idx_accidents_entry_completed`
- Αναζήτηση πινακίδας και τύπου οχήματος:
  - `idx_vehicles_plate_number`, `idx_vehicles_vehicle_type`
- Open flags:
  - `idx_accident_flags_accident_open`
- Audit αναζήτηση ανά actor/entity/action:
  - `idx_audit_logs_actor_created`, `idx_audit_logs_entity`, `idx_audit_logs_action`

## Full-text

- `idx_accidents_summary_fts`
- `idx_vehicles_comments_fts`
- `idx_roads_comments_fts`

Προορίζονται για text search σε σύνοψη/σχόλια με `to_tsvector('simple', ...)`.
