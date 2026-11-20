<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward hasil WhatsApp ke nomor lain (20 November 2026). Diisi per Form di Create/Edit Form,
 * beberapa nomor dipisah koma. NULL = tidak ada forward, perilaku lama tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('forms', 'whatsapp_forward_numbers')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->text('whatsapp_forward_numbers')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('forms', 'whatsapp_forward_numbers')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->dropColumn('whatsapp_forward_numbers');
            });
        }
    }
};
