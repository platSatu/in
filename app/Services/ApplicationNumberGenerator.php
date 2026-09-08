<?php

namespace App\Services;

use App\Models\ApplicationNumberCounter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Generate nomor Aplikasi Kuliah unik & berurutan per bulan, format
 * "APP-YYMM-00001" (mis. "APP-2609-00001" = aplikasi ke-1 bulan September
 * 2026). Aman dipakai walau ada beberapa submit bersamaan (concurrent),
 * karena counter-nya di-lock (lockForUpdate) di dalam transaksi DB --
 * dua request yang submit di detik yang sama tetap dapat nomor urut yang
 * berbeda, bukan nomor yang sama/bentrok.
 *
 * Dibuat fase 4 (fitur Apply Kampus), dipakai satu-satunya oleh
 * App\Http\Controllers\Student\ApplyController::store().
 */
class ApplicationNumberGenerator
{
    public function next(): string
    {
        return DB::transaction(function () {
            $periodKey = now()->format('ym'); // "2609" = September 2026

            $counter = $this->lockedCounterFor($periodKey);

            $nextSequence = $counter->last_sequence + 1;

            $counter->update(['last_sequence' => $nextSequence]);

            return sprintf('APP-%s-%05d', $periodKey, $nextSequence);
        });
    }

    /**
     * Ambil (atau buat) baris counter untuk periode ini dengan lockForUpdate,
     * supaya increment last_sequence di next() aman dari request lain yang
     * submit bersamaan pada periode yang sama.
     */
    private function lockedCounterFor(string $periodKey): ApplicationNumberCounter
    {
        $counter = ApplicationNumberCounter::where('period_key', $periodKey)
            ->lockForUpdate()
            ->first();

        if ($counter) {
            return $counter;
        }

        // Baris counter untuk periode ini belum ada -- kemungkinan besar
        // aplikasi PERTAMA di bulan ini. Kalau dua request benar-benar
        // barengan lolos pengecekan di atas (sangat jarang -- cuma bisa
        // terjadi sekali per bulan, di awal bulan itu), salah satunya akan
        // nabrak constraint unique di period_key saat create() -- ditangkap
        // di bawah, lalu ambil ulang baris yang barusan berhasil dibuat oleh
        // request satunya (pola sama dengan StudentIdentityResolver).
        try {
            return ApplicationNumberCounter::create([
                'period_key' => $periodKey,
                'last_sequence' => 0,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            return ApplicationNumberCounter::where('period_key', $periodKey)
                ->lockForUpdate()
                ->firstOrFail();
        }
    }
}
