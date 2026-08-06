<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { FileBarChart, Wallet, Receipt, Star, TrendingUp, CalendarRange } from 'lucide-vue-next'

const props = defineProps({
    dari: { type: String, default: '' },
    sampai: { type: String, default: '' },
    stores: { type: Array, default: () => [] },
    perToko: { type: Array, default: () => [] },
    perProduk: { type: Array, default: () => [] },
    transaksi: { type: Array, default: () => [] },
    total: { type: Object, default: () => ({}) },
})

const dari = ref(props.dari)
const sampai = ref(props.sampai)

const formatRupiah = (val) => {
    const n = Number(val || 0)
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(n)
}

const formatDate = (val) => {
    if (!val) return '-'
    const d = new Date(val + 'T00:00:00')
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const applyFilter = () => {
    router.get('/laporan', { dari: dari.value, sampai: sampai.value }, { preserveState: true, replace: true })
}

const maxProdukQty = Math.max(...props.perProduk.map((p) => p.qty), 1)
</script>

<template>
    <AppLayout>
        <Head title="Laporan - Kopinka" />

        <PageHeader
            title="Laporan Penjualan"
            description="Ringkasan penjualan per periode, rekap per toko, dan produk terlaris."
        />

        <!-- Filter periode -->
        <Card class="mb-4">
            <CardContent class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div class="grid gap-1.5">
                        <label class="text-muted-foreground text-xs font-medium">Dari</label>
                        <Input v-model="dari" type="date" class="w-44" />
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-muted-foreground text-xs font-medium">Sampai</label>
                        <Input v-model="sampai" type="date" class="w-44" />
                    </div>
                    <Button size="sm" class="h-9" @click="applyFilter">
                        <CalendarRange class="size-4" />
                        Terapkan
                    </Button>
                </div>
                <p class="text-muted-foreground text-sm">
                    Periode: {{ formatDate(dari) }} — {{ formatDate(sampai) }}
                </p>
            </CardContent>
        </Card>

        <!-- Kartu ringkasan total -->
        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground flex items-center gap-1.5 text-sm">
                        <TrendingUp class="size-4" /> Omzet
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="truncate text-xl font-bold">{{ formatRupiah(total.omzet) }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground flex items-center gap-1.5 text-sm">
                        <Receipt class="size-4" /> Transaksi
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-xl font-bold">{{ total.jumlah || 0 }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground flex items-center gap-1.5 text-sm">
                        <Wallet class="size-4" /> Cash
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="truncate text-xl font-bold">{{ formatRupiah(total.cash) }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground flex items-center gap-1.5 text-sm">
                        <Wallet class="size-4" /> QRIS + EDC
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="truncate text-xl font-bold">{{ formatRupiah((total.qris || 0) + (total.edc || 0)) }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground flex items-center gap-1.5 text-sm">
                        <Wallet class="size-4" /> Piutang
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="truncate text-xl font-bold">{{ formatRupiah(total.piutang) }}</p>
                </CardContent>
            </Card>
        </div>

        <!-- Rekap & detail -->
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <!-- Rekap per toko -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <FileBarChart class="text-primary size-5" />
                        Rekap per Toko
                    </CardTitle>
                    <CardDescription>Ringkasan penjualan tiap toko pada periode ini</CardDescription>
                </CardHeader>
                <CardContent>
                    <table v-if="perToko.length" class="w-full border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Toko</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Jumlah</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in perToko" :key="t.store_id" class="hover:bg-muted/40">
                                <td class="border-b px-3 py-2.5 font-medium">{{ t.nama_toko }}</td>
                                <td class="border-b px-3 py-2.5 text-right">{{ t.jumlah }}</td>
                                <td class="border-b px-3 py-2.5 text-right font-bold">{{ formatRupiah(t.omzet) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-muted-foreground py-4 text-sm">Tidak ada data pada periode ini.</p>
                </CardContent>
            </Card>

            <!-- Produk terlaris periode -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Star class="text-primary size-5" />
                        Produk Terlaris (Periode)
                    </CardTitle>
                    <CardDescription>10 produk terlaris pada periode ini</CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-2.5">
                    <div v-for="p in perProduk" :key="p.nama_barang" class="flex items-center gap-3">
                        <span class="bg-muted text-muted-foreground flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold">
                            {{ perProduk.indexOf(p) + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ p.nama_barang }}</p>
                            <div class="bg-muted mt-1 h-2 overflow-hidden rounded-full">
                                <div class="bg-primary h-full rounded-full" :style="{ width: `${(p.qty / maxProdukQty) * 100}%` }" />
                            </div>
                        </div>
                        <span class="shrink-0 text-sm font-bold">{{ p.qty }}</span>
                        <span class="text-muted-foreground w-20 shrink-0 text-right text-xs">{{ formatRupiah(p.total) }}</span>
                    </div>
                    <p v-if="!perProduk.length" class="text-muted-foreground py-4 text-sm">Belum ada penjualan pada periode ini.</p>
                </CardContent>
            </Card>
        </div>

        <!-- Daftar transaksi periode -->
        <Card class="mt-4">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Receipt class="text-primary size-5" />
                    Detail Transaksi Periode
                </CardTitle>
                <CardDescription>Semua transaksi pada rentang tanggal terpilih</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="min-h-0 overflow-auto">
                    <table v-if="transaksi.length" class="w-full min-w-[640px] border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">No Nota</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Tanggal</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Toko</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Anggota</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Total</th>
                                <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in transaksi" :key="t.no_nota" class="hover:bg-muted/40">
                                <td class="border-b px-3 py-2.5">
                                    <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ t.no_nota }}</span>
                                </td>
                                <td class="border-b px-3 py-2.5 text-muted-foreground">{{ formatDate(t.tanggal) }}</td>
                                <td class="border-b px-3 py-2.5">{{ t.nama_toko }}</td>
                                <td class="border-b px-3 py-2.5">{{ t.nama_anggota || 'Umum' }}</td>
                                <td class="border-b px-3 py-2.5 text-right font-bold">{{ formatRupiah(t.jual) }}</td>
                                <td class="border-b px-3 py-2.5 text-right">
                                    <Badge variant="secondary">{{ formatRupiah(t.piutang > 0 ? t.piutang : t.jual) }}</Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-muted-foreground py-4 text-sm">Tidak ada transaksi pada periode ini.</p>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
