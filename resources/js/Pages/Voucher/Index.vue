<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card } from '@/components/ui/card'
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
import CreateVoucherModal from './CreateVoucherModal.vue'
import EditVoucherModal from './EditVoucherModal.vue'
import DeleteVoucherModal from './DeleteVoucherModal.vue'
import ImportVoucherModal from './ImportVoucherModal.vue'
import DetailVoucherModal from './DetailVoucherModal.vue'
import {
    Search,
    Ticket,
    MoreHorizontal,
    Pencil,
    Trash2,
    Plus,
    X,
    Download,
    Upload,
    FileSpreadsheet,
    Eye,
    Barcode,
    Wallet,
} from 'lucide-vue-next'

const props = defineProps({
    vouchers: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
})

const createOpen = ref(false)
const editOpen = ref(false)
const editingVoucher = ref(null)
const deleteOpen = ref(false)
const deletingVoucher = ref(null)
const importOpen = ref(false)
const detailOpen = ref(false)
const selectedVoucher = ref(null)
const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')

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
    searchTimer = setTimeout(() => {
        router.get('/voucher', { search: search.value, status: statusFilter.value }, { preserveState: true, replace: true })
    }, 400)
}

const resetSearch = () => {
    search.value = ''
    router.get('/voucher', { status: statusFilter.value }, { preserveState: true, replace: true })
}

const onStatusChange = () => {
    router.get('/voucher', { search: search.value, status: statusFilter.value }, { preserveState: true, replace: true })
}

const handleExport = () => {
    const params = new URLSearchParams()
    if (search.value) params.set('search', search.value)
    window.location.href = `/voucher/export?${params.toString()}`
}

const openCreate = () => {
    createOpen.value = true
}

const openDetail = (item) => {
    selectedVoucher.value = item
    detailOpen.value = true
}

const openEdit = (item) => {
    editingVoucher.value = item
    editOpen.value = true
}

const confirmDelete = (item) => {
    deletingVoucher.value = item
    deleteOpen.value = true
}

const items = computed(() => props.vouchers?.data || [])
const pagination = computed(() => {
    const a = props.vouchers || {}
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
    status: statusFilter.value,
}))

const badgeVariant = (status) => {
    if (status === 'aktif') return 'success'
    if (status === 'terpakai') return 'secondary'
    return 'warning'
}

const badgeLabel = (status) => {
    if (status === 'aktif') return 'Aktif'
    if (status === 'terpakai') return 'Terpakai'
    return 'Kedaluwarsa'
}
</script>

<template>
    <AppLayout>
        <Head title="Voucher - Kopinka" />

        <PageHeader title="Voucher" description="Kelola kupon berisi barcode dengan nominal tertentu.">
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
                        <Plus class="size-4" />
                        Tambah Voucher
                    </Button>
                </div>
            </template>
        </PageHeader>

        <Card class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden py-0">
            <!-- Filter & Search bar -->
            <div class="border-b px-4 py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="relative w-full lg:max-w-xs">
                        <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            v-model="search"
                            placeholder="Cari kode, nama, barcode... (ketik langsung)"
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
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1 rounded-lg border p-1">
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 px-3 text-xs"
                                :class="{ 'bg-primary text-primary-foreground': statusFilter === '' }"
                                @click="statusFilter = ''; onStatusChange()"
                            >
                                Semua
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 px-3 text-xs"
                                :class="{ 'bg-primary text-primary-foreground': statusFilter === 'aktif' }"
                                @click="statusFilter = 'aktif'; onStatusChange()"
                            >
                                Aktif
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 px-3 text-xs"
                                :class="{ 'bg-primary text-primary-foreground': statusFilter === 'terpakai' }"
                                @click="statusFilter = 'terpakai'; onStatusChange()"
                            >
                                Terpakai
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 px-3 text-xs"
                                :class="{ 'bg-primary text-primary-foreground': statusFilter === 'kedaluwarsa' }"
                                @click="statusFilter = 'kedaluwarsa'; onStatusChange()"
                            >
                                Kedaluwarsa
                            </Button>
                        </div>
                        <p class="text-muted-foreground text-sm">
                            {{ pagination.total }} voucher
                        </p>
                    </div>
                </div>
            </div>

            <!-- Kartu kupon -->
            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <div v-if="items.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    <div
                        v-for="v in items"
                        :key="v.id"
                        class="border-input hover:border-primary/50 bg-card group relative flex flex-col gap-3 overflow-hidden rounded-xl border p-4 shadow-xs transition-all hover:shadow-md"
                    >
                        <!-- Strip kiri warna status -->
                        <div
                            class="absolute inset-y-0 left-0 w-1.5"
                            :class="{
                                'bg-emerald-500': v.status === 'aktif',
                                'bg-muted-foreground/40': v.status === 'terpakai',
                                'bg-amber-500': v.status === 'kedaluwarsa',
                            }"
                        />

                        <!-- Header kartu -->
                        <div class="flex items-start justify-between gap-2 pl-1">
                            <div class="flex items-center gap-2">
                                <div class="bg-primary/10 text-primary flex size-9 items-center justify-center rounded-lg">
                                    <Ticket class="size-4.5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold">{{ v.nama }}</p>
                                    <p class="text-muted-foreground text-[11px]">{{ v.kode }}</p>
                                </div>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon" class="size-7 opacity-0 transition-opacity group-hover:opacity-100">
                                        <MoreHorizontal class="size-4" />
                                        <span class="sr-only">Aksi</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-44">
                                    <DropdownMenuLabel class="text-muted-foreground text-xs">
                                        {{ v.kode }}
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @click="openDetail(v)">
                                        <Eye class="size-4" />
                                        Detail
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="openEdit(v)">
                                        <Pencil class="size-4" />
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem variant="destructive" @click="confirmDelete(v)">
                                        <Trash2 class="size-4" />
                                        Hapus
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <!-- Nominal -->
                        <div class="pl-1">
                            <p class="text-primary text-2xl font-extrabold tracking-tight">{{ formatRupiah(v.nominal) }}</p>
                            <Badge :variant="badgeVariant(v.status)" class="mt-1 text-[10px]">
                                {{ badgeLabel(v.status) }}
                            </Badge>
                        </div>

                        <!-- Barcode -->
                        <div class="flex items-center gap-2 rounded-lg border bg-muted/40 px-3 py-2">
                            <Barcode class="text-muted-foreground size-5 shrink-0" />
                            <div class="min-w-0">
                                <p class="font-mono text-[11px] tracking-widest">{{ v.barcode }}</p>
                                <p class="text-muted-foreground text-[10px]">
                                    Exp: {{ formatDate(v.tanggal_expired) }}
                                </p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="text-muted-foreground flex items-center justify-between border-t pt-2 text-[11px]">
                            <span class="flex items-center gap-1">
                                <Wallet class="size-3" />
                                {{ v.store?.nama || 'Semua Toko' }}
                            </span>
                            <span>{{ formatDate(v.created_at) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                    <div class="bg-muted flex size-14 items-center justify-center rounded-full">
                        <Ticket class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data voucher</p>
                        <p class="text-muted-foreground text-sm">
                            Klik "Tambah Voucher" untuk membuat kupon pertama.
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
                :base-url="'/voucher'"
                :query="activeQuery"
            />
        </Card>

        <CreateVoucherModal :open="createOpen" @update:open="createOpen = $event" />
        <EditVoucherModal :open="editOpen" :voucher="editingVoucher" @update:open="editOpen = $event" />
        <DeleteVoucherModal :open="deleteOpen" :voucher="deletingVoucher" @update:open="deleteOpen = $event" />
        <ImportVoucherModal :open="importOpen" @update:open="importOpen = $event" />
        <DetailVoucherModal :open="detailOpen" :voucher="selectedVoucher" @update:open="detailOpen = $event" />
    </AppLayout>
</template>
