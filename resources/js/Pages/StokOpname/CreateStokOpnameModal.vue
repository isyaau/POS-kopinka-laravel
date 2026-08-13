<script setup>
import { ref, watch, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogFooter,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Loader2, ClipboardCheck, Plus, Trash2, Wand2, Layers } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'
import SearchableSelect from './SearchableSelect.vue'

const props = defineProps({
    open: { type: Boolean, default: false },
    produk: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

// Opsi produk untuk SearchableSelect (label utama nama, sublabel kode + stok)
const produkOptions = computed(() =>
    props.produk.map((p) => ({
        value: p.id,
        label: p.nama_barang,
        sublabel: `${p.kode_barang} • Rak: ${p.no_rak || '-'} • Stok: ${p.stok ?? 0}`,
    })),
)

// Daftar rak unik yang tersedia (untuk filter per rak)
const rakList = computed(() => {
    const set = new Set()
    props.produk.forEach((p) => {
        if (p.no_rak) set.add(p.no_rak)
    })
    return Array.from(set).sort((a, b) => String(a).localeCompare(String(b)))
})

const emptyItem = () => ({
    produk_id: '',
    nama_barang: '',
    stok_sistem: 0,
    stok_fisik: 0,
})

const form = useForm({
    tanggal: '',
    status: 'draft',
    petugas: '',
    keterangan: '',
    items: [emptyItem()],
})

const resetForm = () => {
    form.reset()
    form.tanggal = ''
    form.status = 'draft'
    form.petugas = ''
    form.keterangan = ''
    form.items = [emptyItem()]
    form.clearErrors()
    quickMode.value = 'manual'
    selectedRak.value = ''
    selectedIds.value = []
}

watch(
    () => props.open,
    (val) => {
        if (val) {
            resetForm()
            if (!form.tanggal) form.tanggal = new Date().toISOString().slice(0, 10)
        }
    },
)

// ===== Fitur Isi Cepat =====
const quickMode = ref('manual') // 'manual' | 'semua' | 'rak' | 'pilih'
const selectedRak = ref('')
const selectedIds = ref([])

// Produk yang akan di-generate berdasarkan mode
const produkTerpilih = computed(() => {
    if (quickMode.value === 'semua') return props.produk
    if (quickMode.value === 'rak') {
        return props.produk.filter((p) => String(p.no_rak) === String(selectedRak.value))
    }
    if (quickMode.value === 'pilih') {
        return props.produk.filter((p) => selectedIds.value.includes(Number(p.id)))
    }
    return []
})

const generateItems = () => {
    if (quickMode.value === 'manual') {
        toast.info('Pilih mode isi cepat (Semua Produk / Per Rak / Pilih Manual).')
        return
    }
    if (quickMode.value === 'rak' && !selectedRak.value) {
        toast.error('Pilih rak terlebih dahulu.')
        return
    }

    const list = produkTerpilih.value
    if (!list.length) {
        toast.error('Tidak ada produk yang sesuai dengan pilihan.')
        return
    }

    // Isi otomatis: stok_fisik = stok_sistem (selisih 0), user tinggal ubah yang berbeda.
    form.items = list.map((p) => ({
        produk_id: p.id,
        nama_barang: p.nama_barang,
        stok_sistem: p.stok ?? 0,
        stok_fisik: p.stok ?? 0,
    }))

    const label =
        quickMode.value === 'semua'
            ? 'semua produk aktif'
            : quickMode.value === 'rak'
              ? `rak ${selectedRak.value}`
              : `${list.length} produk terpilih`
    toast.success(`${form.items.length} item opname dibuat dari ${label}.`)
}

// Pilih produk di baris item → isi otomatis nama & stok sistem
const onProdukChange = (item) => {
    const p = props.produk.find((x) => Number(x.id) === Number(item.produk_id))
    if (p) {
        item.nama_barang = p.nama_barang
        item.stok_sistem = p.stok ?? 0
    }
}

const addItem = () => {
    form.items.push(emptyItem())
}

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1)
    }
}

const submit = () => {
    form.post('/stok-opname', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
            toast.success('Stok opname berhasil disimpan.')
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90svh] max-w-4xl overflow-y-auto p-0">
            <form @submit.prevent="submit" class="flex flex-col">
                <!-- Header -->
                <DialogHeader class="border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <ClipboardCheck class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Tambah Stok Opname</DialogTitle>
                            <DialogDescription>
                                Catat hitung fisik barang. Stok sistem terisi otomatis saat pilih produk.
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <!-- Body -->
                <div class="flex flex-col gap-6 p-6">
                    <!-- Informasi Opname -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Informasi Opname</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label htmlFor="tanggal">Tanggal</Label>
                                <Input id="tanggal" v-model="form.tanggal" type="date" :disabled="form.processing" />
                                <p v-if="form.errors.tanggal" class="text-destructive text-xs">{{ form.errors.tanggal }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-10 w-full items-center justify-between rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="draft">Draft</option>
                                    <option value="selesai">Selesai</option>
                                    <option value="batal">Batal</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="petugas">Petugas</Label>
                                <Input id="petugas" v-model="form.petugas" placeholder="Nama petugas opname" :disabled="form.processing" />
                                <p v-if="form.errors.petugas" class="text-destructive text-xs">{{ form.errors.petugas }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="keterangan">Keterangan</Label>
                                <Input id="keterangan" v-model="form.keterangan" placeholder="Catatan (opsional)" :disabled="form.processing" />
                                <p v-if="form.errors.keterangan" class="text-destructive text-xs">{{ form.errors.keterangan }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Isi Cepat -->
                    <section class="bg-muted/40 flex flex-col gap-3 rounded-xl border p-4">
                        <div class="flex items-center gap-2">
                            <Wand2 class="text-primary size-4" />
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Isi Cepat</h3>
                            <span class="text-muted-foreground text-xs">(buat item otomatis)</span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="quickMode = 'manual'"
                                :class="quickMode === 'manual' ? 'bg-primary text-primary-foreground' : 'bg-background border'"
                                class="rounded-md px-3 py-1.5 text-xs font-medium transition"
                            >
                                Isi Manual
                            </button>
                            <button
                                type="button"
                                @click="quickMode = 'semua'"
                                :class="quickMode === 'semua' ? 'bg-primary text-primary-foreground' : 'bg-background border'"
                                class="rounded-md px-3 py-1.5 text-xs font-medium transition"
                            >
                                Semua Produk
                            </button>
                            <button
                                type="button"
                                @click="quickMode = 'rak'"
                                :class="quickMode === 'rak' ? 'bg-primary text-primary-foreground' : 'bg-background border'"
                                class="flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-medium transition"
                            >
                                <Layers class="size-3.5" /> Per Rak
                            </button>
                            <button
                                type="button"
                                @click="quickMode = 'pilih'"
                                :class="quickMode === 'pilih' ? 'bg-primary text-primary-foreground' : 'bg-background border'"
                                class="rounded-md px-3 py-1.5 text-xs font-medium transition"
                            >
                                Pilih Beberapa
                            </button>
                        </div>

                        <!-- Filter per rak -->
                        <div v-if="quickMode === 'rak'" class="flex items-end gap-2">
                            <div class="grid gap-1.5">
                                <Label>Pilih Rak</Label>
                                <select
                                    v-model="selectedRak"
                                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-10 w-full min-w-44 rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">-- Pilih Rak --</option>
                                    <option v-for="rak in rakList" :key="rak" :value="rak">Rak {{ rak }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Pilih beberapa produk -->
                        <div v-if="quickMode === 'pilih'" class="flex flex-col gap-2">
                            <Label>Pilih Produk (centang yang akan diopname)</Label>
                            <div class="bg-background max-h-40 overflow-y-auto rounded-md border p-2">
                                <label
                                    v-for="p in produk"
                                    :key="p.id"
                                    class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-muted"
                                >
                                    <input type="checkbox" :value="Number(p.id)" v-model="selectedIds" class="size-4" />
                                    <span class="font-medium">{{ p.nama_barang }}</span>
                                    <span class="text-muted-foreground text-xs">Rak: {{ p.no_rak || '-' }}</span>
                                </label>
                            </div>
                        </div>

                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            class="w-fit"
                            :disabled="quickMode === 'manual' || form.processing"
                            @click="generateItems"
                        >
                            <Wand2 class="size-4" />
                            Generate Item Opname
                        </Button>
                        <p class="text-muted-foreground text-xs">
                            Stok fisik otomatis diisi sama dengan stok sistem (selisih 0). Anda tinggal ubah kolom
                            <span class="font-semibold">Stok Fisik</span> pada item yang berbeda hasil hitungannya.
                        </p>
                    </section>

                    <!-- Daftar Item -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Item Barang</h3>
                            <div class="bg-border h-px flex-1" />
                            <span class="text-muted-foreground text-xs">{{ form.items.length }} item</span>
                        </div>

                        <div>
                            <table class="w-full border-separate border-spacing-0 text-sm">
                                <thead>
                                    <tr>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-left text-xs font-semibold uppercase">Produk</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-right text-xs font-semibold uppercase">Stok Sistem</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-right text-xs font-semibold uppercase">Stok Fisik</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-right text-xs font-semibold uppercase">Selisih</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-muted/40">
                                        <td class="border-b px-2 py-2 align-top">
                                            <SearchableSelect
                                                v-model="item.produk_id"
                                                :options="produkOptions"
                                                placeholder="Cari produk..."
                                                class="min-w-0 flex-1"
                                                @update:model-value="onProdukChange(item)"
                                                :disabled="form.processing"
                                            />
                                            <p v-if="form.errors[`items.${index}.produk_id`]" class="text-destructive mt-1 text-xs">
                                                {{ form.errors[`items.${index}.produk_id`] }}
                                            </p>
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.stok_sistem" type="number" min="0" step="1" readonly class="h-10 w-24 text-right bg-muted" />
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.stok_fisik" type="number" min="0" step="1" placeholder="0" class="h-10 w-24 text-right" />
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <div class="bg-muted/50 rounded-md border px-2 py-2 text-right text-sm font-semibold whitespace-nowrap" :class="{ 'text-destructive': Number(item.stok_fisik) - Number(item.stok_sistem) < 0, 'text-emerald-600': Number(item.stok_fisik) - Number(item.stok_sistem) > 0 }">
                                                {{ Number(item.stok_fisik) - Number(item.stok_sistem) }}
                                            </div>
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="size-8"
                                                :disabled="form.items.length <= 1"
                                                @click="removeItem(index)"
                                            >
                                                <Trash2 class="text-destructive size-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Button type="button" variant="outline" size="sm" class="w-fit" @click="addItem">
                            <Plus class="size-4" />
                            Tambah Item
                        </Button>
                    </section>
                </div>

                <!-- Footer -->
                <DialogFooter class="border-t p-6 pt-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <ClipboardCheck v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Opname' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
