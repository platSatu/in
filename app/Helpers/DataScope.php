<?php

namespace App\Helpers;

use App\Models\Form;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter query berdasarkan "cakupan data" (scope_level) role aktif user —
 * lihat App\Concerns\HasScopedAccess & App\Models\Role untuk penjelasan
 * lengkap konsep company/branch/division/self. Dipakai di modul Quiz (Form,
 * FormSection, FormQuestion, FormQuestionOption) & Student — SEBELUM ini,
 * modul-modul itu memfilter data cuma dengan `where user_id = pembuatnya
 * sendiri`; sekarang ikut cakupan branch/divisi/company sesuai role user yang
 * login, supaya rekan setim di branch/unit yang sama bisa saling melihat.
 *
 * SENGAJA satu helper generik dipakai bersama semua modul di atas (bukan
 * ditulis ulang query-nya satu-satu per controller) — supaya aturan
 * "siapa boleh lihat apa" hanya ada di SATU tempat, konsisten & gampang
 * diaudit, bukan tersebar & berisiko berbeda-beda tanpa sengaja.
 */
class DataScope
{
    /**
     * Terapkan filter branch/divisi/self langsung ke sebuah query yang
     * TABEL-NYA SENDIRI punya kolom branch/divisi (mis. forms, students).
     *
     * - scope 'company' (isCompanyScoped()): TIDAK difilter sama sekali,
     *   lihat semua data lintas branch & divisi.
     * - scope 'self' SATU-SATUNYA (isSelfScopedOnly()): kembali ke perilaku
     *   LAMA — cuma lihat data milik/tanganan sendiri ($selfOwnerColumn).
     *   Dicek LEBIH DULU sebelum branch/divisi supaya tidak ikut kena
     *   whereIn([]) kosong yang berarti "tidak ada satu pun" (lihat catatan
     *   di visibleBranchIds()/visibleDivisionIds() — array kosong itu beda
     *   makna dengan null).
     * - scope 'branch'/'division': difilter sesuai cabang/divisi yang jadi
     *   cakupan role aktifnya (bisa lebih dari satu kalau user punya
     *   beberapa role/assignment sekaligus).
     */
    public static function applyBranchDivisionScope(
        Builder $query,
        User $user,
        string $selfOwnerColumn,
        string $branchColumn = 'branch_id',
        string $divisionColumn = 'company_division_id'
    ): Builder {
        if ($user->isCompanyScoped()) {
            return $query;
        }

        if ($user->isSelfScopedOnly()) {
            return $query->where($selfOwnerColumn, $user->id);
        }

        // null dari visibleBranchIds()/visibleDivisionIds() berarti "tidak
        // dibatasi pada level ini" (mis. scope branch tidak perlu dibatasi
        // lagi per-divisi, karena branch-wide sudah otomatis mencakup semua
        // divisi di branch itu) — beda dengan array KOSONG yang berarti
        // "tidak ada cakupan sama sekali" (role-nya branch/division tapi
        // baris assignment-nya entah kenapa belum diisi branch/divisi apa
        // pun — kasus data tidak lengkap, sengaja dibiarkan hasilnya kosong
        // total supaya kelihatan jelas ada assignment yang perlu dibenahi,
        // bukan diam-diam jatuh ke "lihat semua" atau "lihat punya sendiri").
        $branchIds = $user->visibleBranchIds();
        if ($branchIds !== null) {
            $query->whereIn($branchColumn, $branchIds);
        }

        $divisionIds = $user->visibleDivisionIds();
        if ($divisionIds !== null) {
            $query->whereIn($divisionColumn, $divisionIds);
        }

        return $query;
    }

    /**
     * Daftar id Form yang boleh dilihat user ini, dipakai modul yang TABEL-NYA
     * SENDIRI tidak punya kolom branch/divisi (FormSection, FormQuestion,
     * FormQuestionOption — semuanya nempel ke Form lewat form_id, langsung
     * atau tidak langsung). Null berarti TIDAK DIBATASI (scope company),
     * jangan di-whereIn sama sekali — supaya query pemanggilnya tidak perlu
     * memuat SELURUH id form ke memori kalau memang tidak perlu dibatasi.
     *
     * @return array<int, string>|null
     */
    public static function visibleFormIds(User $user): ?array
    {
        if ($user->isCompanyScoped()) {
            return null;
        }

        return self::applyBranchDivisionScope(Form::query(), $user, 'user_id')
            ->pluck('id')
            ->all();
    }
}
