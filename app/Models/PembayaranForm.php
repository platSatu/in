<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PembayaranForm extends Model
{
    use HasUuids;

    protected $table = 'pembayaran_forms';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'pembayaran_category_id',
        'name',
        'amount',
        'due_date',
        'description',
        'status',
    ];

    /**
     * Relasi ke user pembuat
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke kategori pembayaran
     */
    public function category()
    {
        return $this->belongsTo(PembayaranCategory::class, 'pembayaran_category_id');
    }
}