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
    TrendingUp, TrendingDown, Package, Search, BarChart3,
    AlertTriangle, CheckCircle2, ArrowUpRight, MinusCircle,
} from 'lucide-vue-next'

const props = defineProps({
    totals: { type: Object, default: () => ({}) },
    perKategori: { type: Array, default: () => [] },
    detailProduk: { type: Array, default: () => [] },
    kategoris: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const kategori = ref(props.filters.kategori || 'semua')
const search = ref(props.filters.search || '')

const formatRupiah = (val) => {
    const n = Number(val || 0)
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n)
}
const formatNumber = (val) => Number(val || 0).toLocaleString('id-ID')
const formatPercent = (val) => `${Number(val || 0).toFixed(1)}%`

const applyFilter = () => {
    router.get('/laporan-margin-retail', {
        kategori: kategori.value === 'semua' ? undefined : kategori.value,
        search: search.value || undefined,
    }, { preserveState: true, replace: true })
}

const resetFilter = () => {
    kategori.value = 'semua'
    search.value = ''
    router.get('/laporan-margin-retail', {}, { preserveState: true, replace: true })
}

const t = computed(() => props.totals || {})

const getMarginBadge = (margin) => {
    if (Number(margin) < 0 || Number(margin) === 0) return { variant: 'destructive', label: 'Rugi' }
    if (Number(margin) < 10) return { variant: 'secondary', label: 'Rendah' }
    if (Number(margin) < 25) return { variant: 'default', label: 'Sedang' }
    return { variant: 'default', label: 'Tinggi' }
}
</script>

<template>
    <AppLayout>
        <Head title="Laporan Margin Retail - Kopinka" />

        <PageHeader title="Laporan Margin Retail" description="Analisis margin keuntungan (harga jual - harga beli) untuk setiap produk berdasarkan stok saat ini." />

        <!-- Filter -->
        <Card class="mb-4">
            <CardContent class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-wrap items-end gap-2">
                    <div class="grid gap-1.5">
                        <label class="text-muted-foreground text-xs font-medium">Kategori</label>
                        <Select v-model="kategori">
                            <SelectTrigger class="w-48"><SelectValue placeholder="Semua Kategori" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Kategori</SelectItem>
                                <SelectItem v-for="k in kategoris" :key="k" :value="k">{{ k }}</SelectItem>
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
                    <Button size="sm" class="h-9" @click="applyFilter">Terapkan</Button>
                    <Button size="sm" variant="ghost" class="h-9" @click="resetFilter">Reset</Button>
                </div>
            </CardContent>
        </Card>

        <!-- Summary Cards Row 1: Margin & Stok -->
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl"><Package class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Jumlah Produk</p>
                            <p class="text-xl font-bold">{{ formatNumber(t.jumlah_produk) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-500/10 text-blue-600 flex size-10 items-center justify-center rounded-xl"><TrendingUp class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Total Stok</p>
                            <p class="text-xl font-bold">{{ formatNumber(t.total_stok) }} <span class="text-muted-foreground text-xs">unit</span></p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-indigo-500/10 text-indigo-600 flex size-10 items-center justify-center rounded-xl"><BarChart3 class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Rata-rata Margin</p>
                            <p class="text-xl font-bold text-indigo-600">{{ formatPercent(t.avg_margin_persen) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-500/10 text-emerald-600 flex size-10 items-center justify-center rounded-xl"><TrendingUp class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Total Modal Stok</p>
                            <p class="text-lg font-bold">{{ formatRupiah(t.total_modal_stok) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card class="ring-2 ring-emerald-500/20">
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-500/10 text-emerald-600 flex size-10 items-center justify-center rounded-xl"><ArrowUpRight class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Total Margin Stok</p>
                            <p class="text-lg font-bold text-emerald-600">{{ formatRupiah(t.total_margin_stok) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Summary Cards Row 2: Category Distribution -->
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-500/10 text-emerald-600 flex size-10 items-center justify-center rounded-xl"><CheckCircle2 class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Margin Tinggi (&ge;25%)</p>
                            <p class="text-xl font-bold text-emerald-600">{{ formatNumber(t.tinggi_count) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-500/10 text-blue-600 flex size-10 items-center justify-center rounded-xl"><TrendingUp class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Margin Sedang (10-25%)</p>
                            <p class="text-xl font-bold text-blue-600">{{ formatNumber(t.sedang_count) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-amber-500/10 text-amber-600 flex size-10 items-center justify-center rounded-xl"><MinusCircle class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Margin Rendah (&lt;10%)</p>
                            <p class="text-xl font-bold text-amber-600">{{ formatNumber(t.rendah_count) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-500/10 text-red-600 flex size-10 items-center justify-center rounded-xl"><AlertTriangle class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Rugi (&#8804; Harga Beli)</p>
                            <p class="text-xl font-bold text-red-600">{{ formatNumber(t.rugi_count) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Rekap per Kategori -->
        <Card class="mb-4">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base"><BarChart3 class="text-primary size-5" /> Rekap Margin per Kategori</CardTitle>
                <CardDescription>Total margin stok per kategori produk</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="max-h-60 overflow-y-auto">
                    <table v-if="perKategori.length" class="w-full border-separate border-spacing-0 text-sm">
                        <thead class="sticky top-0"><tr>
                            <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Kategori</th>
                            <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Jumlah</th>
                            <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Stok</th>
                            <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Rata-rata Margin</th>
                            <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Total Modal</th>
                            <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Total Margin</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="k in perKategori" :key="k.kategori ?? 'none'" class="hover:bg-muted/40">
                                <td class="border-b px-3 py-2.5 font-medium">{{ k.kategori || 'Tanpa Kategori' }}</td>
                                <td class="border-b px-3 py-2.5 text-center">{{ k.jumlah }}</td>
                                <td class="border-b px-3 py-2.5 text-center">{{ formatNumber(k.total_stok) }}</td>
                                <td class="border-b px-3 py-2.5 text-center">
                                    <Badge :variant="Number(k.avg_margin) >= 25 ? 'default' : Number(k.avg_margin) >= 10 ? 'secondary' : Number(k.avg_margin) > 0 ? 'secondary' : 'destructive'">
                                        {{ formatPercent(k.avg_margin) }}
                                    </Badge>
                                </td>
                                <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(k.total_modal) }}</td>
                                <td class="border-b px-3 py-2.5 text-right font-bold text-emerald-600">{{ formatRupiah(k.total_margin) }}</td>
                            </tr>
                            <tr class="bg-muted/60 font-bold">
                                <td class="border-b px-3 py-2.5">TOTAL</td>
                                <td class="border-b px-3 py-2.5 text-center">{{ formatNumber(t.jumlah_produk) }}</td>
                                <td class="border-b px-3 py-2.5 text-center">{{ formatNumber(t.total_stok) }}</td>
                                <td class="border-b px-3 py-2.5 text-center text-indigo-600">{{ formatPercent(t.avg_margin_persen) }}</td>
                                <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(t.total_modal_stok) }}</td>
                                <td class="border-b px-3 py-2.5 text-right text-emerald-600">{{ formatRupiah(t.total_margin_stok) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-muted-foreground py-4 text-center text-sm">Tidak ada data</p>
                </div>
            </CardContent>
        </Card>

        <!-- Detail Produk -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base"><TrendingUp class="text-primary size-5" /> Detail Margin Produk</CardTitle>
                <CardDescription>Daftar produk berdasarkan margin tertinggi ke terendah</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <template v-if="detailProduk.length">
                        <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                            <table class="w-full min-w-[1100px] border-separate border-spacing-0 text-sm">
                                <thead class="sticky top-0 z-10"><tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Kode</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Nama Produk</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Kategori</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Satuan</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Harga Beli</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Harga Jual</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Margin (Rp)</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Margin (%)</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Stok</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Modal Stok</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Margin Stok</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-center text-xs font-semibold uppercase">Grade</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="item in detailProduk" :key="item.id" class="transition-colors hover:bg-muted/40">
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.kode_barang }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5 font-medium">{{ item.nama_barang }}</td>
                                        <td class="border-b px-3 py-2.5 text-muted-foreground text-xs">{{ item.kategori || '-' }}</td>
                                        <td class="border-b px-3 py-2.5 text-muted-foreground text-xs">{{ item.satuan || '-' }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(item.harga_beli) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right font-semibold">{{ formatRupiah(item.harga_jual) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right"
                                            :class="Number(item.margin_rupiah) > 0 ? 'font-bold text-emerald-600' : Number(item.margin_rupiah) < 0 ? 'font-bold text-red-600' : 'text-muted-foreground'">
                                            {{ formatRupiah(item.margin_rupiah) }}
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-right"
                                            :class="Number(item.margin_persen) > 0 ? 'font-bold text-emerald-600' : Number(item.margin_persen) < 0 ? 'font-bold text-red-600' : 'text-muted-foreground'">
                                            {{ formatPercent(item.margin_persen) }}
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-right font-semibold">{{ formatNumber(item.total_stok) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(item.modal_stok) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right font-bold"
                                            :class="Number(item.total_margin_stok) > 0 ? 'text-emerald-600' : Number(item.total_margin_stok) < 0 ? 'text-red-600' : 'text-muted-foreground'">
                                            {{ formatRupiah(item.total_margin_stok) }}
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-center">
                                            <Badge :variant="getMarginBadge(item.margin_persen).variant">
                                                {{ getMarginBadge(item.margin_persen).label }}
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between border-t px-4 py-3">
                            <p class="text-muted-foreground text-sm">Menampilkan {{ detailProduk.length }} produk (dari {{ formatNumber(t.jumlah_produk) }} total)</p>
                        </div>
                    </template>

                    <div v-else class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <div class="bg-muted flex size-14 items-center justify-center rounded-full"><TrendingUp class="text-muted-foreground size-7" /></div>
                        <div>
                            <p class="font-semibold">Belum ada data margin</p>
                            <p class="text-muted-foreground text-sm">Tidak ada produk yang ditemukan.</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
