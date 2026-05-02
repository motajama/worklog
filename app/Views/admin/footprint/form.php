<?php
$isEdit = ($mode ?? 'create') === 'edit';
$formAction = $isEdit
    ? route_url('admin.footprint.update', ['id' => $factor['id']])
    : route_url('admin.footprint.store');

$factorData = [
    'label' => old('label', $factor['label'] ?? ''),
    'category' => old('category', $factor['category'] ?? 'other'),
    'base_unit' => old('base_unit', $factor['base_unit'] ?? 'event'),
    'factor_kg_per_unit' => old('factor_kg_per_unit', (string) ($factor['factor_kg_per_unit'] ?? '')),
    'source_note' => old('source_note', $factor['source_note'] ?? ''),
    'methodology_note' => old('methodology_note', $factor['methodology_note'] ?? ''),
    'geography_code' => old('geography_code', $factor['geography_code'] ?? 'CZ'),
    'valid_from' => old('valid_from', substr((string) ($factor['valid_from'] ?? date('Y-m-d')), 0, 10)),
    'review_after' => old('review_after', substr((string) ($factor['review_after'] ?? date('Y-m-d', strtotime('+1 year'))), 0, 10)),
];

$activeChecked = old('active', (int) ($factor['active'] ?? 1) === 1 ? '1' : '');
?>

<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? ($isEdit ? t('page.admin_footprint_edit_title') : t('page.admin_footprint_create_title'))); ?></h1>
        <p class="page-lead">
            Faktor je odhad v kgCO2e na jednotku. Změna faktoru neovlivní už uložené entries.
        </p>
    </header>

    <article class="card admin-form-card">
        <form method="post" action="<?php echo e($formAction); ?>">
            <div class="form-row">
                <label for="label">label</label>
                <input type="text" id="label" name="label" value="<?php echo e($factorData['label']); ?>" required>
            </div>

            <div class="grid grid-2">
                <div class="form-row">
                    <label for="category">category</label>
                    <select id="category" name="category" required>
                        <?php foreach ($category_options as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $factorData['category'] === $value ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="base_unit">base unit</label>
                    <select id="base_unit" name="base_unit" required>
                        <?php foreach ($unit_options as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $factorData['base_unit'] === $value ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-row">
                    <label for="factor_kg_per_unit">kgCO2e / unit</label>
                    <input type="number" id="factor_kg_per_unit" name="factor_kg_per_unit" value="<?php echo e((string) $factorData['factor_kg_per_unit']); ?>" min="0" step="0.000000001" required>
                </div>

                <div class="form-row">
                    <label for="geography_code">geography code</label>
                    <input type="text" id="geography_code" name="geography_code" value="<?php echo e($factorData['geography_code']); ?>">
                </div>
            </div>

            <div class="form-row">
                <label for="source_note">source note</label>
                <textarea id="source_note" name="source_note" rows="3"><?php echo e($factorData['source_note']); ?></textarea>
            </div>

            <div class="form-row">
                <label for="methodology_note">methodology note</label>
                <textarea id="methodology_note" name="methodology_note" rows="3"><?php echo e($factorData['methodology_note']); ?></textarea>
                <div class="help-line">Používej jako poznámku k odhadu, ne jako tvrdou pravdu.</div>
            </div>

            <div class="grid grid-2">
                <div class="form-row">
                    <label for="valid_from">valid from</label>
                    <input type="date" id="valid_from" name="valid_from" value="<?php echo e($factorData['valid_from']); ?>">
                </div>

                <div class="form-row">
                    <label for="review_after">review after</label>
                    <input type="date" id="review_after" name="review_after" value="<?php echo e($factorData['review_after']); ?>">
                </div>
            </div>

            <div class="form-row checkbox-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="active" value="1" <?php echo $activeChecked ? 'checked' : ''; ?>>
                    active
                </label>
            </div>

            <div class="form-actions">
                <button type="submit"><?php echo e($isEdit ? t('action.update') : t('action.save')); ?></button>
                <a href="<?php echo e(route_url('admin.footprint.index')); ?>" class="button-link secondary-link">
                    <?php echo e(t('action.back')); ?>
                </a>
            </div>
        </form>
    </article>
</section>
