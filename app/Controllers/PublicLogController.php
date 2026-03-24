<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\View;
use App\Services\BalanceService;

class PublicLogController
{
    public static function home(): void
    {
        $entries = DB::selectAll(
            'SELECT
                e.*,
                c.name AS category_name,
                c.kind AS category_kind,
                p.title AS project_title,
                p.visibility AS project_visibility,
                p.public_label AS project_public_label
             FROM entries e
             INNER JOIN categories c ON c.id = e.category_id
             LEFT JOIN projects p ON p.id = e.project_id
             WHERE e.visibility = :visibility
             ORDER BY e.entry_date DESC, e.created_at DESC, e.id DESC',
            [
                'visibility' => 'public',
            ]
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

                $reflectionsByEntry[$entryId][] = $row;
            }
        }

        $groupedEntries = self::groupByMonth($entries);
        $summary = self::buildSummary($entries, 180);
        $balance30 = BalanceService::rangeSummary(30, 'public');

        View::render('pages/home', [
            'page_title' => t('page.home_title'),
            'month_groups' => $groupedEntries,
            'reflections_by_entry' => $reflectionsByEntry,
            'summary' => $summary,
            'balance_30' => $balance30,
        ]);
    }

    protected static function buildSummary(array $entries, int $days = 180): array
    {
        $cutoff = strtotime("-{$days} days");

        $recentEntries = array_values(array_filter(
            $entries,
            static function (array $entry) use ($cutoff): bool {
                $timestamp = strtotime((string) $entry['entry_date']);
                return $timestamp !== false && $timestamp >= $cutoff;
            }
        ));

        $workMinutes = 0;
        $recoveryMinutes = 0;
        $workByCategory = [];
        $recoveryByCategory = [];
        $entryTypeMinutes = [];

        foreach ($recentEntries as $entry) {
            $minutes = (int) ($entry['minutes'] ?? 0);
            $categoryName = (string) ($entry['category_name'] ?? 'unknown');
            $categoryKind = (string) ($entry['category_kind'] ?? 'work');
            $entryType = (string) ($entry['entry_type'] ?? 'achievement');

            if (!isset($entryTypeMinutes[$entryType])) {
                $entryTypeMinutes[$entryType] = 0;
            }
            $entryTypeMinutes[$entryType] += $minutes;

            if ($categoryKind === 'recovery') {
                if (!isset($recoveryByCategory[$categoryName])) {
                    $recoveryByCategory[$categoryName] = 0;
                }
                $recoveryByCategory[$categoryName] += $minutes;
                $recoveryMinutes += $minutes;
            } else {
                if (!isset($workByCategory[$categoryName])) {
                    $workByCategory[$categoryName] = 0;
                }
                $workByCategory[$categoryName] += $minutes;
                $workMinutes += $minutes;
            }
        }

        arsort($workByCategory);
        arsort($recoveryByCategory);
        arsort($entryTypeMinutes);

        return [
            'days' => $days,
            'entry_count' => count($recentEntries),
            'work_total_minutes' => $workMinutes,
            'recovery_total_minutes' => $recoveryMinutes,
            'work_mix' => self::mixRows($workByCategory, $workMinutes),
            'recovery_mix' => self::mixRows($recoveryByCategory, $recoveryMinutes),
            'type_mix' => self::mixRows($entryTypeMinutes, array_sum($entryTypeMinutes)),
        ];
    }

    protected static function mixRows(array $source, int $totalMinutes): array
    {
        $rows = [];

        foreach ($source as $label => $minutes) {
            $percent = $totalMinutes > 0 ? (int) round(($minutes / $totalMinutes) * 100) : 0;

            $rows[] = [
                'label' => $label,
                'minutes' => $minutes,
                'hours_label' => self::formatHours($minutes),
                'percent' => $percent,
                'bar' => self::asciiBar($percent),
            ];
        }

        return $rows;
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

    protected static function groupByMonth(array $entries): array
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

            $groups[$monthKey]['entries'][] = $entry;
        }

        return array_values($groups);
    }

    protected static function monthLabel(int $timestamp): string
    {
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
}
