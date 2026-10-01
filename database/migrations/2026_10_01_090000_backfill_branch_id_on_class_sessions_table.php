<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Perbaikan data (1 Oktober 2026): sebelum ini pengajuan kelas & potong
 * credit manual menyimpan branch_id dari `$student->branch` -- relasi yang
 * TIDAK ADA di App\Models\Student (namanya companyBranch()), jadi branch_id
 * selalu kosong. Akibatnya sesi tidak pernah masuk rekap Honor Pengajar
 * (rekap difilter per cabang). Kode sudah diperbaiki; migration ini mengisi
 * branch_id sesi lama dari cabang siswanya.
 *
 * Hanya mengisi yang kosong, aman dijalankan ulang. Periode honor yang SUDAH
 * ditutup tidak dihitung ulang (rekapnya sudah dikunci).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('class_sessions')
            ->join('students', 'students.id', '=', 'class_sessions.student_id')
            ->whereNull('class_sessions.branch_id')
            ->whereNotNull('students.branch_id')
            ->update(['class_sessions.branch_id' => DB::raw('students.branch_id')]);
    }

    public function down(): void
    {
        // Sengaja dibiarkan: branch_id yang sudah benar tidak perlu dikosongkan lagi.
    }
};
