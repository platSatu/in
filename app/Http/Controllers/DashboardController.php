<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user === null) {
            abort(401);
        }

        // FIX (15 September 2026, permintaan user -- "data yang ditampilkan
        // untuk student/sales/pengajar/superadmin lihat semua"): SEBELUMNYA
        // di sini filternya `where user_id = user yang login` (siapa yang
        // BUAT baris kalender itu) -- artinya staff/siswa yang tidak pernah
        // bikin kalender sendiri (hampir semua orang, kalender biasanya
        // dibuat admin) pasti SELALU kosong. Sekarang jadi "kalender
        // bersama": tiap entry BOLEH di-assign admin ke 1 role tertentu
        // dan/atau 1 branch tertentu (lihat 2 kolom baru di migration
        // add_target_role_and_branch_to_academic_calendars_table +
        // dropdown "Untuk Role"/"Untuk Branch" di
        // Absensi\AcademicCalendarController::create()/edit()).
        //
        // - target_role_id null   = entry itu tampil untuk SEMUA role.
        // - target_branch_id null = entry itu tampil untuk SEMUA branch.
        // - Kalau keduanya diisi, KEDUANYA harus cocok (role user ini salah
        //   satunya = target_role_id DAN branch user ini = target_branch_id)
        //   baru entry-nya muncul.
        // - Superadmin (scope_level company, App\Concerns\HasScopedAccess::
        //   isCompanyScoped()) TIDAK kena filter apa pun -- selalu lihat
        //   SEMUA entry, sesuai permintaan "superadmin lihat semua data".
        $query = AcademicCalendar::query()->where('is_active', true);

        if (!$user->isCompanyScoped()) {
            $roleIds = $user->activeRoleAssignments()
                ->pluck('role.id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $branchId = $user->resolveOwnBranchId();

            $query->where(function ($roleQuery) use ($roleIds) {
                $roleQuery->whereNull('target_role_id');
                if (!empty($roleIds)) {
                    $roleQuery->orWhereIn('target_role_id', $roleIds);
                }
            })->where(function ($branchQuery) use ($branchId) {
                $branchQuery->whereNull('target_branch_id');
                if ($branchId) {
                    $branchQuery->orWhere('target_branch_id', $branchId);
                }
            });
        }

        $calendars = $query->orderBy('start_date')->get();

        // FIX (14 September 2026, permintaan user -- "dashboard itu hanya
        // tanggal saja"): widget "My University Applications" + alur
        // Register manual yang sebelumnya sempat ditaruh di halaman ini
        // SUDAH DIPINDAH ke halaman terpisah "InaStudy"
        // (App\Http\Controllers\StudentPortal\InaStudyController +
        // resources/views/student-portal/inastudy/index.blade.php), dituju
        // langsung lewat menu "InaStudy" di sidebar -- supaya halaman
        // Dashboard ini murni cuma Academic Calendar seperti semula.
        return view('dashboard.index', compact('calendars'));
    }
}
