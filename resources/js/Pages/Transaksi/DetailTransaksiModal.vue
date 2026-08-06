<script setup>
import { computed } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { ClipboardList, Package, Coins, CreditCard, User } from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    transaksi: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const t = computed(() => props.transaksi || {})

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

const sections = computed(() => [
    {
        title: 'Info Nota',
        icon: ClipboardList,
        fields: [
            { label: 'No Nota', value: t.value.no_nota || '-' },
            { label: 'No Kasir', value: t.value.no_kasir || '-' },
            { label: 'Tanggal', value: formatDate(t.value.tanggal) },
            { label: 'ID.AGT', value: t.value.anggota_id ?? '-' },
            { label: 'Nama Anggota', value: t.value.nama_anggota || '-', full: true },
        ],
    },
    {
        title: 'Nilai Transaksi',
        icon: Coins,
        fields: [
            { label: 'NILAI', value: formatRupiah(t.value.nilai) },
            { label: 'DISKON', value: t.value.diskon > 0 ? formatRupiah(t.value.diskon) : '-' },
            { label: 'JUAL', value: formatRupiah(t.value.jual) },
            { label: 'USAHA', value: formatRupiah(t.value.usaha) },
            { label: 'JASA', value: formatRupiah(t.value.jasa) },
            { label: 'PPN.K', value: formatRupiah(t.value.ppn) },
        ],
    },
    {
        title: 'Pembayaran',
        icon: CreditCard,
        fields: [
            { label: 'CASH', value: formatRupiah(t.value.cash) },
            { label: 'QRIS', value: formatRupiah(t.value.qris) },
            { label: 'EDC', value: formatRupiah(t.value.edc) },
            { label: 'VOUCHER', value: formatRupiah(t.value.voucher) },
            { label: 'PIUTANG', value: formatRupiah(t.value.piutang) },
        ],
    },
])

const items = computed(() => t.value.details || [])
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent :show-close="false" class="flex max-h-[88svh] max-w-2xl flex-col gap-0 overflow-hidden p-0">
            <!-- Header -->
            <div class="bg-muted/40 flex shrink-0 items-center justify-between border-b px-6 py-3">
                <DialogHeader class="gap-0">
                    <DialogTitle class="text-sm font-semibold">Detail Transaksi</DialogTitle>
                    <p class="text-muted-foreground text-xs">{{ t.no_nota || '' }}</p>
                </DialogHeader>
                <Badge variant="secondary">{{ t.no_kasir || 'Tanpa Kasir' }}</Badge>
            </div>

            <!-- Body -->
            <div class="min-h-0 flex-1 overflow-y-auto p-6">
                <div class="flex flex-col gap-6">
                    <section v-for="section in sections" :key="section.title" class="flex flex-col gap-3">
                        <div class="flex items-center gap-2">
                            <component :is="section.icon" class="text-primary size-4" />
                            <h3 class="text-sm font-semibold tracking-wide uppercase">{{ section.title }}</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                            <div
                                v-for="field in section.fields"
                                :key="field.label"
                                class="flex flex-col"
                                :class="{ 'sm:col-span-2': field.full }"
                            >
                                <span class="text-muted-foreground text-xs uppercase">{{ field.label }}</span>
                                <span class="text-sm font-medium">{{ field.value }}</span>
                            </div>
                        </div>
                    </section>

                    <!-- Item Barang -->
                    <section class="flex flex-col gap-3">
                        <div class="flex items-center gap-2">
                            <Package class="text-primary size-4" />
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Item Barang</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>

                        <div v-if="items.length === 0" class="text-muted-foreground rounded-lg border border-dashed p-4 text-center text-sm">
                            Tidak ada item pada transaksi ini.
                        </div>

                        <div v-else class="overflow-hidden rounded-lg border">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/50">
                                    <tr>
                                        <th class="text-muted-foreground px-3 py-2 text-left text-xs font-semibold uppercase">Nama Barang</th>
                                        <th class="text-muted-foreground px-3 py-2 text-right text-xs font-semibold uppercase">Qty</th>
                                        <th class="text-muted-foreground px-3 py-2 text-right text-xs font-semibold uppercase">Harga</th>
                                        <th class="text-muted-foreground px-3 py-2 text-right text-xs font-semibold uppercase">Diskon</th>
                                        <th class="text-muted-foreground px-3 py-2 text-right text-xs font-semibold uppercase">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id" class="border-t">
                                        <td class="px-3 py-2 font-medium">{{ item.nama_barang || '-' }}</td>
                                        <td class="px-3 py-2 text-right">{{ item.qty }}</td>
                                        <td class="px-3 py-2 text-right">{{ formatRupiah(item.harga) }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <span v-if="item.diskon_item > 0" class="text-destructive">{{ formatRupiah(item.diskon_item) }}</span>
                                            <span v-else class="text-muted-foreground">-</span>
                                        </td>
                                        <td class="px-3 py-2 text-right font-semibold">{{ formatRupiah(item.subtotal) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-muted/40 flex shrink-0 items-center justify-between border-t px-6 py-3">
                <span class="text-muted-foreground flex items-center gap-2 text-sm">
                    <User class="size-4" />
                    {{ t.nama_anggota || 'Non-anggota' }}
                </span>
                <div class="text-right">
                    <p class="text-muted-foreground text-xs uppercase">Total Jual</p>
                    <p class="text-primary text-lg font-bold">{{ formatRupiah(t.jual) }}</p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
