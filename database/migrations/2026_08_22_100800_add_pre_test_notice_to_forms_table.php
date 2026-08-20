<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `pre_test_notice` adalah catatan/pengumuman opsional per-Form (mis. tata
     * tertib Placement Test) yang, kalau diisi, ditampilkan sebagai 1 step
     * tambahan ("step-notice") di form-wizard.blade.php — selalu tepat setelah
     * step Data Pribadi/Info dan sebelum step Payment/Questions, apapun
     * pengaturan requires_payment/payment_position form tersebut. Kalau kosong
     * (null), step ini otomatis di-skip (lihat hasPreTestNotice di
     * frontend/form-wizard.blade.php).
     */
    public function up(): void
    {
        // Guard: aman dijalankan meskipun kolomnya sudah ada duluan (pola yang
        // sama dengan migration add_is_other_to_form_question_options_table).
        if (Schema::hasColumn('forms', 'pre_test_notice')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $table->text('pre_test_notice')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('forms', 'pre_test_notice')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('pre_test_notice');
        });
    }
};
