<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\ApplicationPayment;
use App\Models\Student;
use App\Models\UniversityApplication;
use App\Models\UniversityProfile;
use App\Models\UniversityProfileDegree;
use App\Services\ApplicationNumberGenerator;
use App\Services\StudentIdentityResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Fitur "Apply ke Kampus" (fase 4) -- form Apply per Major (UniversityProfile)
 * yang tombolnya ada di halaman frontend.university-profile. Namespace
 * terpisah "StudentPortal" (BUKAN "Student", yang sudah dipakai untuk
 * App\Http\Controllers\Student\StudentController -- CRUD data Student/CRM
 * di sisi SUPERADMIN) supaya tidak tertukar: controller di namespace ini
 * semuanya untuk sisi SISWA yang login (Portal Siswa / InaStudy).
 *
 * PENTING (permintaan user, jangan diubah): siswa TIDAK PERNAH melewati
 * middleware 'permission:...' apapun di sini -- cukup 'auth' (+ 'verified'
 * pada store()) -- supaya menu/route superadmin tetap terpisah total dari
 * area siswa. Route controller ini SENGAJA TIDAK didaftarkan di
 * config/menu.php (sidebar admin), jadi tidak pernah nongol di menu
 * superadmin manapun.
 */
class ApplyController extends Controller
{
    /**
     * Tampilkan form Apply untuk satu Major (UniversityProfile) tertentu.
     *
     * Guest (belum login) diarahkan ke halaman Register dulu (bukan Login --
     * permintaan eksplisit user), dengan URL form Apply ini disimpan sebagai
     * "intended URL" supaya begitu selesai register -> verifikasi email,
     * otomatis kembali lagi ke sini (lihat VerifyEmailController yang sudah
     * pakai redirect()->intended()) -- bukan malah dilempar ke dashboard.
     */
    public function show(Request $request, string $universityProfileId): View|RedirectResponse
    {
        $profile = UniversityProfile::with(['university', 'degrees', 'payments'])
            ->where('status', 'active')
            ->findOrFail($universityProfileId);

        // FIX v2 (permintaan user, 16 September 2026): tombol Apply Now di
        // halaman frontend.university-profile sekarang ada DI TIAP JURUSAN
        // (course-item), bukan lagi di level Degree tab -- begitu diklik,
        // jurusan (Course) yang dipilih siswa dibawa ke sini lewat query
        // string ?course=<id Course>, supaya form Apply LANGSUNG terisi
        // penuh untuk jurusan itu (lihat $lockedCourse & tampilan read-only
        // di student-portal.apply.show, gantinya <select> dropdown).
        //
        // ?degree=... (versi SEBELUMNYA, tombol per Degree tab) TETAP
        // didukung di sini sebagai fallback untuk kompatibilitas kalau ada
        // link lama yang masih beredar/di-bookmark -- tapi halaman profile
        // sekarang tidak pernah generate link seperti itu lagi.
        //
        // SENGAJA cuma filter tampilan (bukan keamanan) -- store() di bawah
        // tetap validasi degree_intake_id itu benar milik $profile ini,
        // apapun query string-nya. Kalau ?course=/?degree= tidak dikirim,
        // kosong, atau tidak cocok (mis. link lama/salah ketik/Course sudah
        // dihapus admin), fallback ke SEMUA degree seperti sebelum
        // perubahan ini -- jadi behaviour lama tetap jalan.
        $selectedCourseId = $request->query('course');
        $selectedDegree = $request->query('degree');
        $degreeOptions = $profile->degrees;
        $lockedCourse = null;

        if (filled($selectedCourseId)) {
            $lockedCourse = $profile->degrees->firstWhere('id', $selectedCourseId);

            if ($lockedCourse) {
                $degreeOptions = collect([$lockedCourse]);
                $selectedDegree = $lockedCourse->degree;
            }
        }

        if (! $lockedCourse && filled($selectedDegree)) {
            $filtered = $profile->degrees->filter(fn ($row) => $row->degree === $selectedDegree)->values();

            if ($filtered->isNotEmpty()) {
                $degreeOptions = $filtered;
            } else {
                $selectedDegree = null;
            }
        }

        if (! $request->user()) {
            session(['url.intended' => $request->fullUrl()]);

            return redirect()
                ->route('register')
                ->with('status', 'Silakan daftar akun dulu untuk melanjutkan Apply ke ' . $profile->university->name . '. Sudah punya akun? Klik "Sign in here" di bawah, nanti otomatis kembali ke halaman Apply.');
        }

        $user = $request->user();

        if (! $user->hasVerifiedEmail() || ! $user->isActive()) {
            session(['url.intended' => $request->fullUrl()]);

            return redirect()->route('verification.notice');
        }

        // Registration Fee auto-ditampilkan dari baris Payment yang ditag
        // fee_type = 'registration_fee' oleh admin (lihat migration
        // add_fee_type_to_university_profile_payments_table & perubahan di
        // Quiz\UniversityProfileController) -- kalau belum ada yang ditag,
        // tetap fallback null (form tidak error, cuma tidak menampilkan
        // nominalnya).
        //
        // FIX v2 (permintaan user, 16 September 2026): Registration Fee
        // sekarang PRIORITAS diambil dari Course yang di-lock ($lockedCourse,
        // lihat filter ?course= di atas) -- itu nominal yang BENAR-BENAR akan
        // di-snapshot ke aplikasi begitu siswa submit (lihat store()). Baris
        // Payment fee_type='registration_fee' di atas ($registrationFee)
        // tetap dipertahankan & tetap dihitung di sini sebagai fallback
        // tampilan kalau Course-nya belum diisi Registration Fee sendiri
        // (mis. data lama) -- BUG (16 September 2026): baris $registrationFee
        // ini sempat KE-HAPUS tidak sengaja saat menambahkan
        // $courseRegistrationFeeAmount, menyebabkan "Undefined variable
        // $registrationFee" & 500 di production begitu siswa klik Apply dari
        // course manapun. Dikembalikan di sini.
        $registrationFee = $profile->payments->firstWhere('fee_type', 'registration_fee');
        $courseRegistrationFeeAmount = optional($lockedCourse)->registration_fee_amount;

        $student = Student::where('user_id', $user->id)->first();

        return view('student-portal.apply.show', [
            'profile' => $profile,
            'degreeOptions' => $degreeOptions,
            'selectedDegree' => $selectedDegree,
            'lockedCourse' => $lockedCourse,
            'registrationFee' => $registrationFee,
            'courseRegistrationFeeAmount' => $courseRegistrationFeeAmount,
            // Jurusan yang dikunci tapi Registration Fee-nya belum diatur admin: tombol Apply dimatikan + notifikasi.
            'lockedCourseFeeMissing' => $lockedCourse && (int) $lockedCourse->registration_fee_amount <= 0,
            'feeMissingMessage' => self::FEE_MISSING_MESSAGE,
            'remainingSlots' => $this->remainingSlots($student),
            'defaultWhatsapp' => $student->handphone ?? $user->handphone ?? '',
            'student' => $student,
        ]);
    }

    /** Cart "Pilihan Kampus" (12 November 2026): disimpan di session, maksimal 2 kampus sekali bayar (26 November 2026: dari 3 jadi 2). */
    private const CART = 'apply_cart';

    /**
     * Batas kampus (26 November 2026): maksimal 2 aplikasi AKTIF per siswa, termasuk yang sudah dibayar dan yang
     * masih dalam batas waktu bayar 1 jam. Aplikasi yang batal (oleh admin atau otomatis karena tidak dibayar)
     * dan yang sudah selesai tidak dihitung, jadi slotnya langsung bisa dipakai apply lagi.
     */
    public const CART_MAX = 2;

    public const FEE_MISSING_MESSAGE = 'The Registration Fee for this program has not been set by our team yet, so it cannot be applied for right now. Please contact our team on WhatsApp, or choose another program.';

    /** Sisa slot kampus siswa (CART_MAX dikurangi aplikasi aktif). Aplikasi yang lewat batas bayar dibatalkan dulu. */
    private function remainingSlots(?Student $student): int
    {
        if (! $student) {
            return self::CART_MAX;
        }

        UniversityApplication::cancelOverdue($student->id);

        return max(0, self::CART_MAX - $student->applications()->active()->count());
    }

    private function limitReachedMessage(): string
    {
        return 'You already have '.self::CART_MAX.' active university applications, which is the maximum. '
            .'An unpaid application is cancelled automatically 1 hour after checkout, and you can apply again after that.';
    }

    /**
     * Submit form Apply -- jurusan yang dipilih masuk ke cart "Pilihan Kampus" (12 November 2026).
     * Aplikasi baru dibuat saat checkout, lalu Registration Fee semua kampus dibayar sekaligus.
     */
    public function store(Request $request, string $universityProfileId): RedirectResponse
    {
        $profile = UniversityProfile::with('university')
            ->where('status', 'active')
            ->findOrFail($universityProfileId);

        $validated = $request->validate([
            'degree_intake_id' => 'required|uuid|exists:university_profile_degrees,id',
            'intake_year' => 'required|integer|min:' . now()->year . '|max:' . (now()->year + 5),
            'whatsapp' => 'required|string|max:20',
            // Opsional (13 Oktober 2026), disimpan ke data Student.
            'name_pasport' => 'nullable|string|max:100',
            'number_pasport' => 'nullable|string|max:100',
        ]);

        $degreeRow = UniversityProfileDegree::where('id', $validated['degree_intake_id'])
            ->where('university_profile_id', $profile->id)
            ->firstOrFail();

        // FIX keamanan (14 Oktober 2026): Student milik akun ini diambil lewat
        // resolveForUser() -- nomor WhatsApp yang diketik di form ini TIDAK
        // lagi dipakai untuk mencocokkan ke Student lain (dulu bisa "menyambar"
        // data Student orang lain cukup dengan mengetik nomornya). Nomor ini
        // tetap disimpan di aplikasi & mengisi HP Student kalau masih kosong.
        $student = (new StudentIdentityResolver())->resolveForUser($request->user(), $validated['whatsapp']);

        if (! $student) {
            return redirect()
                ->route('student-portal.apply.show', $profile->id)
                ->with('apply_conflict', StudentIdentityResolver::CONFLICT_MESSAGE);
        }

        // Batas bayar 1 jam (26 November 2026): aplikasi yang lewat batas dibatalkan dulu, supaya tidak menghalangi apply lagi.
        UniversityApplication::cancelOverdue($student->id);

        // Jurusan & intake yang sudah pernah diajukan tidak dibuat dua kali, supaya tidak bayar dobel.
        if ($existing = $this->activeApplication($student, $degreeRow->id, (int) $validated['intake_year'])) {
            return redirect()
                ->route('student-portal.applications.show', $existing->id)
                ->with('success', 'You have already applied to this program for the '.$validated['intake_year'].' intake, so here is your application.');
        }

        // Registration Fee belum diatur admin (26 November 2026): tidak bisa masuk daftar, beri notifikasi jelas.
        if ((int) $degreeRow->registration_fee_amount <= 0) {
            return redirect()
                ->route('student-portal.apply.show', [$profile->id, 'course' => $degreeRow->id])
                ->with('apply_error', self::FEE_MISSING_MESSAGE);
        }

        // Data paspor opsional: yang diisi saja yang disimpan, isian kosong
        // tidak menghapus data yang sudah ada.
        $student->savePassport(array_filter([
            'name_pasport' => $validated['name_pasport'] ?? null,
            'number_pasport' => $validated['number_pasport'] ?? null,
        ], 'filled'));

        $cart = session(self::CART, []);
        $key = $degreeRow->id.':'.$validated['intake_year'];
        // Batas 2 kampus dihitung bersama aplikasi yang masih aktif (26 November 2026), bukan cuma isi daftar.
        $slots = $this->remainingSlots($student);

        if ($slots <= 0) {
            return redirect()
                ->route('student-portal.apply.cart')
                ->with('cart_error', $this->limitReachedMessage());
        }

        if (! isset($cart[$key]) && count($cart) >= $slots) {
            return redirect()
                ->route('student-portal.apply.cart')
                ->with('cart_error', $slots < self::CART_MAX
                    ? 'You can apply to '.self::CART_MAX.' universities in total, and you already have an active application, so only '.$slots.' more can be added. Remove one from your list first if you would like to swap it.'
                    : 'You can choose up to '.self::CART_MAX.' universities at once. Remove one first if you would like to swap it.');
        }

        $cart[$key] = ['course_id' => $degreeRow->id, 'intake_year' => (int) $validated['intake_year'], 'whatsapp' => $validated['whatsapp']];
        session([self::CART => $cart]);

        return redirect()
            ->route('student-portal.apply.cart')
            ->with('cart_added', $profile->university->name);
    }

    /** Halaman cart: daftar kampus pilihan, perkiraan biaya, pilih kampus lagi / hapus / kosongkan / checkout. */
    public function cart(Request $request): View
    {
        $items = $this->cartItems();
        $student = Student::where('user_id', $request->user()->id)->first();
        // Sekaligus membatalkan aplikasi yang lewat batas bayar 1 jam (26 November 2026).
        $slots = $this->remainingSlots($student);
        $otherApplications = $student
            ? $student->applications()->where('status', '!=', UniversityApplication::STATUS_CANCELLED)->count()
            : 0;

        // Aplikasi aktif yang belum dibayar (masih dalam batas 1 jam): ikut ditagih sekaligus saat checkout
        // (lihat ApplicationPaymentController::payingApplications), jadi ikut ditampilkan & dijumlahkan di sini.
        $unpaid = $student
            ? UniversityApplication::with('university')->where('student_id', $student->id)->active()
                ->whereNull('registration_fee_due')
                ->whereDoesntHave('payments', fn ($query) => $query->where('purpose', ApplicationPayment::PURPOSE_REGISTRATION_FEE)
                    ->where('status', ApplicationPayment::STATUS_PAID))
                ->latest('submitted_at')->get()
            : collect();

        return view('student-portal.apply.cart', [
            'items' => $items,
            'unpaid' => $unpaid,
            // Lebih dari 1 kampus (termasuk yang sudah pernah diajukan): tiap kampus cukup DP 50%.
            'isDeposit' => $items->count() + $otherApplications > 1,
            // Sisa slot (26 November 2026): batas 2 kampus dihitung bersama aplikasi yang masih aktif.
            'max' => $slots,
            'limit' => self::CART_MAX,
            'activeCount' => self::CART_MAX - $slots,
            'overLimit' => $items->count() > $slots,
            'hasMissingFee' => $items->contains(fn (array $item) => (int) $item['course']->registration_fee_amount <= 0),
            'feeMissingMessage' => self::FEE_MISSING_MESSAGE,
            'paymentWindow' => UniversityApplication::PAYMENT_WINDOW_MINUTES,
        ]);
    }

    public function removeFromCart(string $key): RedirectResponse
    {
        session([self::CART => array_diff_key(session(self::CART, []), [$key => true])]);

        return redirect()->route('student-portal.apply.cart')->with('cart_status', 'The university has been removed from your list.');
    }

    public function clearCart(): RedirectResponse
    {
        session()->forget(self::CART);

        return redirect()->route('student-portal.apply.cart')->with('cart_status', 'Your list is empty now.');
    }

    /**
     * Checkout: buat aplikasi untuk tiap kampus di cart, lalu ke halaman bayar. Registration Fee semua
     * kampus yang belum dibayar ditagih dalam satu transaksi (lihat ApplicationPaymentController).
     */
    public function checkout(Request $request): RedirectResponse
    {
        $items = $this->cartItems();

        if ($items->isEmpty()) {
            return redirect()->route('student-portal.apply.cart')->with('cart_status', 'Your list is empty. Choose a university first.');
        }

        // Registration Fee belum diatur admin (26 November 2026): checkout ditahan dengan notifikasi, daftar tidak diubah.
        $missingFee = $items->filter(fn (array $item) => (int) $item['course']->registration_fee_amount <= 0);

        if ($missingFee->isNotEmpty()) {
            return redirect()->route('student-portal.apply.cart')->with('cart_error', self::FEE_MISSING_MESSAGE
                .' Please remove: '.$missingFee->map(fn (array $item) => $item['course']->profile->university->name)->implode(', ').'.');
        }

        $student = (new StudentIdentityResolver())->resolveForUser($request->user(), $items->first()['whatsapp']);

        if (! $student) {
            return redirect()->route('student-portal.apply.cart')->with('cart_error', StudentIdentityResolver::CONFLICT_MESSAGE);
        }

        // Batas bayar 1 jam: aplikasi yang lewat batas dibatalkan dulu, supaya slotnya terhitung kosong.
        UniversityApplication::cancelOverdue($student->id);

        $applications = DB::transaction(function () use ($items, $student) {
            // Kunci data student supaya klik "Checkout" dua kali tidak membuat aplikasi dobel.
            Student::whereKey($student->id)->lockForUpdate()->first();

            // Batas 2 kampus aktif (26 November 2026), dicek ulang di dalam kunci supaya tidak bisa diakali dua tab.
            $new = $items->reject(fn (array $item) => $this->activeApplication($student, $item['course']->id, $item['intake_year']));

            if ($new->count() > max(0, self::CART_MAX - $student->applications()->active()->count())) {
                return null;
            }

            return $items->map(fn (array $item) => $this->activeApplication($student, $item['course']->id, $item['intake_year'])
                ?? UniversityApplication::create(UniversityApplication::courseSnapshot($item['course']) + [
                    'application_no' => (new ApplicationNumberGenerator())->next(),
                    'student_id' => $student->id,
                    'intake_year' => $item['intake_year'],
                    'whatsapp' => $item['whatsapp'],
                    // Snapshot Registration Fee dari Course (16 September 2026), lihat UniversityProfileDegree.
                    'registration_fee_amount' => $item['course']->registration_fee_amount,
                    'status' => UniversityApplication::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                    // Bayar dalam 1 jam, kalau tidak dibatalkan otomatis (UniversityApplication::cancelOverdue).
                    'payment_deadline_at' => now()->addMinutes(UniversityApplication::PAYMENT_WINDOW_MINUTES),
                ]));
        });

        if ($applications === null) {
            $slots = $this->remainingSlots($student);

            return redirect()->route('student-portal.apply.cart')->with('cart_error', $slots <= 0
                ? $this->limitReachedMessage()
                : 'You can apply to '.self::CART_MAX.' universities in total, and you already have an active application, so only '.$slots.' more can be added. Please remove one from your list to continue.');
        }

        session()->forget(self::CART);

        // Registration Fee dulu (Formulir & Dokumen terbuka setelah lunas, lihat ApplicationDocumentController).
        return redirect()
            ->route('student-portal.applications.payment.show', [$applications->first()->id, ApplicationPayment::PURPOSE_REGISTRATION_FEE])
            ->with('success', 'Your application'.($applications->count() > 1 ? 's have' : ' has').' been created: '.$applications->pluck('application_no')->implode(', ').'.');
    }

    /** Isi cart yang masih valid (jurusan & program aktif), lengkap dengan kampusnya. */
    private function cartItems(): Collection
    {
        $cart = session(self::CART, []);
        $courses = UniversityProfileDegree::with('profile.university')
            ->whereIn('id', array_column($cart, 'course_id'))
            ->whereHas('profile', fn ($query) => $query->where('status', 'active'))
            ->get()
            ->keyBy('id');

        return collect($cart)
            ->filter(fn (array $item) => $courses->has($item['course_id']))
            ->map(fn (array $item, string $key) => $item + ['key' => $key, 'course' => $courses[$item['course_id']]]);
    }

    private function activeApplication(Student $student, string $courseId, int $intakeYear): ?UniversityApplication
    {
        return UniversityApplication::where('student_id', $student->id)
            ->where('degree_intake_id', $courseId)
            ->where('intake_year', $intakeYear)
            ->where('status', '!=', UniversityApplication::STATUS_CANCELLED)
            ->first();
    }
}
