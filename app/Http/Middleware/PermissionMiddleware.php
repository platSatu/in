<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengecekan akses berbasis permission/modul (lihat config/menu.php +
 * App\Concerns\HasScopedAccess::canAccessPermission()). Dipasang lewat alias
 * 'permission' (lihat bootstrap/app.php), dipakai di routes/web.php sbg:
 *
 *   Route::middleware(['auth', 'permission:company.division'])->...        // butuh akses lihat
 *   Route::middleware(['auth', 'permission:company.division,edit'])->...   // butuh akses kelola
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permissionKey, string $ability = 'view'): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if (! $user->canAccessPermission($permissionKey, $ability)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
