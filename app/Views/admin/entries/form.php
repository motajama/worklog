<?php
$isEdit = ($mode ?? 'create') === 'edit';

$formAction = $isEdit
    ? route_url('admin.entries.update', ['id' => $entry['id']])
    : route_url('admin.entries.store');

$entryData = [
    'entry_date' => old('entry_date', $entry['entry_date'] ?? date('Y-m-d')),
    'slug' => old('slug', $entry['slug'] ?? ''),
    'entry_type' => old('entry_type', $entry['entry_type'] ?? 'achievement'),
    'title' => old('title', $entry['title'] ?? ''),
    'body' => old('body', $entry['body'] ?? ''),
    'public_text' => old('public_text', $entry['public_text'] ?? ''),
    'private_notes' => old('private_notes', $entry['private_notes'] ?? ''),
    'minutes' => old('minutes', (string) ($entry['minutes'] ?? 0)),
    'category_id' => old('category_id', (string) ($entry['category_id'] ?? '')),
    'project_id' => old('project_id', (string) ($entry['project_id'] ?? '')),
    'visibility' => old('visibility', $entry['visibility'] ?? 'private'),
    'locale' => old('locale', $entry['locale'] ?? 'cs'),
    'workload_override' => old('workload_override', (string) ($entry['workload_override'] ?? '')),
    'recovery_override' => old('recovery_override', (string) ($entry['recovery_override'] ?? '')),
    'what_happened' => old('what_happened', $entry['what_happened'] ?? ''),
    'why_it_matters' => old('why_it_matters', $entry['why_it_matters'] ?? ''),
    'my_take' => old('my_take', $entry['my_take'] ?? ''),
    'next_time' => old('next_time', $entry['next_time'] ?? ''),
];

$isInvisibleChecked = old(
    'is_invisible_work',
    (int) ($entry['is_invisible_work'] ?? 0) === 1 ? '1' : ''
);

$allowReflectionsChecked = old(
    'allow_reflections',
    (int) ($entry['allow_reflections'] ?? 0) === 1 ? '1' : ''
);

$workCategories = array_values(array_filter($categories, fn($c) => $c['kind'] === 'work'));
$recoveryCategories = array_values(array_filter($categories, fn($c) => $c['kind'] === 'recovery'));
?>

<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? ($isEdit ? t('page.admin_entry_edit_title') : t('page.admin_entry_create_title'))); ?></h1>
        <p class="page-lead">
            Jeden entry = jeden zaznamenaný kus práce, problému, obnovy nebo opravy.
        </p>
    </header>

    <article class="card admin-form-card">
        <form method="post" action="<?php echo e($formAction); ?>">
            <div class="grid grid-2">
                <div class="form-row">
                    <label for="entry_date"><?php echo e(t('label.date')); ?></label>
                    <input type="date" id="entry_date" name="entry_date" value="<?php echo e($entryData['entry_date']); ?>" required>
                </div>

                <div class="form-row">
                    <label for="entry_type"><?php echo e(t('label.type')); ?></label>
                    <select id="entry_type" name="entry_type" required>
                        <?php foreach ($type_options as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $entryData['entry_type'] === $value ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-row">
                    <label for="title"><?php echo e(t('label.title')); ?></label>
                    <input type="text" id="title" name="title" value="<?php echo e($entryData['title']); ?>">
                </div>

                <div class="form-row">
                    <label for="slug">slug</label>
                    <input type="text" id="slug" name="slug" value="<?php echo e($entryData['slug']); ?>">
                    <div class="help-line">Volitelné. Když nic nezadáš, vezme se z titulku. Když není ani titulek, zůstane prázdné.</div>
                </div>
            </div>

            <div class="form-row">
                <label for="body"><?php echo e(t('label.body')); ?></label>
                <textarea id="body" name="body" rows="6" required><?php echo e($entryData['body']); ?></textarea>
            </div>

            <div class="grid grid-2">
                <div class="form-row">
                    <label for="public_text"><?php echo e(t('label.public_text')); ?></label>
                    <textarea id="public_text" name="public_text" rows="4"><?php echo e($entryData['public_text']); ?></textarea>
                </div>

                <div class="form-row">
                    <label for="private_notes"><?php echo e(t('label.private_notes')); ?></label>
                    <textarea id="private_notes" name="private_notes" rows="4"><?php echo e($entryData['private_notes']); ?></textarea>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-row">
                    <label for="minutes"><?php echo e(t('label.minutes')); ?></label>
                    <input type="number" id="minutes" name="minutes" value="<?php echo e((string) $entryData['minutes']); ?>" min="0" step="1" required>
                </div>

                <div class="form-row">
                    <label for="project_id"><?php echo e(t('label.project')); ?></label>
                    <select id="project_id" name="project_id">
                        <option value="">—</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo e((string) $project['id']); ?>" <?php echo $entryData['project_id'] === (string) $project['id'] ? 'selected' : ''; ?>>
                                <?php echo e($project['title']); ?> [<?php echo e($project['visibility']); ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <label for="category_id"><?php echo e(t('label.category')); ?></label>
                <select id="category_id" name="category_id" required>
                    <option value="">—</option>

                    <optgroup label="work">
                        <?php foreach ($workCategories as $category): ?>
                            <option value="<?php echo e((string) $category['id']); ?>" <?php echo $entryData['category_id'] === (string) $category['id'] ? 'selected' : ''; ?>>
                                <?php echo e($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>

                    <optgroup label="recovery">
                        <?php foreach ($recoveryCategories as $category): ?>
                            <option value="<?php echo e((string) $category['id']); ?>" <?php echo $entryData['category_id'] === (string) $category['id'] ? 'selected' : ''; ?>>
                                <?php echo e($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
                <div class="help-line">
                    Achievement / fuckup / repair mají mít work kategorii. Regen má mít recovery kategorii.
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-row">
                    <label for="visibility"><?php echo e(t('label.visibility')); ?></label>
                    <select id="visibility" name="visibility" required>
                        <?php foreach ($visibility_options as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $entryData['visibility'] === $value ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="locale">locale</label>
                    <select id="locale" name="locale" required>
                        <?php foreach ($locale_options as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $entryData['locale'] === $value ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-row">
                    <label for="workload_override"><?php echo e(t('label.workload_override')); ?></label>
                    <input type="text" id="workload_override" name="workload_override" value="<?php echo e($entryData['workload_override']); ?>">
                </div>

                <div class="form-row">
                    <label for="recovery_override"><?php echo e(t('label.recovery_override')); ?></label>
                    <input type="text" id="recovery_override" name="recovery_override" value="<?php echo e($entryData['recovery_override']); ?>">
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-row checkbox-row">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_invisible_work" value="1" <?php echo $isInvisibleChecked ? 'checked' : ''; ?>>
                        <?php echo e(t('label.is_invisible_work')); ?>
                    </label>
                </div>

                <div class="form-row checkbox-row" id="allow-reflections-row">
                    <label class="checkbox-label">
                        <input type="checkbox" name="allow_reflections" value="1" <?php echo $allowReflectionsChecked ? 'checked' : ''; ?>>
                        <?php echo e(t('label.allow_reflections')); ?>
                    </label>
                </div>
            </div>

            <div class="fuckup-fields" id="fuckup-fields">
                <h2>fuckup meta</h2>

                <div class="form-row">
                    <label for="what_happened"><?php echo e(t('label.what_happened')); ?></label>
                    <textarea id="what_happened" name="what_happened" rows="3"><?php echo e($entryData['what_happened']); ?></textarea>
                </div>

                <div class="form-row">
                    <label for="why_it_matters"><?php echo e(t('label.why_it_matters')); ?></label>
                    <textarea id="why_it_matters" name="why_it_matters" rows="3"><?php echo e($entryData['why_it_matters']); ?></textarea>
                </div>

                <div class="form-row">
                    <label for="my_take"><?php echo e(t('label.my_take')); ?></label>
                    <textarea id="my_take" name="my_take" rows="3"><?php echo e($entryData['my_take']); ?></textarea>
                </div>

                <div class="form-row">
                    <label for="next_time"><?php echo e(t('label.next_time')); ?></label>
                    <textarea id="next_time" name="next_time" rows="3"><?php echo e($entryData['next_time']); ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit"><?php echo e($isEdit ? t('action.update') : t('action.save')); ?></button>
                <a href="<?php echo e(route_url('admin.entries.index')); ?>" class="button-link secondary-link">
                    <?php echo e(t('action.back')); ?>
                </a>
            </div>
        </form>
    </article>

    <?php if ($isEdit): ?>
        <article class="card danger-zone">
            <h2><?php echo e(t('section.danger_zone')); ?></h2>

            <form
                method="post"
                action="<?php echo e(route_url('admin.entries.delete', ['id' => $entry['id']])); ?>"
                onsubmit="return confirm('Opravdu smazat tento entry?');"
            >
                <button type="submit"><?php echo e(t('action.delete')); ?></button>
            </form>
        </article>
    <?php endif; ?>
</section>

<script>
(function () {
    const typeSelect = document.getElementById('entry_type');
    const fuckupFields = document.getElementById('fuckup-fields');

    function syncTypeUI() {
        const isFuckup = typeSelect && typeSelect.value === 'fuckup';

        if (fuckupFields) {
            fuckupFields.style.display = isFuckup ? 'block' : 'none';
        }
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', syncTypeUI);
        syncTypeUI();
    }
})();
</script>
