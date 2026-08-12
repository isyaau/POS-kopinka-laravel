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
import CreateProdukModal from './CreateProdukModal.vue'
import EditProdukModal from './EditProdukModal.vue'
import DeleteProdukModal from './DeleteProdukModal.vue'
import ImportProdukModal from './ImportProdukModal.vue'
import DetailProdukModal from './DetailProdukModal.vue'
import { toast } from '@/components/ui/sonner'
import {
    Search,
    Package,
    MoreHorizontal,
    Pencil,
    Trash2,
    UserPlus,
    X,
    Download,
    Upload,
    FileSpreadsheet,
    Eye,
    AlertTriangle,
    Archive,
    ArchiveRestore,
} from 'lucide-vue-next'

const props = defineProps({
    produk: { type: Object, default: () => ({ data: [] }) },
    suppliers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const createOpen = ref(false)
const editOpen = ref(false)
const editingProduk = ref(null)
const deleteOpen = ref(false)
const deletingProduk = ref(null)
const importOpen = ref(false)
const detailOpen = ref(false)
const selectedProduk = ref(null)
const search = ref(props.filters.search || '')
const tab = ref(props.filters.tab || 'aktif')

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

// Live search dengan debounce
let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get('/produk', {
            search: search.value,
        }, { preserveState: true, replace: true })
    }, 400)
}

const resetSearch = () => {
    search.value = ''
    router.get('/produk', { tab: tab.value }, { preserveState: true, replace: true })
}

// Ganti tab: aktif / arsip / semua
const setTab = (t) => {
    tab.value = t
    router.get('/produk', { tab: t, search: search.value }, { preserveState: true, replace: true })
}

// Pulihkan produk dari arsip
const restoreProduk = (item) => {
    if (!confirm(`Pulihkan produk "${item.nama_barang}"?`)) return
    router.post(`/produk/${item.id}/restore`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Produk berhasil dipulihkan.')
        },
    })
}

const handleExport = () => {
    const params = new URLSearchParams()
    if (search.value) params.set('search', search.value)

    window.location.href = `/produk/export?${params.toString()}`
}

const openCreate = () => {
    createOpen.value = true
}

const openDetail = (item) => {
    selectedProduk.value = item
    detailOpen.value = true
}

const openEdit = (item) => {
    editingProduk.value = item
    editOpen.value = true
}

const confirmDelete = (item) => {
    deletingProduk.value = item
    deleteOpen.value = true
}

const items = computed(() => props.produk?.data || [])
const pagination = computed(() => {
    const a = props.produk || {}
    return {
        current: a.current_page || 1,
        last: a.last_page || 1,
        total: a.total || 0,
        from: a.from || 0,
        to: a.to || 0,
    }
})

const activeQuery = computed(() => ({
    search: search.value,
    tab: tab.value,
}))

const lowStock = (p) => p.stok <= p.stok_minimum
</script>

<template>
    <AppLayout>
        <Head title="Persediaan Produk - Kopinka" />

        <PageHeader title="Persediaan Produk" description="Kelola data barang dan stok per toko.">
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
                            <DropdownMenuItem @click="handleExport">
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
                        Tambah Produk
                    </Button>
                </div>
            </template>
        </PageHeader>

        <Card class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden py-0">
            <!-- Filter & Search bar -->
            <CardContent class="border-b py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex flex-col gap-3">
                        <!-- Tab Aktif / Arsip / Semua -->
                        <div class="bg-muted inline-flex w-fit rounded-lg p-1">
                            <button
                                v-for="t in [
                                    { key: 'aktif', label: 'Aktif' },
                                    { key: 'arsip', label: 'Arsip' },
                                    { key: 'semua', label: 'Semua' },
                                ]"
                                :key="t.key"
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="
                                    tab === t.key
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                @click="setTab(t.key)"
                            >
                                {{ t.label }}
                            </button>
                        </div>
                        <div class="relative w-full lg:w-80">
                            <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                v-model="search"
                                placeholder="Cari kode, nama, kategori... (ketik langsung)"
                                class="pl-9"
                                @input="onSearchInput"
                            />
                            <button
                                v-if="search"
                                class="text-muted-foreground hover:text-foreground absolute top-1/2 right-2.5 -translate-y-1/2"
                                aria-label="Hapus pencarian"
                                @click="resetSearch"
                            >
                                <X class="size-4" />
                            </button>
                        </div>
                    </div>
                    <p class="text-muted-foreground text-sm">
                        {{ pagination.total }} produk
                    </p>
                </div>
            </CardContent>

            <!-- Table -->
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[1100px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">ID</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Kode Barang</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Nama Barang</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Kategori</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Satuan</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Harga Beli</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Harga Jual</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Diskon</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Stok</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Stok Min</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Expired</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">PPN</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="text-muted-foreground border-b px-4 py-3">{{ item.id }}</td>
                                    <td class="border-b px-4 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.kode_barang }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <p class="font-medium truncate">{{ item.nama_barang }}</p>
                                    </td>
                                    <td class="border-b px-4 py-3">{{ item.kategori || '-' }}</td>
                                    <td class="border-b px-4 py-3">{{ item.satuan || '-' }}</td>
                                    <td class="border-b px-4 py-3 text-right">{{ formatRupiah(item.harga_beli) }}</td>
                                    <td class="border-b px-4 py-3 text-right font-medium">{{ formatRupiah(item.harga_jual) }}</td>
                                    <td class="border-b px-4 py-3 text-right">
                                        <span v-if="item.diskon > 0" class="text-destructive font-medium">{{ formatRupiah(item.diskon) }}</span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="border-b px-4 py-3 text-right">
                                        <Badge :variant="lowStock(item) ? 'warning' : 'success'">
                                            <AlertTriangle v-if="lowStock(item)" class="size-3" />
                                            {{ item.stok }}
                                        </Badge>
                                    </td>
                                    <td class="border-b px-4 py-3 text-right">{{ item.stok_minimum }}</td>
                                    <td class="border-b px-4 py-3">{{ formatDate(item.tanggal_expired) }}</td>
                                    <td class="border-b px-4 py-3 text-right">{{ item.ppn }}%</td>
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
                                                    {{ item.nama_barang }}
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
                                                    <Archive class="size-4" />
                                                    Arsipkan
                                                </DropdownMenuItem>
                                                <DropdownMenuItem v-if="item.deleted_at" @click="restoreProduk(item)">
                                                    <ArchiveRestore class="size-4" />
                                                    Pulihkan
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
                        <Package class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data produk</p>
                        <p class="text-muted-foreground text-sm">
                            {{ tab === 'arsip' ? 'Tidak ada produk yang diarsipkan.' : 'Klik "Tambah Produk" untuk menambahkan data pertama.' }}
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
                :base-url="'/produk'"
                :query="activeQuery"
            />
        </Card>

        <CreateProdukModal :open="createOpen" :suppliers="props.suppliers" @update:open="createOpen = $event" />
        <EditProdukModal :open="editOpen" :produk="editingProduk" :suppliers="props.suppliers" @update:open="editOpen = $event" />
        <DeleteProdukModal :open="deleteOpen" :produk="deletingProduk" @update:open="deleteOpen = $event" />
        <ImportProdukModal :open="importOpen" @update:open="importOpen = $event" />
        <DetailProdukModal :open="detailOpen" :produk="selectedProduk" @update:open="detailOpen = $event" />
    </AppLayout>
</template>
