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

            // --- Academic (sebelumnya "Akademik" -- penamaan ulang 10 September 2026) ---
            ['code' => 'formulir', 'label' => 'Formulir', 'group_label' => 'Academic', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 60],
            ['code' => 'study_plan', 'label' => 'Study Plan', 'group_label' => 'Academic', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 70],
            // Label diganti dari "Transcript Nilai" -> "Transcript (Report Card)" (10 September 2026).
            ['code' => 'transcript', 'label' => 'Transcript (Report Card)', 'group_label' => 'Academic', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 80],
            ['code' => 'graduation_letter_expected', 'label' => 'Graduation Letter Expected', 'group_label' => 'Academic', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 90],
            // Label diganti dari "Ijazah Translate" -> "Graduation Certificate Translate" (10 September 2026).
            ['code' => 'ijazah_translate', 'label' => 'Graduation Certificate Translate', 'group_label' => 'Academic', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 100],

            // --- Certificate (sebelumnya "Sertifikat Bahasa" -- penamaan ulang
            // 10 September 2026. CSCA yang tadinya di grup "Kesehatan & Legal"
            // juga dipindah masuk ke grup ini, cukup dengan ganti group_label-nya
            // saja -- 'code'-nya tetap "csca", tidak disentuh) ---
            ['code' => 'certificate_hsk', 'label' => 'Certificate HSK', 'group_label' => 'Certificate', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 110],
            ['code' => 'certificate_ielts_toefl_duolingo', 'label' => 'Certificate IELTS / TOEFL / Duolingo', 'group_label' => 'Certificate', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 120],
            ['code' => 'csca', 'label' => 'CSCA', 'group_label' => 'Certificate', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 130],

            // --- Kesehatan & Legal ---
            // Label diganti dari "Medical Checkup" -> "Medical Certificate" dan
            // "SKCK" -> "Non Criminal Certificate" (10 September 2026). Nama
            // grup "Kesehatan & Legal" sendiri belum diminta diganti, jadi
            // dibiarkan seperti semula.
            //
            // FIX (10 September 2026): fitur "Download Template" -- Medical
            // Certificate dikasih file template kosong (dari user) yang bisa
            // didownload siswa dulu sebelum diisi & diupload ulang lewat
            // kolom upload yang sama. File-nya ditaruh di
            // public/document-templates/medical_certificate.pdf.
            ['code' => 'medical_checkup', 'label' => 'Medical Certificate', 'group_label' => 'Kesehatan & Legal', 'allowed_extensions' => 'pdf,jpg,jpeg', 'template_file_path' => 'document-templates/medical_certificate.pdf', 'is_required' => false, 'sort_order' => 140],
            ['code' => 'skck', 'label' => 'Non Criminal Certificate', 'group_label' => 'Kesehatan & Legal', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 150],

            // --- Supporting Letter (sebelumnya "Surat Pendukung" -- penamaan
            // ulang 10 September 2026) ---
            ['code' => 'recommendation_letter', 'label' => 'Recommendation Letter', 'group_label' => 'Supporting Letter', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 160],
            ['code' => 'guardian_letter', 'label' => 'Guardian Letter', 'group_label' => 'Supporting Letter', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 170],
            ['code' => 'any_certificate', 'label' => 'Any Certificate', 'group_label' => 'Supporting Letter', 'allowed_extensions' => 'pdf,jpg,jpeg', 'is_required' => false, 'sort_order' => 180],

            // --- Documents from InaStudy (FASE 4, 10 September 2026) ---
            // provided_by = 'admin' -- KEBALIKAN dari semua jenis dokumen di
            // atas (default provided_by = 'student' dari kolomnya, tidak
            // perlu disebut eksplisit). 2 baris ini TIDAK PERNAH muncul di
            // halaman upload siswa (lihat DocumentType::scopeStudentUpload()
            // & ApplicationDocumentController::edit()/update()) -- siswa
            // hanya bisa MELIHAT/DOWNLOAD dari halaman ringkasan aplikasi
            // (student-portal.applications.show), tidak pernah upload sendiri.
            // Admin yang upload lewat Quiz\UniversityApplicationController::
            // uploadDocument() (method generik yang sudah ada, tidak perlu
            // diubah). 'code' SENGAJA beda dari 'passport' (Identitas & Foto)
            // di atas -- itu foto KTP/paspor siswa sendiri, ini paspor FISIK
            // yang sudah diproses/terbit dari InaStudy, dokumen yang berbeda.
            ['code' => 'admin_offer_letter', 'label' => 'Offer Letter', 'group_label' => 'Documents from InaStudy', 'allowed_extensions' => 'pdf,jpg,jpeg', 'provided_by' => DocumentType::PROVIDED_BY_ADMIN, 'is_required' => false, 'sort_order' => 200],
            ['code' => 'admin_passport', 'label' => 'Passport (Processed)', 'group_label' => 'Documents from InaStudy', 'allowed_extensions' => 'pdf,jpg,jpeg', 'provided_by' => DocumentType::PROVIDED_BY_ADMIN, 'is_required' => false, 'sort_order' => 210],
        ];

        foreach ($items as $item) {
            DocumentType::updateOrCreate(
                ['code' => $item['code']],
                array_merge($item, ['status' => DocumentType::STATUS_ACTIVE])
            );
        }
    }
}
