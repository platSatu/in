<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * File Aplikasi Kuliah (dokumen & foto formulir) -- 19 Oktober 2026.
 *
 * Disimpan PRIVAT di storage/app/private/{folder}, bukan public/, jadi tidak
 * bisa dibuka lewat URL langsung; selalu lewat route yang mengecek login &
 * kepemilikan. Nilai file_path di database TIDAK berubah formatnya
 * ("applications/documents/uuid.pdf"). File lama yang masih di public/
 * tetap terbaca (fallback) sampai semuanya selesai disalin ke folder privat.
 */
class ApplicationFiles
{
    public const DOCUMENTS = 'applications/documents';
    public const FORM_PHOTOS = 'applications/form-photos';

    private const INLINE_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif'];

    public static function store(UploadedFile $file, string $folder): string
    {
        $directory = storage_path('app/private/'.$folder);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return $folder.'/'.$filename;
    }

    /** Path absolut file, atau null kalau path tidak sah / file tidak ada. */
    public static function path(?string $relativePath): ?string
    {
        // Hanya nama file di dua folder ini; menolak "..", subfolder, atau path lain.
        if (! $relativePath || ! preg_match('#^applications/(documents|form-photos)/[A-Za-z0-9._-]+$#', $relativePath)) {
            return null;
        }

        foreach ([storage_path('app/private/'.$relativePath), public_path($relativePath)] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function delete(?string $relativePath): void
    {
        if (self::path($relativePath)) {
            @unlink(storage_path('app/private/'.$relativePath));
            @unlink(public_path($relativePath));
        }
    }

    /** PDF & gambar dibuka di browser (preview), selain itu diunduh. */
    public static function response(?string $relativePath, ?string $downloadName = null, bool $forceDownload = false): Response
    {
        $path = self::path($relativePath);
        abort_unless($path, 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $name = $downloadName ?: basename($path);
        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];

        if (! $forceDownload && in_array($extension, self::INLINE_EXTENSIONS, true)) {
            return response()->file($path, $headers + ['Content-Disposition' => 'inline; filename="'.addslashes($name).'"']);
        }

        return response()->download($path, $name, $headers);
    }
}
