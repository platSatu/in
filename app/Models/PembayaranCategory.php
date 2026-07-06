<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PembayaranCategory extends Model
{
    use HasUuids;

    protected $table = 'pembayaran_categories';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'status',
    ];

    /**
     * Relasi ke user pembuat kategori
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}