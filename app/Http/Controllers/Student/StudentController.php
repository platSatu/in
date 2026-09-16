<?php

namespace App\Http\Controllers\Student;

use App\Helpers\DataScope;
use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormPayment;
use App\Models\FormSubmission;
use App\Models\Major;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\RoleUser;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    // Fix (14 September 2026, permintaan user -- BUGFIX): dulu di sini ada
    // konstanta ROLE_STUDENT_ID yang isinya UUID role "student" di-hardcode
    // langsung. Masalahnya: role di tabel roles itu dinamis (bisa di-edit
    // namanya kapan saja lewat halaman Roles TANPA ID-nya ikut berubah) --
    // begitu role yang ID-nya sama persis dengan konstanta itu di-edit
    // namanya (mis. tanpa sadar jadi "Sales" alih-alih dibuatkan role baru),
    // tombol "+ Add User" di sini diam-diam ikut assign role "Sales" itu ke
    // akun baru, bukan "student" -- persis bug yang dilaporkan user. Sekarang
    // role "student" dicari dinamis lewat slug (lihat resolveStudentRoleId()
    // di bawah), sama seperti pencarian role "sales" di activeSalesUsers().

    /**
     * Tampilkan daftar student.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $branchId = $request->query('branch_id');
        $formId = $request->query('form_id');

        $user = Auth::user();
        if ($user === null) {
            abort(401);
        }

        $data = Student::query()
            ->with([
                'user',
                'companyBranch',
                'form',
                // Fix (14 September 2026, permintaan user): supaya kolom "Sales
                // Ditugaskan" di index.blade.php tidak N+1 query per baris.
                'handledBy',
                // Dipakai di index.blade.php untuk kolom "Pembayaran": ambil submission
                // TERBARU milik student ini beserta payment-nya (kalau ada), supaya tidak
                // N+1 query per baris. Sama seperti pola $paymentsBySubmission di show(),
                // tapi di sini cukup submission terbaru saja (bukan seluruh riwayat).
                'formSubmissions' => fn ($query) => $query->latest('created_at')->with('payment'),
                // FIX (15 September 2026, permintaan user -- sales bisa lihat progress
                // InaStudy student miliknya): ambil semua Aplikasi Kuliah student ini
                // (biasanya cuma ada 1, lihat guard "hasApplication" di
                // InaStudyController::registerApplication()), diurutkan terbaru dulu,
                // supaya tombol "Progress InaStudy" di index.blade.php bisa ambil
                // ->first() dan langsung link ke quiz.university-application.show
                // pakai id-nya, tanpa N+1 query. SENGAJA TIDAK pakai ->limit(1) di
                // sini -- limit() pada eager load hasMany berlaku GLOBAL ke seluruh
                // baris gabungan (bukan per-student), jadi kalau dipasang cuma
                // 1 aplikasi TOTAL se-halaman yang kebawa, bukan 1 per student.
                'applications' => fn ($query) => $query->latest('submitted_at'),
            ])
            ->tap(fn ($query) => DataScope::applyBranchDivisionScope($query, $user, 'handled_by_user_id'))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('handphone', 'like', "%{$search}%")
                        ->orWhere('sales_id', 'like', "%{$search}%");
                });
            })
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($formId, fn ($query) => $query->where('form_id', $formId))
            // Urutkan data masuk TERBARU di paling atas. `created_at` saja kadang
            // punya beberapa baris dengan detik yang sama persis (mis. input
            // berturut-turut cepat / data lama yang di-import sekaligus), dan untuk
            // baris yang "seri" itu urutannya jadi tidak konsisten kalau cuma andalkan
            // created_at. Student pakai HasUuids (Str::orderedUuid() bawaan Laravel),
            // yang uuid-nya sendiri sudah time-sortable, jadi id dipakai sebagai
            // tie-breaker kedua supaya urutannya selalu deterministik & tetap
            // terbaru-di-atas walau created_at-nya kembar.
            ->latest('created_at')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Daftar untuk dropdown filter. Sengaja tetap tampilkan SEMUA branch/form
        // (tidak ikut di-scope seperti $data di atas) — ini cuma opsi filter (nama
        // branch/form bukan data sensitif), staff dengan cakupan terbatas tetap
        // hanya akan melihat STUDENT-nya yang sesuai scope walau memilih branch/
        // form di luar scope-nya (hasil query $data di atas tetap kosong).
        $companyBranches = CompanyBranch::select('id', 'name')->orderBy('name')->get();
        $forms = Form::select('id', 'name')->orderBy('name')->get();

        // Box ringkasan di atas tabel: total branch & total form yang PERNAH dibuat
        // (bukan cuma yang ada di hasil filter/pencarian saat ini).
        $totalBranches = $companyBranches->count();
        $totalForms = $forms->count();

        return view('student.student.index', compact(
            'data',
            'companyBranches',
            'forms',
            'branchId',
            'formId',
            'totalBranches',
            'totalForms'
        ));
    }

    /**
     * Export Data Student ke CSV (bisa langsung dibuka di Excel maupun
     * diimport ke Google Sheets: Google Sheets punya menu "File > Import"
     * yang menerima file .csv langsung).
     *
     * Filter-nya SENGAJA persis sama dengan index() di atas (search/
     * branch_id/form_id, dari query string yang sama) — jadi tombol Export
     * di halaman index tinggal "ikut" filter yang lagi aktif saat itu:
     * tidak isi filter apa-apa = export semua student, isi Branch = export
     * per branch, isi Form = export per form (atau kombinasi keduanya).
     * Tidak ada UI/endpoint terpisah untuk itu, karena filter yang sudah ada
     * di index() sudah cukup buat mewakili ketiga skenario itu.
     */
    public function export(Request $request): StreamedResponse
    {
        $search = $request->query('search');
        $branchId = $request->query('branch_id');
        $formId = $request->query('form_id');

        $user = Auth::user();
        if ($user === null) {
            abort(401);
        }

        $query = Student::query()
            ->with([
                'companyBranch',
                'form',
                'handledBy',
                'formSubmissions' => fn ($q) => $q->latest('created_at')->with('payment'),
            ])
            // Export mengikuti cakupan akses yang sama dengan index() di atas —
            // staff scope 'division'/'branch'/'self' tidak boleh mengunduh data
            // student di luar cakupannya hanya karena lewat tombol Export.
            ->tap(fn ($q) => DataScope::applyBranchDivisionScope($q, $user, 'handled_by_user_id'))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('handphone', 'like', "%{$search}%")
                        ->orWhere('sales_id', 'like', "%{$search}%");
                });
            })
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($formId, fn ($q) => $q->where('form_id', $formId));

        $filename = $this->buildExportFilename($branchId, $formId);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 di awal file supaya Excel (terutama versi Windows)
            // otomatis mendeteksi encoding-nya sebagai UTF-8 — tanpa ini,
            // nama/email yang ada karakter non-ASCII bisa tampil rusak
            // (mojibake) kalau file-nya dibuka langsung dari Excel.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Nama Lengkap',
                'Email',
                'Handphone',
                'Branch',
                'Form',
                'Kode Sales',
                'Sales Ditugaskan',
                'Status Pembayaran',
                'Order ID Pembayaran',
                'Nominal Pembayaran',
                'Tanggal Bayar',
                'Status Student',
                'Akun Login',
                'Terdaftar Pada',
            ]);

            // chunkById() (bukan get() polos) supaya tidak nge-load semua
            // baris ke memory sekaligus kalau datanya sudah ribuan — jauh
            // lebih hemat memory buat export besar. Dipakai chunkById (bukan
            // chunk() biasa) karena lebih aman dari baris ke-skip/dobel kalau
            // ada data lain yang berubah di tengah proses export (chunkById
            // pakai "WHERE id > id_terakhir", bukan OFFSET yang bisa geser).
            $query->chunkById(500, function ($students) use ($handle) {
                foreach ($students as $student) {
                    $latestSubmission = $student->formSubmissions->first();
                    $payment = $latestSubmission->payment ?? null;

                    if (!$student->form || !$student->form->requires_payment) {
                        $paymentStatus = 'Gratis';
                    } elseif ($payment) {
                        $paymentStatus = ucfirst($payment->status);
                    } else {
                        $paymentStatus = 'Belum Bayar';
                    }

                    fputcsv($handle, [
                        trim($student->first_name . ' ' . $student->last_name),
                        $student->email,
                        $student->handphone,
                        optional($student->companyBranch)->name ?? '-',
                        optional($student->form)->name ?? '-',
                        $student->sales_id ?? '-',
                        optional($student->handledBy)->name ?? '-',
                        $paymentStatus,
                        $payment->order_id ?? '-',
                        $payment ? number_format((float) $payment->amount, 0, ',', '.') : '-',
                        $payment && $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : '-',
                        ucfirst($student->status),
                        $student->user_id ? 'Sudah Terdaftar' : 'Belum Ada',
                        optional($student->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Nama file export mengikuti filter yang lagi aktif, biar dari nama
     * file-nya saja sudah kelihatan itu "export semua" atau "per branch/
     * form" — tanpa perlu buka dulu buat tahu isinya.
     */
    private function buildExportFilename(?string $branchId, ?string $formId): string
    {
        $parts = ['students'];

        if ($branchId) {
            $branchName = optional(CompanyBranch::find($branchId))->name;
            $parts[] = 'branch-' . Str::slug($branchName ?: $branchId);
        }

        if ($formId) {
            $formName = optional(Form::find($formId))->name;
            $parts[] = 'form-' . Str::slug($formName ?: $formId);
        }

        $parts[] = now()->format('Y-m-d_His');

        return implode('_', $parts) . '.csv';
    }

    /**
     * Form tambah student.
     */
    public function create(): View
    {
        $companyBranches = CompanyBranch::select('id', 'name')->orderBy('name')->get();
        $forms = Form::select('id', 'name')->orderBy('name')->get();
        // Fix (14 September 2026, permintaan user): dropdown "Assign ke Sales"
        // sekarang juga tampil di form Add Student, sama seperti di Edit.
        $salesUsers = $this->activeSalesUsers();

        $user = Auth::user();

        // FIX (permintaan user, 14 September 2026): sales (scope 'self') yang
        // menambahkan student MILIKNYA SENDIRI sekarang otomatis dikunci ke
        // (a) branch tempat dia terdaftar (lewat Company > Division > Add
        // User -- App\Models\User::divisions(), lihat ownBranchId() di
        // bawah) dan (b) dirinya sendiri di "Assign ke Sales" -- supaya
        // student yang baru saja dia input TIDAK hilang dari daftarnya
        // sendiri (scope 'self' memfilter murni dari handled_by_user_id,
        // lihat App\Helpers\DataScope::applyBranchDivisionScope()), dan
        // tidak bisa salah/sengaja assign ke sales lain. Superadmin serta
        // role scope branch/division tetap bebas memilih seperti biasa.
        $isSelfScoped = $user->isSelfScopedOnly();
        $lockedBranchId = $isSelfScoped ? $this->ownBranchId($user) : null;
        $lockedSalesUserId = $isSelfScoped ? $user->id : null;

        return view('student.student.create', compact(
            'companyBranches',
            'forms',
            'salesUsers',
            'isSelfScoped',
            'lockedBranchId',
            'lockedSalesUserId'
        ));
    }

    /**
     * Simpan student baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateStudent($request);

        $user = Auth::user();

        // FIX (permintaan user, 14 September 2026): guard SERVER-SIDE --
        // jangan cuma andalkan field yang di-disable di form. Kalau yang
        // submit scope 'self' (sales), branch_id & handled_by_user_id SELALU
        // dipaksa ke branch/dirinya sendiri di sini, apapun yang terkirim
        // dari form (mis. kalau field disabled itu diakali lewat devtools).
        if ($user->isSelfScopedOnly()) {
            $validated['branch_id'] = $this->ownBranchId($user);
            $validated['handled_by_user_id'] = $user->id;
        }

        if ($request->hasFile('images')) {
            $validated['images'] = $this->storeImage($request->file('images'));
        } else {
            // FIX (permintaan user, 16 September 2026): validasi 'images'
            // sudah 'nullable', tapi kalau tidak ada file yang diupload,
            // key ini SAMA SEKALI tidak ikut ke $validated -- begitu masuk
            // Student::create(), INSERT-nya tidak menyertakan kolom
            // 'images' sama sekali. Kolom itu ternyata NOT NULL tanpa
            // default di database (dibuat manual, bukan lewat migration
            // Laravel), jadi INSERT gagal:
            // "SQLSTATE[HY000]: 1364 Field 'images' doesn't have a default
            // value". Migration
            // add_default_null_to_students_images_column sudah bikin
            // kolomnya nullable di DB, tapi baris ini tetap ditambahkan
            // sebagai jaga-jaga (defense in depth) supaya store() ini
            // tidak bergantung 100% ke migration itu sudah jalan atau
            // belum di environment manapun.
            $validated['images'] = null;
        }

        Student::create($validated);

        return redirect()
            ->route('student.student.index')
            ->with('success', 'Data student berhasil ditambahkan.');
    }

    /**
     * Detail student — termasuk SELURUH riwayat pengisian quiz (form submission)
     * sampai status pembayarannya, dipakai oleh view "dokumen" (show.blade.php).
     *
     * Riwayat diambil dari Student::formSubmissions() (bukan cuma branch_id/form_id
     * di kolom students, yang cuma "singgahan terakhir" — lihat catatan di model),
     * supaya form/branch yang pernah diisi sebelumnya tetap kelihatan.
     */
    public function show(string $id): View
    {
        $data = $this->resolveVisibleStudent($id, ['user', 'companyBranch', 'form']);

        $submissions = FormSubmission::where('user_id', $data->id)
            ->with(['form.companyBranch'])
            ->latest('created_at')
            ->get();

        $submissionIds = $submissions->pluck('id');

        // Dikelompokkan per submission_id supaya di view tinggal ambil
        // $answersBySubmission->get($submission->id) tanpa query ulang per baris.
        $answersBySubmission = FormAnswer::whereIn('submission_id', $submissionIds)
            ->with(['question', 'option'])
            ->get()
            ->groupBy('submission_id');

        // Satu submission maksimal 1 payment yang benar-benar terkunci ke dia
        // (lihat FrontendController: form_submission_id baru diisi setelah submit
        // berhasil dan order_id unique), jadi aman di-keyBy.
        $paymentsBySubmission = FormPayment::whereIn('form_submission_id', $submissionIds)
            ->get()
            ->keyBy('form_submission_id');

        // Pertanyaan tipe "major" nyimpan UUID major di FormAnswer.answer_text (bukan
        // namanya langsung), jadi di-resolve sekali di sini (bukan per baris di view)
        // supaya tidak N+1 query saat merender history.
        $majorIds = $answersBySubmission
            ->flatten()
            ->filter(fn ($answer) => optional($answer->question)->type === 'major' && !empty($answer->answer_text))
            ->pluck('answer_text')
            ->unique();

        $majorNames = Major::whereIn('id', $majorIds)->pluck('name', 'id');

        return view('student.student.show', compact(
            'data',
            'submissions',
            'answersBySubmission',
            'paymentsBySubmission',
            'majorNames'
        ));
    }

    /**
     * Form edit student.
     */
    public function edit(string $id): View
    {
        $data = $this->resolveVisibleStudent($id);

        $companyBranches = CompanyBranch::select('id', 'name')->orderBy('name')->get();
        $forms = Form::select('id', 'name')->orderBy('name')->get();
        $salesUsers = $this->activeSalesUsers();

        return view('student.student.edit', compact('data', 'companyBranches', 'forms', 'salesUsers'));
    }

    /**
     * Update student.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $data = $this->resolveVisibleStudent($id);

        $validated = $this->validateStudent($request, $data->id);

        if ($request->hasFile('images')) {
            if ($data->images) {
                $this->deleteImage($data->images);
            }
            $validated['images'] = $this->storeImage($request->file('images'));
        }

        $data->update($validated);

        return redirect()
            ->route('student.student.index')
            ->with('success', 'Data student berhasil diperbarui.');
    }

    /**
     * Hapus student.
     */
    public function destroy(string $id): RedirectResponse
    {
        $data = $this->resolveVisibleStudent($id);

        if ($data->images) {
            $this->deleteImage($data->images);
        }

        $data->delete();

        return redirect()
            ->route('student.student.index')
            ->with('success', 'Data student berhasil dihapus.');
    }

    /**
     * Tombol "+ Add User" di index: buat akun login (tabel users + role_user)
     * dari data student (name, email, handphone).
     *
     * FIX (permintaan user, 16 September 2026): dulu password-nya
     * di-generate ACAK (Str::random) lalu ditampilkan sekali lewat flash
     * message, siswa diarahkan pakai "Forgot Password" untuk ganti sendiri.
     * Sekarang admin isi sendiri password-nya lewat modal di index.blade.php
     * (input "password", langsung di-hash sama seperti sebelumnya) --
     * SENGAJA cuma bagian generate password ini yang diubah, sisanya
     * (status langsung 'active', email langsung dianggap terverifikasi,
     * role student, link ke Student) TETAP SAMA seperti sebelumnya.
     */
    public function addUser(Request $request, string $id): RedirectResponse
    {
        $student = $this->resolveVisibleStudent($id);

        if ($student->user_id) {
            return redirect()
                ->route('student.student.index')
                ->with('error', 'Student ini sudah memiliki akun user.');
        }

        if (User::where('email', $student->email)->exists()) {
            return redirect()
                ->route('student.student.index')
                ->with('error', 'Email ' . $student->email . ' sudah terdaftar sebagai user.');
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ], [], ['password' => 'Password']);

        DB::transaction(function () use ($student, $validated) {
            $user = User::create([
                'name' => trim($student->first_name . ' ' . $student->last_name),
                'email' => $student->email,
                'handphone' => $student->handphone,
                'password' => Hash::make($validated['password']),
                'status' => 'active',
            ]);

            // Fix (14 September 2026, permintaan user): akun yang dibuat manual
            // dari sini (oleh sales/admin) TIDAK pernah memicu event Registered
            // seperti alur daftar sendiri di Auth\RegisteredUserController --
            // jadi tidak ada email verifikasi otomatis terkirim. Kalau
            // dibiarkan, email_verified_at tetap null dan siswa ini akan
            // mentok di halaman "verifikasi email dulu" begitu login pertama
            // kali (lihat middleware 'verified' & ApplyController::show()).
            // Karena akun ini memang sengaja dibuatkan admin/sales (bukan
            // daftar sendiri), email verification langsung ditandai selesai
            // di sini -- markEmailAsVerified() bawaan trait MustVerifyEmail,
            // sama seperti yang dipanggil VerifyEmailController saat siswa
            // klik link verifikasi di alur normal.
            $user->markEmailAsVerified();

            RoleUser::create([
                'user_id' => $user->id,
                'role_id' => $this->resolveStudentRoleId(),
                'status' => RoleUser::STATUS_ACTIVE,
            ]);

            $student->update(['user_id' => $user->id]);
        });

        return redirect()
            ->route('student.student.index')
            ->with('success', 'User berhasil dibuat untuk ' . $student->first_name . ' dengan password yang baru saja Anda tentukan. Akun sudah aktif dan siap dipakai login.');
    }

    /**
     * Validasi form create/update.
     */
    private function validateStudent(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'images' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sales_id' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('students', 'email')->ignore($ignoreId),
            ],
            'handphone' => ['required', 'string', 'max:20'],
            // branch_id & form_id sengaja nullable: kolom ini cuma "singgahan terakhir"
            // (lihat catatan di Student::companyBranch()/form()), student boleh saja
            // belum pernah terhubung ke branch/form manapun saat dibuat manual dari sini.
            'branch_id' => ['nullable', 'exists:company_branch,id'],
            'form_id' => ['nullable', 'exists:forms,id'],
            // Fix (14 September 2026, permintaan user): dropdown "Assign ke
            // Sales" di form edit -- superadmin mencocokkan sales_id (kode
            // teks bebas di atas) ke akun sales resmi lewat kolom ini.
            'handled_by_user_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }

    /**
     * ID role "student", dicari dinamis lewat slug (BUKAN ID hardcode --
     * lihat catatan BUGFIX di atas const lama yang sudah dihapus). Dibuat
     * abort(500) yang jelas kalau role-nya sampai tidak ketemu sama sekali,
     * daripada diam-diam assign role lain yang salah ke akun baru.
     */
    private function resolveStudentRoleId(): string
    {
        $roleId = Role::where('slug', 'student')->value('id');

        if ($roleId === null) {
            abort(500, 'Role "student" tidak ditemukan (dicari lewat slug "student"). Cek halaman Roles -- pastikan ada role dengan slug persis "student" sebelum membuat akun login student lewat tombol "+ Add User".');
        }

        return $roleId;
    }

    /**
     * Daftar user dengan role "sales" aktif (dicocokkan lewat slug, bukan ID
     * hardcode -- role "sales" dibuat dinamis lewat halaman Roles), dipakai
     * untuk dropdown "Assign ke Sales" di form edit Student -- lihat
     * App\Http\Controllers\RoleUserController::assignSalesCodeIfNeeded()
     * untuk asal Kode Sales (User::sales_code) yang ditampilkan sebagai
     * referensi tiap opsinya.
     */
    private function activeSalesUsers()
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                $query->where('slug', 'sales')
                    ->where('roles.status', Role::STATUS_ACTIVE)
                    ->where('role_user.status', RoleUser::STATUS_ACTIVE);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'sales_code']);
    }

    /**
     * Branch tempat user ini (biasanya sales, scope 'self') "berada" --
     * dipakai untuk mengunci field Branch di form Add Student saat yang
     * login scope 'self', lihat create()/store() di atas. Null kalau tidak
     * ketemu sama sekali (tidak seharusnya terjadi untuk sales yang benar,
     * tapi dijaga supaya tidak fatal error, cukup jadi field kosong/tidak
     * terkunci).
     *
     * FIX (15 September 2026): logic-nya DIPUSATKAN ke
     * App\Models\User::resolveOwnBranchId() (dipakai bareng
     * DashboardController::index() untuk filter Academic Calendar per
     * branch) supaya tidak dobel-tulis di 2 tempat berbeda.
     */
    private function ownBranchId(User $user): ?string
    {
        return $user->resolveOwnBranchId();
    }

    /**
     * Simpan file gambar ke public/image/student, return path relatif
     * (dipakai langsung dengan asset() di blade).
     */
    private function storeImage($file): string
    {
        $filename = uniqid('student_') . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('image/student'), $filename);

        return 'image/student/' . $filename;
    }

    private function deleteImage(string $path): void
    {
        $fullPath = public_path($path);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    /**
     * Student yang boleh diakses user ini, sesuai cakupan branch/divisi/self
     * role aktifnya (lihat App\Helpers\DataScope, sama persis dengan filter
     * yang dipakai di index()/export() di atas) — dipakai di show()/edit()/
     * update()/destroy()/addUser() supaya staff tidak bisa buka/ubah/hapus
     * data student di luar cakupannya cuma dengan menebak-nebak ID lewat URL
     * (sebelumnya method-method ini pakai findOrFail() polos tanpa
     * pengecekan apa pun, konsisten dengan index() yang juga belum di-scope).
     *
     * @param array<int, string|\Closure> $with
     */
    private function resolveVisibleStudent(string $id, array $with = []): Student
    {
        $user = Auth::user();
        if ($user === null) {
            abort(401);
        }

        $student = Student::with($with)->findOrFail($id);

        $visible = DataScope::applyBranchDivisionScope(
            Student::query()->where('id', $student->id),
            $user,
            'handled_by_user_id'
        )->exists();

        if (!$visible) {
            abort(403, 'Student tidak valid untuk cakupan akses Anda.');
        }

        return $student;
    }
}