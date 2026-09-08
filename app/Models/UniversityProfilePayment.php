<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UniversityProfilePayment extends Model
{
    use HasUuids;

    protected $table = 'university_profile_payments';

    protected $keyType = 'string';

    public $incrementing = false;

    // Nilai fee_type yang dikenali fitur Apply Kampus untuk auto-deteksi
    // Registration Fee (lihat migration add_fee_type_to_university_profile_payments_table).
    public const FEE_TYPE_REGISTRATION = 'registration_fee';
    public const FEE_TYPE_TUITION = 'tuition_fee';
    public const FEE_TYPE_DORMITORY = 'dormitory_fee';
    public const FEE_TYPE_DEPOSIT_CHINA = 'deposit_china';
    public const FEE_TYPE_OTHER = 'other';

    protected $fillable = [
        'user_id',
        'university_profile_id',
        'location',
        'name',
        'amount',
        'fee_type',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    /**
     * Relasi ke UniversityProfile induknya.
     */
    public function profile()
    {
        return $this->belongsTo(UniversityProfile::class, 'university_profile_id');
    }

    /**
     * Relasi ke User (creator baris ini).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
