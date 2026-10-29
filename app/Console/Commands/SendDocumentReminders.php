<?php

namespace App\Console\Commands;

use App\Models\DocumentNotificationSetting;
use App\Models\UniversityApplication;
use App\Services\InaStudy\DocumentCompleteness;
use App\Services\InaStudy\DocumentNotifier;
use Illuminate\Console\Command;
use Throwable;

/**
 * Pengingat dokumen ke student (29 Oktober 2026, Students > File Student > tab Notifikasi, Card 2 & 3).
 * Dijalankan setiap jam oleh cron; pengingat hanya dikirim pada jam yang dipilih admin (WIB).
 *
 * Aturan pengingat per aplikasi yang dokumen wajibnya belum lengkap:
 *  - Hari ke-0 = saat student submit Formulir. Jadwal = hari yang dipilih admin, mis. H+1, H+5, H+7, H+10.
 *  - Yang dikirim hanya jadwal TERAKHIR yang sudah lewat, dan paling lambat 2 hari setelah jadwalnya
 *    (supaya saat fitur baru dinyalakan, student lama tidak menerima banyak pesan sekaligus).
 *  - Setiap jadwal hanya sekali (dicek dari document_notification_logs).
 * Pesan "dokumen lengkap" juga dicek di sini (mis. dokumen dilengkapi lewat admin), masing-masing sekali.
 */
class SendDocumentReminders extends Command
{
    protected $signature = 'student-documents:remind
        {--force : Proses sekarang tanpa menunggu jam kirim yang diatur}
        {--dry-run : Hanya tampilkan siapa yang akan dikirimi, TIDAK mengirim apa pun}';

    protected $description = 'Kirim pengingat dokumen ke student yang belum lengkap & pesan dokumen lengkap';

    private const GRACE_DAYS = 2;

    public function handle(DocumentCompleteness $completeness, DocumentNotifier $notifier): int
    {
        $setting = DocumentNotificationSetting::current();
        $now = now()->timezone(config('app.timezone'));
        $reminderTime = $this->option('force') || (int) $now->format('G') === (int) ($setting->reminder_hour ?? 10);
        $days = $setting->reminderDays();
        $reminded = 0;
        $completed = 0;
        $dryRun = (bool) $this->option('dry-run');
        $preview = [];

        if ($completeness->requiredTypes()->isEmpty()) {
            $this->warn('Belum ada jenis dokumen wajib (is_required) yang aktif, tidak ada yang diproses.');

            return self::SUCCESS;
        }

        $completeness->openApplications()
            ->with(['student', 'university', 'formDetail', 'documents:id,application_id,document_type_id,review_status'])
            ->chunkById(100, function ($applications) use ($completeness, $notifier, $setting, $now, $reminderTime, $days, $dryRun, &$preview, &$reminded, &$completed) {
                foreach ($applications as $application) {
                    try {
                        if ($completeness->isComplete($application)) {
                            if (! $dryRun) {
                                $notifier->notifyIfComplete($application, $completeness);
                            }
                            $completed++;

                            continue;
                        }

                        if (! $reminderTime || ! $setting->reminder_enabled || $days === []) {
                            continue;
                        }

                        $day = $this->dueDay($application, $completeness, $days, $now);

                        if ($day !== null && ! $notifier->alreadySent($application->id, 'reminder_d'.$day)) {
                            $missing = $completeness->missing($application)->all();

                            if ($dryRun) {
                                $preview[] = [$notifier->studentName($application), 'H+'.$day, $notifier->studentPhone($application) ?? '-', $application->student?->email ?? '-', implode(', ', $missing)];
                                $reminded++;

                                continue;
                            }

                            $reminded += $notifier->remindStudent($application, $day, $missing, $setting) > 0 ? 1 : 0;
                        }
                    } catch (Throwable $e) {
                        report($e);
                        $this->error("Aplikasi {$application->id}: {$e->getMessage()}");
                    }
                }
            });

        if ($dryRun) {
            $this->table(['Student', 'Jadwal', 'WhatsApp', 'Email', 'Dokumen kurang'], $preview);
            $this->warn("DRY RUN: tidak ada pesan yang dikirim. Akan diingatkan: {$reminded} student. Aplikasi sudah lengkap: {$completed}.");

            return self::SUCCESS;
        }

        $this->info("Pengingat terkirim: {$reminded} student. Aplikasi lengkap dicek: {$completed}.".($reminderTime ? '' : ' (Bukan jam kirim pengingat.)'));

        return self::SUCCESS;
    }

    /** Jadwal (H+n) yang jatuh tempo hari ini untuk aplikasi ini, atau null. */
    private function dueDay(UniversityApplication $application, DocumentCompleteness $completeness, array $days, $now): ?int
    {
        $start = $completeness->startDate($application);

        if (! $start) {
            return null;
        }

        $elapsed = (int) $start->copy()->startOfDay()->diffInDays($now->copy()->startOfDay());
        $due = collect($days)->filter(fn ($day) => $day <= $elapsed)->max();

        return $due !== null && $elapsed - $due <= self::GRACE_DAYS ? (int) $due : null;
    }
}
