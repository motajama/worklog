<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\View;
use App\Services\FootprintService;

class FootprintController
{
    public static function index(): void
    {
        $userId = (int) Auth::id();

        View::render('admin/footprint/index', [
            'page_title' => t('page.admin_footprint_title'),
            'factors' => FootprintService::factorsForUser($userId, false),
        ]);
    }

    public static function create(): void
    {
        View::render('admin/footprint/form', [
            'page_title' => t('page.admin_footprint_create_title'),
            'mode' => 'create',
            'factor' => self::emptyFactor(),
            'category_options' => FootprintService::categoryOptions(),
            'unit_options' => FootprintService::unitOptions(),
        ]);
    }

    public static function store(): void
    {
        $userId = (int) Auth::id();
        $data = self::validate($_POST);

        if ($data['errors']) {
            flash('error', implode(' ', $data['errors']));
            old_input($_POST);
            redirect(route_url('admin.footprint.create'));
        }

        DB::execute(
            'INSERT INTO footprint_factors (
                user_id, label, category, base_unit, factor_kg_per_unit, source_note,
                methodology_note, geography_code, active, editable_by_user, is_seed,
                valid_from, review_after
            ) VALUES (
                :user_id, :label, :category, :base_unit, :factor_kg_per_unit, :source_note,
                :methodology_note, :geography_code, :active, 1, 0,
                :valid_from, :review_after
            )',
            ['user_id' => $userId] + $data['values']
        );

        forget_old_input();
        flash('success', 'Footprint faktor byl vytvořen.');
        redirect(route_url('admin.footprint.index'));
    }

    public static function edit(array $params): void
    {
        $userId = (int) Auth::id();
        $factor = FootprintService::factorForUser((int) ($params['id'] ?? 0), $userId);

        if (!$factor) {
            flash('error', 'Footprint faktor nebyl nalezen.');
            redirect(route_url('admin.footprint.index'));
        }

        View::render('admin/footprint/form', [
            'page_title' => t('page.admin_footprint_edit_title'),
            'mode' => 'edit',
            'factor' => $factor,
            'category_options' => FootprintService::categoryOptions(),
            'unit_options' => FootprintService::unitOptions(),
        ]);
    }

    public static function update(array $params): void
    {
        $userId = (int) Auth::id();
        $factorId = (int) ($params['id'] ?? 0);
        $factor = FootprintService::factorForUser($factorId, $userId);

        if (!$factor) {
            flash('error', 'Footprint faktor nebyl nalezen.');
            redirect(route_url('admin.footprint.index'));
        }

        if ((int) ($factor['editable_by_user'] ?? 1) !== 1) {
            flash('error', 'Tento faktor není editovatelný.');
            redirect(route_url('admin.footprint.index'));
        }

        $data = self::validate($_POST);

        if ($data['errors']) {
            flash('error', implode(' ', $data['errors']));
            old_input($_POST);
            redirect(route_url('admin.footprint.edit', ['id' => $factorId]));
        }

        DB::execute(
            'UPDATE footprint_factors SET
                label = :label,
                category = :category,
                base_unit = :base_unit,
                factor_kg_per_unit = :factor_kg_per_unit,
                source_note = :source_note,
                methodology_note = :methodology_note,
                geography_code = :geography_code,
                active = :active,
                valid_from = :valid_from,
                review_after = :review_after,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND user_id = :user_id',
            ['id' => $factorId, 'user_id' => $userId] + $data['values']
        );

        forget_old_input();
        flash('success', 'Footprint faktor byl upraven.');
        redirect(route_url('admin.footprint.index'));
    }

    protected static function validate(array $input): array
    {
        $label = trim((string) ($input['label'] ?? ''));
        $category = trim((string) ($input['category'] ?? 'other'));
        $baseUnit = trim((string) ($input['base_unit'] ?? 'event'));
        $factorRaw = trim(str_replace(',', '.', (string) ($input['factor_kg_per_unit'] ?? '')));
        $sourceNote = trim((string) ($input['source_note'] ?? ''));
        $methodologyNote = trim((string) ($input['methodology_note'] ?? ''));
        $geographyCode = trim((string) ($input['geography_code'] ?? 'CZ'));
        $validFrom = trim((string) ($input['valid_from'] ?? ''));
        $reviewAfter = trim((string) ($input['review_after'] ?? ''));
        $active = isset($input['active']) ? 1 : 0;

        $errors = [];

        if ($label === '') {
            $errors[] = 'Název faktoru je povinný.';
        }

        if (!array_key_exists($category, FootprintService::categoryOptions())) {
            $errors[] = 'Neplatná kategorie faktoru.';
        }

        if (!array_key_exists($baseUnit, FootprintService::unitOptions())) {
            $errors[] = 'Neplatná jednotka faktoru.';
        }

        if ($factorRaw === '' || !is_numeric($factorRaw) || (float) $factorRaw < 0) {
            $errors[] = 'Faktor musí být nezáporné číslo.';
        }

        if ($validFrom !== '' && !self::isValidDate($validFrom)) {
            $errors[] = 'Valid from musí mít formát YYYY-MM-DD.';
        }

        if ($reviewAfter !== '' && !self::isValidDate($reviewAfter)) {
            $errors[] = 'Review after musí mít formát YYYY-MM-DD.';
        }

        return [
            'errors' => $errors,
            'values' => [
                'label' => $label,
                'category' => $category,
                'base_unit' => $baseUnit,
                'factor_kg_per_unit' => (float) $factorRaw,
                'source_note' => $sourceNote !== '' ? $sourceNote : null,
                'methodology_note' => $methodologyNote !== '' ? $methodologyNote : null,
                'geography_code' => $geographyCode !== '' ? $geographyCode : null,
                'active' => $active,
                'valid_from' => $validFrom !== '' ? $validFrom : date('Y-m-d'),
                'review_after' => $reviewAfter !== '' ? $reviewAfter : date('Y-m-d', strtotime('+1 year')),
            ],
        ];
    }

    protected static function emptyFactor(): array
    {
        return [
            'id' => null,
            'label' => '',
            'category' => 'other',
            'base_unit' => 'event',
            'factor_kg_per_unit' => '',
            'source_note' => '',
            'methodology_note' => '',
            'geography_code' => 'CZ',
            'active' => 1,
            'valid_from' => date('Y-m-d'),
            'review_after' => date('Y-m-d', strtotime('+1 year')),
        ];
    }

    protected static function isValidDate(string $date): bool
    {
        $parts = explode('-', $date);

        if (count($parts) !== 3) {
            return false;
        }

        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }
}
