SET NAMES utf8mb4;
SET time_zone = '+00:00';

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
('allow_public_reflections', '1'),
('home_intro_html_cs', '<h2>Worklog</h2><p>Tato aplikace je soukromá. Veřejný log najdeš na <a href="log.php">log.php</a>.</p>'),
('home_intro_html_en', '<h2>Worklog</h2><p>This application is private. Public log lives in <a href="log.php">log.php</a>.</p>')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value);

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
('Care / Mentoring', 'care-mentoring', 'work', 1.00, 0.00, 110),

('Regen', 'regen', 'recovery', 0.00, 1.00, 200),
('Walk / Outside', 'walk-outside', 'recovery', 0.00, 1.10, 210),
('Deep Rest', 'deep-rest', 'recovery', 0.00, 1.20, 220),
('Culture / Non-work', 'culture-nonwork', 'recovery', 0.00, 0.90, 230),
('Social Connection', 'social-connection', 'recovery', 0.00, 1.00, 240),
('Prayer / Meditation', 'prayer-meditation', 'recovery', 0.00, 1.10, 250)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    kind = VALUES(kind),
    intensity_weight = VALUES(intensity_weight),
    recovery_weight = VALUES(recovery_weight),
    sort_order = VALUES(sort_order);

/*
-- Volitelné demo projekty:
INSERT INTO projects (
    title, slug, description, public_label, visibility, status, locale, is_featured, sort_order
) VALUES
('Teaching', 'teaching', 'Umbrella project for teaching-related work.', 'teaching', 'public', 'active', 'en', 1, 10),
('Writing', 'writing', 'Umbrella project for writing projects.', 'writing', 'public', 'active', 'en', 1, 20),
('Internal Research Project', 'internal-research-project', 'Masked internal project.', 'research project', 'masked', 'active', 'en', 0, 30)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description),
    public_label = VALUES(public_label),
    visibility = VALUES(visibility),
    status = VALUES(status),
    locale = VALUES(locale),
    is_featured = VALUES(is_featured),
    sort_order = VALUES(sort_order);
*/

INSERT INTO users (username, password_hash, role, is_active)
VALUES (
    'admin',
    '$2y$12$77YtTL6ky/YOqf8d5Y3zh.IBYJB7yeB/ZhlMAfRYxa1kr7qQfPYta',
    'admin',
    1
)
ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash),
    role = VALUES(role),
    is_active = VALUES(is_active);
