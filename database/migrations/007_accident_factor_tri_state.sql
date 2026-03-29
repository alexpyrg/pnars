BEGIN;

INSERT INTO lookup_domains (code, label_el, source_scope)
VALUES ('yes_no_unknown', 'Απάντηση Ναι/Όχι/Δεν γνωρίζω', 'system')
ON CONFLICT (code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    source_scope = EXCLUDED.source_scope,
    updated_at = NOW();

WITH yes_no_unknown_domain AS (
    SELECT id FROM lookup_domains WHERE code = 'yes_no_unknown'
)
INSERT INTO lookup_values (domain_id, code, label_el, sort_order)
SELECT yes_no_unknown_domain.id, v.code, v.label_el, v.sort_order
FROM yes_no_unknown_domain,
     (VALUES
        ('yes', 'Ναι', 10),
        ('no', 'Όχι', 20),
        ('unknown', 'Δεν γνωρίζω', 30)
     ) AS v(code, label_el, sort_order)
ON CONFLICT (domain_id, code) DO UPDATE
SET label_el = EXCLUDED.label_el,
    sort_order = EXCLUDED.sort_order,
    is_active = TRUE,
    updated_at = NOW();

CREATE TABLE IF NOT EXISTS accident_factor_answers (
    accident_id UUID NOT NULL REFERENCES accidents(id) ON DELETE CASCADE,
    factor_lookup_id BIGINT NOT NULL REFERENCES lookup_values(id),
    answer_lookup_id BIGINT NOT NULL REFERENCES lookup_values(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by UUID NULL REFERENCES users(id),
    updated_by UUID NULL REFERENCES users(id),
    PRIMARY KEY (accident_id, factor_lookup_id)
);

CREATE INDEX IF NOT EXISTS idx_accident_factor_answers_factor ON accident_factor_answers(factor_lookup_id);
CREATE INDEX IF NOT EXISTS idx_accident_factor_answers_answer ON accident_factor_answers(answer_lookup_id);

WITH yes_answer AS (
    SELECT lv.id AS answer_lookup_id
    FROM lookup_values lv
    JOIN lookup_domains ld ON ld.id = lv.domain_id
    WHERE ld.code = 'yes_no_unknown'
      AND lv.code = 'yes'
    LIMIT 1
)
INSERT INTO accident_factor_answers (
    accident_id,
    factor_lookup_id,
    answer_lookup_id,
    created_at,
    updated_at,
    created_by,
    updated_by
)
SELECT
    af.accident_id,
    af.factor_lookup_id,
    ya.answer_lookup_id,
    af.created_at,
    NOW(),
    af.created_by,
    af.created_by
FROM accident_factors af
CROSS JOIN yes_answer ya
ON CONFLICT (accident_id, factor_lookup_id) DO NOTHING;

DROP TABLE IF EXISTS accident_factors;

COMMIT;
