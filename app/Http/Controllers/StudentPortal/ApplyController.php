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
                ->with('status', 'Silakan daftar akun dulu untuk melanjutkan Apply ke ' . $profile->university->name . '.');
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
        // Payment fee_type='registration_fee' di atas tetap dipertahankan
        // cuma sebagai fallback tampilan kalau Course-nya belum diisi
        // Registration Fee sendiri (mis. data lama).
        $courseRegistrationFeeAmount = optional($lockedCourse)->registration_fee_amount;

        $student = Student::where('user_id', $user->id)->first();

        return view('student-portal.apply.show', [
            'profile' => $profile,
            'degreeOptions' => $degreeOptions,
            'selectedDegree' => $selectedDegree,
            'lockedCourse' => $lockedCourse,
            'registrationFee' => $registrationFee,
            'courseRegistrationFeeAmount' => $courseRegistrationFeeAmount,
            'defaultWhatsapp' => $student->handphone ?? $user->handphone ?? '',
        ]);
    }

    /**
     * Submit form Apply -- buat 1 baris university_applications baru untuk
     * Student milik user yang login, snapshot degree/intake/duration/
     * language/registration_fee_amount dari pilihan saat submit (lihat
     * catatan snapshot di migration create_university_applications_table).
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
        ]);

        $degreeRow = UniversityProfileDegree::where('id', $validated['degree_intake_id'])
            ->where('university_profile_id', $profile->id)
            ->firstOrFail();

        $user = $request->user();

        // Cari-atau-buatkan Student (CRM) untuk user ini -- logic PERSIS
        // SAMA dengan yang dipakai registrasi & quiz-wizard (lihat
        // App\Services\StudentIdentityResolver), supaya identitas Student
        // tetap satu walaupun nomor WhatsApp yang diisi di form Apply ini
        // berbeda dari nomor saat register.
        $student = (new StudentIdentityResolver())->findOrCreate([
            'name' => $user->name,
            'email' => $user->email,
            'handphone' => $validated['whatsapp'],
        ]);

        if (empty($student->user_id)) {
            $student->user_id = $user->id;
            $student->save();
        } elseif ($student->user_id !== $user->id) {
            // Nomor WhatsApp (atau email) yang diisi di form ini ternyata
            // sudah terhubung ke akun LAIN (StudentIdentityResolver
            // mencocokkan Student lewat email ATAU handphone -- lihat
            // findOrCreate()). SENGAJA tidak dipindah/ditimpa otomatis ke
            // akun yang sedang login, supaya data CRM milik akun lain itu
            // tidak "kerebut" diam-diam.
            //
            // Tapi jangan diteruskan bikin UniversityApplication di sini --
            // kalau diteruskan, siswa yang baru submit ini pasti ke-block
            // 403 tanpa penjelasan begitu buka halaman upload dokumennya
            // sendiri (lihat
            // ApplicationDocumentController::ownedApplicationOrFail()),
            // karena aplikasi itu bakal ke-link ke Student yang bukan
            // miliknya. Jadi dihentikan di sini dengan pesan jelas: minta
            // dia login pakai akun yang sudah terdaftar itu, bukan bikin
            // akun/aplikasi baru.
            return redirect()
                ->route('student-portal.apply.show', $profile->id)
                ->with('apply_conflict', 'Maaf, nomor WhatsApp ini sudah terdaftar di akun lain. Jika ini nomor Anda sendiri, silakan logout lalu login menggunakan akun tersebut untuk melanjutkan Apply. Jika Anda merasa ini bukan Anda, silakan hubungi admin kami.');
        }

        // FIX v2 (permintaan user, 16 September 2026): registration_fee_amount
        // TIDAK LAGI diisi manual admin SETELAH siswa submit (alur FASE 2 yang
        // lama) -- sekarang di-snapshot LANGSUNG dari Registration Fee yang
        // sudah ditentukan admin DI DEPAN per Course ($degreeRow, lihat
        // UniversityProfileDegree::registration_fee_amount & form Degree &
        // Course di Quiz\UniversityProfileController), supaya begitu siswa
        // submit, langsung bisa lanjut ke halaman pembayaran tanpa nunggu
        // admin isi nominal dulu. Kalau admin belum sempat isi Registration
        // Fee Course ini (masih null), ApplicationPaymentController::init()
        // tetap menolak transaksi dengan pesan jelas -- SAMA seperti perilaku
        // sebelumnya kalau nominal kosong, cuma sumbernya sekarang dari Course.
        $application = UniversityApplication::create([
            'application_no' => (new ApplicationNumberGenerator())->next(),
            'student_id' => $student->id,
            'university_profile_id' => $profile->id,
            // FIX (permintaan user, 16 September 2026): $degreeRow->id
            // (degree_intake_id) dulu cuma dipakai buat validasi
            // "exists:university_profile_degrees,id" lalu dibuang -- tidak
            // pernah ikut disimpan ke aplikasi. course_name-nya (jurusan
            // yang siswa pilih di select "Program / Major") malah TIDAK
            // PERNAH disimpan sama sekali. Sekarang keduanya ikut
            // di-snapshot, supaya begitu 1 Program punya lebih dari 1
            // Course dengan Degree/Intake/Duration yang SAMA, admin tetap
            // bisa tahu persis jurusan mana yang dipilih siswa (lihat
            // migration add_course_snapshot_to_university_applications_table).
            'degree_intake_id' => $degreeRow->id,
            'course_name' => $degreeRow->course_name,
            'university_id' => $profile->university_id,
            'degree' => $degreeRow->degree,
            'language' => $profile->language,
            'intake' => $degreeRow->intake,
            'intake_year' => $validated['intake_year'],
            'duration' => $degreeRow->duration,
            'whatsapp' => $validated['whatsapp'],
            'registration_fee_amount' => $degreeRow->registration_fee_amount,
            'status' => UniversityApplication::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        // FASE 2 -- sebelum Fase 2, tujuan redirect ini adalah langsung ke
        // halaman upload dokumen (student-portal.applications.documents.edit).
        // Sekarang harus lewat gerbang pembayaran Registration Fee dulu (Step
        // 3 di alur yang disepakati): Study Plan & Upload Documents (Step 1 &
        // 2 versi lama, sekarang jadi tahap SETELAH bayar) baru terbuka
        // setelah ApplicationPayment purpose=registration_fee berstatus
        // "paid" (ditandai oleh webhook gateway, lihat
        // FormPaymentController::onApplicationPaymentPaid()). Guard di sisi
        // ApplicationDocumentController::edit()/update() menolak akses kalau
        // belum lunas (lihat komentar di sana).
        return redirect()
            ->route('student-portal.applications.payment.show', [$application->id, ApplicationPayment::PURPOSE_REGISTRATION_FEE])
            ->with('success', 'Aplikasi berhasil dikirim! Nomor aplikasi Anda: ' . $application->application_no);
    }
}
