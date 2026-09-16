<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\CourseLevel;
use App\Models\CoursePackage;
use App\Models\CourseType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "InaYule" sisi student (tab Buy Packages / History / Schedule).
 *
 * STEP 2 (16 September 2026, permintaan user): tab "Buy Packages" sekarang
 * menampilkan katalog CoursePackage sungguhan -- filter Type/Class/Level +
 * search, gaya "catalog product" -- menggantikan placeholder "Data not
 * found" dari STEP 1.
 *
 * SENGAJA dipisah jadi controller SENDIRI (bukan ditumpuk ke
 * App\Http\Controllers\StudentPortal\InaYuleController yang dipakai di
 * STEP 1) -- permintaan user: "mungkin baiknya dipisahkan controllernya
 * karena ini nanti akan ada pembelian dll nya". Controller ini yang nanti
 * bakal menampung logic pembelian package (potong saldo deposit, catat ke
 * ledger credit student, dst -- lihat diskusi konsep InaYule), BELUM
 * dibangun di step ini -- baru katalog + filter + search saja.
 *
 * route('inayule.index') di routes/web.php SEKARANG diarahkan ke sini
 * (bukan lagi ke InaYuleController::index()) -- file InaYuleController.php
 * dibiarkan ada (tidak dihapus) tapi sudah tidak dipakai lagi.
 *
 * Tab "History" & "Schedule" MASIH placeholder "Data not found" di Blade-nya
 * -- History baru bisa diisi data sungguhan setelah tabel riwayat pembelian
 * (course_package_purchases, lihat diskusi konsep InaYule) dibangun, karena
 * belum ada satupun mekanisme beli package yang benar-benar jalan.
 */
class InaYulePackageController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $typeId = $request->query('course_type_id');
        $classId = $request->query('course_class_id');
        $levelId = $request->query('course_level_id');

        $packages = CoursePackage::query()
            ->where('status', 'active')
            ->with(['type', 'courseClass', 'level'])
            ->when($search, fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
            ->when($typeId, fn (Builder $q) => $q->where('course_type_id', $typeId))
            ->when($classId, fn (Builder $q) => $q->where('course_class_id', $classId))
            ->when($levelId, fn (Builder $q) => $q->where('course_level_id', $levelId))
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        // Dropdown filter cuma tampilkan master data aktif -- mirip pola
        // activeOptions() di Course\CoursePackageController, tapi di sini
        // tidak perlu jaring "tetap sertakan yang sedang dipilih" (itu buat
        // form edit; ini cuma filter katalog, aman kalau opsi yang sudah
        // dinonaktifkan hilang dari daftar filter).
        $types = CourseType::where('status', 'active')->orderBy('name')->get();
        $classes = CourseClass::where('status', 'active')->orderBy('name')->get();
        $levels = CourseLevel::where('status', 'active')->orderBy('name')->get();

        return view('student-portal.inayule.index', [
            'packages' => $packages,
            'types' => $types,
            'classes' => $classes,
            'levels' => $levels,
            'filters' => [
                'search' => $search,
                'course_type_id' => $typeId,
                'course_class_id' => $classId,
                'course_level_id' => $levelId,
            ],
        ]);
    }
}
