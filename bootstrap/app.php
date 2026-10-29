<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Jaring pengaman timer pembayaran — lihat docblock
        // App\Console\Commands\ExpireStalePayments. Butuh cron
        // `* * * * * php artisan schedule:run` aktif di server supaya benar-benar jalan.
        $schedule->command('payments:expire-stale')->everyFiveMinutes();
        // Periode Honor Pengajar 26 -> 25: buka tiap tanggal 26, tutup otomatis
        // kalau sudah bersih (lihat TeacherHonorService::syncPeriods()).
        $schedule->command('teacher-honor:sync-periods')->hourly()->withoutOverlapping();
        // Backup database harian, hasilnya di Settings > Backup Database (14 terbaru disimpan).
        $schedule->command('db:backup')->dailyAt('01:00')->timezone('Asia/Jakarta')->withoutOverlapping();
        // Pengingat dokumen student (Students > File Student > tab Notifikasi); jam kirimnya diatur di menu itu.
        $schedule->command('student-documents:remind')->hourly()->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
