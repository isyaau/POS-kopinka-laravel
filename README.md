# 🚀 Setup POS Kopinka — Komponen Seragam + Layout Modular + Auth RBAC + Multi-Toko

Proyek Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS v4 dengan:
- ✅ Komponen UI seragam (shadcn-style) — responsive mobile & PC
- ✅ Layout modular scalable (sidebar + header + drawer mobile)
- ✅ Brand palette Kopinka (merah `#E53E3E`, abu metalik, navy sidebar)
- ✅ Autentikasi + RBAC Spatie (5 permission, prioritas admin)
- ✅ Multi-toko: 1 Pusat + 5 toko Kopinka (dengan Store Switcher)

---

## 1. Pasang Dependency

Semua file sudah dibuat. Jalankan perintah berikut di **terminal Anda** (folder proyek):

```bash
# Backend — Spatie Laravel Permission
composer require spatie/laravel-permission

# Frontend — dependency UI (radix-vue, lucide, sonner, dll)
npm install
```

> [!NOTE]
> `package.json`, `composer.json`, `config/permission.php`, dan migrasi Spatie **sudah dibuat**.
> Langkah `composer require` tetap disarankan agar versi terpasang di `composer.lock` konsisten.

## 2. Migrate & Seed

```bash
# Buat tabel stores, users.store_id, dan tabel permission Spatie
php artisan migrate

# Buat role, 5 permission, 6 toko, dan user demo
php artisan db:seed --class=RolePermissionSeeder
```

## 3. Jalankan Aplikasi

```bash
php artisan serve   # terminal 1  → http://localhost:8000
npm run dev         # terminal 2  → Vite dev server
```

## 4. Akun Demo

| Email | Password | Role | Store |
|---|---|---|---|
| `admin@kopinka.test` | `admin123` | ✅ **Admin** (prioritas) | Kopinka Pusat |
| `super-admin@kopinka.test` | `admin123` | Super Admin | Kopinka Pusat |
| `kasir@kopinka.test` | `admin123` | Kasir | Kopinka 1 |

## 5. Yang Perlu Dicek (Verification)

1. **`/login`** → login `admin@kopinka.test`
2. **Dashboard** → sidebar + header + Store Switcher muncul
3. **Desktop** (>1024px): sidebar penuh → tombol panel ciutkan jadi ikon
4. **Mobile** (<1024px, devtools 375px): sidebar jadi drawer geser dari kiri
5. **Store Switcher** (sebagai admin): pindah pusat ↔ toko 1–5, dan "Semua Toko"
6. **RBAC**: login `kasir@kopinka.test` → menu Laporan & Pengguna tersembunyi,
   akses langsung `/laporan` → **403**
7. **Toggle tema** gelap/terang (ikon di header)

---

## Struktur Penting

```
resources/js/
├── app.js                      → boot Inertia, theme init
├── components/
│   ├── ui/                     → komponen seragam (button, card, sheet, dll)
│   └── layout/                 → AppLayout, AppSidebar, AppHeader, StoreSwitcher, PageHeader, SidebarNav
├── config/navigation.js        → ⭐ daftar semua menu + permission
├── composables/useTheme.js     → toggle dark/light
└── Pages/                      → Auth/Login, Dashboard, POS, Produk, Laporan, Pengguna

app/
├── Models/Store.php
├── Models/User.php             → HasRoles + relasi store
├── Http/Middleware/ResolveStore.php
├── Http/Middleware/CheckPermission.php
└── Http/Controllers/Auth/AuthController.php, StoreSwitchController.php

database/migrations/            → stores, users.store_id, permission tables
database/seeders/RolePermissionSeeder.php → 5 permission, 6 toko, user demo
```

## Menambah Menu Baru (Scalable)

Buka `resources/js/config/navigation.js` dan tambah satu item:

```js
{ title: 'Kasir / POS', href: '/pos', icon: ShoppingCart, permission: 'transaksi.manage' },
```

Sidebar (desktop + drawer mobile) dan filter permission **otomatis** mengikuti.
