<?php

namespace App\Http\Controllers\Absensi;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceSettingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            AttendanceSetting::class,
            (string) $userId,
            ['name', 'status'],
            $search,
            10
        );

        return view('absensi.attendance-setting.index', compact('data'));
    }

    public function create()
    {
        return view('absensi.attendance-setting.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i|after:check_in_time',
            'status' => 'required|in:active,inactive',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(AttendanceSetting::class, $validated);

        return redirect()
            ->route('absensi.attendance-setting.index')
            ->with('success', 'Attendance setting berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(AttendanceSetting::class, $id, (string) $userId);

        return view('absensi.attendance-setting.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(AttendanceSetting::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i|after:check_in_time',
            'status' => 'required|in:active,inactive',
        ]);

        AdminCrud::update(AttendanceSetting::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('absensi.attendance-setting.index')
            ->with('success', 'Attendance setting berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(AttendanceSetting::class, $id, (string) $userId);

        return redirect()
            ->route('absensi.attendance-setting.index')
            ->with('success', 'Attendance setting berhasil dihapus.');
    }
}
