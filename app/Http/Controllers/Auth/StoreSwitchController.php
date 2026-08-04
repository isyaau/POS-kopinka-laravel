<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreSwitchController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'string'],
        ]);

        $user = $request->user();
        $storeId = $validated['store_id'];

        // Kasir tidak boleh pindah toko
        if ($user->hasRole('kasir') && ! $user->hasRole(['admin', 'super-admin'])) {
            session(['store_id' => $user->store_id]);

            return back();
        }

        // Mode "semua toko" hanya untuk pusat/super-admin/admin
        if ($storeId === 'all') {
            if ($user->hasAnyRole(['admin', 'super-admin'])) {
                session(['store_id' => 'all']);
            }

            return back();
        }

        $store = Store::where('id', $storeId)->where('is_active', true)->first();

        if ($store) {
            session(['store_id' => $store->id]);
        }

        return back();
    }
}
