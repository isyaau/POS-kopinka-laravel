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
import CreateKirimBarangModal from './CreateKirimBarangModal.vue'
import EditKirimBarangModal from './EditKirimBarangModal.vue'
import DeleteKirimBarangModal from './DeleteKirimBarangModal.vue'
import DetailKirimBarangModal from './DetailKirimBarangModal.vue'
import {
    Search,
    Truck,
    MoreHorizontal,
    Pencil,
    Trash2,
    X,
    Eye,
    Plus,
} from 'lucide-vue-next'

const props = defineProps({
    kirim: { type: Object, default: () => ({ data: [] }) },
    stores: { type: Array, default: () => [] },
    produk: { type: Array, default: () => [] },
    show_all: { type: Boolean, default: false },
    filters: { type: Object, default: () => ({}) },
})

const createOpen = ref(false)
const editOpen = ref(false)
const editingKirim = ref(null)
const deleteOpen = ref(false)
const deletingKirim = ref(null)
const detailOpen = ref(false)
const selectedKirim = ref(null)
const search = ref(props.filters.search || '')

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

const statusInfo = (status) => {
    switch (status) {
        case 'draft':
            return { label: 'Draft', variant: 'secondary' }
        case 'dikirim':
            return { label: 'Dikirim', variant: 'warning' }
        case 'selesai':
            return { label: 'Selesai', variant: 'success' }
        default:
            return { label: status || '-', variant: 'secondary' }
    }
}

let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get('/kirim-barang', { search: search.value }, { preserveState: true, replace: true })
    }, 400)
}

const resetSearch = () => {
    search.value = ''
    router.get('/kirim-barang', {}, { preserveState: true, replace: true })
}

const openCreate = () => { createOpen.value = true }
const openDetail = (item) => { selectedKirim.value = item; detailOpen.value = true }
const openEdit = (item) => { editingKirim.value = item; editOpen.value = true }
const confirmDelete = (item) => { deletingKirim.value = item; deleteOpen.value = true }

const items = computed(() => props.kirim?.data || [])
const pagination = computed(() => {
    const a = props.kirim || {}
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
        <Head title="Kirim Barang - Kopinka" />

        <PageHeader title="Kirim Barang" description="Mutasi produk antar unit toko.">
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    Kirim Barang
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
                            placeholder="Cari no kirim, toko asal/tujuan... (ketik langsung)"
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
                        {{ pagination.total }} kiriman
                    </p>
                </div>
            </CardContent>

            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[1000px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">No Kirim</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Tanggal</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Toko Asal</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Toko Tujuan</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Item</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Total Nilai</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.no_kirim }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3">{{ formatDate(item.tanggal) }}</td>
                                    <td class="border-b px-4 py-3">{{ item.nama_store_asal || '-' }}</td>
                                    <td class="border-b px-4 py-3">{{ item.nama_store_tujuan || '-' }}</td>
                                    <td class="border-b px-4 py-3 text-right">{{ item.total_item || 0 }}</td>
                                    <td class="border-b px-4 py-3 text-right font-semibold">{{ formatRupiah(item.total_nilai) }}</td>
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
                                                    {{ item.no_kirim }}
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
                        <Truck class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data kirim barang</p>
                        <p class="text-muted-foreground text-sm">
                            Klik "Kirim Barang" untuk mutasi produk ke toko lain.
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
                :base-url="'/kirim-barang'"
                :query="activeQuery"
            />
        </Card>

        <CreateKirimBarangModal :open="createOpen" :stores="props.stores" :produk="props.produk" @update:open="createOpen = $event" />
        <EditKirimBarangModal :open="editOpen" :kirim="editingKirim" :stores="props.stores" :produk="props.produk" @update:open="editOpen = $event" />
        <DeleteKirimBarangModal :open="deleteOpen" :kirim="deletingKirim" @update:open="deleteOpen = $event" />
        <DetailKirimBarangModal :open="detailOpen" :kirim="selectedKirim" @update:open="detailOpen = $event" />
    </AppLayout>
</template>
