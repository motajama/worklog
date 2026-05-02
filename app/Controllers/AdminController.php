<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\View;
use App\Services\BalanceService;
use App\Services\FootprintService;
use App\Services\RoutineService;

class AdminController
{
    public static function dashboard(): void
    {
        $entryCountRow = DB::selectOne('SELECT COUNT(*) AS count FROM entries');
        $projectCountRow = DB::selectOne('SELECT COUNT(*) AS count FROM projects');
        $pendingCountRow = DB::selectOne("SELECT COUNT(*) AS count FROM reflections WHERE status = 'pending'");

        $latestEntries = DB::selectAll(
            'SELECT
                e.id,
                e.entry_date,
                e.entry_type,
                e.title,
                e.visibility,
                e.minutes,
                e.emissions_total_kg,
                e.emissions_status,
                c.name AS category_name,
                p.title AS project_title
             FROM entries e
             INNER JOIN categories c ON c.id = e.category_id
             LEFT JOIN projects p ON p.id = e.project_id
             ORDER BY e.entry_date DESC, e.created_at DESC, e.id DESC
             LIMIT 8'
        );

        $pendingReflections = DB::selectAll(
            "SELECT
                r.id,
                r.entry_id,
                r.author_name,
                r.is_anonymous,
                r.created_at,
                r.body,
                e.title AS entry_title,
                e.entry_date
             FROM reflections r
             INNER JOIN entries e ON e.id = r.entry_id
             WHERE r.status = 'pending'
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT 8"
        );

        View::render('admin/dashboard', [
            'page_title' => t('page.admin_dashboard_title'),
            'stats' => [
                'entries' => (int) ($entryCountRow['count'] ?? 0),
                'projects' => (int) ($projectCountRow['count'] ?? 0),
                'pending_reflections' => (int) ($pendingCountRow['count'] ?? 0),
            ],
            'latest_entries' => $latestEntries,
            'pending_reflections' => $pendingReflections,
            'balance_7' => BalanceService::rangeSummary(7),
            'balance_30' => BalanceService::rangeSummary(30),
            'footprint_30' => FootprintService::totalsForPeriod(30),
            'routine_footprint_30' => RoutineService::totalsForPeriod(30),
            'carbon_per_hour_monthly' => FootprintService::monthlyMedianCarbonPerHour(null, true),
        ]);
    }
}
