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
import { Loader2, PackageCheck, Plus, Trash2, List } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'
import SearchableSelect from './SearchableSelect.vue'

const props = defineProps({
    open: { type: Boolean, default: false },
    suppliers: { type: Array, default: () => [] },
    produk: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

// Opsi supplier untuk SearchableSelect
const supplierOptions = computed(() =>
    props.suppliers.map((s) => ({
        value: s.id,
        label: `${s.kode} — ${s.nama}`,
        sublabel: s.kode,
    })),
)

// Opsi produk untuk SearchableSelect (label utama nama, sublabel kode + stok)
const produkOptions = computed(() =>
    props.produk.map((p) => ({
        value: p.id,
        label: p.nama_barang,
        sublabel: `${p.kode_barang} • Stok: ${p.stok ?? 0}`,
    })),
)

const emptyItem = () => ({
    produk_id: '',
    nama_barang: '',
    qty: 1,
    tanggal_expired: '',
    _manual: false,
})

const form = useForm({
    no_surat_jalan: '',
    tipe: 'konsinyasi',
    supplier_id: '',
    tanggal: '',
    status: 'selesai',
    keterangan: '',
    items: [emptyItem()],
})

const resetForm = () => {
    form.reset()
    form.no_surat_jalan = ''
    form.tipe = 'konsinyasi'
    form.supplier_id = ''
    form.tanggal = ''
    form.status = 'selesai'
    form.keterangan = ''
    form.items = [emptyItem()]
    form.clearErrors()
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

// Pilih produk di baris item → isi otomatis nama
const onProdukChange = (item) => {
    const p = props.produk.find((x) => Number(x.id) === Number(item.produk_id))
    if (p) {
        item.nama_barang = p.nama_barang
    }
}

// Toggle mode: pilih produk dari database ⇄ ketik produk baru
const toggleManual = (item) => {
    item._manual = !item._manual
    if (item._manual) {
        item.produk_id = ''
    } else if (!item.nama_barang) {
        item.nama_barang = ''
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
    form.post('/terima-barang', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
            toast.success('Penerimaan barang berhasil disimpan.')
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90svh] max-w-6xl overflow-y-auto p-0">
            <form @submit.prevent="submit" class="flex flex-col">
                <!-- Header -->
                <DialogHeader class="border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <PackageCheck class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Tambah Penerimaan Barang</DialogTitle>
                            <DialogDescription>
                                Catat barang masuk dari supplier/konsinyor atau retur toko (stok otomatis bertambah).
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <!-- Body -->
                <div class="flex flex-col gap-6 p-6">
                    <!-- Informasi Penerimaan -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Informasi Penerimaan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="tipe">Tipe Penerimaan</Label>
                                <select
                                    id="tipe"
                                    v-model="form.tipe"
                                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-10 w-full items-center justify-between rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="konsinyasi">Konsinyasi (Titipan)</option>
                                    <option value="retur_toko">Retur Toko</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="supplier_id">Supplier</Label>
                                <SearchableSelect
                                    id="supplier_id"
                                    v-model="form.supplier_id"
                                    :options="supplierOptions"
                                    placeholder="— Cari & Pilih Supplier —"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.supplier_id" class="text-destructive text-xs">{{ form.errors.supplier_id }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="tanggal">Tanggal</Label>
                                <Input id="tanggal" v-model="form.tanggal" type="date" :disabled="form.processing" />
                                <p v-if="form.errors.tanggal" class="text-destructive text-xs">{{ form.errors.tanggal }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_surat_jalan">No Surat Jalan</Label>
                                <Input id="no_surat_jalan" v-model="form.no_surat_jalan" placeholder="No surat jalan" :disabled="form.processing" />
                                <p v-if="form.errors.no_surat_jalan" class="text-destructive text-xs">{{ form.errors.no_surat_jalan }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-10 w-full items-center justify-between rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="selesai">Selesai</option>
                                    <option value="draft">Draft</option>
                                    <option value="batal">Batal</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- Daftar Item -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Item Barang</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>

                        <div>
                            <table class="w-full border-separate border-spacing-0 text-sm">
                                <thead>
                                    <tr>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-left text-xs font-semibold uppercase">Produk</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-center text-xs font-semibold uppercase">Qty</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-center text-xs font-semibold uppercase">Expired</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-muted/40">
                                        <td class="border-b px-2 py-2 align-top">
                                            <div class="flex items-start gap-1.5">
                                                <SearchableSelect
                                                    v-if="!item._manual"
                                                    v-model="item.produk_id"
                                                    :options="produkOptions"
                                                    placeholder="Cari produk..."
                                                    class="min-w-0 flex-1"
                                                    @update:model-value="onProdukChange(item)"
                                                />
                                                <Input
                                                    v-else
                                                    v-model="item.nama_barang"
                                                    placeholder="Ketik nama produk baru..."
                                                    class="h-10 min-w-0 flex-1"
                                                />
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    class="size-10 shrink-0"
                                                    :title="item._manual ? 'Pilih produk dari database' : 'Produk baru (belum ada di database)'"
                                                    @click="toggleManual(item)"
                                                >
                                                    <Plus v-if="!item._manual" class="size-4" />
                                                    <List v-else class="size-4" />
                                                </Button>
                                            </div>
                                            <p v-if="form.errors[`items.${index}.produk_id`]" class="text-destructive mt-1 text-xs">
                                                {{ form.errors[`items.${index}.produk_id`] }}
                                            </p>
                                            <p v-if="form.errors[`items.${index}.nama_barang`]" class="text-destructive mt-1 text-xs">
                                                {{ form.errors[`items.${index}.nama_barang`] }}
                                            </p>
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.qty" type="number" min="1" step="1" placeholder="1" class="h-10 w-16 text-center" />
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.tanggal_expired" type="date" class="h-10 w-28" />
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

                    <!-- Keterangan -->
                    <section class="flex flex-col gap-4">
                        <div class="grid gap-2">
                            <Label htmlFor="keterangan">Keterangan</Label>
                            <textarea
                                id="keterangan"
                                v-model="form.keterangan"
                                rows="2"
                                class="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                placeholder="Catatan tambahan (opsional)"
                            />
                        </div>
                    </section>
                </div>

                <!-- Footer -->
                <DialogFooter class="border-t p-6 pt-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <PackageCheck v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Penerimaan' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
