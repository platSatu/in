<?php

namespace App\Services\InaStudy;

use App\Models\ApplicationDocument;
use App\Models\ApplicationPayment;
use App\Models\UniversityApplication;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aksi admin pada Aplikasi Kuliah (22 Oktober 2026): Batalkan & Ubah
 * Kampus/Jurusan. Siapa & kapan otomatis tercatat di Activity Log
 * (App\Helpers\ActivityLogger), termasuk nilai lama -> baru.
 *
 * Baris application_payments dikunci (lockForUpdate) di setiap aksi supaya
 * tidak balapan dengan webhook pembayaran yang masuk di detik yang sama.
 */
class ApplicationAdminService
{
    public function __construct(private readonly ManualApplicationRegistrar $registrar)
    {
    }

    /**
     * Batalkan aplikasi yang BELUM ada uang masuk. Pembayaran manual Rp 0
     * (Register manual) tidak dihitung uang masuk. Tagihan yang masih
     * menunggu ditutup (expired).
     *
     * @throws ValidationException sudah ada pembayaran
     */
    public function cancel(string $applicationId, ?array $visibleStudentIds, ?string $reason): UniversityApplication
    {
        return DB::transaction(function () use ($applicationId, $visibleStudentIds, $reason) {
            $application = $this->lockApplication($applicationId, $visibleStudentIds);

            if ($application->isCancelled()) {
                return $application;
            }

            $payments = $this->lockPayments($application);

            if ($payments->contains(fn ($payment) => $payment->isPaid() && $payment->amount > 0)) {
                throw ValidationException::withMessages([
                    'cancel' => 'Aplikasi '.$application->application_no.' sudah ada pembayaran, tidak bisa dibatalkan dari sini.',
                ]);
            }

            $this->expirePending($payments);

            $application->update([
                'status' => UniversityApplication::STATUS_CANCELLED,
                'cancel_reason' => filled($reason) ? trim($reason) : null,
            ]);

            return $application;
        });
    }

    /** @return array{cancelled: int, skipped: array<int, string>} */
    public function cancelMany(array $applicationIds, ?array $visibleStudentIds, string $reason): array
    {
        $cancelled = 0;
        $skipped = [];

        $ids = UniversityApplication::whereKey(array_unique($applicationIds))
            ->when($visibleStudentIds !== null, fn ($query) => $query->whereIn('student_id', $visibleStudentIds))
            ->pluck('id');

        foreach ($ids as $id) {
            try {
                $this->cancel($id, $visibleStudentIds, $reason);
                $cancelled++;
            } catch (ValidationException $e) {
                $skipped[] = collect($e->errors())->flatten()->first();
            }
        }

        return ['cancelled' => $cancelled, 'skipped' => $skipped];
    }

    /**
     * Ubah kampus & jurusan (dan Intake Year / WhatsApp). Aturan input sama
     * dengan Register manual (ManualApplicationRegistrar::rules()/resolveCourse()).
     * Registration Fee: belum lunas -> ikut fee jurusan baru & tagihan lama
     * ditutup; sudah lunas -> nominal yang dibayar tetap.
     *
     * @return array{application: UniversityApplication, fee_warning: ?string}
     *
     * @throws ValidationException tidak boleh diubah / jurusan tidak valid
     */
    public function changeCourse(string $applicationId, ?array $visibleStudentIds, array $input): array
    {
        $course = $this->registrar->resolveCourse($input);

        return DB::transaction(function () use ($applicationId, $visibleStudentIds, $input, $course) {
            $application = $this->lockApplication($applicationId, $visibleStudentIds);

            if ($reason = $this->changeBlockedReason($application)) {
                throw ValidationException::withMessages(['change_course' => $reason]);
            }

            $payments = $this->lockPayments($application);
            $feePaid = $payments->contains(fn ($payment) => $payment->isPaid()
                && $payment->purpose === ApplicationPayment::PURPOSE_REGISTRATION_FEE);

            $data = UniversityApplication::courseSnapshot($course) + [
                'intake_year' => (int) $input['intake_year'],
                'whatsapp' => $input['whatsapp'],
            ];

            $feeWarning = null;

            if (! $feePaid) {
                $data['registration_fee_amount'] = $course->registration_fee_amount;
                $this->expirePending($payments->where('purpose', ApplicationPayment::PURPOSE_REGISTRATION_FEE));
            } elseif ((int) $application->registration_fee_amount !== (int) $course->registration_fee_amount) {
                $feeWarning = 'Registration Fee jurusan baru (Rp '.number_format((int) $course->registration_fee_amount, 0, ',', '.')
                    .') berbeda dengan yang sudah dibayar (Rp '.number_format((int) $application->registration_fee_amount, 0, ',', '.')
                    .'). Selisihnya diurus manual ya.';
            }

            $application->update($data);

            return ['application' => $application, 'fee_warning' => $feeWarning];
        });
    }

    /** Null = boleh diubah. Dipakai juga di halaman detail untuk menampilkan/menyembunyikan tombol. */
    public function changeBlockedReason(UniversityApplication $application): ?string
    {
        if ($application->isCancelled()) {
            return 'Aplikasi sudah dibatalkan.';
        }

        if (in_array($application->status, UniversityApplication::LOCKED_STATUSES, true)) {
            return 'Aplikasi sudah tahap '.str_replace('_', ' ', $application->status).'. Batalkan lalu apply baru.';
        }

        if ($application->admission_status === UniversityApplication::ADMISSION_STATUS_ACCEPTED) {
            return 'Aplikasi sudah Accepted di kampus ini. Batalkan lalu apply baru.';
        }

        $hasInaDocuments = ApplicationDocument::where('application_id', $application->id)
            ->whereHas('documentType', fn ($query) => $query->adminProvided())
            ->exists();

        return $hasInaDocuments
            ? 'Sudah ada dokumen dari INA (Offer Letter/JW/VISA) untuk kampus ini. Batalkan lalu apply baru.'
            : null;
    }

    private function lockApplication(string $applicationId, ?array $visibleStudentIds): UniversityApplication
    {
        $application = UniversityApplication::whereKey($applicationId)
            ->when($visibleStudentIds !== null, fn ($query) => $query->whereIn('student_id', $visibleStudentIds))
            ->lockForUpdate()
            ->first();

        abort_if(! $application, 404);

        return $application;
    }

    private function lockPayments(UniversityApplication $application): Collection
    {
        return ApplicationPayment::where('application_id', $application->id)->lockForUpdate()->get();
    }

    private function expirePending(Collection $payments): void
    {
        $ids = $payments->where('status', ApplicationPayment::STATUS_PENDING)->modelKeys();

        if ($ids) {
            ApplicationPayment::whereKey($ids)->update(['status' => ApplicationPayment::STATUS_EXPIRED]);
        }
    }
}
