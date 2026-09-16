<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\CourseCredit;
use App\Models\CourseLevel;
use App\Models\CoursePackage;
use App\Models\CoursePackagePurchase;
use App\Models\CourseType;
use App\Models\Student;
use App\Services\StudentIdentityResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
 * karena ini nanti akan ada pembelian dll nya". route('inayule.index') di
 * routes/web.php SEKARANG diarahkan ke sini (bukan lagi ke
 * InaYuleController::index()) -- file InaYuleController.php dibiarkan ada
 * (tidak dihapus) tapi sudah tidak dipakai lagi.
 *
 * STEP 4 (16 September 2026, permintaan user -- "knp angka nol disable
 * juga ya button nya kan tidak ada pembayaran ya" -> "okey boleh tolong
 * dibangun ya, tetap konsisten dan pastikan keamanannya ya"): package
 * dengan harga EFEKTIF Rp 0 (trial, lihat CoursePackage::effectivePrice())
 * sekarang bisa langsung "diklaim" lewat claimTrial() -- membuat baris
 * CoursePackagePurchase (source='trial_claim') + CourseCredit (nambah
 * saldo credit student), TANPA nyentuh Deposit sama sekali karena tidak
 * ada uang berpindah. Package berbayar (effectivePrice() > 0) TETAP
 * disabled di Blade -- logic potong saldo Deposit belum dibangun, masih
 * nunggu integrasi Deposit menyusul (lihat diskusi konsep InaYule).
 *
 * Tab "History" SEKARANG diisi data sungguhan dari CoursePackagePurchase
 * (sebelumnya placeholder "Data not found" karena tabelnya belum ada). Tab
 * "Schedule" MASIH placeholder -- baru bisa diisi setelah jadwal kelas
 * (course_session_attendances / booking sesi, BELUM dibangun) terhubung ke
 * student.
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
            // FIX (16 September 2026, permintaan user): package yang TERAKHIR
            // diinput admin selalu tampil paling atas -- sebelumnya orderBy('name').
            ->latest()
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

        // STEP 4: resolve Student punya user login ini (kalau ada) --
        // dipakai buat (a) tampilkan sisa saldo credit, (b) tandai trial
        // mana saja yang SUDAH pernah diklaim (supaya tombolnya diganti
        // "Sudah Diklaim" di Blade, sebelum sempat submit ke server sama
        // sekali -- guard sungguhannya tetap di claimTrial(), ini cuma UX),
        // dan (c) isi tab History. Staff/admin yang entah bagaimana buka
        // halaman ini (tidak punya Student) akan lihat semuanya kosong --
        // aman, karena tidak ada satupun tombol "Klaim Gratis" yang bisa
        // ke-submit tanpa Student (claimTrial() bikin Student baru kalau
        // benar-benar belum ada, lihat docblock method itu).
        $student = Student::where('user_id', Auth::id())->first();

        $creditBalance = $student ? CourseCredit::currentBalanceFor($student->id) : 0.0;

        $claimedTrialPackageIds = $student
            ? CoursePackagePurchase::where('student_id', $student->id)
                ->where('source', CoursePackagePurchase::SOURCE_TRIAL_CLAIM)
                ->pluck('course_package_id')
                ->all()
            : [];

        // STEP 5 (16 September 2026, permintaan user -- tambah kolom
        // "Terpakai"/"Sisa" di tab History): saldo credit TETAP 1 pool
        // bersama per student (lihat App\Models\CourseCredit, TIDAK diubah
        // jadi per-package-locked) -- breakdown "terpakai"/"sisa" PER BARIS
        // purchase di bawah ini MURNI hasil hitungan tampilan (FIFO: total
        // credit yang sudah kepakai dialokasikan ke purchase yang PALING
        // LAMA dulu, konvensi "yang didapat duluan, dipakai duluan"), BUKAN
        // sumber kebenaran baru -- kalau nanti absensi/booking sesi
        // (course_session_attendances, belum dibangun) motong credit, itu
        // tetap motong dari pool bersama ini, bukan dari 1 purchase
        // tertentu. Perhitungan ini aman diulang kapan saja karena cuma
        // baca data, tidak menyimpan apapun.
        $purchases = $student
            ? CoursePackagePurchase::where('student_id', $student->id)
                ->with('coursePackage')
                ->orderBy('created_at') // ASC dulu -- FIFO alokasi di bawah
                ->get()
            : collect();

        $totalUsed = $student ? (float) CourseCredit::where('student_id', $student->id)->sum('debit') : 0.0;
        $remainingToAllocate = $totalUsed;

        foreach ($purchases as $purchase) {
            $granted = (float) $purchase->credits_granted;
            $usedForThis = $purchase->status === CoursePackagePurchase::STATUS_COMPLETED
                ? min($remainingToAllocate, $granted)
                : 0.0;

            // Attribute dinamis, cuma buat tampilan -- TIDAK ada kolom
            // credits_used/credits_remaining di tabel course_package_
            // purchases, sengaja tidak disimpan (lihat docblock di atas).
            $purchase->credits_used = $usedForThis;
            $purchase->credits_remaining = max($granted - $usedForThis, 0.0);

            $remainingToAllocate -= $usedForThis;
        }

        // Balik ke urutan terbaru dulu buat tampilan (sama seperti sebelumnya).
        $purchases = $purchases->sortByDesc('created_at')->values();

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
            'creditBalance' => $creditBalance,
            'claimedTrialPackageIds' => $claimedTrialPackageIds,
            'purchases' => $purchases,
        ]);
    }

    /**
     * Klaim package TRIAL (harga efektif Rp 0) -- lihat docblock class di
     * atas untuk latar belakang fitur ini.
     *
     * Pengamanan yang dipasang (permintaan user -- "pastikan keamanannya"):
     * 1. Harga package DIHITUNG ULANG di server lewat effectivePrice() --
     *    TIDAK percaya bahwa tombol "Klaim Gratis" ini aktif di browser.
     *    Kalau ada yang coba curang lewat devtools/POST manual ke package
     *    berbayar, request tetap ditolak di sini.
     * 2. Baris Student dikunci (lockForUpdate()) selama transaksi supaya
     *    kalau ada 2 request klaim nyaris bersamaan dari student yang sama
     *    (double click / submit ulang cepat), request kedua akan menunggu
     *    request pertama selesai COMMIT dulu -- baru pengecekan "sudah
     *    pernah klaim" di bawah membaca data yang sudah ter-update, jadi
     *    tidak mungkin ke-klaim 2x untuk trial yang sama.
     * 3. 1 student cuma boleh klaim 1 trial yang sama SEKALI -- dicek dari
     *    CoursePackagePurchase (source='trial_claim'), bukan dari
     *    course_credits (supaya tetap ketahuan meski baris credit-nya
     *    kelak ikut dipakai/dikurangi debit).
     * 4. Pembuatan baris CoursePackagePurchase + CourseCredit dibungkus 1
     *    DB transaction -- kalau salah satu gagal, keduanya di-rollback,
     *    tidak mungkin ada purchase "yatim" tanpa credit-nya atau
     *    sebaliknya.
     */
    public function claimTrial(Request $request, string $packageId): RedirectResponse
    {
        $user = $request->user();

        // Hampir semua User login sudah otomatis punya Student terhubung
        // (dibuat saat register/Google login). StudentIdentityResolver di
        // sini cuma jaring pengaman untuk kasus langka User lama yang
        // belum sempat ke-link -- pola SAMA PERSIS dengan
        // InaStudyController::registerApplication().
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            $student = (new StudentIdentityResolver())->findOrCreate([
                'name' => $user->name,
                'email' => $user->email,
                'handphone' => $user->handphone,
            ]);

            if (empty($student->user_id)) {
                $student->user_id = $user->id;
                $student->save();
            }
        }

        $package = CoursePackage::where('status', 'active')->find($packageId);

        if (!$package) {
            return redirect()
                ->route('inayule.index')
                ->with('status', 'Package tidak ditemukan atau sudah tidak aktif.');
        }

        // Pengamanan #1 -- lihat docblock method di atas.
        if ($package->effectivePrice() > 0.0) {
            return redirect()
                ->route('inayule.index')
                ->with('status', 'Package ini bukan trial gratis. Fitur pembelian berbayar belum tersedia, silakan hubungi admin.');
        }

        return DB::transaction(function () use ($student, $package) {
            // Pengamanan #2 -- lihat docblock method di atas.
            $lockedStudent = Student::where('id', $student->id)->lockForUpdate()->first();

            // Pengamanan #3 -- lihat docblock method di atas.
            $alreadyClaimed = CoursePackagePurchase::where('student_id', $lockedStudent->id)
                ->where('course_package_id', $package->id)
                ->where('source', CoursePackagePurchase::SOURCE_TRIAL_CLAIM)
                ->exists();

            if ($alreadyClaimed) {
                return redirect()
                    ->route('inayule.index')
                    ->with('status', 'Anda sudah pernah klaim trial package ini sebelumnya.');
            }

            $purchase = CoursePackagePurchase::create([
                'student_id' => $lockedStudent->id,
                'course_package_id' => $package->id,
                'price_paid' => 0,
                'credits_granted' => $package->credits,
                'source' => CoursePackagePurchase::SOURCE_TRIAL_CLAIM,
                'status' => CoursePackagePurchase::STATUS_COMPLETED,
            ]);

            $newBalance = CourseCredit::currentBalanceFor($lockedStudent->id) + (float) $package->credits;

            CourseCredit::create([
                'student_id' => $lockedStudent->id,
                'course_package_purchase_id' => $purchase->id,
                'debit' => 0,
                'kredit' => $package->credits,
                'balance' => $newBalance,
                'description' => 'Klaim trial: ' . $package->name,
            ]);

            return redirect()
                ->route('inayule.index')
                ->with('success', 'Trial "' . $package->name . '" berhasil diklaim, credit Anda bertambah.');
        });
    }
}
