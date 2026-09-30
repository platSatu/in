<?php

namespace App\Services\CourseCredit;

use App\Helpers\MoneyMath;
use App\Models\CourseCredit;
use App\Models\CourseCreditAllocation;
use App\Models\CoursePackagePurchase;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * FASE 1 "Fondasi Pelacakan Asal Credit" (16 September 2026, hasil diskusi
 * Konversi/Upgrade Paket & Honor Pengajar) -- satu-satunya pintu untuk
 * MEMOTONG (debit) saldo CourseCredit seorang student, dirancang dipakai
 * bersama oleh 2 fitur yang MASIH tahap desain: Jadwal+Absensi (nanti pakai
 * source_type SESSION_DEBIT) & Konversi/Upgrade Paket (nanti pakai
 * source_type TRADE_IN_DEBIT). BELUM ada pemanggil sungguhan di codebase ini
 * sampai salah satu dari 2 fitur itu dibangun -- disiapkan sekarang supaya
 * keduanya berbagi 1 logika alokasi FIFO yang sama persis, tidak ditulis
 * ulang beda-beda di 2 tempat.
 *
 * KENAPA FIFO ATAS DASAR CoursePackagePurchase (bukan menghitung ulang dari
 * baris course_credits): saldo credit itu 1 POOL BERSAMA per student (lihat
 * docblock CourseCredit::currentBalanceFor()) -- untuk tahu "sisa berapa dari
 * pembelian yang mana", cara paling akurat adalah menjumlah alokasi yang
 * SUDAH tercatat di course_credit_allocations untuk tiap purchase (bukan
 * menaksir ulang dari kolom debit/kredit yang sudah tercampur jadi 1 pool).
 *
 * PRESISI: pembulatan SELALU ke bawah (supaya sistem tidak pernah
 * "menciptakan" nilai lebih dari yang sebenarnya pernah dibayar, sesuai
 * kesepakatan diskusi Konversi Paket) dilakukan lewat App\Helpers\MoneyMath
 * -- lihat docblock di sana untuk alasan lengkap kenapa tidak pakai bcmath.
 * Dipakai sebagai utilitas BERSAMA supaya kebijakan pembulatan ini konsisten
 * dengan App\Services\TeacherHonor\TeacherHonorService (Fase 3).
 *
 * KEAMANAN: row-locking (Student, semua CoursePackagePurchase student itu,
 * baris CourseCredit terakhir) SEMUA di dalam 1 DB transaction -- pola SAMA
 * PERSIS dengan InaYulePackageCheckoutController::completeWithDepositOnly()
 * & InaYulePackageWebhookController -- supaya 2 proses debit nyaris
 * bersamaan tidak mungkin memotong dari saldo/batch yang sama dobel.
 *
 * FASE 4 "Konversi/Upgrade Paket" (16 September 2026): logika jalan FIFO di
 * atas DIPECAH jadi beberapa private helper (lihat allocateFromPurchases(),
 * lockedBalance(), allocateAndCreateDebit() di bawah) supaya bisa dipakai
 * ULANG oleh 3 method publik tanpa nulis logikanya 2-3 kali:
 * - debit(): potong jumlah TETAP (dipakai Fase 2, ClassSessionWorkflowService).
 * - debitEntireBalance(): potong SELURUH sisa saldo sekaligus (trade-in saat
 *   upgrade package, lihat App\Services\CoursePackagePayment\
 *   PackageUpgradeCalculator) -- balik null (bukan exception) kalau saldo
 *   ternyata sudah 0 saat dicek ulang di dalam lock.
 * - previewTradeInValue(): baca-saja, TANPA lock, TANPA menulis apa pun --
 *   dipakai PackageUpgradeCalculator::preview() untuk menghitung breakdown
 *   harga SEBELUM commit, pola "preview di luar lock, cek ulang di dalam
 *   lock" sama seperti InaYulePackageCheckoutController::store().
 *
 * CREDIT TERPISAH PER PAKET (30 September 2026): credit tidak lagi 1 pool
 * lintas paket. Sesi kelas hanya memotong dari pembelian paket yang sama
 * (debit() dengan $coursePackageId), dan upgrade/convert hanya menukar
 * credit dari 1 baris pembelian yang dipilih siswa (debitPurchase()).
 * Sisa per pembelian = credits_granted - total alokasi (remainingByPurchase()).
 * Kolom balance di course_credits tetap total semua paket (ringkasan).
 */
class CourseCreditDebitService
{
    /**
     * Potong $amount credit, FIFO hanya dari pembelian paket $coursePackageId
     * (null = semua paket, untuk data lama tanpa paket).
     *
     * @throws InsufficientCourseCreditException kalau sisa credit paket itu kurang -- dicek di dalam lock.
     */
    public function debit(Student $student, float $amount, string $sourceType, string $description, ?string $coursePackageId = null): CourseCredit
    {
        if ($amount <= 0.0) {
            throw new InvalidArgumentException('Jumlah credit yang dipotong harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($student, $amount, $sourceType, $description, $coursePackageId) {
            $lockedStudent = Student::where('id', $student->id)->lockForUpdate()->first();
            $purchases = $this->lockedPurchases($lockedStudent->id, coursePackageId: $coursePackageId);

            return $this->createDebit($lockedStudent, $purchases, $amount, $sourceType, $description);
        });
    }

    /**
     * Potong credit dari SATU baris pembelian (trade-in upgrade/convert).
     * $amount null = seluruh sisa pembelian itu. Balik null kalau sisanya 0.
     */
    public function debitPurchase(Student $student, CoursePackagePurchase $purchase, ?float $amount, string $sourceType, string $description): ?CourseCredit
    {
        return DB::transaction(function () use ($student, $purchase, $amount, $sourceType, $description) {
            $lockedStudent = Student::where('id', $student->id)->lockForUpdate()->first();
            $purchases = $this->lockedPurchases($lockedStudent->id, purchaseId: $purchase->id);
            $remaining = $this->available($purchases)[$purchase->id] ?? 0.0;
            $amount ??= $remaining;

            if ($amount <= 0.0) {
                return null;
            }

            return $this->createDebit($lockedStudent, $purchases, $amount, $sourceType, $description);
        });
    }

    /**
     * Kembalikan 1 baris debit (refund sesi): baris kredit baru + alokasi
     * NEGATIF ke pembelian asal yang sama, jadi sisa tiap pembelian kembali
     * persis seperti sebelum dipotong. Pemanggil wajib memastikan debit ini
     * belum pernah dikembalikan (dicek di dalam lock sesi).
     */
    public function refund(CourseCredit $debit, string $description): CourseCredit
    {
        return DB::transaction(function () use ($debit, $description) {
            Student::where('id', $debit->student_id)->lockForUpdate()->firstOrFail();
            $lastCredit = CourseCredit::where('student_id', $debit->student_id)
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();
            $amount = (float) $debit->debit;

            $refund = CourseCredit::create([
                'student_id' => $debit->student_id,
                'course_package_purchase_id' => null,
                'source_type' => CourseCredit::SOURCE_SESSION_REFUND,
                'debit' => 0,
                'kredit' => $amount,
                'balance' => MoneyMath::floorToScale((float) ($lastCredit?->balance ?? 0) + $amount, 2),
                'description' => $description,
            ]);

            foreach ($debit->allocations()->get() as $allocation) {
                CourseCreditAllocation::create([
                    'course_credit_id' => $refund->id,
                    'course_package_purchase_id' => $allocation->course_package_purchase_id,
                    'amount' => -(float) $allocation->amount,
                    'unit_price' => $allocation->unit_price,
                    'value' => -(float) $allocation->value,
                ]);
            }

            return $refund;
        });
    }

    /**
     * Sisa credit per pembelian (baca-saja).
     *
     * @param  iterable<CoursePackagePurchase>  $purchases
     * @return array<string, float> purchase_id => sisa credit
     */
    public function remainingByPurchase(iterable $purchases): array
    {
        return $this->available(collect($purchases)->where('status', CoursePackagePurchase::STATUS_COMPLETED));
    }

    /** Harga per credit sebuah pembelian (snapshot saat dibeli), dibulatkan ke bawah. */
    public static function unitPrice(CoursePackagePurchase $purchase): float
    {
        $credits = (float) $purchase->credits_granted;

        return $credits > 0 ? MoneyMath::floorToScale(((float) $purchase->price_paid) / $credits, 4) : 0.0;
    }

    /** Pembelian completed milik student (opsional 1 paket / 1 baris), urut FIFO, dikunci. */
    private function lockedPurchases(string $studentId, ?string $coursePackageId = null, ?string $purchaseId = null): Collection
    {
        return CoursePackagePurchase::where('student_id', $studentId)
            ->where('status', CoursePackagePurchase::STATUS_COMPLETED)
            ->when($coursePackageId, fn ($query) => $query->where('course_package_id', $coursePackageId))
            ->when($purchaseId, fn ($query) => $query->whereKey($purchaseId))
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();
    }

    /**
     * Sisa credit per pembelian = credits_granted - total alokasi (1 query).
     *
     * @return array<string, float>
     */
    private function available(iterable $purchases): array
    {
        $purchases = collect($purchases);
        $allocated = CourseCreditAllocation::whereIn('course_package_purchase_id', $purchases->pluck('id'))
            ->groupBy('course_package_purchase_id')
            ->selectRaw('course_package_purchase_id, SUM(amount) as total')
            ->pluck('total', 'course_package_purchase_id');

        return $purchases->mapWithKeys(fn (CoursePackagePurchase $purchase) => [
            $purchase->id => max(0.0, MoneyMath::floorToScale((float) $purchase->credits_granted - (float) ($allocated[$purchase->id] ?? 0), 2)),
        ])->all();
    }

    /**
     * Tulis 1 baris debit + alokasinya. Dipanggil di dalam transaction yang
     * sudah lock Student & $purchases. Semua angka dicek ulang di sini.
     *
     * @throws InsufficientCourseCreditException
     */
    private function createDebit(Student $lockedStudent, Collection $purchases, float $amount, string $sourceType, string $description): CourseCredit
    {
        $amount = MoneyMath::floorToScale($amount, 2);
        $lastCredit = CourseCredit::where('student_id', $lockedStudent->id)
            ->orderByDesc('created_at')
            ->lockForUpdate()
            ->first();
        $balanceBefore = MoneyMath::floorToScale((float) ($lastCredit?->balance ?? 0), 2);

        $available = $this->available($purchases);
        $remaining = $amount;
        $allocationRows = [];

        foreach ($purchases as $purchase) {
            $take = MoneyMath::floorToScale(min($available[$purchase->id] ?? 0.0, $remaining), 2);

            if ($take <= 0.0) {
                continue;
            }

            $unitPrice = self::unitPrice($purchase);
            $allocationRows[] = [
                'course_package_purchase_id' => $purchase->id,
                'amount' => $take,
                'unit_price' => $unitPrice,
                'value' => MoneyMath::floorToScale($take * $unitPrice, 2),
            ];
            $remaining = MoneyMath::floorToScale($remaining - $take, 2);

            if ($remaining <= 0.0) {
                break;
            }
        }

        if ($remaining > 0.0 || $balanceBefore < $amount) {
            throw new InsufficientCourseCreditException(
                "Sisa credit paket ini tidak cukup (butuh {$amount}, kurang {$remaining})."
            );
        }

        $debit = CourseCredit::create([
            'student_id' => $lockedStudent->id,
            'course_package_purchase_id' => null,
            'source_type' => $sourceType,
            'debit' => $amount,
            'kredit' => 0,
            'balance' => MoneyMath::floorToScale($balanceBefore - $amount, 2),
            'description' => $description,
        ]);

        foreach ($allocationRows as $row) {
            CourseCreditAllocation::create($row + ['course_credit_id' => $debit->id]);
        }

        return $debit->load('allocations');
    }
}
