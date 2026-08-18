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
    RotateCcw, CalendarRange, Store, CheckCircle2, Clock, XCircle,
    Users, Banknote, ArrowRightCircle,
} from 'lucide-vue-next'

const props = defineProps({
    totals: { type: Object, default: () => ({}) },
    perAnggota: { type: Array, default: () => [] },
    perToko: { type: Array, default: () => [] },
    detailTransaksi: { type: Object, default: () => ({ data: [] }) },
    stores: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const dari = ref(props.filters.dari || '')
const sampai = ref(props.filters.sampai || '')
const tokoId = ref(props.filters.toko_id || 'semua')
const statusFilter = ref(props.filters.status || 'semua')

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
    router.get('/laporan-pengembalian-lebih-bayar-potong-gaji', {
        dari: dari.value, sampai: sampai.value,
        toko_id: tokoId.value === 'semua' ? undefined : tokoId.value,
        status: statusFilter.value === 'semua' ? undefined : statusFilter.value,
    }, { preserveState: true, replace: true })
}

const resetFilter = () => {
    dari.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10)
    sampai.value = new Date().toISOString().slice(0, 10)
    tokoId.value = 'semua'
    statusFilter.value = 'semua'
    router.get('/laporan-pengembalian-lebih-bayar-potong-gaji', { dari: dari.value, sampai: sampai.value }, { preserveState: true, replace: true })
}

const goToPage = (page) => {
    router.get('/laporan-pengembalian-lebih-bayar-potong-gaji', {
        dari: dari.value, sampai: sampai.value,
        toko_id: tokoId.value === 'semua' ? undefined : tokoId.value,
        status: statusFilter.value === 'semua' ? undefined : statusFilter.value,
        page,
    }, { preserveState: true, replace: true })
}

const t = computed(() => props.totals || {})
const items = computed(() => props.detailTransaksi?.data || [])
const pagination = computed(() => ({
    current: props.detailTransaksi?.current_page || 1,
    last: props.detailTransaksi?.last_page || 1,
    total: props.detailTransaksi?.total || 0,
}))

const getStatusBadge = (s) => {
    if (s === 'selesai') return 'default'
    if (s === 'pending') return 'secondary'
    return 'destructive'
}
const getStatusLabel = (s) => {
    if (s === 'selesai') return 'Selesai'
    if (s === 'pending') return 'Pending'
    return 'Dibatalkan'
}
</script>

<template>
    <AppLayout>
        <Head title="Laporan Pengembalian Lebih Bayar Potong Gaji - Kopinka" />

        <PageHeader title="Laporan Pengembalian Lebih Bayar Potong Gaji" description="Ringkasan dan detail pengembalian lebih bayar angsuran potong gaji anggota." />

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
                        <Select v-model="statusFilter">
                            <SelectTrigger class="w-36"><SelectValue placeholder="Semua" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Status</SelectItem>
                                <SelectItem value="selesai">Selesai</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="dibatalkan">Dibatalkan</SelectItem>
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
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl"><ArrowRightCircle class="size-5" /></div>
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
                        <div class="bg-amber-500/10 text-amber-600 flex size-10 items-center justify-center rounded-xl"><Banknote class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Total Lebih Bayar</p>
                            <p class="text-lg font-bold text-amber-600">{{ formatRupiah(t.total_lebih_bayar) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card class="ring-2 ring-emerald-500/20">
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-500/10 text-emerald-600 flex size-10 items-center justify-center rounded-xl"><RotateCcw class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Total Dikembalikan</p>
                            <p class="text-lg font-bold text-emerald-600">{{ formatRupiah(t.total_pengembalian) }}</p>
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
                            <p class="text-xl font-bold">{{ formatNumber(t.selesai) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-rose-500/10 text-rose-600 flex size-10 items-center justify-center rounded-xl"><XCircle class="size-5" /></div>
                        <div>
                            <p class="text-muted-foreground text-xs">Pending / Batal</p>
                            <p class="text-xl font-bold">{{ formatNumber(t.pending) }} / {{ formatNumber(t.dibatalkan) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Rekap per Anggota & per Toko -->
        <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <!-- Per Anggota -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"><Users class="text-primary size-5" /> Rekap per Anggota</CardTitle>
                    <CardDescription>Total pengembalian per anggota</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="max-h-80 overflow-y-auto">
                        <table v-if="perAnggota.length" class="w-full border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0"><tr>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Anggota</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Trx</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Lebih Bayar</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Dikembalikan</th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="a in perAnggota" :key="a.anggota_id ?? 'none'" class="hover:bg-muted/40">
                                    <td class="border-b px-3 py-2.5 font-medium">{{ a.nama_anggota }}</td>
                                    <td class="border-b px-3 py-2.5 text-center">{{ a.jumlah }}</td>
                                    <td class="border-b px-3 py-2.5 text-right text-amber-600">{{ formatRupiah(a.total_lebih_bayar) }}</td>
                                    <td class="border-b px-3 py-2.5 text-right font-bold text-emerald-600">{{ formatRupiah(a.total_pengembalian) }}</td>
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
                    <CardDescription>Total pengembalian per toko</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="max-h-80 overflow-y-auto">
                        <table v-if="perToko.length" class="w-full border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0"><tr>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Toko</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Trx</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Lebih Bayar</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Dikembalikan</th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="st in perToko" :key="st.store_id ?? 'none'" class="hover:bg-muted/40">
                                    <td class="border-b px-3 py-2.5 font-medium">{{ st.nama_toko }}</td>
                                    <td class="border-b px-3 py-2.5 text-center">{{ st.jumlah }}</td>
                                    <td class="border-b px-3 py-2.5 text-right text-amber-600">{{ formatRupiah(st.total_lebih_bayar) }}</td>
                                    <td class="border-b px-3 py-2.5 text-right font-bold text-emerald-600">{{ formatRupiah(st.total_pengembalian) }}</td>
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
                <CardTitle class="flex items-center gap-2 text-base"><RotateCcw class="text-primary size-5" /> Detail Pengembalian Lebih Bayar</CardTitle>
                <CardDescription>Seluruh pengembalian lebih bayar potong gaji pada periode {{ formatDate(dari) }} — {{ formatDate(sampai) }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <template v-if="items.length">
                        <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                            <table class="w-full min-w-[1100px] border-separate border-spacing-0 text-sm">
                                <thead class="sticky top-0 z-10"><tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">No. Transaksi</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Tgl Pengembalian</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">No. Register</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Anggota</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Unit / Jabatan</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Toko</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Lebih Bayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-right text-xs font-semibold uppercase">Dikembalikan</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Metode</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2.5 text-left text-xs font-semibold uppercase">Status</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-muted/40">
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.no_transaksi }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5">{{ formatDate(item.tgl_pengembalian) }}</td>
                                        <td class="border-b px-3 py-2.5 text-muted-foreground">{{ item.no_register_tagihan || '-' }}</td>
                                        <td class="border-b px-3 py-2.5">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ item.nama_anggota || '-' }}</span>
                                                <span class="text-muted-foreground text-xs">{{ item.kode_anggota || '' }}</span>
                                            </div>
                                        </td>
                                        <td class="border-b px-3 py-2.5">
                                            <div class="flex flex-col text-xs">
                                                <span>{{ item.unit_kerja || '-' }}</span>
                                                <span class="text-muted-foreground">{{ item.jabatan || '' }}</span>
                                            </div>
                                        </td>
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-muted inline-block rounded px-1.5 py-0.5 text-xs font-medium">{{ item.store?.nama || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5 text-right text-amber-600 font-semibold">{{ formatRupiah(item.jumlah_lebih_bayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-emerald-600 font-bold">{{ formatRupiah(item.jumlah_pengembalian) }}</td>
                                        <td class="border-b px-3 py-2.5">
                                            <span class="bg-muted inline-block rounded px-1.5 py-0.5 text-xs font-medium">{{ item.metode_pengembalian || '-' }}</span>
                                        </td>
                                        <td class="border-b px-3 py-2.5">
                                            <Badge :variant="getStatusBadge(item.status)">{{ getStatusLabel(item.status) }}</Badge>
                                        </td>
                                    </tr>
                                    <!-- TOTAL -->
                                    <tr class="bg-muted/60 font-bold">
                                        <td colspan="6" class="border-b px-3 py-2.5 text-right">TOTAL</td>
                                        <td class="border-b px-3 py-2.5 text-right text-amber-600">{{ formatRupiah(t.total_lebih_bayar) }}</td>
                                        <td class="border-b px-3 py-2.5 text-right text-emerald-600">{{ formatRupiah(t.total_pengembalian) }}</td>
                                        <td colspan="2" class="border-b px-3 py-2.5"></td>
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
                        <div class="bg-muted flex size-14 items-center justify-center rounded-full"><RotateCcw class="text-muted-foreground size-7" /></div>
                        <div>
                            <p class="font-semibold">Belum ada pengembalian lebih bayar</p>
                            <p class="text-muted-foreground text-sm">Tidak ada transaksi pada periode ini.</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
