<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormSubmissionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            FormSubmission::class,
            (string) $userId,
            ['status'],
            $search,
            10,
            ['form']
        );

        return view('quiz.form-submission.index', compact('data'));
    }

    public function create()
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

        return view('quiz.form-submission.create', compact('forms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'form_id' => 'required|string|exists:forms,id',
            'status' => 'required|in:active,inactive',
        ]);

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

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(FormSubmission::class, $validated);

        return redirect()
            ->route('quiz.form-submission.index')
            ->with('success', 'Form Submission berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(FormSubmission::class, $id, (string) $userId, ['form']);

        /** @var Builder $formQuery */
        $formQuery = Form::query();
        $forms = $formQuery
            ->where(['user_id' => (string) $userId])
            ->orderBy('name')
            ->get();

        return view('quiz.form-submission.edit', compact('data', 'forms'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(FormSubmission::class, $id, (string) $userId);

        $validated = $request->validate([
            'form_id' => 'required|string|exists:forms,id',
            'status' => 'required|in:active,inactive',
        ]);

        $formOwned = Form::query()
            ->where(['id' => $validated['form_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();

        if (!$formOwned) {
            abort(403, 'Form tidak valid untuk user ini.');
        }

        AdminCrud::update(FormSubmission::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.form-submission.index')
            ->with('success', 'Form Submission berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(FormSubmission::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.form-submission.index')
            ->with('success', 'Form Submission berhasil dihapus.');
    }
}
