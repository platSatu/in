<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->char('branch_id', 36)->nullable()->after('user_id');
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('no_booth')->nullable()->after('slug');

            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn(['branch_id', 'slug', 'no_booth']);
        });
    }
};
