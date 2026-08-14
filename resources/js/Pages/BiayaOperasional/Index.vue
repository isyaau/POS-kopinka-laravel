<script setup>
import { ref, computed, watch } from 'vue'
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
import CreateBiayaOperasionalModal from './CreateBiayaOperasionalModal.vue'
import EditBiayaOperasionalModal from './EditBiayaOperasionalModal.vue'
import DetailBiayaOperasionalModal from './DetailBiayaOperasionalModal.vue'
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
    ReceiptText,
    MoreHorizontal,
    Pencil,
    Trash2,
    Plus,
    CalendarRange,
    X,
    Eye,
} from 'lucide-vue-next'

const props = defineProps({
    biaya: { type: Object, default: () => ({ data: [] }) },
    kategoriList: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const createOpen = ref(false)
const editOpen = ref(false)
const editingBiaya = ref(null)
const detailOpen = ref(false)
const selectedBiaya = ref(null)
const deleteOpen = ref(false)
const deletingBiaya = ref(null)
const search = ref(props.filters.search || '')
const kategoriFilter = ref(props.filters.kategori || 'semua')
const tanggalMulai = ref(props.filters.tanggal_mulai || '')
const tanggalSelesai = ref(props.filters.tanggal_selesai || '')

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

// Live search dengan debounce
let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get('/biaya-operasional', {
            search: search.value,
            kategori: kategoriFilter.value || undefined,
            tanggal_mulai: tanggalMulai.value || undefined,
            tanggal_selesai: tanggalSelesai.value || undefined,
        }, { preserveState: true, replace: true })
    }, 400)
}

const applyFilters = () => {
    router.get('/biaya-operasional', {
        search: search.value,
        kategori: kategoriFilter.value || undefined,
        tanggal_mulai: tanggalMulai.value || undefined,
        tanggal_selesai: tanggalSelesai.value || undefined,
    }, { preserveState: true, replace: true })
}

const onKategoriFilter = (val) => {
    kategoriFilter.value = val
    router.get('/biaya-operasional', {
        search: search.value,
        kategori: val === 'semua' ? undefined : val,
        tanggal_mulai: tanggalMulai.value || undefined,
        tanggal_selesai: tanggalSelesai.value || undefined,
    }, { preserveState: true, replace: true })
}

const resetFilters = () => {
    search.value = ''
    kategoriFilter.value = 'semua'
    tanggalMulai.value = ''
    tanggalSelesai.value = ''
    router.get('/biaya-operasional', {}, { preserveState: true, replace: true })
}

const openCreate = () => {
    createOpen.value = true
}

const openDetail = (item) => {
    selectedBiaya.value = item
    detailOpen.value = true
}

const openEdit = (item) => {
    editingBiaya.value = item
    editOpen.value = true
}

const confirmDelete = (item) => {
    deletingBiaya.value = item
    deleteOpen.value = true
}

const items = computed(() => props.biaya?.data || [])
const pagination = computed(() => {
    const a = props.biaya || {}
    return {
        current: a.current_page || 1,
        last: a.last_page || 1,
        total: a.total || 0,
        from: a.from || 0,
        to: a.to || 0,
    }
})

// Total keseluruhan dari data terfilter (jika ada di meta)
const grandTotal = computed(() => {
    const a = props.biaya || {}
    return a.total_jumlah ? Number(a.total_jumlah) : null
})

// Query aktif yang dipertahankan saat navigasi pagination
const activeQuery = computed(() => ({
    search: search.value,
    kategori: kategoriFilter.value === 'semua' ? undefined : kategoriFilter.value,
    tanggal_mulai: tanggalMulai.value,
    tanggal_selesai: tanggalSelesai.value,
}))

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
    <AppLayout>
        <Head title="Biaya Operasional - Kopinka" />

        <PageHeader title="Biaya Operasional" description="Catat dan kelola pengeluaran operasional koperasi.">
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button @click="openCreate">
                        <Plus class="size-4" />
                        Tambah Biaya
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
                            placeholder="Cari no bukti, unit, kategori... (ketik langsung)"
                            class="pl-9"
                            @input="onSearchInput"
                        />
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Select v-model="kategoriFilter" @update:model-value="onKategoriFilter">
                            <SelectTrigger class="w-52">
                                <SelectValue placeholder="Semua Kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Kategori</SelectItem>
                                <SelectItem v-for="kat in kategoriList" :key="kat" :value="kat">
                                    {{ kat }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div class="flex items-center gap-2">
                            <CalendarRange class="text-muted-foreground size-4" />
                            <Input v-model="tanggalMulai" type="date" class="w-[10.5rem]" aria-label="Tanggal mulai" />
                            <span class="text-muted-foreground">–</span>
                            <Input v-model="tanggalSelesai" type="date" class="w-[10.5rem]" aria-label="Tanggal selesai" />
                        </div>
                        <Button variant="secondary" size="sm" @click="applyFilters">Terapkan</Button>
                        <Button v-if="hasDateFilter() || search || kategoriFilter !== 'semua'" variant="ghost" size="sm" @click="resetFilters">
                            <X class="size-4" />
                            Reset
                        </Button>
                    </div>
                    <p class="text-muted-foreground text-sm">
                        {{ pagination.total }} transaksi
                        <span v-if="grandTotal !== null" class="ml-1 font-semibold text-foreground">
                            · Total {{ formatRupiah(grandTotal) }}
                        </span>
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
                                    <th class="sticky left-0 z-10 bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">No. Bukti</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Tanggal</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Dari Unit</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Kategori</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Keterangan</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Jumlah</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="">
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.no_bukti }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3">{{ formatDate(item.tanggal) }}</td>
                                    <td class="border-b px-4 py-3">{{ item.dari_unit || '-' }}</td>
                                    <td class="border-b px-4 py-3">
                                        <Badge :variant="kategoriBadge(item.kategori)">{{ item.kategori || '-' }}</Badge>
                                    </td>
                                    <td class="max-w-xs truncate border-b px-4 py-3 text-muted-foreground">{{ item.keterangan || '-' }}</td>
                                    <td class="border-b px-4 py-3 text-right font-semibold">{{ formatRupiah(item.jumlah) }}</td>
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
                                                    {{ item.no_bukti }}
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

                <!-- Empty state -->
                <div v-else class="flex flex-1 flex-col items-center justify-center gap-3 py-16 text-center">
                    <div class="bg-muted flex size-14 items-center justify-center rounded-full">
                        <ReceiptText class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data biaya operasional</p>
                        <p class="text-muted-foreground text-sm">
                            Klik "Tambah Biaya" untuk mencatat pengeluaran pertama.
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
                :base-url="'/biaya-operasional'"
                :query="activeQuery"
            />
        </Card>

        <CreateBiayaOperasionalModal :open="createOpen" :kategori-list="kategoriList" :stores="stores" @update:open="createOpen = $event" />
        <EditBiayaOperasionalModal :open="editOpen" :biaya="editingBiaya" :kategori-list="kategoriList" :stores="stores" @update:open="editOpen = $event" />
        <DetailBiayaOperasionalModal :open="detailOpen" :biaya="selectedBiaya" @update:open="detailOpen = $event" />
        <DeleteConfirmModal :open="deleteOpen" :biaya="deletingBiaya" @update:open="deleteOpen = $event" />
    </AppLayout>
</template>
