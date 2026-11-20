<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voucher diskon pembelian paket credit InaYule (20 November 2026), menu Course > Voucher Paket.
 * SENGAJA terpisah dari tabel `vouchers` lama (sistem paket/deposit lama) supaya data lama tidak tersentuh.
 *
 * course_voucher_redemptions: 1 baris tiap kali kode dipakai saat checkout. Kuota & aturan "1 akun 1x per kode"
 * dihitung dari baris yang masih berlaku (pembayaran lunas, atau masih menunggu bayar & belum kedaluwarsa),
 * jadi pembayaran yang gagal/kedaluwarsa otomatis mengembalikan kuota tanpa perlu diubah manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_vouchers')) {
            Schema::create('course_vouchers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->string('discount_type', 20); // percent | nominal
                $table->decimal('discount_value', 14, 2);
                $table->unsignedInteger('quota');
                $table->dateTime('valid_from');
                $table->dateTime('valid_until');
                $table->boolean('only_eligible_students')->default(true);
                $table->json('course_package_ids')->nullable(); // null = semua paket
                $table->string('status', 20)->default('active'); // active | inactive
                $table->char('created_by', 36)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('course_voucher_redemptions')) {
            Schema::create('course_voucher_redemptions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('course_voucher_id')->constrained('course_vouchers')->cascadeOnDelete();
                $table->char('user_id', 36);
                $table->char('student_id', 36)->nullable();
                $table->char('course_package_id', 36)->nullable();
                $table->char('course_package_payment_id', 36)->nullable();
                $table->decimal('price_before', 14, 2);
                $table->decimal('discount_amount', 14, 2);
                $table->decimal('price_after', 14, 2);
                $table->timestamps();

                $table->index(['course_voucher_id', 'user_id'], 'cvr_voucher_user_idx');
                $table->index('course_package_payment_id', 'cvr_payment_idx');
            });
        }

        // Permission menu baru (pola sama dengan migration sync_permissions_and_grant_superadmin_*).
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();
        $permission = Permission::where('key', 'course.voucher')->first();

        if ($superadmin && $permission) {
            $superadmin->permissions()->syncWithoutDetaching([
                $permission->id => ['can_edit' => true],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_voucher_redemptions');
        Schema::dropIfExists('course_vouchers');
        // Permission sengaja tidak dihapus, sama dengan migration sync permission sebelumnya.
    }
};
