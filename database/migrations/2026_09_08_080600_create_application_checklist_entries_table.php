<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Status tiap item checklist (application_checklist_items) PER aplikasi --
| selesai/belum, catatan, foto bukti, kapan & siapa yang menandai. Ini yang
| dikelola superadmin, dan sumber stepper read-only di dashboard siswa.
|
| Tabel BARU, tidak menyentuh tabel/fitur lain yang sudah ada.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_checklist_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('application_id', 36);
            $table->char('checklist_item_id', 36);

            $table->boolean('is_done')->default(false);
            $table->text('note')->nullable();
            $table->string('photo_path')->nullable();

            $table->timestamp('done_at')->nullable();
            $table->char('done_by_user_id', 36)->nullable();

            $table->timestamps();

            $table->unique(['application_id', 'checklist_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_checklist_entries');
    }
};
