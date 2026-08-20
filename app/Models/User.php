<?php

namespace App\Models;

use App\Concerns\HasScopedAccess;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasScopedAccess;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'handphone',
       // 'role',
       // 'parent_id',
       // 'application_id',
        'status',
       // 'saldo',
       'image',
    ];

    protected static function booted(): void
    {
        static::creating(function ($user) {
            $user->id = (string) Str::uuid();
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_user',
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()
            ->where('slug', $slug)
            ->where('roles.status', Role::STATUS_ACTIVE)
            ->exists();
    }

    public function divisions(): BelongsToMany
    {
        return $this->belongsToMany(
            CompanyDivision::class,
            'company_division_user',
            'user_id',
            'company_division_id'
        )->withPivot('id', 'status')->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
