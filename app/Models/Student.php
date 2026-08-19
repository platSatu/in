<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'sales_id',
        'branch_id',
        'form_id',
        'images',
        'first_name',
        'last_name',
        'email',
        'handphone',
        'status',
    ];

    /**
     * Akun login (User) milik student ini, kalau sudah dibuat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Branch terakhir yang diisi student ini (lihat catatan di migration
     * add_branch_and_form_to_students_table).
     */
    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    /**
     * Form terakhir yang diisi student ini.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    /**
     * Seluruh riwayat pengisian quiz student ini. Di alur wizard publik
     * (FrontendController::formWizardSubmit), FormSubmission.user_id diisi
     * dengan id student yang submit — jadi relasi ini yang jadi sumber "history
     * lengkap", terlepas dari branch_id/form_id di atas yang cuma nyimpan
     * singgahan terakhir.
     */
    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'user_id');
    }
}