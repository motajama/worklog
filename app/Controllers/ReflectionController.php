<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\View;

class ReflectionController
{
    public static function index(): void
    {
        $reflections = DB::selectAll(
            "SELECT
                r.*,
                e.title AS entry_title,
                e.entry_date,
                e.entry_type
             FROM reflections r
             INNER JOIN entries e ON e.id = r.entry_id
             ORDER BY
                CASE r.status
                    WHEN 'pending' THEN 0
                    WHEN 'approved' THEN 1
                    ELSE 2
                END,
                r.created_at DESC,
                r.id DESC"
        );

        View::render('admin/reflections/index', [
            'page_title' => t('page.admin_reflections_title'),
            'reflections' => $reflections,
        ]);
    }

    public static function store(): void
    {
        $entryId = (int) ($_POST['entry_id'] ?? 0);
        $authorName = trim((string) ($_POST['author_name'] ?? ''));
        $authorEmail = trim((string) ($_POST['author_email'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $isAnonymous = isset($_POST['is_anonymous']) ? 1 : 0;

        if ($entryId <= 0) {
            flash('error', 'Chybí entry pro reflexi.');
            redirect(route_url('home'));
        }

        $entry = DB::selectOne(
            'SELECT id, entry_type, visibility, allow_reflections
             FROM entries
             WHERE id = :id
             LIMIT 1',
            ['id' => $entryId]
        );

        if (!$entry) {
            flash('error', 'Entry pro reflexi neexistuje.');
            redirect(route_url('home'));
        }

        if ($entry['visibility'] !== 'public' || $entry['entry_type'] !== 'fuckup' || (int) $entry['allow_reflections'] !== 1) {
            flash('error', 'K tomuhle entry teď nejde vložit reflexe.');
            redirect(route_url('home'));
        }

        if ($body === '') {
            flash('error', 'Reflexe nesmí být prázdná.');
            redirect(route_url('home') . '#entry-' . $entryId);
        }

        DB::execute(
            'INSERT INTO reflections (
                entry_id, author_name, author_email, body, locale, status, is_anonymous
            ) VALUES (
                :entry_id, :author_name, :author_email, :body, :locale, :status, :is_anonymous
            )',
            [
                'entry_id' => $entryId,
                'author_name' => $authorName !== '' ? $authorName : null,
                'author_email' => $authorEmail !== '' ? $authorEmail : null,
                'body' => $body,
                'locale' => current_locale(),
                'status' => 'pending',
                'is_anonymous' => $isAnonymous,
            ]
        );

        flash('success', 'Reflexe byla odeslána ke schválení.');
        redirect(route_url('home') . '#entry-' . $entryId);
    }

    public static function approve(array $params): void
    {
        $reflectionId = (int) ($params['id'] ?? 0);
        $reflection = self::findReflection($reflectionId);

        if (!$reflection) {
            flash('error', 'Reflexe nebyla nalezena.');
            redirect(route_url('admin.reflections.index'));
        }

        DB::execute(
            "UPDATE reflections
             SET status = 'approved', reviewed_at = CURRENT_TIMESTAMP
             WHERE id = :id",
            ['id' => $reflectionId]
        );

        flash('success', 'Reflexe byla schválena.');
        redirect(route_url('admin.reflections.index'));
    }

    public static function reject(array $params): void
    {
        $reflectionId = (int) ($params['id'] ?? 0);
        $reflection = self::findReflection($reflectionId);

        if (!$reflection) {
            flash('error', 'Reflexe nebyla nalezena.');
            redirect(route_url('admin.reflections.index'));
        }

        DB::execute(
            "UPDATE reflections
             SET status = 'rejected', reviewed_at = CURRENT_TIMESTAMP
             WHERE id = :id",
            ['id' => $reflectionId]
        );

        flash('success', 'Reflexe byla zamítnuta.');
        redirect(route_url('admin.reflections.index'));
    }

    protected static function findReflection(int $id): ?array
    {
        return DB::selectOne(
            'SELECT * FROM reflections WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }
}
