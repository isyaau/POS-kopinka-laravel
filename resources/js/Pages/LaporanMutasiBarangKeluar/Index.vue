<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    ArrowUpCircle, Package, CalendarRange, Store, Search, Hash,
} from 'lucide-vue-next'

const props = defineProps({
    totals: { type: Object, default: () => ({}) },
    perProduk: { type: Array, default: () => [] },
    perToko: { type: Array, default: () => [] },
    detailTransaksi: { type: Object, default: () => ({ data: [] }) },
    produks: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const dari = ref(props.filters.dari || '')
const sampai = ref(props.filters.sampai || '')
const produkId = ref(props.filters.produk_id || 'semua')
const tokoId = ref(props.filters.toko_id || 'semua')
const search = ref(props.filters.search || '')

const formatNumber = (val) => Number(val || 0).toLocaleString('id-ID')
const formatDate = (val) => {
    if (!val) return '-'
    const d = new Date(val + 'T00:00:00')
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const buildParams = (overrides = {}) => ({
    dari: dari.value, sampai: sampai.value,
    produk_id: produkId.value === 'semua' ? undefined : produkId.value,
    toko_id: tokoId.value === 'semua' ? undefined : tokoId.value,
    search: search.value || undefined,
    ...overrides,
})

const applyFilter = () => {
    router.get('/laporan-mutasi-barang-keluar', buildParams(), { preserveState: true, replace: true })
}

const resetFilter = () => {
    search.value = ''
    dari.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10)
    sampai.value = new Date().toISOString().slice(0, 10)
    produkId.value = 'semua'
    tokoId.value = 'semua'
    router.get('/laporan-mutasi-barang-keluar', buildParams(), { preserveState: true, replace: true })
}

const goToPage = (page) => {
    router.get('/laporan-mutasi-barang-keluar', buildParams({ page }), { preserveState: true, replace: true })
}

const t = computed(() => props.totals || {})
const items = computed(() => props.detailTransaksi?.data || [])
const pagination = computed(() => ({
    current: props.detailTransaksi?.current_page || 1,
    last: props.detailTransaksi?.last_page || 1,
    total: props.detailTransaksi?.total || 0,
}))
</script>

<template>
    <AppLayout>
        <Head title="Laporan Mutasi Barang Keluar - Kopinka" />

        <PageHeader title="Laporan Mutasi Barang Keluar" description="Daftar seluruh barang keluar (stok keluar) dari semua sumber pada periode tertentu." />

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
                <p class="text-muted-foreground text-sm">Periode: {{ formatDate(dari) }} — {{ formatDate(sampai) }}</p>
            </CardContent>
        </Card>

        <!-- Summary Cards -->
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <Card class="ring-2 ring-red-500/20">
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-500/10 text-red-600 flex size-10 items-center justify-center rounded-xl"><ArrowUpCircle class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Total Barang Keluar</p>
                            <p class="text-xl font-bold text-red-600">-{{ formatNumber(t.total_qty) }} <span class="text-muted-foreground text-xs">unit</span></p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl"><Hash class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Jumlah Transaksi</p>
                            <p class="text-xl font-bold">{{ formatNumber(t.jumlah_transaksi) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-500/10 text-blue-600 flex size-10 items-center justify-center rounded-xl"><Package class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Produk Keluar</p>
                            <p class="text-xl font-bold">{{ formatNumber(perProduk.length) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Rekap -->
        <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <!-- Per Produk -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"><Package class="text-primary size-5" /> Rekap per Produk</CardTitle>
                    <CardDescription>Total qty keluar per produk</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="max-h-80 overflow-y-auto">
                        <table v-if="perProduk.length" class="w-full border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0"><tr>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Kode</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Produk</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Jumlah Trx</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Total Qty</th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="p in perProduk" :key="p.produk_id ?? 'none'" class="hover:bg-muted/40">
                                    <td class="border-b px-3 py-2.5">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ p.kode_produk }}</span>
                                    </td>
                                    <td class="border-b px-3 py-2.5 font-medium">{{ p.nama_produk }}</td>
                                    <td class="border-b px-3 py-2.5 text-center">{{ p.jumlah }}</td>
                                    <td class="border-b px-3 py-2.5 text-right font-bold text-red-600">-{{ formatNumber(p.total_qty) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-muted-foreground py-4 text-center text-sm">Tidak ada data</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Per Toko -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"><Store class="text-primary size-5" /> Rekap per Toko</CardTitle>
                    <CardDescription>Total qty keluar per toko</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="max-h-80 overflow-y-auto">
                        <table v-if="perToko.length" class="w-full border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0"><tr>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Toko</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Jumlah Trx</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Total Qty</th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="st in perToko" :key="st.store_id ?? 'none'" class="hover:bg-muted/40">
                                    <td class="border-b px-3 py-2.5 font-medium">{{ st.nama_toko }}</td>
                                    <td class="border-b px-3 py-2.5 text-center">{{ st.jumlah }}</td>
                                    <td class="border-b px-3 py-2.5 text-right font-bold text-red-600">-{{ formatNumber(st.total_qty) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-muted-foreground py-4 text-center text-sm">Tidak ada data</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Detail Transaksi -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base"><ArrowUpCircle class="text-red-600 size-5" /> Detail Barang Keluar</CardTitle>
                <CardDescription>Seluruh stok keluar pada periode {{ formatDate(dari) }} — {{ formatDate(sampai) }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <template v-if="items.length">
                        <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                            <table class="w-full min-w-[850px] border-separate border-spacing-0 text-sm">
                                <thead class="sticky top-0 z-10"><tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Tanggal</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Kode</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Produk</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Toko</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Qty Keluar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Keterangan</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-muted/40">
                                        <td class="border-b px-3 py-2.5">{{ formatDate(item.tanggal) }}</td>
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.produk?.kode_barang || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5 font-medium">{{ item.produk?.nama_barang || '-' }}</td>
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-muted inline-block rounded px-1.5 py-0.5 text-xs font-medium">{{ item.store?.nama || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-right text-lg font-bold text-red-600">-{{ formatNumber(item.qty) }}</td>
                                        <td class="border-b px-3 py-2.5 text-muted-foreground">{{ item.keterangan || '-' }}</td>
                                    </tr>
                                    <!-- TOTAL -->
                                    <tr class="bg-muted/60 font-bold">
                                        <td colspan="4" class="border-b px-3 py-2.5 text-right">TOTAL</td>
                                        <td class="border-b px-3 py-2.5 text-right text-red-600">-{{ formatNumber(t.total_qty) }}</td>
                                        <td class="border-b px-3 py-2.5"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="pagination.last > 1" class="flex items-center justify-between border-t px-4 py-3">
                            <p class="text-muted-foreground text-sm">Halaman {{ pagination.current }} dari {{ pagination.last }} ({{ pagination.total }} data)</p>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" :disabled="pagination.current <= 1" @click="goToPage(pagination.current - 1)">Sebelumnya</Button>
                                <Button variant="outline" size="sm" :disabled="pagination.current >= pagination.last" @click="goToPage(pagination.current + 1)">Berikutnya</Button>
                            </div>
                        </div>
                    </template>

                    <div v-else class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <div class="bg-muted flex size-14 items-center justify-center rounded-full"><Package class="text-muted-foreground size-7" /></div>
                        <div>
                            <p class="font-semibold">Belum ada barang keluar</p>
                            <p class="text-muted-foreground text-sm">Tidak ada stok keluar pada periode ini.</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
