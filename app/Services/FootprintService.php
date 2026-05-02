<?php

namespace App\Services;

use App\Core\DB;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use JsonException;

class FootprintService
{
    public const STATUS_NOT_RATED = 'not_rated';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_COMPLETE = 'complete';

    public static function categoryOptions(): array
    {
        return [
            'device' => 'device',
            'transport' => 'transport',
            'ai' => 'ai',
            'energy' => 'energy',
            'other' => 'other',
        ];
    }

    public static function unitOptions(): array
    {
        return [
            'hour' => 'hour',
            'event' => 'event',
            'km' => 'km',
            'kwh' => 'kwh',
        ];
    }

    public static function frequencyOptions(): array
    {
        return [
            'daily' => 'daily',
            'weekly' => 'weekly',
            'monthly' => 'monthly',
        ];
    }

    public static function factorsForUser(int $userId, bool $activeOnly = false): array
    {
        $where = 'WHERE user_id = :user_id';
        if ($activeOnly) {
            $where .= ' AND active = 1';
        }

        return DB::selectAll(
            "SELECT *
             FROM footprint_factors
             {$where}
             ORDER BY active DESC, category ASC, label ASC",
            ['user_id' => $userId]
        );
    }

    public static function factorsForEntryForm(int $userId, ?int $entryId = null): array
    {
        if ($entryId === null) {
            return self::factorsForUser($userId, true);
        }

        return DB::selectAll(
            "SELECT DISTINCT f.*
             FROM footprint_factors f
             LEFT JOIN event_footprint_items i
                ON i.factor_id = f.id
                AND i.event_id = :entry_id
             WHERE f.user_id = :user_id
               AND (f.active = 1 OR i.id IS NOT NULL)
             ORDER BY f.active DESC, f.category ASC, f.label ASC",
            [
                'entry_id' => $entryId,
                'user_id' => $userId,
            ]
        );
    }

    public static function factorForUser(int $factorId, int $userId): ?array
    {
        return DB::selectOne(
            'SELECT *
             FROM footprint_factors
             WHERE id = :id AND user_id = :user_id
             LIMIT 1',
            [
                'id' => $factorId,
                'user_id' => $userId,
            ]
        );
    }

    public static function itemsForEntry(int $entryId): array
    {
        return DB::selectAll(
            'SELECT *
             FROM event_footprint_items
             WHERE event_id = :event_id
             ORDER BY id ASC',
            ['event_id' => $entryId]
        );
    }

    public static function itemsForEntries(array $entryIds): array
    {
        $entryIds = array_values(array_unique(array_map('intval', $entryIds)));

        if ($entryIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
        $rows = DB::selectAll(
            "SELECT *
             FROM event_footprint_items
             WHERE event_id IN ({$placeholders})
             ORDER BY event_id ASC, id ASC",
            $entryIds
        );

        $byEntry = [];
        foreach ($rows as $row) {
            $entryId = (int) $row['event_id'];
            $byEntry[$entryId][] = $row;
        }

        return $byEntry;
    }

    public static function validateItems(
        array $input,
        int $userId,
        array $allowedInactiveFactorIds = [],
        array $existingItemsById = []
    ): array {
        $factorIds = $input['footprint_factor_id'] ?? [];
        $quantities = $input['footprint_quantity'] ?? [];
        $allowedInactiveFactorIds = array_flip(array_map('intval', $allowedInactiveFactorIds));

        if (!is_array($factorIds)) {
            $factorIds = [];
        }

        if (!is_array($quantities)) {
            $quantities = [];
        }

        $rowCount = max(count($factorIds), count($quantities));
        $items = [];
        $errors = [];
        $incompleteRows = 0;

        for ($i = 0; $i < $rowCount; $i++) {
            $factorIdRaw = trim((string) ($factorIds[$i] ?? ''));
            $quantityRaw = trim(str_replace(',', '.', (string) ($quantities[$i] ?? '')));

            if ($factorIdRaw === '' && $quantityRaw === '') {
                continue;
            }

            if ($factorIdRaw === '' || $quantityRaw === '') {
                $incompleteRows++;
                continue;
            }

            if (!is_numeric($quantityRaw)) {
                $errors[] = 'Footprint množství musí být číslo.';
                continue;
            }

            $quantity = (float) $quantityRaw;
            if ($quantity < 0) {
                $errors[] = 'Footprint množství nesmí být záporné.';
                continue;
            }

            if (str_starts_with($factorIdRaw, 'snapshot:')) {
                $itemIdRaw = substr($factorIdRaw, strlen('snapshot:'));
                if (!ctype_digit($itemIdRaw) || !isset($existingItemsById[(int) $itemIdRaw])) {
                    $errors[] = 'Footprint snapshot není platný.';
                    continue;
                }

                $existingItem = $existingItemsById[(int) $itemIdRaw];
                $factorValue = (float) $existingItem['factor_kg_per_unit_snapshot'];
                $items[] = [
                    'factor_id' => isset($existingItem['factor_id']) ? (int) $existingItem['factor_id'] : null,
                    'label_snapshot' => $existingItem['label_snapshot'],
                    'category_snapshot' => $existingItem['category_snapshot'],
                    'base_unit_snapshot' => $existingItem['base_unit_snapshot'],
                    'factor_kg_per_unit_snapshot' => $factorValue,
                    'quantity' => $quantity,
                    'emissions_kg' => round($quantity * $factorValue, 9),
                    'factor_snapshot_json' => $existingItem['factor_snapshot_json'],
                ];
                continue;
            }

            if (!ctype_digit($factorIdRaw)) {
                $errors[] = 'Footprint faktor není platný.';
                continue;
            }

            $factor = self::factorForUser((int) $factorIdRaw, $userId);
            if (!$factor) {
                $errors[] = 'Footprint faktor neexistuje nebo není aktivní.';
                continue;
            }

            $factorId = (int) $factor['id'];
            if ((int) ($factor['active'] ?? 0) !== 1 && !isset($allowedInactiveFactorIds[$factorId])) {
                $errors[] = 'Footprint faktor neexistuje nebo není aktivní.';
                continue;
            }

            $factorValue = (float) $factor['factor_kg_per_unit'];
            $items[] = [
                'factor_id' => $factorId,
                'label_snapshot' => $factor['label'],
                'category_snapshot' => $factor['category'],
                'base_unit_snapshot' => $factor['base_unit'],
                'factor_kg_per_unit_snapshot' => $factorValue,
                'quantity' => $quantity,
                'emissions_kg' => round($quantity * $factorValue, 9),
                'factor_snapshot_json' => self::snapshotJson($factor),
            ];
        }

        return [
            'errors' => $errors,
            'items' => $items,
            'status' => self::statusForItems($items, $incompleteRows > 0),
        ];
    }

    public static function saveItemsForEntry(int $entryId, array $items, string $status): void
    {
        DB::execute(
            'DELETE FROM event_footprint_items WHERE event_id = :event_id',
            ['event_id' => $entryId]
        );

        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) $item['emissions_kg'];

            DB::execute(
                'INSERT INTO event_footprint_items (
                    event_id, factor_id, label_snapshot, category_snapshot, base_unit_snapshot,
                    factor_kg_per_unit_snapshot, quantity, emissions_kg, factor_snapshot_json
                ) VALUES (
                    :event_id, :factor_id, :label_snapshot, :category_snapshot, :base_unit_snapshot,
                    :factor_kg_per_unit_snapshot, :quantity, :emissions_kg, :factor_snapshot_json
                )',
                [
                    'event_id' => $entryId,
                    'factor_id' => $item['factor_id'],
                    'label_snapshot' => $item['label_snapshot'],
                    'category_snapshot' => $item['category_snapshot'],
                    'base_unit_snapshot' => $item['base_unit_snapshot'],
                    'factor_kg_per_unit_snapshot' => $item['factor_kg_per_unit_snapshot'],
                    'quantity' => $item['quantity'],
                    'emissions_kg' => $item['emissions_kg'],
                    'factor_snapshot_json' => $item['factor_snapshot_json'],
                ]
            );
        }

        DB::execute(
            'UPDATE entries
             SET emissions_total_kg = :emissions_total_kg,
                 emissions_status = :emissions_status,
                 footprint_updated_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id',
            [
                'id' => $entryId,
                'emissions_total_kg' => round($total, 9),
                'emissions_status' => $status,
            ]
        );
    }

    public static function totalsForPeriod(int $days): array
    {
        $dateTo = date('Y-m-d');
        $dateFrom = date('Y-m-d', strtotime('-' . (max(1, $days) - 1) . ' days'));

        $row = DB::selectOne(
            "SELECT
                COALESCE(SUM(emissions_total_kg), 0) AS emissions_total_kg,
                SUM(CASE WHEN emissions_status = 'not_rated' THEN 1 ELSE 0 END) AS not_rated_count,
                COUNT(*) AS entry_count
             FROM entries
             WHERE entry_date BETWEEN :date_from AND :date_to",
            [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]
        );

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'entry_count' => (int) ($row['entry_count'] ?? 0),
            'not_rated_count' => (int) ($row['not_rated_count'] ?? 0),
            'emissions_total_kg' => (float) ($row['emissions_total_kg'] ?? 0),
        ];
    }

    public static function recurringTotalsForPeriod(int $days): array
    {
        $dateTo = date('Y-m-d');
        $dateFrom = date('Y-m-d', strtotime('-' . (max(1, $days) - 1) . ' days'));

        $row = DB::selectOne(
            "SELECT
                COALESCE(SUM(emissions_kg), 0) AS emissions_total_kg,
                COUNT(*) AS instance_count
             FROM recurring_footprint_instances
             WHERE occurrence_date BETWEEN :date_from AND :date_to
               AND status = 'generated'",
            [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]
        );

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'instance_count' => (int) ($row['instance_count'] ?? 0),
            'emissions_total_kg' => (float) ($row['emissions_total_kg'] ?? 0),
        ];
    }

    public static function generateRecurringInstances(?string $from = null, ?string $to = null): int
    {
        $fromDate = new DateTimeImmutable($from ?: date('Y-m-d', strtotime('-7 days')));
        $toDate = new DateTimeImmutable($to ?: date('Y-m-d', strtotime('+35 days')));
        $period = new DatePeriod($fromDate, new DateInterval('P1D'), $toDate->modify('+1 day'));

        $rules = DB::selectAll(
            "SELECT r.*, f.label AS factor_label
             FROM recurring_footprint_rules r
             INNER JOIN footprint_factors f ON f.id = r.factor_id AND f.user_id = r.user_id
             WHERE r.active = 1
               AND f.active = 1"
        );

        $created = 0;
        foreach ($rules as $rule) {
            $factor = self::factorForUser((int) $rule['factor_id'], (int) $rule['user_id']);
            if (!$factor) {
                continue;
            }

            foreach ($period as $date) {
                $dateLabel = $date->format('Y-m-d');
                if (!self::ruleMatchesDate($rule, $date)) {
                    continue;
                }

                $existing = DB::selectOne(
                    'SELECT id
                     FROM recurring_footprint_instances
                     WHERE rule_id = :rule_id AND occurrence_date = :occurrence_date
                     LIMIT 1',
                    [
                        'rule_id' => $rule['id'],
                        'occurrence_date' => $dateLabel,
                    ]
                );

                if ($existing) {
                    continue;
                }

                $quantity = (float) $rule['quantity'];
                $emissions = round($quantity * (float) $factor['factor_kg_per_unit'], 9);
                DB::execute(
                    'INSERT INTO recurring_footprint_instances (
                        rule_id, user_id, occurrence_date, quantity, emissions_kg, factor_snapshot_json, status
                    ) VALUES (
                        :rule_id, :user_id, :occurrence_date, :quantity, :emissions_kg, :factor_snapshot_json, :status
                    )',
                    [
                        'rule_id' => $rule['id'],
                        'user_id' => $rule['user_id'],
                        'occurrence_date' => $dateLabel,
                        'quantity' => $quantity,
                        'emissions_kg' => $emissions,
                        'factor_snapshot_json' => self::snapshotJson($factor),
                        'status' => 'generated',
                    ]
                );
                $created++;
            }
        }

        return $created;
    }

    public static function formatKg(float|string|null $value): string
    {
        $number = (float) ($value ?? 0);
        if ($number === 0.0) {
            return '0 kgCO2e';
        }

        if ($number < 0.01) {
            return number_format($number, 4, '.', '') . ' kgCO2e';
        }

        return number_format($number, 2, '.', '') . ' kgCO2e';
    }

    protected static function statusForItems(array $items, bool $hasIncompleteRows): string
    {
        if ($items === []) {
            return self::STATUS_NOT_RATED;
        }

        return $hasIncompleteRows ? self::STATUS_PARTIAL : self::STATUS_COMPLETE;
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

    protected static function ruleMatchesDate(array $rule, DateTimeImmutable $date): bool
    {
        $start = new DateTimeImmutable((string) $rule['start_date']);
        if ($date < $start) {
            return false;
        }

        if (!empty($rule['end_date'])) {
            $end = new DateTimeImmutable((string) $rule['end_date']);
            if ($date > $end) {
                return false;
            }
        }

        $frequency = (string) $rule['frequency'];
        if ($frequency === 'daily') {
            return true;
        }

        if ($frequency === 'weekly') {
            $weekday = (string) ($rule['by_weekday'] ?? '');
            return $weekday === '' || (int) $weekday === (int) $date->format('N');
        }

        if ($frequency === 'monthly') {
            return $date->format('d') === $start->format('d');
        }

        return false;
    }
}
