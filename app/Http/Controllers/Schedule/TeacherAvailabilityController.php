<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Concerns\ScopesToVisibleBranches;
use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\Role;
use App\Models\TeacherAvailability;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Jadwal > Ketersediaan Pengajar (25 November 2026).
 *
 * Admin mengisi jam buka tiap pengajar per cabang, baris per baris:
 *   Senin 13:00 - 17:00
 *   Rabu  17:00 - 20:00   (+ Tambah Baris)
 * Berlaku setiap minggu. Siswa kemudian memilih jam kelas di dalam jam buka
 * ini (form Ajukan Jadwal Kelas). Admin cabang hanya melihat cabangnya,
 * superadmin melihat semua cabang (ScopesToVisibleBranches).
 */
class TeacherAvailabilityController extends Controller
{
    use ScopesToVisibleBranches;

    public function index(Request $request): View
    {
        $rows = TeacherAvailability::with(['teacher:id,name', 'branch:id,name'])
            ->tap(fn ($query) => $this->scopeToVisibleBranches($query, $request))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (TeacherAvailability $row) => $row->teacher_user_id.'|'.$row->branch_id);

        return view('schedule.availability.index', [
            'groups' => $rows->sortBy(fn ($group) => $group->first()->teacher?->name),
            'teachers' => $this->teachers(),
            'branches' => $this->branches($request),
        ]);
    }

    /** Form jam buka 1 pengajar di 1 cabang (dipilih dari halaman daftar). */
    public function edit(Request $request): View|RedirectResponse
    {
        $teacher = $this->teachers()->firstWhere('id', $request->query('teacher_user_id'));
        $branch = $this->branches($request)->firstWhere('id', $request->query('branch_id'));

        if (! $teacher || ! $branch) {
            return redirect()->route('schedule.availability.index')->with('error', 'Pilih pengajar dan cabang terlebih dahulu.');
        }

        $rows = TeacherAvailability::where('teacher_user_id', $teacher->id)
            ->where('branch_id', $branch->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get(['day_of_week', 'start_time', 'end_time'])
            ->map(fn ($row) => [
                'day_of_week' => $row->day_of_week,
                'start_time' => substr($row->start_time, 0, 5),
                'end_time' => substr($row->end_time, 0, 5),
            ]);

        return view('schedule.availability.edit', [
            'teacher' => $teacher,
            'branch' => $branch,
            'rows' => old('rows', $rows->all()),
        ]);
    }

    /** Simpan semua baris sekaligus: baris lama diganti dengan isi form. */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_user_id' => ['required', 'string'],
            'branch_id' => ['required', 'string'],
            'rows' => ['nullable', 'array'],
            'rows.*.day_of_week' => ['required', 'integer', 'between:1,6'],
            'rows.*.start_time' => ['required', 'date_format:H:i'],
            'rows.*.end_time' => ['required', 'date_format:H:i', 'after:rows.*.start_time'],
        ], [
            'rows.*.end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        $teacher = $this->teachers()->firstWhere('id', $validated['teacher_user_id']);
        $branch = $this->branches($request)->firstWhere('id', $validated['branch_id']);
        abort_unless($teacher && $branch, 404);

        $rows = collect($validated['rows'] ?? [])->values();
        $this->assertNoOverlap($rows);

        DB::transaction(function () use ($teacher, $branch, $rows) {
            TeacherAvailability::where('teacher_user_id', $teacher->id)->where('branch_id', $branch->id)->delete();

            foreach ($rows as $row) {
                TeacherAvailability::create([
                    'teacher_user_id' => $teacher->id,
                    'branch_id' => $branch->id,
                    'day_of_week' => (int) $row['day_of_week'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                ]);
            }
        });

        return redirect()->route('schedule.availability.index')
            ->with('success', 'Jam buka '.$teacher->name.' di '.$branch->name.' sudah disimpan. Siswa sekarang bisa memilih jam di dalam jam buka ini.');
    }

    /** Dua baris di hari yang sama tidak boleh saling menimpa. */
    private function assertNoOverlap($rows): void
    {
        foreach ($rows as $i => $row) {
            foreach ($rows as $j => $other) {
                if ($i < $j && (int) $row['day_of_week'] === (int) $other['day_of_week']
                    && $row['start_time'] < $other['end_time'] && $row['end_time'] > $other['start_time']) {
                    throw ValidationException::withMessages([
                        'rows' => 'Ada jam yang bertabrakan di hari '.TeacherAvailability::DAYS[(int) $row['day_of_week']].'. Gabungkan atau perbaiki barisnya.',
                    ]);
                }
            }
        }
    }

    private function teachers()
    {
        return User::whereHas('roles', fn ($query) => $query->where('slug', 'teacher')->where('roles.status', Role::STATUS_ACTIVE))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function branches(Request $request)
    {
        return $this->scopeToVisibleBranches(CompanyBranch::query(), $request, 'id')->orderBy('name')->get(['id', 'name']);
    }
}
