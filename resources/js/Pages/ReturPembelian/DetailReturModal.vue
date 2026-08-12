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
    ArrowLeftRight,
    Repeat,
    ReceiptText,
    Coins,
} from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    retur: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const r = computed(() => props.retur || {})

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

const tipeInfo = (tipe) => {
    switch (tipe) {
        case 'retur':
            return { label: 'Retur', variant: 'warning' }
        case 'tukar':
            return { label: 'Tukar', variant: 'purna' }
        default:
            return { label: tipe || '-', variant: 'secondary' }
    }
}

const details = computed(() => r.value.details || [])

const sections = computed(() => [
    {
        title: 'Informasi Retur',
        icon: ArrowLeftRight,
        fields: [
            { label: 'No Retur', value: r.value.no_retur || '-', full: true },
            { label: 'Pembelian Asal', value: r.value.no_pembelian_asal || '-' },
            { label: 'Tanggal', value: formatDate(r.value.tanggal) },
            { label: 'Supplier', value: r.value.nama_supplier || '-', full: true },
            { label: 'Tipe', value: tipeInfo(r.value.tipe).label, badge: true },
            { label: 'Status', value: statusInfo(r.value.status).label, badge: true },
        ],
    },
])
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90svh] max-w-4xl overflow-y-auto gap-0 p-0">
            <DialogHeader class="border-b p-6 pb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <ArrowLeftRight class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <DialogTitle class="text-xl">Detail Retur / Tukar</DialogTitle>
                        <DialogDescription>
                            <span class="bg-primary/10 text-primary inline-block rounded px-1.5 py-0.5 text-xs font-semibold">
                                {{ r.no_retur || '-' }}
                            </span>
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="flex flex-col gap-6 p-6">
                <!-- Informasi Retur -->
                <section v-for="section in sections" :key="section.title" class="flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <component :is="section.icon" class="text-primary size-4" />
                        <h3 class="text-sm font-semibold tracking-wide uppercase">{{ section.title }}</h3>
                        <div class="bg-border h-px flex-1" />
                    </div>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                        <div v-for="field in section.fields" :key="field.label" class="flex flex-col gap-0.5" :class="{ 'sm:col-span-2': field.full }">
                            <span class="text-muted-foreground text-xs">{{ field.label }}</span>
                            <Badge v-if="field.badge" :variant="field.label === 'Status' ? statusInfo(r.status).variant : tipeInfo(r.tipe).variant" class="w-fit">
                                {{ field.value }}
                            </Badge>
                            <span v-else class="text-sm font-medium">{{ field.value }}</span>
                        </div>
                    </div>
                </section>

                <!-- Alasan -->
                <p v-if="r.alasan" class="text-muted-foreground text-sm">
                    <span class="font-semibold">Alasan:</span> {{ r.alasan }}
                </p>

                <Separator />

                <!-- Daftar Item -->
                <section class="flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <component :is="r.tipe === 'tukar' ? Repeat : ReceiptText" class="text-primary size-4" />
                        <h3 class="text-sm font-semibold tracking-wide uppercase">Item Barang ({{ details.length }})</h3>
                        <div class="bg-border h-px flex-1" />
                    </div>
                    <div class="overflow-auto">
                        <table class="w-full min-w-[560px] border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Produk Diretur</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Qty</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Harga Beli</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Subtotal</th>
                                    <th v-if="r.tipe === 'tukar'" class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Produk Pengganti</th>
                                    <th v-if="r.tipe === 'tukar'" class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Qty Tukar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(d, i) in details" :key="i" class="hover:bg-muted/40">
                                    <td class="border-b px-3 py-2">
                                        <p class="font-medium">{{ d.nama_barang || '-' }}</p>
                                    </td>
                                    <td class="border-b px-3 py-2 text-right">{{ d.qty_retur }}</td>
                                    <td class="border-b px-3 py-2 text-right">{{ formatRupiah(d.harga_beli) }}</td>
                                    <td class="border-b px-3 py-2 text-right font-semibold">{{ formatRupiah(d.subtotal) }}</td>
                                    <td v-if="r.tipe === 'tukar'" class="border-b px-3 py-2">
                                        <p class="font-medium">{{ d.nama_barang_tukar || '-' }}</p>
                                    </td>
                                    <td v-if="r.tipe === 'tukar'" class="border-b px-3 py-2 text-center">{{ d.qty_tukar || 0 }}</td>
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
                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <span class="text-muted-foreground text-sm">Total Retur</span>
                        <span class="text-primary text-xl font-bold">{{ formatRupiah(r.total_retur) }}</span>
                    </div>
                </section>

                <!-- Keterangan -->
                <p v-if="r.keterangan" class="text-muted-foreground border-t pt-3 text-sm">
                    <span class="font-semibold">Keterangan:</span> {{ r.keterangan }}
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
