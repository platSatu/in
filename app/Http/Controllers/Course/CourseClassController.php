<?php

namespace App\Http\Controllers\Course;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * CRUD "Course Class" (menu Course > Course Class): master program/subjek
 * kursus (mis. Chinese Adult, Chinese Kids, Conversation Master Class).
 */
class CourseClassController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $data = AdminCrud::paginate(
            CourseClass::class,
            null,
            ['name', 'description'],
            $search,
            10
        );

        return view('course.class.index', compact('data', 'search'));
    }

    public function create()
    {
        return view('course.class.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('course_class', 'name')],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $userId = Auth::id();
        $validated['user_id'] = $userId !== null ? (string) $userId : null;

        AdminCrud::create(CourseClass::class, $validated);

        return redirect()
            ->route('course.class.index')
            ->with('success', 'Course Class berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(CourseClass::class, $id);

        return view('course.class.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        AdminCrud::findOrFail(CourseClass::class, $id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('course_class', 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        AdminCrud::update(CourseClass::class, $id, $validated);

        return redirect()
            ->route('course.class.index')
            ->with('success', 'Course Class berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $class = AdminCrud::findOrFail(CourseClass::class, $id);

        if ($class->packages()->exists()) {
            return back()->with('error', 'Course Class ini masih dipakai oleh Course Package, tidak bisa dihapus.');
        }

        AdminCrud::delete(CourseClass::class, $id);

        return redirect()
            ->route('course.class.index')
            ->with('success', 'Course Class berhasil dihapus.');
    }
}
