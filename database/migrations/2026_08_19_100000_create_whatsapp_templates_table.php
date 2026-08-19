<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel whatsapp_templates dipakai oleh App\Models\WhatsappTemplate dan
     * sudah direferensikan di WhatsappTemplateController + forms.whatsapp_template_id,
     * tapi migration-nya belum pernah dibuat. Ditambahkan di sini supaya fitur
     * "pilih template WA per form" bisa jalan.
     */
    public function up(): void
    {
        // Guard: di environment ini tabelnya ternyata sudah ada duluan (dibuat manual
        // sebelum migration ini ditulis), jadi jangan create ulang supaya migrate
        // tidak gagal dengan error "table already exists".
        if (Schema::hasTable('whatsapp_templates')) {
            return;
        }

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->nullable();

            $table->string('name');
            $table->text('content');
            $table->text('description')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
