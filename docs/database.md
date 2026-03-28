# Στρατηγική Βάσης και Migrations

## Migration order

1. `001_base_schema.sql`
   - extensions
   - core tables
   - constraints
   - triggers `updated_at`
2. `002_indexes.sql`
   - indexing για list/search/report workloads
3. `003_static_reference_data.sql`
   - roles
   - system lookup domains/values (statuses, flags, categories)
4. `004_analytics_views.sql`
   - Grafana-friendly SQL views

## Runner

Χρησιμοποιείται το `cli/migrate.php`.

- Αποθηκεύει εφαρμοσμένα migrations στο `schema_migrations`
- Ελέγχει checksum για ασφάλεια
- Δεν επιτρέπει σιωπηρή αλλαγή ήδη εφαρμοσμένου migration

## Data integrity highlights

- `attachments`: ακριβώς ένας parent (`accident_id` ή `road_id` ή `vehicle_id`)
- `accident_roads`: μέγιστο 2 δρόμοι ανά ατύχημα μέσω `road_order IN (1,2)` + `UNIQUE(accident_id, road_order)`
- `accident_flags`: consistency check για open/closed κατάσταση και resolved στοιχεία
- `users.email`: `citext` + unique

## Αναζήτηση / reporting

- Στοχευμένα B-tree indexes για status/date/creator/plate/type
- GIN full-text indexes για σύνοψη/σχόλια
- Views για χρονοσειρές, καιρικά μοτίβα, τύπους οχημάτων, δραστηριότητα καταχωρητών
