<?php

namespace App\Http\Controllers\Course;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\CourseLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * CRUD "Course Level" (menu Course > Course Level): master level kemampuan
 * (mis. Beginner / Intermediate / Advanced).
 */
class CourseLevelController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $data = AdminCrud::paginate(
            CourseLevel::class,
            null,
            ['name', 'description'],
            $search,
            10
        );

        return view('course.level.index', compact('data', 'search'));
    }

    public function create()
    {
        return view('course.level.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('course_level', 'name')],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $userId = Auth::id();
        $validated['user_id'] = $userId !== null ? (string) $userId : null;

        AdminCrud::create(CourseLevel::class, $validated);

        return redirect()
            ->route('course.level.index')
            ->with('success', 'Course Level berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(CourseLevel::class, $id);

        return view('course.level.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        AdminCrud::findOrFail(CourseLevel::class, $id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('course_level', 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        AdminCrud::update(CourseLevel::class, $id, $validated);

        return redirect()
            ->route('course.level.index')
            ->with('success', 'Course Level berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $level = AdminCrud::findOrFail(CourseLevel::class, $id);

        if ($level->packages()->exists()) {
            return back()->with('error', 'Course Level ini masih dipakai oleh Course Package, tidak bisa dihapus.');
        }

        AdminCrud::delete(CourseLevel::class, $id);

        return redirect()
            ->route('course.level.index')
            ->with('success', 'Course Level berhasil dihapus.');
    }
}
