<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `slug` sebelumnya unique per-form (slug dari nama branch). Sekarang `slug`
     * dipakai sebagai segmen branch pada URL publik (boleh sama untuk banyak
     * booth di branch yang sama), dan `booth_slug` jadi segmen booth-nya.
     * Kombinasi (slug, booth_slug) yang harus unik, bukan slug sendirian.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->string('booth_slug')->nullable()->after('no_booth');
            $table->unique(['slug', 'booth_slug'], 'forms_slug_booth_slug_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropUnique('forms_slug_booth_slug_unique');
            $table->dropColumn('booth_slug');
            $table->unique('slug');
        });
    }
};
