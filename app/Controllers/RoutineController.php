<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\View;
use App\Services\FootprintService;
use App\Services\RoutineService;
use Throwable;

class RoutineController
{
    public static function index(): void
    {
        $userId = (int) Auth::id();
        $routines = RoutineService::routinesForUser($userId);

        foreach ($routines as &$routine) {
            $routine['items'] = RoutineService::itemsForRoutine((int) $routine['id']);
            $routine['per_occurrence'] = RoutineService::perOccurrenceTotals((int) $routine['id']);
        }
        unset($routine);

        View::render('admin/routines/index', [
            'page_title' => t('page.admin_routines_title'),
            'routines' => $routines,
        ]);
    }

    public static function create(): void
    {
        $userId = (int) Auth::id();

        View::render('admin/routines/form', [
            'page_title' => t('page.admin_routine_create_title'),
            'mode' => 'create',
            'routine' => self::emptyRoutine(),
            'routine_items' => [],
            'footprint_factors' => FootprintService::factorsForUser($userId, true),
        ]);
    }

    public static function store(): void
    {
        $userId = (int) Auth::id();
        $routine = RoutineService::validateRoutine($_POST);
        $items = RoutineService::validateItems($_POST, $userId);
        $errors = array_merge($routine['errors'], $items['errors']);

        if ($errors) {
            flash('error', implode(' ', $errors));
            old_input($_POST);
            redirect(route_url('admin.routines.create'));
        }

        try {
            DB::beginTransaction();
            DB::execute(
                'INSERT INTO routines (
                    user_id, label, description, occurrences_per_week, start_date, end_date, active
                ) VALUES (
                    :user_id, :label, :description, :occurrences_per_week, :start_date, :end_date, :active
                )',
                ['user_id' => $userId] + $routine['values']
            );

            RoutineService::saveItemsForRoutine((int) DB::lastInsertId(), $items['items']);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            flash('error', 'Routine could not be saved: ' . $e->getMessage());
            old_input($_POST);
            redirect(route_url('admin.routines.create'));
        }

        forget_old_input();
        flash('success', 'Routine was created.');
        redirect(route_url('admin.routines.index'));
    }

    public static function edit(array $params): void
    {
        $userId = (int) Auth::id();
        $routine = RoutineService::routineForUser((int) ($params['id'] ?? 0), $userId);

        if (!$routine) {
            flash('error', 'Routine was not found.');
            redirect(route_url('admin.routines.index'));
        }

        View::render('admin/routines/form', [
            'page_title' => t('page.admin_routine_edit_title'),
            'mode' => 'edit',
            'routine' => $routine,
            'routine_items' => RoutineService::itemsForRoutine((int) $routine['id']),
            'footprint_factors' => FootprintService::factorsForUser($userId, true),
        ]);
    }

    public static function update(array $params): void
    {
        $userId = (int) Auth::id();
        $routineId = (int) ($params['id'] ?? 0);
        $existing = RoutineService::routineForUser($routineId, $userId);

        if (!$existing) {
            flash('error', 'Routine was not found.');
            redirect(route_url('admin.routines.index'));
        }

        $routine = RoutineService::validateRoutine($_POST);
        $items = RoutineService::validateItems($_POST, $userId);
        $errors = array_merge($routine['errors'], $items['errors']);

        if ($errors) {
            flash('error', implode(' ', $errors));
            old_input($_POST);
            redirect(route_url('admin.routines.edit', ['id' => $routineId]));
        }

        try {
            DB::beginTransaction();
            DB::execute(
                'UPDATE routines SET
                    label = :label,
                    description = :description,
                    occurrences_per_week = :occurrences_per_week,
                    start_date = :start_date,
                    end_date = :end_date,
                    active = :active,
                    updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND user_id = :user_id',
                ['id' => $routineId, 'user_id' => $userId] + $routine['values']
            );

            RoutineService::saveItemsForRoutine($routineId, $items['items']);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            flash('error', 'Routine could not be saved: ' . $e->getMessage());
            old_input($_POST);
            redirect(route_url('admin.routines.edit', ['id' => $routineId]));
        }

        forget_old_input();
        flash('success', 'Routine was updated.');
        redirect(route_url('admin.routines.index'));
    }

    protected static function emptyRoutine(): array
    {
        return [
            'id' => null,
            'label' => '',
            'description' => '',
            'occurrences_per_week' => '5',
            'start_date' => date('Y-m-d'),
            'end_date' => '',
            'active' => 1,
        ];
    }
}
