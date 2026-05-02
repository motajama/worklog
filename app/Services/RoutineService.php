<?php

namespace App\Services;

use App\Core\DB;
use DateTimeImmutable;
use JsonException;

class RoutineService
{
    public static function routinesForUser(int $userId): array
    {
        return DB::selectAll(
            'SELECT *
             FROM routines
             WHERE user_id = :user_id
             ORDER BY active DESC, label ASC',
            ['user_id' => $userId]
        );
    }

    public static function routineForUser(int $routineId, int $userId): ?array
    {
        return DB::selectOne(
            'SELECT *
             FROM routines
             WHERE id = :id AND user_id = :user_id
             LIMIT 1',
            [
                'id' => $routineId,
                'user_id' => $userId,
            ]
        );
    }

    public static function itemsForRoutine(int $routineId): array
    {
        return DB::selectAll(
            'SELECT *
             FROM routine_footprint_items
             WHERE routine_id = :routine_id
             ORDER BY id ASC',
            ['routine_id' => $routineId]
        );
    }

    public static function totalsForPeriod(int $days): array
    {
        $dateTo = date('Y-m-d');
        $dateFrom = date('Y-m-d', strtotime('-' . (max(1, $days) - 1) . ' days'));
        $totals = self::routineTotalsBetween($dateFrom, $dateTo);

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'routine_count' => $totals['routine_count'],
            'occurrence_count' => $totals['occurrence_count'],
            'emissions_total_kg' => $totals['emissions_total_kg'],
        ];
    }

    public static function syntheticEventsForMonthlyCarbonPerHour(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $bounds = self::routineDateBounds($dateFrom, $dateTo);
        if ($bounds === null) {
            return [];
        }

        [$from, $to] = $bounds;
        $months = self::monthRanges($from, $to);
        $routines = self::activeRoutineRows();
        $events = [];

        foreach ($months as $month) {
            foreach ($routines as $routine) {
                $activeDays = self::activeDaysInRange($routine, $month['date_from'], $month['date_to']);
                if ($activeDays <= 0) {
                    continue;
                }

                $perOccurrence = self::perOccurrenceTotals((int) $routine['id']);
                if ($perOccurrence['emissions_kg'] <= 0 || $perOccurrence['duration_minutes'] <= 0) {
                    continue;
                }

                $occurrences = self::expectedOccurrences($routine, $activeDays);
                if ($occurrences <= 0) {
                    continue;
                }

                $repeat = max(1, (int) round($occurrences));
                for ($i = 0; $i < $repeat; $i++) {
                    $events[] = [
                        'entry_date' => $month['month'] . '-15',
                        'minutes' => $perOccurrence['duration_minutes'],
                        'emissions_total_kg' => $perOccurrence['emissions_kg'],
                        'emissions_status' => FootprintService::STATUS_COMPLETE,
                    ];
                }
            }
        }

        return $events;
    }

    public static function validateRoutine(array $input): array
    {
        $label = trim((string) ($input['label'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $occurrencesRaw = trim(str_replace(',', '.', (string) ($input['occurrences_per_week'] ?? '')));
        $startDate = trim((string) ($input['start_date'] ?? ''));
        $endDate = trim((string) ($input['end_date'] ?? ''));
        $active = isset($input['active']) ? 1 : 0;

        $errors = [];

        if ($label === '') {
            $errors[] = 'Routine name is required.';
        }

        if ($occurrencesRaw === '' || !is_numeric($occurrencesRaw) || (float) $occurrencesRaw <= 0) {
            $errors[] = 'Occurrences per week must be a positive number.';
        }

        if ($startDate === '') {
            $errors[] = 'Start date is required.';
        } elseif (!self::isValidDate($startDate)) {
            $errors[] = 'Start date must use YYYY-MM-DD.';
        }

        if ($endDate !== '' && !self::isValidDate($endDate)) {
            $errors[] = 'End date must use YYYY-MM-DD.';
        }

        if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
            $errors[] = 'End date cannot be before start date.';
        }

        return [
            'errors' => $errors,
            'values' => [
                'label' => $label,
                'description' => $description !== '' ? $description : null,
                'occurrences_per_week' => (float) $occurrencesRaw,
                'start_date' => $startDate !== '' ? $startDate : date('Y-m-d'),
                'end_date' => $endDate !== '' ? $endDate : null,
                'active' => $active,
            ],
        ];
    }

    public static function validateItems(array $input, int $userId): array
    {
        $factorIds = is_array($input['routine_factor_id'] ?? null) ? $input['routine_factor_id'] : [];
        $quantities = is_array($input['routine_quantity'] ?? null) ? $input['routine_quantity'] : [];
        $durations = is_array($input['routine_duration_minutes'] ?? null) ? $input['routine_duration_minutes'] : [];
        $rowCount = max(count($factorIds), count($quantities), count($durations));
        $items = [];
        $errors = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $factorIdRaw = trim((string) ($factorIds[$i] ?? ''));
            $quantityRaw = trim(str_replace(',', '.', (string) ($quantities[$i] ?? '')));
            $durationRaw = trim((string) ($durations[$i] ?? ''));

            if ($factorIdRaw === '' && $quantityRaw === '' && $durationRaw === '') {
                continue;
            }

            if ($factorIdRaw === '' || $quantityRaw === '') {
                $errors[] = 'Each routine subevent needs a footprint factor and quantity.';
                continue;
            }

            if (!ctype_digit($factorIdRaw)) {
                $errors[] = 'Routine footprint factor is invalid.';
                continue;
            }

            if (!is_numeric($quantityRaw) || (float) $quantityRaw < 0) {
                $errors[] = 'Routine quantity must be a non-negative number.';
                continue;
            }

            $duration = 0;
            if ($durationRaw !== '') {
                if (!ctype_digit($durationRaw)) {
                    $errors[] = 'Routine duration must be a whole number of minutes.';
                    continue;
                }

                $duration = (int) $durationRaw;
            }

            $factor = FootprintService::factorForUser((int) $factorIdRaw, $userId);
            if (!$factor || (int) ($factor['active'] ?? 0) !== 1) {
                $errors[] = 'Routine footprint factor does not exist or is inactive.';
                continue;
            }

            $factorValue = (float) $factor['factor_kg_per_unit'];
            $quantity = (float) $quantityRaw;
            $items[] = [
                'factor_id' => (int) $factor['id'],
                'label_snapshot' => $factor['label'],
                'category_snapshot' => $factor['category'],
                'base_unit_snapshot' => $factor['base_unit'],
                'factor_kg_per_unit_snapshot' => $factorValue,
                'quantity' => $quantity,
                'duration_minutes' => $duration,
                'emissions_kg' => round($quantity * $factorValue, 9),
                'factor_snapshot_json' => self::snapshotJson($factor),
            ];
        }

        if ($items === []) {
            $errors[] = 'Add at least one routine subevent.';
        }

        return [
            'errors' => $errors,
            'items' => $items,
        ];
    }

    public static function saveItemsForRoutine(int $routineId, array $items): void
    {
        DB::execute(
            'DELETE FROM routine_footprint_items WHERE routine_id = :routine_id',
            ['routine_id' => $routineId]
        );

        foreach ($items as $item) {
            DB::execute(
                'INSERT INTO routine_footprint_items (
                    routine_id, factor_id, label_snapshot, category_snapshot, base_unit_snapshot,
                    factor_kg_per_unit_snapshot, quantity, duration_minutes, emissions_kg, factor_snapshot_json
                ) VALUES (
                    :routine_id, :factor_id, :label_snapshot, :category_snapshot, :base_unit_snapshot,
                    :factor_kg_per_unit_snapshot, :quantity, :duration_minutes, :emissions_kg, :factor_snapshot_json
                )',
                [
                    'routine_id' => $routineId,
                    'factor_id' => $item['factor_id'],
                    'label_snapshot' => $item['label_snapshot'],
                    'category_snapshot' => $item['category_snapshot'],
                    'base_unit_snapshot' => $item['base_unit_snapshot'],
                    'factor_kg_per_unit_snapshot' => $item['factor_kg_per_unit_snapshot'],
                    'quantity' => $item['quantity'],
                    'duration_minutes' => $item['duration_minutes'],
                    'emissions_kg' => $item['emissions_kg'],
                    'factor_snapshot_json' => $item['factor_snapshot_json'],
                ]
            );
        }
    }

    public static function perOccurrenceTotals(int $routineId): array
    {
        $row = DB::selectOne(
            'SELECT
                COALESCE(SUM(emissions_kg), 0) AS emissions_kg,
                COALESCE(SUM(duration_minutes), 0) AS duration_minutes
             FROM routine_footprint_items
             WHERE routine_id = :routine_id',
            ['routine_id' => $routineId]
        );

        return [
            'emissions_kg' => (float) ($row['emissions_kg'] ?? 0),
            'duration_minutes' => (int) ($row['duration_minutes'] ?? 0),
        ];
    }

    protected static function routineTotalsBetween(string $dateFrom, string $dateTo): array
    {
        $routines = self::activeRoutineRows();
        $total = 0.0;
        $occurrencesTotal = 0.0;
        $routineCount = 0;

        foreach ($routines as $routine) {
            $activeDays = self::activeDaysInRange($routine, $dateFrom, $dateTo);
            if ($activeDays <= 0) {
                continue;
            }

            $perOccurrence = self::perOccurrenceTotals((int) $routine['id']);
            if ($perOccurrence['emissions_kg'] <= 0) {
                continue;
            }

            $occurrences = self::expectedOccurrences($routine, $activeDays);
            if ($occurrences <= 0) {
                continue;
            }

            $routineCount++;
            $occurrencesTotal += $occurrences;
            $total += $perOccurrence['emissions_kg'] * $occurrences;
        }

        return [
            'routine_count' => $routineCount,
            'occurrence_count' => (int) round($occurrencesTotal),
            'emissions_total_kg' => round($total, 9),
        ];
    }

    protected static function activeRoutineRows(): array
    {
        return DB::selectAll(
            'SELECT *
             FROM routines
             WHERE active = 1
             ORDER BY start_date ASC, id ASC'
        );
    }

    protected static function routineDateBounds(?string $dateFrom, ?string $dateTo): ?array
    {
        $from = $dateFrom;
        $to = $dateTo ?? date('Y-m-t');

        if ($from === null) {
            $row = DB::selectOne("SELECT MIN(start_date) AS date_from FROM routines WHERE active = 1");
            $from = $row['date_from'] ?? null;
        }

        if (!$from) {
            return null;
        }

        return [$from, $to];
    }

    protected static function monthRanges(string $dateFrom, string $dateTo): array
    {
        $cursor = new DateTimeImmutable(date('Y-m-01', strtotime($dateFrom)));
        $end = new DateTimeImmutable(date('Y-m-01', strtotime($dateTo)));
        $months = [];

        while ($cursor <= $end) {
            $months[] = [
                'month' => $cursor->format('Y-m'),
                'date_from' => $cursor->format('Y-m-01'),
                'date_to' => $cursor->format('Y-m-t'),
            ];
            $cursor = $cursor->modify('+1 month');
        }

        return $months;
    }

    protected static function activeDaysInRange(array $routine, string $dateFrom, string $dateTo): int
    {
        $start = max((string) $routine['start_date'], $dateFrom);
        $end = $dateTo;
        if (!empty($routine['end_date'])) {
            $end = min((string) $routine['end_date'], $dateTo);
        }

        if ($end < $start) {
            return 0;
        }

        return (int) floor((strtotime($end) - strtotime($start)) / 86400) + 1;
    }

    protected static function expectedOccurrences(array $routine, int $activeDays): float
    {
        return ((float) $routine['occurrences_per_week']) * ($activeDays / 7);
    }

    protected static function snapshotJson(array $factor): string
    {
        $snapshot = [
            'id' => (int) $factor['id'],
            'label' => $factor['label'],
            'category' => $factor['category'],
            'base_unit' => $factor['base_unit'],
            'factor_kg_per_unit' => (float) $factor['factor_kg_per_unit'],
            'source_note' => $factor['source_note'] ?? null,
            'methodology_note' => $factor['methodology_note'] ?? null,
            'geography_code' => $factor['geography_code'] ?? null,
            'valid_from' => $factor['valid_from'] ?? null,
        ];

        try {
            return json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            return '{}';
        }
    }

    protected static function isValidDate(string $date): bool
    {
        $parts = explode('-', $date);

        return count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }
}
