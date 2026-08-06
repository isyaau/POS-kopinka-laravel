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
    Package,
    Tag,
    MapPin,
    Boxes,
    CalendarDays,
    Coins,
    Percent,
    Ruler,
    StickyNote,
} from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    produk: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const p = computed(() => props.produk || {})

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

const lowStock = computed(() => (p.value.stok ?? 0) <= (p.value.stok_minimum ?? 0))

const sections = computed(() => [
    {
        title: 'Identitas Barang',
        icon: Tag,
        fields: [
            { label: 'ID Barang', value: p.value.id || '-' },
            { label: 'Kode Barang', value: p.value.kode_barang || '-' },
            { label: 'Nama Barang', value: p.value.nama_barang || '-', full: true },
            { label: 'Kategori', value: p.value.kategori || '-' },
            { label: 'Satuan', value: p.value.satuan || '-' },
            { label: 'No Rak', value: p.value.no_rak || '-' },
            { label: 'Supplier', value: p.value.supplier?.nama || '-', full: true },
        ],
    },
    {
        title: 'Harga',
        icon: Coins,
        fields: [
            { label: 'Harga Beli', value: formatRupiah(p.value.harga_beli) },
            { label: 'Harga Jual', value: formatRupiah(p.value.harga_jual) },
            { label: 'Diskon', value: p.value.diskon > 0 ? formatRupiah(p.value.diskon) : '-' },
            { label: 'PPN', value: `${p.value.ppn ?? 0}%` },
        ],
    },
    {
        title: 'Stok',
        icon: Boxes,
        fields: [
            { label: 'Stok', value: p.value.stok ?? 0 },
            { label: 'Stok Minimum', value: p.value.stok_minimum ?? 0 },
            { label: 'Tanggal Expired', value: formatDate(p.value.tanggal_expired) },
        ],
    },
])
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            :show-close="false"
            class="flex max-h-[88svh] max-w-xl flex-col gap-0 overflow-hidden p-0"
        >
            <!-- Header -->
            <div class="bg-muted/40 flex shrink-0 items-center justify-between border-b px-6 py-3">
                <DialogHeader class="gap-0">
                    <DialogTitle class="text-sm font-semibold">Detail Produk</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-xs">
                        {{ p.nama_barang || 'Informasi lengkap produk' }}
                    </DialogDescription>
                </DialogHeader>
                <Button variant="ghost" size="icon" class="size-8 shrink-0" @click="emit('update:open', false)">
                    <X class="size-4" />
                    <span class="sr-only">Tutup</span>
                </Button>
            </div>

            <!-- Profil singkat -->
            <div class="flex shrink-0 items-center gap-4 border-b px-6 py-5">
                <div class="text-primary bg-primary/10 flex size-14 shrink-0 items-center justify-center rounded-2xl">
                    <Package class="size-7" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold">{{ p.nama_barang || '-' }}</p>
                    <p class="text-muted-foreground text-sm">Barang — Kode {{ p.kode_barang || '-' }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <Badge :variant="lowStock ? 'warning' : 'success'">
                            Stok: {{ p.stok ?? 0 }}
                        </Badge>
                        <Badge variant="secondary">{{ p.kategori || 'Tanpa kategori' }}</Badge>
                        <Badge v-if="p.satuan" variant="outline">
                            <Ruler class="size-3" />
                            {{ p.satuan }}
                        </Badge>
                    </div>
                </div>
            </div>

            <!-- Body -->
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
                            <span class="break-words text-sm font-medium">{{ f.value }}</span>
                        </div>
                    </div>
                </div>

                <!-- Info dibuat -->
                <Separator class="my-5" />
                <div class="flex items-center gap-2">
                    <CalendarDays class="text-muted-foreground size-4" />
                    <h3 class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">Informasi Lain</h3>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-muted-foreground text-xs">Terdaftar</span>
                        <span class="text-sm font-medium">{{ formatDate(p.created_at) }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-muted-foreground text-xs">Terakhir Diperbarui</span>
                        <span class="text-sm font-medium">{{ formatDate(p.updated_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex shrink-0 items-center justify-end gap-2 border-t bg-muted/20 px-6 py-3">
                <Button variant="outline" size="sm" @click="emit('update:open', false)">
                    <X class="size-3.5" />
                    Tutup
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
