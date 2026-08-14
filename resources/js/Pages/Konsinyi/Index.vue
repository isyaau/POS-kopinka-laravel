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
import CreateKonsinyiModal from './CreateKonsinyiModal.vue'
import EditKonsinyiModal from './EditKonsinyiModal.vue'
import DetailKonsinyiModal from './DetailKonsinyiModal.vue'
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
    PackageX,
    MoreHorizontal,
    Pencil,
    Trash2,
    Plus,
    CalendarRange,
    X,
    Eye,
} from 'lucide-vue-next'

const props = defineProps({
    konsinyi: { type: Object, default: () => ({ data: [] }) },
    suppliers: { type: Array, default: () => [] },
    produks: { type: Array, default: () => [] },
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
const jenisFilter = ref(props.filters.jenis || 'semua')
const supplierFilter = ref(props.filters.supplier_id || 'semua')
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

let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
}

const applyFilters = () => {
    router.get('/konsinyi', {
        search: search.value,
        jenis: jenisFilter.value === 'semua' ? undefined : jenisFilter.value,
        supplier_id: supplierFilter.value === 'semua' ? undefined : supplierFilter.value,
        tanggal_mulai: tanggalMulai.value || undefined,
        tanggal_selesai: tanggalSelesai.value || undefined,
    }, { preserveState: true, replace: true })
}

const onJenisFilter = (val) => { jenisFilter.value = val; applyFilters() }
const onSupplierFilter = (val) => { supplierFilter.value = val; applyFilters() }

const resetFilters = () => {
    search.value = ''
    jenisFilter.value = 'semua'
    supplierFilter.value = 'semua'
    tanggalMulai.value = ''
    tanggalSelesai.value = ''
    router.get('/konsinyi', {}, { preserveState: true, replace: true })
}

const hasDateFilter = () => Boolean(tanggalMulai.value || tanggalSelesai.value)

const openCreate = () => { createOpen.value = true }
const openDetail = (item) => { selectedItem.value = item; detailOpen.value = true }
const openEdit = (item) => { editingItem.value = item; editOpen.value = true }
const confirmDelete = (item) => { deletingItem.value = item; deleteOpen.value = true }

const items = computed(() => props.konsinyi?.data || [])
const pagination = computed(() => {
    const a = props.konsinyi || {}
    return { current: a.current_page || 1, last: a.last_page || 1, total: a.total || 0, from: a.from || 0, to: a.to || 0 }
})

const grandTotals = computed(() => props.konsinyi?.grand_totals || null)

const activeQuery = computed(() => ({
    search: search.value,
    jenis: jenisFilter.value === 'semua' ? undefined : jenisFilter.value,
    supplier_id: supplierFilter.value === 'semua' ? undefined : supplierFilter.value,
    tanggal_mulai: tanggalMulai.value,
    tanggal_selesai: tanggalSelesai.value,
}))

const jenisBadge = (jenis) => (jenis === 'retur' ? 'warning' : 'success')

const jenisLabel = (jenis) => (jenis === 'retur' ? 'Retur' : 'Pembayaran')
</script>

<template>
    <AppLayout>
        <Head title="Retur & Pembayaran Barang Konsinyi - Kopinka" />

        <PageHeader title="Retur & Pembayaran Barang Konsinyi" description="Catat retur & pembayaran barang titipan (konsinyi) ke supplier.">
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    Tambah Transaksi
                </Button>
            </template>
        </PageHeader>

        <Card class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden py-0">
            <CardContent class="border-b py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="relative w-full lg:max-w-xs">
                        <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input v-model="search" placeholder="Cari no transaksi, supplier, barang..." class="pl-9" @input="onSearchInput" />
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Select v-model="jenisFilter" @update:model-value="onJenisFilter">
                            <SelectTrigger class="w-40">
                                <SelectValue placeholder="Semua Jenis" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Jenis</SelectItem>
                                <SelectItem value="retur">Retur</SelectItem>
                                <SelectItem value="pembayaran">Pembayaran</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select v-model="supplierFilter" @update:model-value="onSupplierFilter">
                            <SelectTrigger class="w-52">
                                <SelectValue placeholder="Semua Supplier" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Supplier</SelectItem>
                                <SelectItem v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.nama }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <div class="flex items-center gap-2">
                            <CalendarRange class="text-muted-foreground size-4" />
                            <Input v-model="tanggalMulai" type="date" class="w-[10.5rem]" aria-label="Tanggal mulai" />
                            <span class="text-muted-foreground">–</span>
                            <Input v-model="tanggalSelesai" type="date" class="w-[10.5rem]" aria-label="Tanggal selesai" />
                        </div>
                        <Button variant="secondary" size="sm" @click="applyFilters">Terapkan</Button>
                        <Button v-if="hasDateFilter() || search || jenisFilter !== 'semua' || supplierFilter !== 'semua'" variant="ghost" size="sm" @click="resetFilters">
                            <X class="size-4" />
                            Reset
                        </Button>
                    </div>
                </div>
                <div v-if="grandTotals" class="mt-3 flex flex-wrap gap-4 text-sm">
                    <span class="text-muted-foreground">{{ pagination.total }} transaksi</span>
                    <span class="font-semibold">Total: {{ formatRupiah(grandTotals.total) }}</span>
                    <span class="font-semibold text-green-600">Terbayar: {{ formatRupiah(grandTotals.jumlah_bayar) }}</span>
                    <span class="font-semibold text-red-600">Kurang Bayar: {{ formatRupiah(grandTotals.kurang_bayar) }}</span>
                </div>
            </CardContent>

            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[1200px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="sticky left-0 z-10 bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">No. Transaksi</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Tgl</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Jenis</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Supplier</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Barang</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Total</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Jumlah Bayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Kurang</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">User</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.no_transaksi }}</span>
                                    </td>
                                    <td class="border-b px-4 py-3">{{ formatDate(item.tgl_transaksi) }}</td>
                                    <td class="border-b px-4 py-3">
                                        <Badge :variant="jenisBadge(item.jenis)">{{ jenisLabel(item.jenis) }}</Badge>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <div class="font-medium">{{ item.nama_supplier || '-' }}</div>
                                        <div class="text-muted-foreground text-xs">{{ item.kode_supplier || '' }}</div>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <div v-if="item.details && item.details.length" class="font-medium">{{ item.details[0].nama_barang || '-' }}</div>
                                        <div v-else class="font-medium">—</div>
                                        <div class="text-muted-foreground text-xs">
                                            {{ item.details?.length || 0 }} item{{ (item.details?.length || 0) > 1 ? '' : '' }}
                                        </div>
                                    </td>
                                    <td class="border-b px-4 py-3 text-right font-medium">{{ formatRupiah(item.total) }}</td>
                                    <td class="border-b px-4 py-3 text-right font-semibold">{{ formatRupiah(item.jumlah_bayar) }}</td>
                                    <td class="border-b px-4 py-3 text-right font-semibold text-red-600">{{ formatRupiah(item.kurang_bayar) }}</td>
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
                                                <DropdownMenuLabel class="text-muted-foreground text-xs">{{ item.no_transaksi }}</DropdownMenuLabel>
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
                        <PackageX class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data konsinyi</p>
                        <p class="text-muted-foreground text-sm">Klik "Tambah Transaksi" untuk mencatat retur/pembayaran pertama.</p>
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
                :base-url="'/konsinyi'"
                :query="activeQuery"
            />
        </Card>

        <CreateKonsinyiModal :open="createOpen" :suppliers="suppliers" :produks="produks" @update:open="createOpen = $event" />
        <EditKonsinyiModal :open="editOpen" :item="editingItem" :suppliers="suppliers" :produks="produks" @update:open="editOpen = $event" />
        <DetailKonsinyiModal :open="detailOpen" :item="selectedItem" @update:open="detailOpen = $event" />
        <DeleteConfirmModal :open="deleteOpen" :item="deletingItem" @update:open="deleteOpen = $event" />
    </AppLayout>
</template>
