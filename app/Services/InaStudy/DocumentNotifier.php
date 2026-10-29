<?php

namespace App\Services\InaStudy;

use App\Mail\DocumentNotificationMail;
use App\Models\DocumentNotificationLog;
use App\Models\DocumentNotificationSetting;
use App\Models\UniversityApplication;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Pengirim notifikasi dokumen student lewat WhatsApp (gateway Settings > WhatsApp Gateway)
 * dan email. Pengaturannya di Students > File Student > tab Notifikasi
 * (App\Models\DocumentNotificationSetting). Setiap kiriman dicatat di document_notification_logs.
 * Kegagalan kirim tidak pernah menggagalkan proses utama (mis. upload student tetap berhasil).
 */
class DocumentNotifier
{
    public function __construct(private readonly WhatsappMessenger $whatsapp)
    {
    }

    /**
     * Card 1: info ke admin bahwa student baru mengunggah dokumen.
     *
     * @param  array<int, string>  $documents  label dokumen yang diunggah, mis. ["Passport", "Any Certificate: HSK 4"]
     */
    public function documentsUploaded(UniversityApplication $application, array $documents): void
    {
        $setting = DocumentNotificationSetting::current();
        $documents = array_values(array_filter($documents));

        if ($documents === [] || ($setting->adminWhatsappTargets() === [] && $setting->adminEmailTargets() === [])) {
            return;
        }

        $application->loadMissing(['student', 'university']);
        $student = $this->studentName($application);
        $place = $this->placeLabel($application);
        $url = route('quiz.university-application.show', $application->id);
        $list = implode(', ', $documents);

        $whatsapp = "📄 *Dokumen baru diunggah*\n{$student} ({$place}) baru saja mengunggah: {$list}.\n\nCek di: {$url}";
        $email = new DocumentNotificationMail(
            "Dokumen baru dari {$student}",
            ['Halo Admin,', "{$student} ({$place}) baru saja mengunggah dokumen berikut:", '• '.implode("\n• ", $documents), 'Silakan cek dan review dokumennya.'],
            $url,
            'Lihat Aplikasi',
        );

        $this->toAdmins($setting, 'uploaded', $whatsapp, $email, $application->id);
    }

    /** Tombol "Kirim Tes" di tab Notifikasi: pesan contoh ke semua nomor & email admin yang aktif. */
    public function sendAdminTest(): array
    {
        $setting = DocumentNotificationSetting::current();
        $whatsapp = "✅ *Tes notifikasi INA Study*\nKalau pesan ini sampai, notifikasi upload dokumen ke admin sudah berjalan.";
        $email = new DocumentNotificationMail('Tes notifikasi INA Study', ['Halo Admin,', 'Kalau email ini sampai, notifikasi upload dokumen ke admin sudah berjalan.']);

        return $this->toAdmins($setting, 'test', $whatsapp, $email, null);
    }

    /** @return array{sent: int, failed: int} */
    private function toAdmins(DocumentNotificationSetting $setting, string $type, string $whatsapp, DocumentNotificationMail $email, ?string $applicationId): array
    {
        $result = ['sent' => 0, 'failed' => 0];

        foreach ($setting->adminWhatsappTargets() as $number) {
            $result[$this->sendWhatsapp($number, $whatsapp, $type, $applicationId) ? 'sent' : 'failed']++;
        }

        foreach ($setting->adminEmailTargets() as $address) {
            $result[$this->sendEmail($address, $email, $type, $applicationId) ? 'sent' : 'failed']++;
        }

        return $result;
    }

    public function sendWhatsapp(string $number, string $message, string $type, ?string $applicationId): bool
    {
        try {
            $response = $this->whatsapp->send($number, $message);
            $ok = $response !== false && ! (is_array($response) && ($response['success'] ?? null) === false);
            $error = $ok ? null : (is_array($response) ? ($response['message'] ?? 'Gateway menolak pesan') : 'Gateway WhatsApp tidak bisa dihubungi');
        } catch (Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
        }

        $this->log($applicationId, $type, 'whatsapp', $number, $ok, $error);

        return $ok;
    }

    public function sendEmail(string $address, DocumentNotificationMail $mail, string $type, ?string $applicationId): bool
    {
        try {
            Mail::to($address)->send($mail);
            $ok = true;
            $error = null;
        } catch (Throwable $e) {
            report($e);
            $ok = false;
            $error = $e->getMessage();
        }

        $this->log($applicationId, $type, 'email', $address, $ok, $error);

        return $ok;
    }

    public function studentName(UniversityApplication $application): string
    {
        $student = $application->student;

        return trim(($student->first_name ?? '').' '.($student->last_name ?? '')) ?: 'Student';
    }

    /** "Peking University – Computer Science (Bachelor)". */
    public function placeLabel(UniversityApplication $application): string
    {
        $major = $application->major_label;

        return trim(($application->university?->name ?? 'Universitas').($major && $major !== '-' ? ' – '.$major : ''));
    }

    private function log(?string $applicationId, string $type, string $channel, string $recipient, bool $ok, ?string $error): void
    {
        try {
            DocumentNotificationLog::create([
                'application_id' => $applicationId,
                'type' => $type,
                'channel' => $channel,
                'recipient' => $recipient,
                'status' => $ok ? DocumentNotificationLog::STATUS_SENT : DocumentNotificationLog::STATUS_FAILED,
                'error' => $error ? mb_substr($error, 0, 250) : null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Gagal mencatat notifikasi dokumen', ['error' => $e->getMessage()]);
        }
    }
}
