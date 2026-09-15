<?php

namespace App\Http\Controllers\Absensi;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\AcademicCalendar;
use App\Models\CompanyBranch;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * FIX (15 September 2026, permintaan user -- "data yang ditampilkan untuk
 * student/sales/pengajar/superadmin lihat semua"): Academic Calendar SEKARANG
 * jadi "kalender bersama" milik SEMUA admin yang punya akses modul ini
 * (bukan lagi per-pembuat) -- lihat catatan lengkap di
 * App\Http\Controllers\DashboardController::index() untuk cara filternya
 * ditampilkan ke tiap role/branch. Makanya index()/edit()/update()/destroy()
 * di bawah SENGAJA memanggil AdminCrud dengan $userId = null (tidak dibatasi
 * ke baris buatan sendiri) -- admin B sekarang bisa lihat/edit/hapus entry
 * buatan admin A juga, selama sama-sama punya permission
 * 'absensi.academic-calendar'.
 */
class AcademicCalendarController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            AcademicCalendar::class,
            null,
            ['title', 'description', 'event_type', 'start_date', 'end_date'],
            $search,
            10,
            ['targetRole', 'targetBranch']
        );

        return view('absensi.academic-calendar.index', compact('data'));
    }

    public function create()
    {
        [$roles, $companyBranches] = $this->targetOptions();

        return view('absensi.academic-calendar.create', compact('roles', 'companyBranches'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        // user_id tetap dicatat sbg "siapa yang bikin" (jejak audit), TAPI
        // sudah TIDAK dipakai lagi untuk membatasi siapa yang boleh
        // lihat/edit/hapus entry ini -- lihat docblock class di atas.
        $validated['user_id'] = (string) $userId;

        AdminCrud::create(AcademicCalendar::class, $validated);

        return redirect()
            ->route('absensi.academic-calendar.index')
            ->with('success', 'Kalender Academic berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(AcademicCalendar::class, $id);
        [$roles, $companyBranches] = $this->targetOptions();

        return view('absensi.academic-calendar.edit', compact('data', 'roles', 'companyBranches'));
    }

    public function update(Request $request, string $id)
    {
        AdminCrud::findOrFail(AcademicCalendar::class, $id);

        $validated = $this->validatePayload($request);

        AdminCrud::update(AcademicCalendar::class, $id, $validated);

        return redirect()
            ->route('absensi.academic-calendar.index')
            ->with('success', 'Kalender Academic berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        AdminCrud::delete(AcademicCalendar::class, $id);

        return redirect()
            ->route('absensi.academic-calendar.index')
            ->with('success', 'Kalender Academic berhasil dihapus.');
    }

    /**
     * Validasi field form Academic Calendar, dipakai bareng store() & update()
     * supaya aturan tidak dobel-tulis. target_role_id/target_branch_id SENGAJA
     * nullable -- kosong berarti "Semua Role"/"Semua Branch" (lihat dropdown
     * di create/edit.blade.php, opsi kosongnya value="").
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'event_type' => 'required|in:holiday,exam,semester,event,other',
            'is_active' => 'nullable|boolean',
            'target_role_id' => 'nullable|exists:roles,id',
            'target_branch_id' => 'nullable|exists:company_branch,id',
        ]);
    }

    /**
     * Daftar Role & Company Branch untuk 2 dropdown "Untuk Role"/"Untuk
     * Branch" di form create/edit.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    private function targetOptions(): array
    {
        $roles = Role::active()->orderBy('name')->get(['id', 'name']);
        $companyBranches = CompanyBranch::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return [$roles, $companyBranches];
    }
}
