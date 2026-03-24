<?php

namespace App\Services;

use App\Core\DB;

class BalanceService
{
    public static function rangeSummary(int $days, ?string $visibility = null): array
    {
        $days = max(1, $days);

        $dateTo = date('Y-m-d');
        $dateFrom = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

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
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
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
        $requiredActiveRecoveryMinutes = (int) round(($baseRecoveryMinutesPerDay * $days) + ($workloadPointsTotal * $workloadMultiplier));

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
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'entry_count' => $entryCount,

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

            'active_recovery_ratio_raw' => $activeRecoveryRatio,
            'active_recovery_ratio_label' => number_format($activeRecoveryRatio, 2, '.', ''),

            'recovery_delta_minutes' => $recoveryDeltaMinutes,
            'recovery_delta_hours_label' => self::signedHours($recoveryDeltaMinutes),

            'balance_status' => self::statusFromRatio($balanceRatio),
            'balance_bar' => self::ratioBar($balanceRatio),
            'active_recovery_bar' => self::ratioBar($activeRecoveryRatio),
        ];
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
}
