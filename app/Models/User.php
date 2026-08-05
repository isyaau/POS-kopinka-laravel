<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'store_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['admin', 'super-admin']);
    }

    /**
     * Role yang dianggap punya akses semua toko (+ mode "Semua Toko").
     */
    public function hasAllStoreAccess(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin', 'manager', 'akuntansi']);
    }

    /**
     * Daftar id toko yang boleh diakses user (utama + pivot).
     */
    public function accessibleStoreIds(): array
    {
        if ($this->hasAllStoreAccess()) {
            return Store::where('is_active', true)->pluck('id')->all();
        }

        $ids = $this->stores()->pluck('stores.id')->all();
        if ($this->store_id) {
            $ids[] = $this->store_id;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Cek apakah user boleh mengakses toko tertentu.
     */
    public function canAccessStore($storeId): bool
    {
        if ($this->hasAllStoreAccess()) {
            return true;
        }

        return in_array((int) $storeId, $this->accessibleStoreIds(), true);
    }
}
