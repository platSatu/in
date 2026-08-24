<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Helpers\DataScope;
use App\Http\Controllers\Controller;
use App\Models\ClassEnrollment;
use App\Models\ClassSchedule;
use App\Models\CompanyBranch;
use App\Models\CompanyDivision;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormPayment;
use App\Models\FormQuestion;
use App\Models\FormQuestionOption;
use App\Models\FormResult;
use App\Models\FormSection;
use App\Models\FormSubmission;
use App\Models\Major;
use App\Models\RoleUser;
use App\Models\WhatsappTemplate;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $user = Auth::user();
        if ($user === null) {
            abort(401);
        }

        // SEBELUMNYA: selalu di-scope ketat `user_id = pembuatnya sendiri`
        // (AdminCrud::paginate). SEKARANG: ikut cakupan branch/divisi/company
        // role aktif user ini (lihat App\Helpers\DataScope) — supaya rekan
        // setim di branch/unit yang sama bisa saling melihat form yang sama,
        // sesuai role yang di-assign lewat halaman Role to User. Role dengan
        // scope 'self' (atau user tanpa role apa pun yang relevan) tetap
        // berperilaku identik seperti sebelumnya: cuma lihat form buatan
        // sendiri.
        $query = Form::query()->with(['companyBranch', 'division', 'formSubmissions', 'formPayments']);
        DataScope::applyBranchDivisionScope($query, $user, 'user_id');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('no_booth', 'like', "%{$search}%");
            });
        }

        $data = $query->latest('created_at')->paginate(10)->withQueryString();

        // Total form yang BOLEH DILIHAT user ini (bukan lagi selalu "buatan
        // sendiri" — ikut scope yang sama dengan di atas), tidak terpengaruh
        // search/pagination.
        $totalFormsQuery = Form::query();
        DataScope::applyBranchDivisionScope($totalFormsQuery, $user, 'user_id');
        $totalForms = $totalFormsQuery->count();

        return view('quiz.form.index', compact('data', 'totalForms'));
    }

    /**
     * Form yang boleh diakses user ini (lihat App\Helpers\DataScope) —
     * dipakai SEMUA method di controller ini yang sebelumnya memakai
     * AdminCrud::findOrFail(Form::class, $id, $userId) (scope ketat "punya
     * sendiri"). Method ini menggantikan pengecekan itu dengan cakupan
     * branch/divisi/company sesuai role aktif user, TANPA mengubah cara
     * AdminCrud dipakai di modul lain — perubahan ini murni lokal di
     * controller ini.
     */
    private function resolveVisibleForm(string $id): Form
    {
        $form = Form::findOrFail($id);

        if (!$this->isFormVisible($form)) {
            abort(403, 'Form tidak valid untuk cakupan akses Anda.');
        }

        return $form;
    }

    /**
     * @param Form|null $form
     */
    private function isFormVisible($form): bool
    {
        if (!$form) {
            return false;
        }

        $visibleIds = DataScope::visibleFormIds(Auth::user());

        return $visibleIds === null || in_array($form->id, $visibleIds, true);
    }

    /**
     * Halaman detail submission + pembayaran satu form: daftar peserta yang
     * submit, dan (kalau form requires_payment) daftar seluruh transaksi
     * pembayaran, supaya admin bisa membandingkan siapa yang submit vs siapa
     * yang benar-benar bayar (deteksi anomali/kebocoran, mis. transaksi
     * "paid" yang tidak pernah terhubung ke submission mana pun).
     */
    public function submissions(string $id)
    {
        if (Auth::id() === null) {
            abort(401);
        }

        $form = $this->resolveVisibleForm($id);

        $submissions = FormSubmission::where('form_id', $form->id)
            ->with(['student', 'payment', 'result'])
            ->latest('created_at')
            ->get();

        $payments = collect();
        if ($form->requires_payment) {
            $payments = FormPayment::where('form_id', $form->id)
                ->latest('created_at')
                ->get();
        }

        return view('quiz.form.submissions', compact('form', 'submissions', 'payments'));
    }

    /**
     * Konten modal "Detail" di halaman index Quiz Form — dipanggil lewat AJAX
     * fetch() dari tombol "Detail" per baris (lihat quiz/form/index.blade.php),
     * balikin FRAGMEN HTML (bukan JSON/halaman penuh) buat langsung disuntik ke
     * modal-body. Read-only murni: cuma menampilkan daftar pertanyaan (termasuk
     * pertanyaan bercabang/anak, nested di bawah opsi pemicunya) beserta
     * opsi/jawabannya — tidak ada form atau tombol aksi apa pun di dalamnya.
     */
    public function detail(string $id)
    {
        if (Auth::id() === null) {
            abort(401);
        }

        $form = $this->resolveVisibleForm($id);

        $questions = FormQuestion::where('form_id', $form->id)
            ->where('status', 'active')
            ->with('options')
            ->orderBy('order')
            ->orderBy('created_at')
            ->get();

        // Sama seperti FrontendController::buildFormWizardView(): cuma pertanyaan
        // ROOT (tanpa parent_option_id) yang dirender di level atas — pertanyaan
        // anak dirender belakangan secara rekursif lewat @include, begitu opsi
        // pemicunya muncul (lihat quiz/form/_detail-questions.blade.php).
        $rootQuestions = $questions->filter(fn (FormQuestion $q) => $q->parent_option_id === null)->values();

        return view('quiz.form._detail-content', compact('form', 'rootQuestions'));
    }

    /**
     * Konten modal "Jawaban" di halaman quiz.form.submissions — dipanggil lewat
     * fetch() begitu tombol "Lihat Jawaban" per baris peserta diklik, balikin
     * fragment HTML (bukan JSON) buat langsung disuntik ke modal-body. Read-only
     * murni, sama pola dengan detail() di atas, cuma yang ditampilkan di sini
     * JAWABAN ASLI milik satu submission (bukan daftar pertanyaan form-nya).
     *
     * Jawaban dikelompokkan per pertanyaan (satu pertanyaan multiple_choice bisa
     * punya lebih dari satu baris form_answers) dan diurutkan sesuai urutan asli
     * pertanyaan di form (FormQuestion::order), bukan sesuai urutan tersimpannya
     * di database — supaya runtut kebaca seperti waktu peserta mengisi.
     */
    public function submissionAnswers(string $submissionId)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $submission = FormSubmission::with(['student', 'form'])->findOrFail($submissionId);

        // Cakupan sama dengan resolveVisibleForm() — bukan lagi mutlak
        // "form_id.user_id === Auth::id()" (lihat App\Helpers\DataScope).
        if (!$this->isFormVisible($submission->form)) {
            abort(403, 'Submission tidak valid untuk cakupan akses Anda.');
        }

        $form = $submission->form;

        $answersByQuestion = FormAnswer::where('submission_id', $submission->id)
            ->with('option')
            ->get()
            ->groupBy('question_id');

        $questions = FormQuestion::where('form_id', $form->id)
            ->orderBy('order')
            ->orderBy('created_at')
            ->get();

        // Cuma pertanyaan yang BENAR-BENAR ada jawabannya yang ditampilkan (sama
        // seperti aturan penyimpanan di FrontendController: pertanyaan kosong
        // tidak dibuatkan baris form_answers sama sekali).
        $groupedAnswers = $questions
            ->map(fn (FormQuestion $question) => (object) [
                'question' => $question,
                'rows' => $answersByQuestion->get($question->id, collect()),
            ])
            ->filter(fn ($group) => $group->rows->isNotEmpty())
            ->values();

        // Resolve nama Major sekali lewat 1 query (bukan N+1) buat pertanyaan
        // type='major' — form_answers.answer_text nyimpen ID Major-nya, bukan
        // namanya langsung.
        $majorIds = $groupedAnswers
            ->filter(fn ($group) => $group->question->type === 'major')
            ->flatMap(fn ($group) => $group->rows->pluck('answer_text'))
            ->filter()
            ->unique()
            ->values();
        $majors = $majorIds->isNotEmpty()
            ? Major::whereIn('id', $majorIds)->pluck('name', 'id')
            : collect();

        return view('quiz.form._submission-answers', compact('submission', 'groupedAnswers', 'majors'));
    }

    /**
     * Simpan/perbarui hasil MANUAL (result_mode='manual') untuk satu submission,
     * dipanggil dari halaman quiz.form.submissions. Begitu tersimpan, kalau form
     * ini mengaktifkan use_whatsapp_notification, langsung kirim WA berisi hasil
     * tsb — sama seperti mode auto, cuma pemicunya di sini adalah admin menekan
     * simpan, bukan submit placement test.
     */
    public function saveResult(Request $request, string $submissionId)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $submission = FormSubmission::with(['student', 'form'])->findOrFail($submissionId);

        // Cakupan sama dengan resolveVisibleForm() — bukan lagi mutlak
        // "form_id.user_id === Auth::id()" (lihat App\Helpers\DataScope).
        if (!$this->isFormVisible($submission->form)) {
            abort(403, 'Submission tidak valid untuk cakupan akses Anda.');
        }

        $form = $submission->form;

        if ($form->result_mode !== 'manual') {
            abort(422, 'Form ini tidak memakai mode hasil manual.');
        }

        $validated = $request->validate([
            'summary_text' => 'required|string',
        ]);

        $payload = [
            'form_id' => $form->id,
            'mode' => 'manual',
            'summary_text' => $validated['summary_text'],
            'entered_by' => (string) $userId,
        ];

        // === RACE CONDITION SAFETY ===
        // Beberapa admin bisa saja menyimpan hasil untuk submission yang sama nyaris
        // bersamaan. form_results.form_submission_id sudah UNIQUE di level database
        // (lihat migration create_form_results_table), jadi ini bukan sekadar
        // updateOrCreate() biasa (yang punya celah check-then-act: dua request bisa
        // sama-sama melihat "belum ada" lalu sama-sama mencoba INSERT):
        // - Kalau baris sudah ada, dikunci dulu (lockForUpdate) di dalam transaction
        //   sebelum di-UPDATE, supaya update dari request lain yang datang nyaris
        //   bersamaan tetap berurutan (tidak saling timpa).
        // - Kalau baris belum ada, coba INSERT. Kalau ternyata request lain barusan
        //   menang duluan (unique constraint violation dari DB), langsung fallback
        //   ambil baris itu & UPDATE — tidak ada request yang berakhir error 500.
        // Transaction sengaja dibuat SEPENDEK mungkin (cuma query DB, tanpa panggilan
        // API WhatsApp di dalamnya) supaya lock yang dipegang tidak lama-lama —
        // menahan lock sambil menunggu request HTTP eksternal itu yang biasanya jadi
        // sumber deadlock/lock-wait-timeout di aplikasi seperti ini.
        $result = DB::transaction(function () use ($submission, $payload) {
            $existing = FormResult::where('form_submission_id', $submission->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->update($payload);

                return $existing;
            }

            try {
                return FormResult::create($payload + ['form_submission_id' => $submission->id]);
            } catch (QueryException $e) {
                if (!$this->isDuplicateEntry($e)) {
                    throw $e;
                }

                $existing = FormResult::where('form_submission_id', $submission->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $existing->update($payload);

                return $existing;
            }
        });

        // === KIRIM WA (di luar transaction, setelah DB commit) ===
        // "Klaim" hak kirim dengan satu UPDATE ber-syarat (whatsapp_sent_at IS NULL).
        // Kalau dua request memproses hasil yang sama nyaris bersamaan, MySQL hanya
        // akan meluluskan affected-rows=1 ke SATU request — request lain dapat 0 dan
        // otomatis tidak mengirim WA. Ini menghindari WA terkirim dobel tanpa perlu
        // menahan DB lock selama menunggu panggilan API WhatsApp (yang bisa lambat).
        $waSent = false;

        if ($form->use_whatsapp_notification && $submission->student) {
            $claimed = FormResult::where('id', $result->id)
                ->whereNull('whatsapp_sent_at')
                ->update(['whatsapp_sent_at' => now()]);

            if ($claimed === 1) {
                $messenger = new WhatsappMessenger();

                // === PILIH KELAS LINK ===
                // result_mode='manual' -> titik INI (bukan submission awal) yang
                // jadi saat hasil "keluar", jadi link "Pilih Kelas" disiapkan di
                // sini. Sama pola & alasannya dengan mode auto di
                // FrontendController::finalizeCompletedSubmission().
                $pilihKelasLink = ClassSchedule::existsActiveForBranch($form->branch_id)
                    ? route('frontend.class-selection.show', ['submissionId' => $submission->id])
                    : '';

                $message = $messenger->buildMessageFromTemplate($form, [
                    'name' => trim($submission->student->first_name . ' ' . $submission->student->last_name),
                    'form_name' => $form->name,
                    'hasil' => $validated['summary_text'],
                    'pilih_kelas_link' => $pilihKelasLink,
                ]);

                $messenger->send($submission->student->handphone, $message, $form->user_id);

                $waSent = true;
            }
        }

        return redirect()
            ->route('quiz.form.submissions', $form->id)
            ->with('success', 'Hasil berhasil disimpan' . ($waSent ? ' dan dikirim via WhatsApp.' : '.'));
    }

    /**
     * Deteksi error "duplicate entry" (unique constraint violation) dari MySQL,
     * dipakai di saveResult() untuk fallback INSERT -> UPDATE saat dua request
     * race mencoba membuat FormResult yang sama nyaris bersamaan.
     */
    private function isDuplicateEntry(QueryException $e): bool
    {
        return (int) ($e->errorInfo[1] ?? 0) === 1062;
    }

    /**
     * pre_test_notice sekarang boleh berisi HTML sederhana (bukan cuma plain
     * text lagi), karena di quiz/form/create & edit.blade.php ada toolbar
     * kecil (Bold/Perbesar/Merah) yang nge-wrap teks yang diblok user pakai
     * <span style="..."> atau <strong>. Request POST ke endpoint ini TIDAK
     * harus lewat toolbar itu (bisa saja user kirim HTML sembarangan
     * langsung), jadi sebelum disimpan ke DB HTML-nya harus disaring dulu di
     * server, bukan cuma dipercaya dari sisi client:
     *
     * 1) strip_tags() cuma sisain tag yang benar-benar dipakai toolbar
     *    (span/strong/b/br) — tag lain (script, img, a, div, dst) full dibuang.
     * 2) Tiap tag yang lolos itu DIBANGUN ULANG dari nol tanpa atribut
     *    aslinya sama sekali, lalu (khusus <span>) atribut "style"-nya
     *    dipasang lagi tapi cuma boleh berisi property color/font-weight/
     *    font-size dengan value yang aman — supaya onerror=/onclick=/
     *    javascript:/expression() tidak bisa nyelip lewat atribut atau CSS.
     */
    private function sanitizeNoticeHtml(?string $html): ?string
    {
        if ($html === null || trim(strip_tags($html)) === '') {
            return null;
        }

        $clean = strip_tags($html, '<span><strong><b><br>');

        $clean = preg_replace_callback('/<(span|strong|b|br)\b([^>]*)>/i', function (array $match) {
            $tag = strtolower($match[1]);

            if ($tag !== 'span') {
                return '<' . $tag . '>';
            }

            if (!preg_match('/style\s*=\s*"([^"]*)"/i', $match[2], $styleMatch)) {
                return '<span>';
            }

            $safeDeclarations = [];
            foreach (explode(';', $styleMatch[1]) as $declaration) {
                if (preg_match('/^\s*(color|font-weight|font-size)\s*:\s*([#a-zA-Z0-9.%\s]+)\s*$/', $declaration, $declarationMatch)) {
                    $safeDeclarations[] = trim($declarationMatch[1]) . ':' . trim($declarationMatch[2]);
                }
            }

            return $safeDeclarations
                ? '<span style="' . implode(';', $safeDeclarations) . '">'
                : '<span>';
        }, $clean);

        return trim($clean);
    }

    public function create(Request $request)
    {
        $templates = WhatsappTemplate::where('status', 'active')->get();
        $companyBranches = CompanyBranch::select('id', 'name')->orderBy('name')->get();
        $selectedCompanyBranchId = $request->query('company_branch_id');
        $companyDivisions = CompanyDivision::where('status', 'active')
            ->select('id', 'name', 'company_branch_id')
            ->orderBy('name')
            ->get();
        $suggestedDivisionId = $this->suggestedDivisionId();

        return view('quiz.form.create', compact(
            'templates',
            'companyBranches',
            'selectedCompanyBranchId',
            'companyDivisions',
            'suggestedDivisionId'
        ));
    }

    /**
     * Divisi yang otomatis "disarankan" (bukan wajib dipilih) waktu bikin
     * form baru — diambil dari divisi tempat staff yang login sendiri
     * di-assign lewat Role to User (role_user.company_division_id), kalau
     * ada lebih dari satu diambil yang pertama saja sebagai default; staff
     * tetap bebas ganti/kosongkan lewat dropdown-nya. Null kalau staff tidak
     * punya assignment divisi sama sekali (mis. scope company/branch, atau
     * belum di-assign role apa pun) — dropdown-nya cukup tampil kosong.
     */
    private function suggestedDivisionId(): ?string
    {
        $userId = Auth::id();
        if ($userId === null) {
            return null;
        }

        return RoleUser::query()
            ->where('user_id', $userId)
            ->where('status', RoleUser::STATUS_ACTIVE)
            ->whereNotNull('company_division_id')
            ->value('company_division_id');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pre_test_notice' => 'nullable|string',
            'background_image' => 'nullable|image|max:4096',
            'logo' => 'nullable|image|max:2048',
            'use_whatsapp_notification' => 'nullable|boolean',
            'whatsapp_template_id' => 'nullable|string|required_if:use_whatsapp_notification,1|exists:whatsapp_templates,id',
            'branch_id' => 'required|exists:company_branch,id',
            'company_division_id' => 'nullable|string|exists:company_division,id',
            'no_booth' => 'required|string|max:255',
            'requires_payment' => 'nullable|boolean',
            'payment_amount' => 'nullable|required_if:requires_payment,1|numeric|min:0',
            'payment_position' => 'nullable|in:before_questions,after_questions',
            'is_callback_enabled' => 'nullable|boolean',
            'callback_link' => 'nullable|required_if:is_callback_enabled,1|url|max:500',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'has_personal_data_stage' => 'nullable|boolean',
            'result_mode' => 'nullable|in:none,auto,manual,section_threshold',
            'section_fail_threshold' => 'nullable|integer|min:1|max:50',
            'section_pass_threshold' => 'nullable|integer|min:0|max:50',
            'timer_enabled' => 'nullable|boolean',
            'timer_duration_minutes' => 'nullable|required_if:timer_enabled,1|integer|min:1|max:600',
            'timer_auto_save' => 'nullable|boolean',
            'timer_auto_restart' => 'nullable|boolean',
        ]);

        $validated['pre_test_notice'] = $this->sanitizeNoticeHtml($validated['pre_test_notice'] ?? null);

        $validated['requires_payment'] = $request->boolean('requires_payment');
        if (!$validated['requires_payment']) {
            $validated['payment_amount'] = null;
        }
        $validated['payment_position'] = $validated['payment_position'] ?? 'before_questions';

        $validated['is_callback_enabled'] = $request->boolean('is_callback_enabled');
        if (!$validated['is_callback_enabled']) {
            $validated['callback_link'] = null;
        }

        $validated['use_whatsapp_notification'] = $request->boolean('use_whatsapp_notification');
        if (!$validated['use_whatsapp_notification']) {
            $validated['whatsapp_template_id'] = null;
        }

        $validated['has_personal_data_stage'] = $request->boolean('has_personal_data_stage');
        $validated['result_mode'] = $validated['result_mode'] ?? 'none';
        $validated['company_division_id'] = $validated['company_division_id'] ?? null;
        $validated = $this->applySectionThresholdFields($validated);

        $validated = $this->applyTimerFields($request, $validated);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        $branch = CompanyBranch::find($validated['branch_id']);
        $branchSlug = Str::slug($branch?->name ?? 'branch') ?: 'branch';

        $validated['slug'] = $branchSlug;
        $validated['booth_slug'] = $this->generateUniqueBoothSlug($branchSlug, $validated['no_booth']);

        // Background image & logo opsional. Beda dengan logo/banner di tabel
        // `universities` (NOT NULL tanpa default), kolom ini NULLABLE dengan
        // default NULL — jadi kalau tidak diupload cukup dibiarkan tidak ada
        // di $validated (tidak perlu di-default-kan ke string kosong). Kalau
        // NULL, halaman quiz publik otomatis pakai background/logo default
        // (lihat frontend/form-wizard.blade.php).
        if ($request->hasFile('background_image')) {
            $validated['background_image'] = $this->storeFormFile($request->file('background_image'), 'background');
        } else {
            unset($validated['background_image']);
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeFormFile($request->file('logo'), 'logo');
        } else {
            unset($validated['logo']);
        }

        AdminCrud::create(Form::class, $validated);

        return redirect()
            ->route('quiz.form.index')
            ->with('success', 'Form berhasil dibuat.');
    }

    /**
     * Simpan file (background_image/logo) ke public/form/{folder}, kembalikan
     * path relatifnya. Sama polanya dengan storeUniversityFile() di
     * UniversityController.
     */
    private function storeFormFile($file, string $folder): string
    {
        $destination = public_path('form/' . $folder);

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return 'form/' . $folder . '/' . $filename;
    }

    /**
     * Hapus file lama (background_image/logo) dari public/form/{folder}, kalau
     * ada — dipanggil dari update() waktu admin upload file baru pengganti.
     */
    private function deleteFormFile(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $fullPath = public_path($relativePath);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    public function edit(string $id)
    {
        $userId = Auth::id();

        if ($userId === null) {
            abort(401);
        }


        $data = $this->resolveVisibleForm($id);


        $templates = \App\Models\WhatsappTemplate::where('status', 'active')
            ->get();

        $companyBranches = CompanyBranch::select('id', 'name')->orderBy('name')->get();
        $companyDivisions = CompanyDivision::where('status', 'active')
            ->select('id', 'name', 'company_branch_id')
            ->orderBy('name')
            ->get();


        return view('quiz.form.edit', compact(
            'data',
            'templates',
            'companyBranches',
            'companyDivisions'
        ));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $existing = $this->resolveVisibleForm($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pre_test_notice' => 'nullable|string',
            'background_image' => 'nullable|image|max:4096',
            'logo' => 'nullable|image|max:2048',
            'use_whatsapp_notification' => 'nullable|boolean',
            'whatsapp_template_id' => 'nullable|string|required_if:use_whatsapp_notification,1|exists:whatsapp_templates,id',
            'branch_id' => 'required|exists:company_branch,id',
            'company_division_id' => 'nullable|string|exists:company_division,id',
            'no_booth' => 'required|string|max:255',
            'requires_payment' => 'nullable|boolean',
            'payment_amount' => 'nullable|required_if:requires_payment,1|numeric|min:0',
            'payment_position' => 'nullable|in:before_questions,after_questions',
            'is_callback_enabled' => 'nullable|boolean',
            'callback_link' => 'nullable|required_if:is_callback_enabled,1|url|max:500',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'has_personal_data_stage' => 'nullable|boolean',
            'result_mode' => 'nullable|in:none,auto,manual,section_threshold',
            'section_fail_threshold' => 'nullable|integer|min:1|max:50',
            'section_pass_threshold' => 'nullable|integer|min:0|max:50',
            'timer_enabled' => 'nullable|boolean',
            'timer_duration_minutes' => 'nullable|required_if:timer_enabled,1|integer|min:1|max:600',
            'timer_auto_save' => 'nullable|boolean',
            'timer_auto_restart' => 'nullable|boolean',
        ]);

        $validated['pre_test_notice'] = $this->sanitizeNoticeHtml($validated['pre_test_notice'] ?? null);

        $validated['requires_payment'] = $request->boolean('requires_payment');
        if (!$validated['requires_payment']) {
            $validated['payment_amount'] = null;
        }
        $validated['payment_position'] = $validated['payment_position'] ?? 'before_questions';

        $validated['is_callback_enabled'] = $request->boolean('is_callback_enabled');
        if (!$validated['is_callback_enabled']) {
            $validated['callback_link'] = null;
        }

        $validated['use_whatsapp_notification'] = $request->boolean('use_whatsapp_notification');
        if (!$validated['use_whatsapp_notification']) {
            $validated['whatsapp_template_id'] = null;
        }

        $validated['has_personal_data_stage'] = $request->boolean('has_personal_data_stage');
        $validated['result_mode'] = $validated['result_mode'] ?? 'none';
        $validated['company_division_id'] = $validated['company_division_id'] ?? null;
        $validated = $this->applySectionThresholdFields($validated);

        $validated = $this->applyTimerFields($request, $validated);

        // Slug (branch + booth) dibuat ulang kalau branch/no_booth berubah, atau form lama belum punya slug.
        $branch = CompanyBranch::find($validated['branch_id']);
        $newBranchSlug = Str::slug($branch?->name ?? 'branch') ?: 'branch';

        if (
            $newBranchSlug !== $existing->slug ||
            $validated['no_booth'] !== $existing->no_booth ||
            empty($existing->booth_slug)
        ) {
            $validated['slug'] = $newBranchSlug;
            $validated['booth_slug'] = $this->generateUniqueBoothSlug($newBranchSlug, $validated['no_booth'], $existing->id);
        }

        // Ganti background/logo hanya kalau admin upload file baru (file lama
        // dihapus dari disk supaya tidak menumpuk). Kalau tidak upload apa-apa,
        // key-nya dibuang dari $validated supaya AdminCrud::update() tidak
        // menimpa nilai lama dengan null — background/logo yang sudah ada
        // (atau memang belum pernah diisi) tetap seperti semula.
        if ($request->hasFile('background_image')) {
            $validated['background_image'] = $this->storeFormFile($request->file('background_image'), 'background');
            $this->deleteFormFile($existing->background_image);
        } else {
            unset($validated['background_image']);
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeFormFile($request->file('logo'), 'logo');
            $this->deleteFormFile($existing->logo);
        } else {
            unset($validated['logo']);
        }

        AdminCrud::update(Form::class, $id, $validated, null);

        return redirect()
            ->route('quiz.form.index')
            ->with('success', 'Form berhasil diupdate.');
    }

    /**
     * Normalisasi 2 field ambang batas mode hasil "section_threshold" (dipakai
     * bareng oleh store() & update()) — pola sama dengan applyTimerFields() di
     * bawah: gerbangnya adalah result_mode, bukan checkbox tersendiri.
     *
     * - result_mode === 'section_threshold': kalau admin tidak mengisi
     *   angkanya, dipakai default semantik 3 (fail) / 1 (pass) — lihat
     *   FrontendController::computeSectionThresholdResult() untuk bagaimana
     *   angka ini dipakai. Ditolak (ValidationException) kalau pass >= fail,
     *   karena kombinasi itu tidak pernah bisa masuk ke "zona abu-abu" mana
     *   pun dari algoritmanya (pass harus lebih kecil dari fail).
     * - result_mode lainnya: kedua kolom dipaksa NULL, supaya tidak ada
     *   angka lama yang "nyangkut" dari form yang PERNAH memakai mode ini
     *   lalu dipindah ke mode lain.
     */
    private function applySectionThresholdFields(array $validated): array
    {
        if ($validated['result_mode'] !== 'section_threshold') {
            $validated['section_fail_threshold'] = null;
            $validated['section_pass_threshold'] = null;

            return $validated;
        }

        $failThreshold = $validated['section_fail_threshold'] ?? 3;
        $passThreshold = $validated['section_pass_threshold'] ?? 1;

        if ($passThreshold >= $failThreshold) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'section_pass_threshold' => 'Section Pass Threshold harus lebih kecil dari Section Fail Threshold.',
            ]);
        }

        $validated['section_fail_threshold'] = $failThreshold;
        $validated['section_pass_threshold'] = $passThreshold;

        return $validated;
    }

    /**
     * Normalisasi 4 field timer placement test (dipakai bareng oleh store() & update()).
     * "Aktifkan Timer" (timer_enabled) adalah gerbang utama — begitu dimatikan, durasi
     * dan kedua toggle auto-save/auto-restart ikut dipaksa kosong/false, supaya tidak ada
     * nilai lama yang "nyangkut" nyala di database padahal timer-nya sendiri sudah off.
     */
    private function applyTimerFields(Request $request, array $validated): array
    {
        $validated['timer_enabled'] = $request->boolean('timer_enabled');

        if (!$validated['timer_enabled']) {
            $validated['timer_duration_minutes'] = null;
            $validated['timer_auto_save'] = false;
            $validated['timer_auto_restart'] = false;
        } else {
            $validated['timer_auto_save'] = $request->boolean('timer_auto_save');
            $validated['timer_auto_restart'] = $request->boolean('timer_auto_restart');
        }

        return $validated;
    }

    /**
     * Buat booth_slug unik dari no_booth, di-scope per branch slug (supaya URL-nya jadi
     * /quiz/{branchSlug}/{boothSlug} dan tetap unik meskipun booth-nya banyak).
     */
    private function generateUniqueBoothSlug(string $branchSlug, string $source, ?string $excludeId = null): string
    {
        $base = Str::slug($source) ?: 'booth';
        $slug = $base;
        $counter = 2;

        while (
            Form::where('slug', $branchSlug)
                ->where('booth_slug', $slug)
                ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $this->resolveVisibleForm($id);

        AdminCrud::delete(Form::class, $id, null);

        return redirect()
            ->route('quiz.form.index')
            ->with('success', 'Form berhasil dihapus.');
    }

    /**
     * "Reset" semua jejak submission form ini — dipakai admin sebelum publish
     * form ke publik, buat bersihin submission percobaan/testing tanpa perlu
     * hapus form-nya sendiri. Form, form_questions, dan
     * form_question_options (question bank/konfigurasinya) TETAP ada —
     * cuma histori "siapa yang pernah submit form ini" yang dibuang, sampai
     * ke jawaban/hasil/pembayaran per-submission-nya juga (bukan cuma baris
     * form_submissions-nya doang).
     *
     * Ini hard delete permanen (bukan soft-delete), dibungkus 1 DB
     * transaction supaya kalau salah satu query gagal di tengah jalan,
     * semuanya di-rollback (tidak ada submission yang "setengah kehapus").
     *
     * Kolom form_id/submission_id di project ini semuanya cuma char(36)
     * yang di-index (tidak ada foreign key constraint asli di database,
     * jadi tidak ada ON DELETE CASCADE dari MySQL) — makanya tiap tabel
     * anak dihapus manual satu-satu di sini, urutannya anak dulu baru induk
     * (form_submissions paling akhir), biar tidak ninggalin baris yatim.
     *
     * TIDAK disentuh: forms, form_questions, form_question_options, dan
     * `students` (database master student dipakai bareng-bareng lintas
     * form lain, jadi row student-nya sendiri tidak boleh ikut kehapus).
     */
    public function resetSubmissions(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $form = $this->resolveVisibleForm($id);

        DB::transaction(function () use ($form) {
            $submissionIds = FormSubmission::where('form_id', $form->id)->pluck('id');

            if ($submissionIds->isNotEmpty()) {
                ClassEnrollment::whereIn('form_submission_id', $submissionIds)->delete();
                FormResult::whereIn('form_submission_id', $submissionIds)->delete();
                FormAnswer::whereIn('submission_id', $submissionIds)->delete();
                FormPayment::whereIn('form_submission_id', $submissionIds)->delete();
            }

            // Transaksi pembayaran form ini yang belum sempat "nyambung" ke
            // submission mana pun (form_submission_id masih NULL — mis.
            // peserta bayar tapi tidak lanjut submit) ikut dibuang juga,
            // karena sama-sama data percobaan sebelum publish.
            FormPayment::where('form_id', $form->id)->whereNull('form_submission_id')->delete();

            FormSubmission::where('form_id', $form->id)->delete();
        });

        return redirect()
            ->route('quiz.form.index')
            ->with('success', 'Semua submission form "' . $form->name . '" (beserta jawaban, hasil, dan histori pembayarannya) berhasil direset. Form, pertanyaan, dan pilihan jawabannya tetap ada.');
    }

    /**
     * Duplikat form ini secara PENUH: Form itu sendiri, semua Section, semua
     * Question (termasuk pertanyaan bercabang lewat parent_option_id), semua
     * Option jawabannya, DAN file fisiknya (background_image, logo, question
     * image/audio, option image) — bukan cuma referensi path-nya, betul-betul
     * disalin jadi file baru di disk (lihat copyPublicFile()) supaya form
     * asli & hasil duplikatnya independen: menghapus/mengganti file di salah
     * satu form tidak memengaruhi yang lain.
     *
     * Hasil duplikat SELALU berstatus 'inactive' apa pun status form aslinya
     * — supaya tidak bisa diisi publik sebelum admin sempat meninjau &
     * mengaktifkannya secara sadar (mis. cek ulang pertanyaan/section yang
     * baru ter-duplikat, ganti nama/no booth kalau perlu, dst).
     *
     * Submission/jawaban/hasil/pembayaran TIDAK ikut diduplikat — form baru
     * selalu mulai bersih tanpa histori peserta form aslinya (beda dengan
     * resetSubmissions() di atas yang membersihkan form yang SAMA).
     *
     * Struktur pertanyaan bercabang (parent_option_id bisa menunjuk opsi
     * milik pertanyaan LAIN di form yang sama, tanpa urutan yang dijamin di
     * database) tidak bisa langsung disalin satu-persatu, karena opsi
     * pemicunya sendiri belum tentu sudah dibuat duluan. Makanya proses ini
     * dipecah 3 tahap di dalam satu DB transaction:
     * 1) Salin semua Section (bangun peta id lama -> id baru), lalu salin
     *    semua Question pakai section_id dari peta itu (parent_option_id
     *    DIBIARKAN NULL dulu; bangun peta id lama -> id baru + simpan
     *    parent_option_id LAMA tiap question baru yang aslinya punya).
     * 2) Salin semua Option milik question-question form ini pakai
     *    question_id dari peta id Question tahap 1 (bangun peta id Option
     *    lama -> id baru).
     * 3) Baru sekarang isi ulang parent_option_id tiap Question baru yang
     *    aslinya punya parent_option_id, pakai peta id Option dari tahap 2 —
     *    karena opsi pemicunya bisa saja baru dibuat di tahap 2, SETELAH
     *    question-nya sendiri dibuat di tahap 1.
     */
    public function duplicate(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $original = $this->resolveVisibleForm($id);

        $newForm = DB::transaction(function () use ($original, $userId) {
            $newFormData = $original->only([
                'branch_id',
                'company_division_id',
                'no_booth',
                'requires_payment',
                'payment_amount',
                'payment_position',
                'is_callback_enabled',
                'callback_link',
                'use_whatsapp_notification',
                'whatsapp_template_id',
                'has_personal_data_stage',
                'result_mode',
                'section_fail_threshold',
                'section_pass_threshold',
                'description',
                'pre_test_notice',
                'start_date',
                'end_date',
                'timer_enabled',
                'timer_duration_minutes',
                'timer_auto_save',
                'timer_auto_restart',
            ]);

            $newFormData['user_id'] = (string) $userId;
            $newFormData['name'] = $original->name . ' - Copy';

            // Duplikat SELALU inactive, apa pun status form aslinya — lihat
            // docblock method ini.
            $newFormData['status'] = 'inactive';

            // slug (branch) tetap sama persis dengan form asli (branch-nya
            // memang sama), booth_slug dibuat ulang dari no_booth yang sama
            // — generateUniqueBoothSlug() otomatis mendeteksi bahwa
            // kombinasi slug+booth_slug asli sudah dipakai (oleh form
            // aslinya sendiri) dan menambahkan suffix "-2", "-3", dst.
            $newFormData['slug'] = $original->slug;
            $newFormData['booth_slug'] = $this->generateUniqueBoothSlug($original->slug, $original->no_booth);

            $newFormData['background_image'] = $this->copyPublicFile($original->background_image);
            $newFormData['logo'] = $this->copyPublicFile($original->logo);

            $newForm = Form::create($newFormData);

            // --- Tahap 1: Section (parent_section_id null dulu), lalu Question (parent_option_id null dulu) ---
            // Section duplikat sendiri butuh 2 sub-tahap yang sama alasannya dengan
            // Question/parent_option_id di Tahap 3 di bawah: Sub Section bisa saja
            // di-query SEBELUM Section induknya sempat dibuat (urutan dari DB tidak
            // dijamin), jadi id induk yang baru belum tentu ada waktu Sub Section-nya
            // sendiri dibuat.
            $sectionIdMap = [];
            // [newSectionId => oldParentSectionId] — cuma diisi untuk Sub Section
            // (section yang aslinya benar-benar punya parent_section_id).
            $sectionOldParentId = [];
            $sections = FormSection::where('form_id', $original->id)->get();
            foreach ($sections as $section) {
                $newSection = FormSection::create([
                    'user_id' => (string) $userId,
                    'form_id' => $newForm->id,
                    'parent_section_id' => null,
                    'name' => $section->name,
                    'description' => $section->description,
                    'order' => $section->order,
                    'status' => $section->status,
                ]);

                $sectionIdMap[$section->id] = $newSection->id;

                if (!empty($section->parent_section_id)) {
                    $sectionOldParentId[$newSection->id] = $section->parent_section_id;
                }
            }

            foreach ($sectionOldParentId as $newSectionId => $oldParentSectionId) {
                $newParentSectionId = $sectionIdMap[$oldParentSectionId] ?? null;

                if ($newParentSectionId) {
                    FormSection::where('id', $newSectionId)->update(['parent_section_id' => $newParentSectionId]);
                }
                // Kalau Section induk aslinya somehow tidak ikut ter-duplikat
                // (idealnya tidak pernah terjadi, karena semua Section form ini
                // diproses di loop atas), Sub Section ini dibiarkan jadi
                // top-level (parent_section_id tetap null) daripada exception
                // di tengah transaction — sama pola dengan fallback
                // parent_option_id di Tahap 3.
            }

            $questionIdMap = [];
            // [newQuestionId => oldParentOptionId] — dipakai di tahap 3, cuma
            // diisi untuk question yang aslinya benar-benar punya parent_option_id.
            $questionOldParentOptionId = [];
            $questions = FormQuestion::where('form_id', $original->id)->get();
            foreach ($questions as $question) {
                $newQuestion = FormQuestion::create([
                    'user_id' => (string) $userId,
                    'form_id' => $newForm->id,
                    'parent_option_id' => null,
                    'stage_group' => $question->stage_group,
                    'section_id' => $question->section_id ? ($sectionIdMap[$question->section_id] ?? null) : null,
                    'question_text' => $question->question_text,
                    'description' => $question->description,
                    'image' => $this->copyPublicFile($question->image),
                    'audio' => $this->copyPublicFile($question->audio),
                    'type' => $question->type,
                    'correct_answer' => $question->correct_answer,
                    'match_score' => $question->match_score,
                    'required' => $question->required,
                    'order' => $question->order,
                    'status' => $question->status,
                ]);

                $questionIdMap[$question->id] = $newQuestion->id;

                if (!empty($question->parent_option_id)) {
                    $questionOldParentOptionId[$newQuestion->id] = $question->parent_option_id;
                }
            }

            // --- Tahap 2: Option ---
            $optionIdMap = [];
            if (!empty($questionIdMap)) {
                $options = FormQuestionOption::whereIn('question_id', array_keys($questionIdMap))->get();

                foreach ($options as $option) {
                    $newQuestionId = $questionIdMap[$option->question_id] ?? null;
                    if (!$newQuestionId) {
                        // Seharusnya tidak pernah terjadi (semua Option di sini
                        // pasti anak dari salah satu Question form ini, karena
                        // di-query lewat whereIn question_id) — dilewati sebagai
                        // pengaman murni.
                        continue;
                    }

                    $newOption = FormQuestionOption::create([
                        'user_id' => (string) $userId,
                        'question_id' => $newQuestionId,
                        'order' => $option->order,
                        'option_text' => $option->option_text,
                        'image' => $this->copyPublicFile($option->image),
                        'score' => $option->score,
                        'is_other' => $option->is_other,
                        'status' => $option->status,
                    ]);

                    $optionIdMap[$option->id] = $newOption->id;
                }
            }

            // --- Tahap 3: isi ulang parent_option_id pertanyaan bercabang ---
            foreach ($questionOldParentOptionId as $newQuestionId => $oldParentOptionId) {
                $newParentOptionId = $optionIdMap[$oldParentOptionId] ?? null;

                if ($newParentOptionId) {
                    FormQuestion::where('id', $newQuestionId)->update(['parent_option_id' => $newParentOptionId]);
                }
                // Kalau opsi pemicu aslinya somehow tidak ikut ter-duplikat
                // (idealnya tidak pernah terjadi, karena semua Option milik
                // question form ini pasti diproses di tahap 2), pertanyaan
                // anak ini dibiarkan jadi pertanyaan root (parent_option_id
                // tetap null) daripada exception di tengah transaction.
            }

            return $newForm;
        });

        return redirect()
            ->route('quiz.form.index')
            ->with('success', 'Form "' . $original->name . '" berhasil diduplikat menjadi "' . $newForm->name . '" (status: Inactive — silakan tinjau dulu sebelum diaktifkan).');
    }

    /**
     * Salin 1 file dari public/ ke file BARU (nama UUID baru) di folder
     * relatif yang SAMA persis, dipakai duplicate() untuk background_image,
     * logo, question image/audio, dan option image — supaya form hasil
     * duplikat punya file fisiknya sendiri (bukan sekadar menunjuk ke file
     * milik form asli), sehingga aman dihapus/diganti tanpa saling
     * memengaruhi. Aman dipanggil dengan path null/kosong (mis. background
     * belum pernah diupload di form aslinya) — balikin null juga.
     *
     * Kalau baris DB menunjuk path yang file fisiknya sudah tidak ada di
     * disk (data lama yang somehow rusak), duplikat-nya sengaja dibiarkan
     * null (bukan exception) — daripada seluruh proses duplicate() gagal
     * total gara-gara satu file yang hilang.
     */
    private function copyPublicFile(?string $relativePath): ?string
    {
        if (empty($relativePath)) {
            return null;
        }

        $sourcePath = public_path($relativePath);

        if (!file_exists($sourcePath)) {
            return null;
        }

        $directory = dirname($relativePath);
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);

        $destinationDir = public_path($directory);
        if (!file_exists($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $newFilename = Str::uuid() . ($extension ? '.' . $extension : '');
        $destinationPath = $destinationDir . '/' . $newFilename;

        if (!copy($sourcePath, $destinationPath)) {
            return null;
        }

        return $directory . '/' . $newFilename;
    }
}
