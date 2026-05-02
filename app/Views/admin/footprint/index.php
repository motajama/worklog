<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? t('page.admin_footprint_title')); ?></h1>
        <p class="page-lead">
            Katalog odhadů pro kgCO2e. Faktory jsou editovatelné, ale entries si ukládají vlastní snapshot.
        </p>
    </header>

    <div class="toolbar">
        <a href="<?php echo e(route_url('admin.footprint.create')); ?>" class="button-link">
            nový footprint faktor
        </a>
    </div>

    <?php if (empty($factors)): ?>
        <article class="card">
            <p>Zatím tu není žádný footprint faktor.</p>
        </article>
    <?php else: ?>
        <article class="card">
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>label</th>
                            <th>category</th>
                            <th>unit</th>
                            <th>kgCO2e / unit</th>
                            <th>status</th>
                            <th>review</th>
                            <th>akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($factors as $factor): ?>
                            <tr>
                                <td><?php echo e((string) $factor['id']); ?></td>
                                <td>
                                    <strong><?php echo e($factor['label']); ?></strong>
                                    <?php if (!empty($factor['source_note'])): ?>
                                        <div class="table-subline"><?php echo e($factor['source_note']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($factor['category']); ?></td>
                                <td><?php echo e($factor['base_unit']); ?></td>
                                <td><?php echo e((string) $factor['factor_kg_per_unit']); ?></td>
                                <td>
                                    <?php echo (int) $factor['active'] === 1 ? 'active' : 'inactive'; ?>
                                    <?php if ((int) $factor['is_seed'] === 1): ?>
                                        <div class="table-subline">seed, editable estimate</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($factor['review_after'] ?? '—'); ?></td>
                                <td>
                                    <a href="<?php echo e(route_url('admin.footprint.edit', ['id' => $factor['id']])); ?>">
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
