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
    /**
     * UUID role "student" pada tabel roles.
     * Dipakai saat insert ke role_user ketika tombol "+ Add User" diklik.
     */
    private const ROLE_STUDENT_ID = '019eddb7-8f13-733a-805f-e071502b5dc9';

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
                // Dipakai di index.blade.php untuk kolom "Pembayaran": ambil submission
                // TERBARU milik student ini beserta payment-nya (kalau ada), supaya tidak
                // N+1 query per baris. Sama seperti pola $paymentsBySubmission di show(),
                // tapi di sini cukup submission terbaru saja (bukan seluruh riwayat).
                'formSubmissions' => fn ($query) => $query->latest('created_at')->with('payment'),
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

        return view('student.student.create', compact('companyBranches', 'forms'));
    }

    /**
     * Simpan student baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateStudent($request);

        if ($request->hasFile('images')) {
            $validated['images'] = $this->storeImage($request->file('images'));
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

        return view('student.student.edit', compact('data', 'companyBranches', 'forms'));
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
     * otomatis dari data student (name, email, handphone), password random,
     * user diarahkan pakai fitur "Forgot Password" untuk set password sendiri.
     */
    public function addUser(string $id): RedirectResponse
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

        $generatedPassword = Str::random(12);

        DB::transaction(function () use ($student, $generatedPassword) {
            $user = User::create([
                'name' => trim($student->first_name . ' ' . $student->last_name),
                'email' => $student->email,
                'handphone' => $student->handphone,
                'password' => Hash::make($generatedPassword),
                'status' => 'active',
            ]);

            RoleUser::create([
                'user_id' => $user->id,
                'role_id' => self::ROLE_STUDENT_ID,
                'status' => RoleUser::STATUS_ACTIVE,
            ]);

            $student->update(['user_id' => $user->id]);
        });

        return redirect()
            ->route('student.student.index')
            ->with('success', 'User berhasil dibuat untuk ' . $student->first_name . '. Password sementara: ' . $generatedPassword . ' (silakan gunakan fitur "Forgot Password" saat login pertama kali).');
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
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
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