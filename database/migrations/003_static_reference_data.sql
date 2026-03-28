BEGIN;

INSERT INTO roles (code, label_el)
VALUES
    ('registrar', 'Καταχωρητής'),
    ('expert', 'Εμπειρογνώμονας'),
    ('administrator', 'Διαχειριστής')
ON CONFLICT (code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    updated_at = NOW();

INSERT INTO lookup_domains (code, label_el, source_scope)
VALUES
    ('accident_status', 'Κατάσταση εγγραφής ατυχήματος', 'system'),
    ('flag_type', 'Τύποι σήμανσης', 'system'),
    ('information_source', 'Πηγή πληροφόρησης', 'system'),
    ('confidence_level', 'Βαθμός βεβαιότητας', 'system'),
    ('investigation_method', 'Μέθοδος διερεύνησης', 'system'),
    ('investigation_confidence_level', 'Βαθμός βεβαιότητας διερεύνησης', 'system'),
    ('accident_day', 'Ημέρα ατυχήματος', 'system'),
    ('participant_category', 'Κατηγορίες συμμετεχόντων', 'system')
ON CONFLICT (code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    source_scope = EXCLUDED.source_scope,
    updated_at = NOW();

WITH accident_status_domain AS (
    SELECT id FROM lookup_domains WHERE code = 'accident_status'
)
INSERT INTO lookup_values (domain_id, code, label_el, sort_order)
SELECT accident_status_domain.id, v.code, v.label_el, v.sort_order
FROM accident_status_domain,
     (VALUES
        ('draft', 'Πρόχειρο', 10),
        ('submitted', 'Υποβλημένο', 20),
        ('flagged', 'Σε σήμανση', 30),
        ('resolved', 'Επιλυμένο', 40),
        ('closed', 'Κλειστό', 50)
     ) AS v(code, label_el, sort_order)
ON CONFLICT (domain_id, code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    sort_order = EXCLUDED.sort_order,
    updated_at = NOW();

WITH flag_type_domain AS (
    SELECT id FROM lookup_domains WHERE code = 'flag_type'
)
INSERT INTO lookup_values (domain_id, code, label_el, sort_order)
SELECT flag_type_domain.id, v.code, v.label_el, v.sort_order
FROM flag_type_domain,
     (VALUES
        ('incomplete', 'Ελλιπή στοιχεία', 10),
        ('needs_correction', 'Απαιτείται διόρθωση', 20),
        ('unclear_data', 'Ασαφή δεδομένα', 30),
        ('inconsistent_data', 'Ασυνεπή δεδομένα', 40)
     ) AS v(code, label_el, sort_order)
ON CONFLICT (domain_id, code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    sort_order = EXCLUDED.sort_order,
    updated_at = NOW();

WITH info_source_domain AS (
    SELECT id FROM lookup_domains WHERE code = 'information_source'
)
INSERT INTO lookup_values (domain_id, code, label_el, sort_order)
SELECT info_source_domain.id, v.code, v.label_el, v.sort_order
FROM info_source_domain,
     (VALUES
        ('police_report', 'Δελτίο συμβάντος', 10),
        ('expert_observation', 'Αυτοψία εμπειρογνώμονα', 20),
        ('witness_statement', 'Κατάθεση μάρτυρα', 30),
        ('vehicle_data', 'Τεχνικά δεδομένα οχήματος', 40),
        ('other', 'Άλλη πηγή', 50)
     ) AS v(code, label_el, sort_order)
ON CONFLICT (domain_id, code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    sort_order = EXCLUDED.sort_order,
    updated_at = NOW();

WITH confidence_domain AS (
    SELECT id FROM lookup_domains WHERE code = 'confidence_level'
)
INSERT INTO lookup_values (domain_id, code, label_el, sort_order)
SELECT confidence_domain.id, v.code, v.label_el, v.sort_order
FROM confidence_domain,
     (VALUES
        ('high', 'Υψηλή', 10),
        ('medium', 'Μέτρια', 20),
        ('low', 'Χαμηλή', 30),
        ('unknown', 'Άγνωστη', 40)
     ) AS v(code, label_el, sort_order)
ON CONFLICT (domain_id, code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    sort_order = EXCLUDED.sort_order,
    updated_at = NOW();

WITH method_domain AS (
    SELECT id FROM lookup_domains WHERE code = 'investigation_method'
)
INSERT INTO lookup_values (domain_id, code, label_el, sort_order)
SELECT method_domain.id, v.code, v.label_el, v.sort_order
FROM method_domain,
     (VALUES
        ('onsite', 'Επιτόπια διερεύνηση', 10),
        ('desk_review', 'Διοικητική ανασκόπηση', 20),
        ('hybrid', 'Συνδυαστική μέθοδος', 30)
     ) AS v(code, label_el, sort_order)
ON CONFLICT (domain_id, code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    sort_order = EXCLUDED.sort_order,
    updated_at = NOW();

WITH inv_conf_domain AS (
    SELECT id FROM lookup_domains WHERE code = 'investigation_confidence_level'
)
INSERT INTO lookup_values (domain_id, code, label_el, sort_order)
SELECT inv_conf_domain.id, v.code, v.label_el, v.sort_order
FROM inv_conf_domain,
     (VALUES
        ('high', 'Υψηλή', 10),
        ('medium', 'Μέτρια', 20),
        ('low', 'Χαμηλή', 30),
        ('unknown', 'Άγνωστη', 40)
     ) AS v(code, label_el, sort_order)
ON CONFLICT (domain_id, code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    sort_order = EXCLUDED.sort_order,
    updated_at = NOW();

WITH day_domain AS (
    SELECT id FROM lookup_domains WHERE code = 'accident_day'
)
INSERT INTO lookup_values (domain_id, code, label_el, sort_order)
SELECT day_domain.id, v.code, v.label_el, v.sort_order
FROM day_domain,
     (VALUES
        ('monday', 'Δευτέρα', 10),
        ('tuesday', 'Τρίτη', 20),
        ('wednesday', 'Τετάρτη', 30),
        ('thursday', 'Πέμπτη', 40),
        ('friday', 'Παρασκευή', 50),
        ('saturday', 'Σάββατο', 60),
        ('sunday', 'Κυριακή', 70)
     ) AS v(code, label_el, sort_order)
ON CONFLICT (domain_id, code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    sort_order = EXCLUDED.sort_order,
    updated_at = NOW();

WITH participant_domain AS (
    SELECT id FROM lookup_domains WHERE code = 'participant_category'
)
INSERT INTO lookup_values (domain_id, code, label_el, sort_order)
SELECT participant_domain.id, v.code, v.label_el, v.sort_order
FROM participant_domain,
     (VALUES
        ('sedan_limousine', 'Λιμουζίνα / Sedan', 10),
        ('compact_hatchback', 'Κόμπακτ / Hatchback', 20),
        ('coupe_caravan', 'Κουπέ / Καραβάν', 30),
        ('coupe_sports', 'Κουπέ / Σπορ', 40),
        ('professional_vehicle', 'Επαγγελματικό όχημα', 50),
        ('four_by_four', '4x4 / Εκτός δρόμου', 60),
        ('suv', 'SUV', 70),
        ('electric', 'Ηλεκτρικό', 80),
        ('autonomous', 'Αυτόνομο', 90),
        ('pedestrian', 'Πεζός', 100),
        ('unknown', 'Άγνωστο', 110),
        ('van', 'Βαν', 120),
        ('truck', 'Φορτηγό', 130),
        ('trailer', 'Ρυμουλκούμενο', 140),
        ('bus', 'Λεωφορείο', 150),
        ('agricultural_vehicle', 'Αγροτικό όχημα', 160),
        ('motorcycle', 'Μοτοσικλέτα', 170),
        ('bicycle', 'Ποδήλατο', 180),
        ('other_two_wheeler', 'Άλλο δίτροχο', 190),
        ('tricycle', 'Τρίτροχο', 200),
        ('other', 'Άλλο', 210)
     ) AS v(code, label_el, sort_order)
ON CONFLICT (domain_id, code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    sort_order = EXCLUDED.sort_order,
    updated_at = NOW();

COMMIT;
