<?php

namespace App\Console\Commands;

use App\Services\TeacherHonor\TeacherHonorService;
use Illuminate\Console\Command;

/**
 * Periode Honor Pengajar otomatis 26 -> 25 (lihat
 * TeacherHonorService::syncPeriods()): buka periode baru tiap tanggal 26 per
 * cabang, tutup periode yang sudah lewat kalau semua pengajuannya selesai.
 * Aman dijalankan berulang. Terjadwal tiap jam di bootstrap/app.php.
 */
class SyncTeacherHonorPeriods extends Command
{
    protected $signature = 'teacher-honor:sync-periods';

    protected $description = 'Buka periode honor baru tiap tanggal 26 dan tutup periode yang sudah lewat tanggal 25.';

    public function handle(TeacherHonorService $honorService): int
    {
        $result = $honorService->syncPeriods();

        $this->info("Periode dibuat: {$result['created']}, ditutup: {$result['closed']}, masih menunggu pengajuan selesai: {$result['waiting']}");

        return self::SUCCESS;
    }
}
