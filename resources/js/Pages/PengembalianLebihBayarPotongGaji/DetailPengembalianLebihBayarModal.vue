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
    RotateCcw,
    FileText,
    Building2,
    User,
    CreditCard,
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

const getStatusBadge = (status) => {
    const badges = {
        pending: 'default',
        diproses: 'secondary',
        selesai: 'outline',
        batal: 'destructive',
    }
    return badges[status] || 'default'
}

const getMetodeBadge = (metode) => {
    const badges = {
        transfer: 'default',
        tunai: 'secondary',
        potong_gaji_berikutnya: 'outline',
    }
    return badges[metode] || 'default'
}

const b = computed(() => props.item || {})

const sections = computed(() => [
    {
        title: 'Data Register Tagihan',
        icon: FileText,
        fields: [
            { label: 'No. Register Tagihan', value: b.value.no_register_tagihan || '-' },
            { label: 'No. Transaksi Pengembalian', value: b.value.no_transaksi || '-' },
            { label: 'No. Bukti', value: b.value.no_bukti || '-' },
            { label: 'Tgl Pengembalian', value: formatDate(b.value.tgl_pengembalian) },
        ],
    },
    {
        title: 'Data Anggota / Karyawan',
        icon: Building2,
        fields: [
            { label: 'Kode Anggota', value: b.value.kode_anggota || '-' },
            { label: 'Nama Anggota', value: b.value.nama_anggota || '-' },
            { label: 'Unit Kerja', value: b.value.unit_kerja || '-' },
            { label: 'Jabatan', value: b.value.jabatan || '-' },
        ],
    },
    {
        title: 'Data Potong Gaji',
        icon: CreditCard,
        fields: [
            { label: 'No. Transaksi Potong Gaji', value: b.value.no_transaksi_potong_gaji || '-' },
            { label: 'Jumlah Lebih Bayar', value: formatRupiah(b.value.jumlah_lebih_bayar), highlight: true },
            { label: 'Jumlah Pengembalian', value: formatRupiah(b.value.jumlah_pengembalian), highlight: true },
            { label: 'Metode Pengembalian', value: b.value.metode_pengembalian || '-', badge: getMetodeBadge(b.value.metode_pengembalian) },
            { label: 'Status', value: b.value.status || '-', badge: getStatusBadge(b.value.status) },
            { label: 'Keterangan', value: b.value.keterangan || '-', full: true },
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
                    <DialogTitle class="text-sm font-semibold">Detail Pengembalian Lebih Bayar</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-xs">
                        {{ b.no_transaksi || 'Informasi lengkap pengembalian lebih bayar potong gaji' }}
                    </DialogDescription>
                </DialogHeader>
                <Button variant="ghost" size="icon" class="size-8 shrink-0" @click="emit('update:open', false)">
                    <X class="size-4" />
                    <span class="sr-only">Tutup</span>
                </Button>
            </div>

            <div class="flex shrink-0 items-center gap-4 border-b px-6 py-5">
                <div class="bg-emerald-500/10 text-emerald-600 flex size-14 shrink-0 items-center justify-center rounded-2xl">
                    <RotateCcw class="size-7" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold">{{ b.nama_anggota || '-' }}</p>
                    <p class="text-muted-foreground text-sm">
                        {{ b.no_transaksi || '-' }} · {{ formatDate(b.tgl_pengembalian) }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <Badge v-if="b.no_register_tagihan" variant="secondary">{{ b.no_register_tagihan }}</Badge>
                        <Badge :variant="getStatusBadge(b.status)">{{ b.status }}</Badge>
                        <Badge v-if="b.metode_pengembalian" :variant="getMetodeBadge(b.metode_pengembalian)">{{ b.metode_pengembalian }}</Badge>
                    </div>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                <div v-for="(sec, i) in sections" :key="sec.title">
                    <Separator v-if="i > 0" class="my-5" />
                    <div class="mb-3 flex items-center gap-2">
                        <component :is="sec.icon" class="text-emerald-600 size-4" />
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
                            >
                                <Badge v-if="f.badge" :variant="f.badge">{{ f.value }}</Badge>
                                <span v-else>{{ f.value }}</span>
                            </span>
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
