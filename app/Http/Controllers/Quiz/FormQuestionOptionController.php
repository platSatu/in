<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Helpers\DataScope;
use App\Http\Controllers\Controller;
use App\Models\FormQuestion;
use App\Models\FormQuestionOption;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FormQuestionOptionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $questionId = $request->query('question_id');

        $user = Auth::user();
        if ($user === null) {
            abort(401);
        }

        // SEBELUMNYA: selalu di-scope ketat `user_id = pembuatnya sendiri`.
        // SEKARANG: FormQuestionOption tidak punya kolom branch/divisi sendiri,
        // cakupannya ikut Form "kakek"-nya lewat question_id -> form_id (lihat
        // App\Helpers\DataScope::visibleFormIds()) — opsi dari form yang boleh
        // dilihat user ini, bukan cuma opsi buatan sendiri.
        $visibleFormIds = DataScope::visibleFormIds($user);

        $query = FormQuestionOption::query()->with('question');

        if ($visibleFormIds !== null) {
            $query->whereHas('question', function ($q) use ($visibleFormIds) {
                $q->whereIn('form_id', $visibleFormIds);
            });
        }

        // Dipanggil dari tombol "Show" di quiz/form-question/index.blade.php ->
        // langsung terfilter cuma jawaban/opsi milik pertanyaan itu saja. Ini yang
        // dipakai supaya alur "cari pertanyaan -> lihat jawabannya -> tambah/edit
        // jawaban" bisa langsung dari 1 halaman ini, tanpa reset filter question_id.
        $filterQuestion = null;

        if (!empty($questionId)) {
            $filterQuestion = FormQuestion::where('id', $questionId)
                ->when($visibleFormIds !== null, fn ($q) => $q->whereIn('form_id', $visibleFormIds))
                ->first();

            $query->where('question_id', $questionId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('option_text', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $data = $query->latest('created_at')->paginate(10)->withQueryString();

        return view('quiz.form-question-option.index', compact('data', 'filterQuestion'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if ($user === null) {
            abort(401);
        }

        $visibleFormIds = DataScope::visibleFormIds($user);

        $questions = FormQuestion::query()
            ->when($visibleFormIds !== null, fn ($q) => $q->whereIn('form_id', $visibleFormIds))
            ->orderBy('question_text')
            ->get();

        $selectedQuestionId = $request->query('question_id');

        return view('quiz.form-question-option.create', compact('questions', 'selectedQuestionId'));
    }

    /**
     * Simpan banyak option sekaligus (mode "add rows") untuk satu pertanyaan.
     * Urutan tersimpan (`order`) mengikuti persis urutan baris yang diinput,
     * disambung dari urutan terakhir yang sudah ada di pertanyaan tersebut —
     * pola yang sama dengan FormQuestionController::store() untuk batch pertanyaan.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|string|exists:form_questions,id',
            'options' => 'required|array|min:1',
            'options.*.option_text' => 'nullable|string|max:255',
            'options.*.image' => 'nullable|image|max:4096',
            'options.*.score' => 'nullable|integer',
            'options.*.is_other' => 'nullable|boolean',
            'options.*.is_correct' => 'nullable|boolean',
            'options.*.status' => 'nullable|in:active,inactive',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('options', []) as $key => $row) {
                $hasText = trim((string) ($row['option_text'] ?? '')) !== '';
                $hasImage = $request->hasFile("options.$key.image");

                if (!$hasText && !$hasImage) {
                    $validator->errors()->add(
                        'options',
                        'Baris ke-' . ((int) $key + 1) . ': isi minimal salah satu dari teks atau gambar.'
                    );
                }
            }
        });

        $validated = $validator->validate();

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        if (!$this->isQuestionAccessible($validated['question_id'])) {
            abort(403, 'Question tidak valid untuk cakupan akses Anda.');
        }

        $existingCount = FormQuestionOption::where('question_id', $validated['question_id'])->count();
        $nextOrder = $existingCount > 0
            ? ((int) FormQuestionOption::where('question_id', $validated['question_id'])->max('order')) + 1
            : 0;

        // Sengaja TIDAK pakai array_values(): kalau ada baris yang dihapus di
        // tengah lewat JS sebelum submit, index request (mis. options[2]) bisa
        // punya "lubang" — pakai $key asli supaya file yang diambil lewat
        // $request->file("options.$key.image") tetap cocok dengan barisnya.
        $position = 0;
        foreach ($validated['options'] as $key => $row) {
            $imagePath = $request->hasFile("options.$key.image")
                ? $this->storeOptionFile($request->file("options.$key.image"))
                : null;

            FormQuestionOption::create([
                'user_id' => (string) $userId,
                'question_id' => $validated['question_id'],
                'order' => $nextOrder + $position,
                'option_text' => $row['option_text'] ?? null,
                'image' => $imagePath,
                'score' => $row['score'] ?? null,
                // "Lainnya" (isian bebas) — dipakai di pertanyaan Multiple Choice,
                // lihat frontend/partials/question-card.blade.php.
                'is_other' => !empty($row['is_other']),
                // Penanda jawaban benar — dipakai mode hasil "section_threshold"
                // (lihat FrontendController::isQuestionAnsweredCorrectly()).
                // Tidak berpengaruh apa-apa untuk form yang tidak memakai mode
                // hasil ini.
                'is_correct' => !empty($row['is_correct']),
                'status' => $row['status'] ?? 'active',
            ]);

            $position++;
        }

        // Balik ke halaman daftar opsi pertanyaan INI SAJA (difilter question_id),
        // konsisten dengan halaman yang tadi dibuka user sebelum klik "+ Add Options".
        return redirect()
            ->route('quiz.form-question-option.index', ['question_id' => $validated['question_id']])
            ->with('success', count($validated['options']) . ' option berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $user = Auth::user();
        if ($user === null) {
            abort(401);
        }

        $data = $this->resolveVisibleOption($id);

        $visibleFormIds = DataScope::visibleFormIds($user);

        $questions = FormQuestion::query()
            ->when($visibleFormIds !== null, fn ($q) => $q->whereIn('form_id', $visibleFormIds))
            ->orderBy('question_text')
            ->get();

        return view('quiz.form-question-option.edit', compact('data', 'questions'));
    }

    public function update(Request $request, string $id)
    {
        if (Auth::id() === null) {
            abort(401);
        }

        $existing = $this->resolveVisibleOption($id);

        $validator = Validator::make($request->all(), [
            'question_id' => 'required|string|exists:form_questions,id',
            'option_text' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:4096',
            'remove_image' => 'nullable|boolean',
            'score' => 'nullable|integer',
            'is_other' => 'nullable|boolean',
            'is_correct' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $validator->after(function ($validator) use ($request, $existing) {
            $hasText = trim((string) $request->input('option_text', '')) !== '';
            $willHaveImage = $request->hasFile('image')
                || (!empty($existing->image) && !$request->boolean('remove_image'));

            if (!$hasText && !$willHaveImage) {
                $validator->errors()->add('option_text', 'Isi minimal salah satu dari teks atau gambar.');
            }
        });

        $validated = $validator->validate();

        // Checkbox yang tidak dicentang tidak ikut terkirim sama sekali di request,
        // jadi kalau tidak di-set eksplisit di sini, AdminCrud::update() cuma akan
        // membiarkan nilai is_other/is_correct lama tidak berubah (bukan jadi
        // false) — beda dengan pola $validated['x'] = $request->boolean('x')
        // yang sudah dipakai di FormController untuk toggle serupa.
        $validated['is_other'] = $request->boolean('is_other');
        $validated['is_correct'] = $request->boolean('is_correct');

        if (!$this->isQuestionAccessible($validated['question_id'])) {
            abort(403, 'Question tidak valid untuk cakupan akses Anda.');
        }

        if ($request->hasFile('image')) {
            $this->deleteOptionFile($existing->image);
            $validated['image'] = $this->storeOptionFile($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $this->deleteOptionFile($existing->image);
            $validated['image'] = null;
        } else {
            $validated['image'] = $existing->image;
        }

        unset($validated['remove_image']);

        AdminCrud::update(FormQuestionOption::class, $id, $validated, null);

        return redirect()
            ->route('quiz.form-question-option.index')
            ->with('success', 'Form Question Option berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        if (Auth::id() === null) {
            abort(401);
        }

        $existing = $this->resolveVisibleOption($id);

        // Pertanyaan bercabang: opsi ini bisa saja jadi pemicu pertanyaan anak
        // (parent_option_id). Kalau dihapus begitu saja, pertanyaan anaknya akan
        // jadi "yatim" (tidak pernah bisa muncul lagi, tapi datanya masih ada) —
        // diblokir dulu di sini, sama seperti pola ClassScheduleController::destroy()
        // yang memblokir hapus jadwal kalau masih ada peserta terdaftar.
        $hasChildQuestions = FormQuestion::where('parent_option_id', $id)
            ->where('status', 'active')
            ->exists();

        if ($hasChildQuestions) {
            return redirect()
                ->route('quiz.form-question-option.index')
                ->withErrors(['delete' => 'Option ini masih memiliki pertanyaan cabang (anak). Hapus atau alihkan dulu pertanyaan cabangnya sebelum menghapus option ini.']);
        }

        $this->deleteOptionFile($existing->image);

        AdminCrud::delete(FormQuestionOption::class, $id, null);

        return redirect()
            ->route('quiz.form-question-option.index')
            ->with('success', 'Form Question Option berhasil dihapus.');
    }

    /**
     * Simpan file gambar opsi ke public/form-question-option/image, mengikuti
     * pola yang sama dengan UniversityController::storeUniversityFile().
     */
    private function storeOptionFile(UploadedFile $file): string
    {
        $destination = public_path('form-question-option/image');

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return 'form-question-option/image/' . $filename;
    }

    /**
     * Hapus file gambar opsi lama dari public/ kalau ada. Aman dipanggil
     * dengan path null/kosong.
     */
    private function deleteOptionFile(?string $relativePath): void
    {
        if (empty($relativePath)) {
            return;
        }

        $fullPath = public_path($relativePath);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * FormQuestionOption yang boleh diakses user ini, ditentukan lewat cakupan
     * Form "kakek"-nya (question_id -> form_id, lihat
     * App\Helpers\DataScope::visibleFormIds()) — dipakai menggantikan
     * AdminCrud::findOrFail(FormQuestionOption::class, $id, $userId) yang
     * sebelumnya scope ketat "punya sendiri".
     */
    private function resolveVisibleOption(string $id): FormQuestionOption
    {
        $option = FormQuestionOption::with('question')->findOrFail($id);

        if (!$option->question || !$this->isFormAccessible($option->question->form_id)) {
            abort(403, 'Option tidak valid untuk cakupan akses Anda.');
        }

        return $option;
    }

    private function isQuestionAccessible(?string $questionId): bool
    {
        if (!$questionId) {
            return false;
        }

        $question = FormQuestion::find($questionId);

        return $question !== null && $this->isFormAccessible($question->form_id);
    }

    private function isFormAccessible(?string $formId): bool
    {
        if (!$formId) {
            return false;
        }

        $visibleFormIds = DataScope::visibleFormIds(Auth::user());

        return $visibleFormIds === null || in_array($formId, $visibleFormIds, true);
    }
}
