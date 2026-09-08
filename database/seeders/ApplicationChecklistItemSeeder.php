<?php

namespace Database\Seeders;

use App\Models\ApplicationChecklistItem;
use Illuminate\Database\Seeder;

/**
 * Seed daftar item checklist proses Aplikasi Kuliah, 3 section sesuai
 * yang diminta user (lihat diskusi fitur Apply Kampus, 8 September 2026):
 * 'main' (Status Students), 'visa' (proses VISA), 'checkin' (Students
 * Check-in). requires_note/requires_photo dipasang di item yang memang
 * disebutkan butuh catatan/foto (mis. "take pic", "noted details").
 *
 * Aman dijalankan berulang -- pakai updateOrCreate berdasarkan 'code'.
 */
class ApplicationChecklistItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // --- Section: Status Students (main) ---
            ['section' => 'main', 'code' => 'registration_paid', 'label' => 'Registration Paid', 'sort_order' => 10],
            ['section' => 'main', 'code' => 'registration_university', 'label' => 'Registration University', 'sort_order' => 20],
            ['section' => 'main', 'code' => 'interview_to_campus', 'label' => 'Interview to Campus', 'is_optional' => true, 'sort_order' => 30],
            ['section' => 'main', 'code' => 'pre_admission_delivery', 'label' => 'Pre Admission Delivery', 'sort_order' => 40],
            ['section' => 'main', 'code' => 'admission_notice_delivery', 'label' => 'Admission Notice Delivery', 'sort_order' => 50],
            ['section' => 'main', 'code' => 'departure_fee_paid', 'label' => 'Departure Fee Paid', 'sort_order' => 60],
            ['section' => 'main', 'code' => 'deposit_fee_to_campus', 'label' => 'Deposit Fee to Campus', 'is_optional' => true, 'sort_order' => 70],
            ['section' => 'main', 'code' => 'tuition_fee_paid', 'label' => 'Tuition Fee Paid', 'sort_order' => 80],
            ['section' => 'main', 'code' => 'dormitory_fee_paid', 'label' => 'Dormitory Fee Paid', 'sort_order' => 90],
            ['section' => 'main', 'code' => 'ina_brief', 'label' => 'INA BRIEF', 'requires_note' => true, 'sort_order' => 100],

            // --- Section: VISA ---
            ['section' => 'visa', 'code' => 'visa_documents_attached', 'label' => 'Documents Attached', 'requires_note' => true, 'sort_order' => 10],
            ['section' => 'visa', 'code' => 'visa_apply_online', 'label' => 'Apply Online', 'sort_order' => 20],
            ['section' => 'visa', 'code' => 'visa_received_from_center', 'label' => 'Received from Visa Center', 'sort_order' => 30],
            ['section' => 'visa', 'code' => 'visa_interview', 'label' => 'Interview', 'is_optional' => true, 'sort_order' => 40],
            ['section' => 'visa', 'code' => 'visa_passport_received', 'label' => 'Passport Received', 'requires_note' => true, 'sort_order' => 50],

            // --- Section: Students Check-in ---
            ['section' => 'checkin', 'code' => 'checkin_flight_ticket', 'label' => 'Flight Ticket', 'requires_note' => true, 'sort_order' => 10],
            ['section' => 'checkin', 'code' => 'checkin_local_guide_info', 'label' => 'Local Guide Information', 'requires_note' => true, 'sort_order' => 20],
            ['section' => 'checkin', 'code' => 'checkin_pickup_airport_train', 'label' => 'Pick up From Airport / Train', 'requires_photo' => true, 'sort_order' => 30],
            ['section' => 'checkin', 'code' => 'checkin_university_registration', 'label' => 'University Registration', 'requires_photo' => true, 'sort_order' => 40],
            ['section' => 'checkin', 'code' => 'checkin_dorm_checkin', 'label' => 'Dorm Check-in', 'requires_photo' => true, 'sort_order' => 50],
            ['section' => 'checkin', 'code' => 'checkin_sim_card', 'label' => 'Sim Card Arrangement', 'requires_photo' => true, 'sort_order' => 60],
            ['section' => 'checkin', 'code' => 'checkin_bank_arrangement', 'label' => 'Bank Arrangement', 'requires_photo' => true, 'sort_order' => 70],
            ['section' => 'checkin', 'code' => 'checkin_campus_tour', 'label' => 'Campus Tour', 'requires_photo' => true, 'sort_order' => 80],
            ['section' => 'checkin', 'code' => 'checkin_done', 'label' => 'DONE', 'sort_order' => 90],
            ['section' => 'checkin', 'code' => 'checkin_noted', 'label' => 'NOTED', 'requires_note' => true, 'is_optional' => true, 'sort_order' => 100],
        ];

        foreach ($items as $item) {
            ApplicationChecklistItem::updateOrCreate(
                ['code' => $item['code']],
                array_merge([
                    'requires_note' => false,
                    'requires_photo' => false,
                    'is_optional' => false,
                    'status' => ApplicationChecklistItem::STATUS_ACTIVE,
                ], $item)
            );
        }
    }
}
