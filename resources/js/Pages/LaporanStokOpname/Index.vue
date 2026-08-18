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
    ClipboardCheck, CheckCircle2, Clock, XCircle, CalendarRange,
    Store, Search, Package, AlertTriangle, Hash, ArrowRightLeft,
} from 'lucide-vue-next'

const props = defineProps({
    totals: { type: Object, default: () => ({}) },
    detailTotals: { type: Object, default: () => ({}) },
    perToko: { type: Array, default: () => [] },
    detailItems: { type: Object, default: () => ({ data: [] }) },
    stores: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const dari = ref(props.filters.dari || '')
const sampai = ref(props.filters.sampai || '')
const status = ref(props.filters.status || 'semua')
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
    status: status.value === 'semua' ? undefined : status.value,
    toko_id: tokoId.value === 'semua' ? undefined : tokoId.value,
    search: search.value || undefined,
    ...overrides,
})

const applyFilter = () => {
    router.get('/laporan-stok-opname', buildParams(), { preserveState: true, replace: true })
}

const resetFilter = () => {
    search.value = ''
    dari.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10)
    sampai.value = new Date().toISOString().slice(0, 10)
    status.value = 'semua'
    tokoId.value = 'semua'
    router.get('/laporan-stok-opname', buildParams(), { preserveState: true, replace: true })
}

const goToPage = (page) => {
    router.get('/laporan-stok-opname', buildParams({ page }), { preserveState: true, replace: true })
}

const t = computed(() => props.totals || {})
const dt = computed(() => props.detailTotals || {})
const items = computed(() => props.detailItems?.data || [])
const pagination = computed(() => ({
    current: props.detailItems?.current_page || 1,
    last: props.detailItems?.last_page || 1,
    total: props.detailItems?.total || 0,
}))

const getStatusBadge = (s) => {
    if (s === 'selesai') return 'default'
    if (s === 'draft') return 'secondary'
    return 'destructive'
}
const getStatusLabel = (s) => {
    if (s === 'selesai') return 'Selesai'
    if (s === 'draft') return 'Draft'
    return 'Batal'
}
</script>

<template>
    <AppLayout>
        <Head title="Laporan Stok Opname - Kopinka" />

        <PageHeader title="Laporan Stok Opname Barang Persediaan" description="Ringkasan dan detail hasil stok opname (hitung fisik) barang persediaan." />

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
                        <label class="text-muted-foreground text-xs font-medium">Status</label>
                        <Select v-model="status">
                            <SelectTrigger class="w-36"><SelectValue placeholder="Semua" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Status</SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="selesai">Selesai</SelectItem>
                                <SelectItem value="batal">Batal</SelectItem>
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
                            <Input v-model="search" placeholder="Nama barang..." class="w-48 pl-8" @keyup.enter="applyFilter" />
                        </div>
                    </div>
                    <Button size="sm" class="h-9" @click="applyFilter"><CalendarRange class="size-4" /> Terapkan</Button>
                    <Button size="sm" variant="ghost" class="h-9" @click="resetFilter">Reset</Button>
                </div>
                <p class="text-muted-foreground text-sm">Periode: {{ formatDate(dari) }} — {{ formatDate(sampai) }}</p>
            </CardContent>
        </Card>

        <!-- Summary Cards: Opname Headers -->
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl"><ClipboardCheck class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Total Opname</p>
                            <p class="text-xl font-bold">{{ formatNumber(t.jumlah_opname) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-500/10 text-emerald-600 flex size-10 items-center justify-center rounded-xl"><CheckCircle2 class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Selesai</p>
                            <p class="text-xl font-bold text-emerald-600">{{ formatNumber(t.selesai) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-amber-500/10 text-amber-600 flex size-10 items-center justify-center rounded-xl"><Clock class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Draft</p>
                            <p class="text-xl font-bold text-amber-600">{{ formatNumber(t.draft) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-500/10 text-red-600 flex size-10 items-center justify-center rounded-xl"><XCircle class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Batal</p>
                            <p class="text-xl font-bold text-red-600">{{ formatNumber(t.batal) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Summary Cards: Detail Items (selisih) -->
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl"><Package class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Item Diopname</p>
                            <p class="text-xl font-bold">{{ formatNumber(dt.jumlah_item) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-500/10 text-blue-600 flex size-10 items-center justify-center rounded-xl"><ArrowRightLeft class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Stok Sistem → Fisik</p>
                            <p class="text-lg font-bold">{{ formatNumber(dt.total_sistem) }} → {{ formatNumber(dt.total_fisik) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card class="ring-2 ring-amber-500/30">
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-amber-500/10 text-amber-600 flex size-10 items-center justify-center rounded-xl"><AlertTriangle class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Lebih (Sistem &gt; Fisik)</p>
                            <p class="text-lg font-bold text-amber-600">+{{ formatNumber(dt.selisih_lebih) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card class="ring-2 ring-red-500/30">
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-500/10 text-red-600 flex size-10 items-center justify-center rounded-xl"><AlertTriangle class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Kurang (Sistem &lt; Fisik)</p>
                            <p class="text-lg font-bold text-red-600">-{{ formatNumber(dt.selisih_kurang) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Rekap per Toko -->
        <Card class="mb-4">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base"><Store class="text-primary size-5" /> Rekap Opname per Toko</CardTitle>
                <CardDescription>Jumlah opname per toko pada periode ini</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="max-h-60 overflow-y-auto">
                    <table v-if="perToko.length" class="w-full border-separate border-spacing-0 text-sm">
                        <thead class="sticky top-0"><tr>
                            <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Toko</th>
                            <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Total Opname</th>
                            <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Selesai</th>
                            <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Draft</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="st in perToko" :key="st.store_id ?? 'none'" class="hover:bg-muted/40">
                                <td class="border-b px-3 py-2.5 font-medium">{{ st.nama_toko }}</td>
                                <td class="border-b px-3 py-2.5 text-center font-semibold">{{ formatNumber(st.jumlah) }}</td>
                                <td class="border-b px-3 py-2.5 text-center text-emerald-600 font-semibold">{{ formatNumber(st.selesai) }}</td>
                                <td class="border-b px-3 py-2.5 text-center text-amber-600 font-semibold">{{ formatNumber(st.draft) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-muted-foreground py-4 text-center text-sm">Tidak ada data</p>
                </div>
            </CardContent>
        </Card>

        <!-- Detail Items -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base"><ClipboardCheck class="text-primary size-5" /> Detail Hasil Stok Opname</CardTitle>
                <CardDescription>Seluruh item yang diopname pada periode {{ formatDate(dari) }} — {{ formatDate(sampai) }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <template v-if="items.length">
                        <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                            <table class="w-full min-w-[950px] border-separate border-spacing-0 text-sm">
                                <thead class="sticky top-0 z-10"><tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">No. Opname</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Tanggal</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Toko</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Status</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Nama Barang</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Stok Sistem</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Stok Fisik</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Selisih</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-muted/40">
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.stok_opname?.no_opname || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5">{{ formatDate(item.stok_opname?.tanggal) }}</td>
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-muted inline-block rounded px-1.5 py-0.5 text-xs font-medium">{{ item.stok_opname?.store?.nama || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5">
                                            <Badge :variant="getStatusBadge(item.stok_opname?.status)">{{ getStatusLabel(item.stok_opname?.status) }}</Badge>
                                        </td>
                                        <td class="border-b px-3 py-2.5 font-medium">{{ item.nama_barang || '-' }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatNumber(item.stok_sistem) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right font-semibold">{{ formatNumber(item.stok_fisik) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right font-bold"
                                            :class="item.selisih > 0 ? 'text-amber-600' : item.selisih < 0 ? 'text-red-600' : 'text-emerald-600'">
                                            {{ item.selisih > 0 ? '+' : '' }}{{ formatNumber(item.selisih) }}
                                        </td>
                                    </tr>

                                    <!-- TOTAL row -->
                                    <tr class="bg-muted/60 font-bold">
                                        <td colspan="5" class="border-b px-3 py-2.5 text-right">TOTAL</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatNumber(dt.total_sistem) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatNumber(dt.total_fisik) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right"
                                            :class="dt.total_selisih > 0 ? 'text-amber-600' : dt.total_selisih < 0 ? 'text-red-600' : 'text-emerald-600'">
                                            {{ dt.total_selisih > 0 ? '+' : '' }}{{ formatNumber(dt.total_selisih) }}
                                        </td>
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
                        <div class="bg-muted flex size-14 items-center justify-center rounded-full"><ClipboardCheck class="text-muted-foreground size-7" /></div>
                        <div>
                            <p class="font-semibold">Belum ada data stok opname</p>
                            <p class="text-muted-foreground text-sm">Tidak ada opname pada periode ini.</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
