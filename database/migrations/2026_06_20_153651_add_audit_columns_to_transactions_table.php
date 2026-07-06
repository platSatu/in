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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('transaction_code')->nullable()->after('id');
            $table->string('channel')->nullable()->after('status');
            $table->json('metadata')->nullable()->after('channel');
            $table->string('created_by')->nullable()->after('metadata');
            $table->uuid('reversal_of')->nullable()->after('created_by');

            $table->index('transaction_code');
            $table->index('channel');
            $table->index('reversal_of');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
        });
    }
};
