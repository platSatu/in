<?php

namespace App\Http\Controllers\Absensi;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            Attendance::class,
            (string) $userId,
            ['attendance_date', 'status', 'check_in_method', 'device_info'],
            $search,
            10,
            ['setting']
        );

        return view('absensi.attendance.index', compact('data'));
    }

    public function create()
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $settings = AttendanceSetting::query()
            ->where('user_id', (string) $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('absensi.attendance.create', compact('settings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after_or_equal:check_in_time',
            'attendance_setting_id' => 'required|string|exists:attendance_settings,id',
            'status' => 'required|in:present,late,absent,leave,sick,permission',
            'late_minutes' => 'nullable|integer|min:0',
            'work_hours' => 'nullable|numeric|min:0',
            'check_in_lat' => 'nullable|numeric',
            'check_in_lng' => 'nullable|numeric',
            'check_out_lat' => 'nullable|numeric',
            'check_out_lng' => 'nullable|numeric',
            'check_in_method' => 'nullable|string|max:255',
            'device_info' => 'nullable|string',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $settingOwned = AttendanceSetting::query()
            ->where('id', $validated['attendance_setting_id'])
            ->where('user_id', (string) $userId)
            ->exists();

        if (!$settingOwned) {
            abort(403, 'Attendance setting tidak valid untuk user ini.');
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(Attendance::class, $validated);

        return redirect()
            ->route('absensi.attendance.index')
            ->with('success', 'Attendance berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(Attendance::class, $id, (string) $userId, ['setting']);

        $settings = AttendanceSetting::query()
            ->where('user_id', (string) $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('absensi.attendance.edit', compact('data', 'settings'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(Attendance::class, $id, (string) $userId);

        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after_or_equal:check_in_time',
            'attendance_setting_id' => 'required|string|exists:attendance_settings,id',
            'status' => 'required|in:present,late,absent,leave,sick,permission',
            'late_minutes' => 'nullable|integer|min:0',
            'work_hours' => 'nullable|numeric|min:0',
            'check_in_lat' => 'nullable|numeric',
            'check_in_lng' => 'nullable|numeric',
            'check_out_lat' => 'nullable|numeric',
            'check_out_lng' => 'nullable|numeric',
            'check_in_method' => 'nullable|string|max:255',
            'device_info' => 'nullable|string',
        ]);

        $settingOwned = AttendanceSetting::query()
            ->where('id', $validated['attendance_setting_id'])
            ->where('user_id', (string) $userId)
            ->exists();

        if (!$settingOwned) {
            abort(403, 'Attendance setting tidak valid untuk user ini.');
        }

        AdminCrud::update(Attendance::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('absensi.attendance.index')
            ->with('success', 'Attendance berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(Attendance::class, $id, (string) $userId);

        return redirect()
            ->route('absensi.attendance.index')
            ->with('success', 'Attendance berhasil dihapus.');
    }
}
