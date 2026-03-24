SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS app_settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_app_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT NULL,
    public_label VARCHAR(255) NULL,
    visibility ENUM('private', 'public', 'masked') NOT NULL DEFAULT 'private',
    status ENUM('active', 'paused', 'archived', 'completed') NOT NULL DEFAULT 'active',
    locale ENUM('cs', 'en', 'bilingual') NOT NULL DEFAULT 'cs',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_projects_slug (slug),
    KEY idx_projects_status (status),
    KEY idx_projects_visibility (visibility),
    KEY idx_projects_featured_sort (is_featured, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    kind ENUM('work', 'recovery') NOT NULL,
    intensity_weight DECIMAL(6,2) NOT NULL DEFAULT 1.00,
    recovery_weight DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_kind_sort (kind, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    entry_date DATE NOT NULL,
    slug VARCHAR(255) NULL,

    entry_type ENUM('achievement', 'fuckup', 'regen', 'repair') NOT NULL,

    title VARCHAR(255) NULL,
    body TEXT NOT NULL,
    public_text TEXT NULL,
    private_notes TEXT NULL,

    minutes INT UNSIGNED NOT NULL DEFAULT 0,

    category_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED NULL,

    visibility ENUM('private', 'public', 'internal') NOT NULL DEFAULT 'private',
    locale ENUM('cs', 'en', 'bilingual') NOT NULL DEFAULT 'cs',

    is_invisible_work TINYINT(1) NOT NULL DEFAULT 0,

    workload_override DECIMAL(10,2) NULL,
    recovery_override DECIMAL(10,2) NULL,

    what_happened TEXT NULL,
    why_it_matters TEXT NULL,
    my_take TEXT NULL,
    next_time TEXT NULL,

    allow_reflections TINYINT(1) NOT NULL DEFAULT 0,

    repair_of_entry_id INT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_entries_slug (slug),
    KEY idx_entries_entry_date (entry_date DESC),
    KEY idx_entries_type_date (entry_type, entry_date DESC),
    KEY idx_entries_visibility_date (visibility, entry_date DESC),
    KEY idx_entries_project_date (project_id, entry_date DESC),
    KEY idx_entries_category_date (category_id, entry_date DESC),
    KEY idx_entries_repair_of (repair_of_entry_id),

    CONSTRAINT fk_entries_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_entries_project
        FOREIGN KEY (project_id) REFERENCES projects(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_entries_repair_of
        FOREIGN KEY (repair_of_entry_id) REFERENCES entries(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entry_links (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    entry_id INT UNSIGNED NOT NULL,
    label VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    link_type ENUM('external', 'repo', 'article', 'media', 'file', 'other') NOT NULL DEFAULT 'external',
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_entry_links_entry (entry_id, sort_order),
    CONSTRAINT fk_entry_links_entry
        FOREIGN KEY (entry_id) REFERENCES entries(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reflections (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    entry_id INT UNSIGNED NOT NULL,

    author_name VARCHAR(255) NULL,
    author_email VARCHAR(255) NULL,

    body TEXT NOT NULL,

    locale ENUM('cs', 'en', 'bilingual') NOT NULL DEFAULT 'cs',

    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',

    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,

    admin_note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_reflections_status_created (status, created_at DESC),
    KEY idx_reflections_entry (entry_id),

    CONSTRAINT fk_reflections_entry
        FOREIGN KEY (entry_id) REFERENCES entries(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP VIEW IF EXISTS v_entry_metrics;
CREATE VIEW v_entry_metrics AS
SELECT
    e.id,
    e.entry_date,
    e.entry_type,
    e.title,
    e.minutes,
    e.visibility,
    e.project_id,
    e.category_id,
    c.name AS category_name,
    c.slug AS category_slug,
    c.kind AS category_kind,
    c.intensity_weight,
    c.recovery_weight,
    COALESCE(
        e.workload_override,
        CASE
            WHEN c.kind = 'work' THEN ROUND(e.minutes * c.intensity_weight, 2)
            ELSE 0
        END
    ) AS workload_points,
    COALESCE(
        e.recovery_override,
        CASE
            WHEN c.kind = 'recovery' THEN ROUND(e.minutes * c.recovery_weight, 2)
            ELSE 0
        END
    ) AS recovery_points
FROM entries e
INNER JOIN categories c ON c.id = e.category_id;

DROP VIEW IF EXISTS v_daily_totals;
CREATE VIEW v_daily_totals AS
SELECT
    e.entry_date,
    COUNT(e.id) AS total_entries,
    SUM(e.minutes) AS total_logged_minutes,
    SUM(CASE WHEN c.kind = 'work' THEN e.minutes ELSE 0 END) AS work_minutes,
    SUM(CASE WHEN c.kind = 'recovery' THEN e.minutes ELSE 0 END) AS active_recovery_minutes,
    ROUND(SUM(
        COALESCE(
            e.workload_override,
            CASE
                WHEN c.kind = 'work' THEN e.minutes * c.intensity_weight
                ELSE 0
            END
        )
    ), 2) AS workload_points,
    ROUND(SUM(
        COALESCE(
            e.recovery_override,
            CASE
                WHEN c.kind = 'recovery' THEN e.minutes * c.recovery_weight
                ELSE 0
            END
        )
    ), 2) AS recovery_points
FROM entries e
INNER JOIN categories c ON c.id = e.category_id
GROUP BY e.entry_date
ORDER BY e.entry_date DESC;
