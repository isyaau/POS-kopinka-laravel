<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $storeId = session('store_id');

        $store = null;
        if ($storeId && $storeId !== 'all') {
            $store = Store::find($storeId);
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'current_store' => $store ? [
                    'id' => $store->id,
                    'kode' => $store->kode,
                    'nama' => $store->nama,
                    'tipe' => $store->tipe,
                ] : ($storeId === 'all' ? [
                    'id' => 'all',
                    'kode' => 'ALL',
                    'nama' => 'Semua Toko',
                    'tipe' => 'pusat',
                ] : null),
                'total_stores' => Store::where('is_active', true)->count(),
                'user_role' => $user->getRoleNames()->first(),
            ],
        ]);
    }
}
