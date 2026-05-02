<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? t('page.admin_entries_title')); ?></h1>
        <p class="page-lead">
            Tady spravuješ achievements, fuckupy, regen a repair. Tohle je provozní srdceohle je provozní srdce celé appky.
        </p>
    </header>

    <div class="toolbar">
        <a href="<?php echo e(route_url('admin.entries.create')); ?>" class="button-link">
            <?php echo e(t('action.new_entry')); ?>
        </a>
    </div>

    <?php if (empty($entries)): ?>
        <article class="card">
            <p>Zatím tu není žádný entry.</p>
        </article>
    <?php else: ?>
        <article class="card">
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo e(t('label.date')); ?></th>
                            <th><?php echo e(t('label.type')); ?></th>
                            <th><?php echo e(t('label.title')); ?></th>
                            <th><?php echo e(t('label.category')); ?></th>
                            <th><?php echo e(t('label.project')); ?></th>
                            <th><?php echo e(t('label.minutes')); ?></th>
                            <th>footprint</th>
                            <th><?php echo e(t('label.visibility')); ?></th>
                            <th>akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?php echo e((string) $entry['id']); ?></td>
                                <td><?php echo e($entry['entry_date']); ?></td>
                                <td><?php echo e($entry['entry_type']); ?></td>
                                <td>
                                    <strong><?php echo e($entry['title'] ?? '(bez titulku)'); ?></strong>
                                    <div class="table-subline">
                                        <?php echo e(mb_strimwidth(strip_tags($entry['body']), 0, 110, '…', 'UTF-8')); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo e($entry['category_name']); ?>
                                    <div class="table-subline"><?php echo e($entry['category_kind']); ?></div>
                                </td>
                                <td><?php echo e($entry['project_title'] ?? '—'); ?></td>
                                <td><?php echo e((string) $entry['minutes']); ?></td>
                                <td>
                                    <?php if (($entry['emissions_status'] ?? 'not_rated') === 'not_rated'): ?>
                                        <span class="status-badge">not rated</span>
                                    <?php else: ?>
                                        <?php echo e(\App\Services\FootprintService::formatKg($entry['emissions_total_kg'] ?? 0)); ?>
                                        <?php if (($entry['emissions_status'] ?? '') === 'partial'): ?>
                                            <div class="table-subline">partial</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($entry['visibility']); ?></td>
                                <td>
                                    <a href="<?php echo e(route_url('admin.entries.edit', ['id' => $entry['id']])); ?>">
                                        <?php echo e(t('action.edit')); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    <?php endif; ?>
</section>
