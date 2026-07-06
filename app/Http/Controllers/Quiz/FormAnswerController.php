<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\FormAnswer;
use App\Models\FormQuestion;
use App\Models\FormQuestionOption;
use App\Models\FormSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormAnswerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            FormAnswer::class,
            (string) $userId,
            ['answer_text', 'status'],
            $search,
            10,
            ['submission', 'question', 'option']
        );

        return view('quiz.form-answer.index', compact('data'));
    }

    public function create()
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        /** @var Builder $submissionQuery */
        $submissionQuery = FormSubmission::query();
        $submissions = $submissionQuery
            ->where(['user_id' => (string) $userId])
            ->orderByDesc('created_at')
            ->get();

        /** @var Builder $questionQuery */
        $questionQuery = FormQuestion::query();
        $questions = $questionQuery
            ->where(['user_id' => (string) $userId])
            ->orderBy('question_text')
            ->get();

        /** @var Builder $optionQuery */
        $optionQuery = FormQuestionOption::query();
        $options = $optionQuery
            ->where(['user_id' => (string) $userId])
            ->orderBy('option_text')
            ->get();

        return view('quiz.form-answer.create', compact('submissions', 'questions', 'options'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'submission_id' => 'required|string|exists:form_submissions,id',
            'question_id' => 'required|string|exists:form_questions,id',
            'option_id' => 'nullable|string|exists:form_question_options,id',
            'answer_text' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $submissionOwned = FormSubmission::query()
            ->where(['id' => $validated['submission_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();

        if (!$submissionOwned) {
            abort(403, 'Submission tidak valid untuk user ini.');
        }

        $questionOwned = FormQuestion::query()
            ->where(['id' => $validated['question_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();

        if (!$questionOwned) {
            abort(403, 'Question tidak valid untuk user ini.');
        }

        if (!empty($validated['option_id'])) {
            $optionOwned = FormQuestionOption::query()
                ->where(['id' => $validated['option_id']])
                ->where(['user_id' => (string) $userId])
                ->exists();

            if (!$optionOwned) {
                abort(403, 'Option tidak valid untuk user ini.');
            }
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(FormAnswer::class, $validated);

        return redirect()
            ->route('quiz.form-answer.index')
            ->with('success', 'Form Answer berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(FormAnswer::class, $id, (string) $userId, ['submission', 'question', 'option']);

        /** @var Builder $submissionQuery */
        $submissionQuery = FormSubmission::query();
        $submissions = $submissionQuery
            ->where(['user_id' => (string) $userId])
            ->orderByDesc('created_at')
            ->get();

        /** @var Builder $questionQuery */
        $questionQuery = FormQuestion::query();
        $questions = $questionQuery
            ->where(['user_id' => (string) $userId])
            ->orderBy('question_text')
            ->get();

        /** @var Builder $optionQuery */
        $optionQuery = FormQuestionOption::query();
        $options = $optionQuery
            ->where(['user_id' => (string) $userId])
            ->orderBy('option_text')
            ->get();

        return view('quiz.form-answer.edit', compact('data', 'submissions', 'questions', 'options'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(FormAnswer::class, $id, (string) $userId);

        $validated = $request->validate([
            'submission_id' => 'required|string|exists:form_submissions,id',
            'question_id' => 'required|string|exists:form_questions,id',
            'option_id' => 'nullable|string|exists:form_question_options,id',
            'answer_text' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $submissionOwned = FormSubmission::query()
            ->where(['id' => $validated['submission_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();

        if (!$submissionOwned) {
            abort(403, 'Submission tidak valid untuk user ini.');
        }

        $questionOwned = FormQuestion::query()
            ->where(['id' => $validated['question_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();

        if (!$questionOwned) {
            abort(403, 'Question tidak valid untuk user ini.');
        }

        if (!empty($validated['option_id'])) {
            $optionOwned = FormQuestionOption::query()
                ->where(['id' => $validated['option_id']])
                ->where(['user_id' => (string) $userId])
                ->exists();

            if (!$optionOwned) {
                abort(403, 'Option tidak valid untuk user ini.');
            }
        }

        AdminCrud::update(FormAnswer::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.form-answer.index')
            ->with('success', 'Form Answer berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(FormAnswer::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.form-answer.index')
            ->with('success', 'Form Answer berhasil dihapus.');
    }
}
