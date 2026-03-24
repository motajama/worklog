<?php
$reflections = $reflections ?? [];
?>

<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? t('page.admin_reflections_title')); ?></h1>
        <p class="page-lead">
            Moderace veřejných reflexí. Pending jde schválit nebo zamítnout. Approved a rejected tu zůstávají jako stopa.
        </p>
    </header>

    <?php if (empty($reflections)): ?>
        <article class="card">
            <p>Zatím tu nejsou žádné reflexe.</p>
        </article>
    <?php else: ?>
        <div class="reflection-admin-list">
            <?php foreach ($reflections as $reflection): ?>
                <article class="card reflection-admin-item reflection-status-<?php echo e($reflection['status']); ?>">
                    <div class="reflection-admin-header">
                        <div>
                            <strong><?php echo e($reflection['entry_title'] ?: '(bez titulku)'); ?></strong>
                            <div class="table-subline">
                                entry #<?php echo e((string) $reflection['entry_id']); ?>
                                · <?php echo e($reflection['entry_type']); ?>
                                · <?php echo e($reflection['entry_date']); ?>
                            </div>
                        </div>

                        <div class="reflection-status-badge">
                            <?php echo e($reflection['status']); ?>
                        </div>
                    </div>

                    <div class="reflection-admin-meta">
                        <?php if ((int) $reflection['is_anonymous'] === 1): ?>
                            anonym
                        <?php else: ?>
                            <?php echo e($reflection['author_name'] ?: 'bez jména'); ?>
                        <?php endif; ?>

                        <?php if (!empty($reflection['author_email'])): ?>
                            · <?php echo e($reflection['author_email']); ?>
                        <?php endif; ?>

                        · <?php echo e($reflection['created_at']); ?>
                    </div>

                    <div class="reflection-admin-body">
                        <?php echo nl2br(e($reflection['body'])); ?>
                    </div>

                    <div class="form-actions">
                        <?php if ($reflection['status'] !== 'approved'): ?>
                            <form method="post" action="<?php echo e(route_url('admin.reflections.approve', ['id' => $reflection['id']])); ?>" class="inline-form">
                                <button type="submit"><?php echo e(t('action.approve')); ?></button>
                            </form>
                        <?php endif; ?>

                        <?php if ($reflection['status'] !== 'rejected'): ?>
                            <form method="post" action="<?php echo e(route_url('admin.reflections.reject', ['id' => $reflection['id']])); ?>" class="inline-form">
                                <button type="submit"><?php echo e(t('action.reject')); ?></button>
                            </form>
                        <?php endif; ?>

                        <a href="<?php echo e(route_url('admin.entries.edit', ['id' => $reflection['entry_id']])); ?>" class="button-link secondary-link">
                            open entry
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
