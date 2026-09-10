<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/**
 * Seed daftar jenis dokumen Aplikasi Kuliah sesuai daftar yang diminta
 * user (lihat diskusi fitur Apply Kampus, 8 September 2026). Passport &
 * Pass Photo dibatasi JPG saja, sisanya PDF/JPG.
 *
 * Aman dijalankan berulang -- pakai updateOrCreate berdasarkan 'code',
 * jadi tidak bikin baris dobel kalau seeder ini dijalankan lagi.
 */
class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // --- Payment (sebelumnya "Keuangan" -- penamaan ulang 10 September
            // 2026, cuma ganti label/group_label, 'code' tidak disentuh sama
            // sekali supaya data dokumen yang sudah pernah diupload student
            // tetap terhubung dengan benar) ---
            ['code' => 'registration_fee_payment', 'label' => 'Payment Registration Fee', 'group_label' => 'Payment', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 10],
            ['code' => 'deposit_fee_china', 'label' => 'Deposit Fee in China', 'group_label' => 'Payment', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 20],
            ['code' => 'bank_statement', 'label' => 'Bank Statement', 'group_label' => 'Payment', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 30],

            // --- Identitas & Foto (JPG saja) ---
            ['code' => 'passport', 'label' => 'Passport', 'group_label' => 'Identitas & Foto', 'allowed_extensions' => 'jpg,jpeg', 'is_required' => false, 'sort_order' => 40],
            ['code' => 'pass_photo', 'label' => 'Pass Photo', 'group_label' => 'Identitas & Foto', 'allowed_extensions' => 'jpg,jpeg', 'is_required' => false, 'sort_order' => 50],

            // --- Akademik ---
            ['code' => 'formulir', 'label' => 'Formulir', 'group_label' => 'Akademik', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 60],
            ['code' => 'study_plan', 'label' => 'Study Plan', 'group_label' => 'Akademik', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 70],
            // Label diganti dari "Transcript Nilai" -> "Transcript (Report Card)" (10 September 2026).
            ['code' => 'transcript', 'label' => 'Transcript (Report Card)', 'group_label' => 'Akademik', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 80],
            ['code' => 'graduation_letter_expected', 'label' => 'Graduation Letter Expected', 'group_label' => 'Akademik', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 90],
            // Label diganti dari "Ijazah Translate" -> "Graduation Certificate Translate" (10 September 2026).
            ['code' => 'ijazah_translate', 'label' => 'Graduation Certificate Translate', 'group_label' => 'Akademik', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 100],

            // --- Certificate (sebelumnya "Sertifikat Bahasa" -- penamaan ulang
            // 10 September 2026. CSCA yang tadinya di grup "Kesehatan & Legal"
            // juga dipindah masuk ke grup ini, cukup dengan ganti group_label-nya
            // saja -- 'code'-nya tetap "csca", tidak disentuh) ---
            ['code' => 'certificate_hsk', 'label' => 'Certificate HSK', 'group_label' => 'Certificate', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 110],
            ['code' => 'certificate_ielts_toefl_duolingo', 'label' => 'Certificate IELTS / TOEFL / Duolingo', 'group_label' => 'Certificate', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 120],
            ['code' => 'csca', 'label' => 'CSCA', 'group_label' => 'Certificate', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 130],

            // --- Kesehatan & Legal ---
            // CATATAN: label "Medical Checkup" & "SKCK" dan nama grup ini sendiri
            // BELUM diganti -- ditunda dulu per permintaan user (10 September
            // 2026), menyusul setelah ini.
            ['code' => 'medical_checkup', 'label' => 'Medical Checkup', 'group_label' => 'Kesehatan & Legal', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 140],
            ['code' => 'skck', 'label' => 'SKCK', 'group_label' => 'Kesehatan & Legal', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 150],

            // --- Supporting Letter (sebelumnya "Surat Pendukung" -- penamaan
            // ulang 10 September 2026) ---
            ['code' => 'recommendation_letter', 'label' => 'Recommendation Letter', 'group_label' => 'Supporting Letter', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 160],
            ['code' => 'guardian_letter', 'label' => 'Guardian Letter', 'group_label' => 'Supporting Letter', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 170],
            ['code' => 'any_certificate', 'label' => 'Any Certificate', 'group_label' => 'Supporting Letter', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 180],
        ];

        foreach ($items as $item) {
            DocumentType::updateOrCreate(
                ['code' => $item['code']],
                array_merge($item, ['status' => DocumentType::STATUS_ACTIVE])
            );
        }
    }
}
