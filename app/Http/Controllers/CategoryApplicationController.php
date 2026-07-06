<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
use App\Models\ApplicationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CategoryApplicationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            ApplicationCategory::class,
            (string) $userId,
            ['name', 'slug', 'status'],
            $search,
            10
        );

        return view('category-application.index', compact('data'));
    }

    public function create()
    {
        return view('category-application.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:application_categories,slug',
            'status' => 'required|in:active,inactive',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;
        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        AdminCrud::create(ApplicationCategory::class, $validated);

        return redirect()
            ->route('category-application.index')
            ->with('success', 'Category berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(ApplicationCategory::class, $id, (string) $userId);

        return view('category-application.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(ApplicationCategory::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:application_categories,slug,' . $data->id,
            'status' => 'required|in:active,inactive',
        ]);

        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        AdminCrud::update(ApplicationCategory::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('category-application.index')
            ->with('success', 'Category berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(ApplicationCategory::class, $id, (string) $userId);

        return redirect()
            ->route('category-application.index')
            ->with('success', 'Category berhasil dihapus.');
    }
}
