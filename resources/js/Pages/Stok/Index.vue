<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Pagination } from '@/components/ui/pagination'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Search,
    X,
    Boxes,
    TrendingUp,
    TrendingDown,
    AlertTriangle,
    CalendarRange,
    Package,
    Wallet,
    Filter,
} from 'lucide-vue-next'

const props = defineProps({
    mode: { type: String, default: 'terkini' },
    bulan: { type: String, default: '' },
    produk: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({ total_stok: 0, nilai_stok: 0, low_stock: 0 }) },
    suppliers: { type: Array, default: () => [] },
    kategori_list: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search || '')
const bulan = ref(props.bulan || new Date().toISOString().slice(0, 7))
const supplierId = ref(props.filters.supplier_id || 'all')
const kategori = ref(props.filters.kategori || 'all')

const formatRupiah = (val) => {
    const n = Number(val || 0)
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(n)
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

let searchTimer = null
const buildQuery = () => ({
    search: search.value,
    bulan: bulan.value,
    supplier_id: supplierId.value === 'all' ? '' : supplierId.value,
    kategori: kategori.value === 'all' ? '' : kategori.value,
})

const applyFilters = () => {
    router.get(props.mode === 'bulanan' ? '/stok/bulanan' : '/stok', buildQuery(), {
        preserveState: true,
        replace: true,
    })
}

const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 400)
}

const resetSearch = () => {
    search.value = ''
    applyFilters()
}

const goBulanan = () => {
    router.get('/stok/bulanan', buildQuery(), { preserveState: true, replace: true })
}

const goTerkini = () => {
    router.get('/stok', {}, { preserveState: true, replace: true })
}

const lowStock = (p) => Number(p.stok) <= Number(p.stok_minimum)

const statCards = computed(() => [
    {
        title: 'Total Stok',
        value: `${Number(props.stats.total_stok || 0).toLocaleString('id-ID')} unit`,
        icon: Boxes,
        desc: 'Jumlah unit tersimpan',
    },
    {
        title: 'Nilai Stok',
        value: formatRupiah(props.stats.nilai_stok),
        icon: Wallet,
        desc: 'Berdasarkan harga beli',
    },
    {
        title: 'Stok Menipis',
        value: String(props.stats.low_stock),
        icon: AlertTriangle,
        desc: 'Perlu restock',
        alert: props.stats.low_stock > 0,
    },
])
</script>

<template>
    <AppLayout>
        <Head title="Stok - Kopinka" />

        <PageHeader title="Stok" description="Pantau stok terkini dan mutasi stok bulanan.">
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button :variant="mode === 'bulanan' ? 'outline' : 'default'" size="sm" @click="goTerkini">
                        <Boxes class="size-4" />
                        Stok Terkini
                    </Button>
                    <Button :variant="mode === 'bulanan' ? 'default' : 'outline'" size="sm" @click="goBulanan">
                        <CalendarRange class="size-4" />
                        Stok Bulanan
                    </Button>
                </div>
            </template>
        </PageHeader>

        <!-- Stat cards (ringkas, hanya mode terkini) -->
        <div v-if="mode !== 'bulanan'" class="grid grid-cols-3 gap-2 mb-4">
            <div
                v-for="card in statCards"
                :key="card.title"
                class="border-input bg-card flex items-center gap-2 rounded-lg border px-3 py-2"
            >
                <div
                    class="flex size-7 shrink-0 items-center justify-center rounded-md"
                    :class="card.alert ? 'bg-destructive/10 text-destructive' : 'bg-primary/10 text-primary'"
                >
                    <component :is="card.icon" class="size-3.5" />
                </div>
                <div class="min-w-0">
                    <p class="text-muted-foreground truncate text-[10px] leading-tight uppercase">{{ card.title }}</p>
                    <p class="truncate text-sm font-bold leading-tight" :class="card.alert ? 'text-destructive' : ''">
                        {{ card.value }}
                    </p>
                </div>
            </div>
        </div>

        <Card class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden py-0">
            <!-- Filter bar -->
            <CardContent class="border-b py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
                        <div class="relative w-full xl:w-72">
                            <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                v-model="search"
                                placeholder="Cari kode, nama, kategori..."
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

                        <div class="w-full xl:w-52">
                            <Select v-model="supplierId" @update:model-value="applyFilters">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Semua Supplier" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        <span class="flex items-center gap-2">
                                            <Filter class="size-3.5" />
                                            Semua Supplier
                                        </span>
                                    </SelectItem>
                                    <SelectItem v-for="s in props.suppliers" :key="s.id" :value="String(s.id)">
                                        {{ s.nama }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="w-full xl:w-44">
                            <Select v-model="kategori" @update:model-value="applyFilters">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Semua Kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        <span class="flex items-center gap-2">
                                            <Filter class="size-3.5" />
                                            Semua Kategori
                                        </span>
                                    </SelectItem>
                                    <SelectItem v-for="k in props.kategori_list" :key="k" :value="String(k)">
                                        {{ k }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div v-if="mode === 'bulanan'" class="relative">
                            <Input v-model="bulan" type="month" class="w-44" @change="goBulanan" />
                        </div>
                    </div>
                    <p class="text-muted-foreground shrink-0 text-sm">
                        {{ pagination.total }} produk
                    </p>
                </div>
            </CardContent>

            <!-- Table -->
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <!-- Stok Terkini -->
                        <table v-if="mode !== 'bulanan'" class="w-full min-w-[640px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3.5 text-left text-xs font-semibold uppercase">Kode</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3.5 text-left text-xs font-semibold uppercase">Nama Barang</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3.5 text-left text-xs font-semibold uppercase">Kategori</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3.5 text-left text-xs font-semibold uppercase">Supplier</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3.5 text-right text-xs font-semibold uppercase">Stok</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3.5 text-right text-xs font-semibold uppercase">Stok Min</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3.5 text-left text-xs font-semibold uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3.5">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.kode_barang }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="bg-primary/10 text-primary flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold uppercase">
                                                {{ (item.nama_barang || '?').charAt(0) }}
                                            </div>
                                            <p class="font-medium truncate">{{ item.nama_barang }}</p>
                                        </div>
                                    </td>
                                    <td class="border-b px-4 py-3.5">{{ item.kategori || '-' }}</td>
                                    <td class="border-b px-4 py-3.5">{{ item.supplier?.nama || '-' }}</td>
                                    <td class="border-b px-4 py-3.5 text-right">
                                        <Badge :variant="lowStock(item) ? 'warning' : 'success'">
                                            <AlertTriangle v-if="lowStock(item)" class="size-3" />
                                            {{ item.stok }}
                                        </Badge>
                                    </td>
                                    <td class="border-b px-4 py-3.5 text-right">{{ item.stok_minimum }}</td>
                                    <td class="border-b px-4 py-3.5">
                                        <Badge :variant="lowStock(item) ? 'warning' : 'success'">
                                            {{ lowStock(item) ? 'Menipis' : 'Aman' }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Stok Bulanan -->
                        <table v-else class="w-full min-w-[1000px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Kode</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Nama Barang</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Stok Awal</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Masuk</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Keluar</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Penyesuaian</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Stok Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">
                                        <span class="bg-primary/10 text-primary inline-block rounded-md px-2 py-0.5 text-xs font-semibold">
                                            {{ item.kode_barang }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <p class="font-medium">{{ item.nama_barang }}</p>
                                        <p class="text-muted-foreground text-xs">{{ item.kategori || '' }}</p>
                                    </td>
                                    <td class="border-b px-4 py-3 text-right">{{ item.stok_awal }}</td>
                                    <td class="border-b px-4 py-3 text-right">
                                        <span class="text-emerald-600 inline-flex items-center gap-1">
                                            <TrendingUp class="size-3.5" />
                                            {{ item.masuk }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3 text-right">
                                        <span class="text-destructive inline-flex items-center gap-1">
                                            <TrendingDown class="size-3.5" />
                                            {{ item.keluar }}
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3 text-right">{{ item.penyesuaian }}</td>
                                    <td class="border-b px-4 py-3 text-right font-bold">{{ item.stok_akhir }}</td>
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
                        <p class="font-semibold">Tidak ada data stok</p>
                        <p class="text-muted-foreground text-sm">
                            {{ mode === 'bulanan' ? 'Belum ada mutasi stok pada bulan ini.' : 'Belum ada data produk.' }}
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
                :limit="Number(props.filters.limit || 25)"
                :base-url="mode === 'bulanan' ? '/stok/bulanan' : '/stok'"
                :query="mode === 'bulanan' ? { search: search, bulan: bulan } : { search: search }"
            />
        </Card>
    </AppLayout>
</template>
