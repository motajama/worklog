<?php
$monthGroups = $month_groups ?? [];
$workMix = $work_mix ?? ['days' => 180, 'total_hours_label' => '0 h', 'rows' => []];
$balance = $balance ?? null;
$scientific = $scientific ?? [
    'has_data' => false,
    'questionnaire_entry_count' => 0,
    'copsoq_workload_mean_label' => '—',
    'nfr_mean_label' => '—',
    'recovery_experience_mean_label' => '—',
    'derived_balance_percent_label' => '—',
    'derived_balance_bar' => '····················',
    'derived_status' => 'no data',
];
$scientificTrend12 = $scientific_trend_12 ?? [
    'chart_rows' => [],
    'labels_row' => '',
];

$trendChartRowsDisplay = [];
$trendLabelsDisplay = '';

if (!empty($scientificTrend12['chart_rows'])) {
    foreach ($scientificTrend12['chart_rows'] as $row) {
        $cells = preg_split('/\s+/', trim((string) $row));
        $displayCells = [];

        foreach ($cells as $cell) {
            if ($cell === '█') {
                $displayCells[] = ' █ ';
            } else {
                $displayCells[] = ' · ';
            }
        }

        $trendChartRowsDisplay[] = implode('', $displayCells);
    }
}

if (!empty($scientificTrend12['labels_row'])) {
    $labels = preg_split('/\s+/', trim((string) $scientificTrend12['labels_row']));
    $labelCells = [];

    foreach ($labels as $label) {
        $labelCells[] = str_pad($label, 3, ' ', STR_PAD_BOTH);
    }

    $trendLabelsDisplay = implode('', $labelCells);
}

$balanceDays = $balance_days ?? 30;
$workMixDays = $work_mix_days ?? 180;
$publicLogConfig = config('app.public_log', []);
$publicLogDisplay = $publicLogConfig['display'] ?? [];

$isEn = current_locale() === 'en';
$localeKey = $isEn ? 'en' : 'cs';
$currentYear = date('Y');
$currentSkin = current_skin();

$balancePeriodLabel = null;
if ($balance) {
    $balancePeriodLabel = $isEn
        ? ($balance['period_label_en'] ?? null)
        : ($balance['period_label_cs'] ?? null);
}

$skinOptions = [
    'zine-xerox' => 'xerox',
    'amber-terminal' => 'amber',
    'win3-gray' => 'win3',
    'mac-1984-mono' => 'mac',
    'atari' => 'atari',
    'msdos' => 'msdos',
];

$copy = $isEn
    ? [
        'title' => 'log',
        'description' => 'A public work log: what moved, what failed, what needed repair, and how recovery relates to workload.',
        'switch_cs' => 'CZ',
        'switch_en' => 'EN',
        'skins_label' => 'skin',
        'balance_heading_closed' => 'balance / closed month: %s',
        'balance_heading_fallback' => 'balance / last %d days',
        'scientific_heading_closed' => 'quick balance check / closed month: %s',
        'scientific_heading_fallback' => 'quick balance check / last %d days',
        'trend_heading' => 'quick balance / last 12 closed months',
        'work_heading' => 'work barometer / last %d days',
        'work_total' => 'total work time across all entries: %s',
        'no_work_data' => 'no work data yet.',
        'no_scientific_data' => 'no balance check data yet.',
        'no_trend_data' => 'no trend data yet.',
        'entries_label' => 'entries in range',
        'questionnaire_entries_label' => 'check-ins',
        'period_label' => 'period',
        'work_total_label' => 'total work time',
        'balance_ratio_label' => 'recovery ratio',
        'scientific_balance_label' => 'balance score',
        'copsoq_label' => 'work pressure',
        'nfr_label' => 'fatigue',
        'recovery_experience_label' => 'recovery quality',
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
        'panel_intro_text' => 'Click “↗ Reflections” next to an entry and the thread will open here. The pane stays fixed while the page moves underneath it.',
        'fail_badge' => 'FAIL',
        'footer_note' => 'CC-BY-ND-NC %s',
    ]
    : [
        'title' => 'log',
        'description' => 'Veřejný pracovní log: co se pohnulo, co se nepovedlo, co potřebovalo opravu a jak obnova odpovídá workloadu.',
        'switch_cs' => 'CZ',
        'switch_en' => 'EN',
        'skins_label' => 'skin',
        'balance_heading_closed' => 'balance / uzavřený měsíc: %s',
        'balance_heading_fallback' => 'balance / last %d days',
        'scientific_heading_closed' => 'rychlý balance check / uzavřený měsíc: %s',
        'scientific_heading_fallback' => 'rychlý balance check / posledních %d dní',
        'trend_heading' => 'rychlá balance / posledních 12 uzavřených měsíců',
        'work_heading' => 'work barometer / last %d days',
        'work_total' => 'celkový pracovní čas napříč všemi entries: %s',
        'no_work_data' => 'zatím žádná work data.',
        'no_scientific_data' => 'zatím žádná data z balance checku.',
        'no_trend_data' => 'zatím žádná trendová data.',
        'entries_label' => 'entries v období',
        'questionnaire_entries_label' => 'check-iny',
        'period_label' => 'období',
        'work_total_label' => 'celkový pracovní čas',
        'balance_ratio_label' => 'recovery ratio',
        'scientific_balance_label' => 'balance skóre',
        'copsoq_label' => 'pracovní tlak',
        'nfr_label' => 'únava',
        'recovery_experience_label' => 'kvalita obnovy',
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
        'panel_intro_text' => 'Klikni na „↗ Reflexe“ u konkrétního entry a vlákno se otevře tady. Panel zůstává fixovaný a stránka pod ním plyne.',
        'fail_badge' => 'FAKAP',
        'footer_note' => 'CC-BY-ND-NC %s',
    ];

$copy = array_replace($copy, $publicLogConfig['copy'][$localeKey] ?? []);

$showBalanceEntryCount = (bool) ($publicLogDisplay['show_balance_entry_count'] ?? true);
$showWorkMixTotal = (bool) ($publicLogDisplay['show_work_mix_total'] ?? true);
$showWorkMixHours = (bool) ($publicLogDisplay['show_work_mix_hours'] ?? true);
$mobileScrollReflections = (bool) ($publicLogDisplay['mobile_scroll_reflections'] ?? false);
$footerHtml = $publicLogDisplay['footer_html'] ?? null;
?>
<!DOCTYPE html>
<html lang="<?php echo e(current_locale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($copy['title']); ?> — <?php echo e(config('app.app_name')); ?></title>
    <meta name="description" content="<?php echo e($copy['description']); ?>">

    <link rel="stylesheet" href="<?php echo e(asset('assets/css/skins/' . $currentSkin . '.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/log.css')); ?>">
</head>
<body>
    <main class="log-page">
        <header class="log-header">
            <h1><?php echo e($copy['title']); ?></h1>
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
                            <h2>
                                <?php
                                echo e(
                                    $balancePeriodLabel
                                        ? sprintf($copy['balance_heading_closed'], $balancePeriodLabel)
                                        : sprintf($copy['balance_heading_fallback'], (int) $balanceDays)
                                );
                                ?>
                            </h2>

                            <table class="stats-table">
                                <colgroup>
                                    <col>
                                    <col>
                                </colgroup>
                                <tbody>
                                    <?php if ($balancePeriodLabel): ?>
                                        <tr>
                                            <td><?php echo e($copy['period_label']); ?></td>
                                            <td><?php echo e($balancePeriodLabel); ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if ($showBalanceEntryCount): ?>
                                        <tr>
                                            <td><?php echo e($copy['entries_label']); ?></td>
                                            <td><?php echo e((string) $balance['entry_count']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><?php echo e($copy['work_total_label']); ?></td>
                                        <td><?php echo e($balance['work_hours_label']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo e($copy['balance_ratio_label']); ?></td>
                                        <td><?php echo e($balance['display_ratio_label'] ?? $balance['balance_ratio_label']); ?></td>
                                    </tr>
                                    <tr class="therm-row">
                                        <td colspan="2">
                                            <span class="therm-bar"><?php echo e($balance['display_bar'] ?? $balance['balance_bar']); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><?php echo e($copy['status_label']); ?></td>
                                        <td><?php echo e($balance['display_status'] ?? $balance['balance_status']); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>
                    <?php endif; ?>

                    <section class="log-section">
                        <h2>
                            <?php
                            echo e(
                                $balancePeriodLabel
                                    ? sprintf($copy['scientific_heading_closed'], $balancePeriodLabel)
                                    : sprintf($copy['scientific_heading_fallback'], (int) $balanceDays)
                            );
                            ?>
                        </h2>

                        <?php if (empty($scientific['has_data'])): ?>
                            <p><?php echo e($copy['no_scientific_data']); ?></p>
                        <?php else: ?>
                            <table class="stats-table">
                                <colgroup>
                                    <col>
                                    <col>
                                </colgroup>
                                <tbody>
                                    <tr>
                                        <td><?php echo e($copy['questionnaire_entries_label']); ?></td>
                                        <td><?php echo e((string) $scientific['questionnaire_entry_count']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo e($copy['copsoq_label']); ?></td>
                                        <td><?php echo e($scientific['copsoq_workload_mean_label']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo e($copy['nfr_label']); ?></td>
                                        <td><?php echo e($scientific['nfr_mean_label']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo e($copy['recovery_experience_label']); ?></td>
                                        <td><?php echo e($scientific['recovery_experience_mean_label']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo e($copy['scientific_balance_label']); ?></td>
                                        <td><?php echo e($scientific['derived_balance_percent_label']); ?></td>
                                    </tr>
                                    <tr class="therm-row">
                                        <td colspan="2">
                                            <span class="therm-bar"><?php echo e($scientific['derived_balance_bar']); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><?php echo e($copy['status_label']); ?></td>
                                        <td><?php echo e($scientific['derived_status']); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </section>

                    <section class="log-section">
                        <h2><?php echo e($copy['trend_heading']); ?></h2>

                        <?php if (empty($scientificTrend12['chart_rows'])): ?>
                            <p><?php echo e($copy['no_trend_data']); ?></p>
                        <?php else: ?>
                            <pre class="ascii-block ascii-chart"><?php
foreach ($trendChartRowsDisplay as $row) {
    echo e($row) . "\n";
}
echo e($trendLabelsDisplay);
?></pre>
<?php endif; ?>
                    </section>

                    <section class="log-section">
                        <h2><?php echo e(sprintf($copy['work_heading'], (int) $workMixDays)); ?></h2>

                        <?php if (empty($workMix['rows'])): ?>
                            <p><?php echo e($copy['no_work_data']); ?></p>
                        <?php else: ?>
                            <table class="work-table">
                                <colgroup>
                                    <col>
                                    <col>
                                </colgroup>
                                <tbody>
                                    <?php if ($showWorkMixTotal): ?>
                                        <tr>
                                            <td><?php echo e($copy['work_total_label']); ?></td>
                                            <td><?php echo e($workMix['total_hours_label']); ?></td>
                                        </tr>
                                        <tr class="therm-row">
                                            <td colspan="2"></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($workMix['rows'] as $row): ?>
                                        <tr>
                                            <td><?php echo e($row['label']); ?></td>
                                            <td>
                                                <?php
                                                echo e($showWorkMixHours
                                                    ? $row['percent'] . '% · ' . $row['hours_label']
                                                    : $row['percent'] . '% ');
                                                ?>
                                            </td>
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
                                    $hasReflectionThread = (int) $entry['allow_reflections'] === 1;
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

            <aside class="log-right" id="reflections-box">
                <div class="log-right-inner">
                    <div class="reflection-pane">
                        <div class="pane-titlebar">>> <?php echo e($copy['reflections']); ?> <<</div>

                        <div class="reflection-pane-body is-active" id="reflection-pane-default">
                            <p class="reflection-pane-meta"><?php echo e($copy['panel_intro_text']); ?></p>
                        </div>

                        <?php foreach ($monthGroups as $group): ?>
                            <?php foreach ($group['entries'] as $entry): ?>
                                <?php
                                $hasReflectionThread = (int) $entry['allow_reflections'] === 1;
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
                                        <input type="hidden" name="return_url" value="<?php echo e(url('log.php?lang=' . rawurlencode(current_locale()) . '&skin=' . rawurlencode($currentSkin) . '#entry-' . (int) $entry['id'])); ?>">

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
            <div class="log-footer-inner">
                <div class="log-footer-line">
                    <?php echo e(sprintf($copy['footer_note'], (string) $currentYear)); ?>
                    <?php if (is_string($footerHtml) && $footerHtml !== ''): ?>
                        <?php echo $footerHtml; ?>
                    <?php endif; ?>
                </div>

                <nav class="skin-switch" aria-label="Skin switch">
                    <span class="skin-switch-label"><?php echo e($copy['skins_label']); ?>:</span>
                    <?php foreach ($skinOptions as $skinKey => $skinLabel): ?>
                        <a
                            href="<?php echo e('log.php?lang=' . rawurlencode(current_locale()) . '&skin=' . rawurlencode($skinKey)); ?>"
                            class="<?php echo $currentSkin === $skinKey ? 'is-active' : ''; ?>"
                        >
                            <?php echo e($skinLabel); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </footer>
    </main>

    <script>
        (function () {
            const triggers = document.querySelectorAll('[data-reflection-target]');
            const panes = document.querySelectorAll('.reflection-pane-body');
            const mobileScrollReflections = <?php echo $mobileScrollReflections ? 'true' : 'false'; ?>;
            const mobileBreakpoint = window.matchMedia('(max-width: 1180px)');
            const reflectionsBox = document.getElementById('reflections-box');

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

                if (mobileScrollReflections && found && mobileBreakpoint.matches && reflectionsBox) {
                    window.setTimeout(function () {
                        reflectionsBox.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 20);
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
