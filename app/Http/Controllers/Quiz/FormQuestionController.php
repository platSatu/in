<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormQuestionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            FormQuestion::class,
            (string) $userId,
            ['question_text', 'type', 'status'],
            $search,
            10,
            ['form']
        );

        return view('quiz.form-question.index', compact('data'));
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

        return view('quiz.form-question.create', compact('forms'));
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'form_id' => 'required|string|exists:forms,id',
            'question_text' => 'required|string',
            'type' => 'required|in:text,textarea,number,date,single_choice,multiple_choice,dropdown,major',
            'required' => 'required|boolean',
            'order' => 'required|integer|min:0',
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
    
        AdminCrud::create(FormQuestion::class, $validated);
    
        return redirect()
            ->route('quiz.form-question.index')
            ->with('success', 'Form Question berhasil dibuat.');
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
    
        AdminCrud::findOrFail(FormQuestion::class, $id, (string) $userId);
    
        $validated = $request->validate([
            'form_id' => 'required|string|exists:forms,id',
            'question_text' => 'required|string',
            'type' => 'required|in:text,textarea,number,date,single_choice,multiple_choice,dropdown,major',
            'required' => 'required|boolean',
            'order' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);
    
        $formOwned = Form::query()
            ->where(['id' => $validated['form_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();
    
        if (!$formOwned) {
            abort(403, 'Form tidak valid untuk user ini.');
        }
    
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

        AdminCrud::delete(FormQuestion::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.form-question.index')
            ->with('success', 'Form Question berhasil dihapus.');
    }
}
