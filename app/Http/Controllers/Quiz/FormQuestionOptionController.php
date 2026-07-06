<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\FormQuestion;
use App\Models\FormQuestionOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormQuestionOptionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            FormQuestionOption::class,
            (string) $userId,
            ['option_text', 'status'],
            $search,
            10,
            ['question']
        );

        return view('quiz.form-question-option.index', compact('data'));
    }

    public function create()
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

        return view('quiz.form-question-option.create', compact('questions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_id' => 'required|string|exists:form_questions,id',
            'option_text' => 'required|string|max:255',
            'score' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

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

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(FormQuestionOption::class, $validated);

        return redirect()
            ->route('quiz.form-question-option.index')
            ->with('success', 'Form Question Option berhasil dibuat.');
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

        AdminCrud::findOrFail(FormQuestionOption::class, $id, (string) $userId);

        $validated = $request->validate([
            'question_id' => 'required|string|exists:form_questions,id',
            'option_text' => 'required|string|max:255',
            'score' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        $questionOwned = FormQuestion::query()
            ->where(['id' => $validated['question_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();

        if (!$questionOwned) {
            abort(403, 'Question tidak valid untuk user ini.');
        }

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

        AdminCrud::delete(FormQuestionOption::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.form-question-option.index')
            ->with('success', 'Form Question Option berhasil dihapus.');
    }
}
