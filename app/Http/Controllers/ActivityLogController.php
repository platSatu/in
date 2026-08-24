<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman "Activity Log" — read-only murni (lihat catatan di App\Models\ActivityLog
 * kenapa sengaja tidak ada create/edit/delete di sini). Baris-barisnya ditulis
 * OTOMATIS oleh App\Helpers\ActivityLogger, controller ini cuma menampilkan.
 */
class ActivityLogController extends Controller
{
    private const EVENTS = ['login', 'logout', 'created', 'updated', 'deleted'];

    public function index(Request $request)
    {
        $search = $request->query('search');
        $event = $request->query('event');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $query = ActivityLog::query();

        // Superadmin melihat aktivitas SELURUH tim (semua actor); selain itu
        // cuma melihat aktivitas dirinya sendiri — pola paling aman selama
        // aplikasi ini belum punya konsep "tim melihat histori tim yang sama"
        // yang eksplisit di modul lain (lihat App\Concerns\HasScopedAccess,
        // dipakai untuk company/branch/division scoping tapi belum tentu
        // relevan 1:1 untuk histori aktivitas). Kalau nanti perlu visibilitas
        // company-wide untuk role lain, sesuaikan kondisi di bawah ini.
        if (!Auth::user()->hasRole('superadmin')) {
            $query->where('actor_id', (string) $userId);
        }

        if (!empty($event) && in_array($event, self::EVENTS, true)) {
            $query->where('event', $event);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('actor_name', 'like', "%{$search}%")
                    ->orWhere('actor_email', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('subject_label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $data = $query
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('activity-log.index', compact('data', 'event'));
    }

    /**
     * Fragmen HTML detail before/after satu baris, disuntik ke modal lewat
     * fetch() — pola sama persis dengan FormController::detail() (lihat
     * quiz/form/index.blade.php + quiz/form/_detail-content.blade.php).
     */
    public function detail(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $query = ActivityLog::query();

        if (!Auth::user()->hasRole('superadmin')) {
            $query->where('actor_id', (string) $userId);
        }

        $log = $query->findOrFail($id);

        return view('activity-log._detail-content', compact('log'));
    }
}
