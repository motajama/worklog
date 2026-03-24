<?php
$isEdit = ($mode ?? 'create') === 'edit';

$formAction = $isEdit
    ? route_url('admin.projects.update', ['id' => $project['id']])
    : route_url('admin.projects.store');

$projectData = [
    'title' => old('title', $project['title'] ?? ''),
    'slug' => old('slug', $project['slug'] ?? ''),
    'description' => old('description', $project['description'] ?? ''),
    'public_label' => old('public_label', $project['public_label'] ?? ''),
    'visibility' => old('visibility', $project['visibility'] ?? 'private'),
    'status' => old('status', $project['status'] ?? 'active'),
    'locale' => old('locale', $project['locale'] ?? 'cs'),
    'sort_order' => old('sort_order', (string) ($project['sort_order'] ?? 0)),
];

$featuredChecked = old(
    'is_featured',
    (int) ($project['is_featured'] ?? 0) === 1 ? '1' : ''
);
?>

<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? ($isEdit ? t('page.admin_project_edit_title') : t('page.admin_project_create_title'))); ?></h1>
        <p class="page-lead">
            Projekt je interní osa práce. Může být soukromý, veřejný nebo masked.
        </p>
    </header>

    <article class="card admin-form-card">
        <form method="post" action="<?php echo e($formAction); ?>">
            <div class="form-row">
                <label for="title"><?php echo e(t('label.title')); ?></label>
                <input type="text" id="title" name="title" value="<?php echo e($projectData['title']); ?>" required>
            </div>

            <div class="form-row">
                <label for="slug">slug</label>
                <input type="text" id="slug" name="slug" value="<?php echo e($projectData['slug']); ?>">
                <div class="help-line">
                    Když to necháš prázdné, slug se odvodí automaticky z titulku.
                </div>
            </div>

            <div class="form-row">
                <label for="description"><?php echo e(t('label.description')); ?></label>
                <textarea id="description" name="description" rows="5"><?php echo e($projectData['description']); ?></textarea>
            </div>

            <div class="form-row">
                <label for="public_label"><?php echo e(t('label.public_label')); ?></label>
                <input type="text" id="public_label" name="public_label" value="<?php echo e($projectData['public_label']); ?>">
                <div class="help-line">
                    Hodí se hlavně pro masked projekty. Např. interně „GAČR X“, veřejně „research project“.
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-row">
                    <label for="visibility"><?php echo e(t('label.visibility')); ?></label>
                    <select id="visibility" name="visibility" required>
                        <?php foreach ($visibility_options as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $projectData['visibility'] === $value ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="status"><?php echo e(t('label.status')); ?></label>
                    <select id="status" name="status" required>
                        <?php foreach ($status_options as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $projectData['status'] === $value ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-row">
                    <label for="locale">locale</label>
                    <select id="locale" name="locale" required>
                        <?php foreach ($locale_options as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $projectData['locale'] === $value ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="sort_order"><?php echo e(t('label.sort_order')); ?></label>
                    <input type="number" id="sort_order" name="sort_order" value="<?php echo e((string) $projectData['sort_order']); ?>" min="0" step="1">
                </div>
            </div>

            <div class="form-row checkbox-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_featured" value="1" <?php echo $featuredChecked ? 'checked' : ''; ?>>
                    <?php echo e(t('label.is_featured')); ?>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit"><?php echo e($isEdit ? t('action.update') : t('action.save')); ?></button>
                <a href="<?php echo e(route_url('admin.projects.index')); ?>" class="button-link secondary-link">
                    <?php echo e(t('action.back')); ?>
                </a>
            </div>
        </form>
    </article>
</section>
