<?php
/*
|--------------------------------------------------------------------------
| CATATAN PERUBAHAN
|--------------------------------------------------------------------------
| Yang baru ditambahkan (cari komentar "=== MAJOR ===" untuk lompat langsung):
|
| 1. formWizard(): sekarang juga mengirim $majors ke view, dipakai untuk
|    render dropdown pada pertanyaan bertipe 'major'.
|
| 2. formWizardSubmit(): di dalam foreach($questions) ditambah cabang
|    elseif ($question->type === 'major') — jawabannya (major_id) disimpan
|    ke FormAnswer seperti tipe lain, lalu id-nya dikumpulkan ke
|    $selectedMajorIds untuk dipakai setelah loop.
|
| 3. Setelah ringkasan jawaban dibangun, dipanggil
|    buildMajorUniversitiesMessage($selectedMajorIds) yang query tabel
|    setting_universities (major_id -> university_id) dan membentuk section
|    pesan WhatsApp baru berisi daftar universitas untuk major yang dipilih.
|    Section ini digabung ke $message SEBELUM bagian rekomendasi yang sudah
|    ada (matchUniversities), jadi dua-duanya tetap jalan berdampingan.
|
| YANG PERLU DISIAPKAN DI LUAR FILE INI:
| - Model Major: pastikan ada (App\Models\Major).
| - Model SettingUniversity: tambahkan relasi
|       public function university() { return $this->belongsTo(University::class); }
|       public function major()      { return $this->belongsTo(Major::class); }
| - Di form builder admin (controller/validasi yang membuat FormQuestion),
|   pastikan value type 'major' termasuk yang diizinkan, dan untuk tipe ini
|   admin TIDAK perlu membuat FormQuestionOption manual (optionnya otomatis
|   dari tabel majors).
| - Sesuaikan value status yang dianggap "aktif" di setting_universities
|   kalau bukan string 'active'.
|
| === WHATSAPP TEMPLATE ===
| - Model Form: tambahkan relasi belongsTo ke WhatsappTemplate lewat kolom
|   whatsapp_template_id yang sudah ditambahkan ke tabel forms:
|       public function whatsappTemplate()
|       {
|           return $this->belongsTo(\App\Models\WhatsappTemplate::class);
|       }
| - Pesan WhatsApp sekarang TIDAK lagi hardcode di controller. Kalau form
|   sudah dipasangi whatsapp_template_id, isi pesannya diambil dari
|   $form->whatsappTemplate->content, lalu placeholder berikut diganti
|   otomatis oleh buildMessageFromTemplate():
|     {{name}}               -> nama user
|     {{form_name}}          -> nama form
|     {{ringkasan_jawaban}}  -> daftar pertanyaan & jawaban
|     {{universitas_major}}  -> daftar kampus dari major yang dipilih
| - Kalau form BELUM punya whatsapp_template_id (null), sistem otomatis
|   fallback ke format pesan default (persis seperti sebelumnya) supaya
|   form lama yang belum dipasangi template tetap jalan normal.
|
| === REFACTOR: HAPUS ALGORITMA REKOMENDASI BERBASIS PREFERENSI/BUDGET ===
| - saveUserPreferences(), matchUniversities(), calculateMatchScore(),
|   formatBudgetRange(), dan buildRecommendationsMessage() DIHAPUS total —
|   sudah tidak dipakai lagi (termasuk semua hitungan budget).
| - Placeholder {{rekomendasi_kampus}} ikut dihapus dari sistem. Kalau ada
|   template WhatsApp lama yang masih menulis {{rekomendasi_kampus}} di
|   content-nya, tulisan itu tidak akan terganti (muncul apa adanya) —
|   hapus placeholder itu manual dari template terkait.
| - Pesan WhatsApp sekarang murni: template + {{ringkasan_jawaban}} (semua
|   pertanyaan & jawaban form yang dipilih) + {{universitas_major}}.
*/

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\ClassSchedule;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormPayment;
use App\Models\FormQuestion;
use App\Models\FormQuestionOption;
use App\Models\FormResult;
use App\Models\FormSection;
use App\Models\FormSubmission;
use App\Models\Major;
use App\Models\Student;
use App\Models\SettingUniversity;
use App\Models\User;
use App\Models\University;
use App\Models\UniversityProfile;
use App\Models\UniversityAlbum;
use App\Models\WhatsappGateway;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class FrontendController extends Controller
{
    /**
     * Batas & jenis file yang diizinkan untuk jawaban pertanyaan tipe 'file'
     * (lihat cabang $question->type === 'file' di saveQuestionAnswers()).
     */
    private const QUESTION_FILE_ALLOWED_MIMES = 'jpg,jpeg,png,pdf';
    private const QUESTION_FILE_MAX_KB = 5120; // 5MB

    public function index()
    {
        return view('frontend.index');
    }

    public function universitiesByCity(Request $request, string $city)
    {
        $universities = University::whereHas('city', function ($query) use ($city) {
                $query->where('name', $city);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'logo']);
    
        return response()->json([
            'city' => $city,
            'universities' => $universities->map(function ($university) {
                return [
                    'id' => $university->id,
                    'name' => $university->name,
                    'logo' => (!empty($university->logo) && file_exists(public_path($university->logo)))
                        ? asset($university->logo)
                        : null,
                    'detail_url' => route('frontend.university.profile', $university->id),
                ];
            }),
        ]);
    }

     public function handbook()
    {
        $universities = University::whereNotNull('attachment')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('frontend.handbook', compact('universities'));
    }

    public function handbookDownload(string $id)
    {
        $university = University::whereNotNull('attachment')->findOrFail($id);

        $path = public_path($university->attachment);

        if (!file_exists($path)) {
            abort(404);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $downloadName = \Illuminate\Support\Str::slug($university->name) . '.' . $extension;

        return response()->download($path, $downloadName);
    }
    /**
     * Show University Profile Page
     */
    // public function universityProfile($id)
    // {
    //     $university = University::findOrFail($id);
    //     $profile = UniversityProfile::where('university_id', $id)
    //         ->where('status', 'active')
    //         ->firstOrFail();

    //     return view('frontend.university-profile', compact('university', 'profile'));
    // }
    public function universityCatalog(Request $request)
    {
        $search = $request->query('search');
        $cityId = $request->query('city_id');
        $majorId = $request->query('major_id');
        $type = $request->query('type'); // "All Types" filter -> field/program (UniversityProfile::field)
        $scholarship = $request->query('scholarship'); // '1' = hanya yang ada beasiswa

        $universitiesQuery = University::where('status', 'active')->orderBy('name');

        if ($search) {
            // Kolom `city` di tabel universities dipakai sebagai FK ke cities.id
            // (lihat catatan di City::universities()/University::city()), jadi
            // pencarian nama kota harus lewat whereHas ke relasi city, bukan
            // LIKE ke kolom `city` itu sendiri (yang isinya UUID, bukan teks).
            $universitiesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhereHas('city', function ($cityQuery) use ($search) {
                        $cityQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($cityId)) {
            $universitiesQuery->where('city', $cityId);
        }

        if (!empty($majorId)) {
            $universitiesQuery->where('major_id', $majorId);
        }

        // Filter "All Types" (Field/program studi) dan Scholarship sama-sama
        // hidup di tabel university_profiles (bukan kolom langsung di
        // universities), jadi keduanya digabung lewat satu whereHas ke
        // relasi profiles().
        if (!empty($type) || !empty($scholarship)) {
            $universitiesQuery->whereHas('profiles', function ($q) use ($type, $scholarship) {
                $q->where('status', 'active');

                if (!empty($type)) {
                    $q->where('field', 'like', "%{$type}%");
                }

                if (!empty($scholarship)) {
                    $q->where('scholarship_available', true);
                }
            });
        }

        $universities = $universitiesQuery->get();

        // Resolve relasi city() secara eksplisit (bukan lewat magic property
        // $item->city) — kolom `city` di tabel universities punya nama yang
        // sama persis dengan nama relasinya, jadi $item->city SELALU
        // mengembalikan nilai kolom mentah (UUID), bukan objek City, walaupun
        // relasinya sudah di-load() di sini. View mengambil city-nya lewat
        // $item->getRelation('city'), sama seperti perbaikan di halaman admin.
        $universities->load('city');

        $profiles = UniversityProfile::where('status', 'active')
            ->whereIn('university_id', $universities->pluck('id'))
            ->get()
            ->keyBy('university_id');

        // Daftar field/program unik untuk dropdown "All Types" — diambil dari
        // SEMUA profile aktif (bukan cuma dari hasil yang sudah difilter),
        // supaya pilihan di dropdown-nya tetap lengkap apapun filter yang
        // sedang aktif.
        $allFields = UniversityProfile::where('status', 'active')
            ->whereNotNull('field')
            ->pluck('field')
            ->flatMap(fn ($field) => array_map('trim', explode(',', $field)))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Dropdown City & Major juga menampilkan SEMUA pilihan aktif (bukan
        // cuma yang match hasil saat ini), konsisten dengan pola filter
        // dropdown di halaman admin (Student, dsb).
        $cities = City::where('status', 'active')->orderBy('name')->get();
        $majors = Major::where('status', 'active')->orderBy('name')->get();

        return view('frontend.university-catalog', compact(
            'universities',
            'profiles',
            'search',
            'allFields',
            'cities',
            'majors',
            'cityId',
            'majorId',
            'type',
            'scholarship'
        ));
    }


    public function universityProfile($id)
    {
        $university = University::findOrFail($id);

        // Eager-load 'degrees' (tabel anak university_profile_degrees) & 'payments'
        // (tabel anak university_profile_payments, sama polanya) supaya section
        // Degree/Intake/Duration dan Payment di halaman ini bisa tampil — profile
        // itu sendiri TIDAK punya kolom degree/intake/payment langsung (lihat
        // catatan di UniversityProfile::degrees()/payments()), datanya sepenuhnya
        // di tabel anak masing-masing.
        $profile = UniversityProfile::where('university_id', $id)
            ->where('status', 'active')
            ->with(['degrees', 'payments'])
            ->first(); // pakai first(), bukan firstOrFail()

        $albums = UniversityAlbum::where('university_id', $id)
            ->where('status', 'active')
            ->with(['photos' => function ($query) {
                $query->where('status', 'active')
                    ->orderBy('sort_order');
            }])
            ->get();

        // Kolom `city` di tabel universities juga jadi nama relasi city() —
        // $university->city (magic property) SELALU mengembalikan nilai
        // kolom mentah (UUID), bukan objek City (sama persis kasusnya dengan
        // halaman admin quiz.university.show). Ambil eksplisit lewat method
        // city() supaya nama kotanya bisa ditampilkan dengan benar.
        $cityModel = $university->city()->first();

        return view('frontend.university-profile', compact('university', 'profile', 'albums', 'cityModel'));
    }

    /**
     * Form Wizard - Show form selection or direct to wizard
     */
    public function formWizard(Request $request)
    {
        return $this->buildFormWizardView($request->query('form_id'));
    }

    /**
     * URL cantik untuk booth: /quiz/{branchSlug}/{boothSlug}
     * Contoh: inagroup.asia/quiz/mall-of-indonesia/a1
     */
    public function formWizardBySlug(string $branchSlug, string $boothSlug)
    {
        // publiclyAccessible() = status 'active' DAN (kalau diisi) sekarang ada
        // di antara start_date..end_date. Kalau tidak match sama sekali (baik
        // karena inactive, belum mulai, atau sudah lewat), 404 seperti sebelumnya.
        $selectedForm = Form::where('slug', $branchSlug)
            ->where('booth_slug', $boothSlug)
            ->publiclyAccessible()
            ->firstOrFail();

        return $this->buildFormWizardView($selectedForm->id);
    }

    /**
     * Data & view yang dipakai bareng oleh formWizard() (?form_id=) dan
     * formWizardBySlug() (/quiz/{branchSlug}/{boothSlug}).
     */
    private function buildFormWizardView(?string $formId)
    {
        // Get all available forms
        $forms = Form::publiclyAccessible()
            ->orderBy('created_at', 'desc')
            ->get();

        // === MAJOR ===
        // Dipakai untuk render dropdown pada pertanyaan bertipe 'major' di step 2.
        $majors = Major::orderBy('name')->get();

        // If specific form selected, get its questions
        $selectedForm = null;
        $questions = collect();
        $personalDataQuestions = collect();
        $placementTestQuestions = collect();
        $sections = collect();

        // === PREFILL SETELAH KEMBALI DARI GATEWAY PEMBAYARAN ===
        // Wizard ini satu <form> besar yang me-reload PENUH halaman waktu user
        // kembali dari Midtrans/Duitku/iPaymu (?order_id=...) — semua input step
        // sebelumnya (Full Name/Email/WhatsApp) otomatis KOSONG lagi kalau tidak
        // di-prefill manual, padahal JS auto-lompat ke step Payment lalu langsung
        // ke step Questions begitu status "paid" terdeteksi (lihat DOMContentLoaded
        // di form-wizard.blade.php). Akibatnya submit akhir mengirim name/email/
        // handphone KOSONG, gagal validasi required di formWizardSubmit(), dan
        // karena JS itu juga tidak berhenti di step Info untuk menampilkan error-nya,
        // dari sisi user kelihatannya "submit tidak ngapa-ngapain". Data name/email/
        // handphone yang asli sebenarnya sudah tersimpan di form_payments (diisi
        // waktu init pembayaran) — jadi dipakai lagi di sini untuk isi ulang field-nya.
        $paymentPrefill = null;
        $orderId = request()->query('order_id');

        if ($orderId && $formId) {
            $paymentPrefill = FormPayment::where('order_id', $orderId)
                ->where('form_id', $formId)
                ->first();
        }

        if ($formId) {
            // Dulu ini Form::find($formId) tanpa cek status sama sekali, jadi form
            // inactive tetap bisa diakses selama tahu ID-nya lewat ?form_id=. Sekarang
            // ikut disaring publiclyAccessible() juga (status + jadwal start/end_date).
            $selectedForm = Form::publiclyAccessible()->find($formId);
            if ($selectedForm) {
                // Hit counter sederhana: +1 setiap form ini benar-benar ditampilkan ke publik.
                $selectedForm->increment('view_count');

                $questions = FormQuestion::where('form_id', $formId)
                    ->where('status', 'active')
                    ->with('options')
                    ->orderBy('order')
                    ->get();

                // === STAGE "DATA PRIBADI" ===
                // Dipisah dari daftar flat $questions berdasarkan stage_group, supaya
                // wizard bisa render dua step terpisah (lihat frontend/form-wizard.blade.php).
                // Kalau has_personal_data_stage nonaktif, $personalDataQuestions dibiarkan
                // kosong (step-nya tidak dirender sama sekali di blade) meskipun ada
                // pertanyaan lama yang kebetulan bertanda personal_data.
                //
                // === PERTANYAAN BERCABANG (conditional/nested questions) ===
                // $questions di atas masih FLAT berisi SEMUA pertanyaan aktif form ini,
                // termasuk pertanyaan "anak" (parent_option_id terisi). Loop render
                // top-level di form-wizard.blade.php cuma boleh menampilkan pertanyaan
                // ROOT (parent_option_id kosong) — pertanyaan anak dirender belakangan,
                // secara rekursif, lewat @include di dalam question-card.blade.php
                // sendiri begitu opsi pemicunya muncul di antara $question->options.
                $rootQuestions = $questions->filter(fn ($q) => $q->parent_option_id === null);

                if ($selectedForm->has_personal_data_stage) {
                    $personalDataQuestions = $rootQuestions->where('stage_group', 'personal_data')->values();
                }
                $placementTestQuestions = $rootQuestions->where('stage_group', 'placement_test')->values();

                // Section aktif milik form ini (lihat blok "SECTION" di bawah untuk
                // bagaimana ini dipakai mengelompokkan $placementTestQuestions).
                $sections = FormSection::where('form_id', $formId)
                    ->where('status', 'active')
                    ->orderBy('order')
                    ->orderBy('created_at')
                    ->get();
            }
        }

        // === SECTION (pengelompokan tampilan Placement Test) ===
        // Section sifatnya opsional per form (lihat FormSection & migration
        // create_form_sections_table) — kalau form ini BELUM punya section sama
        // sekali, $placementTestGroups dibiarkan kosong dan
        // frontend/form-wizard.blade.php akan merender $placementTestQuestions
        // flat seperti sebelum fitur ini ada (TIDAK ada perubahan tampilan sama
        // sekali untuk form yang sudah berjalan).
        //
        // Kalau form ini punya section, pertanyaan dikelompokkan jadi beberapa
        // "halaman": pertanyaan yang belum ditempatkan ke section mana pun
        // (section_id null) muncul lebih dulu TANPA judul (halaman transisi,
        // supaya tidak ada soal yang ke-skip/hilang selama admin belum selesai
        // mengelompokkan semuanya), baru disusul section-section bernama sesuai
        // urutannya. Section yang kebetulan tidak punya pertanyaan aktif
        // dilewati saja (tidak ada gunanya jadi halaman kosong).
        $placementTestGroups = collect();

        if ($selectedForm && $sections->isNotEmpty()) {
            $ungroupedQuestions = $placementTestQuestions->whereNull('section_id')->values();
            if ($ungroupedQuestions->isNotEmpty()) {
                $placementTestGroups->push((object) [
                    'id' => null,
                    'name' => null,
                    'description' => null,
                    'questions' => $ungroupedQuestions,
                ]);
            }

            foreach ($sections as $section) {
                $sectionQuestions = $placementTestQuestions->where('section_id', $section->id)->values();
                if ($sectionQuestions->isNotEmpty()) {
                    $placementTestGroups->push((object) [
                        'id' => $section->id,
                        'name' => $section->name,
                        'description' => $section->description,
                        'questions' => $sectionQuestions,
                    ]);
                }
            }
        }

        return view('frontend.form-wizard', compact(
            'forms',
            'selectedForm',
            'questions',
            'personalDataQuestions',
            'placementTestQuestions',
            'placementTestGroups',
            'majors',
            'paymentPrefill'
        ));
    }

    /**
     * Form Wizard - Submit form submission
     */
    // public function formWizardSubmit(Request $request)
    // {
    //     $validated = $request->validate([
    //         'form_id' => 'required|exists:forms,id',
    //         'name' => 'required|string|max:255',
    //         'handphone' => 'required|string|max:20',
    //     ]);

    //     // Create or find user by phone (for tracking)
    //     $user = User::where('handphone', $validated['handphone'])->first();

    //     if (!$user) {
    //         // Create temporary user for this submission
    //         // User model has booted() method that auto-generates UUID
    //         $user = Student::create([
    //             'name' => $validated['name'],
    //             'handphone' => $validated['handphone'],
    //             'email' => $validated['email'],
    //         ]);
    //     }

    //     // Get form for message building
    //     $form = Form::find($validated['form_id']);

    //     // Create submission record
    //     // FormSubmission model uses HasUuids trait, so UUID will be auto-generated
    //     $submission = FormSubmission::create([
    //         'user_id' => $user->id,
    //         'form_id' => $validated['form_id'],
    //         'status' => 'active',
    //     ]);

    //     // Get form questions
    //     $questions = FormQuestion::where('form_id', $validated['form_id'])
    //         ->where('status', 'active')
    //         ->with('options')
    //         ->orderBy('order')
    //         ->get();

    //     // Build answers summary for WhatsApp message
    //     $answersSummary = [];
    //     $questionNumber = 1;

    //     // === MAJOR ===
    //     // Kumpulkan major_id yang dipilih user di sini, dipakai setelah loop
    //     // untuk mencari universitas terkait via tabel setting_universities.
    //     $selectedMajorIds = [];

    //     // Process each question answer
    //     foreach ($questions as $question) {
    //         $questionKey = 'question_' . $question->id;
    //         $answerValue = null;

    //         if ($question->type === 'text' || $question->type === 'number') {
    //             $answerText = $request->input($questionKey);
    //             $answerValue = $answerText ?: '-';

    //             if ($answerText) {
    //                 FormAnswer::create([
    //                     'user_id' => $user->id,
    //                     'submission_id' => $submission->id,
    //                     'question_id' => $question->id,
    //                     'option_id' => null,
    //                     'answer_text' => $answerText,
    //                     'status' => 'active',
    //                 ]);
    //             }
    //         } elseif ($question->type === 'single_choice') {
    //             $optionId = $request->input($questionKey);

    //             if ($optionId) {
    //                 $option = FormQuestionOption::find($optionId);
    //                 $answerValue = $option ? $option->option_text : '-';

    //                 FormAnswer::create([
    //                     'user_id' => $user->id,
    //                     'submission_id' => $submission->id,
    //                     'question_id' => $question->id,
    //                     'option_id' => $optionId,
    //                     'answer_text' => null,
    //                     'status' => 'active',
    //                 ]);
    //             } else {
    //                 $answerValue = '-';
    //             }
    //         } elseif ($question->type === 'multiple_choice') {
    //             $optionIds = $request->input($questionKey, []);
    //             $selectedOptions = [];

    //             foreach ($optionIds as $optionId) {
    //                 $option = FormQuestionOption::find($optionId);
    //                 if ($option) {
    //                     $selectedOptions[] = $option->option_text;

    //                     FormAnswer::create([
    //                         'user_id' => $user->id,
    //                         'submission_id' => $submission->id,
    //                         'question_id' => $question->id,
    //                         'option_id' => $optionId,
    //                         'answer_text' => null,
    //                         'status' => 'active',
    //                     ]);
    //                 }
    //             }

    //             $answerValue = !empty($selectedOptions) ? implode(', ', $selectedOptions) : '-';
    //         } elseif ($question->type === 'major') {
    //             // === MAJOR ===
    //             // Optionnya bukan dari form_question_options, tapi id dari tabel majors.
    //             $majorId = $request->input($questionKey);
    //             $major = $majorId ? Major::find($majorId) : null;
    //             $answerValue = $major ? $major->name : '-';

    //             if ($majorId && $major) {
    //                 FormAnswer::create([
    //                     'user_id' => $user->id,
    //                     'submission_id' => $submission->id,
    //                     'question_id' => $question->id,
    //                     'option_id' => null,
    //                     // simpan id-nya (bukan nama) supaya bisa dipakai lookup university nanti
    //                     'answer_text' => $majorId,
    //                     'status' => 'active',
    //                 ]);

    //                 $selectedMajorIds[] = $majorId;
    //             }
    //         }

    //         $answersSummary[] = "{$questionNumber}. {$question->question_text}\n   Jawaban: {$answerValue}";
    //         $questionNumber++;
    //     }

    //     // Ringkasan jawaban jadi satu blok teks, dipakai untuk isi placeholder {{ringkasan_jawaban}}
    //     $ringkasanJawaban = implode("\n", $answersSummary);

    //     // === MAJOR ===
    //     // Trigger: begitu ada major yang dipilih (dari pertanyaan bertipe 'major'),
    //     // cari universitas terkait via setting_universities. Hasilnya dipakai untuk
    //     // isi placeholder {{universitas_major}}. Kalau tidak ada major yang dipilih,
    //     // placeholder ini cukup diganti string kosong.
    //     $universitasMajorMessage = '';
    //     if (!empty($selectedMajorIds)) {
    //         $universitasMajorMessage = $this->buildMajorUniversitiesMessage(array_unique($selectedMajorIds));
    //     }

    //     // === WHATSAPP TEMPLATE ===
    //     // Susun pesan akhir dari whatsapp_template milik form yang dipilih ($form),
    //     // dengan fallback ke format default kalau form belum dipasangi template.
    //     // Pesannya berisi template + ringkasan pertanyaan-jawaban + universitas dari major
    //     // (algoritma rekomendasi berbasis preferensi/budget sudah dihapus, lihat catatan di atas).
    //     $message = $this->buildMessageFromTemplate($form, [
    //         'name' => $user->name,
    //         'form_name' => $form->name,
    //         'ringkasan_jawaban' => $ringkasanJawaban,
    //         'universitas_major' => $universitasMajorMessage,
    //     ]);

    //     // Send WhatsApp message
    //     $this->sendWhatsapp($user->handphone, $message);

    //     return redirect()
    //         ->route('frontend.form.wizard')
    //         ->with('success', 'Thank you! Your form has been submitted successfully.');
    // }
    public function formWizardSubmit(Request $request)
    {
        Log::info('[FORM-WIZARD] === START formWizardSubmit ===', [
            'all_input' => $request->all(),
            'ip' => $request->ip(),
        ]);

        try {
            $validated = $request->validate([
                'form_id' => 'required|exists:forms,id',
                'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s.\'-]+$/u'],
                'email' => ['required', 'email', 'max:255'],
                'handphone' => ['required', 'digits_between:9,16'],
                'payment_order_id' => ['nullable', 'string', 'max:50'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Kalau ini yang muncul di log, berarti request GAGAL validasi dan
            // tidak pernah sampai ke logic Student::create() sama sekali.
            Log::warning('[FORM-WIZARD] Validasi gagal, request dibatalkan di sini', [
                'errors' => $e->errors(),
            ]);

            throw $e; // biarkan Laravel handle redirect-back-with-errors seperti biasa
        }

        Log::info('[FORM-WIZARD] Validasi lolos', ['validated' => $validated]);

        $form = Form::find($validated['form_id']);

        if (!$form) {
            abort(404);
        }

        // === PAYMENT GATE ===
        // Kalau form ini butuh pembayaran, placement test/pertanyaan HANYA boleh
        // disimpan kalau ada FormPayment berstatus "paid" untuk form + order ini.
        // Status "paid" itu sendiri HANYA pernah diset oleh webhook resmi gateway
        // (lihat FormPaymentController::handleWebhook), tidak pernah oleh request
        // browser biasa — jadi ini bukan sekadar validasi UI, tapi gerbang di server.
        $payment = null;

        if ($form->requires_payment) {
            $payment = FormPayment::where('order_id', $validated['payment_order_id'] ?? null)
                ->where('form_id', $form->id)
                ->where('status', 'paid')
                ->whereNull('form_submission_id')
                ->first();

            if (!$payment) {
                Log::warning('[FORM-WIZARD] Submit ditolak, pembayaran belum terkonfirmasi', [
                    'form_id' => $form->id,
                    'payment_order_id' => $validated['payment_order_id'] ?? null,
                ]);

                return redirect()
                    ->route('frontend.form.wizard', ['form_id' => $form->id])
                    ->withErrors(['payment' => 'Pembayaran belum terkonfirmasi. Silakan selesaikan pembayaran terlebih dahulu.'])
                    ->withInput();
            }
        }

        $student = $this->findOrCreateStudent($validated);

        // === STUDENT BRANCH/FORM TRACKING ===
        // Simpan branch & form yang baru diisi student ini di tabel students, dipakai
        // untuk filter di halaman admin Student (index). Ini "singgahan terakhir" saja
        // (row students dipakai bareng lintas form via handphone) — history LENGKAP
        // tiap submission (termasuk yang sebelum-sebelumnya) tetap utuh lewat relasi
        // Student::formSubmissions(), lihat StudentController::show().
        $student->update([
            'branch_id' => $form->branch_id,
            'form_id' => $form->id,
            // Dicatat bareng branch_id di atas (lihat App\Helpers\DataScope) —
            // supaya staff dengan role scope 'division' bisa ikut melihat
            // student ini di menu admin, konsisten dengan cakupan form yang
            // baru saja diisi student ini.
            'company_division_id' => $form->company_division_id,
        ]);

        Log::info('[FORM-WIZARD] Lanjut ke proses FormSubmission & FormAnswer', [
            'student_id' => $student->id,
        ]);

        // Create submission record
        $submission = FormSubmission::create([
            'user_id' => $student->id,
            'form_id' => $validated['form_id'],
            'status' => 'active',
        ]);

        Log::info('[FORM-WIZARD] FormSubmission dibuat', ['submission_id' => $submission->id]);

        // Kunci FormPayment ini ke submission yang baru dibuat, supaya order_id yang
        // sama tidak bisa dipakai lagi untuk submit form kedua kalinya.
        if ($payment) {
            $payment->update(['form_submission_id' => $submission->id]);
        }

        // Get form questions
        $questions = FormQuestion::where('form_id', $validated['form_id'])
            ->where('status', 'active')
            ->with('options')
            ->orderBy('order')
            ->get();

        // === RESULT (auto mode) ===
        // Kalau form ini result_mode='auto', skor dihitung dari kolom
        // form_question_options.score milik opsi yang dipilih peserta — TAPI hanya
        // untuk pertanyaan stage_group='placement_test' (bukan pertanyaan data
        // pribadi). Kolom score sendiri sudah ada & bisa diisi admin sejak awal,
        // cuma sebelumnya tidak pernah dipakai/dijumlahkan di mana pun.
        $isAutoResultForm = $form->result_mode === 'auto';

        // === RESULT (section_threshold / gaya HSK) ===
        // Sama pola dengan $isAutoResultForm di atas: penanda ini murni dipakai
        // finalizeCompletedSubmission() untuk memutuskan apakah perlu menjalankan
        // computeSectionThresholdResult() — TIDAK mengubah apa pun di alur
        // penyimpanan jawaban (saveQuestionAnswers/processQuestionBranch/
        // saveSingleQuestionAnswer tetap identik untuk semua result_mode).
        $isSectionThresholdForm = $form->result_mode === 'section_threshold';

        $answers = $this->saveQuestionAnswers($request, $submission, $student, $questions, $isAutoResultForm);
        $ringkasanJawaban = $answers['ringkasan'];
        $autoScore = $answers['autoScore'];
        $selectedMajorIds = $answers['selectedMajorIds'];

        $universitasMajorMessage = '';
        if (!empty($selectedMajorIds)) {
            $universitasMajorMessage = $this->buildMajorUniversitiesMessage(array_unique($selectedMajorIds));
        }

        $callbackLink = $this->finalizeCompletedSubmission(
            $form,
            $student,
            $submission,
            $ringkasanJawaban,
            $autoScore,
            $universitasMajorMessage,
            $isAutoResultForm,
            $isSectionThresholdForm,
            $payment
        );

        Log::info('[FORM-WIZARD] === END formWizardSubmit, redirect sukses ===', [
            'student_id' => $student->id,
        ]);

        return redirect($this->buildWizardRedirectRoute($form))
            ->with('success', 'Thank you! Your form has been submitted successfully.')
            ->with('callback_link', $callbackLink);
    }

    /**
     * URL untuk balik ke wizard form yang sama setelah submit selesai — pakai URL
     * cantik /quiz/{slug}/{boothSlug} kalau form-nya punya itu (sama seperti pola
     * link Preview di quiz/form/index.blade.php), fallback ke ?form_id= kalau tidak.
     * Dipakai bareng oleh formWizardSubmit() dan formWizardTimeoutSave() (timeout
     * yang jadi percobaan terakhir, lihat docblock method itu).
     */
    private function buildWizardRedirectRoute(Form $form): string
    {
        return ($form->slug && $form->booth_slug)
            ? route('frontend.form.wizard.slug', ['branchSlug' => $form->slug, 'boothSlug' => $form->booth_slug])
            : route('frontend.form.wizard', ['form_id' => $form->id]);
    }

    /**
     * Tahap akhir "penyelesaian resmi" satu submission: skor auto (kalau result_mode
     * 'auto'), callback link (kalau diaktifkan & payment gate-nya lolos), dan kirim
     * WhatsApp (kalau use_whatsapp_notification aktif). Dipakai bareng oleh
     * formWizardSubmit() (submit manual lewat tombol) dan formWizardTimeoutSave()
     * ketika timeout itu jadi percobaan TERAKHIR (timer_auto_restart mati) — di titik
     * itu timeout diperlakukan identik dengan submit manual biasa.
     *
     * @return string|null  callback link yang siap ditampilkan/dikirim ke peserta
     */
    private function finalizeCompletedSubmission(
        Form $form,
        Student $student,
        FormSubmission $submission,
        string $ringkasanJawaban,
        float $autoScore,
        string $universitasMajorMessage,
        bool $isAutoResultForm,
        bool $isSectionThresholdForm,
        ?FormPayment $payment
    ): ?string {
        // === RESULT (auto mode) ===
        // Kalau result_mode='auto', hasil (skor) langsung dihitung & disimpan di sini,
        // sesaat setelah submission tersimpan — tidak perlu tindakan admin apa pun.
        // Untuk result_mode='manual', TIDAK ada FormResult yang dibuat di titik ini;
        // baris form_results untuk submission ini baru akan ada setelah admin mengisi
        // via FormController::saveResult(). result_mode='none' juga tidak membuat apa-apa.
        // Fitur rekomendasi universitas/major ($universitasMajorMessage) berjalan
        // independen, tidak digabung ke sistem hasil ini.
        $hasilMessage = '';
        $formResult = null;

        if ($isAutoResultForm) {
            $formResult = FormResult::updateOrCreate(
                ['form_submission_id' => $submission->id],
                [
                    'form_id' => $form->id,
                    'mode' => 'auto',
                    'score' => $autoScore,
                ]
            );

            $hasilMessage = "Skor Anda: {$autoScore}";
        }

        // === RESULT (section_threshold / gaya HSK) ===
        // Dijalankan PARALEL dengan blok auto di atas (mutually exclusive lewat
        // result_mode, lihat FormController::store()/update()) — sepenuhnya
        // TERPISAH & read-only terhadap FormAnswer (tidak menyentuh
        // saveQuestionAnswers()/processQuestionBranch()/saveSingleQuestionAnswer()
        // sama sekali). Lihat computeSectionThresholdResult() untuk algoritmanya.
        // Kalau form ini belum punya Section top-level sama sekali (admin belum
        // sempat setup section-nya), hasilnya null — TIDAK ada FormResult yang
        // dibuat, sama seperti result_mode='none'.
        $sectionThresholdResult = null;

        if ($isSectionThresholdForm) {
            $sectionThresholdResult = $this->computeSectionThresholdResult($form, $submission);

            if ($sectionThresholdResult) {
                $formResult = FormResult::updateOrCreate(
                    ['form_submission_id' => $submission->id],
                    [
                        'form_id' => $form->id,
                        'mode' => 'section_threshold',
                        'summary_text' => $sectionThresholdResult->name,
                    ]
                );

                $hasilMessage = "Hasil Anda: {$sectionThresholdResult->name}";
            }
        }

        // Dipakai di beberapa titik di bawah (pilih_kelas_link, tanda waktu kirim
        // WA) untuk menyatakan "hasil sudah pasti diketahui saat ini juga" —
        // true untuk auto (skor barusan dihitung) maupun section_threshold yang
        // berhasil menghasilkan sebuah Section (bukan null).
        $hasImmediateResult = $isAutoResultForm || ($isSectionThresholdForm && $sectionThresholdResult !== null);

        // === CALLBACK LINK ===
        // Kalau form ini diaktifkan sebagai "callback" (is_callback_enabled) dan admin
        // sudah mengisi link-nya, link itu BARU boleh disiapkan untuk peserta di titik
        // INI — yaitu setelah FormSubmission di atas benar-benar tersimpan, dan (kalau
        // form requires_payment) setelah $payment di atas sudah lolos gate "paid" yang
        // sumbernya cuma webhook resmi gateway (bukan redirect/klik browser).
        //
        // Sengaja TIDAK ada query/lock tambahan di sini: $payment sudah diverifikasi di
        // payment gate awal caller method ini (early-return kalau belum paid), jadi
        // menghitung ulang di sini tidak menambah beban DB atau membuka celah race
        // condition baru.
        $callbackLink = null;

        if ($form->is_callback_enabled && !empty($form->callback_link)) {
            if ($form->requires_payment) {
                // Form berbayar: link hanya disiapkan kalau $payment sudah lolos gate "paid".
                if ($payment) {
                    $callbackLink = $form->callback_link;
                }
            } else {
                // Form gratis: FormSubmission yang berhasil tersimpan sampai titik ini sudah cukup.
                $callbackLink = $form->callback_link;
            }
        }

        // === PILIH KELAS LINK ===
        // Hanya disiapkan di titik INI kalau result_mode='auto' — hasilnya sudah
        // pasti diketahui saat ini juga (skor barusan dihitung di atas). Untuk
        // result_mode='manual', hasilnya BELUM ada di titik ini (baru ada nanti
        // waktu admin isi lewat FormController::saveResult(), yang punya logic
        // pilih_kelas_link sendiri) — sesuai keputusan awal fitur ini: link
        // "Pilih Kelas" baru muncul "setelah hasil placement test keluar".
        $pilihKelasLink = ($hasImmediateResult && ClassSchedule::existsActiveForBranch($form->branch_id))
            ? route('frontend.class-selection.show', ['submissionId' => $submission->id])
            : '';

        // Kirim WhatsApp HANYA kalau admin mengaktifkan "use_whatsapp_notification" di
        // form ini (lihat toggle di quiz/form/create & edit). Sebelum ini ditambahkan,
        // WA selalu terkirim ke SEMUA form tanpa terkecuali — sekarang jadi opsional
        // per form, sesuai pengaturan admin.
        if ($form->use_whatsapp_notification) {
            $message = $this->buildMessageFromTemplate($form, [
                'name' => trim($student->first_name . ' ' . $student->last_name),
                'form_name' => $form->name,
                'ringkasan_jawaban' => $ringkasanJawaban,
                'universitas_major' => $universitasMajorMessage,
                'callback_link' => $callbackLink ?? '',
                'hasil' => $hasilMessage,
                'pilih_kelas_link' => $pilihKelasLink,
            ]);

            Log::info('[FORM-WIZARD] Sebelum kirim WhatsApp', ['handphone' => $student->handphone]);

            try {
                $this->sendWhatsapp($student->handphone, $message, $form->user_id);
                Log::info('[FORM-WIZARD] sendWhatsapp selesai tanpa exception');

                // Tandai kapan hasil (auto ATAU section_threshold) ini terkirim via WA
                // (dipakai konsisten dengan FormController::saveResult() untuk mode
                // manual, supaya semua mode sama-sama punya jejak waktu pengiriman).
                if ($hasImmediateResult && $formResult) {
                    $formResult->update(['whatsapp_sent_at' => now()]);
                }
            } catch (\Throwable $e) {
                // Kalau sendWhatsapp gagal/lambat/timeout, JANGAN sampai bikin seluruh
                // request dianggap gagal padahal data student/submission sudah tersimpan.
                Log::error('[FORM-WIZARD] sendWhatsapp gagal (data DB tetap aman, ini cuma soal WA)', [
                    'message' => $e->getMessage(),
                ]);
            }
        } else {
            Log::info('[FORM-WIZARD] use_whatsapp_notification nonaktif, WA tidak dikirim', [
                'form_id' => $form->id,
            ]);
        }

        return $callbackLink;
    }

    /**
     * === RESULT (section_threshold / gaya HSK) — MESIN PENILAIAN ===
     *
     * Dipanggil HANYA dari finalizeCompletedSubmission() ketika
     * result_mode='section_threshold'. Method ini READ-ONLY terhadap
     * FormAnswer (cuma query, tidak pernah menulis) — sama sekali tidak
     * menyentuh saveQuestionAnswers()/processQuestionBranch()/
     * saveSingleQuestionAnswer(), karena baris FormAnswer sudah selalu
     * tersimpan lengkap untuk SEMUA result_mode (lihat method-method itu).
     *
     * Algoritma (per Section top-level, berurutan sesuai `order`):
     * 1. Jumlahkan jawaban SALAH di semua Sub Section milik Section ini
     *    ($totalWrong), dan catat Sub Section dengan jawaban salah
     *    TERBANYAK ($maxWrongInOneSubSection).
     * 2. $totalWrong >= section_fail_threshold -> peserta BERHENTI di
     *    Section ini, jadi hasil akhir.
     * 3. $totalWrong <= section_pass_threshold -> peserta LOLOS, lanjut ke
     *    Section berikutnya.
     * 4. Selain dua kondisi di atas (zona di antara pass & fail threshold)
     *    -> lihat sebarannya: kalau salahnya menumpuk di SATU Sub Section
     *    (>= fail_threshold - 1), dianggap BERHENTI (fail) di Section ini;
     *    kalau tersebar di Sub Section berbeda-beda, dianggap LOLOS.
     * 5. Kalau peserta lolos sampai Section TERAKHIR, hasil akhirnya ya
     *    Section terakhir itu (tidak ada "promosi" melebihi Section
     *    terakhir yang tersedia).
     *
     * Section top-level yang tidak punya Sub Section aktif sama sekali
     * dianggap $totalWrong=0 (otomatis lolos) — tidak menyebabkan error,
     * cuma tidak berkontribusi apa-apa ke penilaian.
     *
     * @return FormSection|null  null kalau form ini belum punya Section
     *                           top-level aktif sama sekali (admin belum
     *                           setup section-nya).
     */
    private function computeSectionThresholdResult(Form $form, FormSubmission $submission): ?FormSection
    {
        $topSections = FormSection::where('form_id', $form->id)
            ->whereNull('parent_section_id')
            ->where('status', 'active')
            ->orderBy('order')
            ->orderBy('created_at')
            ->get();

        if ($topSections->isEmpty()) {
            return null;
        }

        $subSections = FormSection::where('form_id', $form->id)
            ->whereNotNull('parent_section_id')
            ->whereIn('parent_section_id', $topSections->pluck('id'))
            ->where('status', 'active')
            ->orderBy('order')
            ->orderBy('created_at')
            ->get()
            ->groupBy('parent_section_id');

        // Cuma 3 tipe pertanyaan ini yang punya konsep "benar/salah" yang jelas
        // (lihat isQuestionAnsweredCorrectly()) — tipe lain (text/number/major/
        // file) TIDAK ikut dihitung sama sekali ke total salah.
        $countableTypes = ['single_choice', 'multiple_choice', 'exact_match'];

        $allSubSectionIds = $subSections->flatten(1)->pluck('id');

        $questionsBySubSection = $allSubSectionIds->isEmpty()
            ? collect()
            : FormQuestion::whereIn('section_id', $allSubSectionIds)
                ->where('status', 'active')
                ->whereIn('type', $countableTypes)
                ->with('options')
                ->orderBy('order')
                ->get()
                ->groupBy('section_id');

        $allQuestionIds = $questionsBySubSection->flatten(1)->pluck('id');

        $answersByQuestion = $allQuestionIds->isEmpty()
            ? collect()
            : FormAnswer::where('submission_id', $submission->id)
                ->whereIn('question_id', $allQuestionIds)
                ->with('option')
                ->get()
                ->groupBy('question_id');

        $failThreshold = $form->section_fail_threshold ?? 3;
        $passThreshold = $form->section_pass_threshold ?? 1;

        $lastSection = null;

        foreach ($topSections as $topSection) {
            $lastSection = $topSection;

            $totalWrong = 0;
            $maxWrongInOneSubSection = 0;

            foreach ($subSections->get($topSection->id, collect()) as $subSection) {
                $wrongCount = 0;

                foreach ($questionsBySubSection->get($subSection->id, collect()) as $question) {
                    $rows = $answersByQuestion->get($question->id, collect());

                    // Pertanyaan yang tidak terjawab sama sekali (tidak ada baris
                    // FormAnswer) dianggap SALAH by design — konsisten dengan
                    // isQuestionAnsweredCorrectly() yang mengembalikan false
                    // kalau $rows kosong.
                    if (!$this->isQuestionAnsweredCorrectly($question, $rows)) {
                        $wrongCount++;
                    }
                }

                $totalWrong += $wrongCount;
                $maxWrongInOneSubSection = max($maxWrongInOneSubSection, $wrongCount);
            }

            if ($totalWrong >= $failThreshold) {
                return $topSection;
            }

            if ($totalWrong <= $passThreshold) {
                continue;
            }

            // Zona di antara pass & fail threshold: lihat sebarannya.
            if ($maxWrongInOneSubSection >= $failThreshold - 1) {
                return $topSection;
            }
        }

        // Lolos sampai Section terakhir -> hasil akhirnya Section terakhir itu.
        return $lastSection;
    }

    /**
     * Benar/salah SATU pertanyaan untuk SATU submission, dipakai
     * computeSectionThresholdResult() di atas. Cuma dipanggil untuk tipe
     * single_choice/multiple_choice/exact_match (lihat $countableTypes di
     * pemanggilnya).
     *
     * - single_choice: benar kalau satu-satunya opsi yang dipilih peserta
     *   ($rows) ditandai is_correct=true oleh admin.
     * - multiple_choice: benar kalau himpunan opsi yang dipilih peserta
     *   SAMA PERSIS dengan himpunan opsi yang ditandai is_correct=true oleh
     *   admin (bukan sekadar "salah satu benar dipilih") — kalau admin
     *   belum menandai satu pun opsi is_correct=true untuk pertanyaan ini,
     *   sengaja SELALU dianggap salah (bukan diam-diam "selalu benar"
     *   karena dua himpunan kosong dianggap sama), supaya konfigurasi yang
     *   belum lengkap kelihatan lewat hasil, bukan lolos diam-diam.
     * - exact_match: benar kalau jawaban peserta (trim) sama persis dengan
     *   correct_answer (trim) — logika sama dengan yang sudah dipakai untuk
     *   mode hasil 'auto' di saveSingleQuestionAnswer().
     *
     * @param  \Illuminate\Support\Collection<int, FormAnswer>  $answerRows
     */
    private function isQuestionAnsweredCorrectly(FormQuestion $question, $answerRows): bool
    {
        if ($question->type === 'single_choice') {
            $row = $answerRows->first();

            return $row !== null && $row->option !== null && (bool) $row->option->is_correct;
        }

        if ($question->type === 'multiple_choice') {
            $correctOptionIds = $question->options
                ->where('is_correct', true)
                ->pluck('id')
                ->sort()
                ->values()
                ->all();

            if (empty($correctOptionIds)) {
                return false;
            }

            $selectedOptionIds = $answerRows
                ->pluck('option_id')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            return $selectedOptionIds === $correctOptionIds;
        }

        if ($question->type === 'exact_match') {
            $row = $answerRows->first();

            if ($row === null || $row->answer_text === null || $question->correct_answer === null) {
                return false;
            }

            return trim($row->answer_text) === trim($question->correct_answer);
        }

        // Tipe lain (text/number/major/file) seharusnya tidak pernah sampai ke
        // sini — sudah difilter lewat $countableTypes di
        // computeSectionThresholdResult(). Dianggap "tidak salah" (netral)
        // sebagai pengaman murni supaya tidak pernah mengurangi skor secara
        // keliru kalau suatu saat dipanggil untuk tipe lain.
        return true;
    }

    /**
     * === TIMER PLACEMENT TEST — AUTO-SAVE SAAT WAKTU HABIS ===
     * Dipanggil via fetch() dari form-wizard.blade.php begitu timer step Placement
     * Test habis DAN admin mengaktifkan toggle "Auto-Save" di form ini (timer_auto_save).
     * Menyimpan jawaban APAPUN/BERAPA PUN yang sempat terisi (server ini memang dari
     * awal tidak pernah menegakkan "required" per pertanyaan, lihat saveQuestionAnswers())
     * — pengecualian ini SENGAJA dipicu oleh timer, bukan berlaku untuk submit manual biasa.
     *
     * Perilakunya bercabang berdasarkan $isFinal = !$form->timer_auto_restart:
     *
     *   - timer_auto_restart AKTIF (bukan percobaan terakhir, akan direset ke soal
     *     pertama lagi oleh JS): perilaku LAMA dipertahankan —
     *       1. FormPayment TIDAK di-lock (form_submission_id tetap NULL) — payment yang
     *          sama masih bisa dipakai untuk percobaan berikutnya, supaya peserta form
     *          berbayar tidak perlu bayar dua kali.
     *       2. TIDAK menghitung skor auto/membuat FormResult, TIDAK mengirim WhatsApp,
     *          TIDAK menyiapkan callback link — ini bukan penyelesaian resmi, cuma jaring
     *          pengaman supaya jawaban yang sempat diisi tidak hilang percuma.
     *       3. Response JSON ringan, JS akan reset semua isian ke kosong (lihat
     *          resetQuestionsStepUI() di form-wizard.blade.php) lalu mulai timer baru.
     *
     *   - timer_auto_restart MATI (INI percobaan terakhir): diperlakukan IDENTIK dengan
     *     submit manual (formWizardSubmit()) — payment di-lock, skor/FormResult/WA/callback
     *     link disiapkan lewat finalizeCompletedSubmission(), dan response JSON membawa
     *     redirect_url supaya JS menavigasi ke halaman "Thank you" yang sama persis
     *     seperti submit manual (bukan berhenti di layar "Waktu habis!" begitu saja).
     */
    public function formWizardTimeoutSave(Request $request)
    {
        try {
            $validated = $request->validate([
                'form_id' => 'required|exists:forms,id',
                'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s.\'-]+$/u'],
                'email' => ['required', 'email', 'max:255'],
                'handphone' => ['required', 'digits_between:9,16'],
                'payment_order_id' => ['nullable', 'string', 'max:50'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Data belum lengkap, jawaban tidak disimpan.', 'errors' => $e->errors()], 422);
        }

        $form = Form::find($validated['form_id']);

        if (!$form) {
            return response()->json(['message' => 'Form tidak ditemukan.'], 404);
        }

        if (!$form->timer_enabled || !$form->timer_auto_save) {
            // Jaga-jaga kalau endpoint ini dipanggil langsung (bukan dari flow
            // timer JS yang semestinya) padahal admin tidak mengaktifkan fitur ini.
            return response()->json(['message' => 'Fitur auto-save timer tidak aktif untuk form ini.'], 422);
        }

        // Percobaan terakhir kalau admin TIDAK mengaktifkan auto-restart — lihat docblock.
        $isFinal = !$form->timer_auto_restart;

        // === PAYMENT GATE ===
        // Sama seperti formWizardSubmit(): kalau form ini requires_payment, hanya boleh
        // menyimpan jawaban kalau ada FormPayment "paid" untuk order ini. Payment ini
        // baru di-lock di bawah kalau $isFinal (lihat catatan di docblock atas).
        $payment = null;

        if ($form->requires_payment) {
            $payment = FormPayment::where('order_id', $validated['payment_order_id'] ?? null)
                ->where('form_id', $form->id)
                ->where('status', 'paid')
                ->whereNull('form_submission_id')
                ->first();

            if (!$payment) {
                return response()->json(['message' => 'Pembayaran belum terkonfirmasi, jawaban tidak disimpan.'], 422);
            }
        }

        $student = $this->findOrCreateStudent($validated);

        $student->update([
            'branch_id' => $form->branch_id,
            'form_id' => $form->id,
            // Dicatat bareng branch_id di atas (lihat App\Helpers\DataScope) —
            // supaya staff dengan role scope 'division' bisa ikut melihat
            // student ini di menu admin, konsisten dengan cakupan form yang
            // baru saja diisi student ini.
            'company_division_id' => $form->company_division_id,
        ]);

        $submission = FormSubmission::create([
            'user_id' => $student->id,
            'form_id' => $form->id,
            'status' => 'active',
            'is_timeout_partial' => true,
        ]);

        // Kunci FormPayment ke submission ini HANYA kalau ini percobaan terakhir — sama
        // seperti formWizardSubmit(), supaya order_id yang sama tidak bisa dipakai lagi.
        if ($isFinal && $payment) {
            $payment->update(['form_submission_id' => $submission->id]);
        }

        $questions = FormQuestion::where('form_id', $form->id)
            ->where('status', 'active')
            ->with('options')
            ->orderBy('order')
            ->get();

        // isAutoResultForm hanya true kalau ini percobaan terakhir DAN form-nya
        // result_mode='auto' — skor auto cuma dihitung sekali, saat penyelesaian resmi.
        $isAutoResultForm = $isFinal && $form->result_mode === 'auto';

        // Sama pola dengan $isAutoResultForm — hasil section_threshold juga cuma
        // dihitung sekali, saat ini benar-benar jadi percobaan TERAKHIR.
        $isSectionThresholdForm = $isFinal && $form->result_mode === 'section_threshold';

        $answers = $this->saveQuestionAnswers($request, $submission, $student, $questions, $isAutoResultForm);

        Log::info('[FORM-WIZARD] Timeout auto-save tersimpan', [
            'form_id' => $form->id,
            'submission_id' => $submission->id,
            'student_id' => $student->id,
            'is_final' => $isFinal,
        ]);

        if (!$isFinal) {
            // Bukan percobaan terakhir — jawaban sudah aman tersimpan, JS akan reset
            // tampilan ke kosong & mulai timer baru. Tidak ada skor/WA/callback di sini.
            return response()->json([
                'ok' => true,
                'final' => false,
                'submission_id' => $submission->id,
            ]);
        }

        // === PERCOBAAN TERAKHIR — perlakukan identik dengan submit manual ===
        $universitasMajorMessage = '';
        if (!empty($answers['selectedMajorIds'])) {
            $universitasMajorMessage = $this->buildMajorUniversitiesMessage(array_unique($answers['selectedMajorIds']));
        }

        $callbackLink = $this->finalizeCompletedSubmission(
            $form,
            $student,
            $submission,
            $answers['ringkasan'],
            $answers['autoScore'],
            $universitasMajorMessage,
            $isAutoResultForm,
            $isSectionThresholdForm,
            $payment
        );

        session()->flash('success', 'Waktu habis — jawaban Anda sudah otomatis tersimpan. Terima kasih!');
        if ($callbackLink) {
            session()->flash('callback_link', $callbackLink);
        }

        Log::info('[FORM-WIZARD] === END formWizardTimeoutSave (final), redirect sukses ===', [
            'student_id' => $student->id,
        ]);

        return response()->json([
            'ok' => true,
            'final' => true,
            'submission_id' => $submission->id,
            'redirect_url' => $this->buildWizardRedirectRoute($form),
        ]);
    }

    /**
     * Cari Student berdasarkan handphone, atau buat baru kalau belum ada. Dipakai
     * bareng oleh formWizardSubmit() (submit lengkap) dan formWizardTimeoutSave()
     * (auto-save saat timer habis).
     */
    private function findOrCreateStudent(array $validated): Student
    {
        Log::info('[FORM-WIZARD] Cek DB connection aktif', [
            'connection' => config('database.default'),
            'database' => DB::connection()->getDatabaseName(),
        ]);

        try {
            $existingStudent = Student::where('handphone', $validated['handphone'])->first();

            Log::info('[FORM-WIZARD] Hasil cek Student existing', [
                'found' => $existingStudent ? true : false,
                'existing_student_id' => $existingStudent->id ?? null,
            ]);

            $nameParts = preg_split('/\s+/', trim($validated['name']), 2);

            $payload = [
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? '',
                'email' => $validated['email'],
                'handphone' => $validated['handphone'],
                'status' => 'active',
            ];

            if ($existingStudent) {
                // BUGFIX: sebelumnya baris Student lama langsung dipakai apa
                // adanya tanpa update nama/email sama sekali -- jadi kalau
                // nomor WhatsApp yang sama pernah dipakai sebelumnya (submit
                // form lain, testing, atau nomor keluarga/orang lain), nama
                // yang BARU SAJA diketik peserta di step ini diam-diam
                // dibuang, dan admin lihat nama LAMA dari submission
                // sebelumnya -- padahal peserta yakin sudah isi nama yang
                // benar. Nomor HP dipakai sebagai "kunci" identitas Student
                // (biar tidak dobel baris per orang), TAPI nama/email harus
                // tetap ikut yang terbaru diketik tiap kali submit.
                $existingStudent->update([
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                    'email' => $payload['email'],
                ]);

                Log::info('[FORM-WIZARD] Pakai Student yang sudah ada, update nama/email ke data terbaru', [
                    'student_id' => $existingStudent->id,
                ]);

                return $existingStudent;
            }

            Log::info('[FORM-WIZARD] Akan create Student baru dengan payload', $payload);

            $student = Student::create($payload);

            Log::info('[FORM-WIZARD] Student::create selesai dieksekusi', [
                'student_id' => $student->id ?? null,
                'student_exists_flag' => $student->exists,
                'was_recently_created' => $student->wasRecentlyCreated,
            ]);

            // Cek ulang langsung ke DB (bukan dari memory object) untuk memastikan
            // baris ini SUNGGUH ada di tabel, bukan cuma ada di object PHP-nya.
            $recheck = DB::table('students')->where('id', $student->id)->first();

            Log::info('[FORM-WIZARD] Recheck langsung ke tabel students via query builder', [
                'ketemu_di_db' => $recheck ? true : false,
                'data' => $recheck,
            ]);

            return $student;
        } catch (\Illuminate\Database\QueryException $e) {
            // Ini bakal ke-catch kalau errornya soal SQL (constraint, kolom NOT NULL, dsb)
            Log::error('[FORM-WIZARD] QueryException saat proses Student', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
            ]);

            throw $e;
        } catch (\Throwable $e) {
            // Tangkap SEMUA jenis error lain (termasuk yang biasanya bikin whoops page)
            Log::error('[FORM-WIZARD] Exception tak terduga saat proses Student', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Loop pertanyaan ROOT form ini (sesuai urutan) dan simpan FormAnswer untuk
     * apapun yang sudah terisi di $request — termasuk, secara rekursif, pertanyaan
     * "anak" (bercabang/nested) dari opsi yang benar-benar dipilih peserta. Tidak
     * pernah menegakkan "required" di level ini (itu murni validasi JS di
     * form-wizard.blade.php) — jawaban kosong cukup dilewati, bukan error. Dipakai
     * bareng oleh formWizardSubmit() (submit lengkap) dan formWizardTimeoutSave()
     * (auto-save saat timer placement test habis).
     *
     * === PERTANYAAN BERCABANG (conditional/nested questions) ===
     * $questions yang masuk ke sini masih FLAT (root + semua anak berlapis, lihat
     * pemanggilnya) — sengaja TIDAK difilter parent_option_id di sini, supaya bisa
     * dikelompokkan sekali via groupBy('parent_option_id') lalu ditelusuri mulai
     * dari root, turun HANYA ke anak dari opsi yang memang benar-benar dipilih di
     * $request (bukan cuma percaya pada state tersembunyi/d-none di client). Ini
     * satu-satunya "sumber kebenaran" untuk pertanyaan cabang mana yang boleh
     * tersimpan jawabannya — client-side hanya mengatur tampilan, bukan keamanan.
     *
     * @param  \Illuminate\Support\Collection<int, FormQuestion>  $questions
     * @return array{ringkasan: string, autoScore: float, selectedMajorIds: array<int, string>}
     */
    private function saveQuestionAnswers(
        Request $request,
        FormSubmission $submission,
        Student $student,
        $questions,
        bool $isAutoResultForm
    ): array {
        $answersSummary = [];
        $questionNumber = 1;
        $selectedMajorIds = [];
        $autoScore = 0;

        $childrenByParentOption = $questions->groupBy('parent_option_id');
        $rootQuestions = $questions->filter(fn ($q) => $q->parent_option_id === null);

        foreach ($rootQuestions as $question) {
            $this->processQuestionBranch(
                $request,
                $submission,
                $student,
                $question,
                $childrenByParentOption,
                $isAutoResultForm,
                0,
                $answersSummary,
                $questionNumber,
                $autoScore,
                $selectedMajorIds
            );
        }

        return [
            'ringkasan' => implode("\n", $answersSummary),
            'autoScore' => $autoScore,
            'selectedMajorIds' => $selectedMajorIds,
        ];
    }

    /**
     * Simpan jawaban SATU pertanyaan (lewat saveSingleQuestionAnswer()), lalu
     * turun secara rekursif ke pertanyaan anak dari opsi yang benar-benar terpilih
     * di jawaban itu. $depth dipakai untuk indentasi ringkasan teks ("↳") supaya
     * laporan WhatsApp juga kelihatan bercabang, sama seperti tampilan form-nya.
     *
     * Parameter penghitung ($answersSummary, $questionNumber, $autoScore,
     * $selectedMajorIds) dilewatkan by-reference supaya terus terakumulasi
     * sepanjang seluruh pohon pertanyaan, termasuk lintas rekursi.
     */
    private function processQuestionBranch(
        Request $request,
        FormSubmission $submission,
        Student $student,
        FormQuestion $question,
        $childrenByParentOption,
        bool $isAutoResultForm,
        int $depth,
        array &$answersSummary,
        int &$questionNumber,
        float &$autoScore,
        array &$selectedMajorIds
    ): void {
        // Pengaman kalau ada data lama yang somehow membentuk cycle (harusnya
        // sudah dicegah saat admin menyimpan pertanyaan, lihat
        // FormQuestionController::wouldCreateCycle()) — supaya tidak pernah sampai
        // infinite recursion di sini.
        if ($depth > 12) {
            return;
        }

        $result = $this->saveSingleQuestionAnswer($request, $submission, $student, $question, $isAutoResultForm);

        $indent = $depth > 0 ? str_repeat('   ', $depth) . '↳ ' : '';
        $answersSummary[] = "{$indent}{$questionNumber}. {$question->question_text}\n{$indent}   Jawaban: {$result['answerValue']}";
        $questionNumber++;
        $autoScore += $result['scoreDelta'];

        if ($result['majorId']) {
            $selectedMajorIds[] = $result['majorId'];
        }

        foreach ($result['selectedOptionIds'] as $optionId) {
            $children = $childrenByParentOption->get($optionId);
            if (!$children) {
                continue;
            }

            foreach ($children as $childQuestion) {
                $this->processQuestionBranch(
                    $request,
                    $submission,
                    $student,
                    $childQuestion,
                    $childrenByParentOption,
                    $isAutoResultForm,
                    $depth + 1,
                    $answersSummary,
                    $questionNumber,
                    $autoScore,
                    $selectedMajorIds
                );
            }
        }
    }

    /**
     * Simpan FormAnswer untuk SATU pertanyaan sesuai tipenya — logika per-tipe
     * sama persis dengan sebelum refactor pertanyaan bercabang, cuma sekarang
     * mengembalikan hasilnya (bukan langsung menumpuk ke variabel di luar) supaya
     * processQuestionBranch() bisa tahu opsi mana yang terpilih (untuk menentukan
     * pertanyaan anak mana yang perlu direkursi).
     *
     * @return array{answerValue: string, selectedOptionIds: array<int, string>, majorId: ?string, scoreDelta: float}
     */
    private function saveSingleQuestionAnswer(
        Request $request,
        FormSubmission $submission,
        Student $student,
        FormQuestion $question,
        bool $isAutoResultForm
    ): array {
        $questionKey = 'question_' . $question->id;
        $answerValue = null;
        $selectedOptionIds = [];
        $majorId = null;
        $scoreDelta = 0;

        if ($question->type === 'text' || $question->type === 'number') {
            $answerText = $request->input($questionKey);
            $answerValue = $answerText ?: '-';

            if ($answerText) {
                FormAnswer::create([
                    'user_id' => $student->id,
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'option_id' => null,
                    'answer_text' => $answerText,
                    'status' => 'active',
                ]);
            }
        } elseif ($question->type === 'single_choice') {
            $optionId = $request->input($questionKey);

            if ($optionId) {
                $option = FormQuestionOption::find($optionId);
                // option_text bisa kosong kalau opsinya berupa gambar saja (mis. soal Listening).
                $answerValue = $option ? ($option->option_text ?: '[Gambar]') : '-';

                FormAnswer::create([
                    'user_id' => $student->id,
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'option_id' => $optionId,
                    'answer_text' => null,
                    'status' => 'active',
                ]);

                // Dicatat sebagai "terpilih" walau $option null (opsi tidak/tidak lagi
                // ada) — childrenByParentOption->get() untuk id semacam itu memang
                // tidak akan menemukan apa-apa, jadi aman, dan tetap konsisten dengan
                // FormAnswer yang sudah terlanjur dibuat di atas.
                $selectedOptionIds[] = $optionId;

                if ($isAutoResultForm && $question->stage_group === 'placement_test' && $option) {
                    $scoreDelta += (float) ($option->score ?? 0);
                }
            } else {
                $answerValue = '-';
            }
        } elseif ($question->type === 'multiple_choice') {
            $optionIds = $request->input($questionKey, []);
            $selectedOptions = [];

            foreach ($optionIds as $optionId) {
                $option = FormQuestionOption::find($optionId);
                if ($option) {
                    // Opsi "Lainnya" (is_other): teks bebas yang diketik peserta datang
                    // dari input terpisah question_{id}_other_text (lihat
                    // frontend/partials/question-card.blade.php), disimpan ke
                    // answer_text supaya jawabannya tidak hilang.
                    $otherText = $option->is_other
                        ? trim((string) $request->input($questionKey . '_other_text', ''))
                        : null;
                    $hasOtherText = $otherText !== null && $otherText !== '';

                    $selectedOptions[] = $hasOtherText ? $otherText : ($option->option_text ?: '[Gambar]');

                    FormAnswer::create([
                        'user_id' => $student->id,
                        'submission_id' => $submission->id,
                        'question_id' => $question->id,
                        'option_id' => $optionId,
                        'answer_text' => $hasOtherText ? $otherText : null,
                        'status' => 'active',
                    ]);

                    $selectedOptionIds[] = $optionId;

                    if ($isAutoResultForm && $question->stage_group === 'placement_test') {
                        $scoreDelta += (float) ($option->score ?? 0);
                    }
                }
            }

            $answerValue = !empty($selectedOptions) ? implode(', ', $selectedOptions) : '-';
        } elseif ($question->type === 'major') {
            $majorIdInput = $request->input($questionKey);
            $major = $majorIdInput ? Major::find($majorIdInput) : null;
            $answerValue = $major ? $major->name : '-';

            if ($majorIdInput && $major) {
                FormAnswer::create([
                    'user_id' => $student->id,
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'option_id' => null,
                    'answer_text' => $majorIdInput,
                    'status' => 'active',
                ]);

                $majorId = $majorIdInput;
            }
        } elseif ($question->type === 'exact_match') {
            // Peserta harus mengetik jawaban PERSIS sama dengan correct_answer
            // (mis. soal yang jawabannya huruf Mandarin) — lihat migration
            // add_exact_match_type_to_form_questions_table. Case-sensitive,
            // cuma spasi di awal/akhir yang diabaikan supaya spasi tidak
            // sengaja bikin jawaban benar dianggap salah.
            $answerText = $request->input($questionKey);
            $answerValue = ($answerText !== null && $answerText !== '') ? $answerText : '-';

            if ($answerText !== null && $answerText !== '') {
                FormAnswer::create([
                    'user_id' => $student->id,
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'option_id' => null,
                    'answer_text' => $answerText,
                    'status' => 'active',
                ]);

                $isExactMatch = $question->correct_answer !== null
                    && trim($answerText) === trim($question->correct_answer);

                if ($isAutoResultForm && $question->stage_group === 'placement_test' && $isExactMatch) {
                    $scoreDelta += (float) ($question->match_score ?? 0);
                }
            }
        } elseif ($question->type === 'file') {
            // Upload jawaban (dokumen pendukung, dsb). Nama field-nya dinamis per
            // pertanyaan (question_{id}) sama seperti tipe lain di sini, jadi
            // divalidasi manual di sini — bukan lewat $request->validate() di awal
            // formWizardSubmit() yang cuma menangani field tetap (name/email/dst).
            // File yang tidak lolos (jenis/ukuran) diperlakukan seperti "belum
            // dijawab" (tidak disimpan) — konsisten dengan tipe lain di sini yang
            // memang tidak pernah menegakkan "required" di server, hanya di JS.
            $uploadedFile = $request->file($questionKey);
            $answerValue = '-';

            if ($uploadedFile) {
                $fileValidator = Validator::make(
                    [$questionKey => $uploadedFile],
                    [$questionKey => 'file|mimes:' . self::QUESTION_FILE_ALLOWED_MIMES . '|max:' . self::QUESTION_FILE_MAX_KB]
                );

                if ($fileValidator->passes()) {
                    $storedPath = $this->storeQuestionAnswerFile($uploadedFile);
                    $answerValue = '[File: ' . $uploadedFile->getClientOriginalName() . ']';

                    FormAnswer::create([
                        'user_id' => $student->id,
                        'submission_id' => $submission->id,
                        'question_id' => $question->id,
                        'option_id' => null,
                        'answer_text' => $storedPath,
                        'status' => 'active',
                    ]);
                } else {
                    Log::warning('[FORM-WIZARD] File jawaban ditolak (jenis/ukuran tidak valid)', [
                        'question_id' => $question->id,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'errors' => $fileValidator->errors()->all(),
                    ]);
                }
            }
        }

        return [
            'answerValue' => $answerValue,
            'selectedOptionIds' => $selectedOptionIds,
            'majorId' => $majorId,
            'scoreDelta' => $scoreDelta,
        ];
    }

    /**
     * Simpan file jawaban pertanyaan tipe 'file' ke public/quiz/file-upload,
     * kembalikan path relatifnya — pola yang sama dengan storeUniversityFile()
     * di UniversityController / storeFormFile() di FormController.
     */
    private function storeQuestionAnswerFile(UploadedFile $file): string
    {
        $destination = public_path('quiz/file-upload');

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($destination, $filename);

        return 'quiz/file-upload/' . $filename;
    }

    /**
     * === MAJOR ===
     * Cari universitas yang terhubung ke major terpilih lewat tabel setting_universities,
     * lalu bentuk section pesan WhatsApp untuk itu.
     *
     * @param array $majorIds
     * @return string
     */
    private function buildMajorUniversitiesMessage(array $majorIds)
    {
        $message = '';

        foreach ($majorIds as $majorId) {
            $major = Major::find($majorId);
            $majorName = $major->name ?? 'Jurusan terpilih';

            $universities = SettingUniversity::where('major_id', $majorId)
                ->where('status', 'active')
                ->with('university')
                ->get()
                ->pluck('university')
                ->filter()
                ->unique('id')
                ->values();

            $message .= "\n\n🏫 *Universitas untuk jurusan {$majorName}:*\n";

            if ($universities->isEmpty()) {
                $message .= "Belum ada universitas yang tersedia untuk jurusan ini saat ini. Tim kami akan segera menginformasikan pilihan lainnya.";
                continue;
            }

            foreach ($universities as $index => $uni) {
                $url = route('frontend.university.profile', $uni->id);
                $message .= ($index + 1) . ". {$uni->name}\n   {$url}\n";
            }
        }

        return $message;
    }

    /**
     * === WHATSAPP TEMPLATE ===
     * Susun isi pesan WhatsApp dari template yang terpasang di form ($form->whatsappTemplate).
     * Kalau form belum punya template (whatsapp_template_id null / relasi kosong), pakai
     * format default supaya form lama tetap jalan tanpa perlu dipasangi template dulu.
     *
     * @param Form  $form
     * @param array $placeholders  key => value, key TANPA kurung kurawal, misal 'name' untuk {{name}}
     * @return string
     */
    private function buildMessageFromTemplate(Form $form, array $placeholders)
    {
        return (new \App\Services\Whatsapp\WhatsappMessenger())->buildMessageFromTemplate($form, $placeholders);
    }

    /**
     * === WHATSAPP GATEWAY ===
     * Kirim pesan WhatsApp. Kredensial diambil dari gateway yang diaktifkan admin
     * pemilik form ($userId) lewat menu Settings > WhatsApp Gateway (lihat
     * App\Http\Controllers\Settings\WhatsappGatewayController). Kalau belum ada
     * gateway yang diaktifkan untuk user itu, fallback ke kredensial lama di .env
     * (WABLAS_TOKEN/WABLAS_SECRET) supaya form yang belum di-setting tetap jalan
     * seperti sebelumnya.
     *
     * Prosedur pengiriman sengaja disamakan untuk semua provider (Wablas-compatible):
     * POST {api_host}/api/v2/send-message, header Authorization: token.secret_key,
     * body {"data":[{"phone":...,"message":...}]}.
     */
    private function sendWhatsapp($phone, $message, ?string $userId = null)
    {
        return (new \App\Services\Whatsapp\WhatsappMessenger())->send((string) $phone, (string) $message, $userId);
    }
}