<script setup>
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogFooter,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Truck } from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    kirim: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const formatRupiah = (val) => {
    const n = Number(val || 0)
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n)
}

const formatDate = (val) => {
    if (!val) return '-'
    const d = new Date(val)
    if (Number.isNaN(d.getTime())) return '-'
    return `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getFullYear()}`
}

const statusInfo = (status) => {
    switch (status) {
        case 'draft': return { label: 'Draft', variant: 'secondary' }
        case 'dikirim': return { label: 'Dikirim', variant: 'warning' }
        case 'selesai': return { label: 'Selesai', variant: 'success' }
        default: return { label: status || '-', variant: 'secondary' }
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-2xl p-0">
            <DialogHeader class="border-b p-6 pb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <Truck class="size-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-xl">Detail Kirim Barang</DialogTitle>
                        <DialogDescription>{{ kirim?.no_kirim }}</DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="flex flex-col gap-5 p-6">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <p class="text-muted-foreground text-xs">Tanggal</p>
                        <p class="font-semibold">{{ formatDate(kirim?.tanggal) }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground text-xs">Status</p>
                        <Badge :variant="statusInfo(kirim?.status).variant" class="mt-0.5">
                            {{ statusInfo(kirim?.status).label }}
                        </Badge>
                    </div>
                    <div>
                        <p class="text-muted-foreground text-xs">Total Item</p>
                        <p class="font-semibold">{{ kirim?.total_item || 0 }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground text-xs">Total Nilai</p>
                        <p class="font-semibold">{{ formatRupiah(kirim?.total_nilai) }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground text-xs">Toko Asal</p>
                        <p class="font-semibold">{{ kirim?.nama_store_asal }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground text-xs">Toko Tujuan</p>
                        <p class="font-semibold">{{ kirim?.nama_store_tujuan }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-muted-foreground mb-2 text-xs font-semibold uppercase">Daftar Produk</p>
                    <div class="overflow-hidden rounded-md border">
                        <table class="w-full text-sm">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase">Produk</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold uppercase">Qty</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase">Harga Beli</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(d, i) in (kirim?.details || [])" :key="i" class="border-t">
                                    <td class="px-3 py-2">{{ d.nama_barang }}</td>
                                    <td class="px-3 py-2 text-center">{{ d.qty }}</td>
                                    <td class="px-3 py-2 text-right">{{ formatRupiah(d.harga_beli) }}</td>
                                    <td class="px-3 py-2 text-right font-semibold">{{ formatRupiah(d.subtotal) }}</td>
                                </tr>
                                <tr v-if="!(kirim?.details || []).length">
                                    <td colspan="4" class="text-muted-foreground px-3 py-4 text-center">Tidak ada item.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="kirim?.keterangan">
                    <p class="text-muted-foreground text-xs font-semibold uppercase">Keterangan</p>
                    <p class="text-sm">{{ kirim.keterangan }}</p>
                </div>
            </div>

            <DialogFooter class="border-t p-6 pt-4">
                <Button type="button" variant="outline" @click="emit('update:open', false)">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
