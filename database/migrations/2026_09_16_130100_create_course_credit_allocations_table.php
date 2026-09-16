<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| === COURSE CREDIT ALLOCATIONS ===
| FASE 1 -- "Fondasi Pelacakan Asal Credit" (16 September 2026). Ini
| jawaban teknis untuk kebutuhan yang muncul dari 2 fitur yang MASIH tahap
| desain (Konversi/Upgrade Paket & Honor Pengajar): keduanya sama-sama
| butuh tahu "1 credit yang baru saja DIPOTONG (debit) itu, dulu didapat
| dari pembelian package yang mana, dan harganya berapa" -- supaya:
| (a) fitur Konversi bisa hitung nilai rupiah trade-in dengan tepat, dan
| (b) fitur Honor Pengajar bisa hitung persentase pengajar dikali harga
|     package ASLI yang dipakai siswa itu, bukan harga package sembarang.
|
| Kenapa perlu tabel PIVOT terpisah (bukan cukup 1 kolom
| course_package_purchase_id di course_credits, yang sudah ada tapi cuma
| bisa nunjuk ke 1 purchase): karena saldo credit itu 1 POOL BERSAMA per
| student (lihat docblock CourseCredit::currentBalanceFor()), jadi 1 kali
| pemotongan bisa saja "diambil" dari LEBIH DARI 1 pembelian sekaligus --
| misalnya batch pembelian tertua ternyata sisanya cuma 0.5 credit,
| sisanya 0.5 credit lagi harus diambil dari batch pembelian berikutnya.
| 1 baris debit di course_credits sekarang bisa "dipecah" jadi beberapa
| baris alokasi di sini, masing-masing menunjuk ke pembelian asal yang
| beda, dengan porsi & harga per-unit masing-masing.
|
| `unit_price` & `value` SENGAJA di-snapshot di sini (bukan dihitung ulang
| dari course_package_purchases.price_paid/credits_granted setiap saat) --
| hasil bagi price_paid/credits_granted bisa saja berulang desimal panjang,
| jadi disnapshot supaya angka yang dipakai utk Konversi/Honor Pengajar
| konsisten selamanya, tidak berubah kalau ada pembulatan berbeda di
| perhitungan lain nanti.
*/
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_credit_allocations')) {
            return;
        }

        Schema::create('course_credit_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Baris debit di course_credits yang "dipecah" alokasinya di
            // sini -- cascadeOnDelete() karena baris alokasi ini sepenuhnya
            // anak dari baris debit itu, tidak berarti apa-apa sendirian
            // kalau induknya dihapus.
            $table->foreignUuid('course_credit_id')
                ->constrained('course_credits')
                ->cascadeOnDelete();

            // Pembelian ASAL yang "dipotong" porsinya di sini -- restrictOnDelete()
            // konsisten dengan FK ke course_package_purchases lain di codebase
            // ini (lihat course_package_purchases.course_package_id), supaya
            // riwayat pembelian yang sudah pernah dipakai tidak bisa hilang
            // referensinya.
            $table->foreignUuid('course_package_purchase_id')
                ->constrained('course_package_purchases')
                ->restrictOnDelete();

            // Berapa credit yang diambil dari pembelian ini untuk debit
            // tersebut -- presisi sama dengan course_credits.debit/kredit.
            $table->decimal('amount', 8, 2);

            // Harga per 1 credit dari pembelian asal (price_paid / credits_granted
            // pembelian itu), di-snapshot saat alokasi dibuat -- presisi lebih
            // tinggi dari kolom uang lain di codebase ini (14,4) supaya hasil
            // bagi yang tidak bulat (mis. Rp 1.000.000 / 3 credit) tidak
            // langsung terpotong sebelum dikalikan `amount`.
            $table->decimal('unit_price', 14, 4);

            // amount * unit_price, disimpan eksplisit (bukan cuma dihitung on
            // the fly) supaya laporan Finance/Honor Pengajar tidak perlu
            // hitung ulang tiap saat -- presisi 2 desimal karena ini nilai
            // akhir dalam Rupiah.
            $table->decimal('value', 14, 2);

            $table->timestamps();

            $table->index(['course_credit_id'], 'cca_course_credit_idx');
            $table->index(['course_package_purchase_id'], 'cca_purchase_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_credit_allocations');
    }
};
