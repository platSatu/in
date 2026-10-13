<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dokumen yang diupload INA untuk student (13 Oktober 2026): Offer Letter,
 * JW, VISA -- provided_by 'admin', tampil di kartu "Documents from InaStudy"
 * dashboard aplikasi student. Ditambahkan lewat migration (bukan hanya
 * seeder) supaya pasti ada di production. Hanya menambah yang belum ada
 * (berdasarkan code), data yang sudah ada tidak diubah.
 */
return new class extends Migration
{
    private const TYPES = [
        ['code' => 'admin_offer_letter', 'label' => 'Offer Letter', 'sort_order' => 200],
        ['code' => 'admin_jw', 'label' => 'JW', 'sort_order' => 220],
        ['code' => 'admin_visa', 'label' => 'VISA', 'sort_order' => 230],
    ];

    public function up(): void
    {
        foreach (self::TYPES as $type) {
            if (DB::table('document_types')->where('code', $type['code'])->exists()) {
                continue;
            }

            DB::table('document_types')->insert($type + [
                'id' => (string) Str::uuid(),
                'group_label' => 'Documents from InaStudy',
                'allowed_extensions' => 'pdf,jpg,jpeg',
                'provided_by' => 'admin',
                'is_required' => false,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Hanya JW & VISA yang dihapus, dan hanya kalau belum ada dokumen yang diupload.
        // Offer Letter juga dipakai DocumentTypeSeeder, jadi tidak disentuh.
        $ids = DB::table('document_types')->whereIn('code', ['admin_jw', 'admin_visa'])->pluck('id');

        if (! DB::table('application_documents')->whereIn('document_type_id', $ids)->exists()) {
            DB::table('document_types')->whereIn('id', $ids)->delete();
        }
    }
};
