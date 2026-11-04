<?php

namespace App\Helpers;

use Illuminate\Support\HtmlString;

/**
 * 4 Nov 2026 — tampilan "Description" form/quiz di halaman peserta supaya tersusun rapi.
 *
 * Admin cukup menulis di kolom Description seperti biasa:
 *   - Enter            → baris baru
 *   - baris kosong     → paragraf baru
 *   - baris diawali "- ", "* ", "• " atau "1. " → daftar poin
 *   - teks "<br>" yang terlanjur diketik juga dianggap baris baru
 * Semua teks tetap di-escape (HTML dari admin TIDAK dijalankan), jadi aman.
 */
class FormDescription
{
    public static function toHtml(?string $text): HtmlString
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $text));
        // "<br>" yang diikuti Enter dihitung satu baris baru saja (bukan paragraf baru)
        $text = preg_replace('/<br\s*\/?>[ \t]*\n?/i', "\n", $text) ?? $text;

        if ($text === '') {
            return new HtmlString('');
        }

        $html = '';
        $paragraf = [];
        $daftar = null; // ['tag' => 'ul'|'ol', 'items' => []]

        $tutupParagraf = function () use (&$html, &$paragraf) {
            if ($paragraf) {
                $html .= '<p>'.implode('<br>', array_map('e', $paragraf)).'</p>';
                $paragraf = [];
            }
        };
        $tutupDaftar = function () use (&$html, &$daftar) {
            if ($daftar) {
                $html .= '<'.$daftar['tag'].'>'.implode('', array_map(fn ($i) => '<li>'.e($i).'</li>', $daftar['items'])).'</'.$daftar['tag'].'>';
                $daftar = null;
            }
        };

        foreach (explode("\n", $text) as $baris) {
            $baris = trim($baris);

            if ($baris === '') {
                $tutupParagraf();
                $tutupDaftar();
                continue;
            }

            if (preg_match('/^(?:[-*•]|\d+[.)])\s+(.+)$/u', $baris, $m)) {
                $tag = preg_match('/^\d/', $baris) ? 'ol' : 'ul';
                $tutupParagraf();
                if ($daftar && $daftar['tag'] !== $tag) {
                    $tutupDaftar();
                }
                $daftar ??= ['tag' => $tag, 'items' => []];
                $daftar['items'][] = $m[1];
                continue;
            }

            $tutupDaftar();
            $paragraf[] = $baris;
        }

        $tutupParagraf();
        $tutupDaftar();

        return new HtmlString($html);
    }

    /** Teks pendek satu baris tampil di tengah; yang panjang / berbaris rata kiri supaya mudah dibaca. */
    public static function isPanjang(?string $text): bool
    {
        $text = (string) $text;

        return mb_strlen($text) > 160 || preg_match('/\n|<br\s*\/?>/i', $text) === 1;
    }
}
