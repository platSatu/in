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
        $registrationFee = $profile->payments->firstWhere('fee_type', 'registration_fee');

        $student = Student::where('user_id', $user->id)->first();

        return view('student-portal.apply.show', [
            'profile' => $profile,
            'registrationFee' => $registrationFee,
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

        // FASE 2 (Alur Pembayaran 2 Arah) -- registration_fee_amount TIDAK LAGI
        // di-auto-isi dari UniversityProfilePayment (fee_type='registration_fee')
        // seperti sebelumnya. Nominal Registration Fee sekarang WAJIB diisi
        // manual oleh admin per-aplikasi (lihat ApplicationPaymentController::
        // init(), yang menolak transaksi kalau nominal ini masih kosong/0).
        // Sengaja dibiarkan null di sini -- diisi admin lewat halaman detail
        // aplikasi (Quiz\UniversityApplicationController) sebelum siswa bisa
        // membayar.
        $application = UniversityApplication::create([
            'application_no' => (new ApplicationNumberGenerator())->next(),
            'student_id' => $student->id,
            'university_profile_id' => $profile->id,
            'university_id' => $profile->university_id,
            'degree' => $degreeRow->degree,
            'language' => $profile->language,
            'intake' => $degreeRow->intake,
            'intake_year' => $validated['intake_year'],
            'duration' => $degreeRow->duration,
            'whatsapp' => $validated['whatsapp'],
            'registration_fee_amount' => null,
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
