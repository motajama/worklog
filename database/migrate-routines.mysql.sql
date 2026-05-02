CREATE TABLE IF NOT EXISTS routines (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    label VARCHAR(255) NOT NULL,
    description TEXT NULL,
    occurrences_per_week DECIMAL(8,3) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_routines_user (user_id, active, start_date),
    CONSTRAINT fk_routines_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS routine_footprint_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    routine_id INT UNSIGNED NOT NULL,
    factor_id INT UNSIGNED NULL,
    label_snapshot VARCHAR(255) NOT NULL,
    category_snapshot ENUM('device', 'transport', 'ai', 'energy', 'other') NOT NULL,
    base_unit_snapshot ENUM('hour', 'event', 'km', 'kwh', 'token') NOT NULL,
    factor_kg_per_unit_snapshot DECIMAL(18,9) NOT NULL,
    quantity DECIMAL(18,6) NOT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    emissions_kg DECIMAL(18,9) NOT NULL,
    factor_snapshot_json JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_routine_footprint_items_routine (routine_id),
    KEY idx_routine_footprint_items_factor (factor_id),
    CONSTRAINT fk_routine_footprint_items_routine
        FOREIGN KEY (routine_id) REFERENCES routines(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_routine_footprint_items_factor
        FOREIGN KEY (factor_id) REFERENCES footprint_factors(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
