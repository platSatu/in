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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasScopedAccess;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const INACTIVE_MESSAGE = 'Akun Anda sedang dinonaktifkan. Silakan hubungi admin kami.';

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
       'referral_code',
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

    /**
     * Kwitansi pembayaran Aplikasi Kuliah (20 November 2026): role Sales tidak boleh melihat/mengelola,
     * role lain boleh. User yang juga superadmin tetap boleh. Role Sales dikenali dari slug/nama "sales".
     */
    public function canAccessPaymentReceipt(): bool
    {
        if ($this->hasRole('superadmin')) {
            return true;
        }

        return ! $this->roles()
            ->where('roles.status', Role::STATUS_ACTIVE)
            ->where(fn ($query) => $query->whereRaw('LOWER(slug) = ?', ['sales'])->orWhereRaw('LOWER(name) = ?', ['sales']))
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
     * Akun yang sudah verifikasi email tapi dinonaktifkan admin -- ditolak
     * saat login (password & Google). Akun baru yang belum verifikasi juga
     * masih inactive, tapi itu normal (diaktifkan oleh link verifikasi).
     */
    public function isSuspended(): bool
    {
        return $this->hasVerifiedEmail() && ! $this->isActive();
    }

    /**
     * Keluarkan akun ini dari SEMUA perangkat: cookie "remember me" dibuat
     * tidak berlaku & sesi login yang tersimpan dihapus (driver database).
     */
    public function logoutEverywhere(): void
    {
        $this->forceFill(['remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $this->getKey())
                ->delete();
        }
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

    /**
     * Kode referal akun ini (25 November 2026). Dibuat sekali saat pertama dibutuhkan, lalu tetap sama.
     * Unik dijaga indeks UNIQUE users.referral_code + pengecekan di newReferralCode().
     */
    public function referralCode(): string
    {
        if (! $this->referral_code) {
            $this->forceFill(['referral_code' => static::newReferralCode((string) $this->name)])->save();
        }

        return $this->referral_code;
    }

    /** Kode baru yang belum dipakai akun lain, mis. "BUDI-K7Q2M" (tanpa huruf/angka yang mirip: 0 O 1 I L). */
    public static function newReferralCode(string $name): string
    {
        do {
            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4)) ?: 'INA';
            $code = strtr($prefix.'-'.strtoupper(\Illuminate\Support\Str::random(5)), ['0' => 'X', 'O' => 'Y', '1' => 'Z', 'I' => 'W', 'L' => 'K']);
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }
}
