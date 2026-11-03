<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === PILIHAN PAKET (harga per pilihan) ===
     * - form_questions.type + 'package': pertanyaan pilihan tunggal yang tiap pilihannya punya harga.
     *   Ditampilkan di step "Pilih Paket" SEBELUM pembayaran; tagihan = nominal tetap form + harga
     *   pilihan yang dipilih (dihitung di server, lihat App\Services\Quiz\PackagePricing).
     * - form_question_options.price: harga pilihan (nullable; hanya dipakai pertanyaan bertipe package).
     * - form_payments.package_option_ids: pilihan paket yang dibayar, dicocokkan lagi saat submit.
     * Form tanpa pertanyaan bertipe package tidak berubah sama sekali.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE form_questions MODIFY type ENUM('text','textarea','number','date','single_choice','multiple_choice','dropdown','major','file','exact_match','package') NOT NULL");

        Schema::table('form_question_options', function (Blueprint $table) {
            if (!Schema::hasColumn('form_question_options', 'price')) {
                $table->decimal('price', 15, 2)->nullable()->after('score');
            }
        });

        Schema::table('form_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('form_payments', 'package_option_ids')) {
                $table->json('package_option_ids')->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_payments', function (Blueprint $table) {
            if (Schema::hasColumn('form_payments', 'package_option_ids')) {
                $table->dropColumn('package_option_ids');
            }
        });

        Schema::table('form_question_options', function (Blueprint $table) {
            if (Schema::hasColumn('form_question_options', 'price')) {
                $table->dropColumn('price');
            }
        });

        DB::statement("ALTER TABLE form_questions MODIFY type ENUM('text','textarea','number','date','single_choice','multiple_choice','dropdown','major','file','exact_match') NOT NULL");
    }
};
