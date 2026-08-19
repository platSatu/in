<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Posisi step pembayaran di wizard publik, dipilih admin per form:
     * - before_questions: bayar dulu, baru placement test (perilaku sebelumnya).
     * - after_questions : isi placement test dulu, baru bayar di akhir sebelum submit.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->enum('payment_position', ['before_questions', 'after_questions'])
                ->default('before_questions')
                ->after('payment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('payment_position');
        });
    }
};
