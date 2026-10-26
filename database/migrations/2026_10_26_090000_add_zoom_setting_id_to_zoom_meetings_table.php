<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catat akun Zoom (baris zoom_settings) yang dipakai saat meeting dibuat.
     *
     * Sejak tab "Pengaturan Zoom" di menu Zoom > Kelola Meeting, admin bisa
     * menyimpan beberapa akun Zoom & mengganti mana yang aktif kapan saja.
     * Meeting yang sudah dibuat tetap "milik" akun Zoom pembuatnya, jadi
     * edit/end/delete/recording harus memanggil Zoom API dengan kredensial
     * akun itu -- bukan akun yang kebetulan sedang aktif.
     *
     * Nullable: kalau akun pembuatnya sudah dihapus, ZoomMeeting::zoomClient()
     * jatuh ke akun yang sedang aktif.
     */
    public function up(): void
    {
        Schema::table('zoom_meetings', function (Blueprint $table) {
            $table->char('zoom_setting_id', 36)->nullable()->after('user_id');
            $table->index('zoom_setting_id');
        });

        // Meeting lama dibuat dengan akun yang aktif saat itu -- isi dengan
        // akun yang sedang aktif sekarang (perkiraan terbaik).
        $activeId = DB::table('zoom_settings')
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->value('id');

        if ($activeId) {
            DB::table('zoom_meetings')
                ->whereNull('zoom_setting_id')
                ->update(['zoom_setting_id' => $activeId]);
        }
    }

    public function down(): void
    {
        Schema::table('zoom_meetings', function (Blueprint $table) {
            $table->dropIndex(['zoom_setting_id']);
            $table->dropColumn('zoom_setting_id');
        });
    }
};
