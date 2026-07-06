<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PembayaranFormLink extends Model
{
    use HasUuids;

    protected $table = 'pembayaran_form_links';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'parent_id',
        'pembayaran_form_id',
        'status',
        'payment_status',
        'payment_method',
        'payment_date',
        'order_id',
    ];

    /**
     * Relasi ke user pemilik link
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke parent (bisa admin/agency/referrer/user lain)
     */
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Relasi ke form pembayaran
     */
    public function pembayaranForm()
    {
        return $this->belongsTo(PembayaranForm::class, 'pembayaran_form_id');
    }
}