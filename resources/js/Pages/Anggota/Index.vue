<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
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
import CreateAnggotaModal from './CreateAnggotaModal.vue'
import EditAnggotaModal from './EditAnggotaModal.vue'
import ImportAnggotaModal from './ImportAnggotaModal.vue'
import DetailAnggotaModal from './DetailAnggotaModal.vue'
import { toast } from '@/components/ui/sonner'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Search,
    UserPlus,
    MoreHorizontal,
    Pencil,
    Trash2,
    Users,
    Download,
    Upload,
    FileSpreadsheet,
    CalendarRange,
    X,
    Eye,
} from 'lucide-vue-next'

const props = defineProps({
    anggota: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
})

const createOpen = ref(false)
const editOpen = ref(false)
const editingAnggota = ref(null)
const importOpen = ref(false)
const detailOpen = ref(false)
const selectedAnggota = ref(null)
const search = ref(props.filters.search || '')
const tanggalMulai = ref(props.filters.tanggal_mulai || '')
const tanggalSelesai = ref(props.filters.tanggal_selesai || '')
const statusFilter = ref(props.filters.status_filter || 'semua')

// Flash toast global (dari session flash backend)
const page = usePage()
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success)
        if (flash?.error) toast.error(flash.error)
    },
    { immediate: true },
)

// Format Rupiah
const formatRupiah = (val) => {
    const n = Number(val || 0)
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(n)
}

// Format tanggal DD-MM-YYYY
const formatDate = (val) => {
    if (!val) return '-'
    const d = new Date(val)
    if (Number.isNaN(d.getTime())) return '-'
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}-${month}-${year}`
}

const hasDateFilter = () => Boolean(tanggalMulai.value || tanggalSelesai.value)

// Live search dengan debounce (tanpa perlu Enter)
let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get('/anggota', {
            search: search.value,
            tanggal_mulai: tanggalMulai.value || undefined,
            tanggal_selesai: tanggalSelesai.value || undefined,
            status_filter: statusFilter.value || undefined,
        }, { preserveState: true, replace: true })
    }, 400)
}

const applyFilters = () => {
    router.get('/anggota', {
        search: search.value,
        tanggal_mulai: tanggalMulai.value || undefined,
        tanggal_selesai: tanggalSelesai.value || undefined,
        status_filter: statusFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

const onStatusFilter = (val) => {
    statusFilter.value = val
    router.get('/anggota', {
        search: search.value,
        tanggal_mulai: tanggalMulai.value || undefined,
        tanggal_selesai: tanggalSelesai.value || undefined,
        status_filter: val === 'semua' ? undefined : val,
    }, { preserveState: true, replace: true })
}

const resetFilters = () => {
    search.value = ''
    tanggalMulai.value = ''
    tanggalSelesai.value = ''
    statusFilter.value = 'semua'
    router.get('/anggota', {}, { preserveState: true, replace: true })
}

const handleExport = (format = 'xlsx') => {
    const params = new URLSearchParams()
    if (search.value) params.set('search', search.value)
    if (tanggalMulai.value) params.set('tanggal_mulai', tanggalMulai.value)
    if (tanggalSelesai.value) params.set('tanggal_selesai', tanggalSelesai.value)
    if (statusFilter.value && statusFilter.value !== 'semua') params.set('status_filter', statusFilter.value)

    window.location.href = `/anggota/export?${params.toString()}`
}

const openCreate = () => {
    createOpen.value = true
}

const openDetail = (item) => {
    selectedAnggota.value = item
    detailOpen.value = true
}

const openEdit = (item) => {
    editingAnggota.value = item
    editOpen.value = true
}

const deleteForm = useForm({})

const confirmDelete = (item) => {
    if (window.confirm(`Hapus anggota "${item.nama}"? Tindakan ini tidak dapat dibatalkan.`)) {
        deleteForm.delete(`/anggota/${item.id}`, {
            preserveScroll: true,
        })
    }
}

const items = computed(() => props.anggota?.data || [])
const pagination = computed(() => {
    const a = props.anggota || {}
    return {
        current: a.current_page || 1,
        last: a.last_page || 1,
        total: a.total || 0,
        from: a.from || 0,
        to: a.to || 0,
    }
})

// Query aktif yang dipertahankan saat navigasi pagination
const activeQuery = computed(() => ({
    search: search.value,
    tanggal_mulai: tanggalMulai.value,
    tanggal_selesai: tanggalSelesai.value,
    status_filter: statusFilter.value === 'semua' ? undefined : statusFilter.value,
}))

const statusBadge = (aktif) => (aktif ? 'success' : 'inactive')

const limitBadge = (a) => (a.status_limit ? 'warning' : 'success')

const purnaBadge = (purna) => (purna ? 'purna' : 'secondary')
</script>

<template>
    <AppLayout>
        <Head title="Anggota - Kopinka" />

        <PageHeader title="Anggota" description="Kelola data anggota dan karyawan Kopinka.">
            <template #actions>
                <div class="flex items-center gap-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline">
                                <Download class="size-4" />
                                Export
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-44">
                            <DropdownMenuItem @click="handleExport('xlsx')">
                                <FileSpreadsheet class="size-4" />
                                Export Excel (.xlsx)
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Button variant="outline" @click="importOpen = true">
                        <Upload class="size-4" />
                        Import
                    </Button>
                    <Button @click="openCreate">
                        <UserPlus class="size-4" />
                        Tambah Anggota
                    </Button>
                </div>
            </template>
        </PageHeader>

        <Card class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden py-0">
            <!-- Filter & Search bar -->
            <CardContent class="border-b py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="relative w-full lg:max-w-xs">
                        <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            v-model="search"
                            placeholder="Cari nama, NIP, divisi... (ketik langsung)"
                            class="pl-9"
                            @input="onSearchInput"
                        />
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Select v-model="statusFilter" @update:model-value="onStatusFilter">
                            <SelectTrigger class="w-48">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Status</SelectItem>
                                <SelectItem value="anggota_aktif">Anggota Aktif</SelectItem>
                                <SelectItem value="anggota_aktif_purna">Anggota Aktif Purna</SelectItem>
                                <SelectItem value="anggota_diblokir">Anggota Diblokir</SelectItem>
                                <SelectItem value="anggota_pasif_purna">Anggota Pasif Purna</SelectItem>
                                <SelectItem value="karyawan_aktif">Karyawan Aktif</SelectItem>
                                <SelectItem value="karyawan_diblokir">Karyawan Diblokir</SelectItem>
                                <SelectItem value="karyawan_pasif_purna">Karyawan Pasif Purna</SelectItem>
                            </SelectContent>
                        </Select>
                        <div class="flex items-center gap-2">
                            <CalendarRange class="text-muted-foreground size-4" />
                            <Input v-model="tanggalMulai" type="date" class="w-[10.5rem]" aria-label="Tanggal mulai" />
                            <span class="text-muted-foreground">–</span>
                            <Input v-model="tanggalSelesai" type="date" class="w-[10.5rem]" aria-label="Tanggal selesai" />
                        </div>
                        <Button variant="secondary" size="sm" @click="applyFilters">Terapkan</Button>
                        <Button v-if="hasDateFilter() || search || statusFilter" variant="ghost" size="sm" @click="resetFilters">
                            <X class="size-4" />
                            Reset
                        </Button>
                    </div>
                    <p class="text-muted-foreground text-sm">
                        {{ pagination.total }} anggota
                    </p>
                </div>
            </CardContent>

            <!-- Table -->
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[900px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr class="">
                                    <th class="sticky left-0 z-10 bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">NIP</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Nama</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Divisi</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">No. HP</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Tgl Terdaftar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Aktif</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Limit</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="">
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.nip }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="bg-primary/10 text-primary flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold uppercase">
                                                {{ (item.nama || '?').charAt(0) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium truncate">{{ item.nama }}</p>
                                                <p v-if="item.status_purna" class="text-muted-foreground text-xs">
                                                    <Badge class="mt-0.5" :variant="purnaBadge(true)">Purna</Badge>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge variant="secondary" class="capitalize">
                                            {{ item.status === 'karyawan' ? 'Karyawan' : 'Anggota' }}
                                        </Badge>
                                    </td>
                                    <td class="text-muted-foreground border-b px-4 py-3">{{ item.divisi_pekerjaan || '-' }}</td>
                                    <td class="border-b px-4 py-3">{{ item.no_hp || '-' }}</td>
                                    <td class="border-b px-4 py-3">{{ formatDate(item.tgl_terdaftar) }}</td>
                                    <td class="border-b px-4 py-3">
                                        <Badge :variant="statusBadge(item.status_aktif)">
                                            {{ item.status_aktif ? 'Aktif' : 'Tidak Aktif' }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="limitBadge(item)">
                                            {{ item.status_limit ? `Limit: ${formatRupiah(item.limit_transaksi)}` : 'Tanpa Limit' }}
                                        </Badge>
                                    </td>
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
                                                    {{ item.nama }}
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

                <!-- Empty state (menggantikan tabel) -->
                <div v-else class="flex flex-1 flex-col items-center justify-center gap-3 py-16 text-center">
                    <div class="bg-muted flex size-14 items-center justify-center rounded-full">
                        <Users class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data anggota</p>
                        <p class="text-muted-foreground text-sm">
                            Klik "Tambah Anggota" untuk menambahkan data pertama.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination
                :current="pagination.current"
                :last="pagination.last"
                :total="pagination.total"
                :from="pagination.from"
                :to="pagination.to"
                :limit="Number(props.filters.limit || 10)"
                :base-url="'/anggota'"
                :query="activeQuery"
            />
        </Card>

        <CreateAnggotaModal :open="createOpen" @update:open="createOpen = $event" />
        <EditAnggotaModal :open="editOpen" :anggota="editingAnggota" @update:open="editOpen = $event" />
        <ImportAnggotaModal :open="importOpen" @update:open="importOpen = $event" />
        <DetailAnggotaModal :open="detailOpen" :anggota="selectedAnggota" @update:open="detailOpen = $event" />
    </AppLayout>
</template>
