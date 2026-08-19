<?php

namespace App\Http\Controllers\Student;

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

        $data = Student::query()
            ->with(['user', 'companyBranch', 'form'])
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
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        // Daftar untuk dropdown filter. Sengaja tampilkan semua branch/form (tanpa
        // scope user_id) supaya superadmin bisa filter lintas branch/form manapun,
        // konsisten dengan index() ini yang juga tidak discope per user_id.
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
        $data = Student::with(['user', 'companyBranch', 'form'])->findOrFail($id);

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
        $data = Student::findOrFail($id);

        $companyBranches = CompanyBranch::select('id', 'name')->orderBy('name')->get();
        $forms = Form::select('id', 'name')->orderBy('name')->get();

        return view('student.student.edit', compact('data', 'companyBranches', 'forms'));
    }

    /**
     * Update student.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $data = Student::findOrFail($id);

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
        $data = Student::findOrFail($id);

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
        $student = Student::findOrFail($id);

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
}