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
    Tag,
    FileText,
    Package,
    User,
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
    const badges = { draft: 'default', tercetak: 'secondary', dipasang: 'outline', batal: 'destructive' }
    return badges[status] || 'default'
}

const getUkuranBadge = (ukuran) => {
    const badges = { kecil: 'secondary', sedang: 'default', besar: 'outline' }
    return badges[ukuran] || 'default'
}

const b = computed(() => props.item || {})

const sections = computed(() => [
    {
        title: 'Data Register Label',
        icon: FileText,
        fields: [
            { label: 'No. Transaksi', value: b.value.no_transaksi || '-' },
            { label: 'No. Bukti', value: b.value.no_bukti || '-' },
            { label: 'Tgl Register', value: formatDate(b.value.tgl_register) },
            { label: 'Status', value: b.value.status || '-', badge: getStatusBadge(b.value.status) },
        ],
    },
    {
        title: 'Data Produk',
        icon: Package,
        fields: [
            { label: 'Kode Barang', value: b.value.kode_barang || '-', mono: true },
            { label: 'Nama Barang', value: b.value.nama_barang || '-' },
            { label: 'Kategori', value: b.value.kategori || '-' },
            { label: 'Satuan', value: b.value.satuan || '-' },
            { label: 'No. Rak', value: b.value.no_rak || '-' },
            { label: 'Harga Jual', value: formatRupiah(b.value.harga_jual), highlight: true },
        ],
    },
    {
        title: 'Detail Label',
        icon: Tag,
        fields: [
            { label: 'Jumlah Label', value: (b.value.jumlah_label || 0) + ' pcs', highlight: true },
            { label: 'Ukuran Label', value: b.value.ukuran_label || '-', badge: getUkuranBadge(b.value.ukuran_label) },
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
                    <DialogTitle class="text-sm font-semibold">Detail Register Label Etalase</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-xs">
                        {{ b.no_transaksi || 'Informasi lengkap register label etalase barang' }}
                    </DialogDescription>
                </DialogHeader>
                <Button variant="ghost" size="icon" class="size-8 shrink-0" @click="emit('update:open', false)">
                    <X class="size-4" />
                    <span class="sr-only">Tutup</span>
                </Button>
            </div>

            <div class="flex shrink-0 items-center gap-4 border-b px-6 py-5">
                <div class="bg-primary/10 text-primary flex size-14 shrink-0 items-center justify-center rounded-2xl">
                    <Tag class="size-7" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold">{{ b.nama_barang || '-' }}</p>
                    <p class="text-muted-foreground text-sm">
                        {{ b.no_transaksi || '-' }} · {{ formatDate(b.tgl_register) }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <Badge :variant="getStatusBadge(b.status)">{{ b.status }}</Badge>
                        <Badge v-if="b.ukuran_label" :variant="getUkuranBadge(b.ukuran_label)">{{ b.ukuran_label }}</Badge>
                        <Badge v-if="b.jumlah_label" variant="secondary">{{ b.jumlah_label }} pcs</Badge>
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
                                :class="[f.highlight ? 'text-lg font-bold text-foreground' : '', f.mono ? 'font-mono text-xs' : '']"
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
