<script setup>
import { computed } from 'vue'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
    ShoppingBag,
    CalendarDays,
    FileText,
    Truck,
    Coins,
    Percent,
    ReceiptText,
    CreditCard,
} from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    pembelian: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const p = computed(() => props.pembelian || {})

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
    const d = new Date(val)
    if (Number.isNaN(d.getTime())) return '-'
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}-${month}-${year}`
}

const statusInfo = (status) => {
    switch (status) {
        case 'draft':
            return { label: 'Draft', variant: 'secondary' }
        case 'selesai':
            return { label: 'Selesai', variant: 'success' }
        case 'batal':
            return { label: 'Batal', variant: 'destructive' }
        default:
            return { label: status || '-', variant: 'secondary' }
    }
}

const jenisBayarLabel = (jenis) => {
    switch (jenis) {
        case 'tunai':
            return 'Tunai'
        case 'kredit':
            return 'Kredit'
        default:
            return jenis || '-'
    }
}

const details = computed(() => p.value.details || [])

const sections = computed(() => [
    {
        title: 'Informasi Pembelian',
        icon: ShoppingBag,
        fields: [
            { label: 'No Pembelian', value: p.value.no_pembelian || '-', full: true },
            { label: 'No Faktur', value: p.value.no_faktur || '-' },
            { label: 'Tanggal', value: formatDate(p.value.tanggal) },
            { label: 'Supplier', value: p.value.nama_supplier || '-', full: true },
            { label: 'Status', value: statusInfo(p.value.status).label, badge: true },
            { label: 'Jenis Bayar', value: jenisBayarLabel(p.value.jenis_bayar), badge: true },
            { label: 'Terlampir Bukti PPN', value: p.value.terlampir_bukti_ppn ? 'Ya' : 'Tidak' },
            { label: 'Harga Jual Termasuk PPN', value: p.value.harga_jual_termasuk_ppn ? 'Ya' : 'Tidak' },
        ],
    },
])

const ringkasan = computed(() => [
    { label: 'Nilai', value: formatRupiah(p.value.nilai) },
    { label: 'Diskon', value: p.value.diskon > 0 ? `- ${formatRupiah(p.value.diskon)}` : '-', negative: p.value.diskon > 0 },
    { label: 'Subtotal', value: formatRupiah(p.value.subtotal) },
    { label: 'PPN Masukan', value: p.value.ppn_masukan > 0 ? `+ ${formatRupiah(p.value.ppn_masukan)}` : '-', positive: p.value.ppn_masukan > 0 },
    { label: 'Total', value: formatRupiah(p.value.total), bold: true },
    { label: 'Sisa Hutang', value: formatRupiah(p.value.sisa_hutang), bold: p.value.sisa_hutang > 0, hutang: p.value.sisa_hutang > 0 },
])
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90svh] max-w-4xl overflow-y-auto gap-0 p-0">
            <DialogHeader class="border-b p-6 pb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <ShoppingBag class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <DialogTitle class="text-xl">Detail Pembelian</DialogTitle>
                        <DialogDescription>
                            <span class="bg-primary/10 text-primary inline-block rounded px-1.5 py-0.5 text-xs font-semibold">
                                {{ p.no_pembelian || '-' }}
                            </span>
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="flex flex-col gap-6 p-6">
                <!-- Informasi Pembelian -->
                <section v-for="section in sections" :key="section.title" class="flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <component :is="section.icon" class="text-primary size-4" />
                        <h3 class="text-sm font-semibold tracking-wide uppercase">{{ section.title }}</h3>
                        <div class="bg-border h-px flex-1" />
                    </div>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                        <div v-for="field in section.fields" :key="field.label" class="flex flex-col gap-0.5" :class="{ 'sm:col-span-2': field.full }">
                            <span class="text-muted-foreground text-xs">{{ field.label }}</span>
                            <Badge v-if="field.badge" :variant="field.label === 'Status' ? statusInfo(p.status).variant : 'outline'" class="w-fit">
                                {{ field.value }}
                            </Badge>
                            <span v-else class="text-sm font-medium">{{ field.value }}</span>
                        </div>
                    </div>
                </section>

                <Separator />

                <!-- Daftar Item -->
                <section class="flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <ReceiptText class="text-primary size-4" />
                        <h3 class="text-sm font-semibold tracking-wide uppercase">Item Barang ({{ details.length }})</h3>
                        <div class="bg-border h-px flex-1" />
                    </div>
                    <div class="overflow-auto">
                        <table class="w-full min-w-[560px] border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Produk</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Qty</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Harga Beli</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Diskon</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Harga Jual</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(d, i) in details" :key="i" class="hover:bg-muted/40">
                                    <td class="border-b px-3 py-2">
                                        <p class="font-medium">{{ d.nama_barang || '-' }}</p>
                                        <p v-if="d.tanggal_expired" class="text-muted-foreground text-xs">
                                            Exp: {{ formatDate(d.tanggal_expired) }}
                                        </p>
                                    </td>
                                    <td class="border-b px-3 py-2 text-right">{{ d.qty }}</td>
                                    <td class="border-b px-3 py-2 text-right">{{ formatRupiah(d.harga_beli) }}</td>
                                    <td class="border-b px-3 py-2 text-right">{{ d.diskon_item > 0 ? formatRupiah(d.diskon_item) : '-' }}</td>
                                    <td class="border-b px-3 py-2 text-right">{{ formatRupiah(d.harga_jual) }}</td>
                                    <td class="border-b px-3 py-2 text-right font-semibold">{{ formatRupiah(d.subtotal) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <Separator />

                <!-- Ringkasan -->
                <section class="flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <Coins class="text-primary size-4" />
                        <h3 class="text-sm font-semibold tracking-wide uppercase">Ringkasan</h3>
                        <div class="bg-border h-px flex-1" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <div
                            v-for="row in ringkasan"
                            :key="row.label"
                            class="flex items-center justify-between gap-4 text-sm"
                            :class="{ 'border-t pt-2': row.bold }"
                        >
                            <span class="text-muted-foreground">{{ row.label }}</span>
                            <span
                                class="font-medium"
                                :class="{
                                    'text-foreground font-semibold': row.bold && !row.hutang,
                                    'text-destructive font-semibold': row.hutang,
                                    'text-destructive': row.negative,
                                    'text-emerald-600': row.positive,
                                }"
                            >
                                {{ row.value }}
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Keterangan -->
                <p v-if="p.keterangan" class="text-muted-foreground border-t pt-3 text-sm">
                    <span class="font-semibold">Keterangan:</span> {{ p.keterangan }}
                </p>
            </div>

            <div class="bg-muted/30 flex items-center justify-end gap-2 border-t px-6 py-4">
                <Button type="button" variant="outline" @click="emit('update:open', false)">
                    Tutup
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
