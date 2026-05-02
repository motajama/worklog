PRAGMA foreign_keys = OFF;

BEGIN TRANSACTION;

ALTER TABLE entries ADD COLUMN emissions_total_kg DECIMAL(18,9) NOT NULL DEFAULT 0;
ALTER TABLE entries ADD COLUMN emissions_status VARCHAR(20) NOT NULL DEFAULT 'not_rated';
ALTER TABLE entries ADD COLUMN footprint_updated_at DATETIME;

CREATE TABLE IF NOT EXISTS footprint_factors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    label VARCHAR(255) NOT NULL,
    category VARCHAR(30) NOT NULL
        CHECK (category IN ('device', 'transport', 'ai', 'energy', 'other')),
    base_unit VARCHAR(20) NOT NULL
        CHECK (base_unit IN ('hour', 'event', 'km', 'kwh', 'token')),
    factor_kg_per_unit DECIMAL(18,9) NOT NULL
        CHECK (factor_kg_per_unit >= 0),
    source_note TEXT,
    methodology_note TEXT,
    geography_code VARCHAR(20),
    active INTEGER NOT NULL DEFAULT 1
        CHECK (active IN (0, 1)),
    editable_by_user INTEGER NOT NULL DEFAULT 1
        CHECK (editable_by_user IN (0, 1)),
    is_seed INTEGER NOT NULL DEFAULT 0
        CHECK (is_seed IN (0, 1)),
    valid_from DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    review_after DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS event_footprint_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id INTEGER NOT NULL,
    factor_id INTEGER,
    label_snapshot VARCHAR(255) NOT NULL,
    category_snapshot VARCHAR(30) NOT NULL,
    base_unit_snapshot VARCHAR(20) NOT NULL,
    factor_kg_per_unit_snapshot DECIMAL(18,9) NOT NULL
        CHECK (factor_kg_per_unit_snapshot >= 0),
    quantity DECIMAL(18,6) NOT NULL
        CHECK (quantity >= 0),
    emissions_kg DECIMAL(18,9) NOT NULL
        CHECK (emissions_kg >= 0),
    factor_snapshot_json TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (event_id)
        REFERENCES entries(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    FOREIGN KEY (factor_id)
        REFERENCES footprint_factors(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS recurring_footprint_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    factor_id INTEGER NOT NULL,
    label VARCHAR(255) NOT NULL,
    quantity DECIMAL(18,6) NOT NULL
        CHECK (quantity >= 0),
    frequency VARCHAR(20) NOT NULL
        CHECK (frequency IN ('daily', 'weekly', 'monthly')),
    by_weekday INTEGER
        CHECK (by_weekday BETWEEN 1 AND 7),
    start_date DATE NOT NULL,
    end_date DATE,
    active INTEGER NOT NULL DEFAULT 1
        CHECK (active IN (0, 1)),

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    FOREIGN KEY (factor_id)
        REFERENCES footprint_factors(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS recurring_footprint_instances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rule_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    occurrence_date DATE NOT NULL,
    quantity DECIMAL(18,6) NOT NULL
        CHECK (quantity >= 0),
    emissions_kg DECIMAL(18,9) NOT NULL
        CHECK (emissions_kg >= 0),
    factor_snapshot_json TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'generated'
        CHECK (status IN ('generated', 'skipped')),

    UNIQUE (rule_id, occurrence_date),

    FOREIGN KEY (rule_id)
        REFERENCES recurring_footprint_rules(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_footprint_factors_user
    ON footprint_factors(user_id, active, category);

CREATE INDEX IF NOT EXISTS idx_event_footprint_items_event
    ON event_footprint_items(event_id);

CREATE INDEX IF NOT EXISTS idx_recurring_footprint_rules_user
    ON recurring_footprint_rules(user_id, active);

CREATE INDEX IF NOT EXISTS idx_recurring_footprint_instances_user_date
    ON recurring_footprint_instances(user_id, occurrence_date);

INSERT INTO footprint_factors (
    user_id, label, category, base_unit, factor_kg_per_unit, source_note,
    methodology_note, geography_code, active, editable_by_user, is_seed,
    valid_from, review_after
)
SELECT
    u.id, f.label, f.category, f.base_unit, f.factor_kg_per_unit, f.source_note,
    f.methodology_note, 'CZ', 1, 1, 1,
    CURRENT_TIMESTAMP, datetime('now', '+1 year')
FROM users u
CROSS JOIN (
    SELECT 'Apple MacBook Air A1466 Linux' AS label, 'device' AS category, 'hour' AS base_unit, 0.004 AS factor_kg_per_unit, 'user estimate (~4 g/h)' AS source_note, 'Editable estimate stored as kgCO2e per hour.' AS methodology_note
    UNION ALL SELECT 'Desktop PC: Teams + Firefox', 'device', 'hour', 0.045, 'editable user estimate', 'Editable estimate stored as kgCO2e per hour.'
    UNION ALL SELECT 'Train trip CZ', 'transport', 'km', 0.035, 'editable user estimate', 'Editable estimate stored as kgCO2e per km.'
    UNION ALL SELECT 'Tram trip CZ', 'transport', 'km', 0.010, 'editable user estimate', 'Editable estimate stored as kgCO2e per km.'
    UNION ALL SELECT 'OpenAI standard use', 'ai', 'event', 0.0002, 'editable user estimate', 'AI footprint is an editable estimate, not a measured truth.'
    UNION ALL SELECT 'Codex coding session', 'ai', 'hour', 0.020, 'editable user estimate', 'AI footprint is an editable estimate, not a measured truth.'
    UNION ALL SELECT 'Token use (configured estimate)', 'ai', 'token', 0.0000000002, 'configurable app estimate', 'Default comes from app.footprint.token_kg_per_token.'
) f
WHERE NOT EXISTS (
    SELECT 1 FROM footprint_factors existing WHERE existing.user_id = u.id
);

COMMIT;

PRAGMA foreign_keys = ON;
