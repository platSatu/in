<?php

namespace App\Helpers;

/**
 * Utilitas pembulatan angka uang yang dipakai BERSAMA oleh beberapa service
 * finansial (App\Services\CourseCredit\CourseCreditDebitService,
 * App\Services\TeacherHonor\TeacherHonorService, dst) -- sengaja
 * disatukan di sini supaya kebijakan "selalu bulatkan ke bawah" (hasil
 * diskusi Konversi Paket: sistem tidak boleh pernah "menciptakan" nilai
 * lebih dari yang sebenarnya) konsisten persis di semua tempat, tidak
 * ditulis ulang beda-beda.
 *
 * Sengaja TIDAK pakai ekstensi bcmath (tidak ada satupun pemakaian bcmath
 * di codebase ini & belum bisa dipastikan aktif di server produksi) --
 * epsilon kecil ditambahkan dulu untuk meredam representasi float yang
 * tidak presisi (mis. 2.0 yang sebenarnya tersimpan sebagai
 * 1.9999999999998 di memori) supaya tidak salah terpotong 1 angka lebih
 * rendah dari yang seharusnya.
 */
class MoneyMath
{
    public static function floorToScale(float $value, int $scale): float
    {
        $factor = 10 ** $scale;

        return floor(($value * $factor) + 1e-6) / $factor;
    }
}
