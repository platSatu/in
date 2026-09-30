<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refund credit sesi & potong credit manual oleh admin (30 September 2026),
 * lihat App\Services\ClassSession\ClassSessionWorkflowService::refund() dan
 * ::adminCharge().
 *
 * - teacher_user_id jadi nullable: potong credit manual boleh tanpa pengajar
 *   (tidak ada honor).
 * - is_admin_entry: sesi dibuat langsung oleh admin (mis. siswa tidak hadir,
 *   diganti video, dianggap hadir).
 * - refunded_*: sesi yang credit-nya dikembalikan; refund_course_credit_id
 *   menunjuk baris credit pengembaliannya.
 *
 * Nama foreign key dibuat pendek (batas MySQL 64 karakter). Aman dijalankan ulang.
 */
return new class extends Migration
{
    private const FOREIGN_KEY = 'cs_refund_credit_fk';

    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->char('teacher_user_id', 36)->nullable()->change();
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('class_sessions', 'is_admin_entry')) {
                $table->boolean('is_admin_entry')->default(false)->after('admin_user_id');
            }
            if (! Schema::hasColumn('class_sessions', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('is_admin_entry');
            }
            if (! Schema::hasColumn('class_sessions', 'refunded_by_user_id')) {
                $table->char('refunded_by_user_id', 36)->nullable()->after('refunded_at');
            }
            if (! Schema::hasColumn('class_sessions', 'refund_reason')) {
                $table->string('refund_reason', 500)->nullable()->after('refunded_by_user_id');
            }
            if (! Schema::hasColumn('class_sessions', 'refund_course_credit_id')) {
                $table->uuid('refund_course_credit_id')->nullable()->after('refund_reason');
            }
        });

        if (! $this->hasForeignKey()) {
            Schema::table('class_sessions', function (Blueprint $table) {
                $table->foreign('refund_course_credit_id', self::FOREIGN_KEY)->references('id')->on('course_credits')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if ($this->hasForeignKey()) {
            Schema::table('class_sessions', function (Blueprint $table) {
                $table->dropForeign(self::FOREIGN_KEY);
            });
        }

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropColumn(['is_admin_entry', 'refunded_at', 'refunded_by_user_id', 'refund_reason', 'refund_course_credit_id']);
        });
    }

    private function hasForeignKey(): bool
    {
        return collect(Schema::getForeignKeys('class_sessions'))
            ->contains(fn (array $foreignKey) => $foreignKey['name'] === self::FOREIGN_KEY);
    }
};
