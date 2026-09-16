<?php

namespace App\Services\CourseCredit;

use RuntimeException;

/**
 * Dilempar oleh CourseCreditDebitService::debit() kalau saldo credit student
 * ternyata tidak cukup untuk menutup jumlah yang mau dipotong -- dibuat jadi
 * exception class sendiri (bukan RuntimeException polos) supaya pemanggil
 * (nanti fitur Absensi/Konversi) bisa menangkapnya secara spesifik untuk
 * menampilkan pesan yang ramah ke user, dibedakan dari error lain.
 */
class InsufficientCourseCreditException extends RuntimeException
{
}
