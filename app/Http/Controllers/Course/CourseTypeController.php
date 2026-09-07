<?php

namespace App\Http\Controllers\Course;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\CourseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * CRUD "Course Type" (menu Course > Course Type): master format kelas
 * kursus (mis. Private / Semi-Private), dirujuk oleh Course Package. Data
 * bersifat katalog BERSAMA (bukan milik per-admin) -- sengaja tidak
 * di-scope oleh user_id di query, supaya semua admin yang punya izin bisa
 * melihat & mengelola baris yang sama, sama seperti pola zoom_meetings.
 */
class CourseTypeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $data = AdminCrud::paginate(
            CourseType::class,
            null,
            ['name', 'description'],
            $search,
            10
        );

        return view('course.type.index', compact('data', 'search'));
    }

    public function create()
    {
        return view('course.type.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('course_type', 'name')],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $userId = Auth::id();
        $validated['user_id'] = $userId !== null ? (string) $userId : null;

        AdminCrud::create(CourseType::class, $validated);

        return redirect()
            ->route('course.type.index')
            ->with('success', 'Course Type berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(CourseType::class, $id);

        return view('course.type.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        AdminCrud::findOrFail(CourseType::class, $id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('course_type', 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        AdminCrud::update(CourseType::class, $id, $validated);

        return redirect()
            ->route('course.type.index')
            ->with('success', 'Course Type berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $type = AdminCrud::findOrFail(CourseType::class, $id);

        if ($type->packages()->exists()) {
            return back()->with('error', 'Course Type ini masih dipakai oleh Course Package, tidak bisa dihapus.');
        }

        AdminCrud::delete(CourseType::class, $id);

        return redirect()
            ->route('course.type.index')
            ->with('success', 'Course Type berhasil dihapus.');
    }
}
