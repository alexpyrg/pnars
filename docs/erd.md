# Σχέσεις Οντοτήτων (ER Summary)

- `roles (1) -> (N) users`
- `users (1) -> (N) accidents` μέσω `created_by`
- `accidents (1) -> (N) vehicles`
- `accidents (N) <-> (N) roads` μέσω `accident_roads` με περιορισμό 1-2 roads ανά accident
- `accidents (1) -> (N) accident_factors`
- `accidents (1) -> (N) accident_participant_counts`
- `accidents/roads/vehicles (1) -> (N) attachments` (με check ακριβώς ένας parent)
- `accidents (1) -> (N) accident_flags`
- `accidents (1) -> (N) accident_status_history`
- `users (1) -> (N) audit_logs`
- `lookup_domains (1) -> (N) lookup_values`
- `vehicle_manufacturers (1) -> (N) vehicle_models`
