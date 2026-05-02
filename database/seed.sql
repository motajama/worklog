PRAGMA foreign_keys = ON;

BEGIN TRANSACTION;

-- =========================================================
-- APP SETTINGS
-- =========================================================

INSERT INTO app_settings (setting_key, setting_value) VALUES
('site_name', 'Public Ethics of Work'),
('site_tagline_cs', 'veřejná etika práce'),
('site_tagline_en', 'public ethics of work'),
('default_locale', 'cs'),
('default_skin', 'mac-1984-mono'),
('sleep_minutes_per_day', '480'),
('recovery_base_minutes', '30'),
('recovery_multiplier', '0.35'),
('short_window_days', '7'),
('long_window_days', '30'),
('allow_public_reflections', '1');

-- =========================================================
-- USERS
-- =========================================================

INSERT INTO users (username, password_hash, role, is_active) VALUES
('admin', '$2y$12$77YtTL6ky/YOqf8d5Y3zh.IBYJB7yeB/ZhlMAfRYxa1kr7qQfPYta', 'admin', 1);

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

-- =========================================================
-- CATEGORIES
-- WORK
-- intensity_weight = workload multiplier
-- recovery_weight  = usually 0 for work
-- =========================================================

INSERT INTO categories (name, slug, kind, intensity_weight, recovery_weight, sort_order) VALUES
('Teaching', 'teaching', 'work', 1.00, 0.00, 10),
('Teaching Prep', 'teaching-prep', 'work', 1.10, 0.00, 20),
('Research / Reading', 'research-reading', 'work', 0.90, 0.00, 30),
('Academic Writing', 'academic-writing', 'work', 1.30, 0.00, 40),
('Grant Writing', 'grant-writing', 'work', 1.40, 0.00, 50),
('Public Writing / Media', 'public-writing-media', 'work', 1.20, 0.00, 60),
('Art Practice', 'art-practice', 'work', 1.20, 0.00, 70),
('Technical Work', 'technical-work', 'work', 1.20, 0.00, 80),
('Meetings / Consultations', 'meetings-consultations', 'work', 1.00, 0.00, 90),
('Administration', 'administration', 'work', 0.80, 0.00, 100),
('Care / Mentoring', 'care-mentoring', 'work', 1.00, 0.00, 110);

-- =========================================================
-- CATEGORIES
-- RECOVERY
-- recovery_weight = active recovery multiplier
-- =========================================================

INSERT INTO categories (name, slug, kind, intensity_weight, recovery_weight, sort_order) VALUES
('Regen', 'regen', 'recovery', 0.00, 1.00, 200),
('Walk / Outside', 'walk-outside', 'recovery', 0.00, 1.10, 210),
('Deep Rest', 'deep-rest', 'recovery', 0.00, 1.20, 220),
('Culture / Non-work', 'culture-nonwork', 'recovery', 0.00, 0.90, 230),
('Social Connection', 'social-connection', 'recovery', 0.00, 1.00, 240),
('Prayer / Meditation', 'prayer-meditation', 'recovery', 0.00, 1.10, 250);

-- =========================================================
-- OPTIONAL DEMO PROJECTS
-- Uncomment if you want starter data
-- =========================================================
--
-- INSERT INTO projects (
--     title, slug, description, public_label, visibility, status, locale, is_featured, sort_order
-- ) VALUES
-- ('Teaching', 'teaching', 'Umbrella project for teaching-related work.', 'teaching', 'public', 'active', 'en', 1, 10),
-- ('Writing', 'writing', 'Umbrella project for writing projects.', 'writing', 'public', 'active', 'en', 1, 20),
-- ('Internal Research Project', 'internal-research-project', 'Masked internal project.', 'research project', 'masked', 'active', 'en', 0, 30);

INSERT INTO app_settings (setting_key, setting_value) VALUES
('home_intro_html_cs', '<h2>Worklog</h2><p>Tato aplikace je soukromá. Veřejný log najdeš na <a href="log.php">log.php</a>.</p>'),
('home_intro_html_en', '<h2>Worklog</h2><p>This application is private. Public log lives in <a href="log.php">log.php</a>.</p>');

COMMIT;
