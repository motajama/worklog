SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE entries
    ADD COLUMN emissions_total_kg DECIMAL(18,9) NOT NULL DEFAULT 0 AFTER recovery_override,
    ADD COLUMN emissions_status ENUM('not_rated', 'partial', 'complete') NOT NULL DEFAULT 'not_rated' AFTER emissions_total_kg,
    ADD COLUMN footprint_updated_at DATETIME NULL AFTER emissions_status;

CREATE TABLE IF NOT EXISTS footprint_factors (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    label VARCHAR(255) NOT NULL,
    category ENUM('device', 'transport', 'ai', 'energy', 'other') NOT NULL,
    base_unit ENUM('hour', 'event', 'km', 'kwh', 'token') NOT NULL,
    factor_kg_per_unit DECIMAL(18,9) NOT NULL,
    source_note TEXT NULL,
    methodology_note TEXT NULL,
    geography_code VARCHAR(20) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    editable_by_user TINYINT(1) NOT NULL DEFAULT 1,
    is_seed TINYINT(1) NOT NULL DEFAULT 0,
    valid_from DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    review_after DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_footprint_factors_user (user_id, active, category),
    CONSTRAINT fk_footprint_factors_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_footprint_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id INT UNSIGNED NOT NULL,
    factor_id INT UNSIGNED NULL,
    label_snapshot VARCHAR(255) NOT NULL,
    category_snapshot ENUM('device', 'transport', 'ai', 'energy', 'other') NOT NULL,
    base_unit_snapshot ENUM('hour', 'event', 'km', 'kwh', 'token') NOT NULL,
    factor_kg_per_unit_snapshot DECIMAL(18,9) NOT NULL,
    quantity DECIMAL(18,6) NOT NULL,
    emissions_kg DECIMAL(18,9) NOT NULL,
    factor_snapshot_json JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_event_footprint_items_event (event_id),
    KEY idx_event_footprint_items_factor (factor_id),
    CONSTRAINT fk_event_footprint_items_entry
        FOREIGN KEY (event_id) REFERENCES entries(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_event_footprint_items_factor
        FOREIGN KEY (factor_id) REFERENCES footprint_factors(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recurring_footprint_rules (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    factor_id INT UNSIGNED NOT NULL,
    label VARCHAR(255) NOT NULL,
    quantity DECIMAL(18,6) NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
    by_weekday TINYINT UNSIGNED NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_recurring_footprint_rules_user (user_id, active),
    KEY idx_recurring_footprint_rules_factor (factor_id),
    CONSTRAINT fk_recurring_footprint_rules_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_recurring_footprint_rules_factor
        FOREIGN KEY (factor_id) REFERENCES footprint_factors(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recurring_footprint_instances (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    rule_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    occurrence_date DATE NOT NULL,
    quantity DECIMAL(18,6) NOT NULL,
    emissions_kg DECIMAL(18,9) NOT NULL,
    factor_snapshot_json JSON NOT NULL,
    status ENUM('generated', 'skipped') NOT NULL DEFAULT 'generated',
    PRIMARY KEY (id),
    UNIQUE KEY uq_recurring_footprint_instances_rule_date (rule_id, occurrence_date),
    KEY idx_recurring_footprint_instances_user_date (user_id, occurrence_date),
    CONSTRAINT fk_recurring_footprint_instances_rule
        FOREIGN KEY (rule_id) REFERENCES recurring_footprint_rules(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_recurring_footprint_instances_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO footprint_factors (
    user_id, label, category, base_unit, factor_kg_per_unit, source_note,
    methodology_note, geography_code, active, editable_by_user, is_seed,
    valid_from, review_after
)
SELECT
    u.id, f.label, f.category, f.base_unit, f.factor_kg_per_unit, f.source_note,
    f.methodology_note, 'CZ', 1, 1, 1,
    NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR)
FROM users u
JOIN (
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
