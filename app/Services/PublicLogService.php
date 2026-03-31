<?php

namespace App\Services;

use App\Core\DB;

class PublicLogService
{
    public static function build(int $balanceDays = 30, int $workMixDays = 180): array
    {
        $entries = DB::selectAll(
            "SELECT
                e.id,
                e.entry_date,
                e.title,
                e.body,
                e.public_text,
                e.entry_type,
                e.allow_reflections,
                c.name AS category_name,
                c.kind AS category_kind
             FROM entries e
             INNER JOIN categories c ON c.id = e.category_id
             WHERE e.visibility = 'public'
             ORDER BY e.entry_date DESC, e.created_at DESC, e.id DESC"
        );

        $entryIds = array_map(
            static fn(array $entry) => (int) $entry['id'],
            $entries
        );

        $reflectionsByEntry = [];
        if (!empty($entryIds)) {
            $placeholders = implode(',', array_fill(0, count($entryIds), '?'));

            $rows = DB::selectAll(
                "SELECT
                    id,
                    entry_id,
                    author_name,
                    body,
                    is_anonymous,
                    created_at
                 FROM reflections
                 WHERE status = 'approved'
                   AND entry_id IN ({$placeholders})
                 ORDER BY created_at ASC, id ASC",
                $entryIds
            );

            foreach ($rows as $row) {
                $entryId = (int) $row['entry_id'];

                if (!isset($reflectionsByEntry[$entryId])) {
                    $reflectionsByEntry[$entryId] = [];
                }

                $reflectionsByEntry[$entryId][] = [
                    'id' => (int) $row['id'],
                    'author_name' => $row['author_name'],
                    'body' => $row['body'],
                    'is_anonymous' => (int) $row['is_anonymous'],
                    'created_at' => $row['created_at'],
                ];
            }
        }

        return [
            'month_groups' => self::groupByMonth($entries, $reflectionsByEntry),
            'work_mix' => self::workMix($workMixDays),

            // public log: balance vždy za poslední uzavřený měsíc,
            // stále ale z celého datasetu (public + private + internal)
            'balance' => BalanceService::lastClosedMonthSummary(null),

            'balance_days' => $balanceDays,
            'work_mix_days' => $workMixDays,
        ];
    }

    protected static function workMix(int $days): array
    {
        $days = max(1, $days);
        $dateTo = date('Y-m-d');
        $dateFrom = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        $rows = DB::selectAll(
            "SELECT
                c.name AS label,
                SUM(e.minutes) AS minutes
             FROM entries e
             INNER JOIN categories c ON c.id = e.category_id
             WHERE c.kind = 'work'
               AND e.entry_date BETWEEN :date_from AND :date_to
             GROUP BY c.id, c.name
             ORDER BY minutes DESC, c.name ASC",
            [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]
        );

        $totalMinutes = 0;
        foreach ($rows as $row) {
            $totalMinutes += (int) $row['minutes'];
        }

        $mix = [];
        foreach ($rows as $row) {
            $minutes = (int) $row['minutes'];
            $percent = $totalMinutes > 0 ? (int) round(($minutes / $totalMinutes) * 100) : 0;

            $mix[] = [
                'label' => $row['label'],
                'minutes' => $minutes,
                'hours_label' => self::formatHours($minutes),
                'percent' => $percent,
                'bar' => self::asciiBar($percent),
            ];
        }

        return [
            'days' => $days,
            'total_minutes' => $totalMinutes,
            'total_hours_label' => self::formatHours($totalMinutes),
            'rows' => $mix,
        ];
    }

    protected static function groupByMonth(array $entries, array $reflectionsByEntry = []): array
    {
        $groups = [];

        foreach ($entries as $entry) {
            $timestamp = strtotime((string) $entry['entry_date']);
            $monthKey = date('Y-m', $timestamp);

            if (!isset($groups[$monthKey])) {
                $groups[$monthKey] = [
                    'key' => $monthKey,
                    'label' => self::monthLabel($timestamp),
                    'entries' => [],
                ];
            }

            $bodyText = trim((string) ($entry['public_text'] ?: $entry['body']));
            $bodyText = preg_replace('/\s+/u', ' ', $bodyText ?? '');
            $bodyText = trim((string) $bodyText);

            $entryTitle = trim((string) ($entry['title'] ?? ''));

            if ($entryTitle === '' && $bodyText !== '') {
                $entryTitle = mb_strimwidth($bodyText, 0, 72, '…', 'UTF-8');
            }

            if ($bodyText === '') {
                $bodyText = $entryTitle !== '' ? $entryTitle : '(empty entry)';
            }

            $entryId = (int) $entry['id'];

            $groups[$monthKey]['entries'][] = [
                'id' => $entryId,
                'title' => $entryTitle,
                'text' => $bodyText,
                'entry_type' => $entry['entry_type'],
                'category_name' => $entry['category_name'],
                'entry_date' => $entry['entry_date'],
                'allow_reflections' => (int) ($entry['allow_reflections'] ?? 0),
                'reflections' => $reflectionsByEntry[$entryId] ?? [],
            ];
        }

        return array_values($groups);
    }

    protected static function monthLabel(int $timestamp): string
    {
        if (current_locale() === 'en') {
            return strtolower(date('F Y', $timestamp));
        }

        $months = [
            1 => 'leden',
            2 => 'únor',
            3 => 'březen',
            4 => 'duben',
            5 => 'květen',
            6 => 'červen',
            7 => 'červenec',
            8 => 'srpen',
            9 => 'září',
            10 => 'říjen',
            11 => 'listopad',
            12 => 'prosinec',
        ];

        $month = (int) date('n', $timestamp);
        $year = date('Y', $timestamp);

        return ($months[$month] ?? date('F', $timestamp)) . ' ' . $year;
    }

    protected static function asciiBar(int $percent, int $width = 20): string
    {
        $filled = (int) round(($percent / 100) * $width);
        $filled = max(0, min($width, $filled));

        return str_repeat('█', $filled) . str_repeat('·', $width - $filled);
    }

    protected static function formatHours(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 h';
        }

        $hours = $minutes / 60;

        if (abs($hours - round($hours)) < 0.01) {
            return (string) ((int) round($hours)) . ' h';
        }

        return number_format($hours, 1, '.', '') . ' h';
    }
}
