<?php

namespace App\Http\Controllers\Course;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\CourseLevel;
use App\Models\CoursePackage;
use App\Models\CourseType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * CRUD "Course Package" (menu Course > Course Package): katalog produk
 * kursus siap jual, kombinasi Course Type + Course Class + Course Level +
 * duration + price + credits. Ini adalah SKU yang nanti dipilih end user
 * setelah topup saldo.
 */
class CoursePackageController extends Controller
{
    /**
     * Satuan durasi yang diizinkan -- string (bukan enum di DB) supaya
     * gampang ditambah opsi baru tanpa migration, tapi tetap divalidasi
     * ketat di sini supaya datanya konsisten dipakai di tampilan/laporan.
     *
     * @var array<int, string>
     */
    private const DURATION_UNITS = ['day', 'week', 'month', 'session'];

    /**
     * @var array<int, string>
     */
    private const STATUSES = ['active', 'inactive'];

    public function index(Request $request)
    {
        $search = $request->query('search');

        $data = CoursePackage::query()
            ->with(['type', 'courseClass', 'level'])
            ->when($search, fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('course.package.index', compact('data', 'search'));
    }

    public function create()
    {
        return view('course.package.create', [
            'types' => $this->activeOptions(CourseType::class),
            'classes' => $this->activeOptions(CourseClass::class),
            'levels' => $this->activeOptions(CourseLevel::class),
            'durationUnits' => self::DURATION_UNITS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $userId = Auth::id();
        $validated['user_id'] = $userId !== null ? (string) $userId : null;

        AdminCrud::create(CoursePackage::class, $validated);

        return redirect()
            ->route('course.package.index')
            ->with('success', 'Course Package berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(CoursePackage::class, $id);

        return view('course.package.edit', [
            'data' => $data,
            'types' => $this->activeOptions(CourseType::class, $data->course_type_id),
            'classes' => $this->activeOptions(CourseClass::class, $data->course_class_id),
            'levels' => $this->activeOptions(CourseLevel::class, $data->course_level_id),
            'durationUnits' => self::DURATION_UNITS,
        ]);
    }

    public function update(Request $request, string $id)
    {
        AdminCrud::findOrFail(CoursePackage::class, $id);

        $validated = $this->validated($request);

        AdminCrud::update(CoursePackage::class, $id, $validated);

        return redirect()
            ->route('course.package.index')
            ->with('success', 'Course Package berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        AdminCrud::delete(CoursePackage::class, $id);

        return redirect()
            ->route('course.package.index')
            ->with('success', 'Course Package berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'course_type_id' => ['required', 'uuid', Rule::exists('course_type', 'id')],
            'course_class_id' => ['required', 'uuid', Rule::exists('course_class', 'id')],
            'course_level_id' => ['required', 'uuid', Rule::exists('course_level', 'id')],
            'duration_value' => ['required', 'integer', 'min:1', 'max:120'],
            'duration_unit' => ['required', Rule::in(self::DURATION_UNITS)],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'credits' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);
    }

    /**
     * Dropdown pilihan master data yang berstatus aktif saja -- kalau
     * $selectedId dikasih (dipakai di form edit) & baris itu kebetulan
     * sudah dinonaktifkan setelah package ini dibuat, baris tsb tetap
     * disertakan supaya pilihan yang sedang tersimpan tidak hilang dari
     * dropdown begitu saja.
     *
     * @param class-string<CourseType|CourseClass|CourseLevel> $modelClass
     */
    private function activeOptions(string $modelClass, ?string $selectedId = null)
    {
        return $modelClass::query()
            ->where(function (Builder $q) use ($selectedId) {
                $q->where('status', 'active');

                if ($selectedId !== null) {
                    $q->orWhere('id', $selectedId);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
