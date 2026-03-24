<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\View;

class ProjectController
{
    public static function index(): void
    {
        $projects = DB::selectAll(
            'SELECT *
             FROM projects
             ORDER BY status = "active" DESC, sort_order ASC, created_at DESC'
        );

        View::render('admin/projects/index', [
            'page_title' => t('page.admin_projects_title'),
            'projects' => $projects,
        ]);
    }

    public static function create(): void
    {
        View::render('admin/projects/form', [
            'page_title' => t('page.admin_project_create_title'),
            'mode' => 'create',
            'project' => self::emptyProject(),
            'visibility_options' => self::visibilityOptions(),
            'status_options' => self::statusOptions(),
            'locale_options' => self::localeOptions(),
        ]);
    }

    public static function store(): void
    {
        $data = self::validate($_POST);

        if ($data['errors']) {
            flash('error', implode(' ', $data['errors']));
            old_input($_POST);
            redirect(route_url('admin.projects.create'));
        }

        DB::execute(
            'INSERT INTO projects (
                title, slug, description, public_label, visibility, status, locale, is_featured, sort_order
            ) VALUES (
                :title, :slug, :description, :public_label, :visibility, :status, :locale, :is_featured, :sort_order
            )',
            [
                'title' => $data['values']['title'],
                'slug' => $data['values']['slug'],
                'description' => $data['values']['description'],
                'public_label' => $data['values']['public_label'],
                'visibility' => $data['values']['visibility'],
                'status' => $data['values']['status'],
                'locale' => $data['values']['locale'],
                'is_featured' => $data['values']['is_featured'],
                'sort_order' => $data['values']['sort_order'],
            ]
        );

        forget_old_input();
        flash('success', 'Projekt byl vytvořen.');
        redirect(route_url('admin.projects.index'));
    }

    public static function edit(array $params): void
    {
        $project = self::findProject((int) ($params['id'] ?? 0));

        if (!$project) {
            flash('error', 'Projekt nebyl nalezen.');
            redirect(route_url('admin.projects.index'));
        }

        View::render('admin/projects/form', [
            'page_title' => t('page.admin_project_edit_title'),
            'mode' => 'edit',
            'project' => $project,
            'visibility_options' => self::visibilityOptions(),
            'status_options' => self::statusOptions(),
            'locale_options' => self::localeOptions(),
        ]);
    }

    public static function update(array $params): void
    {
        $projectId = (int) ($params['id'] ?? 0);
        $project = self::findProject($projectId);

        if (!$project) {
            flash('error', 'Projekt nebyl nalezen.');
            redirect(route_url('admin.projects.index'));
        }

        $data = self::validate($_POST, $projectId);

        if ($data['errors']) {
            flash('error', implode(' ', $data['errors']));
            old_input($_POST);
            redirect(route_url('admin.projects.edit', ['id' => $projectId]));
        }

        DB::execute(
            'UPDATE projects SET
                title = :title,
                slug = :slug,
                description = :description,
                public_label = :public_label,
                visibility = :visibility,
                status = :status,
                locale = :locale,
                is_featured = :is_featured,
                sort_order = :sort_order,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = :id',
            [
                'id' => $projectId,
                'title' => $data['values']['title'],
                'slug' => $data['values']['slug'],
                'description' => $data['values']['description'],
                'public_label' => $data['values']['public_label'],
                'visibility' => $data['values']['visibility'],
                'status' => $data['values']['status'],
                'locale' => $data['values']['locale'],
                'is_featured' => $data['values']['is_featured'],
                'sort_order' => $data['values']['sort_order'],
            ]
        );

        forget_old_input();
        flash('success', 'Projekt byl upraven.');
        redirect(route_url('admin.projects.index'));
    }

    protected static function validate(array $input, ?int $ignoreId = null): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $slugInput = trim((string) ($input['slug'] ?? ''));
        $slug = self::slugify($slugInput !== '' ? $slugInput : $title);
        $description = trim((string) ($input['description'] ?? ''));
        $publicLabel = trim((string) ($input['public_label'] ?? ''));
        $visibility = trim((string) ($input['visibility'] ?? 'private'));
        $status = trim((string) ($input['status'] ?? 'active'));
        $locale = trim((string) ($input['locale'] ?? 'cs'));
        $isFeatured = isset($input['is_featured']) ? 1 : 0;
        $sortOrder = (int) ($input['sort_order'] ?? 0);

        $errors = [];

        if ($title === '') {
            $errors[] = 'Titulek projektu je povinný.';
        }

        if ($slug === '') {
            $errors[] = 'Slug projektu nesmí být prázdný.';
        }

        if (!array_key_exists($visibility, self::visibilityOptions())) {
            $errors[] = 'Neplatná viditelnost projektu.';
        }

        if (!array_key_exists($status, self::statusOptions())) {
            $errors[] = 'Neplatný stav projektu.';
        }

        if (!array_key_exists($locale, self::localeOptions())) {
            $errors[] = 'Neplatná jazyková varianta projektu.';
        }

        $existing = DB::selectOne(
            'SELECT id FROM projects WHERE slug = :slug LIMIT 1',
            ['slug' => $slug]
        );

        if ($existing && (int) $existing['id'] !== (int) $ignoreId) {
            $errors[] = 'Slug už existuje. Zvol jiný.';
        }

        return [
            'errors' => $errors,
            'values' => [
                'title' => $title,
                'slug' => $slug,
                'description' => $description !== '' ? $description : null,
                'public_label' => $publicLabel !== '' ? $publicLabel : null,
                'visibility' => $visibility,
                'status' => $status,
                'locale' => $locale,
                'is_featured' => $isFeatured,
                'sort_order' => $sortOrder,
            ],
        ];
    }

    protected static function findProject(int $id): ?array
    {
        return DB::selectOne(
            'SELECT * FROM projects WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    protected static function emptyProject(): array
    {
        return [
            'id' => null,
            'title' => '',
            'slug' => '',
            'description' => '',
            'public_label' => '',
            'visibility' => 'private',
            'status' => 'active',
            'locale' => 'cs',
            'is_featured' => 0,
            'sort_order' => 0,
        ];
    }

    protected static function visibilityOptions(): array
    {
        return [
            'private' => 'private',
            'public' => 'public',
            'masked' => 'masked',
        ];
    }

    protected static function statusOptions(): array
    {
        return [
            'active' => 'active',
            'paused' => 'paused',
            'archived' => 'archived',
            'completed' => 'completed',
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
