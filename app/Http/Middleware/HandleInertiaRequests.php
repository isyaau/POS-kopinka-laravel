<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $auth = $user ? [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'store_id' => $user->store_id,
            ],
            'roles' => $user->getRoleNames()->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'stores' => $this->accessibleStores($user),
            'current_store' => $this->currentStore($user),
        ] : [
            'user' => null,
            'roles' => [],
            'permissions' => [],
            'stores' => [],
            'current_store' => null,
        ];

        return [
            ...parent::share($request),
            'auth' => $auth,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Daftar toko yang bisa diakses user.
     * Pusat/admin/super-admin bisa akses semua + mode "all".
     */
    protected function accessibleStores($user): array
    {
        $isKasirOnly = $user->hasRole('kasir') && ! $user->hasAnyRole(['admin', 'super-admin']);

        if ($isKasirOnly) {
            return $user->store
                ? [[
                    'id' => $user->store->id,
                    'kode' => $user->store->kode,
                    'nama' => $user->store->nama,
                    'tipe' => $user->store->tipe,
                ]]
                : [];
        }

        $stores = Store::where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'kode' => $s->kode,
                'nama' => $s->nama,
                'tipe' => $s->tipe,
            ])
            ->push([
                'id' => 'all',
                'kode' => 'ALL',
                'nama' => 'Semua Toko',
                'tipe' => 'pusat',
            ])
            ->values()
            ->all();

        return $stores;
    }

    /**
     * Toko yang sedang aktif (dari session), default ke toko user.
     */
    protected function currentStore($user): ?array
    {
        $storeId = session('store_id', $user->store_id);

        if ($storeId === 'all') {
            return [
                'id' => 'all',
                'kode' => 'ALL',
                'nama' => 'Semua Toko',
                'tipe' => 'pusat',
            ];
        }

        if ($storeId) {
            $store = Store::find($storeId);
            if ($store) {
                return [
                    'id' => $store->id,
                    'kode' => $store->kode,
                    'nama' => $store->nama,
                    'tipe' => $store->tipe,
                ];
            }
        }

        return null;
    }
}
