# WORKLOG: Public Ethics of Work

Worklog: Public Ethics of Work is an open-source work log for people whose work is fragmented, collective, moral, creative, pedagogical, political, and difficult to reduce to output metrics.

It is not a productivity tracker.
It is a structured system for making work visible as an ethical practice.

The app is built around the idea that work should be documented without pretending to be frictionless. It should be possible to record achievements, failures, repair, regeneration, invisible labour, and public reflection in one place.

---

## Version 1

Version 1 separates the project into two layers:

- a **private application** for logging, editing, moderating, and configuring content
- a **public log** (`log.php`) that publishes selected entries in a minimal, skin-driven format

In the private app:

- `/` is a simple public intro page
- `/admin` is the authenticated dashboard
- `/admin/entries` manages entries
- `/admin/projects` manages projects
- `/admin/reflections` moderates public reflections
- `/admin/settings` manages intro text and account settings

In the public layer:

- `log.php` renders the public monthly log
- public entries are shown by month
- reflections for selected fuckup/fail entries can be read and submitted there
- the public statistics panel is rendered from **all entries**, including private and internal ones

---

## Core ideas

- work should be documented in public without pretending to be smooth
- regeneration is part of work
- invisible work matters
- dilemmas and failures matter
- reflection can be public and dialogical
- the system should stay simple enough for daily use
- the interface should be lightweight, legible, and skinable

---

## Main features in v1

- quick daily logging
- four entry types:
  - `achievement`
  - `fuckup`
  - `regen`
  - `repair`
- visibility layers:
  - `public`
  - `private`
  - `internal`
- categories and project assignment
- public monthly log grouped by month
- public reflections for selected fuckup/fail entries
- recovery/workload balance model
- skin-based design system
- Czech/English interface
- editable intro page content in HTML

---

## Data model at a glance

### Entries

Each entry can include:

- date
- type
- title
- body
- public text
- private notes
- minutes
- category
- project
- visibility
- locale
- invisible-work flag
- optional workload / recovery overrides

For `fuckup` entries, additional reflective fields are available:

- what happened
- why it matters
- my take
- next time
- allow reflections

### Projects

Projects are stored separately and can be assigned to entries. They can be public, masked, or internal depending on how you want them to appear.

### Reflections

Reflections are user-submitted responses to selected public fuckup/fail entries. They are stored with moderation states:

- `pending`
- `approved`
- `rejected`

### Settings

The app stores settings in `app_settings`. In v1 this is mainly used for:

- intro HTML in Czech
- intro HTML in English

---

## Directory overview

Typical structure:

```text
app/
  Config/
  Controllers/
  Core/
  Lang/
  Services/
  Views/
database/
  schema.sql
  seed.sql
public/
  index.php
  log.php
  assets/
    css/
      base.css
      log.css
      skins/
README.md
```

Notes:

- `public/index.php` is the private app front controller
- `public/log.php` is the public log front controller
- `database/schema.sql` contains the schema
- `database/seed.sql` contains starter content and defaults
- `app/Views/public/log-page.php` renders the public log
- `public/assets/css/skins/` contains skin files

---

## Local setup

### Requirements

- PHP 8.1+ recommended
- PDO enabled
- SQLite or MySQL/MariaDB
- a web server, or the built-in PHP dev server

### Quick local setup with SQLite

Create the SQLite database:

```bash
mkdir -p database
touch database/worklog.sqlite
sqlite3 database/worklog.sqlite < database/schema.sql
sqlite3 database/worklog.sqlite < database/seed.sql
```

Then start the local server:

```bash
php -S localhost:8000 -t public
```

Open:

```text
http://localhost:8000/
```

Public log:

```text
http://localhost:8000/log.php
```

### MySQL / MariaDB setup

1. Create an empty database.
2. Import `database/schema.sql`.
3. Import `database/seed.sql`.
4. Update the database credentials in the project configuration used by your install.
5. Start the app through your web server or the PHP dev server.

If you are using phpMyAdmin, open the SQL tab and run the contents of `schema.sql`, then `seed.sql`.

---

## First steps after install

1. Log in to the private app.
2. Go to **Settings**.
3. Change the default password immediately.
4. Edit the public intro HTML for Czech and English.
5. Open `log.php` and check the public layer.
6. Create test entries for all four entry types.
7. Approve at least one reflection to verify the moderation loop.

---

## Public/private split

This is important.

### Private app

The private app is the working backend.
It is where you:

- create and edit entries
- create and edit projects
- moderate reflections
- change intro content
- change your password
- inspect workload / balance

### Public log

The public log is intentionally separate.
It lives in `log.php` and should stay visually lighter and simpler.

It shows:

- public entries grouped by month
- the public reflection interface for selected fail/fuckup entries
- the balance block
- the work barometer

The monthly list itself only shows public entries, but the summary statistics can be configured to draw from all entries.

---

## Balance model

The balance model in v1 assumes:

- 8 hours of sleep per day are always reserved
- workload is derived from work entries and category weights
- active recovery is derived from recovery entries and category weights
- the app computes a ratio between required recovery and actual recovery

This is meant to be simple, interpretable, and adjustable.

If you want to tune the logic, look at the service responsible for balance computation and the category weights used in the database.

---

## How to change common things

### Change the public intro page

Go to:

- `/admin/settings`

Edit:

- `intro HTML (cs)`
- `intro HTML (en)`

These fields accept HTML.

### Change the public log layout

Main files:

- `app/Views/public/log-page.php`
- `public/assets/css/log.css`

### Change the private app layout

Main files:

- `public/assets/css/base.css`
- `app/Views/...`

### Change routes

Main file:

- `app/Config/routes.php`

### Add or change translations

Main files:

- `app/Lang/cs.php`
- `app/Lang/en.php`

### Adjust public log data processing

Main file:

- `app/Services/PublicLogService.php`

### Adjust balance logic

Main file:

- `app/Services/BalanceService.php`

---

## Skin system

The project uses CSS skins.

### Where skins live

```text
public/assets/css/skins/
```

Examples:

- `zine-xerox.css`
- `amber-terminal.css`
- `win3-gray.css`
- `mac-1984-mono.css`
- `atari.css`
- `msdos.css`

### Skin contract

Each skin should define the same base variables:

```css
:root {
  --font-body: ...;
  --font-title: ...;
  --font-ui: ...;
  --font-mono: ...;

  --font-weight-title: 700;

  --color-bg: ...;
  --color-panel-bg: ...;
  --color-text: ...;
  --color-muted: ...;

  --border-strong: ...;
  --border-heavy: ...;

  --shadow-button: ...;
  --shadow-panel: ...;
}
```

The public log also uses a few optional variables:

```css
:root {
  --log-pane-title-bg: var(--color-text);
  --log-pane-title-fg: var(--color-panel-bg);

  --log-fail-bg: var(--color-text);
  --log-fail-fg: var(--color-panel-bg);
  --log-fail-border: var(--border-strong);
}
```

### Recommended pattern for skins

After defining variables, keep the skin small and consistent:

```css
body {
  background: var(--color-bg);
  color: var(--color-text);
  font-family: var(--font-body);
}

a {
  color: inherit;
}

.site-branding,
.site-controls,
.site-nav,
.card,
.footer-line,
.flash,
.page-header {
  background: var(--color-panel-bg);
  border: var(--border-strong);
  box-shadow: var(--shadow-panel);
}

.control-link,
.nav-link,
.button-link {
  border: var(--border-strong);
  background: var(--color-panel-bg);
  box-shadow: var(--shadow-button);
}

.control-link.is-active,
.nav-link.is-active {
  background: var(--color-text);
  color: var(--color-panel-bg);
}
```

### How to add a new skin

1. Create a new CSS file in `public/assets/css/skins/`.
2. Copy an existing skin.
3. Change only variables and, if really necessary, a small number of overrides.
4. Add the skin to any skin selector or skin list used by the app/public log.
5. Test both:
   - the private backend
   - `log.php`

### Skin design rule

Do not hard-code component colors and borders in multiple places unless they are truly skin-specific. Prefer variables. That keeps the backend and public log visually coherent.

---

## How to deploy the web

### Option A: proper web root to `public/` (recommended)

This is the cleanest setup.

1. Upload the whole project to the server.
2. Set the web root / document root to:

```text
public/
```

3. Create the database.
4. Import:
   - `database/schema.sql`
   - `database/seed.sql`
5. Configure database access.
6. Make sure PHP sessions work.
7. Open:
   - `/` for the intro page
   - `/login` for admin login
   - `/log.php` for the public log

### Option B: shared hosting without nested public root

If your hosting does not let you point the document root to `public/`, you have two choices:

1. keep the public files in the web root and adjust bootstrap paths carefully
2. or restructure deployment so only `public/` is exposed

If possible, prefer exposing only `public/`. It is safer and cleaner.

### Apache / Nginx note

The project is built as a plain PHP app with a front controller for the private app and a separate `log.php` entry point for the public log.

That means:

- `index.php` should receive application routes
- `log.php` should stay directly accessible as a file
- static assets should remain directly accessible

### Shared hosting checklist

- PHP version compatible
- PDO extension enabled
- sessions enabled
- database created
- schema imported
- seed imported
- document root correct
- file paths correct
- `public/log.php` accessible
- admin login changed from default password

---

## Security notes

Version 1 is intentionally lightweight, but you should still do the following:

- change the default password immediately
- keep the private app behind authentication
- expose only the public layer intentionally
- review which entries are marked public
- moderate reflections regularly
- keep backups of the database

If the app is deployed publicly, make sure the server exposes only intended files.

---

## Editorial workflow suggestion

A simple working rhythm:

1. log entries continuously during the week
2. mark only selected entries as public
3. publish failures only when you are ready for reflection
4. moderate reflections periodically
5. review the balance panel monthly
6. revise project categories and labels when needed

---

## Roadmap ideas after v1

Possible next steps:

- filters in admin lists
- export options
- richer settings
- better moderation tools
- analytics snapshots
- more granular skin controls
- stronger deployment docs for Apache / Nginx / shared hosting

---

## License

Code: AGPL-3.0

---

## Status

Early development, but version 1 now includes the full private/public split and a functioning public log workflow.
