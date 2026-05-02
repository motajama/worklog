<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Core\DB;

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';

App::boot();

$pdo = DB::connection();
$driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

$columnExists = static function (string $table, string $column) use ($pdo, $driver): bool {
    if ($driver === 'sqlite') {
        $stmt = $pdo->query('PRAGMA table_info(' . $table . ')');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (($row['name'] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }

    $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE :column');
    $stmt->execute(['column' => $column]);

    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
};

$tableExists = static function (string $table) use ($pdo, $driver): bool {
    if ($driver === 'sqlite') {
        $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :table");
        $stmt->execute(['table' => $table]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    $stmt = $pdo->prepare(
        'SELECT TABLE_NAME
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
         LIMIT 1'
    );
    $stmt->execute(['table' => $table]);

    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
};

$addColumn = static function (string $column, string $sqliteDefinition, string $mysqlDefinition) use ($columnExists, $driver): void {
    if ($columnExists('entries', $column)) {
        return;
    }

    DB::execute('ALTER TABLE entries ADD COLUMN ' . ($driver === 'sqlite' ? $sqliteDefinition : $mysqlDefinition));
};

$addColumn(
    'emissions_total_kg',
    'emissions_total_kg DECIMAL(18,9) NOT NULL DEFAULT 0',
    'emissions_total_kg DECIMAL(18,9) NOT NULL DEFAULT 0 AFTER recovery_override'
);
$addColumn(
    'emissions_status',
    "emissions_status VARCHAR(20) NOT NULL DEFAULT 'not_rated'",
    "emissions_status ENUM('not_rated', 'partial', 'complete') NOT NULL DEFAULT 'not_rated' AFTER emissions_total_kg"
);
$addColumn(
    'footprint_updated_at',
    'footprint_updated_at DATETIME',
    'footprint_updated_at DATETIME NULL AFTER emissions_status'
);

if (!$tableExists('footprint_factors')) {
    DB::execute($driver === 'sqlite'
        ? "CREATE TABLE footprint_factors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            label VARCHAR(255) NOT NULL,
            category VARCHAR(30) NOT NULL CHECK (category IN ('device', 'transport', 'ai', 'energy', 'other')),
            base_unit VARCHAR(20) NOT NULL CHECK (base_unit IN ('hour', 'event', 'km', 'kwh')),
            factor_kg_per_unit DECIMAL(18,9) NOT NULL CHECK (factor_kg_per_unit >= 0),
            source_note TEXT,
            methodology_note TEXT,
            geography_code VARCHAR(20),
            active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
            editable_by_user INTEGER NOT NULL DEFAULT 1 CHECK (editable_by_user IN (0, 1)),
            is_seed INTEGER NOT NULL DEFAULT 0 CHECK (is_seed IN (0, 1)),
            valid_from DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            review_after DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
        )"
        : "CREATE TABLE footprint_factors (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            label VARCHAR(255) NOT NULL,
            category ENUM('device', 'transport', 'ai', 'energy', 'other') NOT NULL,
            base_unit ENUM('hour', 'event', 'km', 'kwh') NOT NULL,
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
            CONSTRAINT fk_footprint_factors_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

if (!$tableExists('event_footprint_items')) {
    DB::execute($driver === 'sqlite'
        ? "CREATE TABLE event_footprint_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id INTEGER NOT NULL,
            factor_id INTEGER,
            label_snapshot VARCHAR(255) NOT NULL,
            category_snapshot VARCHAR(30) NOT NULL,
            base_unit_snapshot VARCHAR(20) NOT NULL,
            factor_kg_per_unit_snapshot DECIMAL(18,9) NOT NULL CHECK (factor_kg_per_unit_snapshot >= 0),
            quantity DECIMAL(18,6) NOT NULL CHECK (quantity >= 0),
            emissions_kg DECIMAL(18,9) NOT NULL CHECK (emissions_kg >= 0),
            factor_snapshot_json TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES entries(id) ON UPDATE CASCADE ON DELETE CASCADE,
            FOREIGN KEY (factor_id) REFERENCES footprint_factors(id) ON UPDATE CASCADE ON DELETE SET NULL
        )"
        : "CREATE TABLE event_footprint_items (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id INT UNSIGNED NOT NULL,
            factor_id INT UNSIGNED NULL,
            label_snapshot VARCHAR(255) NOT NULL,
            category_snapshot ENUM('device', 'transport', 'ai', 'energy', 'other') NOT NULL,
            base_unit_snapshot ENUM('hour', 'event', 'km', 'kwh') NOT NULL,
            factor_kg_per_unit_snapshot DECIMAL(18,9) NOT NULL,
            quantity DECIMAL(18,6) NOT NULL,
            emissions_kg DECIMAL(18,9) NOT NULL,
            factor_snapshot_json JSON NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event_footprint_items_event (event_id),
            KEY idx_event_footprint_items_factor (factor_id),
            CONSTRAINT fk_event_footprint_items_entry FOREIGN KEY (event_id) REFERENCES entries(id) ON UPDATE CASCADE ON DELETE CASCADE,
            CONSTRAINT fk_event_footprint_items_factor FOREIGN KEY (factor_id) REFERENCES footprint_factors(id) ON UPDATE CASCADE ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

if (!$tableExists('recurring_footprint_rules')) {
    DB::execute($driver === 'sqlite'
        ? "CREATE TABLE recurring_footprint_rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            factor_id INTEGER NOT NULL,
            label VARCHAR(255) NOT NULL,
            quantity DECIMAL(18,6) NOT NULL CHECK (quantity >= 0),
            frequency VARCHAR(20) NOT NULL CHECK (frequency IN ('daily', 'weekly', 'monthly')),
            by_weekday INTEGER CHECK (by_weekday BETWEEN 1 AND 7),
            start_date DATE NOT NULL,
            end_date DATE,
            active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
            FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
            FOREIGN KEY (factor_id) REFERENCES footprint_factors(id) ON UPDATE CASCADE ON DELETE RESTRICT
        )"
        : "CREATE TABLE recurring_footprint_rules (
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
            CONSTRAINT fk_recurring_footprint_rules_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
            CONSTRAINT fk_recurring_footprint_rules_factor FOREIGN KEY (factor_id) REFERENCES footprint_factors(id) ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

if (!$tableExists('recurring_footprint_instances')) {
    DB::execute($driver === 'sqlite'
        ? "CREATE TABLE recurring_footprint_instances (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            rule_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            occurrence_date DATE NOT NULL,
            quantity DECIMAL(18,6) NOT NULL CHECK (quantity >= 0),
            emissions_kg DECIMAL(18,9) NOT NULL CHECK (emissions_kg >= 0),
            factor_snapshot_json TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'generated' CHECK (status IN ('generated', 'skipped')),
            UNIQUE (rule_id, occurrence_date),
            FOREIGN KEY (rule_id) REFERENCES recurring_footprint_rules(id) ON UPDATE CASCADE ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
        )"
        : "CREATE TABLE recurring_footprint_instances (
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
            CONSTRAINT fk_recurring_footprint_instances_rule FOREIGN KEY (rule_id) REFERENCES recurring_footprint_rules(id) ON UPDATE CASCADE ON DELETE CASCADE,
            CONSTRAINT fk_recurring_footprint_instances_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

if ($driver === 'sqlite') {
    DB::execute('CREATE INDEX IF NOT EXISTS idx_footprint_factors_user ON footprint_factors(user_id, active, category)');
    DB::execute('CREATE INDEX IF NOT EXISTS idx_event_footprint_items_event ON event_footprint_items(event_id)');
    DB::execute('CREATE INDEX IF NOT EXISTS idx_recurring_footprint_rules_user ON recurring_footprint_rules(user_id, active)');
    DB::execute('CREATE INDEX IF NOT EXISTS idx_recurring_footprint_instances_user_date ON recurring_footprint_instances(user_id, occurrence_date)');
}

$seedSql = $driver === 'sqlite'
    ? "INSERT INTO footprint_factors (
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
    ) f
    WHERE NOT EXISTS (
        SELECT 1 FROM footprint_factors existing WHERE existing.user_id = u.id
    )"
    : "INSERT INTO footprint_factors (
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
    ) f
    WHERE NOT EXISTS (
        SELECT 1 FROM footprint_factors existing WHERE existing.user_id = u.id
    )";

DB::execute($seedSql);

echo 'Footprint migration complete.' . PHP_EOL;
