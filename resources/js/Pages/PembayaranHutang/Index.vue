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
import CreatePembayaranHutangModal from './CreatePembayaranHutangModal.vue'
import EditPembayaranHutangModal from './EditPembayaranHutangModal.vue'
import DetailPembayaranHutangModal from './DetailPembayaranHutangModal.vue'
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
    Wallet,
    MoreHorizontal,
    Pencil,
    Trash2,
    Plus,
    CalendarRange,
    X,
    Eye,
} from 'lucide-vue-next'

const props = defineProps({
    pembayaran: { type: Object, default: () => ({ data: [] }) },
    suppliers: { type: Array, default: () => [] },
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

// Live search
let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
}

const applyFilters = () => {
    router.get('/pembayaran-hutang', {
        search: search.value,
        supplier_id: supplierFilter.value === 'semua' ? undefined : supplierFilter.value,
        tanggal_mulai: tanggalMulai.value || undefined,
        tanggal_selesai: tanggalSelesai.value || undefined,
    }, { preserveState: true, replace: true })
}

const onSupplierFilter = (val) => {
    supplierFilter.value = val
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    supplierFilter.value = 'semua'
    tanggalMulai.value = ''
    tanggalSelesai.value = ''
    router.get('/pembayaran-hutang', {}, { preserveState: true, replace: true })
}

const hasDateFilter = () => Boolean(tanggalMulai.value || tanggalSelesai.value)

const openCreate = () => { createOpen.value = true }
const openDetail = (item) => { selectedItem.value = item; detailOpen.value = true }
const openEdit = (item) => { editingItem.value = item; editOpen.value = true }
const confirmDelete = (item) => { deletingItem.value = item; deleteOpen.value = true }

const items = computed(() => props.pembayaran?.data || [])
const pagination = computed(() => {
    const a = props.pembayaran || {}
    return { current: a.current_page || 1, last: a.last_page || 1, total: a.total || 0, from: a.from || 0, to: a.to || 0 }
})

// Grand totals dari meta
const grandTotals = computed(() => {
    const g = props.pembayaran?.grand_totals || null
    return g
})

const activeQuery = computed(() => ({
    search: search.value,
    supplier_id: supplierFilter.value === 'semua' ? undefined : supplierFilter.value,
    tanggal_mulai: tanggalMulai.value,
    tanggal_selesai: tanggalSelesai.value,
}))

const statusBadge = (item) => {
    if (Number(item.sisa_hutang) <= 0) return 'success'
    if (Number(item.kurang_bayar) > 0 && Number(item.jumlah_bayar) > 0) return 'warning'
    return 'destructive'
}

const statusLabel = (item) => {
    if (Number(item.sisa_hutang) <= 0) return 'Lunas'
    if (Number(item.kurang_bayar) > 0 && Number(item.jumlah_bayar) > 0) return 'Sebagian'
    return 'Belum Bayar'
}
</script>

<template>
    <AppLayout>
        <Head title="Pembayaran Hutang Supplier - Kopinka" />

        <PageHeader title="Pembayaran Hutang Supplier" description="Catat pembayaran hutang ke supplier beserta log user.">
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    Tambah Pembayaran
                </Button>
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
                            placeholder="Cari no transaksi, faktur, supplier..."
                            class="pl-9"
                            @input="onSearchInput"
                        />
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Select v-model="supplierFilter" @update:model-value="onSupplierFilter">
                            <SelectTrigger class="w-52">
                                <SelectValue placeholder="Semua Supplier" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Supplier</SelectItem>
                                <SelectItem v-for="s in suppliers" :key="s.id" :value="s.id">
                                    {{ s.nama }}
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
                        <Button v-if="hasDateFilter() || search || supplierFilter !== 'semua'" variant="ghost" size="sm" @click="resetFilters">
                            <X class="size-4" />
                            Reset
                        </Button>
                    </div>
                </div>
                <div v-if="grandTotals" class="mt-3 flex flex-wrap gap-4 text-sm">
                    <span class="text-muted-foreground">{{ pagination.total }} transaksi</span>
                    <span class="font-semibold">Total Harus Dibayar: {{ formatRupiah(grandTotals.total_harus_dibayar) }}</span>
                    <span class="font-semibold text-green-600">Total Terbayar: {{ formatRupiah(grandTotals.total_terbayar) }}</span>
                    <span class="font-semibold text-red-600">Sisa Hutang: {{ formatRupiah(grandTotals.sisa_hutang) }}</span>
                </div>
            </CardContent>

            <!-- Table -->
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[1200px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="sticky left-0 z-10 bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">No. Transaksi</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Tgl Bayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Supplier</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">No Faktur</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Harus Dibayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Jumlah Bayar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Sisa Hutang</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">User</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-center text-xs font-semibold uppercase">Status</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.no_transaksi }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3">{{ formatDate(item.tanggal_bayar) }}</td>
                                    <td class="border-b px-4 py-3">
                                        <div class="font-medium">{{ item.nama_supplier || '-' }}</div>
                                        <div class="text-muted-foreground text-xs">{{ item.kode_supplier || '' }}</div>
                                    </td>
                                    <td class="border-b px-4 py-3 text-muted-foreground">{{ item.no_faktur || '-' }}</td>
                                    <td class="border-b px-4 py-3 text-right font-medium">{{ formatRupiah(item.total_harus_dibayar) }}</td>
                                    <td class="border-b px-4 py-3 text-right font-semibold">{{ formatRupiah(item.jumlah_bayar) }}</td>
                                    <td class="border-b px-4 py-3 text-right font-semibold text-red-600">{{ formatRupiah(item.sisa_hutang) }}</td>
                                    <td class="border-b px-4 py-3 text-muted-foreground">{{ item.user?.name || '—' }}</td>
                                    <td class="border-b px-4 py-3 text-center">
                                        <Badge :variant="statusBadge(item)">{{ statusLabel(item) }}</Badge>
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

                <!-- Empty state -->
                <div v-else class="flex flex-1 flex-col items-center justify-center gap-3 py-16 text-center">
                    <div class="bg-muted flex size-14 items-center justify-center rounded-full">
                        <Wallet class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data pembayaran hutang</p>
                        <p class="text-muted-foreground text-sm">
                            Klik "Tambah Pembayaran" untuk mencatat pembayaran pertama.
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
                :base-url="'/pembayaran-hutang'"
                :query="activeQuery"
            />
        </Card>

        <CreatePembayaranHutangModal :open="createOpen" :suppliers="suppliers" @update:open="createOpen = $event" />
        <EditPembayaranHutangModal :open="editOpen" :item="editingItem" :suppliers="suppliers" @update:open="editOpen = $event" />
        <DetailPembayaranHutangModal :open="detailOpen" :item="selectedItem" @update:open="detailOpen = $event" />
        <DeleteConfirmModal :open="deleteOpen" :item="deletingItem" @update:open="deleteOpen = $event" />
    </AppLayout>
</template>
