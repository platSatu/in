<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Menu Course > Student InaYule (13 November 2026, lihat App\Http\Controllers\Course\CourseStudentController):
| - students.teacher_user_id: pengajar tetap murid InaYule (opsional, kosong untuk data lama).
| - permission 'course.student' (pola sama dengan sync_permissions_and_grant_superadmin_ina_visa).
|   Role lain: halaman Roles -> edit role -> centang "Student InaYule" (grup Course).
*/
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'teacher_user_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignUuid('teacher_user_id')->nullable()->after('handled_by_user_id')
                    ->constrained('users')->nullOnDelete();
            });
        }

        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();
        $permission = Permission::where('key', 'course.student')->first();

        if ($superadmin && $permission) {
            $superadmin->permissions()->syncWithoutDetaching([
                $permission->id => ['can_edit' => true],
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'teacher_user_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropConstrainedForeignId('teacher_user_id');
            });
        }
        // Permission sengaja tidak dihapus, sama dengan migration sync permission sebelumnya.
    }
};
