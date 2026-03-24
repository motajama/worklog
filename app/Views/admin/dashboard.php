<?php
$stats = $stats ?? [
    'entries' => 0,
    'projects' => 0,
    'pending_reflections' => 0,
];

$latestEntries = $latest_entries ?? [];
$pendingReflections = $pending_reflections ?? [];
?>

<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? t('page.admin_dashboard_title')); ?></h1>
        <p class="page-lead">
            Přehled toho, co se právě děje: entries, projekty a reflexe čekající na moderaci.
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
