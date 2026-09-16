<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Models\CourseCredit;
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
            // FIX (16 September 2026, permintaan user): kolom Package di
            // Blade sekarang ikut tampilkan Type/Class/Level -- eager load
            // relasi bersarangnya sekalian di sini supaya tidak N+1 query.
            ->with(['student', 'coursePackage.type', 'coursePackage.courseClass', 'coursePackage.level'])
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

        // FIX (16 September 2026, permintaan user -- tambah kolom
        // "Terpakai"/"Sisa"): pola & alasan SAMA PERSIS dengan
        // InaYulePackageController::index() (baca docblock di sana) -- FIFO
        // per student, MURNI hitungan tampilan, saldo credit tetap 1 pool
        // bersama (App\Models\CourseCredit). Bedanya di sini datanya lintas
        // BANYAK student sekaligus (1 halaman Laporan bisa berisi purchase
        // dari student yang berbeda-beda), jadi FIFO-nya dihitung PER
        // student_id yang tampil di halaman ini -- ambil SELURUH riwayat
        // purchase student2 tsb (bukan cuma yang di halaman ini) supaya
        // urutan konsumsinya tetap benar, baru dipetakan balik ke baris
        // yang ditampilkan.
        $studentIds = $purchases->getCollection()->pluck('student_id')->filter()->unique()->values();

        $creditsMap = [];

        if ($studentIds->isNotEmpty()) {
            $allPurchasesByStudent = CoursePackagePurchase::whereIn('student_id', $studentIds)
                ->orderBy('created_at')
                ->get(['id', 'student_id', 'credits_granted', 'status', 'created_at']);

            $totalUsedByStudent = CourseCredit::whereIn('student_id', $studentIds)
                ->selectRaw('student_id, SUM(debit) as total_debit')
                ->groupBy('student_id')
                ->pluck('total_debit', 'student_id');

            $remainingToAllocateByStudent = [];
            foreach ($studentIds as $sid) {
                $remainingToAllocateByStudent[$sid] = (float) ($totalUsedByStudent[$sid] ?? 0);
            }

            foreach ($allPurchasesByStudent as $p) {
                $granted = (float) $p->credits_granted;
                $usedForThis = $p->status === CoursePackagePurchase::STATUS_COMPLETED
                    ? min($remainingToAllocateByStudent[$p->student_id], $granted)
                    : 0.0;

                $creditsMap[$p->id] = [
                    'used' => $usedForThis,
                    'remaining' => max($granted - $usedForThis, 0.0),
                ];

                $remainingToAllocateByStudent[$p->student_id] -= $usedForThis;
            }
        }

        foreach ($purchases as $purchase) {
            $purchase->credits_used = $creditsMap[$purchase->id]['used'] ?? 0.0;
            $purchase->credits_remaining = $creditsMap[$purchase->id]['remaining'] ?? (float) $purchase->credits_granted;
        }

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
