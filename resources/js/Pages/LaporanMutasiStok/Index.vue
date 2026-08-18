<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Boxes, ArrowDownCircle, ArrowUpCircle, RefreshCw,
    Search, Package, AlertTriangle, CalendarRange, Store,
    PackageCheck, PackageX, MinusCircle, PlusCircle,
} from 'lucide-vue-next'

const props = defineProps({
    tab: { type: String, default: 'kartu' },
    kartuStok: { type: Object, default: () => ({ data: [] }) },
    kartuSummary: { type: Object, default: () => ({}) },
    detailMutasi: { type: Object, default: () => ({ data: [] }) },
    mutasiSummary: { type: Object, default: () => ({}) },
    produks: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const activeTab = ref(props.tab || 'kartu')
const search = ref(props.filters.search || '')
const dari = ref(props.filters.dari || '')
const sampai = ref(props.filters.sampai || '')
const produkId = ref(props.filters.produk_id || 'semua')
const tokoId = ref(props.filters.toko_id || 'semua')

const formatRupiah = (val) => {
    const n = Number(val || 0)
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n)
}
const formatNumber = (val) => Number(val || 0).toLocaleString('id-ID')
const formatDate = (val) => {
    if (!val) return '-'
    const d = new Date(val + 'T00:00:00')
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const buildParams = (overrides = {}) => ({
    tab: activeTab.value,
    dari: dari.value,
    sampai: sampai.value,
    produk_id: produkId.value === 'semua' ? undefined : produkId.value,
    toko_id: tokoId.value === 'semua' ? undefined : tokoId.value,
    search: search.value || undefined,
    ...overrides,
})

const applyFilter = () => {
    router.get('/laporan-mutasi-stok', buildParams(), { preserveState: true, replace: true })
}

const resetFilter = () => {
    search.value = ''
    dari.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10)
    sampai.value = new Date().toISOString().slice(0, 10)
    produkId.value = 'semua'
    tokoId.value = 'semua'
    router.get('/laporan-mutasi-stok', buildParams(), { preserveState: true, replace: true })
}

const switchTab = (tab) => {
    activeTab.value = tab
    router.get('/laporan-mutasi-stok', buildParams({ tab }), { preserveState: true, replace: true })
}

const goToPage = (page) => {
    router.get('/laporan-mutasi-stok', buildParams({ page }), { preserveState: true, replace: true })
}

const ks = computed(() => props.kartuSummary || {})
const ms = computed(() => props.mutasiSummary || {})
const kartuItems = computed(() => props.kartuStok?.data || [])
const mutasiItems = computed(() => props.detailMutasi?.data || [])
const kartuPag = computed(() => ({
    current: props.kartuStok?.current_page || 1,
    last: props.kartuStok?.last_page || 1,
    total: props.kartuStok?.total || 0,
}))
const mutasiPag = computed(() => ({
    current: props.detailMutasi?.current_page || 1,
    last: props.detailMutasi?.last_page || 1,
    total: props.detailMutasi?.total || 0,
}))

const getStokBadge = (stok, min) => {
    if (stok === 0) return 'destructive'
    if (stok <= min) return 'secondary'
    return 'default'
}
const getStokLabel = (stok, min) => {
    if (stok === 0) return 'Habis'
    if (stok <= min) return 'Menipis'
    return 'Aman'
}
const getTipeBadge = (tipe) => {
    if (tipe === 'masuk') return 'default'
    if (tipe === 'keluar') return 'destructive'
    return 'secondary'
}
</script>

<template>
    <AppLayout>
        <Head title="Laporan Mutasi &amp; Kartu Stok - Kopinka" />

        <PageHeader title="Laporan Mutasi & Kartu Stok" description="Pantau pergerakan stok dan kondisi stok per produk di seluruh toko." />

        <!-- Filter -->
        <Card class="mb-4">
            <CardContent class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-wrap items-end gap-2">
                    <div class="grid gap-1.5">
                        <label class="text-muted-foreground text-xs font-medium">Dari</label>
                        <Input v-model="dari" type="date" class="w-44" />
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-muted-foreground text-xs font-medium">Sampai</label>
                        <Input v-model="sampai" type="date" class="w-44" />
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-muted-foreground text-xs font-medium">Produk</label>
                        <Select v-model="produkId">
                            <SelectTrigger class="w-48"><SelectValue placeholder="Semua Produk" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Produk</SelectItem>
                                <SelectItem v-for="p in produks" :key="p.id" :value="String(p.id)">{{ p.nama_barang }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-muted-foreground text-xs font-medium">Toko</label>
                        <Select v-model="tokoId">
                            <SelectTrigger class="w-40"><SelectValue placeholder="Semua Toko" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Toko</SelectItem>
                                <SelectItem v-for="st in stores" :key="st.id" :value="String(st.id)">{{ st.nama }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-muted-foreground text-xs font-medium">Cari</label>
                        <div class="relative">
                            <Search class="text-muted-foreground absolute left-2.5 top-2 size-4" />
                            <Input v-model="search" placeholder="Kode/nama produk..." class="w-52 pl-8" @keyup.enter="applyFilter" />
                        </div>
                    </div>
                    <Button size="sm" class="h-9" @click="applyFilter"><CalendarRange class="size-4" /> Terapkan</Button>
                    <Button size="sm" variant="ghost" class="h-9" @click="resetFilter">Reset</Button>
                </div>
                <p class="text-muted-foreground text-sm">{{ activeTab === 'mutasi' ? `Periode: ${formatDate(dari)} — ${formatDate(sampai)}` : 'Stok saat ini' }}</p>
            </CardContent>
        </Card>

        <!-- Summary Cards -->
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <template v-if="activeTab === 'kartu'">
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl"><Package class="size-5" /></div>
                            <div>
                                <p class="text-muted-foreground text-xs">Total Produk</p>
                                <p class="text-xl font-bold">{{ formatNumber(ks.jumlah_produk) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-500/10 text-blue-600 flex size-10 items-center justify-center rounded-xl"><Boxes class="size-5" /></div>
                            <div>
                                <p class="text-muted-foreground text-xs">Total Stok</p>
                                <p class="text-xl font-bold">{{ formatNumber(ks.total_stok) }} <span class="text-muted-foreground text-xs">unit</span></p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card class="ring-2 ring-amber-500/30">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-amber-500/10 text-amber-600 flex size-10 items-center justify-center rounded-xl"><AlertTriangle class="size-5" /></div>
                            <div>
                                <p class="text-muted-foreground text-xs">Stok Menipis</p>
                                <p class="text-xl font-bold text-amber-600">{{ formatNumber(ks.stok_menipis) }} <span class="text-muted-foreground text-xs">produk</span></p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card class="ring-2 ring-red-500/30">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-red-500/10 text-red-600 flex size-10 items-center justify-center rounded-xl"><PackageX class="size-5" /></div>
                            <div>
                                <p class="text-muted-foreground text-xs">Stok Habis</p>
                                <p class="text-xl font-bold text-red-600">{{ formatNumber(ks.stok_habis) }} <span class="text-muted-foreground text-xs">produk</span></p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </template>
            <template v-else>
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl"><RefreshCw class="size-5" /></div>
                            <div>
                                <p class="text-muted-foreground text-xs">Total Transaksi</p>
                                <p class="text-xl font-bold">{{ formatNumber(ms.jumlah) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-emerald-500/10 text-emerald-600 flex size-10 items-center justify-center rounded-xl"><ArrowDownCircle class="size-5" /></div>
                            <div>
                                <p class="text-muted-foreground text-xs">Stok Masuk</p>
                                <p class="text-xl font-bold text-emerald-600">+{{ formatNumber(ms.total_masuk) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-red-500/10 text-red-600 flex size-10 items-center justify-center rounded-xl"><ArrowUpCircle class="size-5" /></div>
                            <div>
                                <p class="text-muted-foreground text-xs">Stok Keluar</p>
                                <p class="text-xl font-bold text-red-600">-{{ formatNumber(ms.total_keluar) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-amber-500/10 text-amber-600 flex size-10 items-center justify-center rounded-xl"><RefreshCw class="size-5" /></div>
                            <div>
                                <p class="text-muted-foreground text-xs">Penyesuaian</p>
                                <p class="text-xl font-bold">{{ formatNumber(ms.total_penyesuaian) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>

        <!-- Tab Switch -->
        <div class="mb-4 flex gap-2">
            <Button :variant="activeTab === 'kartu' ? 'default' : 'outline'" size="sm" @click="switchTab('kartu')">
                <Boxes class="size-4" /> Kartu Stok
            </Button>
            <Button :variant="activeTab === 'mutasi' ? 'default' : 'outline'" size="sm" @click="switchTab('mutasi')">
                <RefreshCw class="size-4" /> Mutasi Stok
            </Button>
        </div>

        <!-- KARTU STOK TABLE -->
        <Card v-if="activeTab === 'kartu'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base"><Boxes class="text-primary size-5" /> Kartu Stok</CardTitle>
                <CardDescription>Kondisi stok per produk per toko saat ini</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <template v-if="kartuItems.length">
                        <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                            <table class="w-full min-w-[900px] border-separate border-spacing-0 text-sm">
                                <thead class="sticky top-0 z-10"><tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Kode</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Nama Produk</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Toko</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-center text-xs font-semibold uppercase">Stok Saat Ini</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-center text-xs font-semibold uppercase">Stok Minimum</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Harga Beli</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Nilai Stok</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Status</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="item in kartuItems" :key="item.id" class="transition-colors hover:bg-muted/40">
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.produk?.kode_barang || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5 font-medium">{{ item.produk?.nama_barang || '-' }}</td>
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-muted inline-block rounded px-1.5 py-0.5 text-xs font-medium">{{ item.store?.nama || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-center text-lg font-bold" :class="item.stok === 0 ? 'text-red-600' : item.stok <= item.stok_minimum ? 'text-amber-600' : ''">
                                            {{ formatNumber(item.stok) }}
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-center text-muted-foreground">{{ formatNumber(item.stok_minimum) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(item.produk?.harga_beli) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right font-semibold">{{ formatRupiah(item.stok * (item.produk?.harga_beli || 0)) }}</td>
                                        <td class="border-b px-3 py-2.5">
                                            <Badge :variant="getStokBadge(item.stok, item.stok_minimum)">
                                                {{ getStokLabel(item.stok, item.stok_minimum) }}
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="kartuPag.last > 1" class="flex items-center justify-between border-t px-4 py-3">
                            <p class="text-muted-foreground text-sm">Halaman {{ kartuPag.current }} dari {{ kartuPag.last }} ({{ kartuPag.total }} data)</p>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" :disabled="kartuPag.current <= 1" @click="goToPage(kartuPag.current - 1)">Sebelumnya</Button>
                                <Button variant="outline" size="sm" :disabled="kartuPag.current >= kartuPag.last" @click="goToPage(kartuPag.current + 1)">Berikutnya</Button>
                            </div>
                        </div>
                    </template>
                    <div v-else class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <div class="bg-muted flex size-14 items-center justify-center rounded-full"><Boxes class="text-muted-foreground size-7" /></div>
                        <p class="font-semibold">Belum ada data stok</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- MUTASI STOK TABLE -->
        <Card v-if="activeTab === 'mutasi'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base"><RefreshCw class="text-primary size-5" /> Mutasi Stok</CardTitle>
                <CardDescription>Riwayat pergerakan stok masuk, keluar, dan penyesuaian periode {{ formatDate(dari) }} — {{ formatDate(sampai) }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <template v-if="mutasiItems.length">
                        <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                            <table class="w-full min-w-[900px] border-separate border-spacing-0 text-sm">
                                <thead class="sticky top-0 z-10"><tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Tanggal</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Kode</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Produk</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Toko</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-center text-xs font-semibold uppercase">Tipe</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Qty</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Keterangan</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="item in mutasiItems" :key="item.id" class="transition-colors hover:bg-muted/40">
                                        <td class="border-b px-3 py-2.5">{{ formatDate(item.tanggal) }}</td>
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.produk?.kode_barang || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5 font-medium">{{ item.produk?.nama_barang || '-' }}</td>
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-muted inline-block rounded px-1.5 py-0.5 text-xs font-medium">{{ item.store?.nama || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-center">
                                            <Badge :variant="getTipeBadge(item.tipe)">
                                                <ArrowDownCircle v-if="item.tipe === 'masuk'" class="mr-1 size-3" />
                                                <ArrowUpCircle v-else-if="item.tipe === 'keluar'" class="mr-1 size-3" />
                                                <RefreshCw v-else class="mr-1 size-3" />
                                                {{ item.tipe === 'masuk' ? 'Masuk' : item.tipe === 'keluar' ? 'Keluar' : 'Penyesuaian' }}
                                            </Badge>
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-right text-lg font-bold"
                                            :class="item.tipe === 'masuk' ? 'text-emerald-600' : item.tipe === 'keluar' ? 'text-red-600' : 'text-amber-600'">
                                            {{ item.tipe === 'masuk' ? '+' : item.tipe === 'keluar' ? '-' : item.qty >= 0 ? '+' : '' }}{{ formatNumber(Math.abs(item.qty)) }}
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-muted-foreground">{{ item.keterangan || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="mutasiPag.last > 1" class="flex items-center justify-between border-t px-4 py-3">
                            <p class="text-muted-foreground text-sm">Halaman {{ mutasiPag.current }} dari {{ mutasiPag.last }} ({{ mutasiPag.total }} data)</p>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" :disabled="mutasiPag.current <= 1" @click="goToPage(mutasiPag.current - 1)">Sebelumnya</Button>
                                <Button variant="outline" size="sm" :disabled="mutasiPag.current >= mutasiPag.last" @click="goToPage(mutasiPag.current + 1)">Berikutnya</Button>
                            </div>
                        </div>
                    </template>
                    <div v-else class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <div class="bg-muted flex size-14 items-center justify-center rounded-full"><RefreshCw class="text-muted-foreground size-7" /></div>
                        <p class="font-semibold">Belum ada mutasi stok pada periode ini</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
