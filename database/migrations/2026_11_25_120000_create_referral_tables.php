<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kode Referal INA YULE (25 November 2026).
 *
 * - users.referral_code      : kode unik tiap akun (indeks UNIQUE, tidak mungkin ganda). Semua akun lama
 *                              langsung dibuatkan kodenya di sini; akun baru dibuatkan saat pertama dibutuhkan.
 * - referral_settings        : 1 baris pengaturan admin (on/off, % diskon pembeli, % komisi pemberi kode).
 * - referral_redemptions     : 1 pemakaian kode oleh 1 pembeli baru. Komisi pemberi kode dicatat di sini
 *                              setelah pembayaran lunas, dan masuk ke Saldo (deposits) yang tidak bisa dicairkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'referral_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('referral_code', 20)->nullable()->unique();
            });
        }

        // Semua akun lama langsung punya kode (unik dijaga indeks + pengecekan).
        DB::table('users')->whereNull('referral_code')->select(['id', 'name'])->chunkById(200, function ($users) {
            foreach ($users as $user) {
                $code = User::newReferralCode((string) $user->name);
                DB::table('users')->where('id', $user->id)->update(['referral_code' => $code]);
            }
        });

        if (! Schema::hasTable('referral_settings')) {
            Schema::create('referral_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('is_active')->default(false);
                $table->decimal('buyer_discount_percent', 5, 2)->default(10);
                $table->decimal('referrer_commission_percent', 5, 2)->default(10);
                $table->char('updated_by', 36)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('referral_redemptions')) {
            Schema::create('referral_redemptions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 20);
                $table->char('referrer_user_id', 36)->index();
                $table->char('buyer_user_id', 36)->index();
                $table->char('buyer_student_id', 36)->nullable();
                $table->char('course_package_id', 36)->nullable();
                $table->char('course_package_payment_id', 36)->nullable()->index();
                $table->decimal('price_before', 14, 2);          // harga setelah voucher, sebelum diskon referal
                $table->decimal('buyer_discount_percent', 5, 2);
                $table->decimal('discount_amount', 14, 2);
                $table->decimal('price_after', 14, 2);            // yang benar-benar dibayar pembeli
                $table->decimal('referrer_commission_percent', 5, 2);
                $table->decimal('commission_amount', 14, 2)->nullable(); // diisi saat lunas
                $table->char('commission_deposit_id', 36)->nullable();
                $table->timestamp('commission_paid_at')->nullable();
                $table->timestamps();
            });
        }

        // Menu Course > Kode Referal (pola sama dengan migration voucher).
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();
        $permission = Permission::where('key', 'course.referral')->first();

        if ($superadmin && $permission) {
            $superadmin->permissions()->syncWithoutDetaching([
                $permission->id => ['can_edit' => true],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_redemptions');
        Schema::dropIfExists('referral_settings');

        if (Schema::hasColumn('users', 'referral_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['referral_code']);
                $table->dropColumn('referral_code');
            });
        }
    }
};
