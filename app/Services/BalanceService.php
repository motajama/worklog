<?php

namespace App\Services;

use App\Core\DB;
use DateInterval;
use DateTimeImmutable;

class BalanceService
{
    protected const COPSOQ_FIELDS = [
        'copsoq_quantitative_demands',
        'copsoq_work_pace',
        'copsoq_cognitive_demands',
        'copsoq_low_control',
    ];

    protected const NFR_FIELDS = [
        'nfr_exhausted',
        'nfr_detach_difficulty',
        'nfr_need_long_recovery',
        'nfr_overload',
    ];

    protected const RECOVERY_EXPERIENCE_FIELDS = [
        'recovery_detachment',
        'recovery_relaxation',
        'recovery_mastery',
        'recovery_control',
    ];

    public static function rangeSummary(int $days, ?string $visibility = null): array
    {
        $days = max(1, $days);

        $dateTo = new DateTimeImmutable('today');
        $dateFrom = $dateTo->sub(new DateInterval('P' . ($days - 1) . 'D'));

        return self::summaryForPeriod(
            $dateFrom->format('Y-m-d'),
            $dateTo->format('Y-m-d'),
            $visibility
        );
    }

    public static function lastClosedMonthSummary(?string $visibility = null): array
    {
        $today = new DateTimeImmutable('today');
        $lastDayCurrentMonth = new DateTimeImmutable('last day of this month');

        if ($today->format('Y-m-d') === $lastDayCurrentMonth->format('Y-m-d')) {
            $dateFrom = $today->modify('first day of this month');
            $dateTo = $today;
        } else {
            $firstDayCurrentMonth = $today->modify('first day of this month');
            $dateTo = $firstDayCurrentMonth->modify('-1 day');
            $dateFrom = $dateTo->modify('first day of this month');
        }

        return self::summaryForPeriod(
            $dateFrom->format('Y-m-d'),
            $dateTo->format('Y-m-d'),
            $visibility
        );
    }

    public static function summaryForPeriod(string $dateFrom, string $dateTo, ?string $visibility = null): array
    {
        $from = new DateTimeImmutable($dateFrom);
        $to = new DateTimeImmutable($dateTo);

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $days = (int) $from->diff($to)->days + 1;

        $sleepMinutesPerDay = (int) config('app.sleep_minutes_per_day', 480);
        $baseRecoveryMinutesPerDay = (float) config('app.recovery.base_minutes', 30);
        $workloadMultiplier = (float) config('app.recovery.workload_multiplier', 0.35);

        $sql = "
            SELECT
                COUNT(e.id) AS entry_count,
                COALESCE(SUM(CASE WHEN c.kind = 'work' THEN e.minutes ELSE 0 END), 0) AS work_minutes,
                COALESCE(SUM(CASE WHEN c.kind = 'recovery' THEN e.minutes ELSE 0 END), 0) AS raw_recovery_minutes,
                COALESCE(SUM(
                    COALESCE(
                        e.workload_override,
                        CASE
                            WHEN c.kind = 'work' THEN e.minutes * c.intensity_weight
                            ELSE 0
                        END
                    )
                ), 0) AS workload_points_total,
                COALESCE(SUM(
                    COALESCE(
                        e.recovery_override,
                        CASE
                            WHEN c.kind = 'recovery' THEN e.minutes * c.recovery_weight
                            ELSE 0
                        END
                    )
                ), 0) AS active_recovery_points_total
            FROM entries e
            INNER JOIN categories c ON c.id = e.category_id
            WHERE e.entry_date BETWEEN :date_from AND :date_to
        ";

        $params = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        if ($visibility !== null) {
            $sql .= " AND e.visibility = :visibility";
            $params['visibility'] = $visibility;
        }

        $row = DB::selectOne($sql, $params) ?? [];

        $entryCount = (int) ($row['entry_count'] ?? 0);
        $workMinutes = (int) round((float) ($row['work_minutes'] ?? 0));
        $rawRecoveryMinutes = (int) round((float) ($row['raw_recovery_minutes'] ?? 0));
        $workloadPointsTotal = (float) ($row['workload_points_total'] ?? 0);
        $activeRecoveryPointsTotal = (float) ($row['active_recovery_points_total'] ?? 0);

        $sleepMinutesTotal = $sleepMinutesPerDay * $days;
        $requiredActiveRecoveryMinutes = (int) round(
            ($baseRecoveryMinutesPerDay * $days) + ($workloadPointsTotal * $workloadMultiplier)
        );

        $activeRecoveryMinutes = (int) round($activeRecoveryPointsTotal);

        $balanceRatio = ($sleepMinutesTotal + $requiredActiveRecoveryMinutes) > 0
            ? ($sleepMinutesTotal + $activeRecoveryMinutes) / ($sleepMinutesTotal + $requiredActiveRecoveryMinutes)
            : 1.0;

        $activeRecoveryRatio = $requiredActiveRecoveryMinutes > 0
            ? $activeRecoveryMinutes / $requiredActiveRecoveryMinutes
            : 1.0;

        $recoveryDeltaMinutes = $activeRecoveryMinutes - $requiredActiveRecoveryMinutes;

        return [
            'days' => $days,
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'entry_count' => $entryCount,

            'period_kind' => 'closed_month',
            'period_label_cs' => self::monthLabel($from, 'cs'),
            'period_label_en' => self::monthLabel($from, 'en'),

            'work_minutes' => $workMinutes,
            'work_hours_label' => self::formatHours($workMinutes),

            'raw_recovery_minutes' => $rawRecoveryMinutes,
            'raw_recovery_hours_label' => self::formatHours($rawRecoveryMinutes),

            'sleep_minutes_total' => $sleepMinutesTotal,
            'sleep_hours_label' => self::formatHours($sleepMinutesTotal),

            'workload_points_total' => round($workloadPointsTotal, 1),

            'active_recovery_minutes' => $activeRecoveryMinutes,
            'active_recovery_hours_label' => self::formatHours($activeRecoveryMinutes),

            'required_active_recovery_minutes' => $requiredActiveRecoveryMinutes,
            'required_active_recovery_hours_label' => self::formatHours($requiredActiveRecoveryMinutes),

            'balance_ratio_raw' => $balanceRatio,
            'balance_ratio_label' => number_format($balanceRatio, 2, '.', ''),

            'display_ratio_raw' => $activeRecoveryRatio,
            'display_ratio_label' => number_format($activeRecoveryRatio, 2, '.', ''),
            'display_status' => self::statusFromRatio($activeRecoveryRatio),
            'display_bar' => self::ratioBar($activeRecoveryRatio),

            'active_recovery_ratio_raw' => $activeRecoveryRatio,
            'active_recovery_ratio_label' => number_format($activeRecoveryRatio, 2, '.', ''),

            'recovery_delta_minutes' => $recoveryDeltaMinutes,
            'recovery_delta_hours_label' => self::signedHours($recoveryDeltaMinutes),

            'balance_status' => self::statusFromRatio($balanceRatio),
            'balance_bar' => self::ratioBar($balanceRatio),
            'active_recovery_bar' => self::ratioBar($activeRecoveryRatio),
        ];
    }

    public static function questionnaireSummary(string $dateFrom, string $dateTo, ?string $visibility = null): array
    {
        $fields = array_merge(
            ['id', 'entry_date'],
            self::COPSOQ_FIELDS,
            self::NFR_FIELDS,
            self::RECOVERY_EXPERIENCE_FIELDS
        );

        $sql = 'SELECT ' . implode(', ', $fields) . ' FROM entries WHERE entry_date BETWEEN :date_from AND :date_to';
        $params = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        if ($visibility !== null) {
            $sql .= ' AND visibility = :visibility';
            $params['visibility'] = $visibility;
        }

        $sql .= ' ORDER BY entry_date ASC, id ASC';

        $rows = DB::selectAll($sql, $params);

        $copsoqAverages = [];
        $nfrAverages = [];
        $recoveryAverages = [];

        $questionnaireEntryCount = 0;

        foreach ($rows as $row) {
            $copsoq = self::completeScaleAverage($row, self::COPSOQ_FIELDS);
            $nfr = self::completeScaleAverage($row, self::NFR_FIELDS);
            $recovery = self::completeScaleAverage($row, self::RECOVERY_EXPERIENCE_FIELDS);

            if ($copsoq !== null || $nfr !== null || $recovery !== null) {
                $questionnaireEntryCount++;
            }

            if ($copsoq !== null) {
                $copsoqAverages[] = $copsoq;
            }

            if ($nfr !== null) {
                $nfrAverages[] = $nfr;
            }

            if ($recovery !== null) {
                $recoveryAverages[] = $recovery;
            }
        }

        $copsoqMean = self::mean($copsoqAverages);
        $nfrMean = self::mean($nfrAverages);
        $recoveryMean = self::mean($recoveryAverages);

        $hasCompleteComposite = $copsoqMean !== null && $nfrMean !== null && $recoveryMean !== null;

        $derivedRaw = $hasCompleteComposite
            ? $recoveryMean - (($copsoqMean + $nfrMean) / 2)
            : null;

        $derivedPercent = $derivedRaw !== null
            ? (int) round(self::clamp((($derivedRaw + 4.0) / 8.0) * 100.0, 0.0, 100.0))
            : 0;

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'entry_count' => count($rows),
            'questionnaire_entry_count' => $questionnaireEntryCount,
            'has_data' => $questionnaireEntryCount > 0,
            'has_complete_composite' => $hasCompleteComposite,

            'copsoq_workload_mean_raw' => $copsoqMean,
            'copsoq_workload_mean_label' => self::scaleLabel($copsoqMean),

            'nfr_mean_raw' => $nfrMean,
            'nfr_mean_label' => self::scaleLabel($nfrMean),

            'recovery_experience_mean_raw' => $recoveryMean,
            'recovery_experience_mean_label' => self::scaleLabel($recoveryMean),

            'derived_balance_raw' => $derivedRaw,
            'derived_balance_label' => $derivedRaw !== null ? number_format($derivedRaw, 2, '.', '') : '—',

            'derived_balance_percent' => $derivedPercent,
            'derived_balance_percent_label' => $derivedRaw !== null ? $derivedPercent . '/100' : '—',
            'derived_balance_bar' => self::percentBar($derivedPercent),

            'derived_status' => self::scientificStatus($derivedPercent),
        ];
    }

    public static function questionnaireTrendLast12Months(?string $visibility = null): array
    {
        $today = new DateTimeImmutable('today');
        $lastDayCurrentMonth = new DateTimeImmutable('last day of this month');

        $latestMonth = $today->format('Y-m-d') === $lastDayCurrentMonth->format('Y-m-d')
            ? $today->modify('first day of this month')
            : $today->modify('first day of last month');

        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $latestMonth->modify('-' . $i . ' months');
            $dateFrom = $month->format('Y-m-01');
            $dateTo = $month->format('Y-m-t');

            $summary = self::questionnaireSummary($dateFrom, $dateTo, $visibility);

            $series[] = [
                'label' => $month->format('m'),
                'year' => $month->format('Y'),
                'value' => (int) ($summary['derived_balance_percent'] ?? 0),
            ];
        }

        $values = array_map(
            static fn(array $row): int => (int) $row['value'],
            $series
        );

        return [
            'series' => $series,
            'chart_rows' => self::verticalChartRows($values, 10),
            'labels_row' => implode(' ', array_map(
                static fn(array $row): string => $row['label'],
                $series
            )),
        ];
    }

    protected static function completeScaleAverage(array $row, array $fields): ?float
    {
        $values = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $row) || $row[$field] === null || $row[$field] === '') {
                return null;
            }

            $values[] = (int) $row[$field];
        }

        return self::mean($values);
    }

    protected static function mean(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        return array_sum($values) / count($values);
    }

    protected static function scaleLabel(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format($value, 2, '.', '') . ' / 4';
    }

    protected static function scientificStatus(int $percent): string
    {
        if ($percent >= 70) {
            return 'resilient';
        }

        if ($percent >= 55) {
            return 'stable';
        }

        if ($percent >= 45) {
            return 'mixed';
        }

        if ($percent >= 30) {
            return 'strained';
        }

        return 'high strain';
    }

    protected static function verticalChartRows(array $values, int $height = 10): array
    {
        $rows = [];

        for ($level = $height; $level >= 1; $level--) {
            $line = [];

            foreach ($values as $value) {
                $filledHeight = (int) round(($value / 100) * $height);
                $filledHeight = max(0, min($height, $filledHeight));
                $line[] = $filledHeight >= $level ? '█' : '·';
            }

            $rows[] = implode(' ', $line);
        }

        return $rows;
    }

    protected static function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    protected static function statusFromRatio(float $ratio): string
    {
        if ($ratio >= 1.05) {
            return 'surplus';
        }

        if ($ratio >= 0.95) {
            return 'balanced';
        }

        if ($ratio >= 0.90) {
            return 'light deficit';
        }

        if ($ratio >= 0.80) {
            return 'deficit';
        }

        return 'hard deficit';
    }

    protected static function ratioBar(float $ratio, int $width = 20): string
    {
        $clamped = max(0.0, min(1.0, $ratio));
        $filled = (int) round($clamped * $width);
        $filled = max(0, min($width, $filled));

        return str_repeat('█', $filled) . str_repeat('·', $width - $filled);
    }

    protected static function percentBar(int $percent, int $width = 20): string
    {
        $clamped = max(0, min(100, $percent));
        $filled = (int) round(($clamped / 100) * $width);
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

    protected static function signedHours(int $minutes): string
    {
        $sign = $minutes >= 0 ? '+' : '-';
        $absolute = abs($minutes);

        return $sign . self::formatHours($absolute);
    }

    public static function approximateTrendLast12Months(?string $visibility = null): array
    {
        $today = new DateTimeImmutable('today');
        $lastDayCurrentMonth = new DateTimeImmutable('last day of this month');

        $latestMonth = $today->format('Y-m-d') === $lastDayCurrentMonth->format('Y-m-d')
            ? $today->modify('first day of this month')
            : $today->modify('first day of last month');

        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $latestMonth->modify('-' . $i . ' months');
            $dateFrom = $month->format('Y-m-01');
            $dateTo = $month->format('Y-m-t');

            $summary = self::summaryForPeriod($dateFrom, $dateTo, $visibility);
            $value = (int) round(max(0, min(100, ($summary['display_ratio_raw'] ?? 0) * 100)));

            $series[] = [
                'label' => $month->format('m'),
                'year' => $month->format('Y'),
                'value' => $value,
            ];
        }

        $values = array_map(
            static fn(array $row): int => (int) $row['value'],
            $series
        );

        return [
            'series' => $series,
            'chart_rows' => self::verticalChartRows($values, 10),
            'labels_row' => implode(' ', array_map(
                static fn(array $row): string => $row['label'],
                $series
            )),
        ];
    }

    protected static function monthLabel(DateTimeImmutable $date, string $locale): string
    {
        if ($locale === 'en') {
            return strtolower($date->format('F Y'));
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

        $month = (int) $date->format('n');
        $year = $date->format('Y');

        return ($months[$month] ?? $date->format('F')) . ' ' . $year;
    }
}
