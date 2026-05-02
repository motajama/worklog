<?php
$isEdit = ($mode ?? 'create') === 'edit';
$formAction = $isEdit
    ? route_url('admin.routines.update', ['id' => $routine['id']])
    : route_url('admin.routines.store');

$routineData = [
    'label' => old('label', $routine['label'] ?? ''),
    'description' => old('description', $routine['description'] ?? ''),
    'occurrences_per_week' => old('occurrences_per_week', (string) ($routine['occurrences_per_week'] ?? '5')),
    'start_date' => old('start_date', substr((string) ($routine['start_date'] ?? date('Y-m-d')), 0, 10)),
    'end_date' => old('end_date', substr((string) ($routine['end_date'] ?? ''), 0, 10)),
];
$activeChecked = old('active', (int) ($routine['active'] ?? 1) === 1 ? '1' : '');

$footprintFactors = $footprint_factors ?? [];
$storedItems = $routine_items ?? [];
$oldFactorIds = old('routine_factor_id', null);
$oldQuantities = old('routine_quantity', null);
$oldDurations = old('routine_duration_minutes', null);
$rows = [];

if (is_array($oldFactorIds) || is_array($oldQuantities) || is_array($oldDurations)) {
    $oldFactorIds = is_array($oldFactorIds) ? $oldFactorIds : [];
    $oldQuantities = is_array($oldQuantities) ? $oldQuantities : [];
    $oldDurations = is_array($oldDurations) ? $oldDurations : [];
    $rowCount = max(count($oldFactorIds), count($oldQuantities), count($oldDurations));

    for ($i = 0; $i < $rowCount; $i++) {
        $rows[] = [
            'factor_id' => (string) ($oldFactorIds[$i] ?? ''),
            'quantity' => (string) ($oldQuantities[$i] ?? ''),
            'duration_minutes' => (string) ($oldDurations[$i] ?? ''),
        ];
    }
} else {
    foreach ($storedItems as $item) {
        $rows[] = [
            'factor_id' => (string) ($item['factor_id'] ?? ''),
            'quantity' => (string) ($item['quantity'] ?? ''),
            'duration_minutes' => (string) ($item['duration_minutes'] ?? ''),
        ];
    }
}

if ($rows === []) {
    $rows = array_fill(0, 2, ['factor_id' => '', 'quantity' => '', 'duration_minutes' => '']);
} else {
    $rows[] = ['factor_id' => '', 'quantity' => '', 'duration_minutes' => ''];
}
?>

<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? ($isEdit ? t('page.admin_routine_edit_title') : t('page.admin_routine_create_title'))); ?></h1>
        <p class="page-lead">
            A routine is a private repeated activity. Add subevents per occurrence, then set how often the routine happens each week.
        </p>
    </header>

    <article class="card admin-form-card">
        <form method="post" action="<?php echo e($formAction); ?>">
            <div class="form-row">
                <label for="label">name</label>
                <input type="text" id="label" name="label" value="<?php echo e($routineData['label']); ?>" required>
            </div>

            <div class="form-row">
                <label for="description">description</label>
                <textarea id="description" name="description" rows="3"><?php echo e($routineData['description']); ?></textarea>
            </div>

            <div class="grid grid-3">
                <div class="form-row">
                    <label for="occurrences_per_week">times per week</label>
                    <input type="number" id="occurrences_per_week" name="occurrences_per_week" value="<?php echo e((string) $routineData['occurrences_per_week']); ?>" min="0.001" step="0.001" required>
                </div>

                <div class="form-row">
                    <label for="start_date">start date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo e($routineData['start_date']); ?>" required>
                </div>

                <div class="form-row">
                    <label for="end_date">end date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo e($routineData['end_date']); ?>">
                </div>
            </div>

            <div class="form-row checkbox-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="active" value="1" <?php echo $activeChecked ? 'checked' : ''; ?>>
                    active
                </label>
            </div>

            <div class="routine-fields" data-routine-form>
                <h2>subevents per occurrence</h2>
                <div class="help-line">
                    Duration is minutes per occurrence. Rows without duration still count for total footprint, but cannot contribute to kg CO2e/hour median.
                </div>

                <div class="routine-rows" data-routine-rows>
                    <?php foreach ($rows as $row): ?>
                        <div class="routine-row" data-routine-row>
                            <div class="form-row">
                                <label>factor</label>
                                <select name="routine_factor_id[]" data-routine-factor>
                                    <option value="">-</option>
                                    <?php foreach ($footprintFactors as $factor): ?>
                                        <option
                                            value="<?php echo e((string) $factor['id']); ?>"
                                            data-factor="<?php echo e((string) $factor['factor_kg_per_unit']); ?>"
                                            <?php echo (string) $row['factor_id'] === (string) $factor['id'] ? 'selected' : ''; ?>
                                        >
                                            <?php echo e($factor['label']); ?> / <?php echo e($factor['base_unit']); ?> / <?php echo e((string) $factor['factor_kg_per_unit']); ?> kgCO2e
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-row">
                                <label>quantity</label>
                                <input type="number" name="routine_quantity[]" value="<?php echo e((string) $row['quantity']); ?>" min="0" step="0.001" data-routine-quantity>
                            </div>

                            <div class="form-row">
                                <label>duration min</label>
                                <input type="number" name="routine_duration_minutes[]" value="<?php echo e((string) $row['duration_minutes']); ?>" min="0" step="1">
                            </div>

                            <div class="form-row">
                                <label>subtotal</label>
                                <div class="footprint-subtotal" data-routine-subtotal>-</div>
                            </div>

                            <div class="form-row">
                                <label>action</label>
                                <button type="button" class="secondary-button" data-routine-remove>remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="footprint-actions">
                    <button type="button" class="secondary-button" data-routine-add>Add subevent</button>
                    <div class="footprint-total">Per occurrence: <strong data-routine-total>-</strong></div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit"><?php echo e($isEdit ? t('action.update') : t('action.save')); ?></button>
                <a href="<?php echo e(route_url('admin.routines.index')); ?>" class="button-link secondary-link">
                    <?php echo e(t('action.back')); ?>
                </a>
            </div>
        </form>
    </article>
</section>

<script>
(function () {
    const form = document.querySelector('[data-routine-form]');
    if (!form) return;

    const rowsBox = form.querySelector('[data-routine-rows]');
    const addButton = form.querySelector('[data-routine-add]');
    const totalBox = form.querySelector('[data-routine-total]');

    function formatKg(value) {
        if (!Number.isFinite(value)) return '-';
        return value < 0.01 && value > 0 ? value.toFixed(4) + ' kgCO2e' : value.toFixed(2) + ' kgCO2e';
    }

    function syncTotals() {
        let total = 0;
        rowsBox.querySelectorAll('[data-routine-row]').forEach(function (row) {
            const select = row.querySelector('[data-routine-factor]');
            const quantityInput = row.querySelector('[data-routine-quantity]');
            const subtotalBox = row.querySelector('[data-routine-subtotal]');
            const selected = select && select.selectedOptions.length ? select.selectedOptions[0] : null;
            const factor = selected ? Number.parseFloat(selected.getAttribute('data-factor') || '') : NaN;
            const quantity = quantityInput ? Number.parseFloat(quantityInput.value || '') : NaN;
            const subtotal = Number.isFinite(factor) && Number.isFinite(quantity) ? factor * quantity : NaN;

            if (Number.isFinite(subtotal)) {
                total += subtotal;
                subtotalBox.textContent = formatKg(subtotal);
            } else {
                subtotalBox.textContent = '-';
            }
        });
        totalBox.textContent = formatKg(total);
    }

    function bindRow(row) {
        row.querySelectorAll('select, input').forEach(function (input) {
            input.addEventListener('input', syncTotals);
            input.addEventListener('change', syncTotals);
        });

        const removeButton = row.querySelector('[data-routine-remove]');
        if (removeButton) {
            removeButton.addEventListener('click', function () {
                const rows = rowsBox.querySelectorAll('[data-routine-row]');
                if (rows.length <= 1) {
                    row.querySelectorAll('select, input').forEach(function (input) { input.value = ''; });
                } else {
                    row.remove();
                }
                syncTotals();
            });
        }
    }

    rowsBox.querySelectorAll('[data-routine-row]').forEach(bindRow);
    if (addButton) {
        addButton.addEventListener('click', function () {
            const first = rowsBox.querySelector('[data-routine-row]');
            if (!first) return;
            const clone = first.cloneNode(true);
            clone.querySelectorAll('select, input').forEach(function (input) { input.value = ''; });
            const subtotal = clone.querySelector('[data-routine-subtotal]');
            if (subtotal) subtotal.textContent = '-';
            rowsBox.appendChild(clone);
            bindRow(clone);
            syncTotals();
        });
    }
    syncTotals();
})();
</script>
