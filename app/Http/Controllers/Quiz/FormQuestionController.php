<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FormQuestionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $formId = $request->query('form_id');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $query = FormQuestion::query()
            ->with('form')
            ->where('user_id', (string) $userId);

        // Dipanggil dari tombol "Show Questions" di quiz/form/index.blade.php ->
        // langsung terfilter cuma soal milik form itu saja.
        $filterForm = null;

        if (!empty($formId)) {
            $filterForm = Form::where('id', $formId)
                ->where('user_id', (string) $userId)
                ->first();

            $query->where('form_id', $formId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('question_text', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        // Tetap dikelompokkan per form (bukan flat by created_at) supaya soal dari
        // form yang sama selalu bersebelahan — nama form di tabel jadi bisa
        // ditampilkan sekali saja (lihat pengecekan $item->form_id !== $lastFormId
        // di quiz/form-question/index.blade.php). Kalau sudah terfilter form_id
        // (cuma 1 form), pengelompokan ini otomatis tidak relevan lagi tapi tetap
        // aman dipakai.
        //
        // Urutan antar-grup & di dalam grup sama-sama "terbaru dulu": grup form
        // diurutkan dari form yang punya soal PALING BARU dibuat, dan di dalam
        // satu grup soal yang paling baru diinput tampil paling atas (bukan lagi
        // ikut urutan tampil placement test / kolom "order").
        $data = $query
            ->orderByRaw('(select max(fq2.created_at) from form_questions as fq2 where fq2.form_id = form_questions.form_id) desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('quiz.form-question.index', compact('data', 'filterForm'));
    }

    public function create(Request $request)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        /** @var Builder $formQuery */
        $formQuery = Form::query();
        $forms = $formQuery
            ->where(['user_id' => (string) $userId])
            ->orderBy('name')
            ->get();

        $selectedFormId = $request->query('form_id');

        return view('quiz.form-question.create', compact('forms', 'selectedFormId'));
    }

    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'form_id' => 'required|string|exists:forms,id',
    //         'question_text' => 'required|string',
    //         'type' => 'required|in:single_choice,multiple_choice,text,number,major',
    //         'order' => 'required|integer|min:0',
    //         'status' => 'required|in:active,inactive',
    //     ]);

    //     $userId = Auth::id();
    //     if ($userId === null) {
    //         abort(401);
    //     }

    //     $formOwned = Form::query()
    //         ->where(['id' => $validated['form_id']])
    //         ->where(['user_id' => (string) $userId])
    //         ->exists();

    //     if (!$formOwned) {
    //         abort(403, 'Form tidak valid untuk user ini.');
    //     }

    //     $validated['user_id'] = (string) $userId;

    //     AdminCrud::create(FormQuestion::class, $validated);

    //     return redirect()
    //         ->route('quiz.form-question.index')
    //         ->with('success', 'Form Question berhasil dibuat.');
    // }
    /**
     * Simpan banyak pertanyaan sekaligus (mode "add rows") untuk satu form.
     * Urutan tersimpan (`order`) mengikuti persis urutan baris yang diinput,
     * disambung dari urutan terakhir yang sudah ada di form tersebut.
     *
     * Satu pertanyaan boleh berupa kombinasi bebas teks/audio/gambar (mis.
     * soal Listening: audio + gambar tanpa teks) — makanya question_text
     * TIDAK wajib per-field, tapi minimal salah satu dari ketiganya harus
     * diisi per baris (dicek di validator->after() di bawah).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'form_id' => 'required|string|exists:forms,id',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'nullable|string',
            'questions.*.description' => 'nullable|string',
            'questions.*.type' => 'required|in:text,textarea,number,date,single_choice,multiple_choice,dropdown,major,file',
            'questions.*.stage_group' => 'nullable|in:personal_data,placement_test',
            'questions.*.required' => 'nullable|boolean',
            'questions.*.status' => 'nullable|in:active,inactive',
            'questions.*.image' => 'nullable|image|max:4096',
            'questions.*.audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac|max:8192',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('questions', []) as $key => $row) {
                $hasText = trim((string) ($row['question_text'] ?? '')) !== '';
                $hasImage = $request->hasFile("questions.$key.image");
                $hasAudio = $request->hasFile("questions.$key.audio");

                if (!$hasText && !$hasImage && !$hasAudio) {
                    $validator->errors()->add(
                        'questions',
                        'Baris ke-' . ((int) $key + 1) . ': isi minimal salah satu dari teks, audio, atau gambar.'
                    );
                }
            }
        });

        $validated = $validator->validate();

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $formOwned = Form::query()
            ->where(['id' => $validated['form_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();

        if (!$formOwned) {
            abort(403, 'Form tidak valid untuk user ini.');
        }

        $existingCount = FormQuestion::where('form_id', $validated['form_id'])->count();
        $nextOrder = $existingCount > 0
            ? ((int) FormQuestion::where('form_id', $validated['form_id'])->max('order')) + 1
            : 0;

        // Sengaja TIDAK pakai array_values(): kalau ada baris yang dihapus di
        // tengah lewat JS sebelum submit, index request (mis. questions[2])
        // bisa punya "lubang" — pakai $key asli supaya file yang diambil lewat
        // $request->file("questions.$key.image") tetap cocok dengan barisnya.
        $position = 0;
        foreach ($validated['questions'] as $key => $row) {
            $imagePath = $request->hasFile("questions.$key.image")
                ? $this->storeQuestionFile($request->file("questions.$key.image"), 'image')
                : null;

            $audioPath = $request->hasFile("questions.$key.audio")
                ? $this->storeQuestionFile($request->file("questions.$key.audio"), 'audio')
                : null;

            FormQuestion::create([
                'user_id' => (string) $userId,
                'form_id' => $validated['form_id'],
                'stage_group' => $row['stage_group'] ?? 'placement_test',
                'question_text' => $row['question_text'] ?? null,
                'description' => $row['description'] ?? null,
                'image' => $imagePath,
                'audio' => $audioPath,
                'type' => $row['type'],
                'required' => (bool) ($row['required'] ?? false),
                'order' => $nextOrder + $position,
                'status' => $row['status'] ?? 'active',
            ]);

            $position++;
        }

        return redirect()
            ->route('quiz.form-question.index')
            ->with('success', count($validated['questions']) . ' pertanyaan berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(FormQuestion::class, $id, (string) $userId, ['form']);

        /** @var Builder $formQuery */
        $formQuery = Form::query();
        $forms = $formQuery
            ->where(['user_id' => (string) $userId])
            ->orderBy('name')
            ->get();

        return view('quiz.form-question.edit', compact('data', 'forms'));
    }

    // public function update(Request $request, string $id)
    // {
    //     $userId = Auth::id();
    //     if ($userId === null) {
    //         abort(401);
    //     }

    //     AdminCrud::findOrFail(FormQuestion::class, $id, (string) $userId);

    //     $validated = $request->validate([
    //         'form_id' => 'required|string|exists:forms,id',
    //         'question_text' => 'required|string',
    //         'type' => 'required|in:single_choice,multiple_choice,text,number,major',
    //         'order' => 'required|integer|min:0',
    //         'status' => 'required|in:active,inactive',
    //     ]);

    //     $formOwned = Form::query()
    //         ->where(['id' => $validated['form_id']])
    //         ->where(['user_id' => (string) $userId])
    //         ->exists();

    //     if (!$formOwned) {
    //         abort(403, 'Form tidak valid untuk user ini.');
    //     }

    //     AdminCrud::update(FormQuestion::class, $id, $validated, (string) $userId);

    //     return redirect()
    //         ->route('quiz.form-question.index')
    //         ->with('success', 'Form Question berhasil diupdate.');
    // }
    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        /** @var FormQuestion $existing */
        $existing = AdminCrud::findOrFail(FormQuestion::class, $id, (string) $userId);

        $validator = Validator::make($request->all(), [
            'form_id' => 'required|string|exists:forms,id',
            'question_text' => 'nullable|string',
            'description' => 'nullable|string',
            'type' => 'required|in:text,textarea,number,date,single_choice,multiple_choice,dropdown,major,file',
            'stage_group' => 'nullable|in:personal_data,placement_test',
            'required' => 'required|boolean',
            'order' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
            'image' => 'nullable|image|max:4096',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac|max:8192',
            'remove_image' => 'nullable|boolean',
            'remove_audio' => 'nullable|boolean',
        ]);

        $validator->after(function ($validator) use ($request, $existing) {
            $hasText = trim((string) $request->input('question_text', '')) !== '';
            $willHaveImage = $request->hasFile('image')
                || (!empty($existing->image) && !$request->boolean('remove_image'));
            $willHaveAudio = $request->hasFile('audio')
                || (!empty($existing->audio) && !$request->boolean('remove_audio'));

            if (!$hasText && !$willHaveImage && !$willHaveAudio) {
                $validator->errors()->add('question_text', 'Isi minimal salah satu dari teks, audio, atau gambar.');
            }
        });

        $validated = $validator->validate();

        $formOwned = Form::query()
            ->where(['id' => $validated['form_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();

        if (!$formOwned) {
            abort(403, 'Form tidak valid untuk user ini.');
        }

        if ($request->hasFile('image')) {
            $this->deleteQuestionFile($existing->image);
            $validated['image'] = $this->storeQuestionFile($request->file('image'), 'image');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteQuestionFile($existing->image);
            $validated['image'] = null;
        } else {
            $validated['image'] = $existing->image;
        }

        if ($request->hasFile('audio')) {
            $this->deleteQuestionFile($existing->audio);
            $validated['audio'] = $this->storeQuestionFile($request->file('audio'), 'audio');
        } elseif ($request->boolean('remove_audio')) {
            $this->deleteQuestionFile($existing->audio);
            $validated['audio'] = null;
        } else {
            $validated['audio'] = $existing->audio;
        }

        unset($validated['remove_image'], $validated['remove_audio']);

        $validated['stage_group'] = $validated['stage_group'] ?? 'placement_test';

        AdminCrud::update(FormQuestion::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.form-question.index')
            ->with('success', 'Form Question berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        /** @var FormQuestion $existing */
        $existing = AdminCrud::findOrFail(FormQuestion::class, $id, (string) $userId);

        $this->deleteQuestionFile($existing->image);
        $this->deleteQuestionFile($existing->audio);

        AdminCrud::delete(FormQuestion::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.form-question.index')
            ->with('success', 'Form Question berhasil dihapus.');
    }

    /**
     * Simpan file upload pertanyaan (gambar/audio) ke public/form-question/{folder},
     * mengikuti pola yang sama dengan UniversityController::storeUniversityFile().
     */
    private function storeQuestionFile(UploadedFile $file, string $folder): string
    {
        $destination = public_path('form-question/' . $folder);

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return 'form-question/' . $folder . '/' . $filename;
    }

    /**
     * Hapus file lama dari public/ kalau ada, dipanggil saat file diganti atau
     * pertanyaannya dihapus. Aman dipanggil dengan path null/kosong.
     */
    private function deleteQuestionFile(?string $relativePath): void
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
