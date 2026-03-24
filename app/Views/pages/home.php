<?php
$monthGroups = $month_groups ?? [];
$reflectionsByEntry = $reflections_by_entry ?? [];
$summaryData = $summary ?? [
    'days' => 180,
    'entry_count' => 0,
    'work_total_minutes' => 0,
    'recovery_total_minutes' => 0,
    'work_mix' => [],
    'recovery_mix' => [],
    'type_mix' => [],
];
$balance30 = $balance_30 ?? null;
?>

<section class="page-section public-log-page">
    <header class="page-header public-log-header">
        <h1><?php echo e(t('section.public_log')); ?></h1>
        <p class="page-lead">
            Veřejná etika práce. Krátké záznamy o tom, co se povedlo, co drhlo, co drží práci pohromadě a kde je potřeba obnova.
        </p>
    </header>

    <?php if ($balance30): ?>
        <article class="card ascii-card">
            <h2>balance / last 30 days</h2>
            <pre class="ascii-block"><?php
echo e('public entries         ' . $balance30['entry_count']) . "\n";
echo e('work total             ' . $balance30['work_hours_label']) . "\n";
echo e('sleep baseline         ' . $balance30['sleep_hours_label']) . "\n";
echo e('active regen           ' . $balance30['active_recovery_hours_label']) . "\n";
echo e('required regen         ' . $balance30['required_active_recovery_hours_label']) . "\n";
echo e('regen delta            ' . $balance30['recovery_delta_hours_label']) . "\n";
echo e('balance ratio          ' . $balance30['balance_ratio_label'] . '  ' . $balance30['balance_bar']) . "\n";
echo e('active regen ratio     ' . $balance30['active_recovery_ratio_label'] . '  ' . $balance30['active_recovery_bar']) . "\n";
echo e('status                 ' . $balance30['balance_status']) . "\n";
?></pre>
        </article>
    <?php endif; ?>

    <section class="ascii-summary">
        <article class="card ascii-card">
            <h2>work mix / last <?php echo e((string) $summaryData['days']); ?> days</h2>

            <div class="ascii-meta-line">
                public entries: <?php echo e((string) $summaryData['entry_count']); ?> · work total: <?php echo e((string) ($summaryData['work_total_minutes'] > 0 ? number_format($summaryData['work_total_minutes'] / 60, 1, '.', '') : '0')); ?> h
            </div>

            <?php if (empty($summaryData['work_mix'])): ?>
                <div class="muted-line">Zatím žádná veřejná work data.</div>
            <?php else: ?>
                <pre class="ascii-block"><?php
foreach ($summaryData['work_mix'] as $row) {
    $label = str_pad(mb_strimwidth($row['label'], 0, 22, '…', 'UTF-8'), 22, ' ');
    $percent = str_pad((string) $row['percent'], 3, ' ', STR_PAD_LEFT);
    $hours = str_pad($row['hours_label'], 6, ' ', STR_PAD_LEFT);
    echo e($label . '  ' . $percent . '%  ' . $hours . '  ' . $row['bar']) . "\n";
}
?></pre>
            <?php endif; ?>
        </article>

        <article class="card ascii-card">
            <h2>regen mix / last <?php echo e((string) $summaryData['days']); ?> days</h2>

            <div class="ascii-meta-line">
                active regen total: <?php echo e((string) ($summaryData['recovery_total_minutes'] > 0 ? number_format($summaryData['recovery_total_minutes'] / 60, 1, '.', '') : '0')); ?> h
            </div>

            <?php if (empty($summaryData['recovery_mix'])): ?>
                <div class="muted-line">Zatím žádná veřejná regen data.</div>
            <?php else: ?>
                <pre class="ascii-block"><?php
foreach ($summaryData['recovery_mix'] as $row) {
    $label = str_pad(mb_strimwidth($row['label'], 0, 22, '…', 'UTF-8'), 22, ' ');
    $percent = str_pad((string) $row['percent'], 3, ' ', STR_PAD_LEFT);
    $hours = str_pad($row['hours_label'], 6, ' ', STR_PAD_LEFT);
    echo e($label . '  ' . $percent . '%  ' . $hours . '  ' . $row['bar']) . "\n";
}
?></pre>
            <?php endif; ?>
        </article>

        <article class="card ascii-card">
            <h2>entry type mix / last <?php echo e((string) $summaryData['days']); ?> days</h2>

            <?php if (empty($summaryData['type_mix'])): ?>
                <div class="muted-line">Zatím žádná veřejná data.</div>
            <?php else: ?>
                <pre class="ascii-block"><?php
foreach ($summaryData['type_mix'] as $row) {
    $label = str_pad(mb_strimwidth($row['label'], 0, 22, '…', 'UTF-8'), 22, ' ');
    $percent = str_pad((string) $row['percent'], 3, ' ', STR_PAD_LEFT);
    $hours = str_pad($row['hours_label'], 6, ' ', STR_PAD_LEFT);
    echo e($label . '  ' . $percent . '%  ' . $hours . '  ' . $row['bar']) . "\n";
}
?></pre>
            <?php endif; ?>
        </article>
    </section>

    <?php if (empty($monthGroups)): ?>
        <article class="card">
            <p>Zatím tu nejsou žádné veřejné záznamy.</p>
        </article>
    <?php else: ?>
        <?php foreach ($monthGroups as $group): ?>
            <section class="month-group">
                <header class="month-header">
                    <h2><?php echo e($group['label']); ?></h2>
                </header>

                <div class="log-list">
                    <?php foreach ($group['entries'] as $entry): ?>
                        <?php
                        $entryId = (int) $entry['id'];
                        $entryType = $entry['entry_type'];
                        $entryText = trim((string) ($entry['public_text'] ?? '')) !== ''
                            ? $entry['public_text']
                            : $entry['body'];

                        $projectLabel = null;
                        if (!empty($entry['project_title'])) {
                            if (($entry['project_visibility'] ?? null) === 'public') {
                                $projectLabel = $entry['project_title'];
                            } elseif (($entry['project_visibility'] ?? null) === 'masked') {
                                $projectLabel = $entry['project_public_label'] ?: 'internal project';
                            }
                        }

                        $approvedReflections = $reflectionsByEntry[$entryId] ?? [];
                        ?>
                        <article class="log-entry log-entry-<?php echo e($entryType); ?>" id="entry-<?php echo e((string) $entryId); ?>">
                            <div class="log-entry-line">
                                <span class="log-entry-date"><?php echo e(date('j. n.', strtotime((string) $entry['entry_date']))); ?></span>

                                <span class="log-entry-type"><?php echo e($entryType); ?></span>

                                <?php if (!empty($entry['title'])): ?>
                                    <span class="log-entry-title"><?php echo e($entry['title']); ?></span>
                                <?php endif; ?>

                                <?php if ((int) $entry['minutes'] > 0): ?>
                                    <span class="log-entry-meta"><?php echo e((string) $entry['minutes']); ?> min</span>
                                <?php endif; ?>

                                <span class="log-entry-meta"><?php echo e($entry['category_name']); ?></span>

                                <?php if ($projectLabel): ?>
                                    <span class="log-entry-meta"><?php echo e($projectLabel); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="log-entry-body">
                                <?php echo nl2br(e($entryText)); ?>
                            </div>

                            <?php if ($entryType === 'fuckup'): ?>
                                <?php
                                $hasMeta = !empty($entry['what_happened']) || !empty($entry['why_it_matters']) || !empty($entry['my_take']) || !empty($entry['next_time']);
                                ?>
                                <?php if ($hasMeta): ?>
                                    <details class="log-entry-details">
                                        <summary>context</summary>

                                        <div class="fuckup-context">
                                            <?php if (!empty($entry['what_happened'])): ?>
                                                <div class="context-block">
                                                    <strong><?php echo e(t('label.what_happened')); ?>:</strong>
                                                    <div><?php echo nl2br(e($entry['what_happened'])); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($entry['why_it_matters'])): ?>
                                                <div class="context-block">
                                                    <strong><?php echo e(t('label.why_it_matters')); ?>:</strong>
                                                    <div><?php echo nl2br(e($entry['why_it_matters'])); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($entry['my_take'])): ?>
                                                <div class="context-block">
                                                    <strong><?php echo e(t('label.my_take')); ?>:</strong>
                                                    <div><?php echo nl2br(e($entry['my_take'])); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($entry['next_time'])): ?>
                                                <div class="context-block">
                                                    <strong><?php echo e(t('label.next_time')); ?>:</strong>
                                                    <div><?php echo nl2br(e($entry['next_time'])); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                <?php endif; ?>

                                <?php if ((int) $entry['allow_reflections'] === 1): ?>
                                    <details class="log-entry-details reflections-details">
                                        <summary>
                                            reflexe
                                            <?php if (!empty($approvedReflections)): ?>
                                                (<?php echo e((string) count($approvedReflections)); ?>)
                                            <?php endif; ?>
                                        </summary>

                                        <?php if (!empty($approvedReflections)): ?>
                                            <div class="reflection-list">
                                                <?php foreach ($approvedReflections as $reflection): ?>
                                                    <article class="reflection-item">
                                                        <div class="reflection-meta">
                                                            <?php
                                                            $author = ((int) $reflection['is_anonymous'] === 1)
                                                                ? 'anonym'
                                                                : ($reflection['author_name'] ?: 'bez jména');
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
                                            <p class="muted-line">Zatím žádná schválená reflexe.</p>
                                        <?php endif; ?>

                                        <form method="post" action="<?php echo e(route_url('reflections.store')); ?>" class="reflection-form">
                                            <input type="hidden" name="entry_id" value="<?php echo e((string) $entryId); ?>">

                                            <div class="form-row">
                                                <label for="author_name_<?php echo e((string) $entryId); ?>"><?php echo e(t('label.author_name')); ?></label>
                                                <input type="text" id="author_name_<?php echo e((string) $entryId); ?>" name="author_name">
                                            </div>

                                            <div class="form-row">
                                                <label for="author_email_<?php echo e((string) $entryId); ?>"><?php echo e(t('label.author_email')); ?></label>
                                                <input type="email" id="author_email_<?php echo e((string) $entryId); ?>" name="author_email">
                                            </div>

                                            <div class="form-row">
                                                <label for="reflection_body_<?php echo e((string) $entryId); ?>"><?php echo e(t('label.reflection')); ?></label>
                                                <textarea id="reflection_body_<?php echo e((string) $entryId); ?>" name="body" rows="4" required></textarea>
                                            </div>

                                            <div class="form-row checkbox-row">
                                                <label class="checkbox-label">
                                                    <input type="checkbox" name="is_anonymous" value="1">
                                                    <?php echo e(t('label.is_anonymous')); ?>
                                                </label>
                                            </div>

                                            <div class="form-actions">
                                                <button type="submit"><?php echo e(t('action.send_reflection')); ?></button>
                                            </div>
                                        </form>
                                    </details>
                                <?php endif; ?>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
