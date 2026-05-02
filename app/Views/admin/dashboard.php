<?php
$stats = $stats ?? [
    'entries' => 0,
    'projects' => 0,
    'pending_reflections' => 0,
];

$latestEntries = $latest_entries ?? [];
$pendingReflections = $pending_reflections ?? [];
$balance7 = $balance_7 ?? null;
$balance30 = $balance_30 ?? null;
$footprint30 = $footprint_30 ?? ['emissions_total_kg' => 0, 'not_rated_count' => 0, 'entry_count' => 0];
$recurringFootprint30 = $recurring_footprint_30 ?? ['emissions_total_kg' => 0, 'instance_count' => 0];
$carbonPerHourMonthly = $carbon_per_hour_monthly ?? [];
$latestCarbonPerHourMonth = $carbonPerHourMonthly !== []
    ? $carbonPerHourMonthly[count($carbonPerHourMonthly) - 1]
    : null;
$latestCarbonPerHourClass = $latestCarbonPerHourMonth
    ? \App\Services\FootprintService::classifyCarbonPerHour($latestCarbonPerHourMonth['median_kg_per_hour'] ?? null)
    : null;
$carbonPerHourJson = json_encode($carbonPerHourMonthly, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$formatKg = static function (float|string|null $value): string {
    return \App\Services\FootprintService::formatKg($value);
};
$formatKgPerHour = static function (float|string|null $value): string {
    return number_format((float) ($value ?? 0), 3, '.', '') . ' kg CO2e/hour';
};
$isEn = current_locale() === 'en';
$carbonCopy = $isEn
    ? [
        'heading' => 'Median carbon footprint per work hour',
        'help' => 'Monthly median of kg CO2e per hour, calculated only from events with both carbon footprint and valid duration.',
        'methodology' => 'Each event keeps its saved carbon footprint total. For this graph, the app divides that total by the logged duration in hours, skips events without footprint or valid minutes, then takes the monthly median. The colored guide lines mark the interpretation bands.',
        'empty' => 'No carbon-per-hour data available yet.',
        'events' => 'events',
    ]
    : [
        'heading' => 'Medián uhlíkové stopy za pracovní hodinu',
        'help' => 'Měsíční medián kg CO2e za hodinu, počítaný jen z eventů s uhlíkovou stopou a platnou délkou.',
        'methodology' => 'Každý event má uložený celkový odhad uhlíkové stopy. Graf ho vydělí zapsanou délkou v hodinách, přeskočí eventy bez footprintu nebo platných minut a z každého měsíce vezme medián. Barevné linky ukazují interpretační pásma.',
        'empty' => 'Zatím nejsou dostupná data CO2e za hodinu.',
        'events' => 'eventů',
    ];
?>

<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? t('page.admin_dashboard_title')); ?></h1>
        <p class="page-lead">
            Přehled toho, co se právě děje: entries, projekty, pending reflexe a balance.
        </p>
    </header>

    <div class="grid grid-3">
        <article class="card stat-card">
            <h2>entries</h2>
            <div class="stat-number"><?php echo e((string) $stats['entries']); ?></div>
        </article>

        <article class="card stat-card">
            <h2>projects</h2>
            <div class="stat-number"><?php echo e((string) $stats['projects']); ?></div>
        </article>

        <article class="card stat-card">
            <h2>pending reflections</h2>
            <div class="stat-number"><?php echo e((string) $stats['pending_reflections']); ?></div>
        </article>
    </div>

    <div class="grid grid-3">
        <article class="card stat-card">
            <h2>event footprint / 30 days</h2>
            <div class="stat-number"><?php echo e($formatKg($footprint30['emissions_total_kg'])); ?></div>
            <div class="table-subline">
                <?php echo e((string) $footprint30['not_rated_count']); ?> not rated / <?php echo e((string) $footprint30['entry_count']); ?> entries
            </div>
        </article>

        <article class="card stat-card">
            <h2>recurring footprint / 30 days</h2>
            <div class="stat-number"><?php echo e($formatKg($recurringFootprint30['emissions_total_kg'])); ?></div>
            <div class="table-subline">
                <?php echo e((string) $recurringFootprint30['instance_count']); ?> generated instances
            </div>
        </article>

        <article class="card stat-card">
            <h2>combined footprint / 30 days</h2>
            <div class="stat-number">
                <?php echo e($formatKg((float) $footprint30['emissions_total_kg'] + (float) $recurringFootprint30['emissions_total_kg'])); ?>
            </div>
            <div class="table-subline">event + recurring, kgCO2e</div>
        </article>
    </div>

    <article class="card carbon-chart-card">
        <h2>
            <?php echo e($carbonCopy['heading']); ?>
            <span class="heading-info" tabindex="0" aria-label="<?php echo e($carbonCopy['methodology']); ?>">
                [i]
                <span class="heading-info-text" role="tooltip"><?php echo e($carbonCopy['methodology']); ?></span>
            </span>
        </h2>
        <p class="help-line">
            <?php echo e($carbonCopy['help']); ?>
        </p>

        <?php if (empty($carbonPerHourMonthly)): ?>
            <p><?php echo e($carbonCopy['empty']); ?></p>
        <?php else: ?>
            <div class="carbon-chart" data-carbon-chart data-points="<?php echo e((string) $carbonPerHourJson); ?>"></div>

            <?php if ($latestCarbonPerHourMonth && $latestCarbonPerHourClass): ?>
                <div class="carbon-chart-summary carbon-interval-<?php echo e((string) $latestCarbonPerHourClass['level']); ?>">
                    <strong><?php echo e((string) $latestCarbonPerHourMonth['month']); ?></strong>
                    · <?php echo e($formatKgPerHour($latestCarbonPerHourMonth['median_kg_per_hour'])); ?>
                    · <?php echo e($latestCarbonPerHourClass['label']); ?>
                    · <?php echo e((string) $latestCarbonPerHourMonth['event_count']); ?> <?php echo e($carbonCopy['events']); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </article>

    <div class="grid grid-2">
        <?php foreach ([7 => $balance7, 30 => $balance30] as $days => $balance): ?>
            <?php if ($balance): ?>
                <article class="card ascii-card">
                    <h2>balance / last <?php echo e((string) $days); ?> days</h2>
                    <pre class="ascii-block"><?php
echo e('entries                ' . $balance['entry_count']) . "\n";
echo e('work total             ' . $balance['work_hours_label']) . "\n";
echo e('sleep baseline         ' . $balance['sleep_hours_label']) . "\n";
echo e('active regen           ' . $balance['active_recovery_hours_label']) . "\n";
echo e('required regen         ' . $balance['required_active_recovery_hours_label']) . "\n";
echo e('regen delta            ' . $balance['recovery_delta_hours_label']) . "\n";
echo e('balance ratio          ' . $balance['balance_ratio_label'] . '  ' . $balance['balance_bar']) . "\n";
echo e('active regen ratio     ' . $balance['active_recovery_ratio_label'] . '  ' . $balance['active_recovery_bar']) . "\n";
echo e('status                 ' . $balance['balance_status']) . "\n";
?></pre>
                </article>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-2">
        <article class="card">
            <h2>latest entries</h2>

            <?php if (empty($latestEntries)): ?>
                <p>Zatím žádné entries.</p>
            <?php else: ?>
                <ul class="mono-list">
                    <?php foreach ($latestEntries as $entry): ?>
                        <li>
                            <strong><?php echo e($entry['entry_date']); ?></strong>
                            · <?php echo e($entry['entry_type']); ?>
                            · <?php echo e($entry['title'] ?: '(bez titulku)'); ?>
                            · <?php echo e($entry['category_name']); ?>
                            <?php if (!empty($entry['project_title'])): ?>
                                · <?php echo e($entry['project_title']); ?>
                            <?php endif; ?>
                            · footprint:
                            <?php if (($entry['emissions_status'] ?? 'not_rated') === 'not_rated'): ?>
                                <span class="status-badge">not rated</span>
                            <?php else: ?>
                                <?php
                                $entryCarbonPerHour = \App\Services\FootprintService::calculateEventCarbonPerHour($entry);
                                $entryCarbonClass = \App\Services\FootprintService::classifyCarbonPerHour($entryCarbonPerHour);
                                ?>
                                <span class="<?php echo $entryCarbonClass ? 'carbon-interval-' . e((string) $entryCarbonClass['level']) : ''; ?>">
                                    <?php echo e($formatKg($entry['emissions_total_kg'])); ?>
                                    <?php if ($entryCarbonPerHour !== null): ?>
                                        / <?php echo e($formatKgPerHour($entryCarbonPerHour)); ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                            · <a href="<?php echo e(route_url('admin.entries.edit', ['id' => $entry['id']])); ?>">edit</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>

        <article class="card">
            <h2>pending reflections</h2>

            <?php if (empty($pendingReflections)): ?>
                <p>Žádné reflexe nečekají.</p>
            <?php else: ?>
                <ul class="mono-list">
                    <?php foreach ($pendingReflections as $reflection): ?>
                        <li>
                            <strong><?php echo e($reflection['entry_date']); ?></strong>
                            · <?php echo e($reflection['entry_title'] ?: '(bez titulku)'); ?>
                            · <?php echo (int) $reflection['is_anonymous'] === 1 ? 'anonym' : e($reflection['author_name'] ?: 'bez jména'); ?>
                            · <a href="<?php echo e(route_url('admin.reflections.index')); ?>">open queue</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </div>
</section>

<script>
(function () {
    function renderCarbonChart(container) {
        let points = [];
        try {
            points = JSON.parse(container.getAttribute('data-points') || '[]');
        } catch (error) {
            points = [];
        }

        if (!points.length) {
            return;
        }

        const width = 640;
        const height = 220;
        const padLeft = 54;
        const padRight = 18;
        const padTop = 20;
        const padBottom = 42;
        const chartWidth = width - padLeft - padRight;
        const chartHeight = height - padTop - padBottom;
        const maxValue = Math.max.apply(null, points.map(function (point) {
            return Number(point.median_kg_per_hour) || 0;
        }));
        const yMax = Math.max(maxValue > 0 ? maxValue * 1.15 : 0, 0.30);

        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Monthly median carbon footprint per work hour');

        function append(name, attrs, text) {
            const node = document.createElementNS('http://www.w3.org/2000/svg', name);
            Object.keys(attrs).forEach(function (key) {
                node.setAttribute(key, attrs[key]);
            });
            if (text !== undefined) {
                node.textContent = text;
            }
            svg.appendChild(node);
            return node;
        }

        function yForValue(value) {
            return padTop + chartHeight - (value / yMax * chartHeight);
        }

        [
            {value: 0.05, level: 'excellent'},
            {value: 0.10, level: 'good'},
            {value: 0.15, level: 'watch'},
            {value: 0.30, level: 'high'}
        ].forEach(function (threshold) {
            if (threshold.value > yMax) {
                return;
            }

            const y = yForValue(threshold.value);
            append('line', {
                x1: padLeft,
                y1: y,
                x2: width - padRight,
                y2: y,
                class: 'carbon-threshold-line carbon-interval-' + threshold.level
            });
            append('text', {
                x: width - padRight - 2,
                y: y - 4,
                'text-anchor': 'end',
                class: 'carbon-threshold-label carbon-interval-' + threshold.level
            }, threshold.value.toFixed(2));
        });

        append('line', {x1: padLeft, y1: padTop, x2: padLeft, y2: height - padBottom, class: 'carbon-chart-axis'});
        append('line', {x1: padLeft, y1: height - padBottom, x2: width - padRight, y2: height - padBottom, class: 'carbon-chart-axis'});

        const coords = points.map(function (point, index) {
            const x = points.length === 1
                ? padLeft + chartWidth / 2
                : padLeft + (chartWidth * index / (points.length - 1));
            const value = Number(point.median_kg_per_hour) || 0;
            const y = yForValue(value);
            return {x: x, y: y, value: value, month: point.month, count: point.event_count};
        });

        append('polyline', {
            points: coords.map(function (coord) {
                return coord.x.toFixed(2) + ',' + coord.y.toFixed(2);
            }).join(' '),
            class: 'carbon-chart-line'
        });

        coords.forEach(function (coord, index) {
            append('circle', {cx: coord.x, cy: coord.y, r: 4, class: 'carbon-chart-point'});
            append('text', {x: coord.x, y: height - 18, 'text-anchor': 'middle', class: 'carbon-chart-label'}, coord.month.slice(2));
            if (index === coords.length - 1) {
                append('text', {x: coord.x, y: coord.y - 9, 'text-anchor': 'middle', class: 'carbon-chart-value'}, coord.value.toFixed(3));
            }
        });

        append('text', {x: 8, y: padTop + 4, class: 'carbon-chart-label'}, yMax.toFixed(3));
        append('text', {x: 8, y: height - padBottom, class: 'carbon-chart-label'}, '0');

        container.textContent = '';
        container.appendChild(svg);
    }

    document.querySelectorAll('[data-carbon-chart]').forEach(renderCarbonChart);
})();
</script>
