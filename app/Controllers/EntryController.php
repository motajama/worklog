<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\Auth;
use App\Core\View;
use App\Services\FootprintService;
use Throwable;

class EntryController
{
    public static function index(): void
    {
        $entries = DB::selectAll(
            'SELECT
                e.*,
                c.name AS category_name,
                c.kind AS category_kind,
                p.title AS project_title
             FROM entries e
             INNER JOIN categories c ON c.id = e.category_id
             LEFT JOIN projects p ON p.id = e.project_id
             ORDER BY e.entry_date DESC, e.created_at DESC, e.id DESC'
        );

        View::render('admin/entries/index', [
            'page_title' => t('page.admin_entries_title'),
            'entries' => $entries,
        ]);
    }

    public static function create(): void
    {
        $userId = (int) Auth::id();

        View::render('admin/entries/form', [
            'page_title' => t('page.admin_entry_create_title'),
            'mode' => 'create',
            'entry' => self::emptyEntry(),
            'footprint_items' => [],
            'footprint_factors' => FootprintService::factorsForUser($userId, true),
            'type_options' => self::typeOptions(),
            'visibility_options' => self::visibilityOptions(),
            'locale_options' => self::localeOptions(),
            'categories' => self::categories(),
            'projects' => self::projects(),
        ]);
    }

    public static function store(array $params = []): void
    {
        $userId = (int) Auth::id();
        $data = self::validate($_POST);
        $footprint = FootprintService::validateItems($_POST, $userId);
        $data['errors'] = array_merge($data['errors'], $footprint['errors']);

        if ($data['errors']) {
            flash('error', implode(' ', $data['errors']));
            old_input($_POST);
            redirect(route_url('admin.entries.create'));
        }

        try {
            DB::beginTransaction();

            DB::execute(
                'INSERT INTO entries (
                    entry_date, slug, entry_type, title, body, public_text, private_notes, minutes,
                    category_id, project_id, visibility, locale, is_invisible_work,
                    workload_override, recovery_override,
                    copsoq_quantitative_demands, copsoq_work_pace, copsoq_cognitive_demands, copsoq_low_control,
                    nfr_exhausted, nfr_detach_difficulty, nfr_need_long_recovery, nfr_overload,
                    recovery_detachment, recovery_relaxation, recovery_mastery, recovery_control,
                    what_happened, why_it_matters, my_take, next_time,
                    allow_reflections
                ) VALUES (
                    :entry_date, :slug, :entry_type, :title, :body, :public_text, :private_notes, :minutes,
                    :category_id, :project_id, :visibility, :locale, :is_invisible_work,
                    :workload_override, :recovery_override,
                    :copsoq_quantitative_demands, :copsoq_work_pace, :copsoq_cognitive_demands, :copsoq_low_control,
                    :nfr_exhausted, :nfr_detach_difficulty, :nfr_need_long_recovery, :nfr_overload,
                    :recovery_detachment, :recovery_relaxation, :recovery_mastery, :recovery_control,
                    :what_happened, :why_it_matters, :my_take, :next_time,
                    :allow_reflections
                )',
                $data['values']
            );

            $entryId = (int) DB::lastInsertId();
            FootprintService::saveItemsForEntry($entryId, $footprint['items'], $footprint['status']);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            flash('error', 'Entry se nepodařilo uložit: ' . $e->getMessage());
            old_input($_POST);
            redirect(route_url('admin.entries.create'));
        }

        forget_old_input();
        flash('success', 'Entry byl vytvořen.');
        redirect(route_url('admin.entries.index'));
    }

    public static function edit(array $params): void
    {
        $entry = self::findEntry((int) ($params['id'] ?? 0));

        if (!$entry) {
            flash('error', 'Entry nebyl nalezen.');
            redirect(route_url('admin.entries.index'));
        }

        $userId = (int) Auth::id();
        View::render('admin/entries/form', [
            'page_title' => t('page.admin_entry_edit_title'),
            'mode' => 'edit',
            'entry' => $entry,
            'footprint_items' => FootprintService::itemsForEntry((int) $entry['id']),
            'footprint_factors' => FootprintService::factorsForUser($userId, true),
            'type_options' => self::typeOptions(),
            'visibility_options' => self::visibilityOptions(),
            'locale_options' => self::localeOptions(),
            'categories' => self::categories(),
            'projects' => self::projects(),
        ]);
    }

    public static function update(array $params): void
    {
        $entryId = (int) ($params['id'] ?? 0);
        $entry = self::findEntry($entryId);

        if (!$entry) {
            flash('error', 'Entry nebyl nalezen.');
            redirect(route_url('admin.entries.index'));
        }

        $userId = (int) Auth::id();
        $data = self::validate($_POST, $entryId);
        $footprint = FootprintService::validateItems($_POST, $userId);
        $data['errors'] = array_merge($data['errors'], $footprint['errors']);

        if ($data['errors']) {
            flash('error', implode(' ', $data['errors']));
            old_input($_POST);
            redirect(route_url('admin.entries.edit', ['id' => $entryId]));
        }

        $values = $data['values'];
        $values['id'] = $entryId;

        try {
            DB::beginTransaction();

            DB::execute(
                'UPDATE entries SET
                    entry_date = :entry_date,
                    slug = :slug,
                    entry_type = :entry_type,
                    title = :title,
                    body = :body,
                    public_text = :public_text,
                    private_notes = :private_notes,
                    minutes = :minutes,
                    category_id = :category_id,
                    project_id = :project_id,
                    visibility = :visibility,
                    locale = :locale,
                    is_invisible_work = :is_invisible_work,
                    workload_override = :workload_override,
                    recovery_override = :recovery_override,
                    copsoq_quantitative_demands = :copsoq_quantitative_demands,
                    copsoq_work_pace = :copsoq_work_pace,
                    copsoq_cognitive_demands = :copsoq_cognitive_demands,
                    copsoq_low_control = :copsoq_low_control,
                    nfr_exhausted = :nfr_exhausted,
                    nfr_detach_difficulty = :nfr_detach_difficulty,
                    nfr_need_long_recovery = :nfr_need_long_recovery,
                    nfr_overload = :nfr_overload,
                    recovery_detachment = :recovery_detachment,
                    recovery_relaxation = :recovery_relaxation,
                    recovery_mastery = :recovery_mastery,
                    recovery_control = :recovery_control,
                    what_happened = :what_happened,
                    why_it_matters = :why_it_matters,
                    my_take = :my_take,
                    next_time = :next_time,
                    allow_reflections = :allow_reflections,
                    updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                $values
            );

            FootprintService::saveItemsForEntry($entryId, $footprint['items'], $footprint['status']);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            flash('error', 'Entry se nepodařilo uložit: ' . $e->getMessage());
            old_input($_POST);
            redirect(route_url('admin.entries.edit', ['id' => $entryId]));
        }

        forget_old_input();
        flash('success', 'Entry byl upraven.');
        redirect(route_url('admin.entries.index'));
    }

    public static function delete(array $params): void
    {
        $entryId = (int) ($params['id'] ?? 0);
        $entry = self::findEntry($entryId);

        if (!$entry) {
            flash('error', 'Entry nebyl nalezen.');
            redirect(route_url('admin.entries.index'));
        }

        DB::execute(
            'DELETE FROM entries WHERE id = :id',
            ['id' => $entryId]
        );

        flash('success', 'Entry byl smazán.');
        redirect(route_url('admin.entries.index'));
    }

    protected static function validate(array $input, ?int $ignoreId = null): array
    {
        $entryDate = trim((string) ($input['entry_date'] ?? ''));
        $entryType = trim((string) ($input['entry_type'] ?? 'achievement'));
        $title = trim((string) ($input['title'] ?? ''));
        $slugInput = trim((string) ($input['slug'] ?? ''));
        $slug = $slugInput !== '' ? self::slugify($slugInput) : ($title !== '' ? self::slugify($title) : null);

        $body = trim((string) ($input['body'] ?? ''));
        $publicText = trim((string) ($input['public_text'] ?? ''));
        $privateNotes = trim((string) ($input['private_notes'] ?? ''));

        $minutes = (int) ($input['minutes'] ?? 0);
        $categoryId = (int) ($input['category_id'] ?? 0);

        $projectRaw = trim((string) ($input['project_id'] ?? ''));
        $projectId = $projectRaw !== '' ? (int) $projectRaw : null;

        $visibility = trim((string) ($input['visibility'] ?? 'private'));
        $locale = trim((string) ($input['locale'] ?? 'cs'));
        $isInvisibleWork = isset($input['is_invisible_work']) ? 1 : 0;

        $workloadOverrideRaw = trim((string) ($input['workload_override'] ?? ''));
        $recoveryOverrideRaw = trim((string) ($input['recovery_override'] ?? ''));

        $whatHappened = trim((string) ($input['what_happened'] ?? ''));
        $whyItMatters = trim((string) ($input['why_it_matters'] ?? ''));
        $myTake = trim((string) ($input['my_take'] ?? ''));
        $nextTime = trim((string) ($input['next_time'] ?? ''));

        $allowReflections = isset($input['allow_reflections']) ? 1 : 0;

        $errors = [];

        if ($entryDate === '') {
            $errors[] = 'Datum je povinné.';
        } elseif (!self::isValidDate($entryDate)) {
            $errors[] = 'Datum nemá platný formát YYYY-MM-DD.';
        }

        if (!array_key_exists($entryType, self::typeOptions())) {
            $errors[] = 'Neplatný typ entry.';
        }

        if ($body === '') {
            $errors[] = 'Text entry je povinný.';
        }

        if ($minutes < 0) {
            $errors[] = 'Počet minut nesmí být záporný.';
        }

        if (!array_key_exists($visibility, self::visibilityOptions())) {
            $errors[] = 'Neplatná viditelnost entry.';
        }

        if (!array_key_exists($locale, self::localeOptions())) {
            $errors[] = 'Neplatná jazyková varianta.';
        }

        $category = null;
        if ($categoryId <= 0) {
            $errors[] = 'Kategorie je povinná.';
        } else {
            $category = DB::selectOne(
                'SELECT id, kind FROM categories WHERE id = :id LIMIT 1',
                ['id' => $categoryId]
            );

            if (!$category) {
                $errors[] = 'Kategorie neexistuje.';
            }
        }

        if ($projectId !== null) {
            $project = DB::selectOne(
                'SELECT id FROM projects WHERE id = :id LIMIT 1',
                ['id' => $projectId]
            );

            if (!$project) {
                $errors[] = 'Zvolený projekt neexistuje.';
            }
        }

        if ($category) {
            $categoryKind = $category['kind'];

            if ($entryType === 'regen' && $categoryKind !== 'recovery') {
                $errors[] = 'Regen musí mít recovery kategorii.';
            }

            if (in_array($entryType, ['achievement', 'fuckup', 'repair'], true) && $categoryKind !== 'work') {
                $errors[] = 'Achievement, fuckup a repair musí mít work kategorii.';
            }
        }

        if ($slug !== null && $slug !== '') {
            $existing = DB::selectOne(
                'SELECT id FROM entries WHERE slug = :slug LIMIT 1',
                ['slug' => $slug]
            );

            if ($existing && (int) $existing['id'] !== (int) $ignoreId) {
                $errors[] = 'Slug už existuje. Zvol jiný.';
            }
        } else {
            $slug = null;
        }

        $workloadOverride = null;
        if ($workloadOverrideRaw !== '') {
            if (!is_numeric($workloadOverrideRaw)) {
                $errors[] = 'Workload override musí být číslo.';
            } else {
                $workloadOverride = (float) $workloadOverrideRaw;
            }
        }

        $recoveryOverride = null;
        if ($recoveryOverrideRaw !== '') {
            if (!is_numeric($recoveryOverrideRaw)) {
                $errors[] = 'Recovery override musí být číslo.';
            } else {
                $recoveryOverride = (float) $recoveryOverrideRaw;
            }
        }

        if ($entryType !== 'fuckup') {
            $whatHappened = null;
            $whyItMatters = null;
            $myTake = null;
            $nextTime = null;
        } else {
            $whatHappened = $whatHappened !== '' ? $whatHappened : null;
            $whyItMatters = $whyItMatters !== '' ? $whyItMatters : null;
            $myTake = $myTake !== '' ? $myTake : null;
            $nextTime = $nextTime !== '' ? $nextTime : null;
        }

        $questionnaireValues = array_fill_keys(array_keys(self::questionnaireFieldLabels()), null);
        $simpleCheckFields = self::simpleCheckFields();
        $hasSimpleCheckInput = false;

        foreach (array_keys($simpleCheckFields) as $field) {
            if (array_key_exists($field, $input)) {
                $hasSimpleCheckInput = true;
                break;
            }
        }

        if ($hasSimpleCheckInput) {
            foreach ($simpleCheckFields as $field => $group) {
                $raw = trim((string) ($input[$field] ?? ''));

                if ($raw === '') {
                    continue;
                }

                if (!ctype_digit($raw)) {
                    $errors[] = $group['label'] . ': odpověď musí být 0–4.';
                    continue;
                }

                $value = (int) $raw;
                if ($value < 0 || $value > 4) {
                    $errors[] = $group['label'] . ': odpověď musí být 0–4.';
                    continue;
                }

                foreach ($group['fields'] as $mappedField) {
                    $questionnaireValues[$mappedField] = $value;
                }
            }
        } else {
            foreach (self::questionnaireFieldLabels() as $field => $label) {
                $raw = trim((string) ($input[$field] ?? ''));

                if ($raw === '') {
                    continue;
                }

                if (!ctype_digit($raw)) {
                    $errors[] = $label . ': odpověď musí být 0–4.';
                    continue;
                }

                $value = (int) $raw;
                if ($value < 0 || $value > 4) {
                    $errors[] = $label . ': odpověď musí být 0–4.';
                    continue;
                }

                $questionnaireValues[$field] = $value;
            }
        }

        return [
            'errors' => $errors,
            'values' => array_merge([
                'entry_date' => $entryDate,
                'slug' => $slug,
                'entry_type' => $entryType,
                'title' => $title !== '' ? $title : null,
                'body' => $body,
                'public_text' => $publicText !== '' ? $publicText : null,
                'private_notes' => $privateNotes !== '' ? $privateNotes : null,
                'minutes' => $minutes,
                'category_id' => $categoryId,
                'project_id' => $projectId,
                'visibility' => $visibility,
                'locale' => $locale,
                'is_invisible_work' => $isInvisibleWork,
                'workload_override' => $workloadOverride,
                'recovery_override' => $recoveryOverride,
                'what_happened' => $whatHappened,
                'why_it_matters' => $whyItMatters,
                'my_take' => $myTake,
                'next_time' => $nextTime,
                'allow_reflections' => $allowReflections,
            ], $questionnaireValues),
        ];
    }

    protected static function questionnaireFieldLabels(): array
    {
        return [
            'copsoq_quantitative_demands' => 'COPSOQ: množství práce',
            'copsoq_work_pace' => 'COPSOQ: pracovní tempo',
            'copsoq_cognitive_demands' => 'COPSOQ: kognitivní nároky',
            'copsoq_low_control' => 'COPSOQ: nízká kontrola',

            'nfr_exhausted' => 'NFR: vyčerpání',
            'nfr_detach_difficulty' => 'NFR: potíž se odpoutat',
            'nfr_need_long_recovery' => 'NFR: dlouhé zotavení',
            'nfr_overload' => 'NFR: přetížení',

            'recovery_detachment' => 'Recovery: odpoutání',
            'recovery_relaxation' => 'Recovery: relaxace',
            'recovery_mastery' => 'Recovery: mastery',
            'recovery_control' => 'Recovery: kontrola času',
        ];
    }

    protected static function simpleCheckFields(): array
    {
        return [
            'balance_workload' => [
                'label' => 'Pracovní tlak',
                'fields' => [
                    'copsoq_quantitative_demands',
                    'copsoq_work_pace',
                    'copsoq_cognitive_demands',
                    'copsoq_low_control',
                ],
            ],
            'balance_fatigue' => [
                'label' => 'Únava po práci',
                'fields' => [
                    'nfr_exhausted',
                    'nfr_detach_difficulty',
                    'nfr_need_long_recovery',
                    'nfr_overload',
                ],
            ],
            'balance_recovery' => [
                'label' => 'Kvalita obnovy',
                'fields' => [
                    'recovery_detachment',
                    'recovery_relaxation',
                    'recovery_mastery',
                    'recovery_control',
                ],
            ],
        ];
    }

    protected static function findEntry(int $id): ?array
    {
        return DB::selectOne(
            'SELECT * FROM entries WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    protected static function emptyEntry(): array
    {
        return [
            'id' => null,
            'entry_date' => date('Y-m-d'),
            'slug' => '',
            'entry_type' => 'achievement',
            'title' => '',
            'body' => '',
            'public_text' => '',
            'private_notes' => '',
            'minutes' => 0,
            'category_id' => '',
            'project_id' => '',
            'visibility' => 'private',
            'locale' => 'cs',
            'is_invisible_work' => 0,
            'workload_override' => '',
            'recovery_override' => '',

            'copsoq_quantitative_demands' => '',
            'copsoq_work_pace' => '',
            'copsoq_cognitive_demands' => '',
            'copsoq_low_control' => '',

            'nfr_exhausted' => '',
            'nfr_detach_difficulty' => '',
            'nfr_need_long_recovery' => '',
            'nfr_overload' => '',

            'recovery_detachment' => '',
            'recovery_relaxation' => '',
            'recovery_mastery' => '',
            'recovery_control' => '',

            'what_happened' => '',
            'why_it_matters' => '',
            'my_take' => '',
            'next_time' => '',
            'allow_reflections' => 0,
        ];
    }

    protected static function categories(): array
    {
        return DB::selectAll(
            'SELECT id, name, slug, kind
             FROM categories
             ORDER BY kind ASC, sort_order ASC, name ASC'
        );
    }

    protected static function projects(): array
    {
        return DB::selectAll(
            'SELECT id, title, visibility, status
             FROM projects
             ORDER BY status = "active" DESC, sort_order ASC, title ASC'
        );
    }

    protected static function typeOptions(): array
    {
        return [
            'achievement' => 'achievement',
            'fuckup' => 'fuckup',
            'regen' => 'regen',
            'repair' => 'repair',
        ];
    }

    protected static function visibilityOptions(): array
    {
        return [
            'private' => 'private',
            'public' => 'public',
            'internal' => 'internal',
        ];
    }

    protected static function localeOptions(): array
    {
        return [
            'cs' => 'cs',
            'en' => 'en',
            'bilingual' => 'bilingual',
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

    protected static function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));

        $map = [
            'á' => 'a', 'ä' => 'a', 'à' => 'a', 'â' => 'a',
            'č' => 'c', 'ć' => 'c',
            'ď' => 'd',
            'é' => 'e', 'ě' => 'e', 'ë' => 'e', 'è' => 'e', 'ê' => 'e',
            'í' => 'i', 'ï' => 'i', 'ì' => 'i', 'î' => 'i',
            'ľ' => 'l', 'ĺ' => 'l',
            'ň' => 'n',
            'ó' => 'o', 'ö' => 'o', 'ò' => 'o', 'ô' => 'o',
            'ř' => 'r',
            'š' => 's',
            'ť' => 't',
            'ú' => 'u', 'ů' => 'u', 'ü' => 'u', 'ù' => 'u', 'û' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ž' => 'z',
        ];

        $value = strtr($value, $map);
        $value = preg_replace('~[^a-z0-9]+~', '-', $value);
        $value = trim((string) $value, '-');

        return $value;
    }
}
