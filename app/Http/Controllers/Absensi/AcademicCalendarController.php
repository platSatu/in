<?php

namespace App\Http\Controllers\Absensi;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\AcademicCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            (string) $userId,
            ['title', 'description', 'event_type', 'start_date', 'end_date'],
            $search,
            10
        );

        return view('absensi.academic-calendar.index', compact('data'));
    }

    public function create()
    {
        return view('absensi.academic-calendar.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'event_type' => 'required|in:holiday,exam,semester,event,other',
            'is_active' => 'nullable|boolean',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(AcademicCalendar::class, $validated);

        return redirect()
            ->route('absensi.academic-calendar.index')
            ->with('success', 'Kalender Academic berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(AcademicCalendar::class, $id, (string) $userId);

        return view('absensi.academic-calendar.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(AcademicCalendar::class, $id, (string) $userId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'event_type' => 'required|in:holiday,exam,semester,event,other',
            'is_active' => 'nullable|boolean',
        ]);

        AdminCrud::update(AcademicCalendar::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('absensi.academic-calendar.index')
            ->with('success', 'Kalender Academic berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(AcademicCalendar::class, $id, (string) $userId);

        return redirect()
            ->route('absensi.academic-calendar.index')
            ->with('success', 'Kalender Academic berhasil dihapus.');
    }
}
