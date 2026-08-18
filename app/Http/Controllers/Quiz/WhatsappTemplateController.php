<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsappTemplateController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            WhatsappTemplate::class,
            (string) $userId,
            ['name', 'description'],
            $search,
            10
        );

        return view('quiz.whatsapp-template.index', compact('data'));
    }

    public function create()
    {
        return view('quiz.whatsapp-template.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(WhatsappTemplate::class, $validated);

        return redirect()
            ->route('quiz.whatsapp-template.index')
            ->with('success', 'Template WhatsApp berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(WhatsappTemplate::class, $id, (string) $userId);

        return view('quiz.whatsapp-template.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(WhatsappTemplate::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        AdminCrud::update(WhatsappTemplate::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.whatsapp-template.index')
            ->with('success', 'Template WhatsApp berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(WhatsappTemplate::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.whatsapp-template.index')
            ->with('success', 'Template WhatsApp berhasil dihapus.');
    }
}