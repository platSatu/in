<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === ZOOM MEETINGS ===
     * Cermin lokal dari meeting yang dibuat lewat Zoom API (menu Zoom >
     * Kelola Meeting) -- App\Services\Zoom\ZoomClient yang benar-benar bicara
     * ke Zoom, tabel ini cuma menyimpan hasil & status terakhirnya supaya
     * daftar meeting bisa ditampilkan tanpa harus panggil API tiap kali.
     */
    public function up(): void
    {
        Schema::create('zoom_meetings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36)->nullable();

            // ID numerik meeting dari Zoom (mis. 815xxxxxxxx) -- disimpan
            // string karena bisa melebihi jangkauan integer & tidak pernah
            // dipakai untuk operasi matematis, cuma identifier ke Zoom API.
            $table->string('zoom_meeting_id')->unique();
            // UUID instance meeting dari Zoom, dipakai buat query recordings
            // (beda dari zoom_meeting_id -- 1 zoom_meeting_id bisa punya
            // banyak "uuid" kalau meeting yang sama dijalankan berkali-kali).
            $table->string('zoom_uuid')->nullable();

            $table->string('topic');
            $table->text('agenda')->nullable();

            // Tipe meeting Zoom: 1=instant, 2=scheduled. Modul ini cuma
            // membuat scheduled meeting (2); kolom ini disimpan untuk
            // referensi/pengembangan lanjutan (mis. kalau nanti ditambah
            // instant meeting).
            $table->unsignedTinyInteger('type')->default(2);

            $table->dateTime('start_time')->nullable();
            $table->unsignedInteger('duration')->default(60);
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->string('password', 20)->nullable();

            $table->text('join_url')->nullable();
            // start_url berisi token akses host & bisa sangat panjang -- text,
            // bukan string(255).
            $table->text('start_url')->nullable();

            // Opsi meeting (join_before_host, waiting_room, host_video,
            // participant_video, mute_upon_entry) disimpan sebagai JSON supaya
            // gampang ditambah opsi baru tanpa migration lagi.
            $table->json('settings')->nullable();

            // Status LOKAL kita (beda dari status Zoom sendiri) -- 'scheduled'
            // sampai di-akhiri lewat tombol "End Meeting" (jadi 'ended').
            // Dihapus dari Zoom + tabel ini kalau di-destroy(), bukan diberi
            // status baru -- lihat App\Http\Controllers\Zoom\MeetingController.
            $table->enum('status', ['scheduled', 'started', 'ended'])->default('scheduled');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('recordings_synced_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoom_meetings');
    }
};
