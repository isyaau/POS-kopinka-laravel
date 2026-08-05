<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureAnggotaPermission();
    }

    /**
     * Fallback agar permission & role mapping selalu tersedia,
     * sehingga menu Anggota langsung muncul tanpa harus menjalankan seeder manual.
     */
    protected function ensureAnggotaPermission(): void
    {
        try {
            if (! Permission::where('name', 'anggota.manage')->exists()) {
                Permission::create(['name' => 'anggota.manage']);
            }

            foreach (['admin', 'super-admin'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role && ! $role->hasPermissionTo('anggota.manage')) {
                    $role->givePermissionTo('anggota.manage');
                }
            }
        } catch (Throwable) {
            // Abaikan jika tabel permission belum ada (migrasi belum dijalankan)
        }
    }
}
