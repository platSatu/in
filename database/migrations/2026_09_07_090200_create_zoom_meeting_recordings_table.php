<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === ZOOM MEETING RECORDINGS ===
     * Daftar file recording per meeting, hasil sinkronisasi dari Zoom API
     * (GET /meetings/{id}/recordings) -- lihat
     * App\Http\Controllers\Zoom\MeetingController::recordings() &
     * App\Services\Zoom\ZoomClient::listRecordings().
     */
    public function up(): void
    {
        Schema::create('zoom_meeting_recordings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('zoom_meeting_id')
                ->constrained('zoom_meetings')
                ->cascadeOnDelete();

            // ID unik file recording dari Zoom (field "id" di response Zoom,
            // beda dari primary key tabel ini) -- dipakai supaya sinkronisasi
            // ulang tidak membuat duplikat baris (upsert berdasar ini).
            $table->string('recording_file_id')->unique();

            $table->string('recording_type')->nullable();
            $table->string('file_type', 20)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->text('play_url')->nullable();
            $table->text('download_url')->nullable();

            $table->dateTime('recording_start')->nullable();
            $table->dateTime('recording_end')->nullable();

            $table->string('status', 30)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoom_meeting_recordings');
    }
};
