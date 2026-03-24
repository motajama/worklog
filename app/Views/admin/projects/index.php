<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? t('page.admin_projects_title')); ?></h1>
        <p class="page-lead">
            Interní seznam projektů. Odtud se bude později přiřazovat projekt k achievementům, fuckupům, regen i repair.
        </p>
    </header>

    <div class="toolbar">
        <a href="<?php echo e(route_url('admin.projects.create')); ?>" class="button-link">
            <?php echo e(t('action.new_project')); ?>
        </a>
    </div>

    <?php if (empty($projects)): ?>
        <article class="card">
            <p>Zatím tu není žádný projekt.</p>
        </article>
    <?php else: ?>
        <article class="card">
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo e(t('label.title')); ?></th>
                            <th>slug</th>
                            <th><?php echo e(t('label.visibility')); ?></th>
                            <th><?php echo e(t('label.status')); ?></th>
                            <th>locale</th>
                            <th>featured</th>
                            <th>sort</th>
                            <th>akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo e((string) $project['id']); ?></td>
                                <td>
                                    <strong><?php echo e($project['title']); ?></strong>
                                    <?php if (!empty($project['public_label'])): ?>
                                        <div class="table-subline">public label: <?php echo e($project['public_label']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo e($project['slug']); ?></code></td>
                                <td><?php echo e($project['visibility']); ?></td>
                                <td><?php echo e($project['status']); ?></td>
                                <td><?php echo e($project['locale']); ?></td>
                                <td><?php echo (int) $project['is_featured'] === 1 ? 'yes' : 'no'; ?></td>
                                <td><?php echo e((string) $project['sort_order']); ?></td>
                                <td>
                                    <a href="<?php echo e(route_url('admin.projects.edit', ['id' => $project['id']])); ?>">
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
