<?php

namespace App\Services\InaStudy;

use App\Http\Controllers\Payment\FormPaymentController;
use App\Http\Controllers\StudentPortal\ApplyController;
use App\Models\ApplicationPayment;
use App\Models\Student;
use App\Models\University;
use App\Models\UniversityApplication;
use App\Models\UniversityProfileDegree;
use App\Services\ApplicationNumberGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Register Aplikasi Kuliah oleh admin/sales lewat popup "Add to InaStudy" di
 * index Student (Student\StudentController::addToInaStudy).
 *
 * Disamakan dengan Apply di depan (18 November 2026, dulu 1 aplikasi & gratis Rp 0):
 *  - boleh beberapa kampus sekaligus (maksimal ApplyController::CART_MAX), yang
 *    ditolak hanya jurusan & intake yang sama -- sama dengan ApplyController;
 *  - Registration Fee di-snapshot dari Course & dihitung oleh model yang sama
 *    dengan depan (UniversityApplication::registrationFeeToCharge(): lebih dari
 *    1 kampus aktif = DP 50% per kampus, sisanya lewat "Tagih Pelunasan");
 *  - cara bayar: link bayar ke siswa (halaman bayar portal, semua kampus sekali
 *    bayar) atau transfer manual (langsung lunas, sama dengan tombol "Tandai Lunas").
 *
 * options(), rules() & resolveCourse() juga dipakai "Ubah Kampus/Jurusan"
 * (ApplicationAdminService::changeCourse) -- jangan ubah perilakunya.
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
                        // Untuk ringkasan biaya di popup Add to InaStudy (18 November 2026).
                        'intake' => $degreeRow->intake,
                        'fee' => (int) $degreeRow->registration_fee_amount,
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

    public const PAYMENT_LINK = 'link';
    public const PAYMENT_MANUAL = 'manual';

    /** @return array<string, array<int, mixed>> Isian popup Add to InaStudy (beberapa kampus sekaligus). */
    public function registerRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:'.ApplyController::CART_MAX],
            'items.*.university_id' => ['required', 'uuid'],
            'items.*.degree' => ['required', 'string', Rule::in(UniversityProfileDegree::DEGREES)],
            'items.*.degree_intake_id' => ['required', 'uuid'],
            // Sama dengan form Apply (StudentPortal\ApplyController::store).
            'items.*.intake_year' => ['required', 'integer', 'min:'.now()->year, 'max:'.(now()->year + 5)],
            'whatsapp' => ['required', 'string', 'max:20'],
            'payment' => ['required', Rule::in([self::PAYMENT_LINK, self::PAYMENT_MANUAL])],
        ];
    }

    /**
     * Daftarkan siswa ke beberapa kampus sekaligus, seperti checkout cart di depan
     * (ApplyController::checkout()).
     *
     * @param  array{items: array<int, array{university_id: string, degree: string, degree_intake_id: string, intake_year: int}>, whatsapp: string, payment: string}  $input  sudah lolos registerRules()
     * @return Collection<int, UniversityApplication> aplikasi yang baru dibuat
     *
     * @throws ValidationException
     */
    public function registerMany(Student $student, array $input, ?string $actorId = null): Collection
    {
        $items = collect($input['items'])->values()->map(fn (array $item) => [
            'course' => $this->resolveCourse($item),
            'intake_year' => (int) $item['intake_year'],
        ]);

        if ($items->unique(fn ($item) => $item['course']->id.':'.$item['intake_year'])->count() !== $items->count()) {
            throw ValidationException::withMessages(['items' => 'Ada jurusan & intake yang dipilih dua kali. Hapus salah satunya.']);
        }

        $manual = $input['payment'] === self::PAYMENT_MANUAL;

        // Link bayar dibuka siswa dari portal: wajib punya akun login.
        if (! $manual && ! $student->user_id) {
            throw ValidationException::withMessages(['payment' => 'Siswa ini belum punya akun login, jadi belum bisa bayar lewat link. Buatkan akunnya dulu, atau pilih "Siswa sudah transfer manual".']);
        }

        if ($manual && $items->contains(fn ($item) => (int) $item['course']->registration_fee_amount <= 0)) {
            throw ValidationException::withMessages(['payment' => 'Registration Fee jurusan yang dipilih belum diatur, jadi belum bisa ditandai lunas. Atur dulu di halaman Degree & Course University Profile.']);
        }

        $result = DB::transaction(function () use ($student, $input, $items, $manual, $actorId) {
            // Kunci baris student supaya klik ganda / dua admin bersamaan tidak membuat aplikasi dobel.
            Student::whereKey($student->id)->lockForUpdate()->first();

            foreach ($items as $item) {
                // Sama dengan ApplyController::activeApplication(): Cancelled tidak menghalangi daftar ulang.
                $exists = UniversityApplication::where('student_id', $student->id)
                    ->where('degree_intake_id', $item['course']->id)
                    ->where('intake_year', $item['intake_year'])
                    ->where('status', '!=', UniversityApplication::STATUS_CANCELLED)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'items' => 'Siswa ini sudah terdaftar di '.$item['course']->profile->university->name.' - '.($item['course']->course_name ?: 'jurusan ini').' untuk intake '.$item['intake_year'].'.',
                    ]);
                }
            }

            // Sama dengan ApplyController::checkout().
            $applications = $items->map(fn ($item) => UniversityApplication::create(UniversityApplication::courseSnapshot($item['course']) + [
                'application_no' => (new ApplicationNumberGenerator())->next(),
                'student_id' => $student->id,
                'intake_year' => $item['intake_year'],
                'whatsapp' => $input['whatsapp'],
                'registration_fee_amount' => $item['course']->registration_fee_amount,
                'status' => UniversityApplication::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]));

            // Transfer manual: sama dengan tombol "Tandai Lunas" (UniversityApplicationController::updateFees).
            // Nominal dihitung setelah semua aplikasi dibuat, supaya DP 50% ikut jumlah kampus terbaru.
            $payments = $manual ? $applications->map(fn (UniversityApplication $application) => $application->payments()->create([
                'purpose' => ApplicationPayment::PURPOSE_REGISTRATION_FEE,
                'order_id' => 'MANUAL'.now()->format('ymd').strtoupper(Str::random(8)),
                'gateway' => 'manual',
                'payment_method' => 'Transfer Manual',
                'amount' => $application->registrationFeeToCharge(),
                'status' => ApplicationPayment::STATUS_PAID,
                'paid_at' => now(),
                'raw_callback' => ['recorded_by' => $actorId],
            ])) : collect();

            return compact('applications', 'payments');
        });

        // Setelah commit: buka Formulir & Dokumen + kirim invoice, sama dengan pembayaran lunas lainnya.
        $result['payments']->each(fn (ApplicationPayment $payment) => app(FormPaymentController::class)->openApplicationAfterPayment($payment));

        return $result['applications'];
    }

    /**
     * Nominal & link yang dibayar siswa sekarang: semua kampus yang belum dibayar, sama persis dengan
     * halaman bayar portal (ApplicationPaymentController::show/payingApplications).
     *
     * @return array{amount: int, url: string, count: int}
     */
    public function paymentLink(UniversityApplication $application): array
    {
        $bundle = collect([$application])
            ->merge($application->registrationBundle()->reject(fn ($item) => $item->is($application)))
            ->take(ApplyController::CART_MAX);

        return [
            'amount' => (int) $bundle->sum(fn (UniversityApplication $item) => $item->registrationFeeToCharge()),
            'url' => route('student-portal.applications.payment.show', [$application->id, ApplicationPayment::PURPOSE_REGISTRATION_FEE]),
            'count' => $bundle->count(),
        ];
    }

    /**
     * Course harus benar-benar milik universitas & degree yang dipilih (dicek
     * di server, bukan cuma andalan filter dropdown di JS). Dipakai juga oleh
     * ApplicationAdminService::changeCourse().
     *
     * @throws ValidationException
     */
    public function resolveCourse(array $input): UniversityProfileDegree
    {
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

        return $degreeRow;
    }
}
