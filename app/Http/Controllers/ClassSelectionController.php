<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\ClassSchedule;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

/**
 * Halaman PUBLIK (tanpa login) "Pilih Kelas" — dibuka dari link yang
 * disisipkan otomatis ke pesan WhatsApp hasil placement test (lihat
 * ClassSchedule::existsActiveForBranch(), dipakai di
 * FrontendController::finalizeCompletedSubmission() untuk result_mode='auto'
 * dan FormController::saveResult() untuk result_mode='manual').
 *
 * Token akses = UUID FormSubmission itu sendiri (pola yang sama seperti
 * order_id di alur pembayaran, lihat App\Http\Controllers\Payment\FormPaymentController)
 * — cukup sulit ditebak orang lain, tidak perlu kolom token terpisah.
 *
 * Dipisah dari FrontendController (yang sudah sangat besar) supaya berkas
 * tetap ramping, sama alasannya dengan kenapa fitur pembayaran juga punya
 * controller sendiri.
 */
class ClassSelectionController extends Controller
{
    public function show(string $submissionId): View
    {
        $submission = FormSubmission::with(['student', 'form.companyBranch'])->findOrFail($submissionId);

        $existingEnrollment = ClassEnrollment::where('form_submission_id', $submission->id)
            ->where('status', 'active')
            ->with('classSchedule')
            ->first();

        $branchId = $submission->form->branch_id ?? null;

        $schedules = $branchId
            ? ClassSchedule::where('branch_id', $branchId)
                ->where('status', 'active')
                ->withCount('activeEnrollments')
                ->orderBy('class_date')
                ->orderBy('start_time')
                ->get()
            : collect();

        return view('frontend.class-selection.show', compact('submission', 'schedules', 'existingEnrollment'));
    }

    public function store(Request $request, string $submissionId): RedirectResponse
    {
        $submission = FormSubmission::with(['student', 'form'])->findOrFail($submissionId);

        $validated = $request->validate([
            'class_schedule_id' => 'required|string|exists:class_schedules,id',
        ]);

        if (!$submission->student) {
            abort(422, 'Data student untuk submission ini tidak ditemukan.');
        }

        // Sudah pernah pilih kelas sebelumnya (mis. link WA dibuka/di-submit dua
        // kali) -> jangan diproses lagi, cukup arahkan balik ke halaman show
        // (yang otomatis menampilkan kelas yang sudah dipilih).
        $alreadyEnrolled = ClassEnrollment::where('form_submission_id', $submission->id)
            ->where('status', 'active')
            ->exists();

        if ($alreadyEnrolled) {
            return redirect()
                ->route('frontend.class-selection.show', $submission->id)
                ->with('info', 'Anda sudah terdaftar di salah satu kelas sebelumnya.');
        }

        $branchId = $submission->form->branch_id ?? null;

        try {
            $enrollment = DB::transaction(function () use ($validated, $submission, $branchId) {
                // Lock baris jadwalnya dulu supaya dua request yang masuk nyaris
                // bersamaan (mis. klik ganda / dua tab) tidak sama-sama lolos
                // pengecekan kuota di bawah sebelum salah satunya benar-benar
                // tersimpan — pola yang sama dengan FormController::saveResult().
                $schedule = ClassSchedule::where('id', $validated['class_schedule_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$schedule || $schedule->status !== 'active' || (string) $schedule->branch_id !== (string) $branchId) {
                    throw new RuntimeException('CLASS_INVALID');
                }

                $activeCount = ClassEnrollment::where('class_schedule_id', $schedule->id)
                    ->where('status', 'active')
                    ->count();

                if ($activeCount >= $schedule->capacity) {
                    throw new RuntimeException('CLASS_FULL');
                }

                $created = ClassEnrollment::create([
                    'class_schedule_id' => $schedule->id,
                    'student_id' => $submission->student->id,
                    'form_submission_id' => $submission->id,
                    'status' => 'active',
                ]);

                $created->setRelation('classSchedule', $schedule);

                return $created;
            });
        } catch (RuntimeException $e) {
            $message = $e->getMessage() === 'CLASS_FULL'
                ? 'Maaf, kuota kelas ini baru saja penuh. Silakan pilih kelas lain.'
                : 'Kelas yang dipilih tidak valid untuk submission ini.';

            return redirect()
                ->route('frontend.class-selection.show', $submission->id)
                ->withErrors(['class_schedule_id' => $message]);
        } catch (QueryException $e) {
            // Unique constraint form_submission_id -> submission ini ternyata
            // sudah punya enrollment (race: dua request submit nyaris
            // bersamaan lolos pengecekan $alreadyEnrolled di atas berbarengan).
            return redirect()
                ->route('frontend.class-selection.show', $submission->id)
                ->with('info', 'Anda sudah terdaftar di salah satu kelas sebelumnya.');
        }

        $this->sendEnrollmentConfirmation($submission, $enrollment);

        return redirect()
            ->route('frontend.class-selection.show', $submission->id)
            ->with('success', 'Berhasil terdaftar di kelas pilihan Anda!');
    }

    private function sendEnrollmentConfirmation(FormSubmission $submission, ClassEnrollment $enrollment): void
    {
        $student = $submission->student;
        if (!$student || empty($student->handphone)) {
            return;
        }

        $schedule = $enrollment->classSchedule;
        if (!$schedule) {
            return;
        }

        $tanggal = optional($schedule->class_date)->format('d/m/Y');
        $jam = $schedule->start_time ? substr($schedule->start_time, 0, 5) : '-';

        $message = 'Halo ' . trim($student->first_name . ' ' . $student->last_name) . ",\n\n";
        $message .= "Anda berhasil terdaftar di kelas berikut:\n";
        $message .= '📚 *' . $schedule->name . '*' . ($schedule->level ? " ({$schedule->level})" : '') . "\n";
        $message .= "🗓️ {$tanggal}, pukul {$jam}\n\n";
        $message .= 'Sampai jumpa di kelas! 😊';

        $form = $submission->form ?? Form::find($submission->form_id);

        try {
            (new WhatsappMessenger())->send($student->handphone, $message, $form->user_id ?? null);
        } catch (\Throwable $e) {
            // Sama seperti pengiriman WA hasil placement test: kegagalan kirim
            // WA di sini TIDAK boleh membatalkan pendaftaran kelas yang sudah
            // tersimpan — cukup dicatat di log.
            Log::error('[CLASS-SELECTION] Gagal kirim WA konfirmasi kelas', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
