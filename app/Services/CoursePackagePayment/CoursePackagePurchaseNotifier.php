<?php

namespace App\Services\CoursePackagePayment;

use App\Models\CourseCredit;
use App\Models\CoursePackagePayment;
use App\Models\WhatsappTemplate;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Kirim notifikasi WA "pembelian package berhasil" -- dipakai DUA tempat:
 * InaYulePackageCheckoutController (jalur instan, 100% tertutup saldo
 * Deposit) & InaYulePackageWebhookController (jalur gateway/campuran, setelah
 * DB transaction commit). SENGAJA dipisah jadi 1 class supaya logic
 * template+placeholder tidak dobel di 2 controller.
 *
 * Template yang dipakai adalah WhatsappTemplate yang ditandai admin lewat
 * WhatsappTemplate::is_course_package_purchase_template (menu Quiz >
 * WhatsApp Template, tombol "Aktifkan utk Notifikasi Package") -- BUKAN
 * App\Services\Whatsapp\WhatsappMessenger::buildMessageFromTemplate() yang
 * hard-coupled ke relasi Form::whatsappTemplate, jadi di sini placeholder
 * disubstitusi manual (pola sama, str_replace('{{key}}', ...)).
 *
 * PENTING: dipanggil SETELAH DB transaction pengkreditan commit, TIDAK
 * pernah di dalamnya -- kalau pengiriman WA gagal (gateway WA down dll),
 * itu TIDAK BOLEH membatalkan/rollback uang & credit yang sudah berhasil
 * diproses, cukup dicatat di log (lihat try/catch di notify()).
 */
class CoursePackagePurchaseNotifier
{
    public function notify(CoursePackagePayment $payment): void
    {
        try {
            $template = WhatsappTemplate::forCoursePackagePurchase()->active()->first();

            if (!$template) {
                Log::warning('[COURSE-PACKAGE] Tidak ada WhatsApp Template aktif yang ditandai utk notifikasi pembelian package -- notifikasi dilewati.', [
                    'order_id' => $payment->order_id,
                ]);

                return;
            }

            $payment->loadMissing(['student', 'coursePackage']);
            $student = $payment->student;
            $package = $payment->coursePackage;

            $phone = $student?->handphone;

            if (empty($phone)) {
                Log::warning('[COURSE-PACKAGE] Student tanpa nomor handphone, notifikasi WA pembelian package dilewati.', [
                    'order_id' => $payment->order_id,
                    'student_id' => $payment->student_id,
                ]);

                return;
            }

            $placeholders = [
                'name' => trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')),
                'package_name' => $package->name ?? '',
                'credits' => rtrim(rtrim(number_format((float) $payment->credits_granted, 2, '.', ''), '0'), '.'),
                'price' => 'Rp ' . number_format((float) $payment->price_total, 0, ',', '.'),
                'order_id' => $payment->order_id,
                'sisa_credit' => rtrim(rtrim(number_format(CourseCredit::currentBalanceFor($payment->student_id), 2, '.', ''), '0'), '.'),
            ];

            $content = (string) $template->content;
            foreach ($placeholders as $key => $value) {
                $content = str_replace('{{' . $key . '}}', (string) $value, $content);
            }

            (new WhatsappMessenger())->send($phone, $content, $student->user_id);
        } catch (Throwable $e) {
            Log::error('[COURSE-PACKAGE] Gagal kirim notifikasi WA pembelian package', [
                'order_id' => $payment->order_id ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
