<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| === TEACHER COMMISSION RATES ===
| FASE 3 (16 September 2026) -- persentase komisi 1 pengajar, BISA
| BERBEDA-BEDA tiap pengajar (requirement eksplisit owner: "tidak flat, tp
| sesuai harga package yang berbeda-beda"). 1 baris = rate TERKINI 1
| pengajar -- kalau admin ubah persentase seorang pengajar, baris ini
| di-UPDATE di tempat (bukan bikin baris baru), TAPI setiap kali honor
| dihitung (lihat TeacherHonorService & tabel teacher_honors), persentase
| yang dipakai SELALU disnapshot ke baris teacher_honors saat itu juga --
| jadi perubahan rate di sini TIDAK PERNAH mengubah honor yang sudah
| terhitung sebelumnya secara retroaktif.
|
| `teacher_user_id` SENGAJA tanpa foreign key constraint (char(36) + unique
| index) -- mengikuti pola yang sama sudah dipakai student_id di beberapa
| tabel lain di codebase ini (course_package_purchases, course_credits,
| class_sessions).
*/
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_commission_rates')) {
            return;
        }

        Schema::create('teacher_commission_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('teacher_user_id', 36)->unique();

            // Persen (mis. 30.00 = 30%) -- lihat TeacherCommissionRate::rateFor()
            // untuk kebijakan default kalau pengajar belum diatur ratenya sama
            // sekali (sengaja 0, BUKAN angka tebakan, supaya admin sadar harus
            // set dulu sebelum pengajar itu bisa dapat honor).
            $table->decimal('percentage', 5, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_commission_rates');
    }
};
