<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ['name', 'description'],
            $search,
            10
        );

        return view('quiz.form.index', compact('data'));
    }

    public function create()
    {
        $templates = WhatsappTemplate::where('status', 'active')->get();

        return view('quiz.form.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'whatsapp_template_id' => 'nullable|string|exists:whatsapp_templates,id',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

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


        return view('quiz.form.edit', compact(
            'data',
            'templates'
        ));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(Form::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'whatsapp_template_id' => 'nullable|string|exists:whatsapp_templates,id',
        ]);

        AdminCrud::update(Form::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.form.index')
            ->with('success', 'Form berhasil diupdate.');
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
