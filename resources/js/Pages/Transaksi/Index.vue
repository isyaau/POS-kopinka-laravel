<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import Kbd from '@/components/Kbd.vue'
import ShortcutHelpDialog from '@/components/ShortcutHelpDialog.vue'
import { useHotkeys } from '@/composables/useHotkeys'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Pagination } from '@/components/ui/pagination'
import CreateTransaksiModal from './CreateTransaksiModal.vue'
import EditTransaksiModal from './EditTransaksiModal.vue'
import DeleteTransaksiModal from './DeleteTransaksiModal.vue'
import ImportTransaksiModal from './ImportTransaksiModal.vue'
import DetailTransaksiModal from './DetailTransaksiModal.vue'
import {
    Search,
    ClipboardList,
    MoreHorizontal,
    Pencil,
    Trash2,
    Plus,
    X,
    Download,
    Upload,
    FileSpreadsheet,
    Eye,
    Printer,
    Keyboard,
} from 'lucide-vue-next'
import { printStruk } from '@/lib/struk'

const props = defineProps({
    transaksi: { type: Object, default: () => ({ data: [] }) },
    anggota: { type: Array, default: () => [] },
    produk: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const createOpen = ref(false)
const editOpen = ref(false)
const editingTransaksi = ref(null)
const deleteOpen = ref(false)
const deletingTransaksi = ref(null)
const importOpen = ref(false)
const detailOpen = ref(false)
const selectedTransaksi = ref(null)
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

let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get('/transaksi', { search: search.value }, { preserveState: true, replace: true })
    }, 400)
}

const resetSearch = () => {
    search.value = ''
    router.get('/transaksi', {}, { preserveState: true, replace: true })
}

const handleExport = () => {
    const params = new URLSearchParams()
    if (search.value) params.set('search', search.value)
    window.location.href = `/transaksi/export?${params.toString()}`
}

const openCreate = () => {
    createOpen.value = true
}

const openDetail = (item) => {
    selectedTransaksi.value = item
    detailOpen.value = true
}

const cetakStruk = (item) => {
    printStruk({
        no_nota: item.no_nota,
        no_kasir: item.no_kasir,
        tanggal: item.tanggal,
        nama_anggota: item.nama_anggota,
        anggota_id: item.anggota_id,
        items: item.details || [],
        nilai: item.nilai,
        diskon: item.diskon,
        jual: item.jual,
        cash: item.cash,
        qris: item.qris,
        edc: item.edc,
        voucher: item.voucher,
        piutang: item.piutang,
    })
}

const openEdit = (item) => {
    editingTransaksi.value = item
    editOpen.value = true
}

const confirmDelete = (item) => {
    deletingTransaksi.value = item
    deleteOpen.value = true
}

const items = computed(() => props.transaksi?.data || [])
const pagination = computed(() => {
    const a = props.transaksi || {}
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
}))

const totalPembayaran = (t) =>
    Number(t.cash || 0) + Number(t.qris || 0) + Number(t.edc || 0) + Number(t.voucher || 0) + Number(t.piutang || 0)

// ===== Pintasan papan ketik =====
const activeRow = ref(-1)
const helpOpen = ref(false)

const hasRow = computed(() => activeRow.value >= 0 && !!items.value[activeRow.value])
const activeItem = computed(() => (hasRow.value ? items.value[activeRow.value] : null))

const focusSearch = () => {
    const el = document.getElementById('transaksi-search')
    if (!el) return
    el.focus()
    el.select?.()
}

const scrollActiveRow = () =>
    nextTick(() => {
        document.querySelector(`[data-row-index="${activeRow.value}"]`)?.scrollIntoView({ block: 'nearest' })
    })

const moveRow = (delta) => {
    if (!items.value.length) return
    const next =
        activeRow.value < 0 ? (delta > 0 ? 0 : items.value.length - 1) : activeRow.value + delta
    activeRow.value = Math.min(Math.max(next, 0), items.value.length - 1)
    scrollActiveRow()
}

const clearRow = () => {
    activeRow.value = -1
}

const escapeAction = () => {
    if (search.value) {
        resetSearch()
        document.getElementById('transaksi-search')?.blur()
        return
    }
    clearRow()
}

watch(items, () => clearRow())

const anyModalOpen = computed(
    () => createOpen.value || editOpen.value || deleteOpen.value || importOpen.value || detailOpen.value,
)

useHotkeys(
    [
        { key: '/', description: 'Fokus ke pencarian', group: 'Navigasi', handler: focusSearch },
        {
            key: 'ArrowDown',
            description: 'Pilih baris berikutnya',
            group: 'Navigasi',
            allowInInput: true,
            handler: () => moveRow(1),
        },
        {
            key: 'ArrowUp',
            description: 'Pilih baris sebelumnya',
            group: 'Navigasi',
            allowInInput: true,
            handler: () => moveRow(-1),
        },
        {
            key: 'Escape',
            description: 'Bersihkan pencarian / batalkan pilihan',
            group: 'Navigasi',
            allowInInput: true,
            handler: escapeAction,
        },
        { key: 'n', description: 'Tambah transaksi', group: 'Aksi', handler: openCreate },
        {
            key: 'i',
            ctrl: true,
            description: 'Import transaksi',
            group: 'Aksi',
            handler: () => (importOpen.value = true),
        },
        { key: 'e', ctrl: true, description: 'Export Excel', group: 'Aksi', handler: handleExport },
        {
            key: 'Enter',
            description: 'Buka detail baris terpilih',
            group: 'Baris terpilih',
            allowInInput: true,
            when: hasRow,
            handler: () => openDetail(activeItem.value),
        },
        {
            key: 'e',
            description: 'Edit baris terpilih',
            group: 'Baris terpilih',
            when: hasRow,
            handler: () => openEdit(activeItem.value),
        },
        {
            key: 'p',
            description: 'Cetak ulang struk baris terpilih',
            group: 'Baris terpilih',
            when: hasRow,
            handler: () => cetakStruk(activeItem.value),
        },
        {
            key: 'Delete',
            description: 'Hapus baris terpilih',
            group: 'Baris terpilih',
            when: hasRow,
            handler: () => confirmDelete(activeItem.value),
        },
        {
            key: '?',
            description: 'Buka / tutup daftar pintasan',
            group: 'Bantuan',
            handler: () => (helpOpen.value = !helpOpen.value),
        },
    ],
    { enabled: () => !anyModalOpen.value },
)
</script>

<template>
    <AppLayout>
        <Head title="Transaksi - Kopinka" />

        <PageHeader title="Transaksi" description="Kelola data transaksi penjualan per nota.">
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button variant="ghost" class="text-muted-foreground" @click="helpOpen = true">
                        <Keyboard class="size-4" />
                        Pintasan
                        <Kbd keys="?" />
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline">
                                <Download class="size-4" />
                                Export
                                <Kbd keys="Ctrl+E" />
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
                        <Kbd keys="Ctrl+I" />
                    </Button>
                    <Button @click="openCreate">
                        <Plus class="size-4" />
                        Tambah Transaksi
                        <Kbd keys="N" class="bg-primary-foreground/20 border-transparent text-primary-foreground" />
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
                            id="transaksi-search"
                            v-model="search"
                            placeholder="Cari nota, anggota, kasir... (ketik langsung)"
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
                        <Kbd
                            v-else
                            keys="/"
                            class="pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2 opacity-70"
                        />
                    </div>
                    <p class="text-muted-foreground text-sm">
                        {{ pagination.total }} transaksi
                    </p>
                </div>
            </CardContent>

            <!-- Table -->
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[1400px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-left text-xs font-semibold uppercase">NO KASIR</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-left text-xs font-semibold uppercase">NOTA</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-left text-xs font-semibold uppercase">ID.AGT</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-left text-xs font-semibold uppercase">NAMA ANGGOTA</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">NILAI</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">DISKON</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">JUAL</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">USAHA</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">JASA</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">PPN.K</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">CASH</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">QRIS</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">EDC</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">VOUCHER</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">PIUTANG</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-left text-xs font-semibold uppercase">TGL</th>
                                    <th class="bg-muted text-muted-foreground border-b px-3 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in items"
                                    :key="item.id"
                                    :data-row-index="index"
                                    class="hover:bg-muted/40 cursor-default transition-colors"
                                    :class="index === activeRow ? 'bg-accent ring-1 ring-inset ring-primary/30' : ''"
                                    @click="activeRow = index"
                                >
                                    <td class="border-b px-3 py-3">{{ item.no_kasir || '-' }}</td>
                                    <td class="border-b px-3 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.no_nota }}
                                        </span>
                                    </td>
                                    <td class="text-muted-foreground border-b px-3 py-3">{{ item.anggota_id ?? '-' }}</td>
                                    <td class="border-b px-3 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="bg-primary/10 text-primary flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold uppercase">
                                                {{ (item.nama_anggota || '?').charAt(0) }}
                                            </div>
                                            <p class="font-medium truncate">{{ item.nama_anggota || '-' }}</p>
                                        </div>
                                    </td>
                                    <td class="border-b px-3 py-3 text-right font-medium">{{ formatRupiah(item.nilai) }}</td>
                                    <td class="border-b px-3 py-3 text-right">
                                        <span v-if="item.diskon > 0" class="text-destructive font-medium">{{ formatRupiah(item.diskon) }}</span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="border-b px-3 py-3 text-right font-semibold">{{ formatRupiah(item.jual) }}</td>
                                    <td class="border-b px-3 py-3 text-right">{{ formatRupiah(item.usaha) }}</td>
                                    <td class="border-b px-3 py-3 text-right">{{ formatRupiah(item.jasa) }}</td>
                                    <td class="border-b px-3 py-3 text-right">{{ formatRupiah(item.ppn) }}</td>
                                    <td class="border-b px-3 py-3 text-right">{{ formatRupiah(item.cash) }}</td>
                                    <td class="border-b px-3 py-3 text-right">{{ formatRupiah(item.qris) }}</td>
                                    <td class="border-b px-3 py-3 text-right">{{ formatRupiah(item.edc) }}</td>
                                    <td class="border-b px-3 py-3 text-right">{{ formatRupiah(item.voucher) }}</td>
                                    <td class="border-b px-3 py-3 text-right">
                                        <span v-if="item.piutang > 0" class="text-destructive font-medium">{{ formatRupiah(item.piutang) }}</span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="border-b px-3 py-3">{{ formatDate(item.tanggal) }}</td>
                                    <td class="border-b px-3 py-3 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" class="size-8">
                                                    <MoreHorizontal class="size-4" />
                                                    <span class="sr-only">Aksi</span>
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-44">
                                                <DropdownMenuLabel class="text-muted-foreground text-xs">
                                                    {{ item.no_nota }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem @click="cetakStruk(item)">
                                                    <Printer class="size-4" />
                                                    Cetak Ulang
                                                </DropdownMenuItem>
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
                        <ClipboardList class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada data transaksi</p>
                        <p class="text-muted-foreground text-sm">
                            Klik "Tambah Transaksi" untuk menambahkan data pertama.
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
                :base-url="'/transaksi'"
                :query="activeQuery"
            />
        </Card>

        <CreateTransaksiModal
            :open="createOpen"
            :anggota="props.anggota"
            :produk="props.produk"
            @update:open="createOpen = $event"
        />
        <EditTransaksiModal
            :open="editOpen"
            :transaksi="editingTransaksi"
            :anggota="props.anggota"
            :produk="props.produk"
            @update:open="editOpen = $event"
        />
        <DeleteTransaksiModal :open="deleteOpen" :transaksi="deletingTransaksi" @update:open="deleteOpen = $event" />
        <ImportTransaksiModal :open="importOpen" @update:open="importOpen = $event" />
        <DetailTransaksiModal :open="detailOpen" :transaksi="selectedTransaksi" @update:open="detailOpen = $event" />
        <ShortcutHelpDialog :open="helpOpen" @update:open="helpOpen = $event" />
    </AppLayout>
</template>
