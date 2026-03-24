<?php

namespace App\Services;

use App\Core\DB;

class AppSettings
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $row = DB::selectOne(
            'SELECT setting_value
             FROM app_settings
             WHERE setting_key = :key
             LIMIT 1',
            ['key' => $key]
        );

        if (!$row) {
            return $default;
        }

        return $row['setting_value'];
    }

    public static function set(string $key, ?string $value): void
    {
        $existing = DB::selectOne(
            'SELECT id
             FROM app_settings
             WHERE setting_key = :key
             LIMIT 1',
            ['key' => $key]
        );

        if ($existing) {
            DB::execute(
                'UPDATE app_settings
                 SET setting_value = :value,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE setting_key = :key',
                [
                    'key' => $key,
                    'value' => $value,
                ]
            );

            return;
        }

        DB::execute(
            'INSERT INTO app_settings (setting_key, setting_value)
             VALUES (:key, :value)',
            [
                'key' => $key,
                'value' => $value,
            ]
        );
    }

    public static function setMany(array $items): void
    {
        foreach ($items as $key => $value) {
            self::set((string) $key, $value !== null ? (string) $value : null);
        }
    }

    public static function localeValue(string $baseKey, string $locale, string $default = ''): string
    {
        $value = self::get($baseKey . '_' . $locale);

        if ($value !== null && $value !== '') {
            return $value;
        }

        return $default;
    }
}
