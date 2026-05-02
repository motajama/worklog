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
$formatKg = static function (float|string|null $value): string {
    return \App\Services\FootprintService::formatKg($value);
};
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
                                <?php echo e($formatKg($entry['emissions_total_kg'])); ?>
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
