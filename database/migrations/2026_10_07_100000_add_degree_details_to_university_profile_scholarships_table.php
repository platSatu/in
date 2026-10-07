<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Scholarship (add row di University Profile), 7 Oktober 2026:
 * kolom form "Price" diganti "Degree" (pilihan sama dengan
 * UniversityProfileDegree::DEGREES) dan "Currency" diganti "Details" (textarea).
 *
 * Kolom lama price & currency SENGAJA tidak dihapus dulu supaya data lama
 * tidak hilang. Nilai lamanya juga disalin ke Details (mis. "Rp 5.000.000")
 * supaya langsung terlihat di form & halaman publik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_profile_scholarships', function (Blueprint $table) {
            $table->string('degree', 50)->nullable()->after('name');
            $table->text('details')->nullable()->after('degree');
        });

        DB::table('university_profile_scholarships')
            ->whereNotNull('price')
            ->whereNull('details')
            ->orderBy('id')
            ->each(function ($row) {
                $symbol = match ($row->currency) {
                    'rupiah' => 'Rp ',
                    'yuan' => '¥ ',
                    default => '',
                };

                DB::table('university_profile_scholarships')
                    ->where('id', $row->id)
                    ->update(['details' => $symbol.number_format((int) $row->price, 0, ',', '.')]);
            });
    }

    public function down(): void
    {
        Schema::table('university_profile_scholarships', function (Blueprint $table) {
            $table->dropColumn(['degree', 'details']);
        });
    }
};
