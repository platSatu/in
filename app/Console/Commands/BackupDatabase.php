<?php

namespace App\Console\Commands;

use App\Services\Backup\DatabaseBackup;
use Illuminate\Console\Command;

/** Backup database harian (dijadwalkan di bootstrap/app.php). Hasilnya bisa diunduh di Settings > Backup Database. */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Backup database ke storage/app/private/backups ('.DatabaseBackup::KEEP.' backup terbaru disimpan)';

    public function handle(DatabaseBackup $backup): int
    {
        $this->info('Backup dibuat: '.$backup->create());

        return self::SUCCESS;
    }
}
