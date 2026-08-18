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
    Users, Receipt, CheckCircle2, Clock, AlertTriangle,
    CalendarRange, Wallet, Store, CreditCard, TrendingDown,
} from 'lucide-vue-next'

const props = defineProps({
    transaksiTotals: { type: Object, default: () => ({}) },
    angsuranTotals: { type: Object, default: () => ({}) },
    perAnggota: { type: Array, default: () => [] },
    perToko: { type: Array, default: () => [] },
    detailTransaksi: { type: Object, default: () => ({ data: [] }) },
    detailAngsuran: { type: Object, default: () => ({ data: [] }) },
    anggotas: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const dari = ref(props.filters.dari || '')
const sampai = ref(props.filters.sampai || '')
const anggotaId = ref(props.filters.anggota_id || 'semua')
const tokoId = ref(props.filters.toko_id || 'semua')
const activeTab = ref('transaksi')

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
    router.get('/laporan-piutang-anggota', {
        dari: dari.value, sampai: sampai.value,
        anggota_id: anggotaId.value === 'semua' ? undefined : anggotaId.value,
        toko_id: tokoId.value === 'semua' ? undefined : tokoId.value,
    }, { preserveState: true, replace: true })
}

const resetFilter = () => {
    dari.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10)
    sampai.value = new Date().toISOString().slice(0, 10)
    anggotaId.value = 'semua'
    tokoId.value = 'semua'
    router.get('/laporan-piutang-anggota', { dari: dari.value, sampai: sampai.value }, { preserveState: true, replace: true })
}

const goToPage = (page) => {
    const base = activeTab.value === 'transaksi' ? '/laporan-piutang-anggota' : '/laporan-piutang-anggota'
    router.get(base, { ...props.filters, tab: activeTab.value, page }, { preserveState: true, replace: true })
}

const tt = computed(() => props.transaksiTotals || {})
const at = computed(() => props.angsuranTotals || {})
const txItems = computed(() => props.detailTransaksi?.data || [])
const anItems = computed(() => props.detailAngsuran?.data || [])
const txPag = computed(() => ({ current: props.detailTransaksi?.current_page || 1, last: props.detailTransaksi?.last_page || 1, total: props.detailTransaksi?.total || 0 }))
const anPag = computed(() => ({ current: props.detailAngsuran?.current_page || 1, last: props.detailAngsuran?.last_page || 1, total: props.detailAngsuran?.total || 0 }))
</script>

<template>
    <AppLayout>
        <Head title="Laporan Piutang Anggota - Kopinka" />

        <PageHeader title="Laporan Piutang Anggota" description="Ringkasan dan detail piutang anggota dari penjualan dan penerimaan angsuran." />

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
                        <label class="text-muted-foreground text-xs font-medium">Anggota</label>
                        <Select v-model="anggotaId">
                            <SelectTrigger class="w-48"><SelectValue placeholder="Semua Anggota" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Anggota</SelectItem>
                                <SelectItem v-for="a in anggotas" :key="a.id" :value="String(a.id)">{{ a.nama }}</SelectItem>
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
                        <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl"><Receipt class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Transaksi Piutang</p>
                            <p class="text-xl font-bold">{{ formatNumber(tt.jumlah) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-500/10 text-blue-600 flex size-10 items-center justify-center rounded-xl"><TrendingDown class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Total Penjualan</p>
                            <p class="text-lg font-bold">{{ formatRupiah(tt.total_penjualan) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card class="ring-2 ring-red-500/30">
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-500/10 text-red-600 flex size-10 items-center justify-center rounded-xl"><AlertTriangle class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Total Piutang</p>
                            <p class="text-lg font-bold text-red-600">{{ formatRupiah(tt.total_piutang) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-500/10 text-emerald-600 flex size-10 items-center justify-center rounded-xl"><CheckCircle2 class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Terbayar (Angsuran)</p>
                            <p class="text-lg font-bold">{{ formatRupiah(at.total_terbayar) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-amber-500/10 text-amber-600 flex size-10 items-center justify-center rounded-xl"><CreditCard class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Kurang Bayar</p>
                            <p class="text-lg font-bold">{{ formatRupiah(at.kurang_bayar) }}</p>
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
                            <p class="text-xl font-bold">{{ formatNumber(at.jumlah_belum_lunas) }} <span class="text-muted-foreground text-xs">angsuran</span></p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Rekap -->
        <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <!-- Per Anggota -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"><Users class="text-primary size-5" /> Rekap per Anggota</CardTitle>
                    <CardDescription>Piutang dari masing-masing anggota</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="max-h-80 overflow-y-auto">
                        <table v-if="perAnggota.length" class="w-full border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0"><tr>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Anggota</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Transaksi</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Penjualan</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Piutang</th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="a in perAnggota" :key="a.anggota_id ?? 'none'" class="hover:bg-muted/40">
                                    <td class="border-b px-3 py-2.5 font-medium">{{ a.nama_anggota }}</td>
                                    <td class="border-b px-3 py-2.5 text-center">{{ a.jumlah }}</td>
                                    <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(a.total_penjualan) }}</td>
                                    <td class="border-b px-3 py-2.5 text-right font-bold text-red-600">{{ formatRupiah(a.total_piutang) }}</td>
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
                    <CardDescription>Piutang dari masing-masing toko</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="max-h-80 overflow-y-auto">
                        <table v-if="perToko.length" class="w-full border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0"><tr>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Toko</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Transaksi</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Penjualan</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Piutang</th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="st in perToko" :key="st.store_id ?? 'none'" class="hover:bg-muted/40">
                                    <td class="border-b px-3 py-2.5 font-medium">{{ st.nama_toko }}</td>
                                    <td class="border-b px-3 py-2.5 text-center">{{ st.jumlah }}</td>
                                    <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(st.total_penjualan) }}</td>
                                    <td class="border-b px-3 py-2.5 text-right font-bold text-red-600">{{ formatRupiah(st.total_piutang) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-muted-foreground py-4 text-center text-sm">Tidak ada data</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Tab Switch -->
        <div class="mb-4 flex gap-2">
            <Button :variant="activeTab === 'transaksi' ? 'default' : 'outline'" size="sm" @click="activeTab = 'transaksi'">
                <Receipt class="size-4" /> Transaksi Piutang
            </Button>
            <Button :variant="activeTab === 'angsuran' ? 'default' : 'outline'" size="sm" @click="activeTab = 'angsuran'">
                <Wallet class="size-4" /> Penerimaan Angsuran
            </Button>
        </div>

        <!-- Detail Transaksi Piutang -->
        <Card v-if="activeTab === 'transaksi'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base"><Receipt class="text-primary size-5" /> Detail Transaksi Piutang</CardTitle>
                <CardDescription>Transaksi dengan piutang pada periode {{ formatDate(dari) }} — {{ formatDate(sampai) }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <template v-if="txItems.length">
                        <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                            <table class="w-full min-w-[800px] border-separate border-spacing-0 text-sm">
                                <thead class="sticky top-0 z-10"><tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">No. Nota</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Tanggal</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Anggota</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Toko</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Total Jual</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Piutang</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="item in txItems" :key="item.id" class="transition-colors hover:bg-muted/40">
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.no_nota }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5">{{ formatDate(item.tanggal) }}</td>
                                        <td class="border-b px-3 py-2.5">{{ item.nama_anggota || 'Umum' }}</td>
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-muted inline-block rounded px-1.5 py-0.5 text-xs font-medium">{{ item.store?.nama || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(item.jual) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right font-bold text-red-600">{{ formatRupiah(item.piutang) }}</td>
                                    </tr>
                                    <tr class="bg-muted/60 font-bold">
                                        <td colspan="4" class="border-b px-3 py-2.5 text-right">TOTAL</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(tt.total_penjualan) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-red-600">{{ formatRupiah(tt.total_piutang) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="txPag.last > 1" class="flex items-center justify-between border-t px-4 py-3">
                            <p class="text-muted-foreground text-sm">Halaman {{ txPag.current }} dari {{ txPag.last }} ({{ txPag.total }} data)</p>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" :disabled="txPag.current <= 1" @click="goToPage(txPag.current - 1)">Sebelumnya</Button>
                                <Button variant="outline" size="sm" :disabled="txPag.current >= txPag.last" @click="goToPage(txPag.current + 1)">Berikutnya</Button>
                            </div>
                        </div>
                    </template>
                    <div v-else class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <div class="bg-muted flex size-14 items-center justify-center rounded-full"><Receipt class="text-muted-foreground size-7" /></div>
                        <p class="font-semibold">Belum ada transaksi piutang pada periode ini</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Detail Angsuran -->
        <Card v-if="activeTab === 'angsuran'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base"><Wallet class="text-primary size-5" /> Detail Penerimaan Angsuran</CardTitle>
                <CardDescription>Penerimaan angsuran pada periode {{ formatDate(dari) }} — {{ formatDate(sampai) }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <template v-if="anItems.length">
                        <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                            <table class="w-full min-w-[1100px] border-separate border-spacing-0 text-sm">
                                <thead class="sticky top-0 z-10"><tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">No. Transaksi</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Tanggal</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Anggota</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Toko</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Harus Bayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Terbayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Diskon</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Kurang Bayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Sisa Piutang</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Status</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="item in anItems" :key="item.id" class="transition-colors hover:bg-muted/40">
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.no_transaksi }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5">{{ formatDate(item.tgl_transaksi) }}</td>
                                        <td class="border-b px-3 py-2.5">{{ item.nama_anggota || '-' }}</td>
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-muted inline-block rounded px-1.5 py-0.5 text-xs font-medium">{{ item.store?.nama || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(item.total_harus_dibayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-emerald-600">{{ formatRupiah(item.total_terbayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-muted-foreground">{{ formatRupiah(item.total_diskon) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(item.kurang_bayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right">
                                            <span v-if="Number(item.sisa_piutang) > 0" class="font-bold text-red-600">{{ formatRupiah(item.sisa_piutang) }}</span>
                                            <span v-else class="text-emerald-600 font-semibold">Lunas</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5">
                                            <Badge :variant="Number(item.sisa_piutang) > 0 ? 'destructive' : 'default'">
                                                {{ Number(item.sisa_piutang) > 0 ? 'Belum Lunas' : 'Lunas' }}
                                            </Badge>
                                        </td>
                                    </tr>
                                    <tr class="bg-muted/60 font-bold">
                                        <td colspan="4" class="border-b px-3 py-2.5 text-right">TOTAL</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(at.harus_bayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-emerald-600">{{ formatRupiah(at.total_terbayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-muted-foreground">-</td>
                                        <td class="border-b px-3 py-2.5 text-right">{{ formatRupiah(at.kurang_bayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-red-600">{{ formatRupiah(at.sisa_piutang) }}</td>
                                        <td class="border-b px-3 py-2.5"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="anPag.last > 1" class="flex items-center justify-between border-t px-4 py-3">
                            <p class="text-muted-foreground text-sm">Halaman {{ anPag.current }} dari {{ anPag.last }} ({{ anPag.total }} data)</p>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" :disabled="anPag.current <= 1" @click="goToPage(anPag.current - 1)">Sebelumnya</Button>
                                <Button variant="outline" size="sm" :disabled="anPag.current >= anPag.last" @click="goToPage(anPag.current + 1)">Berikutnya</Button>
                            </div>
                        </div>
                    </template>
                    <div v-else class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <div class="bg-muted flex size-14 items-center justify-center rounded-full"><Wallet class="text-muted-foreground size-7" /></div>
                        <p class="font-semibold">Belum ada penerimaan angsuran pada periode ini</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
