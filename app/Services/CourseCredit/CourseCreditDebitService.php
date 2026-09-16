<?php

namespace App\Services\CourseCredit;

use App\Models\CourseCredit;
use App\Models\CourseCreditAllocation;
use App\Models\CoursePackagePurchase;
use App\Models\Student;
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
 * PRESISI: sengaja TIDAK pakai ekstensi bcmath (tidak ada satupun pemakaian
 * bcmath di codebase ini & belum bisa dipastikan aktif di server produksi).
 * Pembulatan SELALU ke bawah (supaya sistem tidak pernah "menciptakan" nilai
 * lebih dari yang sebenarnya pernah dibayar, sesuai kesepakatan diskusi
 * Konversi Paket) dilakukan lewat floorToScale() dengan epsilon kecil untuk
 * meredam representasi float yang tidak presisi (mis. 2.0 tersimpan sebagai
 * 1.9999999999998).
 *
 * KEAMANAN: row-locking (Student, semua CoursePackagePurchase student itu,
 * baris CourseCredit terakhir) SEMUA di dalam 1 DB transaction -- pola SAMA
 * PERSIS dengan InaYulePackageCheckoutController::completeWithDepositOnly()
 * & InaYulePackageWebhookController -- supaya 2 proses debit nyaris
 * bersamaan tidak mungkin memotong dari saldo/batch yang sama dobel.
 */
class CourseCreditDebitService
{
    /**
     * @throws InsufficientCourseCreditException kalau saldo credit student
     *         ternyata kurang dari $amount -- dicek ULANG di dalam lock,
     *         bukan cuma preview dari luar transaction.
     */
    public function debit(Student $student, float $amount, string $sourceType, string $description): CourseCredit
    {
        if ($amount <= 0.0) {
            throw new InvalidArgumentException('Jumlah credit yang dipotong harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($student, $amount, $sourceType, $description) {
            $lockedStudent = Student::where('id', $student->id)->lockForUpdate()->first();

            $lastCredit = CourseCredit::where('student_id', $lockedStudent->id)
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            $balanceBefore = (float) ($lastCredit?->balance ?? 0);

            if ($balanceBefore < $amount) {
                throw new InsufficientCourseCreditException(
                    "Saldo credit student tidak cukup (tersedia {$balanceBefore}, butuh {$amount})."
                );
            }

            $purchases = CoursePackagePurchase::where('student_id', $lockedStudent->id)
                ->where('status', CoursePackagePurchase::STATUS_COMPLETED)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $remaining = $amount;
            $allocationRows = [];

            foreach ($purchases as $purchase) {
                if ($remaining <= 0.0) {
                    break;
                }

                $creditsGranted = (float) $purchase->credits_granted;

                if ($creditsGranted <= 0.0) {
                    continue;
                }

                $alreadyAllocated = (float) CourseCreditAllocation::where('course_package_purchase_id', $purchase->id)->sum('amount');
                $availableInBatch = $this->floorToScale($creditsGranted - $alreadyAllocated, 2);

                if ($availableInBatch <= 0.0) {
                    continue;
                }

                $takeFromBatch = $this->floorToScale(min($availableInBatch, $remaining), 2);

                if ($takeFromBatch <= 0.0) {
                    continue;
                }

                $unitPrice = $this->floorToScale(((float) $purchase->price_paid) / $creditsGranted, 4);
                $value = $this->floorToScale($takeFromBatch * $unitPrice, 2);

                $allocationRows[] = [
                    'course_package_purchase_id' => $purchase->id,
                    'amount' => $takeFromBatch,
                    'unit_price' => $unitPrice,
                    'value' => $value,
                ];

                $remaining = $this->floorToScale($remaining - $takeFromBatch, 2);
            }

            // Jaring pengaman -- kalau ternyata FIFO di atas TIDAK bisa
            // menutup $amount penuh (mis. data allocations & balance sempat
            // tidak sinkron), batalkan semuanya daripada membuat baris debit
            // yang asal-usulnya tidak lengkap terlacak.
            if ($remaining > 0.0) {
                throw new InsufficientCourseCreditException(
                    "Riwayat pembelian credit student tidak cukup untuk menutup {$amount} credit (kurang {$remaining})."
                );
            }

            $balanceAfter = $this->floorToScale($balanceBefore - $amount, 2);

            $debit = CourseCredit::create([
                'student_id' => $lockedStudent->id,
                'course_package_purchase_id' => null,
                'source_type' => $sourceType,
                'debit' => $amount,
                'kredit' => 0,
                'balance' => $balanceAfter,
                'description' => $description,
            ]);

            foreach ($allocationRows as $row) {
                CourseCreditAllocation::create($row + ['course_credit_id' => $debit->id]);
            }

            return $debit->load('allocations');
        });
    }

    /**
     * Bulatkan SELALU ke bawah ke $scale angka desimal -- epsilon kecil
     * ditambahkan dulu untuk meredam representasi float yang tidak presisi
     * (mis. 2.0 yang sebenarnya tersimpan sebagai 1.9999999999998 di memori)
     * supaya tidak salah kepotong 1 angka lebih rendah dari yang seharusnya.
     */
    private function floorToScale(float $value, int $scale): float
    {
        $factor = 10 ** $scale;

        return floor(($value * $factor) + 1e-6) / $factor;
    }
}
