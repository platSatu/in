<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Models\CoursePackage;
use App\Models\CoursePackagePurchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu "Laporan" (16 September 2026, permintaan user -- "buatkan 1 menu
 * baru di dalam course namanya laporan, tampilkan siapa yang beli
 * packages") -- READ ONLY, tidak ada create/edit/delete sama sekali,
 * cuma nampilin isi tabel CoursePackagePurchase (lihat docblock model itu)
 * dari sisi admin: siapa (Student) beli/klaim package apa, kapan, berapa
 * harga & credits-nya, serta sumbernya (trial gratis atau beli pakai
 * Deposit -- yang terakhir ini BELUM ada mekanismenya, jadi baris dengan
 * source itu belum akan pernah muncul sampai fitur pembelian berbayar
 * dibangun).
 *
 * Permission 'course.report' TERPISAH dari 'course.package' (bukan numpang
 * ke situ) -- supaya bisa di-grant sendiri-sendiri lewat halaman Role
 * (mis. staff finance boleh lihat Laporan tapi tidak boleh ubah katalog
 * Course Package, atau sebaliknya). Lihat config/menu.php & migration
 * sync_permissions_and_grant_superadmin_course_report.
 */
class CourseReportController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $packageId = $request->query('course_package_id');
        $source = $request->query('source');

        $purchases = CoursePackagePurchase::query()
            ->with(['student', 'coursePackage'])
            ->when($search, function (Builder $q) use ($search) {
                $q->whereHas('student', function (Builder $sq) use ($search) {
                    $sq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('handphone', 'like', "%{$search}%");
                })->orWhereHas('coursePackage', function (Builder $sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%");
                });
            })
            ->when($packageId, fn (Builder $q) => $q->where('course_package_id', $packageId))
            ->when($source, fn (Builder $q) => $q->where('source', $source))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Dropdown filter package -- semua package (aktif/nonaktif) supaya
        // laporan lama yang package-nya sudah dinonaktifkan admin tetap
        // bisa difilter, bukan cuma daftar filter InaYulePackageController
        // (activeOptions()) yang memang sengaja hanya untuk katalog beli.
        $packages = CoursePackage::orderBy('name')->get(['id', 'name']);

        return view('course.report.index', [
            'purchases' => $purchases,
            'packages' => $packages,
            'filters' => [
                'search' => $search,
                'course_package_id' => $packageId,
                'source' => $source,
            ],
        ]);
    }
}
