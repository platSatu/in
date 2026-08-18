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
use App\Models\FormQuestion;
use App\Models\FormQuestionOption;
use App\Models\FormSubmission;
use App\Models\Major;
use App\Models\Student;
use App\Models\SettingUniversity;
use App\Models\User;
use App\Models\University;
use App\Models\UniversityProfile;
use App\Models\UniversityAlbum;
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
        $formId = $request->query('form_id');

        // Get all available forms
        $forms = Form::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        // === MAJOR ===
        // Dipakai untuk render dropdown pada pertanyaan bertipe 'major' di step 2.
        $majors = Major::orderBy('name')->get();

        // If specific form selected, get its questions
        $selectedForm = null;
        $questions = [];

        if ($formId) {
            $selectedForm = Form::find($formId);
            if ($selectedForm) {
                $questions = FormQuestion::where('form_id', $formId)
                    ->where('status', 'active')
                    ->with('options')
                    ->orderBy('order')
                    ->get();
            }
        }

        return view('frontend.form-wizard', compact('forms', 'selectedForm', 'questions', 'majors'));
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

        try {
            Log::info('[FORM-WIZARD] Cek DB connection aktif', [
                'connection' => config('database.default'),
                'database' => DB::connection()->getDatabaseName(),
            ]);

            $existingStudent = Student::where('handphone', $validated['handphone'])->first();

            Log::info('[FORM-WIZARD] Hasil cek Student existing', [
                'found' => $existingStudent ? true : false,
                'existing_student_id' => $existingStudent->id ?? null,
            ]);

            $student = $existingStudent;

            if (!$student) {
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
            } else {
                Log::info('[FORM-WIZARD] Pakai Student yang sudah ada, tidak insert baru', [
                    'student_id' => $student->id,
                ]);
            }
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

        Log::info('[FORM-WIZARD] Lanjut ke proses FormSubmission & FormAnswer', [
            'student_id' => $student->id,
        ]);

        // Get form for message building
        $form = Form::find($validated['form_id']);

        // Create submission record
        $submission = FormSubmission::create([
            'user_id' => $student->id,
            'form_id' => $validated['form_id'],
            'status' => 'active',
        ]);

        Log::info('[FORM-WIZARD] FormSubmission dibuat', ['submission_id' => $submission->id]);

        // Get form questions
        $questions = FormQuestion::where('form_id', $validated['form_id'])
            ->where('status', 'active')
            ->with('options')
            ->orderBy('order')
            ->get();

        $answersSummary = [];
        $questionNumber = 1;
        $selectedMajorIds = [];

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
                    $answerValue = $option ? $option->option_text : '-';

                    FormAnswer::create([
                        'user_id' => $student->id,
                        'submission_id' => $submission->id,
                        'question_id' => $question->id,
                        'option_id' => $optionId,
                        'answer_text' => null,
                        'status' => 'active',
                    ]);
                } else {
                    $answerValue = '-';
                }
            } elseif ($question->type === 'multiple_choice') {
                $optionIds = $request->input($questionKey, []);
                $selectedOptions = [];

                foreach ($optionIds as $optionId) {
                    $option = FormQuestionOption::find($optionId);
                    if ($option) {
                        $selectedOptions[] = $option->option_text;

                        FormAnswer::create([
                            'user_id' => $student->id,
                            'submission_id' => $submission->id,
                            'question_id' => $question->id,
                            'option_id' => $optionId,
                            'answer_text' => null,
                            'status' => 'active',
                        ]);
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

        $ringkasanJawaban = implode("\n", $answersSummary);

        $universitasMajorMessage = '';
        if (!empty($selectedMajorIds)) {
            $universitasMajorMessage = $this->buildMajorUniversitiesMessage(array_unique($selectedMajorIds));
        }

        $message = $this->buildMessageFromTemplate($form, [
            'name' => trim($student->first_name . ' ' . $student->last_name),
            'form_name' => $form->name,
            'ringkasan_jawaban' => $ringkasanJawaban,
            'universitas_major' => $universitasMajorMessage,
        ]);

        Log::info('[FORM-WIZARD] Sebelum kirim WhatsApp', ['handphone' => $student->handphone]);

        try {
            $this->sendWhatsapp($student->handphone, $message);
            Log::info('[FORM-WIZARD] sendWhatsapp selesai tanpa exception');
        } catch (\Throwable $e) {
            // Kalau sendWhatsapp gagal/lambat/timeout, JANGAN sampai bikin seluruh
            // request dianggap gagal padahal data student/submission sudah tersimpan.
            Log::error('[FORM-WIZARD] sendWhatsapp gagal (data DB tetap aman, ini cuma soal WA)', [
                'message' => $e->getMessage(),
            ]);
        }

        Log::info('[FORM-WIZARD] === END formWizardSubmit, redirect sukses ===', [
            'student_id' => $student->id,
        ]);

        return redirect()
            ->route('frontend.form.wizard')
            ->with('success', 'Thank you! Your form has been submitted successfully.');
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
        $template = $form->whatsappTemplate ?? null;

        if (!$template || empty($template->content)) {
            // Fallback: format default (persis seperti sebelum ada sistem template)
            $message = "Halo {$placeholders['name']},\n\n";
            $message .= "Terima kasih telah mengisi formulir \"{$placeholders['form_name']}\".\n\n";
            $message .= "*Ringkasan Jawaban:*\n";
            $message .= $placeholders['ringkasan_jawaban'] . "\n\n";
            $message .= "Hasil Anda sudah kami terima. Terima kasih! 😊";
            $message .= $placeholders['universitas_major'];

            return $message;
        }

        $content = $template->content;

        foreach ($placeholders as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    /**
     * Send WhatsApp message using Wablas API
     */
    private function sendWhatsapp($phone, $message)
    {
        try {
            // Clean phone number (remove all non-digits except +)
            $phone = preg_replace('/[^0-9+]/', '', $phone);

            // If phone starts with +62, replace with 62
            if (str_starts_with($phone, '+62')) {
                $phone = '62' . substr($phone, 3);
            } elseif (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $response = Http::withHeaders([
                'Authorization' => env('WABLAS_TOKEN') . '.' . env('WABLAS_SECRET'),
                'Content-Type' => 'application/json',
            ])->post('https://smg.wablas.com/api/v2/send-message', [
                        'data' => [
                            [
                                'phone' => $phone,
                                'message' => $message,
                            ]
                        ]
                    ]);

            // Log response
            Log::info('Wablas Response - Form Wizard', [
                'phone' => $phone,
                'body' => $response->json(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Wablas Error - Form Wizard', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}