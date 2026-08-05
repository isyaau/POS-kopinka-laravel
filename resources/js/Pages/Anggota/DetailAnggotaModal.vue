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
    UserRound,
    Phone,
    Briefcase,
    Wallet,
} from 'lucide-vue-next'

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

const a = computed(() => props.anggota || {})

const inisial = computed(() => (a.value.nama || '?').charAt(0).toUpperCase())

const sections = computed(() => [
    {
        title: 'Data Pribadi',
        icon: UserRound,
        fields: [
            { label: 'Nama Lengkap', value: a.value.nama || '-' },
            { label: 'Tempat, Tgl Lahir', value: `${a.value.tempat_lahir || '-'}, ${formatDate(a.value.tanggal_lahir)}` },
            { label: 'Jenis Kelamin', value: a.value.jenis_kelamin === 'L' ? 'Laki-laki' : a.value.jenis_kelamin === 'P' ? 'Perempuan' : '-' },
            { label: 'Pendidikan', value: a.value.pendidikan || '-' },
            { label: 'Alamat', value: a.value.alamat || '-', full: true },
        ],
    },
    {
        title: 'Keanggotaan',
        icon: Briefcase,
        fields: [
            { label: 'NIP / No. Anggota', value: a.value.nip || '-' },
            { label: 'Status', value: a.value.status === 'karyawan' ? 'Karyawan' : 'Anggota' },
            { label: 'Divisi Pekerjaan', value: a.value.divisi_pekerjaan || '-' },
            { label: 'Tgl Terdaftar', value: formatDate(a.value.tgl_terdaftar) },
            { label: 'Status Purna', value: a.value.status_purna ? 'Ya' : 'Tidak' },
            { label: 'Tgl Pensiun', value: formatDate(a.value.tgl_pensiun) },
        ],
    },
    {
        title: 'Keuangan & Limit',
        icon: Wallet,
        fields: [
            { label: 'Simpanan Pokok', value: formatRupiah(a.value.simpanan_pokok) },
            { label: 'Simpanan Wajib', value: formatRupiah(a.value.simpanan_wajib) },
            { label: 'Status Limit', value: a.value.status_limit ? 'Aktif' : 'Tidak Ada' },
            { label: 'Limit Transaksi', value: a.value.status_limit ? formatRupiah(a.value.limit_transaksi) : '-' },
        ],
    },
])

const statusBadge = computed(() => {
    if (a.value.status_purna) return { text: 'Purna', variant: 'purna' }
    if (a.value.status_aktif) return { text: 'Aktif', variant: 'success' }
    return { text: 'Tidak Aktif', variant: 'inactive' }
})

const contactItems = computed(() => [
    { icon: Phone, text: a.value.no_hp || '-', href: a.value.no_hp ? `tel:${a.value.no_hp}` : null },
])
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            :show-close="false"
            class="flex max-h-[88svh] max-w-xl flex-col gap-0 overflow-hidden p-0"
        >
            <!-- ===== Header ===== -->
            <div class="bg-muted/40 flex shrink-0 items-center justify-between border-b px-6 py-3">
                <DialogHeader class="gap-0">
                    <DialogTitle class="text-sm font-semibold">Detail Data Anggota</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-xs">
                        {{ a.nama || 'Informasi lengkap anggota' }}
                    </DialogDescription>
                </DialogHeader>
                <Button variant="ghost" size="icon" class="size-8 shrink-0" @click="emit('update:open', false)">
                    <X class="size-4" />
                    <span class="sr-only">Tutup</span>
                </Button>
            </div>

            <!-- ===== Profil singkat ===== -->
            <div class="flex shrink-0 items-center gap-4 border-b px-6 py-5">
                <div class="text-primary bg-primary/10 flex size-14 shrink-0 items-center justify-center rounded-2xl">
                    <span class="text-2xl font-bold">{{ inisial }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold">{{ a.nama || '-' }}</p>
                    <p class="text-muted-foreground text-sm">
                        {{ a.status === 'karyawan' ? 'Karyawan' : 'Anggota' }} — NIP {{ a.nip || '-' }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <Badge :variant="statusBadge.variant">{{ statusBadge.text }}</Badge>
                        <Badge v-if="a.status_limit" variant="warning">Limit: {{ formatRupiah(a.limit_transaksi) }}</Badge>
                        <Badge v-if="a.divisi_pekerjaan" variant="secondary">{{ a.divisi_pekerjaan }}</Badge>
                    </div>
                </div>
            </div>

            <!-- ===== Body (scrollable) ===== -->
            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                <!-- Kontak -->
                <div class="mb-5 flex flex-wrap gap-x-6 gap-y-2">
                    <span v-for="c in contactItems" :key="c.text" class="text-muted-foreground flex items-center gap-1.5 text-sm">
                        <component :is="c.icon" class="size-4" />
                        <a v-if="c.href" :href="c.href" class="hover:text-foreground transition-colors">{{ c.text }}</a>
                        <template v-else>{{ c.text }}</template>
                    </span>
                </div>

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
            </div>

            <!-- ===== Footer ===== -->
            <div class="flex shrink-0 items-center justify-end gap-2 border-t bg-muted/20 px-6 py-3">
                <Button variant="outline" size="sm" @click="emit('update:open', false)">
                    <X class="size-3.5" />
                    Tutup
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
