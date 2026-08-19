<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\Form;
use App\Models\FormPayment;
use App\Models\FormResult;
use App\Models\FormSubmission;
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

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            Form::class,
            (string) $userId,
            ['name', 'description', 'no_booth'],
            $search,
            10,
            ['companyBranch', 'formSubmissions', 'formPayments']
        );

        // Total form yang sudah dibuat admin ini (tidak terpengaruh search/pagination).
        $totalForms = Form::where('user_id', (string) $userId)->count();

        return view('quiz.form.index', compact('data', 'totalForms'));
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
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $form = AdminCrud::findOrFail(Form::class, $id, (string) $userId);

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

        $form = $submission->form;
        if (!$form || (string) $form->user_id !== (string) $userId) {
            abort(403, 'Submission tidak valid untuk user ini.');
        }

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

                $message = $messenger->buildMessageFromTemplate($form, [
                    'name' => trim($submission->student->first_name . ' ' . $submission->student->last_name),
                    'form_name' => $form->name,
                    'hasil' => $validated['summary_text'],
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

    public function create(Request $request)
    {
        $templates = WhatsappTemplate::where('status', 'active')->get();
        $companyBranches = CompanyBranch::select('id', 'name')->orderBy('name')->get();
        $selectedCompanyBranchId = $request->query('company_branch_id');

        return view('quiz.form.create', compact('templates', 'companyBranches', 'selectedCompanyBranchId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'use_whatsapp_notification' => 'nullable|boolean',
            'whatsapp_template_id' => 'nullable|string|required_if:use_whatsapp_notification,1|exists:whatsapp_templates,id',
            'branch_id' => 'required|exists:company_branch,id',
            'no_booth' => 'required|string|max:255',
            'requires_payment' => 'nullable|boolean',
            'payment_amount' => 'nullable|required_if:requires_payment,1|numeric|min:0',
            'payment_position' => 'nullable|in:before_questions,after_questions',
            'is_callback_enabled' => 'nullable|boolean',
            'callback_link' => 'nullable|required_if:is_callback_enabled,1|url|max:500',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'has_personal_data_stage' => 'nullable|boolean',
            'result_mode' => 'nullable|in:none,auto,manual',
            'timer_enabled' => 'nullable|boolean',
            'timer_duration_minutes' => 'nullable|required_if:timer_enabled,1|integer|min:1|max:600',
            'timer_auto_save' => 'nullable|boolean',
            'timer_auto_restart' => 'nullable|boolean',
        ]);

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

        AdminCrud::create(Form::class, $validated);

        return redirect()
            ->route('quiz.form.index')
            ->with('success', 'Form berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();

        if ($userId === null) {
            abort(401);
        }


        $data = AdminCrud::findOrFail(
            Form::class,
            $id,
            (string) $userId
        );


        $templates = \App\Models\WhatsappTemplate::where('status', 'active')
            ->get();

        $companyBranches = CompanyBranch::select('id', 'name')->orderBy('name')->get();


        return view('quiz.form.edit', compact(
            'data',
            'templates',
            'companyBranches'
        ));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $existing = AdminCrud::findOrFail(Form::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'use_whatsapp_notification' => 'nullable|boolean',
            'whatsapp_template_id' => 'nullable|string|required_if:use_whatsapp_notification,1|exists:whatsapp_templates,id',
            'branch_id' => 'required|exists:company_branch,id',
            'no_booth' => 'required|string|max:255',
            'requires_payment' => 'nullable|boolean',
            'payment_amount' => 'nullable|required_if:requires_payment,1|numeric|min:0',
            'payment_position' => 'nullable|in:before_questions,after_questions',
            'is_callback_enabled' => 'nullable|boolean',
            'callback_link' => 'nullable|required_if:is_callback_enabled,1|url|max:500',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'has_personal_data_stage' => 'nullable|boolean',
            'result_mode' => 'nullable|in:none,auto,manual',
            'timer_enabled' => 'nullable|boolean',
            'timer_duration_minutes' => 'nullable|required_if:timer_enabled,1|integer|min:1|max:600',
            'timer_auto_save' => 'nullable|boolean',
            'timer_auto_restart' => 'nullable|boolean',
        ]);

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

        AdminCrud::update(Form::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.form.index')
            ->with('success', 'Form berhasil diupdate.');
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

        AdminCrud::delete(Form::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.form.index')
            ->with('success', 'Form berhasil dihapus.');
    }
}
