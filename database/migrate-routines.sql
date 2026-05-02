CREATE TABLE IF NOT EXISTS routines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    label VARCHAR(255) NOT NULL,
    description TEXT,
    occurrences_per_week DECIMAL(8,3) NOT NULL CHECK (occurrences_per_week > 0),
    start_date DATE NOT NULL,
    end_date DATE,
    active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS routine_footprint_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    routine_id INTEGER NOT NULL,
    factor_id INTEGER,
    label_snapshot VARCHAR(255) NOT NULL,
    category_snapshot VARCHAR(30) NOT NULL,
    base_unit_snapshot VARCHAR(20) NOT NULL,
    factor_kg_per_unit_snapshot DECIMAL(18,9) NOT NULL CHECK (factor_kg_per_unit_snapshot >= 0),
    quantity DECIMAL(18,6) NOT NULL CHECK (quantity >= 0),
    duration_minutes INTEGER NOT NULL DEFAULT 0 CHECK (duration_minutes >= 0),
    emissions_kg DECIMAL(18,9) NOT NULL CHECK (emissions_kg >= 0),
    factor_snapshot_json TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (routine_id) REFERENCES routines(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (factor_id) REFERENCES footprint_factors(id) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_routines_user
    ON routines(user_id, active, start_date);

CREATE INDEX IF NOT EXISTS idx_routine_footprint_items_routine
    ON routine_footprint_items(routine_id);
