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
import { X, UserRound } from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    anggota: { type: Object, default: null },
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

const fields = computed(() => {
    const a = props.anggota
    if (!a) return []
    return [
        { label: 'Status di Kopinka', value: a.status === 'karyawan' ? 'Karyawan' : 'Anggota' },
        { label: 'NIP / No. Anggota', value: a.nip },
        { label: 'Nama Lengkap', value: a.nama },
        { label: 'Alamat', value: a.alamat || '-' },
        { label: 'Tempat Lahir', value: a.tempat_lahir || '-' },
        { label: 'Tanggal Lahir', value: formatDate(a.tanggal_lahir) },
        { label: 'Jenis Kelamin', value: a.jenis_kelamin === 'L' ? 'Laki-laki' : a.jenis_kelamin === 'P' ? 'Perempuan' : '-' },
        { label: 'Pendidikan', value: a.pendidikan || '-' },
        { label: 'No. HP', value: a.no_hp || '-' },
        { label: 'Divisi Pekerjaan', value: a.divisi_pekerjaan || '-' },
        { label: 'Status Purna', value: a.status_purna ? 'Ya' : 'Tidak' },
        { label: 'Tgl Terdaftar', value: formatDate(a.tgl_terdaftar) },
        { label: 'Tgl Pensiun', value: formatDate(a.tgl_pensiun) },
        { label: 'Simpanan Pokok', value: formatRupiah(a.simpanan_pokok) },
        { label: 'Simpanan Wajib', value: formatRupiah(a.simpanan_wajib) },
        { label: 'Status Aktif', value: a.status_aktif ? 'Aktif' : 'Tidak Aktif' },
        { label: 'Limit Transaksi', value: a.status_limit ? `Aktif — ${formatRupiah(a.limit_transaksi)}` : 'Tidak Ada' },
    ]
})
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[85svh] max-w-lg overflow-y-auto p-0">
            <div class="sticky top-0 z-10 border-b bg-background p-4">
                <div class="flex items-center gap-3">
                    <div class="text-primary bg-primary/10 flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <UserRound class="size-5" />
                    </div>
                    <DialogHeader class="min-w-0 gap-0">
                        <DialogTitle class="text-lg">Detail Anggota</DialogTitle>
                        <DialogDescription class="text-muted-foreground truncate text-xs">
                            {{ anggota?.nama }}
                        </DialogDescription>
                    </DialogHeader>
                    <Button variant="ghost" size="icon" class="ml-auto size-8" @click="emit('update:open', false)">
                        <X class="size-4" />
                    </Button>
                </div>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                    <div v-for="f in fields" :key="f.label" class="flex flex-col gap-0.5">
                        <span class="text-muted-foreground text-xs">{{ f.label }}</span>
                        <span class="break-words font-medium">{{ f.value }}</span>
                    </div>
                </div>

                <!-- Status badges ringkas -->
                <div class="mt-4 flex flex-wrap gap-2 border-t pt-4">
                    <Badge :variant="anggota?.status_aktif ? 'success' : 'inactive'">
                        {{ anggota?.status_aktif ? 'Aktif' : 'Tidak Aktif' }}
                    </Badge>
                    <Badge v-if="anggota?.status_limit" variant="warning">
                        Limit: {{ formatRupiah(anggota?.limit_transaksi) }}
                    </Badge>
                    <Badge v-if="anggota?.status_purna" variant="purna">Purna</Badge>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
