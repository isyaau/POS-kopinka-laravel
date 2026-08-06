<script setup>
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import {
    Store as StoreIcon,
    TrendingUp,
    Users,
    ShieldCheck,
    UserCheck,
    Wallet,
    Receipt,
    Star,
    AlertTriangle,
} from 'lucide-vue-next'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const page = usePage()
const auth = computed(() => page.props.auth || {})
const stats = computed(() => page.props.stats || {})
const currentStore = computed(() => stats.value.current_store || {})
const user = computed(() => auth.value.user || {})
const role = computed(() => (auth.value.roles || [])[0] || 'pengguna')

const anggotaStats = computed(() => stats.value.anggota_stats || {})
const anggota = computed(() => anggotaStats.value.anggota || {})
const karyawan = computed(() => anggotaStats.value.karyawan || {})

const penjualan = computed(() => stats.value.penjualan || {})
const hariIni = computed(() => penjualan.value.hari_ini || {})
const bulanIni = computed(() => penjualan.value.bulan_ini || {})
const grafik = computed(() => penjualan.value.grafik || [])
const metode = computed(() => penjualan.value.metode || {})
const produkTerlaris = computed(() => stats.value.produk_terlaris || [])
const stokMenipisList = computed(() => stats.value.stok_menipis || [])

const totalAnggota = computed(
    () => (anggota.value.aktif || 0) + (anggota.value.aktif_purna || 0) + (anggota.value.diblokir || 0) + (anggota.value.pasif_purna || 0),
)
const totalKaryawan = computed(
    () => (karyawan.value.aktif || 0) + (karyawan.value.diblokir || 0) + (karyawan.value.pasif_purna || 0),
)

const formatRupiah = (val) => {
    const n = Number(val || 0)
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(n)
}

// ---- Konfigurasi grafik donut ----
const COLORS = {
    aktif: '#22c55e',
    aktif_purna: '#0ea5e9',
    diblokir: '#f59e0b',
    pasif_purna: '#94a3b8',
}

const buildDonut = (segments) => {
    const total = segments.reduce((s, x) => s + x.value, 0)
    if (total <= 0) return []
    const radius = 80
    const circumference = 2 * Math.PI * radius
    let offset = 0
    return segments
        .filter((s) => s.value > 0)
        .map((s) => {
            const frac = s.value / total
            const dash = frac * circumference
            const arc = { ...s, dash, offset, total }
            offset += dash
            return arc
        })
}

const anggotaSegments = computed(() =>
    buildDonut([
        { key: 'aktif', label: 'Aktif', value: anggota.value.aktif || 0, color: COLORS.aktif },
        { key: 'aktif_purna', label: 'Aktif Purna', value: anggota.value.aktif_purna || 0, color: COLORS.aktif_purna },
        { key: 'diblokir', label: 'Diblokir', value: anggota.value.diblokir || 0, color: COLORS.diblokir },
        { key: 'pasif_purna', label: 'Pasif Purna', value: anggota.value.pasif_purna || 0, color: COLORS.pasif_purna },
    ]),
)

const karyawanSegments = computed(() =>
    buildDonut([
        { key: 'aktif', label: 'Aktif', value: karyawan.value.aktif || 0, color: COLORS.aktif },
        { key: 'diblokir', label: 'Diblokir', value: karyawan.value.diblokir || 0, color: COLORS.diblokir },
        { key: 'pasif_purna', label: 'Pasif Purna', value: karyawan.value.pasif_purna || 0, color: COLORS.pasif_purna },
    ]),
)

const pctOf = (val, total) => (total > 0 ? Math.round((val / total) * 100) : 0)

const variantFor = (key) =>
    key === 'aktif' ? 'success' : key === 'aktif_purna' ? 'purna' : key === 'diblokir' ? 'warning' : 'inactive'

const hasAnggota = computed(() => totalAnggota.value > 0 || totalKaryawan.value > 0)

// ---- Bar chart 7 hari (omzet) ----
const chartMax = computed(() => Math.max(...grafik.value.map((g) => g.omzet), 1))

// ---- Metode bayar (hari ini) ----
const metodeList = computed(() => {
    const m = metode.value
    return [
        { label: 'Cash', value: m.cash || 0, color: '#22c55e' },
        { label: 'QRIS', value: m.qris || 0, color: '#6366f1' },
        { label: 'EDC', value: m.edc || 0, color: '#0ea5e9' },
        { label: 'Voucher', value: m.voucher || 0, color: '#f59e0b' },
        { label: 'Piutang', value: m.piutang || 0, color: '#f43f5e' },
    ]
})
const totalMetode = computed(() => metodeList.value.reduce((s, x) => s + x.value, 0))

const maxTerlaris = computed(() => Math.max(...produkTerlaris.value.map((p) => p.qty), 1))
</script>

<template>
    <AppLayout>
        <Head title="Dashboard - POS Kopinka" />

        <PageHeader
            title="Dashboard"
            :description="`Selamat datang kembali, ${user.name || 'Pengguna'} — ${currentStore.nama || 'Pilih toko'} (${currentStore.kode || '-'})`"
        >
            <template #actions>
                <Badge variant="secondary" class="capitalize">{{ role }}</Badge>
            </template>
        </PageHeader>

        <!-- Kartu Selamat Datang -->
        <Card class="mb-6">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <TrendingUp class="text-primary size-5" />
                    Selamat Datang di POS Kopinka
                </CardTitle>
                <CardDescription>
                    Sistem kasir multi-toko untuk 1 kantor pusat dan 5 toko Kopinka.
                </CardDescription>
            </CardHeader>
            <CardContent class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-muted-foreground max-w-2xl text-sm">
                    Gunakan menu di samping untuk mengelola transaksi, produk, laporan, dan pengguna.
                    Sebagai admin, Anda dapat berpindah toko melalui dropdown di header.
                </p>
                <Button as="a" href="/pos" class="h-11 shrink-0" size="lg">
                    Buka Kasir
                </Button>
            </CardContent>
        </Card>

        <!-- Kartu Statistik Utama: Kinerja Toko -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Omzet Hari Ini</CardTitle>
                    <Wallet class="text-emerald-600 size-4" />
                </CardHeader>
                <CardContent>
                    <p class="truncate text-2xl font-bold">{{ formatRupiah(hariIni.omzet) }}</p>
                    <p class="text-muted-foreground text-xs">{{ hariIni.jumlah }} transaksi hari ini</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Omzet Bulan Ini</CardTitle>
                    <Receipt class="text-primary size-4" />
                </CardHeader>
                <CardContent>
                    <p class="truncate text-2xl font-bold">{{ formatRupiah(bulanIni.omzet) }}</p>
                    <p class="text-muted-foreground text-xs">{{ bulanIni.jumlah }} transaksi bulan ini</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Total Anggota</CardTitle>
                    <Users class="text-primary size-4" />
                </CardHeader>
                <CardContent>
                    <p class="truncate text-2xl font-bold">{{ anggotaStats.total || 0 }}</p>
                    <p class="text-muted-foreground text-xs">{{ totalAnggota }} anggota · {{ totalKaryawan }} karyawan</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Total Toko</CardTitle>
                    <StoreIcon class="text-primary size-4" />
                </CardHeader>
                <CardContent>
                    <p class="truncate text-2xl font-bold">{{ stats.total_stores || 0 }}</p>
                    <p class="text-muted-foreground text-xs">1 pusat + {{ Math.max(0, (stats.total_stores || 1) - 1) }} toko</p>
                </CardContent>
            </Card>
        </div>

        <!-- Grafik & Statistik Penjualan -->
        <div class="mb-6 grid grid-cols-1 gap-4 xl:grid-cols-[repeat(2,minmax(0,1fr))]">
            <!-- Grafik 7 hari -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrendingUp class="text-primary size-5" />
                        Grafik Penjualan 7 Hari
                    </CardTitle>
                    <CardDescription>Omzet harian (Rp) seminggu terakhir</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex h-44 items-end gap-2">
                        <div v-for="g in grafik" :key="g.tanggal" class="flex flex-1 flex-col items-center gap-1">
                            <div
                                class="w-full rounded-md"
                                :style="{
                                    height: `${Math.max(6, (g.omzet / chartMax) * 160)}px`,
                                    background: g.omzet > 0
                                        ? 'linear-gradient(180deg, #10b981, #059669)'
                                        : 'var(--muted)',
                                }"
                                :title="`${g.label}: ${formatRupiah(g.omzet)}`"
                            />
                            <span class="text-muted-foreground text-[10px]">{{ g.label }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Metode pembayaran -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Wallet class="text-primary size-5" />
                        Metode Pembayaran Hari Ini
                    </CardTitle>
                    <CardDescription>Distribusi pembayaran per metode</CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-3">
                    <div v-for="mt in metodeList" :key="mt.label" class="flex items-center gap-3">
                        <span class="size-3 shrink-0 rounded-full" :style="{ background: mt.color }" />
                        <span class="w-20 text-sm font-medium">{{ mt.label }}</span>
                        <div class="bg-muted h-2.5 flex-1 overflow-hidden rounded-full">
                            <div
                                class="h-full rounded-full"
                                :style="{ width: `${totalMetode > 0 ? (mt.value / totalMetode) * 100 : 0}%`, background: mt.color }"
                            />
                        </div>
                        <span class="text-sm font-bold">{{ formatRupiah(mt.value) }}</span>
                    </div>
                    <p v-if="totalMetode === 0" class="text-muted-foreground pt-1 text-sm">Belum ada transaksi hari ini.</p>
                </CardContent>
            </Card>
        </div>

        <!-- Statistik Anggota & Karyawan (donut) -->
        <div v-if="hasAnggota" class="mb-6 grid min-w-0 grid-cols-1 gap-4 xl:grid-cols-[repeat(2,minmax(0,1fr))]">
            <!-- Anggota donut -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UserCheck class="text-primary size-5" />
                        Statistik Anggota
                    </CardTitle>
                    <CardDescription>Distribusi status anggota Kopinka</CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col items-center gap-6 sm:flex-row sm:items-center">
                    <div class="relative size-44 shrink-0">
                        <svg viewBox="0 0 200 200" class="size-full -rotate-90">
                            <circle cx="100" cy="100" r="80" fill="none" stroke="var(--border)" stroke-width="22" />
                            <circle
                                v-for="s in anggotaSegments"
                                :key="s.key"
                                cx="100"
                                cy="100"
                                r="80"
                                fill="none"
                                :stroke="s.color"
                                stroke-width="22"
                                stroke-linecap="round"
                                :stroke-dasharray="`${s.dash} ${200 * Math.PI - s.dash}`"
                                :stroke-dashoffset="-s.offset"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-bold">{{ totalAnggota }}</span>
                            <span class="text-muted-foreground text-xs">Anggota</span>
                        </div>
                    </div>
                    <div class="flex w-full flex-col gap-2.5">
                        <div
                            v-for="s in anggotaSegments"
                            :key="s.key"
                            class="flex items-center justify-between gap-3 rounded-lg border p-2.5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="size-3 rounded-full" :style="{ background: s.color }" />
                                <span class="text-sm font-medium">{{ s.label }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold">{{ s.value }}</span>
                                <Badge :variant="variantFor(s.key)">{{ pctOf(s.value, s.total) }}%</Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Karyawan donut -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ShieldCheck class="text-primary size-5" />
                        Statistik Karyawan
                    </CardTitle>
                    <CardDescription>Distribusi status karyawan Kopinka</CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col items-center gap-6 sm:flex-row sm:items-center">
                    <div class="relative size-44 shrink-0">
                        <svg viewBox="0 0 200 200" class="size-full -rotate-90">
                            <circle cx="100" cy="100" r="80" fill="none" stroke="var(--border)" stroke-width="22" />
                            <circle
                                v-for="s in karyawanSegments"
                                :key="s.key"
                                cx="100"
                                cy="100"
                                r="80"
                                fill="none"
                                :stroke="s.color"
                                stroke-width="22"
                                stroke-linecap="round"
                                :stroke-dasharray="`${s.dash} ${200 * Math.PI - s.dash}`"
                                :stroke-dashoffset="-s.offset"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-bold">{{ totalKaryawan }}</span>
                            <span class="text-muted-foreground text-xs">Karyawan</span>
                        </div>
                    </div>
                    <div class="flex w-full flex-col gap-2.5">
                        <div
                            v-for="s in karyawanSegments"
                            :key="s.key"
                            class="flex items-center justify-between gap-3 rounded-lg border p-2.5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="size-3 rounded-full" :style="{ background: s.color }" />
                                <span class="text-sm font-medium">{{ s.label }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold">{{ s.value }}</span>
                                <Badge :variant="variantFor(s.key)">{{ pctOf(s.value, s.total) }}%</Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Produk Terlaris & Stok Menipis -->
        <div class="mb-6 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Star class="text-primary size-5" />
                        Produk Terlaris
                    </CardTitle>
                    <CardDescription>5 besar berdasarkan jumlah terjual</CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-3">
                    <div v-for="(p, i) in produkTerlaris" :key="p.nama_barang" class="flex items-center gap-3">
                        <span
                            class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                            :class="i === 0 ? 'bg-amber-400/20 text-amber-500' : 'bg-muted text-muted-foreground'"
                        >
                            {{ i + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ p.nama_barang }}</p>
                            <div class="bg-muted mt-1 h-2 overflow-hidden rounded-full">
                                <div class="bg-primary h-full rounded-full" :style="{ width: `${(p.qty / maxTerlaris) * 100}%` }" />
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-bold">{{ p.qty }}</span>
                        <span class="text-muted-foreground w-24 shrink-0 text-right text-xs">{{ formatRupiah(p.total) }}</span>
                    </div>
                    <p v-if="!produkTerlaris.length" class="text-muted-foreground py-4 text-sm">Belum ada transaksi.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <AlertTriangle class="text-amber-500 size-5" />
                        Stok Menipis
                    </CardTitle>
                    <CardDescription>Produk yang perlu segera restock</CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-2.5">
                    <div v-for="p in stokMenipisList" :key="p.kode_barang" class="flex items-center gap-3 rounded-lg border p-2.5">
                        <div class="bg-amber-500/10 text-amber-600 flex size-8 shrink-0 items-center justify-center rounded-md">
                            <AlertTriangle class="size-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ p.nama_barang }}</p>
                            <p class="text-muted-foreground text-xs">{{ p.kode_barang }}</p>
                        </div>
                        <Badge variant="warning">
                            {{ p.stok }}/{{ p.stok_minimum }} {{ p.satuan || '' }}
                        </Badge>
                    </div>
                    <p v-if="!stokMenipisList.length" class="text-muted-foreground py-4 text-sm">Semua stok aman.</p>
                </CardContent>
            </Card>
        </div>

        <!-- Ringkasan tabel status (saat tidak ada donut) -->
        <div v-if="!hasAnggota" class="mb-3">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrendingUp class="text-primary size-5" />
                        Statistik Anggota & Karyawan
                    </CardTitle>
                    <CardDescription>Ringkasan status berdasarkan kategori</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-2">
                            <p class="text-muted-foreground text-sm font-semibold">Anggota</p>
                            <div class="flex flex-wrap gap-2">
                                <Badge variant="success">Aktif: {{ anggota.aktif || 0 }}</Badge>
                                <Badge variant="purna">Aktif Purna: {{ anggota.aktif_purna || 0 }}</Badge>
                                <Badge variant="warning">Diblokir: {{ anggota.diblokir || 0 }}</Badge>
                                <Badge variant="inactive">Pasif Purna: {{ anggota.pasif_purna || 0 }}</Badge>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <p class="text-muted-foreground text-sm font-semibold">Karyawan</p>
                            <div class="flex flex-wrap gap-2">
                                <Badge variant="success">Aktif: {{ karyawan.aktif || 0 }}</Badge>
                                <Badge variant="warning">Diblokir: {{ karyawan.diblokir || 0 }}</Badge>
                                <Badge variant="inactive">Pasif Purna: {{ karyawan.pasif_purna || 0 }}</Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Spacer agar card statistik tidak menempel ke footer -->
        <div aria-hidden="true" class="h-6 shrink-0" />
    </AppLayout>
</template>
