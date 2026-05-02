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

    'copsoq_quantitative_demands' => old('copsoq_quantitative_demands', (string) ($entry['copsoq_quantitative_demands'] ?? '')),
    'copsoq_work_pace' => old('copsoq_work_pace', (string) ($entry['copsoq_work_pace'] ?? '')),
    'copsoq_cognitive_demands' => old('copsoq_cognitive_demands', (string) ($entry['copsoq_cognitive_demands'] ?? '')),
    'copsoq_low_control' => old('copsoq_low_control', (string) ($entry['copsoq_low_control'] ?? '')),

    'nfr_exhausted' => old('nfr_exhausted', (string) ($entry['nfr_exhausted'] ?? '')),
    'nfr_detach_difficulty' => old('nfr_detach_difficulty', (string) ($entry['nfr_detach_difficulty'] ?? '')),
    'nfr_need_long_recovery' => old('nfr_need_long_recovery', (string) ($entry['nfr_need_long_recovery'] ?? '')),
    'nfr_overload' => old('nfr_overload', (string) ($entry['nfr_overload'] ?? '')),

    'recovery_detachment' => old('recovery_detachment', (string) ($entry['recovery_detachment'] ?? '')),
    'recovery_relaxation' => old('recovery_relaxation', (string) ($entry['recovery_relaxation'] ?? '')),
    'recovery_mastery' => old('recovery_mastery', (string) ($entry['recovery_mastery'] ?? '')),
    'recovery_control' => old('recovery_control', (string) ($entry['recovery_control'] ?? '')),

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

$averageScore = static function (array $fields) use ($entry): string {
    $values = [];

    foreach ($fields as $field) {
        $value = $entry[$field] ?? '';

        if ($value !== '' && is_numeric($value)) {
            $values[] = (int) $value;
        }
    }

    if ($values === []) {
        return '';
    }

    return (string) (int) round(array_sum($values) / count($values));
};

$simpleBalanceData = [
    'balance_workload' => (string) old('balance_workload', $averageScore([
        'copsoq_quantitative_demands',
        'copsoq_work_pace',
        'copsoq_cognitive_demands',
        'copsoq_low_control',
    ])),
    'balance_fatigue' => (string) old('balance_fatigue', $averageScore([
        'nfr_exhausted',
        'nfr_detach_difficulty',
        'nfr_need_long_recovery',
        'nfr_overload',
    ])),
    'balance_recovery' => (string) old('balance_recovery', $averageScore([
        'recovery_detachment',
        'recovery_relaxation',
        'recovery_mastery',
        'recovery_control',
    ])),
];

$likertOptions = [
    '' => 'nevyplněno',
    '0' => '0 = velmi nízké',
    '1' => '1 = nízké',
    '2' => '2 = střední',
    '3' => '3 = vysoké',
    '4' => '4 = velmi vysoké',
];

$simpleBalanceQuestions = [
    'balance_workload' => 'Jak silný byl pracovní tlak?',
    'balance_fatigue' => 'Jak silná byla únava / potřeba zotavení?',
    'balance_recovery' => 'Jak dobrá byla reálná obnova?',
];

$footprintFactors = $footprint_factors ?? [];
$storedFootprintItems = $footprint_items ?? [];
$storedFootprintItemsById = [];
foreach ($storedFootprintItems as $item) {
    $storedFootprintItemsById[(string) ($item['id'] ?? '')] = $item;
}
$oldFootprintFactorIds = old('footprint_factor_id', null);
$oldFootprintQuantities = old('footprint_quantity', null);
$footprintRows = [];

if (is_array($oldFootprintFactorIds) || is_array($oldFootprintQuantities)) {
    $oldFootprintFactorIds = is_array($oldFootprintFactorIds) ? $oldFootprintFactorIds : [];
    $oldFootprintQuantities = is_array($oldFootprintQuantities) ? $oldFootprintQuantities : [];
    $rowCount = max(count($oldFootprintFactorIds), count($oldFootprintQuantities));

    for ($i = 0; $i < $rowCount; $i++) {
        $factorId = (string) ($oldFootprintFactorIds[$i] ?? '');
        $snapshotItem = null;
        if (str_starts_with($factorId, 'snapshot:')) {
            $itemId = substr($factorId, strlen('snapshot:'));
            $snapshotItem = $storedFootprintItemsById[$itemId] ?? null;
            $factorId = '';
        }

        $footprintRows[] = [
            'factor_id' => $factorId,
            'quantity' => (string) ($oldFootprintQuantities[$i] ?? ''),
            'item_id' => (string) ($snapshotItem['id'] ?? ''),
            'label_snapshot' => (string) ($snapshotItem['label_snapshot'] ?? ''),
            'base_unit_snapshot' => (string) ($snapshotItem['base_unit_snapshot'] ?? ''),
            'factor_kg_per_unit_snapshot' => (string) ($snapshotItem['factor_kg_per_unit_snapshot'] ?? ''),
        ];
    }
} else {
    foreach ($storedFootprintItems as $item) {
        $footprintRows[] = [
            'factor_id' => (string) ($item['factor_id'] ?? ''),
            'quantity' => (string) ($item['quantity'] ?? ''),
            'item_id' => (string) ($item['id'] ?? ''),
            'label_snapshot' => (string) ($item['label_snapshot'] ?? ''),
            'base_unit_snapshot' => (string) ($item['base_unit_snapshot'] ?? ''),
            'factor_kg_per_unit_snapshot' => (string) ($item['factor_kg_per_unit_snapshot'] ?? ''),
        ];
    }
}

if ($footprintRows === []) {
    $footprintRows = array_fill(0, 3, [
        'factor_id' => '',
        'quantity' => '',
    ]);
} elseif (!is_array($oldFootprintFactorIds) && !is_array($oldFootprintQuantities)) {
    $footprintRows[] = [
        'factor_id' => '',
        'quantity' => '',
    ];
}
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

            <div class="questionnaire-fields">
                <h2>rychlý balance check</h2>
                <div class="help-line">
                    Dobrovolné. Tři odpovědi 0–4 stačí pro měsíční veřejný přehled.
                </div>

                <div class="grid grid-3">
                    <?php foreach ($simpleBalanceQuestions as $field => $label): ?>
                        <div class="form-row">
                            <label for="<?php echo e($field); ?>"><?php echo e($label); ?></label>
                            <select id="<?php echo e($field); ?>" name="<?php echo e($field); ?>">
                                <?php foreach ($likertOptions as $value => $optionLabel): ?>
                                    <option value="<?php echo e($value); ?>" <?php echo $simpleBalanceData[$field] === $value ? 'selected' : ''; ?>>
                                        <?php echo e($optionLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="footprint-fields" data-footprint-form>
                <h2>carbon footprint</h2>
                <div class="help-line">
                    Volitelné. Můžeš přidat více položek; uloží se snapshot faktoru, takže historické entries se po úpravě faktoru nepřepočítají.
                </div>

                <div class="footprint-rows" data-footprint-rows>
                    <?php foreach ($footprintRows as $row): ?>
                        <div class="footprint-row" data-footprint-row>
                            <div class="form-row">
                                <label>faktor</label>
                                <select name="footprint_factor_id[]" data-footprint-factor>
                                    <option value="">—</option>
                                    <?php if (($row['factor_id'] ?? '') === '' && ($row['item_id'] ?? '') !== ''): ?>
                                        <option
                                            value="snapshot:<?php echo e((string) $row['item_id']); ?>"
                                            data-factor="<?php echo e((string) ($row['factor_kg_per_unit_snapshot'] ?? 0)); ?>"
                                            data-unit="<?php echo e((string) ($row['base_unit_snapshot'] ?? '')); ?>"
                                            data-footprint-snapshot-option
                                            selected
                                        >
                                            <?php echo e((string) ($row['label_snapshot'] ?? 'saved snapshot')); ?> / <?php echo e((string) ($row['base_unit_snapshot'] ?? '')); ?> / <?php echo e((string) ($row['factor_kg_per_unit_snapshot'] ?? 0)); ?> kgCO2e / saved snapshot
                                        </option>
                                    <?php endif; ?>
                                    <?php foreach ($footprintFactors as $factor): ?>
                                        <option
                                            value="<?php echo e((string) $factor['id']); ?>"
                                            data-factor="<?php echo e((string) $factor['factor_kg_per_unit']); ?>"
                                            data-unit="<?php echo e((string) $factor['base_unit']); ?>"
                                            <?php echo (string) $row['factor_id'] === (string) $factor['id'] ? 'selected' : ''; ?>
                                        >
                                            <?php echo e($factor['label']); ?> / <?php echo e($factor['base_unit']); ?> / <?php echo e((string) $factor['factor_kg_per_unit']); ?> kgCO2e<?php echo (int) ($factor['active'] ?? 1) !== 1 ? ' / inactive' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-row">
                                <label>množství / čas</label>
                                <input type="number" name="footprint_quantity[]" value="<?php echo e((string) $row['quantity']); ?>" min="0" step="0.001" data-footprint-quantity>
                            </div>

                            <div class="form-row">
                                <label>subtotal</label>
                                <div class="footprint-subtotal" data-footprint-subtotal>—</div>
                            </div>

                            <div class="form-row">
                                <label>akce</label>
                                <button type="button" class="secondary-button" data-footprint-remove>odebrat</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="footprint-actions">
                    <button type="button" class="secondary-button" data-footprint-add>Přidat položku</button>
                    <div class="footprint-total">Total: <strong data-footprint-total>—</strong></div>
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

    const footprintForm = document.querySelector('[data-footprint-form]');
    if (!footprintForm) {
        return;
    }

    const rowsBox = footprintForm.querySelector('[data-footprint-rows]');
    const addButton = footprintForm.querySelector('[data-footprint-add]');
    const totalBox = footprintForm.querySelector('[data-footprint-total]');

    function formatKg(value) {
        if (!Number.isFinite(value)) {
            return '—';
        }

        if (value > 0 && value < 0.01) {
            return value.toFixed(4) + ' kgCO2e';
        }

        return value.toFixed(2) + ' kgCO2e';
    }

    function syncFootprintTotals() {
        let total = 0;

        rowsBox.querySelectorAll('[data-footprint-row]').forEach(function (row) {
            const select = row.querySelector('[data-footprint-factor]');
            const quantityInput = row.querySelector('[data-footprint-quantity]');
            const subtotalBox = row.querySelector('[data-footprint-subtotal]');
            const selected = select && select.selectedOptions.length ? select.selectedOptions[0] : null;
            const factor = selected ? Number.parseFloat(selected.getAttribute('data-factor') || '') : NaN;
            const quantity = quantityInput ? Number.parseFloat(quantityInput.value || '') : NaN;
            const subtotal = Number.isFinite(factor) && Number.isFinite(quantity) ? factor * quantity : NaN;

            if (Number.isFinite(subtotal)) {
                total += subtotal;
                subtotalBox.textContent = formatKg(subtotal);
            } else {
                subtotalBox.textContent = '—';
            }
        });

        totalBox.textContent = formatKg(total);
    }

    function bindRow(row) {
        const select = row.querySelector('[data-footprint-factor]');
        const quantityInput = row.querySelector('[data-footprint-quantity]');
        const removeButton = row.querySelector('[data-footprint-remove]');

        if (select) {
            select.addEventListener('change', syncFootprintTotals);
        }

        if (quantityInput) {
            quantityInput.addEventListener('input', syncFootprintTotals);
        }

        if (removeButton) {
            removeButton.addEventListener('click', function () {
                const rows = rowsBox.querySelectorAll('[data-footprint-row]');
                if (rows.length <= 1) {
                    if (select) select.value = '';
                    if (quantityInput) quantityInput.value = '';
                } else {
                    row.remove();
                }
                syncFootprintTotals();
            });
        }
    }

    rowsBox.querySelectorAll('[data-footprint-row]').forEach(bindRow);

    if (addButton) {
        addButton.addEventListener('click', function () {
            const firstRow = rowsBox.querySelector('[data-footprint-row]');
            if (!firstRow) {
                return;
            }

            const clone = firstRow.cloneNode(true);
            const select = clone.querySelector('[data-footprint-factor]');
            const quantityInput = clone.querySelector('[data-footprint-quantity]');
            const subtotalBox = clone.querySelector('[data-footprint-subtotal]');
            if (select) {
                select.querySelectorAll('[data-footprint-snapshot-option]').forEach(function (option) {
                    option.remove();
                });
                select.value = '';
            }
            if (quantityInput) quantityInput.value = '';
            if (subtotalBox) subtotalBox.textContent = '—';
            rowsBox.appendChild(clone);
            bindRow(clone);
            syncFootprintTotals();
        });
    }

    syncFootprintTotals();
})();
</script>
