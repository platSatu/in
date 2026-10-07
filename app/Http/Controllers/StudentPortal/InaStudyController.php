<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\Student;
use App\Models\UniversityProfileDegree;
use App\Services\InaStudy\ManualApplicationRegistrar;
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
    public function __construct(private readonly ManualApplicationRegistrar $registrar)
    {
    }

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
        //
        // FIX v2 (permintaan user, 16 September 2026): dropdown "Jurusan" di
        // sini dulu memilih langsung 1 UniversityProfile (Major, mis.
        // "Aeronautical Engineering"), TIDAK sinkron lagi dengan struktur
        // Apply dari halaman publik (frontend.university-profile) yang
        // sekarang sudah 3 langkah: pilih Kampus -> pilih Degree -> pilih
        // Jurusan (Course/UniversityProfileDegree, lihat ApplyController).
        // Disamakan di sini: tiap University di-map ke daftar "courses"
        // (flatten dari SEMUA Course di SEMUA Major aktif universitas itu),
        // supaya JS di view bisa filter 2 tingkat (Degree lalu Jurusan)
        // persis seperti alur Apply -- $registerDegreeOrder dipakai supaya
        // urutan pilihan Degree tetap konsisten (Diploma/Bachelor/Master/
        // PhD), bukan urutan sembarang hasil query.
        $registerUniversities = $this->registrar->options();

        $registerDegreeOrder = UniversityProfileDegree::DEGREES;

        // Dipakai view untuk memutuskan tampil/tidaknya widget "My University
        // Applications" sama sekali -- staff biasa yang entah bagaimana bisa
        // membuka halaman ini (tidak punya Student) tidak akan melihat
        // widget/tombol Register.
        $hasStudent = (bool) $student;

        // Nomor awal kolom WhatsApp di form Register -- sama dengan form Apply.
        $defaultWhatsapp = $student->handphone ?? Auth::user()->handphone ?? '';

        return view('student-portal.inastudy.index', compact('myApplications', 'totalDocumentTypes', 'registerUniversities', 'registerDegreeOrder', 'hasStudent', 'defaultWhatsapp'));
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
        // FIX v2 (permintaan user, 16 September 2026): selection-nya sekarang
        // 3 langkah (University -> Degree -> Jurusan/Course) sama seperti
        // Apply dari halaman publik -- lihat docblock index() di atas &
        // ApplyController::store() untuk pola validasi yang sama persis
        // (degree_intake_id harus benar-benar milik university_id yang
        // dikirim, SENGAJA dicek di server, bukan cuma andalan filter
        // tampilan di JS). university_profile_id TIDAK perlu dikirim dari
        // form lagi -- diturunkan otomatis dari Course ($degreeRow->profile).
        $validated = $request->validate($this->registrar->rules());

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

        // Validasi jurusan, guard "sudah punya aplikasi", pembuatan aplikasi
        // + payment manual: lihat App\Services\InaStudy\ManualApplicationRegistrar
        // (dipakai juga tombol "Add to InaStudy" admin di index Student).
        $this->registrar->register($student, $validated);

        return redirect()
            ->route('inastudy.index')
            ->with('success', 'Registrasi berhasil! Silakan lanjutkan mengisi Formulir & upload dokumen.');
    }
}
