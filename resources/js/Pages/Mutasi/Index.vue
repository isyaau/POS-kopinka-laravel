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
    Search,
    ArrowLeftRight,
    X,
} from 'lucide-vue-next'

const props = defineProps({
    mutasi: { type: Object, default: () => ({ data: [] }) },
    stores: { type: Array, default: () => [] },
    produk: { type: Array, default: () => [] },
    show_all: { type: Boolean, default: false },
    filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search || '')
const storeFilter = ref(props.filters.store_id_filter ?? (props.show_all ? 'all' : ''))
const produkId = ref(props.filters.produk_id ?? '')
const tipe = ref(props.filters.tipe ?? '')
const dari = ref(props.filters.dari ?? '')
const sampai = ref(props.filters.sampai ?? '')

const formatRupiah = (val) => {
    const n = Number(val || 0)
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n)
}

const formatDate = (val) => {
    if (!val) return '-'
    const d = new Date(val)
    if (Number.isNaN(d.getTime())) return '-'
    return `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getFullYear()}`
}

const tipeInfo = (t) => {
    switch (t) {
        case 'masuk': return { label: 'Masuk', variant: 'success' }
        case 'keluar': return { label: 'Keluar', variant: 'destructive' }
        case 'penyesuaian': return { label: 'Penyesuaian', variant: 'warning' }
        default: return { label: t || '-', variant: 'secondary' }
    }
}

const buildQuery = () => ({
    search: search.value,
    store_id_filter: storeFilter.value,
    produk_id: produkId.value,
    tipe: tipe.value,
    dari: dari.value,
    sampai: sampai.value,
})

let timer = null
const onFilterChange = () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get('/mutasi', buildQuery(), { preserveState: true, replace: true })
    }, 400)
}

const resetFilter = () => {
    search.value = ''
    storeFilter.value = props.show_all ? 'all' : ''
    produkId.value = ''
    tipe.value = ''
    dari.value = ''
    sampai.value = ''
    router.get('/mutasi', {}, { preserveState: true, replace: true })
}

const items = computed(() => props.mutasi?.data || [])
const pagination = computed(() => {
    const a = props.mutasi || {}
    return {
        current: a.current_page || 1,
        last: a.last_page || 1,
        total: a.total || 0,
        from: a.from || 0,
        to: a.to || 0,
    }
})

const activeQuery = computed(() => buildQuery())
const hasFilter = computed(() =>
    search.value || (storeFilter.value && storeFilter.value !== 'all') || produkId.value || tipe.value || dari.value || sampai.value,
)
</script>

<template>
    <AppLayout>
        <Head title="Mutasi Produk - Kopinka" />

        <PageHeader title="Mutasi Produk" description="Riwayat pergerakan stok per produk dan toko." />

        <Card class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden py-0">
            <CardContent class="border-b py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="relative w-full lg:max-w-xs">
                        <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            v-model="search"
                            placeholder="Cari produk... (ketik langsung)"
                            class="pl-9"
                            @input="onFilterChange"
                        />
                        <button
                            v-if="search"
                            class="text-muted-foreground hover:text-foreground absolute top-1/2 right-2.5 -translate-y-1/2"
                            aria-label="Hapus pencarian"
                            @click="search = ''; onFilterChange()"
                        >
                            <X class="size-4" />
                        </button>
                    </div>
                    <p class="text-muted-foreground text-sm">{{ pagination.total }} mutasi</p>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <select
                        v-model="storeFilter"
                        class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm focus-visible:ring-[3px] focus-visible:outline-none"
                        @change="onFilterChange"
                    >
                        <option value="all">Semua Toko</option>
                        <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.nama }}</option>
                    </select>

                    <select
                        v-model="produkId"
                        class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm focus-visible:ring-[3px] focus-visible:outline-none"
                        @change="onFilterChange"
                    >
                        <option value="">Semua Produk</option>
                        <option v-for="p in produk" :key="p.id" :value="p.id">{{ p.nama_barang }}</option>
                    </select>

                    <select
                        v-model="tipe"
                        class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm focus-visible:ring-[3px] focus-visible:outline-none"
                        @change="onFilterChange"
                    >
                        <option value="">Semua Tipe</option>
                        <option value="masuk">Masuk</option>
                        <option value="keluar">Keluar</option>
                        <option value="penyesuaian">Penyesuaian</option>
                    </select>

                    <Input v-model="dari" type="date" class="h-9 w-40" @change="onFilterChange" />
                    <span class="text-muted-foreground self-center text-sm">s/d</span>
                    <Input v-model="sampai" type="date" class="h-9 w-40" @change="onFilterChange" />

                    <Button v-if="hasFilter" type="button" variant="ghost" size="sm" @click="resetFilter">
                        <X class="size-4" /> Reset
                    </Button>
                </div>
            </CardContent>

            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[1000px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Tanggal</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Produk</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Toko</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Tipe</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Qty</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Saldo</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">{{ formatDate(item.tanggal) }}</td>
                                    <td class="border-b px-4 py-3">
                                        <span class="font-medium">{{ item.produk?.nama_barang || '-' }}</span>
                                        <span class="text-muted-foreground ml-1 text-xs">{{ item.produk?.kode_barang }}</span>
                                    </td>
                                    <td class="border-b px-4 py-3">{{ item.store?.nama || 'Pusat' }}</td>
                                    <td class="border-b px-4 py-3">
                                        <Badge :variant="tipeInfo(item.tipe).variant">{{ tipeInfo(item.tipe).label }}</Badge>
                                    </td>
                                    <td class="border-b px-4 py-3 text-right font-semibold">{{ item.qty }}</td>
                                    <td class="border-b px-4 py-3 text-right">
                                        <span class="bg-primary/10 text-primary rounded-md px-2 py-0.5 text-xs font-semibold">{{ item.saldo ?? 0 }}</span>
                                    </td>
                                    <td class="border-b px-4 py-3 text-muted-foreground max-w-xs truncate">{{ item.keterangan || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <div v-else class="flex flex-1 flex-col items-center justify-center gap-3 py-16 text-center">
                    <div class="bg-muted flex size-14 items-center justify-center rounded-full">
                        <ArrowLeftRight class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada mutasi</p>
                        <p class="text-muted-foreground text-sm">Riwayat pergerakan stok akan tampil di sini.</p>
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
                :base-url="'/mutasi'"
                :query="activeQuery"
            />
        </Card>
    </AppLayout>
</template>
