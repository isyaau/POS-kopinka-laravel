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

        // Mode "semua toko" hanya untuk role dengan akses semua toko
        if ($storeId === 'all') {
            if ($user->hasAllStoreAccess()) {
                session(['store_id' => 'all']);
            }

            return back();
        }

        // Hanya izinkan pindah ke toko yang boleh diakses user
        if ($user->canAccessStore($storeId)) {
            $store = Store::where('id', $storeId)->where('is_active', true)->first();

            if ($store) {
                session(['store_id' => $store->id]);
            }
        }

        return back();
    }
}
