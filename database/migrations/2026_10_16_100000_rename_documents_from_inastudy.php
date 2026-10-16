<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 16 Oktober 2026: ganti nama "Documents from InaStudy". Hanya label & urutan,
 * 'code' tidak disentuh supaya file yang sudah diupload admin tetap terhubung.
 * Passport (Processed) menjadi "Any Documents" dan dipindah ke urutan terakhir.
 */
return new class extends Migration
{
    private const LABELS = [
        'admin_offer_letter' => ['Admission Letter', 200, 'Offer Letter', 200],
        'admin_jw' => ['Visa Application JW202/JW201', 210, 'JW', 220],
        'admin_visa' => ['Visa X1/X2', 220, 'VISA', 230],
        'admin_passport' => ['Any Documents', 230, 'Passport (Processed)', 210],
    ];

    public function up(): void
    {
        foreach (self::LABELS as $code => [$label, $sort]) {
            DB::table('document_types')->where('code', $code)->update(['label' => $label, 'sort_order' => $sort, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (self::LABELS as $code => [, , $label, $sort]) {
            DB::table('document_types')->where('code', $code)->update(['label' => $label, 'sort_order' => $sort, 'updated_at' => now()]);
        }
    }
};
