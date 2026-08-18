<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Pagination } from '@/components/ui/pagination'
import CreatePengembalianLebihBayarModal from './CreatePengembalianLebihBayarModal.vue'
import EditPengembalianLebihBayarModal from './EditPengembalianLebihBayarModal.vue'
import DetailPengembalianLebihBayarModal from './DetailPengembalianLebihBayarModal.vue'
import DeleteConfirmModal from './DeleteConfirmModal.vue'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Search,
    AlertTriangle,
    MoreHorizontal,
    Pencil,
    Trash2,
    Plus,
    CalendarRange,
    X,
    Eye,
    RotateCcw,
} from 'lucide-vue-next'

const props = defineProps({
    pengembalian: { type: Object, default: () => ({ data: [] }) },
    anggotas: { type: Array, default: () => [] },
    registerTagihans: { type: Array, default: () => [] },
    penerimaanAngsuranPotongGajis: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const createOpen = ref(false)
const editOpen = ref(false)
const editingItem = ref(null)
const detailOpen = ref(false)
const selectedItem = ref(null)
const deleteOpen = ref(false)
const deletingItem = ref(null)
const search = ref(props.filters.search || '')
const anggotaFilter = ref(props.filters.anggota_id || 'semua')
const statusFilter = ref(props.filters.status || 'semua')
const tanggalMulai = ref(props.filters.tanggal_mulai || '')
const tanggalSelesai = ref(props.filters.tanggal_selesai || '')

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

let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
}

const applyFilters = () => {
    router.get('/pengembalian-lebih-bayar-potong-gaji', {
        search: search.value,
        anggota_id: anggotaFilter.value === 'semua' ? undefined : anggotaFilter.value,
        status: statusFilter.value === 'semua' ? undefined : statusFilter.value,
        tanggal_mulai: tanggalMulai.value || undefined,
        tanggal_selesai: tanggalSelesai.value || undefined,
    }, { preserveState: true, replace: true })
}

const onAnggotaFilter = (val) => {
    anggotaFilter.value = val
    applyFilters()
}

const onStatusFilter = (val) => {
    statusFilter.value = val
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    anggotaFilter.value = 'semua'
    statusFilter.value = 'semua'
    tanggalMulai.value = ''
    tanggalSelesai.value = ''
    router.get('/pengembalian-lebih-bayar-potong-gaji', {}, { preserveState: true, replace: true })
}

const hasDateFilter = () => Boolean(tanggalMulai.value || tanggalSelesai.value)

const openCreate = () => { createOpen.value = true }
const openDetail = (item) => { selectedItem.value = item; detailOpen.value = true }
const openEdit = (item) => { editingItem.value = item; editOpen.value = true }
const confirmDelete = (item) => { deletingItem.value = item; deleteOpen.value = true }

const items = computed(() => props.pengembalian?.data || [])
const pagination = computed(() => {
    const a = props.pengembalian || {}
    return { current: a.current_page || 1, last: a.last_page || 1, total: a.total || 0, from: a.from || 0, to: a.to || 0 }
})

const activeQuery = computed(() => ({
    search: search.value,
    anggota_id: anggotaFilter.value === 'semua' ? undefined : anggotaFilter.value,
    status: statusFilter.value === 'semua' ? undefined : statusFilter.value,
    tanggal_mulai: tanggalMulai.value,
    tanggal_selesai: tanggalSelesai.value,
}))
</script>

<template>
    <AppLayout>
        <Head title="Pengembalian Lebih Bayar Potong Gaji - Kopinka" />

        <PageHeader title="Pengembalian Lebih Bayar Potong Gaji" description="Catat pengembalian dana ketika potong gaji melebihi sisa piutang (lebih bayar).">
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    Tambah Pengembalian
                </Button>
            </template>
        </PageHeader>

        <Card class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden py-0">
            <CardContent class="border-b py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="relative w-full lg:max-w-xs">
                        <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            v-model="search"
                            placeholder="Cari no transaksi, register tagihan, anggota, metode..."
                            class="pl-9"
                            @input="onSearchInput"
                        />
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Select v-model="anggotaFilter" @update:model-value="onAnggotaFilter">
                            <SelectTrigger class="w-52">
                                <SelectValue placeholder="Semua Anggota" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Anggota</SelectItem>
                                <SelectItem v-for="a in anggotas" :key="a.id" :value="a.id">
                                    {{ a.nama }} ({{ a.nip }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Select v-model="statusFilter" @update:model-value="onStatusFilter">
                            <SelectTrigger class="w-40">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Status</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="diproses">Diproses</SelectItem>
                                <SelectItem value="selesai">Selesai</SelectItem>
                                <SelectItem value="batal">Batal</SelectItem>
                            </SelectContent>
                        </Select>
                        <div class="flex items-center gap-2">
                            <CalendarRange class="text-muted-foreground size-4" />
                            <Input v-model="tanggalMulai" type="date" class="w-[10.5rem]" aria-label="Tanggal mulai" />
                            <span class="text-muted-foreground">–</span>
                            <Input v-model="tanggalSelesai" type="date" class="w-[10.5rem]" aria-label="Tanggal selesai" />
                        </div>
                        <Button variant="secondary" size="sm" @click="applyFilters">Terapkan</Button>
                        <Button v-if="hasDateFilter() || search || anggotaFilter !== 'semua' || statusFilter !== 'semua'" variant="ghost" size="sm" @click="resetFilters">
                            <X class="size-4" />
                            Reset
                        </Button>
                    </div>
                </div>
            </CardContent>

            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[1300px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="bg-muted text-muted-foreground sticky left-0 z-10 border-b px-4 py-3 text-left text-xs font-semibold uppercase">No. Transaksi</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Tgl Pengembalian</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">No. Register Tagihan</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Anggota / Karyawan</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Unit Kerja</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Lebih Bayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Dikembalikan</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Metode</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">User</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-muted/40">
                                    <td class="border-b px-4 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.no_transaksi }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3">{{ formatDate(item.tgl_pengembalian) }}</td>
                                    <td class="border-b px-4 py-3">
                                        <span class="text-primary font-medium underline cursor-pointer" @click="openDetail(item)">
                                            {{ item.no_register_tagihan || '-' }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <div class="font-medium">{{ item.nama_anggota || '-' }}</div>
                                        <div class="text-muted-foreground text-xs">{{ item.kode_anggota || '' }}</div>
                                    </td>
                                    <td class="border-b px-4 py-3 text-muted-foreground">{{ item.unit_kerja || '-' }}</td>
                                    <td class="border-b px-4 py-3 text-right font-semibold text-orange-600">{{ formatRupiah(item.jumlah_lebih_bayar) }}</td>
                                    <td class="border-b px-4 py-3 text-right font-semibold text-emerald-600">{{ formatRupiah(item.jumlah_pengembalian) }}</td>
                                    <td class="border-b px-4 py-3">
                                        <Badge :variant="getMetodeBadge(item.metode_pengembalian)">
                                            {{ item.metode_pengembalian || '-' }}
                                        </Badge>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <Badge :variant="getStatusBadge(item.status)">
                                            {{ item.status }}
                                        </Badge>
                                    </td>
                                    <td class="border-b px-4 py-3 text-muted-foreground">{{ item.user?.name || '—' }}</td>
                                    <td class="border-b px-4 py-3 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" class="size-8">
                                                    <MoreHorizontal class="size-4" />
                                                    <span class="sr-only">Aksi</span>
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-44">
                                                <DropdownMenuLabel class="text-muted-foreground text-xs">
                                                    {{ item.no_transaksi }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem @click="openDetail(item)">
                                                    <Eye class="size-4" />
                                                    Detail
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="openEdit(item)">
                                                    <Pencil class="size-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuItem variant="destructive" @click="confirmDelete(item)">
                                                    <Trash2 class="size-4" />
                                                    Hapus
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <div v-else class="flex flex-1 flex-col items-center justify-center gap-3 py-16 text-center">
                    <div class="bg-muted flex size-14 items-center justify-center rounded-full">
                        <RotateCcw class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data pengembalian lebih bayar potong gaji</p>
                        <p class="text-muted-foreground text-sm">
                            Klik "Tambah Pengembalian" untuk mencatat data pertama.
                        </p>
                    </div>
                </div>
            </div>

            <Pagination
                :current="pagination.current"
                :last="pagination.last"
                :total="pagination.total"
                :from="pagination.from"
                :to="pagination.to"
                :limit="Number(props.filters.limit || 10)"
                :base-url="'/pengembalian-lebih-bayar-potong-gaji'"
                :query="activeQuery"
            />
        </Card>

        <CreatePengembalianLebihBayarModal :open="createOpen" :registerTagihans="registerTagihans" :penerimaanAngsuranPotongGajis="penerimaanAngsuranPotongGajis" @update:open="createOpen = $event" />
        <EditPengembalianLebihBayarModal :open="editOpen" :item="editingItem" :registerTagihans="registerTagihans" :penerimaanAngsuranPotongGajis="penerimaanAngsuranPotongGajis" @update:open="editOpen = $event" />
        <DetailPengembalianLebihBayarModal :open="detailOpen" :item="selectedItem" @update:open="detailOpen = $event" />
        <DeleteConfirmModal :open="deleteOpen" :item="deletingItem" @update:open="deleteOpen = $event" />
    </AppLayout>
</template>
