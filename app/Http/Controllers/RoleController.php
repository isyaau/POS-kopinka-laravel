<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Role sistem yang tidak boleh diubah/hapus.
     */
    protected const SYSTEM_ROLES = ['super-admin', 'admin', 'kasir'];

    /**
     * Menampilkan daftar role.
     */
    public function index(): Response
    {
        $roles = Role::query()
            ->withCount('users')
            ->with('permissions')
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $this->label($role->name),
                'is_system' => in_array($role->name, self::SYSTEM_ROLES, true),
                'users_count' => $role->users_count,
                'permissions' => $role->permissions->pluck('name')->sort()->values(),
            ]);

        $permissions = Permission::query()->orderBy('name')->pluck('name');

        return Inertia::render('Role/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Simpan role baru.
     */
    public function store(RoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return back()->with('success', "Role \"{$role->name}\" berhasil dibuat.");
    }

    /**
     * Perbarui role.
     */
    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        if (in_array($role->name, self::SYSTEM_ROLES, true)) {
            return back()->with('error', 'Role sistem tidak dapat diubah.');
        }

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return back()->with('success', "Role \"{$role->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus role (kecuali sistem & role yang masih dipakai).
     */
    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, self::SYSTEM_ROLES, true)) {
            return back()->with('error', 'Role sistem tidak dapat dihapus.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', "Role \"{$role->name}\" masih dipakai pengguna, tidak dapat dihapus.");
        }

        $name = $role->name;
        $role->delete();

        return back()->with('success', "Role \"{$name}\" berhasil dihapus.");
    }

    /**
     * Label tampilan role.
     */
    protected function label(string $name): string
    {
        return match ($name) {
            'super-admin' => 'Super Admin',
            'admin' => 'Admin',
            'kasir' => 'Kasir',
            'manager' => 'Manager',
            'kepala-toko' => 'Kepala Toko',
            'akuntansi' => 'Akuntansi',
            default => ucwords(str_replace('-', ' ', $name)),
        };
    }
}
