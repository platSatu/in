<?php

namespace App\Helpers;

/**
 * Format nilai mentah dari kolom old_values/new_values (App\Models\ActivityLog)
 * supaya enak dibaca di halaman detail (activity-log/_detail-content.blade.php,
 * activity-log/_value-list.blade.php) — dipisah dari ActivityLogger karena ini
 * murni soal TAMPILAN, bukan soal menulis/mencatat log-nya.
 */
class ActivityLogFormatter
{
    public static function displayValue(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (is_string($value)) {
            return $value === '' ? '(kosong)' : $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '(kosong)';
        }

        return (string) $value;
    }
}
