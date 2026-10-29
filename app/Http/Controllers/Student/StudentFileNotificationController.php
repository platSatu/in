<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DocumentNotificationSetting;
use App\Services\InaStudy\DocumentNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Tab "Notifikasi" di Students > File Student (29 Oktober 2026): pengaturan
 * info upload ke admin, pengingat dokumen ke student, dan pesan dokumen lengkap.
 * Pengiriman pesannya ada di App\Services\InaStudy\DocumentNotifier.
 */
class StudentFileNotificationController extends Controller
{
    public function edit(Request $request): View
    {
        return view('student.files.notifications', [
            'setting' => DocumentNotificationSetting::current()->load('updatedBy'),
            'hourOptions' => DocumentNotificationSetting::REMINDER_HOURS,
            'maxDay' => DocumentNotificationSetting::MAX_REMINDER_DAY,
            'canEdit' => (bool) $request->user()?->canAccessPermission('student.files', 'edit'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Nomor dirapikan dulu: hanya angka, 08xx / +62xx jadi 62xx; baris kosong dibuang.
        $request->merge([
            'admin_whatsapp_numbers' => collect($request->input('admin_whatsapp_numbers', []))
                ->map(fn ($n) => $this->normalizePhone((string) $n))->filter()->unique()->values()->all(),
            'admin_emails' => collect($request->input('admin_emails', []))
                ->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique()->values()->all(),
            'reminder_days' => collect($request->input('reminder_days', []))
                ->filter(fn ($d) => trim((string) $d) !== '')->values()->all(),
        ]);

        $data = $request->validate([
            'admin_whatsapp_enabled' => ['boolean'],
            'admin_whatsapp_numbers' => ['array', 'max:10', Rule::requiredIf($request->boolean('admin_whatsapp_enabled'))],
            'admin_whatsapp_numbers.*' => ['regex:/^62\d{8,13}$/'],
            'admin_email_enabled' => ['boolean'],
            'admin_emails' => ['array', 'max:10', Rule::requiredIf($request->boolean('admin_email_enabled'))],
            'admin_emails.*' => ['email:rfc', 'max:150'],
            'reminder_enabled' => ['boolean'],
            'reminder_whatsapp' => ['boolean'],
            'reminder_email' => ['boolean'],
            'reminder_days' => ['array', 'max:10', Rule::requiredIf($request->boolean('reminder_enabled'))],
            'reminder_days.*' => ['integer', 'min:1', 'max:'.DocumentNotificationSetting::MAX_REMINDER_DAY],
            'reminder_hour' => ['required', 'integer', Rule::in(DocumentNotificationSetting::REMINDER_HOURS)],
            'complete_notify_student' => ['boolean'],
            'complete_notify_admin' => ['boolean'],
        ], [
            'admin_whatsapp_numbers.required' => 'Isi minimal 1 nomor WhatsApp admin, atau matikan saklarnya.',
            'admin_whatsapp_numbers.*.regex' => 'Nomor WhatsApp admin tidak valid. Contoh: 081234567890.',
            'admin_emails.required' => 'Isi minimal 1 email admin, atau matikan saklarnya.',
            'admin_emails.*.email' => 'Format email admin tidak valid.',
            'reminder_days.required' => 'Isi minimal 1 jadwal pengingat, atau matikan pengingatnya.',
            'reminder_days.max' => 'Maksimal 10 jadwal pengingat.',
            'reminder_days.*.integer' => 'Jadwal pengingat harus berupa angka hari, mis. 1, 5, 7.',
            'reminder_days.*.min' => 'Jadwal pengingat paling cepat H+1.',
            'reminder_days.*.max' => 'Jadwal pengingat paling lama H+'.DocumentNotificationSetting::MAX_REMINDER_DAY.'.',
        ]);

        if ($request->boolean('reminder_enabled') && ! $request->boolean('reminder_whatsapp') && ! $request->boolean('reminder_email')) {
            return back()->withInput()->withErrors(['reminder_whatsapp' => 'Pilih minimal 1 kanal pengingat (WhatsApp atau Email).']);
        }

        $days = collect($data['reminder_days'] ?? [])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();

        DocumentNotificationSetting::current()->update([
            'admin_whatsapp_enabled' => $request->boolean('admin_whatsapp_enabled'),
            'admin_whatsapp_numbers' => $data['admin_whatsapp_numbers'] ?? [],
            'admin_email_enabled' => $request->boolean('admin_email_enabled'),
            'admin_emails' => $data['admin_emails'] ?? [],
            'reminder_enabled' => $request->boolean('reminder_enabled'),
            'reminder_whatsapp' => $request->boolean('reminder_whatsapp'),
            'reminder_email' => $request->boolean('reminder_email'),
            'reminder_days' => $days,
            'reminder_hour' => (int) $data['reminder_hour'],
            'complete_notify_student' => $request->boolean('complete_notify_student'),
            'complete_notify_admin' => $request->boolean('complete_notify_admin'),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('student.files.notifications')->with('success', 'Pengaturan notifikasi disimpan.');
    }

    /** Tombol "Kirim Tes ke Admin": pesan contoh ke nomor & email admin yang sudah disimpan. */
    public function test(DocumentNotifier $notifier): RedirectResponse
    {
        $setting = DocumentNotificationSetting::current();

        if ($setting->adminWhatsappTargets() === [] && $setting->adminEmailTargets() === []) {
            return back()->with('error', 'Aktifkan & simpan dulu nomor WhatsApp atau email admin di card pertama.');
        }

        $result = $notifier->sendAdminTest();
        $message = "Pesan tes: {$result['sent']} terkirim".($result['failed'] ? ", {$result['failed']} gagal (cek pengaturan WhatsApp Gateway / email server)." : '. Silakan cek WhatsApp & email admin.');

        return back()->with($result['failed'] ? 'error' : 'success', $message);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return match (true) {
            $digits === '' => '',
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => $digits,
        };
    }
}
