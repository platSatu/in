<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_user_qr_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36);

            $table->string('qr_token')->unique();

            // ✅ tambahan untuk simpan file QR image
            $table->string('qr_code_path')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamp('last_rotated_at')->nullable();

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('qr_token');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_user_qr_codes');
    }
};
