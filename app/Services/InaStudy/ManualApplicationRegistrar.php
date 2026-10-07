<?php

namespace App\Services\InaStudy;

use App\Models\ApplicationPayment;
use App\Models\Student;
use App\Models\University;
use App\Models\UniversityApplication;
use App\Models\UniversityProfileDegree;
use App\Services\ApplicationNumberGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Register Aplikasi Kuliah MANUAL tanpa Registration Fee (1 Oktober 2026).
 * Satu tempat aturan untuk dua pintu:
 * - siswa sendiri dari halaman InaStudy (StudentPortal\InaStudyController);
 * - admin/sales lewat tombol "Add to InaStudy" di index Student
 *   (Student\StudentController::addToInaStudy), supaya admin bisa membantu
 *   mendaftarkan lalu mengisi formulir & dokumen dari halaman Progress InaStudy.
 *
 * Aplikasi dibuat seperti biasa lalu langsung dibuatkan 1 ApplicationPayment
 * PAID (amount 0, metode 'manual') untuk registration_fee, sehingga formulir &
 * upload dokumen terbuka tanpa pembayaran gateway. Struktur tabel tidak diubah.
 */
class ManualApplicationRegistrar
{
    /** Pilihan dropdown Universitas -> Degree -> Jurusan (Course). */
    public function options(): Collection
    {
        return University::query()
            ->where('status', 'active')
            ->with(['profiles' => function ($query) {
                $query->where('status', 'active')
                    ->orderBy('field')
                    ->with(['degrees' => fn ($q) => $q->orderBy('sort_order')]);
            }])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($university) => [
                'id' => $university->id,
                'name' => $university->name,
                'courses' => $university->profiles
                    ->flatMap(fn ($profile) => $profile->degrees->map(fn ($degreeRow) => [
                        'id' => $degreeRow->id,
                        'degree' => $degreeRow->degree,
                        // Label sama dengan pilihan "Program / Major" di form Apply.
                        'label' => $degreeRow->course_name ?: ($profile->field ?: 'Program'),
                    ]))
                    ->filter(fn ($course) => filled($course['degree']) && filled($course['label']))
                    ->values(),
            ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'university_id' => ['required', 'uuid', 'exists:universities,id'],
            'degree' => ['required', 'string', Rule::in(UniversityProfileDegree::DEGREES)],
            'degree_intake_id' => ['required', 'uuid', 'exists:university_profile_degrees,id'],
            // Sama dengan form Apply (StudentPortal\ApplyController::store).
            'intake_year' => ['required', 'integer', 'min:'.now()->year, 'max:'.(now()->year + 5)],
            'whatsapp' => ['required', 'string', 'max:20'],
        ];
    }

    /**
     * @param  array{university_id: string, degree: string, degree_intake_id: string, intake_year: int, whatsapp: string}  $input  sudah lolos rules()
     *
     * @throws ValidationException jurusan tidak cocok / siswa sudah punya aplikasi
     */
    public function register(Student $student, array $input): UniversityApplication
    {
        // Course harus benar-benar milik universitas & degree yang dipilih
        // (dicek di server, bukan cuma andalan filter dropdown di JS).
        $degreeRow = UniversityProfileDegree::with('profile')
            ->where('id', $input['degree_intake_id'])
            ->where('degree', $input['degree'])
            ->whereHas('profile', fn ($query) => $query
                ->where('university_id', $input['university_id'])
                ->where('status', 'active'))
            ->first();

        if (! $degreeRow) {
            throw ValidationException::withMessages([
                'degree_intake_id' => 'Jurusan yang dipilih tidak sesuai dengan universitas/degree yang dipilih. Silakan coba lagi.',
            ]);
        }

        return DB::transaction(function () use ($student, $input, $degreeRow) {
            // Kunci baris student supaya klik ganda / dua admin bersamaan
            // tidak membuat 2 aplikasi.
            Student::whereKey($student->id)->lockForUpdate()->first();

            if (UniversityApplication::where('student_id', $student->id)->exists()) {
                throw ValidationException::withMessages([
                    'university_id' => 'Sudah ada Aplikasi Kuliah untuk siswa ini. Silakan lanjutkan dari daftar aplikasinya.',
                ]);
            }

            $profile = $degreeRow->profile;

            $application = UniversityApplication::create([
                'application_no' => (new ApplicationNumberGenerator())->next(),
                'student_id' => $student->id,
                'university_profile_id' => $profile->id,
                'university_id' => $input['university_id'],
                'degree_intake_id' => $degreeRow->id,
                'course_name' => $degreeRow->course_name,
                'degree' => $degreeRow->degree,
                'language' => $profile->language,
                'intake' => $degreeRow->intake,
                'duration' => $degreeRow->duration,
                'intake_year' => $input['intake_year'],
                'whatsapp' => $input['whatsapp'],
                'status' => UniversityApplication::STATUS_SUBMITTED,
                'admission_status' => UniversityApplication::ADMISSION_STATUS_UNDER_REVIEW,
                'submitted_at' => now(),
            ]);

            ApplicationPayment::create([
                'application_id' => $application->id,
                'purpose' => ApplicationPayment::PURPOSE_REGISTRATION_FEE,
                'order_id' => 'MANUAL-'.$application->application_no,
                'amount' => 0,
                'status' => ApplicationPayment::STATUS_PAID,
                'payment_method' => 'manual',
                'paid_at' => now(),
            ]);

            return $application;
        });
    }
}
