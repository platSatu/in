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

    /**
     * Card 2: pengingat ke student bahwa masih ada dokumen wajib yang belum diunggah.
     * Tercatat dengan tipe "reminder_d{hari}" supaya tiap jadwal hanya terkirim sekali.
     *
     * @param  array<int, string>  $missing
     * @return int jumlah pesan yang terkirim
     */
    public function remindStudent(UniversityApplication $application, int $day, array $missing, DocumentNotificationSetting $setting): int
    {
        $application->loadMissing(['student', 'university']);
        $name = $this->firstName($application);
        $place = $application->university?->name ?? 'kampus pilihan Kakak';
        $list = implode(', ', $missing);
        $url = route('student-portal.applications.documents.edit', $application->id);
        $type = 'reminder_d'.$day;
        $sent = 0;

        if ($setting->reminder_whatsapp && ($phone = $this->studentPhone($application))) {
            $message = "Hai Kak {$name} 👋\n\n"
                ."Kami dari Tim INA Study mau mengingatkan, masih ada beberapa dokumen yang belum Kakak unggah untuk pendaftaran di {$place}:\n"
                ."*{$list}*\n\n"
                ."Yuk dilengkapi supaya proses pendaftaran Kakak bisa segera kami lanjutkan 😊\n{$url}\n\n"
                .'Kalau ada kendala, balas pesan ini ya, kami siap bantu!';
            $sent += (int) $this->sendWhatsapp($phone, $message, $type, $application->id);
        }

        if ($setting->reminder_email && ($email = $application->student?->email)) {
            $sent += (int) $this->sendEmail($email, new DocumentNotificationMail(
                'Yuk lengkapi dokumen pendaftaran Kakak',
                [
                    "Hai Kak {$name} 👋",
                    "Kami dari Tim INA Study mau mengingatkan, masih ada beberapa dokumen yang belum Kakak unggah untuk pendaftaran di {$place}:",
                    '• '.implode("\n• ", $missing),
                    'Yuk dilengkapi supaya proses pendaftaran Kakak bisa segera kami lanjutkan 😊',
                    'Kalau ada kendala, balas email ini ya, kami siap bantu!',
                ],
                $url,
                'Lengkapi Dokumen',
            ), $type, $application->id);
        }

        return $sent;
    }

    /**
     * Card 3: dokumen wajib sudah lengkap -- terima kasih ke student & info ke admin, masing-masing SEKALI.
     * Aman dipanggil berkali-kali (cek catatan "complete_student" / "complete_admin" yang sudah terkirim).
     */
    public function notifyIfComplete(UniversityApplication $application, DocumentCompleteness $completeness): void
    {
        $setting = DocumentNotificationSetting::current();

        if ((! $setting->complete_notify_student && ! $setting->complete_notify_admin) || ! $completeness->isComplete($application)) {
            return;
        }

        $application->loadMissing(['student', 'university']);
        $name = $this->firstName($application);
        $place = $application->university?->name ?? 'kampus pilihan Kakak';

        if ($setting->complete_notify_student && ! $this->alreadySent($application->id, 'complete_student')) {
            if ($setting->reminder_whatsapp && ($phone = $this->studentPhone($application))) {
                $this->sendWhatsapp($phone, "Terima kasih Kak {$name}! 🎉\n\nSemua dokumen untuk pendaftaran di {$place} sudah lengkap. Tim kami akan segera memeriksanya dan mengabari Kakak untuk langkah berikutnya 😊", 'complete_student', $application->id);
            }
            if ($setting->reminder_email && ($email = $application->student?->email)) {
                $this->sendEmail($email, new DocumentNotificationMail(
                    'Terima kasih, dokumen Kakak sudah lengkap 🎉',
                    ["Terima kasih Kak {$name}! 🎉", "Semua dokumen untuk pendaftaran di {$place} sudah lengkap.", 'Tim kami akan segera memeriksanya dan mengabari Kakak untuk langkah berikutnya 😊'],
                ), 'complete_student', $application->id);
            }
        }

        if ($setting->complete_notify_admin && ! $this->alreadySent($application->id, 'complete_admin')) {
            $student = $this->studentName($application);
            $placeLabel = $this->placeLabel($application);
            $url = route('quiz.university-application.show', $application->id);

            $this->toAdmins($setting, 'complete_admin',
                "✅ *Dokumen lengkap*\n{$student} ({$placeLabel}) sudah melengkapi semua dokumen wajib.\n\nCek di: {$url}",
                new DocumentNotificationMail("Dokumen {$student} sudah lengkap", ['Halo Admin,', "{$student} ({$placeLabel}) sudah melengkapi semua dokumen wajib.", 'Silakan lanjutkan proses review.'], $url, 'Lihat Aplikasi'),
                $application->id,
            );
        }
    }

    /** Sudah pernah terkirim (minimal 1 kanal berhasil) untuk aplikasi & tipe ini? */
    public function alreadySent(string $applicationId, string $type): bool
    {
        return DocumentNotificationLog::where('application_id', $applicationId)
            ->where('type', $type)
            ->where('status', DocumentNotificationLog::STATUS_SENT)
            ->exists();
    }

    /** Nomor WA student: nomor di aplikasi, kalau kosong nomor HP di data student. */
    public function studentPhone(UniversityApplication $application): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) ($application->whatsapp ?: $application->student?->handphone));

        return strlen($phone) >= 9 ? $phone : null;
    }

    private function firstName(UniversityApplication $application): string
    {
        return trim((string) ($application->student->first_name ?? '')) ?: 'Kakak';
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
