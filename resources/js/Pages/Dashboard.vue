<script setup>
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import {
    Store as StoreIcon,
    Building2,
    TrendingUp,
    Package,
    ShoppingCart,
    Users,
    ShieldCheck,
    UserCheck,
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

const totalAnggota = computed(
    () => (anggota.value.aktif || 0) + (anggota.value.aktif_purna || 0) + (anggota.value.diblokir || 0) + (anggota.value.pasif_purna || 0),
)
const totalKaryawan = computed(
    () => (karyawan.value.aktif || 0) + (karyawan.value.diblokir || 0) + (karyawan.value.pasif_purna || 0),
)

// ---- Konfigurasi grafik donut ----
const COLORS = {
    aktif: '#22c55e', // green-500
    aktif_purna: '#0ea5e9', // sky-500
    diblokir: '#f59e0b', // amber-500
    pasif_purna: '#94a3b8', // slate-400
}

// Konversi daftar nilai -> koordinat SVG untuk donut chart
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

// Persentase untuk label tengah
const pctOf = (val, total) => (total > 0 ? Math.round((val / total) * 100) : 0)

const variantFor = (key) =>
    key === 'aktif' ? 'success' : key === 'aktif_purna' ? 'purna' : key === 'diblokir' ? 'warning' : 'inactive'

const hasAnggota = computed(() => totalAnggota.value > 0 || totalKaryawan.value > 0)
</script>

<template>
    <AppLayout content-fill="false">
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

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Toko Aktif</CardTitle>
                    <Building2 class="text-primary size-4" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">{{ currentStore.nama || '-' }}</p>
                    <p class="text-muted-foreground text-xs">{{ currentStore.kode || '-' }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Total Toko</CardTitle>
                    <StoreIcon class="text-primary size-4" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">{{ stats.total_stores || 0 }}</p>
                    <p class="text-muted-foreground text-xs">1 pusat + 5 toko</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Total Anggota</CardTitle>
                    <Users class="text-primary size-4" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">{{ anggotaStats.total || 0 }}</p>
                    <p class="text-muted-foreground text-xs">{{ totalAnggota }} anggota · {{ totalKaryawan }} karyawan</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Transaksi Hari Ini</CardTitle>
                    <ShoppingCart class="text-primary size-4" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">—</p>
                    <p class="text-muted-foreground text-xs">Modul segera hadir</p>
                </CardContent>
            </Card>
        </div>

        <!-- ===== Grafik Statistik Anggota & Karyawan ===== -->
        <div v-if="hasAnggota" class="mb-3 grid grid-cols-1 gap-4 lg:grid-cols-2">
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

        <!-- Ringkasan tabel status (saat tidak ada data donut, tetap informatif) -->
        <div v-else class="mb-3">
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
