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
    Truck,
    Tag,
    MapPin,
    UserRound,
    Phone,
    StickyNote,
    CalendarDays,
} from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    supplier: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const s = computed(() => props.supplier || {})

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
        title: 'Identitas',
        icon: Tag,
        fields: [
            { label: 'Kode', value: s.value.kode || '-' },
            { label: 'Nama Supplier', value: s.value.nama || '-' },
        ],
    },
    {
        title: 'Kontak & Alamat',
        icon: MapPin,
        fields: [
            { label: 'Alamat', value: s.value.alamat || '-', full: true },
            { label: 'Contact Person', value: s.value.contact_person || '-' },
            { label: 'No Telp/HP', value: s.value.no_telp || '-' },
        ],
    },
    {
        title: 'Catatan',
        icon: StickyNote,
        fields: [
            { label: 'Keterangan', value: s.value.keterangan || '-', full: true },
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
                    <DialogTitle class="text-sm font-semibold">Detail Supplier</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-xs">
                        {{ s.nama || 'Informasi lengkap supplier' }}
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
                    <Truck class="size-7" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold">{{ s.nama || '-' }}</p>
                    <p class="text-muted-foreground text-sm">Supplier — Kode {{ s.kode || '-' }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <Badge variant="secondary">Supplier</Badge>
                        <Badge v-if="s.contact_person" variant="outline">
                            <UserRound class="size-3" />
                            {{ s.contact_person }}
                        </Badge>
                        <Badge v-if="s.no_telp" variant="outline">
                            <Phone class="size-3" />
                            {{ s.no_telp }}
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
                        <span class="text-sm font-medium">{{ formatDate(s.created_at) }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-muted-foreground text-xs">Terakhir Diperbarui</span>
                        <span class="text-sm font-medium">{{ formatDate(s.updated_at) }}</span>
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
