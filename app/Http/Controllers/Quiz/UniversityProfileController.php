<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\University;
use App\Models\UniversityProfile;
use App\Models\UniversityProfileDegree;
use App\Models\UniversityProfilePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UniversityProfileController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        // Sama seperti University sendiri -- daftar Profile boleh dilihat SEMUA
        // admin, tidak lagi dibatasi per pembuat.
        $data = AdminCrud::paginate(
            UniversityProfile::class,
            null,
            ['field', 'language', 'status'],
            $search,
            10,
            ['university']
        );

        return view('quiz.university-profile.index', compact('data'));
    }

    // public function create()
    // {
    //     $userId = Auth::id();
    //     if ($userId === null) {
    //         abort(401);
    //     }

    //     $universities = University::query()
    //         ->where('user_id', (string) $userId)
    //         ->orderBy('name')
    //         ->get();

    //     return view('quiz.university-profile.create', compact('universities'));
    // }

    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'university_id' => 'required|string|exists:universities,id',
    //         'field' => 'required|string|max:255',
    //         'min_budget' => 'nullable|integer|min:0',
    //         'max_budget' => 'nullable|integer|min:0|gte:min_budget',
    //         'language' => 'nullable|string|max:255',
    //         'scholarship_available' => 'required|boolean',
    //         'status' => 'required|in:active,inactive',
    //         'degree' => 'nullable',
    //         'intake' => 'nullable',
    //     ]);

    //     $userId = Auth::id();
    //     if ($userId === null) {
    //         abort(401);
    //     }

    //     $universityOwned = University::query()
    //         ->where('id', $validated['university_id'])
    //         ->where('user_id', (string) $userId)
    //         ->exists();

    //     if (!$universityOwned) {
    //         abort(403, 'University tidak valid untuk user ini.');
    //     }

    //     $validated['user_id'] = (string) $userId;

    //     AdminCrud::create(UniversityProfile::class, $validated);

    //     return redirect()
    //         ->route('quiz.university-profile.index')
    //         ->with('success', 'University Profile berhasil dibuat.');
    // }
public function create(Request $request)
{
    $userId = Auth::id();
    if ($userId === null) {
        abort(401);
    }

    // Eager load 'major' supaya view bisa langsung menampilkan Major dari
    // university yang terkunci (dipakai untuk field "Major" yang di-disable).
    // Daftar university di dropdown ini SEMUA university (tidak dibatasi
    // pembuatnya) -- admin manapun boleh bikin Profile untuk university
    // manapun.
    $universities = University::with('major')
        ->orderBy('name')
        ->get();

    $selectedUniversityId = $request->query('university_id');

    return view('quiz.university-profile.create', compact('universities', 'selectedUniversityId'));
}

public function store(Request $request)
{
    $validated = $request->validate([
        'university_id' => 'required|string|exists:universities,id',
        // Degree Title / Key Courses / Entry Requirements: hasil perbandingan
        // dengan brosur kampus (mis. NUAA) — semuanya opsional karena
        // kebutuhan tiap kampus beda-beda, tidak semua profile perlu diisi.
        'degree_title' => 'nullable|string|max:255',
        'key_courses' => 'nullable|string',
        'entry_requirements' => 'nullable|string',
        'min_budget' => 'nullable|integer|min:0',
        'max_budget' => 'nullable|integer|min:0|gte:min_budget',
        'language' => 'nullable|string|max:255',
        'scholarship_available' => 'required|boolean',
        'status' => 'required|in:active,inactive',
        // Degree/Intake sekarang berupa daftar baris ("add row" di form,
        // sama polanya dengan upload foto album) — semuanya opsional karena
        // tidak semua kampus datanya lengkap.
        'degree_intakes' => 'nullable|array',
        'degree_intakes.*.degree' => 'nullable|string|max:255',
        'degree_intakes.*.intake' => 'nullable|string|max:255',
        'degree_intakes.*.duration' => 'nullable|string|max:255',
        // Payment: daftar rincian biaya ("add row" juga), pilih lokasi bayar
        // (Indonesia / China) + nama item + jumlah — semuanya opsional.
        'payments' => 'nullable|array',
        'payments.*.location' => 'nullable|in:indonesia,china',
        'payments.*.name' => 'nullable|string|max:255',
        'payments.*.amount' => 'nullable|integer|min:0',
    ]);

    $userId = Auth::id();
    if ($userId === null) {
        abort(401);
    }

    // Tidak lagi dibatasi ->where('user_id', ...) -- university boleh milik
    // admin manapun, yang penting id-nya valid/ada.
    $university = University::with('major')
        ->where('id', $validated['university_id'])
        ->first();

    if (!$university) {
        abort(403, 'University tidak valid.');
    }

    // Buang baris degree/intake yang semuanya kosong (bukan disimpan
    // sebagai baris kosong).
    $degreeIntakeRows = collect($validated['degree_intakes'] ?? [])
        ->filter(fn ($row) => filled($row['degree'] ?? null) || filled($row['intake'] ?? null) || filled($row['duration'] ?? null))
        ->values();

    unset($validated['degree_intakes']);

    // Sama seperti degree/intake di atas — buang baris payment yang
    // semuanya kosong.
    $paymentRows = collect($validated['payments'] ?? [])
        ->filter(fn ($row) => filled($row['location'] ?? null) || filled($row['name'] ?? null) || filled($row['amount'] ?? null))
        ->values();

    unset($validated['payments']);

    $validated['user_id'] = (string) $userId;

    // "Field" sekarang diturunkan otomatis dari Major yang terpasang di
    // University terpilih (bukan input manual lagi) — dikosongkan (bukan
    // null) kalau University itu belum punya Major, karena kolom `field`
    // di tabel ini NOT NULL tanpa default (lihat kasus serupa
    // `status`/`logo`/`banner` di tabel `universities`).
    // Catatan: tabel `university_profiles` TIDAK punya kolom `degree`/
    // `intake` sama sekali (sempat dikira ada — lihat riwayat error 1054
    // "Unknown column 'degree'"), jadi kedua field itu sengaja tidak diisi
    // di sini; datanya sepenuhnya hidup di tabel `university_profile_degrees`.
    $validated['field'] = optional($university->major)->name ?? '';

    $profile = AdminCrud::create(UniversityProfile::class, $validated);

    foreach ($degreeIntakeRows as $index => $row) {
        UniversityProfileDegree::create([
            'user_id' => (string) $userId,
            'university_profile_id' => $profile->id,
            'degree' => $row['degree'] ?? null,
            'intake' => $row['intake'] ?? null,
            'duration' => $row['duration'] ?? null,
            'sort_order' => $index,
        ]);
    }

    foreach ($paymentRows as $index => $row) {
        UniversityProfilePayment::create([
            'user_id' => (string) $userId,
            'university_profile_id' => $profile->id,
            'location' => $row['location'] ?? null,
            'name' => $row['name'] ?? null,
            'amount' => $row['amount'] ?? null,
            'sort_order' => $index,
        ]);
    }

    // Kembali ke halaman "profile" University (quiz.university.show) — di
    // situ juga sudah ada tombol +Add Album, jadi user tetap bisa lanjut
    // menambahkan album dari sana kalau mau.
    return redirect()
        ->route('quiz.university.show', $validated['university_id'])
        ->with('success', 'University Profile berhasil dibuat.');
}


    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(UniversityProfile::class, $id, null, ['university', 'degrees', 'payments']);

        $universities = University::query()
            ->orderBy('name')
            ->get();

        return view('quiz.university-profile.edit', compact('data', 'universities'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(UniversityProfile::class, $id, null);

        // Catatan: 'degree'/'intake' sengaja tidak divalidasi di sini lewat
        // kolom langsung — tabel `university_profiles` tidak punya kolom itu
        // (lihat catatan di store()), datanya sekarang di tabel
        // `university_profile_degrees` lewat 'degree_intakes' di bawah.
        $validated = $request->validate([
            'university_id' => 'required|string|exists:universities,id',
            'field' => 'required|string|max:255',
            'degree_title' => 'nullable|string|max:255',
            'key_courses' => 'nullable|string',
            'entry_requirements' => 'nullable|string',
            'min_budget' => 'nullable|integer|min:0',
            'max_budget' => 'nullable|integer|min:0|gte:min_budget',
            'language' => 'nullable|string|max:255',
            'scholarship_available' => 'required|boolean',
            'status' => 'required|in:active,inactive',
            // Degree/Intake & Payment: sama pola "add row" dengan store() —
            // seluruh baris lama diganti dengan baris yang dikirim form ini
            // (lihat sinkronisasi delete+recreate di bawah).
            'degree_intakes' => 'nullable|array',
            'degree_intakes.*.degree' => 'nullable|string|max:255',
            'degree_intakes.*.intake' => 'nullable|string|max:255',
            'degree_intakes.*.duration' => 'nullable|string|max:255',
            'payments' => 'nullable|array',
            'payments.*.location' => 'nullable|in:indonesia,china',
            'payments.*.name' => 'nullable|string|max:255',
            'payments.*.amount' => 'nullable|integer|min:0',
        ]);

        // Tidak lagi dibatasi ->where('user_id', ...) -- cukup pastikan id-nya valid.
        $universityOwned = University::query()
            ->where('id', $validated['university_id'])
            ->exists();

        if (!$universityOwned) {
            abort(403, 'University tidak valid.');
        }

        // Buang baris degree/intake & payment yang semuanya kosong (sama
        // logikanya dengan store()).
        $degreeIntakeRows = collect($validated['degree_intakes'] ?? [])
            ->filter(fn ($row) => filled($row['degree'] ?? null) || filled($row['intake'] ?? null) || filled($row['duration'] ?? null))
            ->values();

        $paymentRows = collect($validated['payments'] ?? [])
            ->filter(fn ($row) => filled($row['location'] ?? null) || filled($row['name'] ?? null) || filled($row['amount'] ?? null))
            ->values();

        unset($validated['degree_intakes'], $validated['payments']);

        $profile = AdminCrud::update(UniversityProfile::class, $id, $validated, null);

        // Sinkronisasi baris Degree/Intake & Payment: hapus semua baris lama
        // punya profile ini, lalu buat ulang dari yang dikirim form —
        // paling aman & sederhana untuk child table "add row" begini (tidak
        // ada tabel lain yang mereferensikan baris-baris ini).
        $profile->degrees()->delete();
        foreach ($degreeIntakeRows as $index => $row) {
            UniversityProfileDegree::create([
                'user_id' => (string) $userId,
                'university_profile_id' => $profile->id,
                'degree' => $row['degree'] ?? null,
                'intake' => $row['intake'] ?? null,
                'duration' => $row['duration'] ?? null,
                'sort_order' => $index,
            ]);
        }

        $profile->payments()->delete();
        foreach ($paymentRows as $index => $row) {
            UniversityProfilePayment::create([
                'user_id' => (string) $userId,
                'university_profile_id' => $profile->id,
                'location' => $row['location'] ?? null,
                'name' => $row['name'] ?? null,
                'amount' => $row['amount'] ?? null,
                'sort_order' => $index,
            ]);
        }

        // Sehabis edit, kembali lagi ke halaman "profile" University-nya
        // (bukan ke index list profile global).
        return redirect()
            ->route('quiz.university.show', $validated['university_id'])
            ->with('success', 'University Profile berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        // Ambil dulu university_id-nya sebelum dihapus, supaya redirect bisa
        // kembali ke halaman profile University yang sesuai.
        $existing = AdminCrud::findOrFail(UniversityProfile::class, $id, null);
        $universityId = $existing->university_id;

        AdminCrud::delete(UniversityProfile::class, $id, null);

        return redirect()
            ->route('quiz.university.show', $universityId)
            ->with('success', 'University Profile berhasil dihapus.');
    }
}
