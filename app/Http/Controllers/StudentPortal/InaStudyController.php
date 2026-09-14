<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\ApplicationPayment;
use App\Models\DocumentType;
use App\Models\Student;
use App\Models\University;
use App\Models\UniversityApplication;
use App\Models\UniversityProfile;
use App\Services\ApplicationNumberGenerator;
use App\Services\StudentIdentityResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman "InaStudy" (14 September 2026, permintaan user) -- DIPISAH dari
 * DashboardController/dashboard.index supaya dashboard umum (menu
 * "Dashboard" di sidebar) tetap cuma berisi Academic Calendar, sementara
 * widget "My University Applications" + alur Register manual (lihat
 * docblock registerApplication() di bawah) sekarang punya halaman sendiri
 * yang dituju langsung oleh menu "InaStudy" di sidebar (bukan lari ke
 * route('dashboard') lagi -- lihat resources/views/layouts/partials/
 * sidebar.blade.php).
 *
 * Sebelumnya (versi awal fitur ini) seluruh logic di bawah sempat hidup di
 * App\Http\Controllers\DashboardController -- riwayat lengkap alasan
 * bypass Registration Fee ada di docblock registerApplication().
 */
class InaStudyController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();

        $student = Student::where('user_id', $userId)->first();
        $myApplications = $student
            ? $student->applications()->with(['university', 'universityProfile'])->withCount('documents')->orderByDesc('submitted_at')->get()
            : collect();

        // Sama seperti di halaman admin (Quiz\UniversityApplicationController::index)
        // -- dipakai bareng documents_count di atas untuk progress bar "x / total"
        // dokumen di widget "My University Applications" (lihat diskusi
        // "progress bar student dashboard", 10 September 2026).
        $totalDocumentTypes = DocumentType::active()->count();

        // FASE "InaStudy Register Manual" (14 September 2026, permintaan user):
        // widget "My University Applications" di atas SEKARANG selalu
        // ditampilkan (bukan cuma kalau ada data) supaya siswa yang belum
        // punya aplikasi sama sekali bisa langsung Register dari sini --
        // tanpa lewat Registration Fee (lihat registerApplication() di
        // bawah). $registerUniversities cuma perlu di-load untuk user yang
        // punya Student (staff biasa yang tidak punya Student tidak akan
        // pernah melihat widget/tombol Register-nya di view, tapi tidak
        // relevan lagi di halaman ini karena route ini cuma bisa dituju
        // lewat menu InaStudy yang memang hanya tampil untuk siswa), tapi
        // query-nya ringan (cuma universitas + profile aktif) jadi aman
        // dijalankan sekalian di sini tanpa dicek $student dulu.
        $registerUniversities = University::query()
            ->where('status', 'active')
            ->with(['profiles' => function ($query) {
                $query->where('status', 'active')
                    ->orderBy('field')
                    ->select('id', 'university_id', 'field', 'degree_title');
            }])
            ->orderBy('name')
            ->get(['id', 'name']);

        // Dipakai view untuk memutuskan tampil/tidaknya widget "My University
        // Applications" sama sekali -- staff biasa yang entah bagaimana bisa
        // membuka halaman ini (tidak punya Student) tidak akan melihat
        // widget/tombol Register.
        $hasStudent = (bool) $student;

        return view('student-portal.inastudy.index', compact('myApplications', 'totalDocumentTypes', 'registerUniversities', 'hasStudent'));
    }

    /**
     * FASE "InaStudy Register Manual" (14 September 2026) -- registrasi
     * Aplikasi Kuliah MANUAL langsung dari halaman InaStudy, tanpa lewat
     * halaman publik Apply Kampus (App\Http\Controllers\StudentPortal\
     * ApplyController) dan TANPA gerbang Registration Fee (yang mewajibkan
     * pembayaran gateway sungguhan) -- sesuai permintaan eksplisit user
     * ("hilangkan pembayaran tapi jangan merubah struktur databasenya ya,
     * nnt akan terpakai terus").
     *
     * Caranya: bikin baris UniversityApplication seperti biasa (field
     * degree/intake/dst dibiarkan kosong -- semuanya nullable, lihat migration
     * create_university_applications_table), LALU langsung bikinkan satu
     * baris ApplicationPayment ber-status PAID (amount 0, payment_method
     * 'manual') untuk purpose registration_fee. Ini otomatis "membuka"
     * ApplicationFormController & ApplicationDocumentController, karena
     * blockIfRegistrationFeeUnpaid() di kedua controller itu cuma mengecek
     * ADA/TIDAKNYA baris ApplicationPayment berstatus paid -- TIDAK ada
     * satupun kolom/tabel baru yang ditambahkan, semua tabel & controller
     * lama (termasuk ApplyController, ApplicationFormController,
     * ApplicationDocumentController) tetap 100% tidak diubah/disentuh.
     */
    public function registerApplication(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'university_id' => ['required', 'uuid', 'exists:universities,id'],
            'university_profile_id' => ['required', 'uuid', 'exists:university_profiles,id'],
        ]);

        $profile = UniversityProfile::where('id', $validated['university_profile_id'])
            ->where('university_id', $validated['university_id'])
            ->first();

        if (!$profile) {
            return redirect()
                ->route('inastudy.index')
                ->with('status', 'Jurusan yang dipilih tidak sesuai dengan universitas yang dipilih. Silakan coba lagi.');
        }

        $user = $request->user();

        // Hampir semua User login sudah otomatis punya Student terhubung
        // (dibuat saat register/Google login, lihat
        // RegisteredUserController::store() & GoogleAuthController::callback()).
        // StudentIdentityResolver di sini cuma jaring pengaman untuk kasus
        // langka User lama yang belum sempat ke-link.
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

        // Register cuma boleh dipakai selagi belum ada Aplikasi Kuliah sama
        // sekali -- tombolnya di view juga cuma tampil kalau daftar aplikasi
        // masih kosong, ini guard sisi server-nya (jaga-jaga submit ulang
        // lewat tab lama/devtools).
        $hasApplication = UniversityApplication::where('student_id', $student->id)->exists();

        if ($hasApplication) {
            return redirect()
                ->route('inastudy.index')
                ->with('status', 'Anda sudah memiliki Aplikasi Kuliah. Silakan lanjutkan dari daftar aplikasi Anda.');
        }

        $application = UniversityApplication::create([
            'application_no' => (new ApplicationNumberGenerator())->next(),
            'student_id' => $student->id,
            'university_profile_id' => $profile->id,
            'university_id' => $validated['university_id'],
            'status' => UniversityApplication::STATUS_SUBMITTED,
            'admission_status' => UniversityApplication::ADMISSION_STATUS_UNDER_REVIEW,
            'submitted_at' => now(),
        ]);

        // "Bayar" otomatis (amount 0, manual) -- lihat docblock method ini di
        // atas untuk alasan lengkapnya.
        ApplicationPayment::create([
            'application_id' => $application->id,
            'purpose' => ApplicationPayment::PURPOSE_REGISTRATION_FEE,
            'order_id' => 'MANUAL-' . $application->application_no,
            'amount' => 0,
            'status' => ApplicationPayment::STATUS_PAID,
            'payment_method' => 'manual',
            'paid_at' => now(),
        ]);

        return redirect()
            ->route('inastudy.index')
            ->with('success', 'Registrasi berhasil! Silakan lanjutkan mengisi Formulir & upload dokumen.');
    }
}
