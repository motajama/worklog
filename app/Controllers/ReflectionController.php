<?php

namespace App\Controllers;

use App\Core\DB;

class ReflectionController
{
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
}
