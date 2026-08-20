<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\FormQuestion;
use App\Models\FormQuestionOption;
use Illuminate\Database\Eloquent\Builder;
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

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $query = FormQuestionOption::query()
            ->with('question')
            ->where('user_id', (string) $userId);

        // Dipanggil dari tombol "Show" di quiz/form-question/index.blade.php ->
        // langsung terfilter cuma jawaban/opsi milik pertanyaan itu saja. Ini yang
        // dipakai supaya alur "cari pertanyaan -> lihat jawabannya -> tambah/edit
        // jawaban" bisa langsung dari 1 halaman ini, tanpa reset filter question_id.
        $filterQuestion = null;

        if (!empty($questionId)) {
            $filterQuestion = FormQuestion::where('id', $questionId)
                ->where('user_id', (string) $userId)
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
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        /** @var Builder $questionQuery */
        $questionQuery = FormQuestion::query();
        $questions = $questionQuery
            ->where(['user_id' => (string) $userId])
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

        $questionOwned = FormQuestion::query()
            ->where(['id' => $validated['question_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();

        if (!$questionOwned) {
            abort(403, 'Question tidak valid untuk user ini.');
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
                'status' => $row['status'] ?? 'active',
            ]);

            $position++;
        }

        return redirect()
            ->route('quiz.form-question-option.index')
            ->with('success', count($validated['options']) . ' option berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(FormQuestionOption::class, $id, (string) $userId, ['question']);

        /** @var Builder $questionQuery */
        $questionQuery = FormQuestion::query();
        $questions = $questionQuery
            ->where(['user_id' => (string) $userId])
            ->orderBy('question_text')
            ->get();

        return view('quiz.form-question-option.edit', compact('data', 'questions'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        /** @var FormQuestionOption $existing */
        $existing = AdminCrud::findOrFail(FormQuestionOption::class, $id, (string) $userId);

        $validator = Validator::make($request->all(), [
            'question_id' => 'required|string|exists:form_questions,id',
            'option_text' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:4096',
            'remove_image' => 'nullable|boolean',
            'score' => 'nullable|integer',
            'is_other' => 'nullable|boolean',
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
        // membiarkan nilai is_other lama tidak berubah (bukan jadi false) — beda
        // dengan pola $validated['x'] = $request->boolean('x') yang sudah dipakai
        // di FormController untuk toggle serupa.
        $validated['is_other'] = $request->boolean('is_other');

        $questionOwned = FormQuestion::query()
            ->where(['id' => $validated['question_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();

        if (!$questionOwned) {
            abort(403, 'Question tidak valid untuk user ini.');
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

        AdminCrud::update(FormQuestionOption::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.form-question-option.index')
            ->with('success', 'Form Question Option berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        /** @var FormQuestionOption $existing */
        $existing = AdminCrud::findOrFail(FormQuestionOption::class, $id, (string) $userId);

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

        AdminCrud::delete(FormQuestionOption::class, $id, (string) $userId);

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
}
