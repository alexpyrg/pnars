-- Εκτέλεση με psql από τον φάκελο database:
-- psql -U postgres -d traffic_accidents -f schema.sql

\i migrations/001_base_schema.sql
\i migrations/002_indexes.sql
\i migrations/003_static_reference_data.sql
\i migrations/004_analytics_views.sql
\i migrations/005_performance_tuning.sql
\i migrations/006_rate_limit_buckets.sql
\i migrations/007_accident_factor_tri_state.sql
