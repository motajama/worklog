<?php
$routines = $routines ?? [];
$formatKg = static fn(float|string|null $value): string => \App\Services\FootprintService::formatKg($value);
$formatHours = static function (int $minutes): string {
    if ($minutes <= 0) {
        return '0 h';
    }

    $hours = $minutes / 60;
    return abs($hours - round($hours)) < 0.01
        ? (string) ((int) round($hours)) . ' h'
        : number_format($hours, 1, '.', '') . ' h';
};
?>

<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? t('page.admin_routines_title')); ?></h1>
        <p class="page-lead">
            Private repeated activities such as commuting. They are not public log entries, but they feed aggregate footprint statistics.
        </p>
    </header>

    <div class="toolbar">
        <a href="<?php echo e(route_url('admin.routines.create')); ?>" class="button-link">new routine</a>
    </div>

    <?php if (empty($routines)): ?>
        <article class="card">
            <p>No routines yet.</p>
        </article>
    <?php else: ?>
        <article class="card">
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>routine</th>
                            <th>frequency</th>
                            <th>per occurrence</th>
                            <th>period</th>
                            <th>status</th>
                            <th>action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routines as $routine): ?>
                            <?php $perOccurrence = $routine['per_occurrence'] ?? ['emissions_kg' => 0, 'duration_minutes' => 0]; ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($routine['label']); ?></strong>
                                    <?php if (!empty($routine['description'])): ?>
                                        <div class="table-subline"><?php echo e($routine['description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e((string) $routine['occurrences_per_week']); ?> / week</td>
                                <td>
                                    <?php echo e($formatKg($perOccurrence['emissions_kg'])); ?>
                                    <div class="table-subline"><?php echo e($formatHours((int) $perOccurrence['duration_minutes'])); ?></div>
                                </td>
                                <td>
                                    <?php echo e((string) $routine['start_date']); ?>
                                    <?php if (!empty($routine['end_date'])): ?>
                                        <div class="table-subline">until <?php echo e((string) $routine['end_date']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo (int) $routine['active'] === 1 ? 'active' : 'inactive'; ?></td>
                                <td>
                                    <a href="<?php echo e(route_url('admin.routines.edit', ['id' => $routine['id']])); ?>">
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
