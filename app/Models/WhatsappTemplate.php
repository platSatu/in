<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    use HasUuids;

    protected $table = 'whatsapp_templates';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'description',
        'status',
        'is_course_package_purchase_template',
    ];

    protected $casts = [
        'is_course_package_purchase_template' => 'boolean',
    ];

    /**
     * Admin yang membuat/memiliki template ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Form-form yang memakai template ini.
     * Aktifkan setelah kolom whatsapp_template_id ditambahkan ke tabel forms.
     */
    public function forms()
    {
        return $this->hasMany(Form::class, 'whatsapp_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Template yang ditandai admin (lewat menu Quiz > WhatsApp Template,
     * tombol "Aktifkan utk Notifikasi Package") untuk dipakai saat kirim
     * notifikasi WA "pembelian package berhasil" -- lihat
     * App\Http\Controllers\Quiz\WhatsappTemplateController::activateForCoursePackagePurchase()
     * & App\Http\Controllers\StudentPortal\InaYulePackageWebhookController.
     * Cuma 1 baris yang boleh true di satu waktu (deactivateOthers() saat
     * mengaktifkan yang baru), pola SAMA PERSIS dengan PaymentGateway::is_active.
     */
    public function scopeForCoursePackagePurchase($query)
    {
        return $query->where('is_course_package_purchase_template', true);
    }
}