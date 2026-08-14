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
    ReceiptText,
    Building2,
    Tag,
    FileText,
    Wallet,
    Hash,
    CalendarDays,
} from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    biaya: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

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

const b = computed(() => props.biaya || {})

const sections = computed(() => [
    {
        title: 'Informasi Biaya',
        icon: ReceiptText,
        fields: [
            { label: 'No. Bukti', value: b.value.no_bukti || '-' },
            { label: 'Tanggal', value: formatDate(b.value.tanggal) },
            { label: 'Dari Unit', value: b.value.dari_unit || '-' },
            { label: 'Kategori', value: b.value.kategori || '-' },
        ],
    },
    {
        title: 'Keterangan & Nominal',
        icon: Wallet,
        fields: [
            { label: 'Keterangan', value: b.value.keterangan || '-', full: true },
            { label: 'Jumlah', value: formatRupiah(b.value.jumlah), highlight: true },
        ],
    },
])

const kategoriBadge = (kat) => {
    const map = {
        'Gaji Karyawan': 'warning',
        'Listrik': 'secondary',
        'Air': 'secondary',
        'Telepon & Internet': 'secondary',
        'Sewa Gedung': 'purna',
        'ATK': 'secondary',
        'Transportasi': 'secondary',
        'Perawatan & Pemeliharaan': 'secondary',
        'Promosi & Marketing': 'secondary',
        'Pajak & Retribusi': 'destructive',
        'Konsumsi': 'secondary',
        'Bank & Admin': 'secondary',
        'Lainnya': 'outline',
    }
    return map[kat] || 'secondary'
}
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
                    <DialogTitle class="text-sm font-semibold">Detail Biaya Operasional</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-xs">
                        {{ b.no_bukti || 'Informasi lengkap biaya' }}
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
                    <ReceiptText class="size-7" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold">{{ b.kategori || '-' }}</p>
                    <p class="text-muted-foreground text-sm">
                        {{ b.no_bukti || '-' }} · {{ formatDate(b.tanggal) }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <Badge :variant="kategoriBadge(b.kategori)">{{ b.kategori || '-' }}</Badge>
                        <Badge v-if="b.dari_unit" variant="secondary">{{ b.dari_unit }}</Badge>
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
                            <span
                                class="break-words text-sm font-medium"
                                :class="f.highlight ? 'text-lg font-bold text-foreground' : ''"
                            >{{ f.value }}</span>
                        </div>
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
