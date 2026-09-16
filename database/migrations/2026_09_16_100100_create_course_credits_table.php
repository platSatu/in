<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| === COURSE CREDITS (LEDGER) ===
| Ledger saldo credit kursus milik 1 student -- pola KOLOMNYA SENGAJA
| disamakan dengan tabel `deposits` yang sudah ada (debit/kredit/balance,
| tiap baris nyimpan running balance-nya sendiri setelah baris itu, lihat
| App\Models\Deposit & Deposit::currentBalanceFor()) supaya developer yang
| sudah familiar dengan pola Deposit langsung paham cara baca tabel ini.
|
| `kredit` = credit MASUK (dari klaim trial / beli package, lihat
| CoursePackagePurchase). `debit` = credit KELUAR (dipakai untuk booking/
| absensi kelas -- course_session_attendances, BELUM dibangun, disiapkan
| kolomnya saja supaya tidak perlu migration tambahan nanti begitu fitur
| absensi dibangun).
|
| `course_package_purchase_id` nullable + nullOnDelete() -- baris ledger
| yang berasal dari klaim/beli package akan diisi (jejak audit "credit ini
| datang dari purchase yang mana"), tapi kolomnya nullable supaya baris
| ledger lain di masa depan (mis. penyesuaian manual oleh admin, atau
| pemakaian credit lewat absensi) tidak dipaksa harus selalu terikat ke 1
| purchase tertentu.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_credits', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('student_id', 36)->index();

            $table->foreignUuid('course_package_purchase_id')
                ->nullable()
                ->constrained('course_package_purchases')
                ->nullOnDelete();

            $table->decimal('debit', 10, 2)->default(0);
            $table->decimal('kredit', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);

            $table->string('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_credits');
    }
};
