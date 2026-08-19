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

use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormPayment;
use App\Models\FormQuestion;
use App\Models\FormQuestionOption;
use App\Models\FormResult;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class FrontendController extends Controller
{
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

        $universitiesQuery = University::where('status', 'active')->orderBy('name');

        if ($search) {
            $universitiesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")
                ->orWhere('country', 'like', "%{$search}%");
            });
        }

        $universities = $universitiesQuery->get();

        $profiles = UniversityProfile::where('status', 'active')
            ->whereIn('university_id', $universities->pluck('id'))
            ->get()
            ->keyBy('university_id');

        // Kumpulkan daftar field unik untuk filter chip
        $allFields = collect();
        foreach ($profiles as $p) {
            if ($p->field) {
                $allFields = $allFields->merge(array_map('trim', explode(',', $p->field)));
            }
        }
        $allFields = $allFields->unique()->sort()->values();

        return view('frontend.university-catalog', compact('universities', 'profiles', 'search', 'allFields'));
    }


    public function universityProfile($id)
    {
        $university = University::findOrFail($id);

        $profile = UniversityProfile::where('university_id', $id)
            ->where('status', 'active')
            ->first(); // pakai first(), bukan firstOrFail()

        $albums = UniversityAlbum::where('university_id', $id)
            ->where('status', 'active')
            ->with(['photos' => function ($query) {
                $query->where('status', 'active')
                    ->orderBy('sort_order');
            }])
            ->get();

        return view('frontend.university-profile', compact('university', 'profile', 'albums'));
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
                if ($selectedForm->has_personal_data_stage) {
                    $personalDataQuestions = $questions->where('stage_group', 'personal_data')->values();
                }
                $placementTestQuestions = $questions->where('stage_group', 'placement_test')->values();
            }
        }

        return view('frontend.form-wizard', compact(
            'forms',
            'selectedForm',
            'questions',
            'personalDataQuestions',
            'placementTestQuestions',
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
            ]);

            Log::info('[FORM-WIZARD] Sebelum kirim WhatsApp', ['handphone' => $student->handphone]);

            try {
                $this->sendWhatsapp($student->handphone, $message, $form->user_id);
                Log::info('[FORM-WIZARD] sendWhatsapp selesai tanpa exception');

                // Tandai kapan hasil auto ini terkirim via WA (dipakai konsisten dengan
                // FormController::saveResult() untuk mode manual, supaya kedua mode
                // sama-sama punya jejak waktu pengiriman).
                if ($isAutoResultForm && $formResult) {
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

            if ($existingStudent) {
                Log::info('[FORM-WIZARD] Pakai Student yang sudah ada, tidak insert baru', [
                    'student_id' => $existingStudent->id,
                ]);

                return $existingStudent;
            }

            $nameParts = preg_split('/\s+/', trim($validated['name']), 2);

            $payload = [
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? '',
                'email' => $validated['email'],
                'handphone' => $validated['handphone'],
                'status' => 'active',
            ];

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
     * Loop seluruh pertanyaan form (sesuai urutan) dan simpan FormAnswer untuk
     * apapun yang sudah terisi di $request. Tidak pernah menegakkan "required" di
     * level ini (itu murni validasi JS di form-wizard.blade.php) — jawaban kosong
     * cukup dilewati, bukan error. Dipakai bareng oleh formWizardSubmit() (submit
     * lengkap) dan formWizardTimeoutSave() (auto-save saat timer placement test habis).
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

        foreach ($questions as $question) {
            $questionKey = 'question_' . $question->id;
            $answerValue = null;

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

                    if ($isAutoResultForm && $question->stage_group === 'placement_test' && $option) {
                        $autoScore += (float) ($option->score ?? 0);
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
                        $selectedOptions[] = $option->option_text ?: '[Gambar]';

                        FormAnswer::create([
                            'user_id' => $student->id,
                            'submission_id' => $submission->id,
                            'question_id' => $question->id,
                            'option_id' => $optionId,
                            'answer_text' => null,
                            'status' => 'active',
                        ]);

                        if ($isAutoResultForm && $question->stage_group === 'placement_test') {
                            $autoScore += (float) ($option->score ?? 0);
                        }
                    }
                }

                $answerValue = !empty($selectedOptions) ? implode(', ', $selectedOptions) : '-';
            } elseif ($question->type === 'major') {
                $majorId = $request->input($questionKey);
                $major = $majorId ? Major::find($majorId) : null;
                $answerValue = $major ? $major->name : '-';

                if ($majorId && $major) {
                    FormAnswer::create([
                        'user_id' => $student->id,
                        'submission_id' => $submission->id,
                        'question_id' => $question->id,
                        'option_id' => null,
                        'answer_text' => $majorId,
                        'status' => 'active',
                    ]);

                    $selectedMajorIds[] = $majorId;
                }
            }

            $answersSummary[] = "{$questionNumber}. {$question->question_text}\n   Jawaban: {$answerValue}";
            $questionNumber++;
        }

        return [
            'ringkasan' => implode("\n", $answersSummary),
            'autoScore' => $autoScore,
            'selectedMajorIds' => $selectedMajorIds,
        ];
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