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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Store,
    Plus,
    Search,
    MoreHorizontal,
    Pencil,
    Trash2,
    Building2,
    MapPin,
    Phone,
    X,
    Eye,
} from 'lucide-vue-next'
import TokoFormModal from './TokoFormModal.vue'
import TokoDeleteModal from './TokoDeleteModal.vue'
import TokoDetailModal from './TokoDetailModal.vue'

const props = defineProps({
    stores: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
})

const formOpen = ref(false)
const editingStore = ref(null)
const deleteOpen = ref(false)
const deletingStore = ref(null)
const detailOpen = ref(false)
const selectedStore = ref(null)
const search = ref(props.filters.search || '')
const tipeFilter = ref(props.filters.tipe || 'semua')

// Live search dengan debounce
let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get('/toko', {
            search: search.value,
            tipe: tipeFilter.value === 'semua' ? undefined : tipeFilter.value,
        }, { preserveState: true, replace: true })
    }, 400)
}

const onTipeFilter = (val) => {
    tipeFilter.value = val
    router.get('/toko', {
        search: search.value,
        tipe: val === 'semua' ? undefined : val,
    }, { preserveState: true, replace: true })
}

const resetFilters = () => {
    search.value = ''
    tipeFilter.value = 'semua'
    router.get('/toko', {}, { preserveState: true, replace: true })
}

const openCreate = () => {
    editingStore.value = null
    formOpen.value = true
}

const openEdit = (item) => {
    editingStore.value = item
    formOpen.value = true
}

const openDelete = (item) => {
    deletingStore.value = item
    deleteOpen.value = true
}

const openDetail = (item) => {
    selectedStore.value = item
    detailOpen.value = true
}

const items = computed(() => props.stores?.data || [])
const pagination = computed(() => {
    const s = props.stores || {}
    return {
        current: s.current_page || 1,
        last: s.last_page || 1,
        total: s.total || 0,
        from: s.from || 0,
        to: s.to || 0,
    }
})

const activeQuery = computed(() => ({
    search: search.value,
    tipe: tipeFilter.value === 'semua' ? undefined : tipeFilter.value,
}))
</script>

<template>
    <AppLayout>
        <Head title="Toko - Kopinka" />

        <PageHeader title="Toko" description="Kelola kantor pusat dan cabang toko Kopinka.">
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    Tambah Toko
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
                            placeholder="Cari nama, kode, alamat... (ketik langsung)"
                            class="pl-9"
                            @input="onSearchInput"
                        />
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Select v-model="tipeFilter" @update:model-value="onTipeFilter">
                            <SelectTrigger class="w-44">
                                <SelectValue placeholder="Semua Tipe" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua">Semua Tipe</SelectItem>
                                <SelectItem value="pusat">Kantor Pusat</SelectItem>
                                <SelectItem value="toko">Toko</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button v-if="search || tipeFilter !== 'semua'" variant="ghost" size="sm" @click="resetFilters">
                            <X class="size-4" />
                            Reset
                        </Button>
                    </div>
                    <p class="text-muted-foreground text-sm">{{ pagination.total }} toko</p>
                </div>
            </CardContent>

            <!-- Table -->
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[900px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Nama</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Kode</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Tipe</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Alamat</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Telepon</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="bg-primary/10 text-primary flex size-8 shrink-0 items-center justify-center rounded-lg">
                                                <Building2 class="size-4" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium truncate">{{ item.nama }}</p>
                                                <p v-if="item.tipe === 'pusat'" class="text-muted-foreground text-xs">Kantor Pusat</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.kode }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <Badge :variant="item.tipe === 'pusat' ? 'purna' : 'secondary'">
                                            {{ item.tipe === 'pusat' ? 'Pusat' : 'Toko' }}
                                        </Badge>
                                    </td>
                                    <td class="text-muted-foreground border-b px-4 py-3">
                                        <span class="flex items-center gap-1.5">
                                            <MapPin class="size-3.5" />
                                            {{ item.alamat || '-' }}
                                        </span>
                                    </td>
                                    <td class="text-muted-foreground border-b px-4 py-3">
                                        <span class="flex items-center gap-1.5">
                                            <Phone class="size-3.5" />
                                            {{ item.telepon || '-' }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <Badge :variant="item.is_active ? 'success' : 'inactive'">
                                            {{ item.is_active ? 'Aktif' : 'Tidak Aktif' }}
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
                                                <DropdownMenuItem variant="destructive" @click="openDelete(item)">
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
                        <Store class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data toko</p>
                        <p class="text-muted-foreground text-sm">
                            Klik "Tambah Toko" untuk menambahkan data pertama.
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
                :base-url="'/toko'"
                :query="activeQuery"
            />
        </Card>

        <TokoFormModal :open="formOpen" :store="editingStore" @update:open="formOpen = $event" />
        <TokoDeleteModal :open="deleteOpen" :store="deletingStore" @update:open="deleteOpen = $event" />
        <TokoDetailModal :open="detailOpen" :store="selectedStore" @update:open="detailOpen = $event" />
    </AppLayout>
</template>
