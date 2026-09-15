<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Halaman "InaYule" (modul kursus Mandarin: Buy Packages / History /
 * Schedule).
 *
 * STEP 1 (15 September 2026, permintaan user): baru bikin TAMPILANNYA dulu
 * (skeleton 3 tab), semua isinya masih placeholder "Data not found" --
 * data model di baliknya (course_package_purchases, course_sessions, dst,
 * lihat diskusi konsep InaYule) masih didiskusikan & BELUM dibangun.
 * Jangan tambah query/logic apapun di controller ini sebelum ada keputusan
 * lanjutan dari diskusi tsb -- lihat juga docblock
 * resources/views/student-portal/inayule/index.blade.php.
 */
class InaYuleController extends Controller
{
    public function index(): View
    {
        return view('student-portal.inayule.index');
    }
}
