<script setup>
import { computed } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { Ticket, Barcode, Wallet, CalendarClock } from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    voucher: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const v = computed(() => props.voucher || {})

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

const badgeVariant = (status) => {
    if (status === 'aktif') return 'success'
    if (status === 'terpakai') return 'secondary'
    return 'warning'
}

const badgeLabel = (status) => {
    if (status === 'aktif') return 'Aktif'
    if (status === 'terpakai') return 'Terpakai'
    return 'Kedaluwarsa'
}

const sections = computed(() => [
    {
        title: 'Info Kupon',
        icon: Ticket,
        fields: [
            { label: 'Kode', value: v.value.kode || '-' },
            { label: 'Nama', value: v.value.nama || '-', full: true },
            { label: 'Nominal', value: formatRupiah(v.value.nominal) },
            { label: 'Status', value: badgeLabel(v.value.status) },
        ],
    },
    {
        title: 'Barcode',
        icon: Barcode,
        fields: [{ label: 'Kode Barcode', value: v.value.barcode || '-', full: true }],
    },
    {
        title: 'Masa Berlaku',
        icon: CalendarClock,
        fields: [
            { label: 'Tanggal Expired', value: formatDate(v.value.tanggal_expired) },
            { label: 'Dibuat', value: formatDate(v.value.created_at) },
        ],
    },
])
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent :show-close="false" class="flex max-h-[88svh] max-w-xl flex-col gap-0 overflow-hidden p-0">
            <!-- Header -->
            <div class="bg-muted/40 flex shrink-0 items-center justify-between border-b px-6 py-3">
                <DialogHeader class="gap-0">
                    <DialogTitle class="text-sm font-semibold">Detail Voucher</DialogTitle>
                    <p class="text-muted-foreground text-xs">{{ v.kode || '' }}</p>
                </DialogHeader>
                <Badge :variant="badgeVariant(v.status)">{{ badgeLabel(v.status) }}</Badge>
            </div>

            <!-- Body -->
            <div class="min-h-0 flex-1 overflow-y-auto p-6">
                <div class="flex flex-col gap-6">
                    <!-- Nominal besar -->
                    <div class="bg-primary/5 rounded-xl border p-5 text-center">
                        <p class="text-muted-foreground text-xs uppercase">Nominal Kupon</p>
                        <p class="text-primary mt-1 text-4xl font-extrabold tracking-tight">{{ formatRupiah(v.nominal) }}</p>
                        <p class="text-muted-foreground mt-2 text-sm">{{ v.nama }}</p>
                    </div>

                    <!-- Barcode besar -->
                    <div class="flex flex-col items-center gap-2 rounded-xl border bg-muted/40 p-5">
                        <p class="text-muted-foreground text-xs uppercase">Barcode</p>
                        <p class="font-mono text-lg tracking-[0.3em]">{{ v.barcode }}</p>
                        <div class="flex h-16 w-full max-w-xs items-end justify-between gap-px opacity-80">
                            <span v-for="i in 64" :key="i" class="w-px" :class="(i * 7) % 3 === 0 ? 'bg-foreground h-full' : 'bg-foreground h-1/2'" />
                        </div>
                        <Badge variant="outline" class="mt-1">
                            <Wallet class="size-3" />
                            {{ v.store?.nama || 'Semua Toko' }}
                        </Badge>
                    </div>

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

                    <div v-if="v.keterangan" class="flex flex-col gap-1 rounded-lg border p-3">
                        <span class="text-muted-foreground text-xs uppercase">Keterangan</span>
                        <span class="text-sm">{{ v.keterangan }}</span>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
