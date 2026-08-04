<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStore
{
    /**
     * Handle an incoming request.
     *
     * Memastikan session store aktif valid:
     * - Kasir selalu terkunci ke tokonya sendiri.
     * - Admin/super-admin bebas memilih toko atau mode "all".
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $isKasirOnly = $user->hasRole('kasir') && ! $user->hasAnyRole(['admin', 'super-admin']);

        if ($isKasirOnly) {
            session(['store_id' => $user->store_id]);

            return $next($request);
        }

        $sessionStoreId = session('store_id');

        // Validasi store yang dipilih masih aktif
        if ($sessionStoreId && $sessionStoreId !== 'all' && ! Store::where('id', $sessionStoreId)->where('is_active', true)->exists()) {
            session()->forget('store_id');
        }

        return $next($request);
    }
}
