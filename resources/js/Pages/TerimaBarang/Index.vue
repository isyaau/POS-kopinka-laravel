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
import CreateTerimaBarangModal from './CreateTerimaBarangModal.vue'
import EditTerimaBarangModal from './EditTerimaBarangModal.vue'
import DeleteTerimaBarangModal from './DeleteTerimaBarangModal.vue'
import DetailTerimaBarangModal from './DetailTerimaBarangModal.vue'
import {
    Search,
    PackageCheck,
    MoreHorizontal,
    Pencil,
    Trash2,
    X,
    Eye,
    Plus,
} from 'lucide-vue-next'

const props = defineProps({
    terima: { type: Object, default: () => ({ data: [] }) },
    suppliers: { type: Array, default: () => [] },
    produk: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const createOpen = ref(false)
const editOpen = ref(false)
const editingItem = ref(null)
const deleteOpen = ref(false)
const deletingItem = ref(null)
const detailOpen = ref(false)
const selectedItem = ref(null)
const search = ref(props.filters.search || '')

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

// Label tipe penerimaan
const tipeInfo = (tipe) => {
    switch (tipe) {
        case 'konsinyasi':
            return { label: 'Konsinyasi (Titipan)', variant: 'default' }
        case 'retur_toko':
            return { label: 'Retur Toko', variant: 'secondary' }
        default:
            return { label: tipe || '-', variant: 'secondary' }
    }
}

// Label status
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

// Live search dengan debounce
let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get('/terima-barang', {
            search: search.value,
        }, { preserveState: true, replace: true })
    }, 400)
}

const resetSearch = () => {
    search.value = ''
    router.get('/terima-barang', {}, { preserveState: true, replace: true })
}

const openCreate = () => { createOpen.value = true }
const openDetail = (item) => { selectedItem.value = item; detailOpen.value = true }
const openEdit = (item) => { editingItem.value = item; editOpen.value = true }
const confirmDelete = (item) => { deletingItem.value = item; deleteOpen.value = true }

const items = computed(() => props.terima?.data || [])
const pagination = computed(() => {
    const a = props.terima || {}
    return {
        current: a.current_page || 1,
        last: a.last_page || 1,
        total: a.total || 0,
        from: a.from || 0,
        to: a.to || 0,
    }
})

const activeQuery = computed(() => ({ search: search.value }))
</script>

<template>
    <AppLayout>
        <Head title="Terima Barang - Kopinka" />

        <PageHeader title="Terima Barang" description="Catat penerimaan barang dari supplier/konsinyor & retur toko.">
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    Tambah Penerimaan
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
                            placeholder="Cari no terima, surat jalan, supplier... (ketik langsung)"
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
                    <p class="text-muted-foreground text-sm">
                        {{ pagination.total }} penerimaan
                    </p>
                </div>
            </CardContent>

            <!-- Table -->
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[1000px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">No Terima</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">No Surat Jalan</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Tanggal</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Supplier</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Tipe</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Item</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.no_terima }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3">{{ item.no_surat_jalan || '-' }}</td>
                                    <td class="border-b px-4 py-3">{{ formatDate(item.tanggal) }}</td>
                                    <td class="border-b px-4 py-3">{{ item.nama_supplier || '-' }}</td>
                                    <td class="border-b px-4 py-3">
                                        <Badge :variant="tipeInfo(item.tipe).variant">{{ tipeInfo(item.tipe).label }}</Badge>
                                    </td>
                                    <td class="border-b px-4 py-3 text-right font-semibold">{{ item.details?.length || 0 }}</td>
                                    <td class="border-b px-4 py-3">
                                        <Badge :variant="statusInfo(item.status).variant">
                                            {{ statusInfo(item.status).label }}
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
                                                    {{ item.no_terima }}
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
                        <PackageCheck class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data penerimaan barang</p>
                        <p class="text-muted-foreground text-sm">
                            Klik "Tambah Penerimaan" untuk mencatat barang masuk pertama.
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
                :base-url="'/terima-barang'"
                :query="activeQuery"
            />
        </Card>

        <CreateTerimaBarangModal :open="createOpen" :suppliers="props.suppliers" :produk="props.produk" @update:open="createOpen = $event" />
        <EditTerimaBarangModal :open="editOpen" :terima="editingItem" :suppliers="props.suppliers" :produk="props.produk" @update:open="editOpen = $event" />
        <DeleteTerimaBarangModal :open="deleteOpen" :terima="deletingItem" @update:open="deleteOpen = $event" />
        <DetailTerimaBarangModal :open="detailOpen" :terima="selectedItem" @update:open="detailOpen = $event" />
    </AppLayout>
</template>
