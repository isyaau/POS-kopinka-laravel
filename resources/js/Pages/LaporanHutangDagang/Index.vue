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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    FileBarChart,
    Wallet,
    AlertTriangle,
    CheckCircle2,
    Clock,
    CalendarRange,
    Package,
    Truck,
    Store,
    Receipt,
    Banknote,
} from 'lucide-vue-next'

const props = defineProps({
    totals: { type: Object, default: () => ({}) },
    perSupplier: { type: Array, default: () => [] },
    perToko: { type: Array, default: () => [] },
    detailTransaksi: { type: Object, default: () => ({ data: [] }) },
    hutangKredit: { type: Array, default: () => [] },
    totalHutangKredit: { type: Number, default: 0 },
    suppliers: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const dari = ref(props.filters.dari || '')
const sampai = ref(props.filters.sampai || '')
const supplierId = ref(props.filters.supplier_id || 'semua')
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

const applyFilter = () => {
    router.get('/laporan-hutang-dagang', {
        dari: dari.value, sampai: sampai.value,
        supplier_id: supplierId.value === 'semua' ? undefined : supplierId.value,
        toko_id: tokoId.value === 'semua' ? undefined : tokoId.value,
    }, { preserveState: true, replace: true })
}

const resetFilter = () => {
    dari.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10)
    sampai.value = new Date().toISOString().slice(0, 10)
    supplierId.value = 'semua'
    tokoId.value = 'semua'
    router.get('/laporan-hutang-dagang', { dari: dari.value, sampai: sampai.value }, { preserveState: true, replace: true })
}

const getLunasBadge = (sisa) => Number(sisa) > 0 ? 'destructive' : 'default'

const t = computed(() => props.totals || {})
const items = computed(() => props.detailTransaksi?.data || [])
const pagination = computed(() => {
    const a = props.detailTransaksi || {}
    return { current: a.current_page || 1, last: a.last_page || 1, total: a.total || 0 }
})
</script>

<template>
    <AppLayout>
        <Head title="Laporan Hutang Dagang - Kopinka" />

        <PageHeader title="Laporan Hutang Dagang" description="Ringkasan dan detail hutang supplier dari pembelian kredit seluruh toko." />

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
                        <label class="text-muted-foreground text-xs font-medium">Supplier</label>
                        <Select v-model="supplierId">
                            <SelectTrigger class="w-48"><SelectValue placeholder="Semua Supplier" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Supplier</SelectItem>
                                <SelectItem v-for="s in suppliers" :key="s.id" :value="String(s.id)">{{ s.nama }}</SelectItem>
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
                    <Button size="sm" class="h-9" @click="applyFilter"><CalendarRange class="size-4" /> Terapkan</Button>
                    <Button size="sm" variant="ghost" class="h-9" @click="resetFilter">Reset</Button>
                </div>
                <p class="text-muted-foreground text-sm">Periode: {{ formatDate(dari) }} — {{ formatDate(sampai) }}</p>
            </CardContent>
        </Card>

        <!-- Summary Cards -->
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl"><FileBarChart class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Total Transaksi</p>
                            <p class="text-xl font-bold">{{ formatNumber(t.jumlah_transaksi) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-500/10 text-blue-600 flex size-10 items-center justify-center rounded-xl"><Receipt class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Nilai Pembelian</p>
                            <p class="text-lg font-bold">{{ formatRupiah(t.total_nilai) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-500/10 text-emerald-600 flex size-10 items-center justify-center rounded-xl"><CheckCircle2 class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Sudah Dibayar</p>
                            <p class="text-lg font-bold">{{ formatRupiah(t.total_terbayar) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-amber-500/10 text-amber-600 flex size-10 items-center justify-center rounded-xl"><Banknote class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Kurang Bayar</p>
                            <p class="text-lg font-bold">{{ formatRupiah(t.total_kurang_bayar) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card class="ring-2 ring-red-500/30">
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-500/10 text-red-600 flex size-10 items-center justify-center rounded-xl"><AlertTriangle class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Total Sisa Hutang</p>
                            <p class="text-lg font-bold text-red-600">{{ formatRupiah(t.total_sisa_hutang) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-rose-500/10 text-rose-600 flex size-10 items-center justify-center rounded-xl"><Clock class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Belum Lunas</p>
                            <p class="text-xl font-bold">{{ formatNumber(t.jumlah_belum_lunas) }} <span class="text-muted-foreground text-xs">transaksi</span></p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Hutang Kredit dari Pembelian -->
        <Card class="mb-4 ring-2 ring-amber-500/20">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <AlertTriangle class="text-amber-500 size-5" />
                    Pembelian Kredit Belum Dibayar
                </CardTitle>
                <CardDescription>Pembelian dengan jenis bayar kredit yang masih memiliki sisa hutang</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <template v-if="hutangKredit.length">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-muted-foreground text-sm">{{ hutangKredit.length }} transaksi kredit aktif</p>
                            <p class="text-base font-bold text-red-600">Total: {{ formatRupiah(totalHutangKredit) }}</p>
                        </div>
                        <div class="max-h-60 overflow-auto overscroll-contain">
                            <table class="w-full min-w-[800px] border-separate border-spacing-0 text-sm">
                                <thead class="sticky top-0 z-10">
                                    <tr>
                                        <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">No. Pembelian</th>
                                        <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Tanggal</th>
                                        <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Supplier</th>
                                        <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Total</th>
                                        <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Sisa Hutang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in hutangKredit" :key="item.id" class="hover:bg-muted/40">
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.no_pembelian }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5">{{ formatDate(item.tanggal) }}</td>
                                        <td class="border-b px-3 py-2.5">{{ item.nama_supplier || '-' }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(item.total) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right font-bold text-red-600">{{ formatRupiah(item.sisa_hutang) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <div v-else class="py-8 text-center">
                        <p class="text-muted-foreground text-sm">Tidak ada pembelian kredit yang belum dibayar 🎉</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Rekap -->
        <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <!-- Per Supplier -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"><Truck class="text-primary size-5" /> Rekap per Supplier</CardTitle>
                    <CardDescription>Sisa hutang dari masing-masing supplier</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="max-h-80 overflow-y-auto">
                        <table v-if="perSupplier.length" class="w-full border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0"><tr>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Supplier</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Transaksi</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Harus Bayar</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Terbayar</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Sisa Hutang</th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="s in perSupplier" :key="s.supplier_id ?? 'none'" class="hover:bg-muted/40">
                                    <td class="border-b px-3 py-2.5 font-medium">{{ s.nama_supplier }}</td>
                                    <td class="border-b px-3 py-2.5 text-center">{{ s.jumlah }}</td>
                                    <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(s.harus_bayar) }}</td>
                                    <td class="border-b px-3 py-2.5 text-right text-emerald-600">{{ formatRupiah(s.terbayar) }}</td>
                                    <td class="border-b px-3 py-2.5 text-right font-bold text-red-600">{{ formatRupiah(s.sisa_hutang) }}</td>
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
                    <CardDescription>Sisa hutang dari masing-masing toko</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="max-h-80 overflow-y-auto">
                        <table v-if="perToko.length" class="w-full border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0"><tr>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Toko</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Transaksi</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Harus Bayar</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Terbayar</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Sisa Hutang</th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="st in perToko" :key="st.store_id ?? 'none'" class="hover:bg-muted/40">
                                    <td class="border-b px-3 py-2.5 font-medium">{{ st.nama_toko }}</td>
                                    <td class="border-b px-3 py-2.5 text-center">{{ st.jumlah }}</td>
                                    <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(st.harus_bayar) }}</td>
                                    <td class="border-b px-3 py-2.5 text-right text-emerald-600">{{ formatRupiah(st.terbayar) }}</td>
                                    <td class="border-b px-3 py-2.5 text-right font-bold text-red-600">{{ formatRupiah(st.sisa_hutang) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-muted-foreground py-4 text-center text-sm">Tidak ada data</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Detail Transaksi Pembayaran Hutang -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base"><Wallet class="text-primary size-5" /> Detail Pembayaran Hutang</CardTitle>
                <CardDescription>Semua transaksi pembayaran hutang pada periode {{ formatDate(dari) }} — {{ formatDate(sampai) }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <template v-if="items.length">
                        <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                            <table class="w-full min-w-[1100px] border-separate border-spacing-0 text-sm">
                                <thead class="sticky top-0 z-10"><tr>
                                    <th class="bg-muted text-muted-foreground sticky left-0 z-10 border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">No. Transaksi</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">No. Faktur</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Supplier</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Tgl Beli</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Jatuh Tempo</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Harus Bayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Terbayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Diskon</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Kurang Bayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Sisa Hutang</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Status</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-muted/40">
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.no_transaksi }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-muted-foreground">{{ item.no_faktur || '-' }}</td>
                                        <td class="border-b px-3 py-2.5">{{ item.nama_supplier || '-' }}</td>
                                        <td class="border-b px-3 py-2.5">{{ formatDate(item.tgl_pembelian) }}</td>
                                        <td class="border-b px-3 py-2.5">{{ formatDate(item.tgl_jatuh_tempo) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(item.total_harus_dibayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-emerald-600">{{ formatRupiah(item.total_terbayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-muted-foreground">{{ formatRupiah(item.total_diskon) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(item.kurang_bayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">
                                            <span v-if="Number(item.sisa_hutang) > 0" class="font-bold text-red-600">{{ formatRupiah(item.sisa_hutang) }}</span>
                                            <span v-else class="text-emerald-600 font-semibold">Lunas</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5">
                                            <Badge :variant="getLunasBadge(item.sisa_hutang)">
                                                {{ Number(item.sisa_hutang) > 0 ? 'Belum Lunas' : 'Lunas' }}
                                            </Badge>
                                        </td>
                                    </tr>

                                    <!-- Summary row -->
                                    <tr class="bg-muted/60 font-bold">
                                        <td colspan="5" class="border-b px-3 py-2.5 text-right">TOTAL</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(t.total_harus_dibayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-emerald-600">{{ formatRupiah(t.total_terbayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-muted-foreground">{{ formatRupiah(t.total_diskon) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(t.total_kurang_bayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-red-600">{{ formatRupiah(t.total_sisa_hutang) }}</td>
                                        <td class="border-b px-3 py-2.5"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="pagination.last > 1" class="flex items-center justify-between border-t px-4 py-3">
                            <p class="text-muted-foreground text-sm">Halaman {{ pagination.current }} dari {{ pagination.last }} ({{ pagination.total }} data)</p>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" :disabled="pagination.current <= 1"
                                    @click="router.get('/laporan-hutang-dagang', { ...filters, page: pagination.current - 1 }, { preserveState: true, replace: true })">Sebelumnya</Button>
                                <Button variant="outline" size="sm" :disabled="pagination.current >= pagination.last"
                                    @click="router.get('/laporan-hutang-dagang', { ...filters, page: pagination.current + 1 }, { preserveState: true, replace: true })">Berikutnya</Button>
                            </div>
                        </div>
                    </template>

                    <div v-else class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <div class="bg-muted flex size-14 items-center justify-center rounded-full"><Package class="text-muted-foreground size-7" /></div>
                        <div>
                            <p class="font-semibold">Belum ada data pembayaran hutang</p>
                            <p class="text-muted-foreground text-sm">Tidak ada transaksi hutang pada periode ini.</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
