<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
use App\Models\Package;
use App\Models\ApplicationCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            Package::class,
            (string) $userId,
            ['name', 'description', 'status'],
            $search,
            10,
            ['applicationCategory']
        );

        return view('package.index', compact('data'));
    }

    public function create()
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        /** @var Builder $categoryQuery */
        $categoryQuery = ApplicationCategory::query();
        $categories = $categoryQuery
            ->where(['user_id' => (string) $userId])
            ->where(['status' => 'active'])
            ->orderBy('name')
            ->get();

        return view('package.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'application_category_id' => 'required|string|exists:application_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $categoryOwned = ApplicationCategory::query()
            ->where(['id' => $validated['application_category_id']])
            ->where(['user_id' => (string) $userId])
            ->exists();

        if (!$categoryOwned) {
            abort(403, 'Category tidak valid untuk user ini.');
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('packages', 'public');
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(Package::class, $validated);

        return redirect()
            ->route('package.index')
            ->with('success', 'Package berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(Package::class, $id, (string) $userId, ['applicationCategory']);

        /** @var Builder $categoryQuery */
        $categoryQuery = ApplicationCategory::query();
        $categories = $categoryQuery
            ->where(['user_id' => (string) $userId])
            ->where(['status' => 'active'])
            ->orderBy('name')
            ->get();

        return view('package.edit', compact('data', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(Package::class, $id, (string) $userId);

        $validated = $request->validate([
            'application_category_id' => 'sometimes|required|string|exists:application_categories,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'sometimes|required|in:active,inactive',
            'price' => 'sometimes|required|numeric|min:0',
            'duration_days' => 'sometimes|required|integer|min:1',
        ]);

        if (isset($validated['application_category_id'])) {
            $categoryOwned = ApplicationCategory::query()
                ->where(['id' => $validated['application_category_id']])
                ->where(['user_id' => (string) $userId])
                ->exists();

            if (!$categoryOwned) {
                abort(403, 'Category tidak valid untuk user ini.');
            }
        }

        if ($request->hasFile('image')) {
            if (!empty($data->image) && Storage::disk('public')->exists($data->image)) {
                Storage::disk('public')->delete($data->image);
            }

            $validated['image'] = $request->file('image')->store('packages', 'public');
        }

        AdminCrud::update(Package::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('package.index')
            ->with('success', 'Package berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(Package::class, $id, (string) $userId);

        if (!empty($data->image) && Storage::disk('public')->exists($data->image)) {
            Storage::disk('public')->delete($data->image);
        }

        AdminCrud::delete(Package::class, $id, (string) $userId);

        return redirect()
            ->route('package.index')
            ->with('success', 'Package berhasil dihapus.');
    }
}
