<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Inti fitur "Apply ke kampus": satu baris = satu calon siswa (Student)
| apply ke satu Major (university_profile_id) di satu kampus. Kolom
| degree/intake/duration/registration_fee_amount SENGAJA di-snapshot di
| sini (disalin nilainya saat submit), BUKAN selalu dibaca ulang dari
| university_profile_degrees/payments -- supaya kalau kampus mengubah
| pilihan/biayanya di kemudian hari, aplikasi yang sudah submit sebelumnya
| tidak ikut berubah datanya.
|
| Tabel BARU, tidak menyentuh tabel/fitur lain yang sudah ada.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('application_no')->unique(); // format: APP-2609-00001

            $table->char('student_id', 36);
            $table->char('university_profile_id', 36); // Major yang di-apply
            $table->char('university_id', 36); // denormalized dari profile, memudahkan filter laporan per kampus

            // Snapshot pilihan saat submit (lihat catatan di atas)
            $table->string('degree')->nullable();
            $table->string('intake')->nullable();
            $table->string('duration')->nullable();
            $table->string('whatsapp')->nullable();

            $table->unsignedBigInteger('registration_fee_amount')->nullable();
            $table->date('registration_fee_paid_at')->nullable();

            $table->unsignedBigInteger('deposit_fee_china_amount')->nullable();

            // Status ringkas dipakai buat stepper read-only di dashboard siswa
            // & filter laporan superadmin: submitted, documents_review,
            // registered, visa_process, check_in, completed, cancelled.
            $table->string('status')->default('submitted');

            $table->char('handled_by_user_id', 36)->nullable(); // staff yang pegang aplikasi ini

            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->index('student_id');
            $table->index('university_profile_id');
            $table->index(['university_id', 'status']);
            $table->index('handled_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_applications');
    }
};
