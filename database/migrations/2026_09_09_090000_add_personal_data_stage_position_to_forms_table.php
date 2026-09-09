<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Posisi step "Data Pribadi" di wizard publik, dipilih admin per form
     * -- mengikuti pola PERSIS payment_position (lihat migration
     * add_payment_position_to_forms_table), supaya konsisten dengan cara
     * form ini sudah mengatur urutan step lain:
     * - first: Data Pribadi di depan, sebelum Placement Test (perilaku
     *   SEBELUMNYA -- default, form yang sudah berjalan sama sekali tidak
     *   berubah kecuali admin sengaja mengganti setting ini).
     * - last : Placement Test dulu, baru Data Pribadi di akhir sebelum submit.
     *
     * Kalau form ini requires_payment JUGA dan posisi ini di-set 'last',
     * urutannya jadi: Placement Test -> Data Pribadi -> Payment -> submit
     * (payment tetap di paling akhir -- gateway pembayaran butuh nama/email
     * peserta yang sudah terisi, jadi tidak bisa diletakkan sebelum Data
     * Pribadi). Lihat penyesuaian stepOrder di frontend/form-wizard.blade.php.
     *
     * Kolom ini hanya bermakna kalau has_personal_data_stage juga true --
     * form tanpa step Data Pribadi mengabaikan kolom ini sepenuhnya.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            if (!Schema::hasColumn('forms', 'personal_data_stage_position')) {
                $table->enum('personal_data_stage_position', ['first', 'last'])
                    ->default('first')
                    ->after('has_personal_data_stage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            if (Schema::hasColumn('forms', 'personal_data_stage_position')) {
                $table->dropColumn('personal_data_stage_position');
            }
        });
    }
};
