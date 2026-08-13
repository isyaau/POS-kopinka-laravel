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
    ClipboardCheck,
    CalendarDays,
    FileText,
    Calculator,
} from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    opname: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const p = computed(() => props.opname || {})

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

const details = computed(() => p.value.details || [])
const totalSelisih = computed(() => details.value.reduce((sum, d) => sum + (Number(d.selisih) || 0), 0))
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90svh] max-w-4xl overflow-y-auto gap-0 p-0">
            <DialogHeader class="border-b p-6 pb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <ClipboardCheck class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <DialogTitle class="text-xl">Detail Stok Opname</DialogTitle>
                        <DialogDescription>
                            <span class="bg-primary/10 text-primary inline-block rounded px-1.5 py-0.5 text-xs font-semibold">
                                {{ p.no_opname || '-' }}
                            </span>
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="flex flex-col gap-6 p-6">
                <!-- Informasi Opname -->
                <section class="flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <ClipboardCheck class="text-primary size-4" />
                        <h3 class="text-sm font-semibold tracking-wide uppercase">Informasi Opname</h3>
                        <div class="bg-border h-px flex-1" />
                    </div>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-muted-foreground text-xs">No Opname</span>
                            <span class="text-sm font-medium">{{ p.no_opname || '-' }}</span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-muted-foreground text-xs">Tanggal</span>
                            <span class="text-sm font-medium">{{ formatDate(p.tanggal) }}</span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-muted-foreground text-xs">Petugas</span>
                            <span class="text-sm font-medium">{{ p.petugas || '-' }}</span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-muted-foreground text-xs">Status</span>
                            <Badge :variant="statusInfo(p.status).variant">{{ statusInfo(p.status).label }}</Badge>
                        </div>
                    </div>
                </section>

                <Separator />

                <!-- Daftar Item -->
                <section class="flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <Calculator class="text-primary size-4" />
                        <h3 class="text-sm font-semibold tracking-wide uppercase">Item Opname ({{ details.length }})</h3>
                        <div class="bg-border h-px flex-1" />
                    </div>
                    <div class="overflow-auto">
                        <table class="w-full min-w-[600px] border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Produk</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Stok Sistem</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Stok Fisik</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Selisih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(d, i) in details" :key="i" class="hover:bg-muted/40">
                                    <td class="border-b px-3 py-2">
                                        <p class="font-medium">{{ d.nama_barang || '-' }}</p>
                                    </td>
                                    <td class="border-b px-3 py-2 text-right">{{ d.stok_sistem }}</td>
                                    <td class="border-b px-3 py-2 text-right">{{ d.stok_fisik }}</td>
                                    <td class="border-b px-3 py-2 text-right font-semibold" :class="{ 'text-destructive': Number(d.selisih) < 0, 'text-emerald-600': Number(d.selisih) > 0 }">
                                        {{ d.selisih }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-end gap-4 text-sm">
                        <span class="text-muted-foreground">Total selisih:</span>
                        <span class="font-semibold" :class="{ 'text-destructive': totalSelisih < 0, 'text-emerald-600': totalSelisih > 0 }">
                            {{ totalSelisih }}
                        </span>
                    </div>
                </section>

                <!-- Keterangan -->
                <p v-if="p.keterangan" class="text-muted-foreground border-t pt-3 text-sm">
                    <span class="font-semibold">Keterangan:</span> {{ p.keterangan }}
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
