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
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36);

            $table->date('attendance_date');

            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();

            $table->char('attendance_setting_id', 36)->nullable();

            $table->enum('status', [
                'present',
                'late',
                'absent',
                'leave',
            ])->default('present');

            $table->integer('late_minutes')->nullable();

            $table->decimal('work_hours', 5, 2)->nullable();

            // optional location
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();

            // tracking method
            $table->string('check_in_method')->nullable();

            $table->string('device_info')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('attendance_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
