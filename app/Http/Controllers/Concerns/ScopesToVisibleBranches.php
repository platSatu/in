<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;

/**
 * Batasan data PER CABANG untuk halaman admin (Pengajuan Kelas / Potong
 * Credit / Riwayat Credit, Honor Pengajar). Cabang yang boleh dilihat diambil
 * dari role aktif user (App\Concerns\HasScopedAccess::visibleBranchIds()):
 * role tingkat company melihat semua cabang, role cabang/divisi hanya
 * cabangnya sendiri. Aturan sama dengan App\Helpers\DataScope di modul Student.
 */
trait ScopesToVisibleBranches
{
    /** @return array<int, string>|null null = semua cabang */
    protected function visibleBranches(Request $request): ?array
    {
        return $request->user()->visibleBranchIds();
    }

    protected function scopeToVisibleBranches(QueryBuilder $query, Request $request, string $column = 'branch_id'): QueryBuilder
    {
        $branchIds = $this->visibleBranches($request);

        return $branchIds === null ? $query : $query->whereIn($column, $branchIds);
    }

    /** Data cabang lain diperlakukan seperti tidak ada (404), bukan sekadar ditolak. */
    protected function abortUnlessBranchVisible(Request $request, ?string $branchId): void
    {
        $branchIds = $this->visibleBranches($request);

        abort_if($branchIds !== null && ! in_array($branchId, $branchIds, true), 404);
    }
}
