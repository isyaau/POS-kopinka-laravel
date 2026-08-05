<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Menampilkan daftar pengguna.
     */
    public function index(Request $request): Response
    {
        $limit = (int) $request->input('limit', 10);
        $limit = in_array($limit, [10, 25, 50, 100]) ? $limit : 10;

        $query = User::query()
            ->with('store')
            ->with('roles')
            ->with('stores');

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $users = $query->orderBy('id')->paginate($limit)->withQueryString();

        $users->getCollection()->transform(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'store_id' => $user->store_id,
                'store' => $user->store ? ['id' => $user->store->id, 'kode' => $user->store->kode, 'nama' => $user->store->nama] : null,
                'roles' => $user->roles->pluck('name'),
                'stores' => $user->stores->map(fn ($s) => ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama])->values(),
            ];
        });

        $roles = Role::query()->orderBy('id')->get(['id', 'name'])
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])
            ->values();

        $stores = Store::query()->orderBy('id')->get(['id', 'kode', 'nama', 'tipe'])
            ->map(fn ($s) => ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama, 'tipe' => $s->tipe])
            ->values();

        return Inertia::render('Pengguna/Index', [
            'users' => $users,
            'roles' => $roles,
            'stores' => $stores,
            'filters' => [
                'search' => $request->input('search', ''),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Simpan pengguna baru.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['role', 'stores']);
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user = User::create($data);

        if ($request->role) {
            $user->assignRole($request->role);
        }

        if (! empty($request->stores)) {
            $user->stores()->sync($request->stores);
        }

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Perbarui pengguna.
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except(['role', 'stores']);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);

        // Sinkronkan role (single role)
        $roles = $request->role ? [$request->role] : [];
        $user->syncRoles($roles);

        // Sinkronkan akses toko tambahan (pivot)
        $user->stores()->sync($request->stores ?? []);

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    /**
     * Hapus pengguna.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
