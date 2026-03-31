PRAGMA foreign_keys = ON;

BEGIN TRANSACTION;

-- =========================================================
-- APP SETTINGS
-- =========================================================

CREATE TABLE app_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- USERS
-- =========================================================

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'admin',
    is_active INTEGER NOT NULL DEFAULT 1
        CHECK (is_active IN (0, 1)),
    last_login_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- PROJECTS
-- =========================================================

CREATE TABLE projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    public_label VARCHAR(255),
    visibility VARCHAR(20) NOT NULL DEFAULT 'private'
        CHECK (visibility IN ('private', 'public', 'masked')),
    status VARCHAR(20) NOT NULL DEFAULT 'active'
        CHECK (status IN ('active', 'paused', 'archived', 'completed')),
    locale VARCHAR(20) NOT NULL DEFAULT 'cs'
        CHECK (locale IN ('cs', 'en', 'bilingual')),
    is_featured INTEGER NOT NULL DEFAULT 0
        CHECK (is_featured IN (0, 1)),
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- CATEGORIES
-- kind:
--   work     = contributes to workload
--   recovery = contributes to active recovery
-- =========================================================

CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    kind VARCHAR(20) NOT NULL
        CHECK (kind IN ('work', 'recovery')),
    intensity_weight DECIMAL(6,2) NOT NULL DEFAULT 1.00
        CHECK (intensity_weight >= 0),
    recovery_weight DECIMAL(6,2) NOT NULL DEFAULT 0.00
        CHECK (recovery_weight >= 0),
    is_system INTEGER NOT NULL DEFAULT 1
        CHECK (is_system IN (0, 1)),
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- ENTRIES
-- entry_type:
--   achievement
--   fuckup
--   regen
--   repair
--
-- visibility:
--   private  = only admin
--   public   = visible publicly
--   internal = internal log, not public
--
-- Notes:
-- - body is the main full text
-- - public_text can be a shorter public version
-- - private_notes stays admin-only
-- - fuckup fields are optional and used mainly for entry_type = 'fuckup'
-- - repair_of_entry_id links a repair to an older entry
-- - COPSOQ/NFR/Recovery Experience short module fields are optional
-- =========================================================

CREATE TABLE entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_date DATE NOT NULL,
    slug VARCHAR(255) UNIQUE,

    entry_type VARCHAR(20) NOT NULL
        CHECK (entry_type IN ('achievement', 'fuckup', 'regen', 'repair')),

    title VARCHAR(255),
    body TEXT NOT NULL,
    public_text TEXT,
    private_notes TEXT,

    minutes INTEGER NOT NULL DEFAULT 0
        CHECK (minutes >= 0),

    category_id INTEGER NOT NULL,
    project_id INTEGER,

    visibility VARCHAR(20) NOT NULL DEFAULT 'private'
        CHECK (visibility IN ('private', 'public', 'internal')),

    locale VARCHAR(20) NOT NULL DEFAULT 'cs'
        CHECK (locale IN ('cs', 'en', 'bilingual')),

    is_invisible_work INTEGER NOT NULL DEFAULT 0
        CHECK (is_invisible_work IN (0, 1)),

    workload_override DECIMAL(10,2),
    recovery_override DECIMAL(10,2),

    -- COPSOQ III short module (0-4)
    copsoq_quantitative_demands INTEGER
        CHECK (copsoq_quantitative_demands BETWEEN 0 AND 4),
    copsoq_work_pace INTEGER
        CHECK (copsoq_work_pace BETWEEN 0 AND 4),
    copsoq_cognitive_demands INTEGER
        CHECK (copsoq_cognitive_demands BETWEEN 0 AND 4),
    copsoq_low_control INTEGER
        CHECK (copsoq_low_control BETWEEN 0 AND 4),

    -- Need for Recovery short module (0-4)
    nfr_exhausted INTEGER
        CHECK (nfr_exhausted BETWEEN 0 AND 4),
    nfr_detach_difficulty INTEGER
        CHECK (nfr_detach_difficulty BETWEEN 0 AND 4),
    nfr_need_long_recovery INTEGER
        CHECK (nfr_need_long_recovery BETWEEN 0 AND 4),
    nfr_overload INTEGER
        CHECK (nfr_overload BETWEEN 0 AND 4),

    -- Recovery Experience short module (0-4)
    recovery_detachment INTEGER
        CHECK (recovery_detachment BETWEEN 0 AND 4),
    recovery_relaxation INTEGER
        CHECK (recovery_relaxation BETWEEN 0 AND 4),
    recovery_mastery INTEGER
        CHECK (recovery_mastery BETWEEN 0 AND 4),
    recovery_control INTEGER
        CHECK (recovery_control BETWEEN 0 AND 4),

    what_happened TEXT,
    why_it_matters TEXT,
    my_take TEXT,
    next_time TEXT,

    allow_reflections INTEGER NOT NULL DEFAULT 0
        CHECK (allow_reflections IN (0, 1)),

    repair_of_entry_id INTEGER,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (project_id)
        REFERENCES projects(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    FOREIGN KEY (repair_of_entry_id)
        REFERENCES entries(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

-- =========================================================
-- ENTRY LINKS
-- Traces / receipts / outputs related to an entry
-- =========================================================

CREATE TABLE entry_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_id INTEGER NOT NULL,
    label VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    link_type VARCHAR(30) NOT NULL DEFAULT 'external'
        CHECK (link_type IN ('external', 'repo', 'article', 'media', 'file', 'other')),
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (entry_id)
        REFERENCES entries(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- =========================================================
-- REFLECTIONS
-- Public responses to public fuckups (moderated)
-- =========================================================

CREATE TABLE reflections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_id INTEGER NOT NULL,

    author_name VARCHAR(255),
    author_email VARCHAR(255),

    body TEXT NOT NULL,

    locale VARCHAR(20) NOT NULL DEFAULT 'cs'
        CHECK (locale IN ('cs', 'en', 'bilingual')),

    status VARCHAR(20) NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'approved', 'rejected')),

    is_anonymous INTEGER NOT NULL DEFAULT 0
        CHECK (is_anonymous IN (0, 1)),

    admin_note TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME,

    FOREIGN KEY (entry_id)
        REFERENCES entries(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- =========================================================
-- INDEXES
-- =========================================================

CREATE INDEX idx_projects_status
    ON projects(status);

CREATE INDEX idx_projects_visibility
    ON projects(visibility);

CREATE INDEX idx_projects_featured_sort
    ON projects(is_featured, sort_order);

CREATE INDEX idx_categories_kind_sort
    ON categories(kind, sort_order);

CREATE INDEX idx_entries_entry_date
    ON entries(entry_date DESC);

CREATE INDEX idx_entries_type_date
    ON entries(entry_type, entry_date DESC);

CREATE INDEX idx_entries_visibility_date
    ON entries(visibility, entry_date DESC);

CREATE INDEX idx_entries_project_date
    ON entries(project_id, entry_date DESC);

CREATE INDEX idx_entries_category_date
    ON entries(category_id, entry_date DESC);

CREATE INDEX idx_entries_repair_of
    ON entries(repair_of_entry_id);

CREATE INDEX idx_reflections_status_created
    ON reflections(status, created_at DESC);

CREATE INDEX idx_reflections_entry
    ON reflections(entry_id);

CREATE INDEX idx_entry_links_entry
    ON entry_links(entry_id, sort_order);

-- =========================================================
-- VIEWS
-- Convenience views for workload / recovery calculations
-- =========================================================

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

COMMIT;
