<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === JADWAL & VIEWER FORM ===
     * start_date & end_date: kondisi tambahan (di luar kolom `status`) supaya URL
     * publik form hanya bisa diakses kalau status = active DAN (kalau diisi)
     * waktu sekarang berada di antara start_date..end_date. Nullable karena
     * kalau tidak diisi berarti form tidak dibatasi jadwal (perilaku lama tetap
     * sama, hanya bergantung ke status). Dicek bareng di Form::scopePubliclyAccessible().
     *
     * view_count: hit counter sederhana, +1 setiap kali halaman form publik
     * (booth) diakses/dibuka (termasuk reload), dipakai untuk kolom "Viewer"
     * di index admin.
     */
    public function up(): void
    {
        // Guard per kolom, sama seperti migration lain di batch ini — supaya aman
        // dijalankan meskipun sebagian kolom sudah ada duluan di environment tertentu.
        Schema::table('forms', function (Blueprint $table) {
            if (!Schema::hasColumn('forms', 'start_date')) {
                $table->timestamp('start_date')->nullable()->after('status');
            }

            if (!Schema::hasColumn('forms', 'end_date')) {
                $table->timestamp('end_date')->nullable()->after('start_date');
            }

            if (!Schema::hasColumn('forms', 'view_count')) {
                $table->unsignedBigInteger('view_count')->default(0)->after('end_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $columns = array_filter(
                ['start_date', 'end_date', 'view_count'],
                fn ($column) => Schema::hasColumn('forms', $column)
            );

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
