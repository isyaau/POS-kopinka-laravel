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
    X,
    Wallet,
    Building2,
    FileText,
    User,
    Hash,
    CalendarDays,
    Calculator,
} from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    item: { type: Object, default: null },
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
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}-${month}-${year}`
}

const b = computed(() => props.item || {})

const statusBadge = (item) => {
    if (Number(item.sisa_hutang) <= 0) return 'success'
    if (Number(item.kurang_bayar) > 0 && Number(item.jumlah_bayar) > 0) return 'warning'
    return 'destructive'
}

const statusLabel = (item) => {
    if (Number(item.sisa_hutang) <= 0) return 'Lunas'
    if (Number(item.kurang_bayar) > 0 && Number(item.jumlah_bayar) > 0) return 'Sebagian'
    return 'Belum Bayar'
}

const sections = computed(() => [
    {
        title: 'Data Supplier',
        icon: Building2,
        fields: [
            { label: 'Kode Supplier', value: b.value.kode_supplier || '-' },
            { label: 'Nama Supplier', value: b.value.nama_supplier || '-' },
            { label: 'Alamat', value: b.value.alamat || '-', full: true },
            { label: 'No. Telp', value: b.value.no_telp || '-' },
            { label: 'Contact Person', value: b.value.contact_person || '-' },
        ],
    },
    {
        title: 'Data Transaksi',
        icon: FileText,
        fields: [
            { label: 'No. Transaksi', value: b.value.no_transaksi || '-' },
            { label: 'No. Faktur', value: b.value.no_faktur || '-' },
            { label: 'No. Bukti', value: b.value.no_bukti || '-' },
            { label: 'Tgl Pembelian', value: formatDate(b.value.tgl_pembelian) },
            { label: 'Tgl Jatuh Tempo', value: formatDate(b.value.tgl_jatuh_tempo) },
            { label: 'Tanggal Bayar', value: formatDate(b.value.tanggal_bayar) },
        ],
    },
    {
        title: 'Perhitungan Hutang',
        icon: Calculator,
        fields: [
            { label: 'Nilai Pembelian', value: formatRupiah(b.value.nilai_pembelian) },
            { label: 'Retur Pembelian', value: formatRupiah(b.value.retur_pembelian) },
            { label: 'Diskon Pembayaran', value: formatRupiah(b.value.diskon_pembayaran) },
            { label: 'Total Harus Dibayar', value: formatRupiah(b.value.total_harus_dibayar), highlight: true },
            { label: 'Jumlah Bayar', value: formatRupiah(b.value.jumlah_bayar) },
            { label: 'Total Terbayar', value: formatRupiah(b.value.total_terbayar) },
            { label: 'Total Diskon', value: formatRupiah(b.value.total_diskon) },
            { label: 'Kurang Bayar', value: formatRupiah(b.value.kurang_bayar) },
            { label: 'Sisa Hutang', value: formatRupiah(b.value.sisa_hutang), highlight: true },
        ],
    },
    {
        title: 'Log User',
        icon: User,
        fields: [
            { label: 'User Input', value: b.value.user?.name || '—' },
            { label: 'Dibuat', value: b.value.created_at ? formatDate(b.value.created_at) : '-' },
            { label: 'Diperbarui', value: b.value.updated_at ? formatDate(b.value.updated_at) : '-' },
        ],
    },
])
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent :show-close="false" class="flex max-h-[88svh] max-w-2xl flex-col gap-0 overflow-hidden p-0">
            <div class="bg-muted/40 flex shrink-0 items-center justify-between border-b px-6 py-3">
                <DialogHeader class="gap-0">
                    <DialogTitle class="text-sm font-semibold">Detail Pembayaran Hutang</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-xs">
                        {{ b.no_transaksi || 'Informasi lengkap pembayaran' }}
                    </DialogDescription>
                </DialogHeader>
                <Button variant="ghost" size="icon" class="size-8 shrink-0" @click="emit('update:open', false)">
                    <X class="size-4" />
                    <span class="sr-only">Tutup</span>
                </Button>
            </div>

            <div class="flex shrink-0 items-center gap-4 border-b px-6 py-5">
                <div class="text-primary bg-primary/10 flex size-14 shrink-0 items-center justify-center rounded-2xl">
                    <Wallet class="size-7" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold">{{ b.nama_supplier || '-' }}</p>
                    <p class="text-muted-foreground text-sm">
                        {{ b.no_transaksi || '-' }} · {{ formatDate(b.tanggal_bayar) }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <Badge :variant="statusBadge(b)">{{ statusLabel(b) }}</Badge>
                        <Badge v-if="b.kode_supplier" variant="secondary">{{ b.kode_supplier }}</Badge>
                        <Badge v-if="b.no_faktur" variant="outline">{{ b.no_faktur }}</Badge>
                    </div>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                <div v-for="(sec, i) in sections" :key="sec.title">
                    <Separator v-if="i > 0" class="my-5" />
                    <div class="mb-3 flex items-center gap-2">
                        <component :is="sec.icon" class="text-primary size-4" />
                        <h3 class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">{{ sec.title }}</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                        <div
                            v-for="f in sec.fields"
                            :key="f.label"
                            class="flex flex-col gap-0.5"
                            :class="f.full ? 'sm:col-span-2' : ''"
                        >
                            <span class="text-muted-foreground text-xs">{{ f.label }}</span>
                            <span
                                class="break-words text-sm font-medium"
                                :class="f.highlight ? 'text-lg font-bold text-foreground' : ''"
                            >{{ f.value }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t bg-muted/20 px-6 py-3">
                <Button variant="outline" size="sm" @click="emit('update:open', false)">
                    <X class="size-3.5" />
                    Tutup
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
