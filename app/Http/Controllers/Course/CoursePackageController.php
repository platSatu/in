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

    /**
     * Rate konversi Duration -> Credits (15-16 September 2026, permintaan
     * user -- "credits ini yang akan dihitung nanti", dipakai sebagai dasar
     * ledger credit student & absensi kursus InaYule). Definisi 1 credit =
     * 1 jam kelas, dikonfirmasi user: 1 Month = 8 credit (1 jam x 8 sesi).
     *
     * Rate 'week' & 'day' MASIH ASUMSI (diturunkan dari 8/month ÷ 4 minggu,
     * dan 1 sesi/hari) -- BELUM dikonfirmasi eksplisit oleh user, tolong
     * sesuaikan begitu ada kepastian. Rate 'session' sengaja 1:1 karena
     * satuannya sendiri sudah representasi 1 sesi.
     *
     * PENTING: nilai Credits yang TERSIMPAN selalu dihitung ulang di sini
     * (lihat calculateCredits(), dipanggil dari store()/update()) --
     * SENGAJA TIDAK dipercaya dari input form (field Credits di
     * create/edit.blade.php dibuat readonly, cuma preview JS di sisi
     * client), supaya tidak bisa disimpangi & selalu konsisten jadi dasar
     * perhitungan ledger credit nanti.
     *
     * @var array<string, float>
     */
    private const CREDITS_PER_DURATION_UNIT = [
        'day' => 1,
        'week' => 2,
        'month' => 8,
        'session' => 1,
    ];

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
        $validated['credits'] = $this->calculateCredits((int) $validated['duration_value'], $validated['duration_unit']);

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
        $validated['credits'] = $this->calculateCredits((int) $validated['duration_value'], $validated['duration_unit']);

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
     * Duplikat 1 Course Package (permintaan user, 16 September 2026 --
     * "pastikan semuanya tercopy sama persis, name-nya tambahkan '- Copy'").
     *
     * SEMUA kolom produk disalin apa adanya dari baris asli (type/class/
     * level/duration/price/promo_price/description/status) -- cuma `name`
     * yang diubah (ditambah suffix ' - Copy') dan `user_id` yang diisi
     * ulang jadi admin yang lagi melakukan copy ini (bukan ikut punya
     * admin pembuat baris asli). Credits SENGAJA dihitung ULANG lewat
     * calculateCredits() (bukan sekadar disalin dari $original->credits)
     * supaya tetap konsisten dengan aturan "credits selalu dihitung ulang
     * di server" yang sama dipakai di store()/update() -- toh hasilnya
     * pasti identik selama duration_value/duration_unit ikut disalin
     * persis, kecuali rate di CREDITS_PER_DURATION_UNIT pernah berubah
     * setelah baris asli dibuat.
     */
    public function copy(string $id)
    {
        $original = AdminCrud::findOrFail(CoursePackage::class, $id);

        $userId = Auth::id();

        $data = [
            'name' => $original->name . ' - Copy',
            'course_type_id' => $original->course_type_id,
            'course_class_id' => $original->course_class_id,
            'course_level_id' => $original->course_level_id,
            'duration_value' => $original->duration_value,
            'duration_unit' => $original->duration_unit,
            'price' => $original->price,
            'promo_price' => $original->promo_price,
            'credits' => $this->calculateCredits((int) $original->duration_value, $original->duration_unit),
            'description' => $original->description,
            'status' => $original->status,
            'user_id' => $userId !== null ? (string) $userId : null,
        ];

        AdminCrud::create(CoursePackage::class, $data);

        return redirect()
            ->route('course.package.index')
            ->with('success', 'Course Package berhasil di-copy.');
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
            // FIX (16 September 2026, permintaan user): promo_price OPSIONAL --
            // kalau diisi, harga asli ditampilkan dicoret & ini yang jadi harga
            // jual (lihat CoursePackage::hasActivePromo()/effectivePrice()).
            // 'lt:price' otomatis menolak kalau promo_price >= price.
            'promo_price' => ['nullable', 'numeric', 'min:0', 'lt:price', 'max:999999999.99'],
            // 'credits' SENGAJA tidak divalidasi/diambil dari input di sini --
            // selalu dihitung ulang lewat calculateCredits() di store()/update(),
            // lihat docblock CREDITS_PER_DURATION_UNIT di atas.
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);
    }

    /**
     * Hitung Credits dari Duration + Duration Unit (lihat docblock
     * CREDITS_PER_DURATION_UNIT di atas untuk rate & alasannya). Dibulatkan
     * 2 desimal supaya konsisten dengan cast 'credits' => 'decimal:2' di
     * App\Models\CoursePackage.
     */
    private function calculateCredits(int $durationValue, string $durationUnit): float
    {
        $rate = self::CREDITS_PER_DURATION_UNIT[$durationUnit] ?? 0;

        return round($durationValue * $rate, 2);
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
