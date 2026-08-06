<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pastikan permission voucher.* ada dan diberikan ke role admin & super-admin.
     * Dibuat sebagai migration (bukan hanya seeder) agar cukup menjalankan
     * `php artisan migrate` tanpa harus seed ulang seluruh database.
     */
    public function up(): void
    {
        $permissions = [
            'voucher.view',
            'voucher.create',
            'voucher.update',
            'voucher.delete',
            'voucher.export',
            'voucher.import',
        ];

        $ids = [];
        foreach ($permissions as $name) {
            $id = DB::table('permissions')->where('name', $name)->value('id');

            if (! $id) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $ids[] = $id;
        }

        // Role yang mendapat akses penuh (admin & super-admin)
        $roleIds = DB::table('roles')
            ->whereIn('name', ['admin', 'super-admin'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($ids as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', [
            'voucher.view',
            'voucher.create',
            'voucher.update',
            'voucher.delete',
            'voucher.export',
            'voucher.import',
        ])->delete();
    }
};
