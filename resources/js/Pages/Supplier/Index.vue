<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Pagination } from '@/components/ui/pagination'
import CreateSupplierModal from './CreateSupplierModal.vue'
import EditSupplierModal from './EditSupplierModal.vue'
import DeleteSupplierModal from './DeleteSupplierModal.vue'
import ImportSupplierModal from './ImportSupplierModal.vue'
import SupplierDetailModal from './SupplierDetailModal.vue'
import { toast } from '@/components/ui/sonner'
import {
    Search,
    Truck,
    MoreHorizontal,
    Pencil,
    Trash2,
    UserPlus,
    X,
    Download,
    Upload,
    FileSpreadsheet,
    Eye,
    Archive,
    ArchiveRestore,
} from 'lucide-vue-next'

const props = defineProps({
    suppliers: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
})

const createOpen = ref(false)
const editOpen = ref(false)
const editingSupplier = ref(null)
const deleteOpen = ref(false)
const deletingSupplier = ref(null)
const importOpen = ref(false)
const detailOpen = ref(false)
const selectedSupplier = ref(null)
const search = ref(props.filters.search || '')
const tab = ref(props.filters.tab || 'aktif')

// Live search dengan debounce
let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get('/suppliers', {
            search: search.value,
        }, { preserveState: true, replace: true })
    }, 400)
}

const resetSearch = () => {
    search.value = ''
    router.get('/suppliers', { tab: tab.value }, { preserveState: true, replace: true })
}

// Ganti tab: aktif / arsip / semua
const setTab = (t) => {
    tab.value = t
    router.get('/suppliers', { tab: t, search: search.value }, { preserveState: true, replace: true })
}

// Pulihkan supplier dari arsip
const restoreSupplier = (item) => {
    if (!confirm(`Pulihkan supplier "${item.nama}"?`)) return
    router.post(`/suppliers/${item.id}/restore`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Supplier berhasil dipulihkan.')
        },
    })
}

const handleExport = () => {
    const params = new URLSearchParams()
    if (search.value) params.set('search', search.value)

    window.location.href = `/suppliers/export?${params.toString()}`
}

const openCreate = () => {
    createOpen.value = true
}

const openDetail = (item) => {
    selectedSupplier.value = item
    detailOpen.value = true
}

const openEdit = (item) => {
    editingSupplier.value = item
    editOpen.value = true
}

const confirmDelete = (item) => {
    deletingSupplier.value = item
    deleteOpen.value = true
}

const items = computed(() => props.suppliers?.data || [])
const pagination = computed(() => {
    const a = props.suppliers || {}
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
</script>

<template>
    <AppLayout>
        <Head title="Supplier - Kopinka" />

        <PageHeader title="Supplier" description="Kelola data supplier Kopinka.">
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
                        Tambah Supplier
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
                                placeholder="Cari kode, nama, kontak... (ketik langsung)"
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
                        {{ pagination.total }} supplier
                    </p>
                </div>
            </CardContent>

            <!-- Table -->
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[900px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="sticky left-0 z-10 bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Kode</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Nama Supplier</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Alamat</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Contact Person</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">No Telp/HP</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Keterangan</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.kode }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="bg-primary/10 text-primary flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold uppercase">
                                                {{ (item.nama || '?').charAt(0) }}
                                            </div>
                                            <p class="font-medium truncate">{{ item.nama }}</p>
                                        </div>
                                    </td>
                                    <td class="text-muted-foreground border-b px-4 py-3 max-w-[220px]">
                                        <p class="truncate">{{ item.alamat || '-' }}</p>
                                    </td>
                                    <td class="border-b px-4 py-3">{{ item.contact_person || '-' }}</td>
                                    <td class="border-b px-4 py-3">{{ item.no_telp || '-' }}</td>
                                    <td class="text-muted-foreground border-b px-4 py-3 max-w-[200px]">
                                        <p class="truncate">{{ item.keterangan || '-' }}</p>
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
                                                    <Archive class="size-4" />
                                                    Arsipkan
                                                </DropdownMenuItem>
                                                <DropdownMenuItem v-if="item.deleted_at" @click="restoreSupplier(item)">
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
                        <Truck class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data supplier</p>
                        <p class="text-muted-foreground text-sm">
                            {{ tab === 'arsip' ? 'Tidak ada supplier yang diarsipkan.' : 'Klik "Tambah Supplier" untuk menambahkan data pertama.' }}
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
                :base-url="'/suppliers'"
                :query="activeQuery"
            />
        </Card>

        <CreateSupplierModal :open="createOpen" @update:open="createOpen = $event" />
        <EditSupplierModal :open="editOpen" :supplier="editingSupplier" @update:open="editOpen = $event" />
        <DeleteSupplierModal :open="deleteOpen" :supplier="deletingSupplier" @update:open="deleteOpen = $event" />
        <ImportSupplierModal :open="importOpen" @update:open="importOpen = $event" />
        <SupplierDetailModal :open="detailOpen" :supplier="selectedSupplier" @update:open="detailOpen = $event" />
    </AppLayout>
</template>
