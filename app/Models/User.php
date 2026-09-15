<?php

namespace App\Models;

use App\Concerns\HasScopedAccess;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
       'sales_code',
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

    /**
     * Data Student milik akun login ini, kalau ada (siswa yang daftar/login
     * lewat Portal Siswa) -- inverse dari Student::user(). Dipakai
     * resolveOwnBranchId() di bawah, TIDAK semua User punya ini (staff biasa
     * seperti sales/pengajar/superadmin tidak punya baris Student).
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    /**
     * Branch tempat user ini "berada", dicari lewat 2 jalur berbeda
     * tergantung jenis akunnya -- dipakai FIX (15 September 2026, permintaan
     * user) untuk filter Academic Calendar per branch di
     * DashboardController::index(), juga dipakai
     * Student\StudentController::ownBranchId() sebelumnya (logic yang sama,
     * sekarang dipusatkan di sini supaya tidak dobel):
     * 1. Staff (sales/pengajar/admin) -- lewat Company > Division > Add User
     *    (divisions(), pivot company_division_user), branch-nya ikut divisi.
     * 2. Siswa (login Portal Siswa) -- langsung dari Student::branch_id
     *    miliknya sendiri (siswa tidak pernah masuk company_division_user).
     * Null kalau user ini tidak match keduanya (mis. staff yang belum
     * ditempatkan ke divisi manapun, atau siswa yang belum pernah keisi
     * branch_id sama sekali).
     */
    public function resolveOwnBranchId(): ?string
    {
        $branchId = $this->divisions()->value('company_branch_id');

        if ($branchId) {
            return $branchId;
        }

        return $this->student?->branch_id;
    }
}
