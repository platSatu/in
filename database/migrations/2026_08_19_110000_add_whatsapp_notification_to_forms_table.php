<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Toggle admin: apakah form ini pakai notifikasi WhatsApp (template) atau tidak.
     * whatsapp_template_id sengaja nullable (tanpa foreign key, mengikuti pola
     * branch_id di migration add_branch_fields_to_forms_table) supaya konsisten
     * dengan kolom lain di tabel forms yang sudah ada.
     */
    public function up(): void
    {
        // Guard per kolom: environment ini kadang sudah punya sebagian kolom (mis.
        // dibuat manual sebelumnya), jadi tiap kolom/index dicek dulu supaya migrate
        // tidak gagal dengan error "column already exists".
        Schema::table('forms', function (Blueprint $table) {
            if (!Schema::hasColumn('forms', 'use_whatsapp_notification')) {
                $table->boolean('use_whatsapp_notification')->default(false)->after('callback_link');
            }

            if (!Schema::hasColumn('forms', 'whatsapp_template_id')) {
                $table->char('whatsapp_template_id', 36)->nullable()->after('use_whatsapp_notification');
                $table->index('whatsapp_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            if (Schema::hasColumn('forms', 'whatsapp_template_id')) {
                $table->dropIndex(['whatsapp_template_id']);
            }

            $columns = array_filter(
                ['use_whatsapp_notification', 'whatsapp_template_id'],
                fn ($column) => Schema::hasColumn('forms', $column)
            );

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
