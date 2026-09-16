<?php

namespace App\Services\CourseCredit;

use App\Helpers\MoneyMath;
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
            $balanceBefore = $this->lockedBalance($lockedStudent);

            if ($balanceBefore < $amount) {
                throw new InsufficientCourseCreditException(
                    "Saldo credit student tidak cukup (tersedia {$balanceBefore}, butuh {$amount})."
                );
            }

            return $this->allocateAndCreateDebit($lockedStudent, $balanceBefore, $amount, $sourceType, $description);
        });
    }

    /**
     * FASE 4 -- menukar (trade-in) SELURUH sisa saldo credit student jadi 1
     * baris debit, dengan asal (allocations) tetap terlacak FIFO persis
     * seperti debit() biasa. Dipakai saat siswa upgrade paket: sisa credit
     * lama "dicairkan" ke nilai rupiah untuk dipakai menutup harga paket
     * baru -- BUKAN dikembalikan tunai (lihat diskusi: "tidak ada proses
     * mencairkan tapi harus dipergunakan untuk membeli paket lagi").
     *
     * SELALU dipanggil di DALAM 1 DB transaction yang sama dengan pembuatan
     * CoursePackagePurchase & CoursePackagePayment baru (lihat
     * PackageUpgradeCalculator::completeInstant()) supaya trade-in & checkout
     * package baru selalu sepasang, tidak mungkin salah satu doang berhasil.
     *
     * @return CourseCredit|null null kalau saldo credit student ternyata
     *         sudah 0 saat dicek ulang di dalam lock -- BUKAN exception,
     *         supaya pemanggil bisa lanjut checkout seperti pembelian baru
     *         biasa (tanpa trade-in) tanpa perlu try/catch.
     */
    public function debitEntireBalance(Student $student, string $sourceType, string $description): ?CourseCredit
    {
        return DB::transaction(function () use ($student, $sourceType, $description) {
            $lockedStudent = Student::where('id', $student->id)->lockForUpdate()->first();
            $balanceBefore = $this->lockedBalance($lockedStudent);

            if ($balanceBefore <= 0.0) {
                return null;
            }

            return $this->allocateAndCreateDebit($lockedStudent, $balanceBefore, $balanceBefore, $sourceType, $description);
        });
    }

    /**
     * FASE 4 -- preview TANPA lock, TANPA menulis apa pun: menghitung nilai
     * rupiah (kalau ditukar/trade-in) dari SELURUH sisa saldo credit student
     * saat ini, dipakai PackageUpgradeCalculator::preview() untuk memutuskan
     * breakdown harga (trade-in menutup berapa, sisanya Deposit lalu
     * gateway) SEBELUM commit sungguhan.
     *
     * Angka di sini BISA berubah oleh transaksi lain sampai commit
     * sungguhan lewat debitEntireBalance() -- itu sebabnya
     * debitEntireBalance() SELALU mengecek ulang saldo dari 0 di dalam
     * lock-nya sendiri, TIDAK percaya angka preview ini.
     *
     * @return array{balance: float, value: float}
     */
    public function previewTradeInValue(Student $student): array
    {
        $balance = MoneyMath::floorToScale(
            (float) (CourseCredit::where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->value('balance') ?? 0),
            2
        );

        if ($balance <= 0.0) {
            return ['balance' => 0.0, 'value' => 0.0];
        }

        $purchases = CoursePackagePurchase::where('student_id', $student->id)
            ->where('status', CoursePackagePurchase::STATUS_COMPLETED)
            ->orderBy('created_at')
            ->get();

        $result = $this->allocateFromPurchases($purchases, $balance);

        $value = MoneyMath::floorToScale(
            array_sum(array_column($result['allocations'], 'value')),
            2
        );

        return ['balance' => $balance, 'value' => $value];
    }

    /**
     * Saldo credit TERKINI 1 student, diambil DI DALAM lock (baris
     * CourseCredit terakhirnya) -- caller WAJIB sudah lockForUpdate() baris
     * Student itu sendiri lebih dulu (urutan lock: Student baru CourseCredit,
     * sama di debit() & debitEntireBalance()).
     */
    private function lockedBalance(Student $lockedStudent): float
    {
        $lastCredit = CourseCredit::where('student_id', $lockedStudent->id)
            ->orderByDesc('created_at')
            ->lockForUpdate()
            ->first();

        return MoneyMath::floorToScale((float) ($lastCredit?->balance ?? 0), 2);
    }

    /**
     * Inti pemotongan: lock semua CoursePackagePurchase student, jalankan
     * FIFO (allocateFromPurchases()), lalu tulis 1 baris CourseCredit debit
     * + baris-baris CourseCreditAllocation-nya. Dipanggil dari DALAM
     * transaction yang sudah lock Student & CourseCredit terakhir
     * (lockedBalance()) -- $balanceBefore WAJIB angka yang sudah dicek di
     * dalam lock itu, bukan angka preview dari luar.
     *
     * @throws InsufficientCourseCreditException lihat allocateFromPurchases().
     */
    private function allocateAndCreateDebit(Student $lockedStudent, float $balanceBefore, float $amount, string $sourceType, string $description): CourseCredit
    {
        $purchases = CoursePackagePurchase::where('student_id', $lockedStudent->id)
            ->where('status', CoursePackagePurchase::STATUS_COMPLETED)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        $result = $this->allocateFromPurchases($purchases, $amount);
        $allocationRows = $result['allocations'];
        $remaining = $result['remaining'];

        // Jaring pengaman -- kalau ternyata FIFO di atas TIDAK bisa
        // menutup $amount penuh (mis. data allocations & balance sempat
        // tidak sinkron), batalkan semuanya daripada membuat baris debit
        // yang asal-usulnya tidak lengkap terlacak.
        if ($remaining > 0.0) {
            throw new InsufficientCourseCreditException(
                "Riwayat pembelian credit student tidak cukup untuk menutup {$amount} credit (kurang {$remaining})."
            );
        }

        $balanceAfter = MoneyMath::floorToScale($balanceBefore - $amount, 2);

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
    }

    /**
     * Jalan FIFO murni (tanpa lock, tanpa tulis apa pun) atas 1 koleksi
     * CoursePackagePurchase yang SUDAH diambil pemanggil (locked atau tidak,
     * tergantung tujuannya -- lihat allocateAndCreateDebit() vs
     * previewTradeInValue()): tentukan dari batch mana saja $amount credit
     * ini "berasal", beserta nilai rupiah tiap batch berdasarkan
     * price_paid/credits_granted snapshot batch itu.
     *
     * @param iterable<CoursePackagePurchase> $purchases
     * @return array{allocations: array<int, array{course_package_purchase_id: string, amount: float, unit_price: float, value: float}>, remaining: float}
     */
    private function allocateFromPurchases(iterable $purchases, float $amount): array
    {
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
            $availableInBatch = MoneyMath::floorToScale($creditsGranted - $alreadyAllocated, 2);

            if ($availableInBatch <= 0.0) {
                continue;
            }

            $takeFromBatch = MoneyMath::floorToScale(min($availableInBatch, $remaining), 2);

            if ($takeFromBatch <= 0.0) {
                continue;
            }

            $unitPrice = MoneyMath::floorToScale(((float) $purchase->price_paid) / $creditsGranted, 4);
            $value = MoneyMath::floorToScale($takeFromBatch * $unitPrice, 2);

            $allocationRows[] = [
                'course_package_purchase_id' => $purchase->id,
                'amount' => $takeFromBatch,
                'unit_price' => $unitPrice,
                'value' => $value,
            ];

            $remaining = MoneyMath::floorToScale($remaining - $takeFromBatch, 2);
        }

        return ['allocations' => $allocationRows, 'remaining' => $remaining];
    }
}
