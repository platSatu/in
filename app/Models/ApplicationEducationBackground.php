<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FASE 3 -- satu baris "Education Background" (fitur "add row" di Step 1
 * Formulir web) milik 1 UniversityApplication. Sesuai instruksi form
 * aslinya: "Start From Elementary School, Junior High, Senior High/
 * University" -- siswa isi 1 baris per jenjang pendidikan yang relevan,
 * urutannya lewat 'sort_order' (bebas ditambah/dihapus, tidak wajib 4
 * jenjang semua).
 */
class ApplicationEducationBackground extends Model
{
    use HasUuids;

    protected $table = 'application_education_backgrounds';

    protected $keyType = 'string';

    public $incrementing = false;

    public const LEVEL_ELEMENTARY = 'elementary';
    public const LEVEL_JUNIOR_HIGH = 'junior_high';
    public const LEVEL_SENIOR_HIGH = 'senior_high';
    public const LEVEL_UNIVERSITY = 'university';

    public const LEVELS = [
        self::LEVEL_ELEMENTARY => 'Elementary School',
        self::LEVEL_JUNIOR_HIGH => 'Junior High School',
        self::LEVEL_SENIOR_HIGH => 'Senior High School',
        self::LEVEL_UNIVERSITY => 'University',
    ];

    protected $fillable = [
        'application_id',
        'level',
        'school_name',
        'location',
        'year_start',
        'year_end',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(UniversityApplication::class, 'application_id');
    }

    public function levelLabel(): string
    {
        return self::LEVELS[$this->level] ?? ($this->level ?: '-');
    }
}
