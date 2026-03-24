<?php
$monthGroups = $month_groups ?? [];
$workMix = $work_mix ?? ['days' => 180, 'total_hours_label' => '0 h', 'rows' => []];
$balance = $balance ?? null;
$balanceDays = $balance_days ?? 30;
$workMixDays = $work_mix_days ?? 180;

$isEn = current_locale() === 'en';
$currentSkin = current_skin();
$currentYear = date('Y');

$copy = $isEn
    ? [
        'title' => 'log',
        'subtitle' => 'public ethics of work',
        'description' => 'A public work log: what moved, what failed, what needed repair, and how recovery relates to workload.',
        'switch_cs' => 'CZ',
        'switch_en' => 'EN',
        'balance_heading' => 'balance / last %d days',
        'work_heading' => 'work barometer / last %d days',
        'work_total' => 'total work time across all entries: %s',
        'no_work_data' => 'no work data yet.',
        'entries_label' => 'entries in range',
        'work_total_label' => 'work total',
        'balance_ratio_label' => 'balance ratio',
        'status_label' => 'status',
        'empty_month' => '—',
        'reflections' => 'Reflections',
        'no_reflections' => 'No approved reflections yet.',
        'name' => 'name',
        'email' => 'email',
        'reflection' => 'reflection',
        'anonymous' => 'send anonymously',
        'send' => 'send reflection',
        'anonymous_author' => 'anonymous',
        'nameless_author' => 'no name',
        'panel_intro_title' => 'reflection pane',
        'panel_intro_text' => 'Click “↗ Reflections” next to an entry and the thread will open here. The pane stays fixed while the page moves underneath it.',
        'fail_badge' => 'FAIL',
    ]
    : [
        'title' => 'log',
        'subtitle' => 'veřejná etika práce',
        'description' => 'Veřejný pracovní log: co se pohnulo, co se nepovedlo, co potřebovalo opravu a jak obnova odpovídá workloadu.',
        'switch_cs' => 'CZ',
        'switch_en' => 'EN',
        'balance_heading' => 'balance / last %d days',
        'work_heading' => 'work barometer / last %d days',
        'work_total' => 'celkový pracovní čas napříč všemi entries: %s',
        'no_work_data' => 'zatím žádná work data.',
        'entries_label' => 'entries v období',
        'work_total_label' => 'work total',
        'balance_ratio_label' => 'balance ratio',
        'status_label' => 'status',
        'empty_month' => '—',
        'reflections' => 'Reflexe',
        'no_reflections' => 'Zatím žádná schválená reflexe.',
        'name' => 'jméno',
        'email' => 'e-mail',
        'reflection' => 'reflexe',
        'anonymous' => 'odeslat anonymně',
        'send' => 'odeslat reflexi',
        'anonymous_author' => 'anonym',
        'nameless_author' => 'bez jména',
        'panel_intro_title' => 'panel reflexí',
        'panel_intro_text' => 'Klikni na „↗ Reflexe“ u konkrétního entry a vlákno se otevře tady. Panel zůstává fixovaný a stránka pod ním plyne.',
        'fail_badge' => 'FAKAP',
    ];
?>
<!DOCTYPE html>
<html lang="<?php echo e(current_locale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($copy['title']); ?> — <?php echo e(config('app.app_name')); ?></title>
    <meta name="description" content="<?php echo e($copy['description']); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/skins/' . current_skin() . '.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/log.css')); ?>">

    <style>
        :root {
            --panel-width: 420px;
            --page-max-width: 1440px;
            --page-padding: 1.5rem;
            --layout-gap: 2.8rem;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
        }

        .log-page {
            max-width: var(--page-max-width);
            margin: 0 auto;
            padding: 2rem var(--page-padding) 4rem;
        }

        .log-header {
            margin-bottom: 1.5rem;
        }

        .log-header h1 {
            margin: 0 0 0.35rem;
            font-size: 2rem;
            line-height: 1.1;
        }

        .log-header p {
            margin: 0;
        }

        .log-intro {
            max-width: 78ch;
            margin-top: 0.8rem;
            line-height: 1.65;
        }

        .locale-switch-row {
            margin: 1rem 0 1.8rem;
        }

        .locale-switch {
            display: inline-flex;
            gap: 0.35rem;
            align-items: center;
        }

        .locale-switch a {
            text-decoration: none;
            border: 1px solid currentColor;
            padding: 0.18rem 0.42rem;
        }

        .locale-switch a.is-active {
            font-weight: 700;
        }

        .log-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) var(--panel-width);
            gap: var(--layout-gap);
            align-items: start;
        }

        .log-left {
            min-width: 0;
        }

        .log-right {
            width: var(--panel-width);
            min-height: 1px;
        }

        .log-right-inner {
            position: fixed;
            top: 0.75rem;
            right: max(var(--page-padding), calc((100vw - var(--page-max-width)) / 2 + var(--page-padding)));
            width: var(--panel-width);
            max-height: calc(100vh - 1.5rem);
        }

        .reflection-pane {
            border: 3px solid #000;
            background: #fff;
            box-shadow: 4px 4px 0 #000;
            padding: 0.9rem 1rem 1.1rem;
            max-height: calc(100vh - 1.5rem);
            overflow-y: auto;
        }

        .pane-titlebar {
            margin: 0 10px 0.95rem;
            background: #000;
            color: #fff;
            text-align: center;
            padding: 0.32rem 0.55rem;
            font-family: "Monaco", "Courier New", monospace;
            font-size: 0.92rem;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .reflection-pane h2 {
            margin: 0 0 0.55rem;
            font-size: 1rem;
            text-transform: lowercase;
        }

        .reflection-pane-meta {
            margin: 0 0 1rem;
            font-size: 0.88rem;
            opacity: 0.8;
            line-height: 1.5;
        }

        .reflection-pane-body {
            display: none;
        }

        .reflection-pane-body.is-active {
            display: block;
        }

        .log-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.8rem;
            margin-bottom: 2.8rem;
        }

        .log-section {
            margin-bottom: 2.5rem;
        }

        .log-section h2 {
            margin: 0 0 0.85rem;
            font-size: 1.05rem;
            text-transform: lowercase;
            letter-spacing: 0.01em;
        }

        .log-meta {
            margin: 0 0 0.9rem;
            opacity: 0.8;
        }

        .stats-table,
        .work-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .stats-table col:first-child,
        .work-table col:first-child {
            width: 58%;
        }

        .stats-table col:last-child,
        .work-table col:last-child {
            width: 42%;
        }

        .stats-table td,
        .work-table td {
            padding: 0.12rem 0;
            vertical-align: top;
            font-family: "Monaco", "Courier New", monospace;
            font-size: 0.94rem;
            line-height: 1.55;
        }

        .stats-table td:first-child,
        .work-table td:first-child {
            padding-right: 1rem;
        }

        .stats-table td:last-child,
        .work-table td:last-child {
            text-align: right;
            white-space: nowrap;
        }

        .therm-row td {
            padding-top: 0.08rem;
            padding-bottom: 0.38rem;
            text-align: left !important;
        }

        .therm-bar {
            display: block;
            font-family: "Monaco", "Courier New", monospace;
            white-space: pre-wrap;
        }

        .month-block {
            padding-top: 1.1rem;
            border-top: 1px solid currentColor;
        }

        .log-list {
            margin: 0;
            padding-left: 1.4rem;
        }

        .log-list li + li {
            margin-top: 0.95rem;
        }

        .log-entry-line {
            line-height: 1.7;
            max-width: 80ch;
        }

        .entry-title {
            font-weight: 700;
        }

        .entry-sep {
            opacity: 0.8;
            margin: 0 0.28rem;
            font-family: "Monaco", "Courier New", monospace;
        }

        .fail-badge {
            display: inline-block;
            margin-right: 0.5rem;
            padding: 0.08rem 0.42rem;
            background: #b30000;
            color: #ffd94d;
            border: 1px solid #7a0000;
            font-family: "Monaco", "Courier New", monospace;
            font-size: 0.82rem;
            line-height: 1.2;
            vertical-align: baseline;
            white-space: nowrap;
        }

        .entry-reflection-trigger {
            display: inline-block;
            margin-left: 0.42rem;
            white-space: nowrap;
            text-decoration: none;
            border-bottom: 1px solid currentColor;
        }

        .entry-reflection-trigger:hover {
            text-decoration: none;
            opacity: 0.8;
        }

        .reflection-list {
            display: grid;
            gap: 0;
            margin-bottom: 1.15rem;
        }

        .reflection-item {
            padding: 0.15rem 0 0.35rem;
        }

        .reflection-item + .reflection-item {
            margin-top: 0.95rem;
            padding-top: 0.95rem;
            border-top: 2px dotted currentColor;
        }

        .reflection-meta {
            font-size: 0.86rem;
            opacity: 0.8;
            margin-bottom: 0.28rem;
        }

        .reflection-body {
            line-height: 1.65;
        }

        .reflection-form {
            display: grid;
            gap: 0.82rem;
            margin-top: 1.2rem;
            padding-top: 1rem;
            border-top: 2px solid currentColor;
        }

        .reflection-form .form-row {
            display: grid;
            gap: 0.34rem;
        }

        .reflection-form input,
        .reflection-form textarea,
        .reflection-form button {
            width: 100%;
            padding: 0.52rem 0.58rem;
            border: 2px solid #000;
            background: #fff;
            color: inherit;
            font: inherit;
        }

        .reflection-form button {
            cursor: pointer;
            box-shadow: 2px 2px 0 #000;
        }

        .reflection-form .checkbox-row {
            display: block;
        }

        .reflection-form .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .reflection-form .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .log-footer {
            margin-top: 3rem;
            padding-top: 1rem;
            border-top: 1px solid currentColor;
            font-size: 0.92rem;
        }

        .muted-line {
            opacity: 0.75;
            line-height: 1.6;
        }

        @media (max-width: 1180px) {
            .log-layout {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .log-right {
                width: auto;
            }

            .log-right-inner {
                position: static;
                width: auto;
                max-height: none;
            }

            .reflection-pane {
                max-height: none;
                overflow: visible;
            }
        }

        @media (max-width: 900px) {
            .log-summary-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        @media (max-width: 720px) {
            .log-page {
                padding: 1.25rem 0.95rem 3rem;
            }

            .log-header h1 {
                font-size: 1.6rem;
            }

            .stats-table td,
            .work-table td {
                font-size: 0.88rem;
            }
        }
    </style>
</head>
<body>
    <main class="log-page">
        <header class="log-header">
            <h1><?php echo e($copy['title']); ?></h1>
            <p><?php echo e($copy['subtitle']); ?></p>
            <p class="log-intro"><?php echo e($copy['description']); ?></p>
        </header>

        <div class="locale-switch-row">
            <nav class="locale-switch" aria-label="Language switch">
                <a
                    href="<?php echo e('log.php?lang=cs&skin=' . rawurlencode($currentSkin)); ?>"
                    class="<?php echo !$isEn ? 'is-active' : ''; ?>"
                >
                    <?php echo e($copy['switch_cs']); ?>
                </a>
                <a
                    href="<?php echo e('log.php?lang=en&skin=' . rawurlencode($currentSkin)); ?>"
                    class="<?php echo $isEn ? 'is-active' : ''; ?>"
                >
                    <?php echo e($copy['switch_en']); ?>
                </a>
            </nav>
        </div>

        <div class="log-layout">
            <section class="log-left">
                <div class="log-summary-grid">
                    <?php if ($balance): ?>
                        <section class="log-section">
                            <h2><?php echo e(sprintf($copy['balance_heading'], (int) $balanceDays)); ?></h2>

                            <table class="stats-table">
                                <colgroup>
                                    <col>
                                    <col>
                                </colgroup>
                                <tbody>
                                    <tr>
                                        <td><?php echo e($copy['entries_label']); ?></td>
                                        <td><?php echo e((string) $balance['entry_count']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo e($copy['work_total_label']); ?></td>
                                        <td><?php echo e($balance['work_hours_label']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo e($copy['balance_ratio_label']); ?></td>
                                        <td><?php echo e($balance['balance_ratio_label']); ?></td>
                                    </tr>
                                    <tr class="therm-row">
                                        <td colspan="2">
                                            <span class="therm-bar"><?php echo e($balance['balance_bar']); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><?php echo e($copy['status_label']); ?></td>
                                        <td><?php echo e($balance['balance_status']); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>
                    <?php endif; ?>

                    <section class="log-section">
                        <h2><?php echo e(sprintf($copy['work_heading'], (int) $workMixDays)); ?></h2>
                        <p class="log-meta">
                            <?php echo e(sprintf($copy['work_total'], $workMix['total_hours_label'])); ?>
                        </p>

                        <?php if (empty($workMix['rows'])): ?>
                            <p><?php echo e($copy['no_work_data']); ?></p>
                        <?php else: ?>
                            <table class="work-table">
                                <colgroup>
                                    <col>
                                    <col>
                                </colgroup>
                                <tbody>
                                    <?php foreach ($workMix['rows'] as $row): ?>
                                        <tr>
                                            <td><?php echo e($row['label']); ?></td>
                                            <td><?php echo e($row['percent'] . '% · ' . $row['hours_label']); ?></td>
                                        </tr>
                                        <tr class="therm-row">
                                            <td colspan="2">
                                                <span class="therm-bar"><?php echo e($row['bar']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </section>
                </div>

                <?php foreach ($monthGroups as $group): ?>
                    <section class="log-section month-block">
                        <h2><?php echo e($group['label']); ?></h2>

                        <?php if (empty($group['entries'])): ?>
                            <p><?php echo e($copy['empty_month']); ?></p>
                        <?php else: ?>
                            <ul class="log-list">
                                <?php foreach ($group['entries'] as $entry): ?>
                                    <?php
                                    $hasReflectionThread = $entry['entry_type'] === 'fuckup' && (int) $entry['allow_reflections'] === 1;
                                    $panelId = 'reflection-pane-' . (int) $entry['id'];
                                    ?>
                                    <li id="entry-<?php echo e((string) $entry['id']); ?>">
                                        <div class="log-entry-line">
                                            <?php if ($entry['entry_type'] === 'fuckup'): ?>
                                                <span class="fail-badge">>> <?php echo e($copy['fail_badge']); ?> <<</span>
                                            <?php endif; ?>

                                            <?php if (!empty($entry['title'])): ?>
                                                <span class="entry-title"><?php echo e($entry['title']); ?></span>
                                                <span class="entry-sep">::</span>
                                            <?php endif; ?>

                                            <?php echo e($entry['text']); ?>

                                            <?php if ($hasReflectionThread): ?>
                                                <a
                                                    href="#<?php echo e($panelId); ?>"
                                                    class="entry-reflection-trigger"
                                                    data-reflection-target="<?php echo e($panelId); ?>"
                                                >
                                                    ↗ <?php echo e($copy['reflections']); ?>
                                                    <?php if (!empty($entry['reflections'])): ?>
                                                        (<?php echo e((string) count($entry['reflections'])); ?>)
                                                    <?php endif; ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </section>

            <aside class="log-right">
                <div class="log-right-inner">
                    <div class="reflection-pane">
                        <div class="pane-titlebar">>> <?php echo e($copy['reflections']); ?> <<</div>

                        <div class="reflection-pane-body is-active" id="reflection-pane-default">
                            <p class="reflection-pane-meta"><?php echo e($copy['panel_intro_text']); ?></p>
                        </div>

                        <?php foreach ($monthGroups as $group): ?>
                            <?php foreach ($group['entries'] as $entry): ?>
                                <?php
                                $hasReflectionThread = $entry['entry_type'] === 'fuckup' && (int) $entry['allow_reflections'] === 1;
                                if (!$hasReflectionThread) {
                                    continue;
                                }
                                $panelId = 'reflection-pane-' . (int) $entry['id'];
                                ?>
                                <div class="reflection-pane-body" id="<?php echo e($panelId); ?>">
                                    <p class="reflection-pane-meta">
                                        <?php echo e($group['label']); ?> · <?php echo e($entry['title']); ?>
                                    </p>

                                    <?php if (!empty($entry['reflections'])): ?>
                                        <div class="reflection-list">
                                            <?php foreach ($entry['reflections'] as $reflection): ?>
                                                <article class="reflection-item">
                                                    <div class="reflection-meta">
                                                        <?php
                                                        $author = ((int) $reflection['is_anonymous'] === 1)
                                                            ? $copy['anonymous_author']
                                                            : ($reflection['author_name'] ?: $copy['nameless_author']);
                                                        ?>
                                                        <strong><?php echo e($author); ?></strong>
                                                    </div>
                                                    <div class="reflection-body">
                                                        <?php echo nl2br(e($reflection['body'])); ?>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="muted-line"><?php echo e($copy['no_reflections']); ?></p>
                                    <?php endif; ?>

                                    <form method="post" action="<?php echo e(route_url('reflections.store')); ?>" class="reflection-form">
                                        <input type="hidden" name="entry_id" value="<?php echo e((string) $entry['id']); ?>">

                                        <div class="form-row">
                                            <label for="author_name_<?php echo e((string) $entry['id']); ?>"><?php echo e($copy['name']); ?></label>
                                            <input type="text" id="author_name_<?php echo e((string) $entry['id']); ?>" name="author_name">
                                        </div>

                                        <div class="form-row">
                                            <label for="author_email_<?php echo e((string) $entry['id']); ?>"><?php echo e($copy['email']); ?></label>
                                            <input type="email" id="author_email_<?php echo e((string) $entry['id']); ?>" name="author_email">
                                        </div>

                                        <div class="form-row">
                                            <label for="reflection_body_<?php echo e((string) $entry['id']); ?>"><?php echo e($copy['reflection']); ?></label>
                                            <textarea id="reflection_body_<?php echo e((string) $entry['id']); ?>" name="body" rows="5" required></textarea>
                                        </div>

                                        <div class="form-row checkbox-row">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="is_anonymous" value="1">
                                                <?php echo e($copy['anonymous']); ?>
                                            </label>
                                        </div>

                                        <div class="form-row">
                                            <button type="submit"><?php echo e($copy['send']); ?></button>
                                        </div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>

        <footer class="log-footer">
            © <?php echo e((string) $currentYear); ?> · <a href="https://www.janmotal.cz">www.janmotal.cz</a>
        </footer>
    </main>

    <script>
        (function () {
            const triggers = document.querySelectorAll('[data-reflection-target]');
            const panes = document.querySelectorAll('.reflection-pane-body');

            function openPane(id) {
                let found = false;

                panes.forEach(function (pane) {
                    const isTarget = pane.id === id;
                    pane.classList.toggle('is-active', isTarget);
                    if (isTarget) {
                        found = true;
                    }
                });

                const fallback = document.getElementById('reflection-pane-default');
                if (fallback) {
                    fallback.classList.toggle('is-active', !found);
                }
            }

            triggers.forEach(function (trigger) {
                trigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    const targetId = trigger.getAttribute('data-reflection-target');
                    if (!targetId) return;
                    openPane(targetId);
                    if (history.replaceState) {
                        history.replaceState(null, '', '#' + targetId);
                    }
                });
            });

            const hash = window.location.hash ? window.location.hash.substring(1) : '';
            if (hash) {
                openPane(hash);
            }
        })();
    </script>
</body>
</html>
