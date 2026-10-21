<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 21 Oktober 2026: data Father & Mother terpisah + alasan apply scholarship.
 * Kolom lama parents_name/parents_phone/parents_occupation dipakai sebagai data Mother.
 */
return new class extends Migration
{
    private const COLUMNS = ['father_name', 'father_occupation', 'father_phone', 'father_email', 'mother_email', 'scholarship_reason'];

    public function up(): void
    {
        Schema::table('application_form_details', function (Blueprint $table) {
            $table->string('father_name')->nullable()->after('hobby');
            $table->string('father_occupation')->nullable()->after('father_name');
            $table->string('father_phone')->nullable()->after('father_occupation');
            $table->string('father_email')->nullable()->after('father_phone');
            $table->string('mother_email')->nullable()->after('parents_occupation');
            $table->text('scholarship_reason')->nullable()->after('sponsorship_type');
        });
    }

    public function down(): void
    {
        Schema::table('application_form_details', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });
    }
};
