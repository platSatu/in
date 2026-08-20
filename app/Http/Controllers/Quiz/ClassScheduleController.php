<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\CompanyBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CRUD admin untuk "Kelas/Jadwal Kursus" (menu Quiz > Class Schedule) — data
 * jadwal milik satu Branch (nama kelas, level, tanggal+jam, kapasitas) yang
 * ditawarkan ke student lewat halaman publik "Pilih Kelas" setelah hasil
 * placement test mereka keluar (lihat ClassSelectionController).
 */
class ClassScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $search = $request->query('search');

        $data = ClassSchedule::with('companyBranch')
            ->withCount('activeEnrollments')
            ->where('user_id', (string) $userId)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('level', 'like', "%{$search}%");
                });
            })
            ->orderBy('class_date')
            ->orderBy('start_time')
            ->paginate(10)
            ->withQueryString();

        return view('quiz.class-schedule.index', compact('data'));
    }

    public function create(): View
    {
        $branches = CompanyBranch::select('id', 'name')->orderBy('name')->get();

        return view('quiz.class-schedule.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated = $this->validated($request);
        $validated['user_id'] = (string) $userId;

        AdminCrud::create(ClassSchedule::class, $validated);

        return redirect()
            ->route('quiz.class-schedule.index')
            ->with('success', 'Jadwal kelas berhasil dibuat.');
    }

    public function edit(string $id): View
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(ClassSchedule::class, $id, (string) $userId);
        $branches = CompanyBranch::select('id', 'name')->orderBy('name')->get();

        return view('quiz.class-schedule.edit', compact('data', 'branches'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(ClassSchedule::class, $id, (string) $userId);

        $validated = $this->validated($request);

        AdminCrud::update(ClassSchedule::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.class-schedule.index')
            ->with('success', 'Jadwal kelas berhasil diupdate.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $schedule = AdminCrud::findOrFail(ClassSchedule::class, $id, (string) $userId);

        // Jangan biarkan jadwal yang masih punya peserta aktif terhapus begitu
        // saja — akan meninggalkan baris class_enrollments yatim (menunjuk ke
        // class_schedule_id yang sudah tidak ada, karena project ini tidak
        // memakai FK constraint di database).
        $activeParticipants = $schedule->activeEnrollments()->count();
        if ($activeParticipants > 0) {
            return redirect()
                ->route('quiz.class-schedule.index')
                ->withErrors(['delete' => "Tidak bisa menghapus, kelas ini masih punya {$activeParticipants} peserta terdaftar."]);
        }

        AdminCrud::delete(ClassSchedule::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.class-schedule.index')
            ->with('success', 'Jadwal kelas berhasil dihapus.');
    }

    /**
     * Daftar peserta (student) yang terdaftar aktif di satu jadwal kelas —
     * dibuka dari tombol "Peserta" di quiz.class-schedule.index.
     */
    public function participants(string $id): View
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $schedule = AdminCrud::findOrFail(ClassSchedule::class, $id, (string) $userId, ['companyBranch']);

        $enrollments = $schedule->activeEnrollments()
            ->with('student')
            ->latest('created_at')
            ->get();

        return view('quiz.class-schedule.participants', compact('schedule', 'enrollments'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'branch_id' => 'required|string|exists:company_branch,id',
            'name' => 'required|string|max:255',
            'level' => 'nullable|string|max:255',
            'class_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive',
        ]);
    }
}
