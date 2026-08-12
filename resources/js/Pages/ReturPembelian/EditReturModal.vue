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
import { Loader2, ArrowLeftRight, Plus, Trash2, Repeat } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'
import SearchableSelect from './SearchableSelect.vue'

const props = defineProps({
    open: { type: Boolean, default: false },
    retur: { type: Object, default: null },
    suppliers: { type: Array, default: () => [] },
    produk: { type: Array, default: () => [] },
    pembelianList: { type: Array, default: () => [] },
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

// Opsi pembelian asal (berisi no pembelian + supplier)
const pembelianOptions = computed(() =>
    props.pembelianList.map((p) => ({
        value: p.id,
        label: p.no_pembelian,
        sublabel: `${p.nama_supplier || 'Tanpa supplier'} • ${p.tanggal || ''}`,
    })),
)

// Opsi produk untuk SearchableSelect (label utama nama, sublabel kode)
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
    qty_retur: 1,
    harga_beli: '',
    produk_tukar_id: '',
    nama_barang_tukar: '',
    qty_tukar: 0,
})

const form = useForm({
    pembelian_id: '',
    no_pembelian_asal: '',
    supplier_id: '',
    nama_supplier: '',
    tanggal: '',
    tipe: 'retur',
    alasan: '',
    status: 'selesai',
    keterangan: '',
    items: [emptyItem()],
})

const fillForm = () => {
    const r = props.retur
    if (!r) return

    form.pembelian_id = r.pembelian_id || ''
    form.no_pembelian_asal = r.no_pembelian_asal || ''
    form.supplier_id = r.supplier_id || ''
    form.nama_supplier = r.nama_supplier || ''
    form.tanggal = r.tanggal || ''
    form.tipe = r.tipe || 'retur'
    form.alasan = r.alasan || ''
    form.status = r.status || 'selesai'
    form.keterangan = r.keterangan || ''

    form.items = (r.details || []).map((d) => ({
        produk_id: d.produk_id || '',
        nama_barang: d.nama_barang || '',
        qty_retur: d.qty_retur || 1,
        harga_beli: d.harga_beli || '',
        produk_tukar_id: d.produk_tukar_id || '',
        nama_barang_tukar: d.nama_barang_tukar || '',
        qty_tukar: d.qty_tukar || 0,
    }))

    if (form.items.length === 0) form.items = [emptyItem()]
    form.clearErrors()
}

watch(
    () => props.open,
    (val) => {
        if (val) {
            fillForm()
        }
    },
)

// Pilih pembelian asal → auto-isi supplier + no pembelian asal
const onPembelianChange = () => {
    const p = props.pembelianList.find((x) => Number(x.id) === Number(form.pembelian_id))
    if (p) {
        form.no_pembelian_asal = p.no_pembelian || ''
        if (p.supplier_id) {
            form.supplier_id = p.supplier_id
            form.nama_supplier = p.nama_supplier || ''
        }
    }
}

// Pilih supplier manual → auto-isi nama
const onSupplierChange = () => {
    const s = props.suppliers.find((x) => Number(x.id) === Number(form.supplier_id))
    if (s) {
        form.nama_supplier = s.nama || ''
    }
}

// Pilih produk di baris item → isi otomatis nama & harga beli
const onProdukChange = (item) => {
    const p = props.produk.find((x) => Number(x.id) === Number(item.produk_id))
    if (p) {
        item.nama_barang = p.nama_barang
        if (!item.harga_beli) item.harga_beli = Math.round(Number(p.harga_beli) || 0)
    }
}

// Pilih produk pengganti (tukar) → isi otomatis nama
const onProdukTukarChange = (item) => {
    const p = props.produk.find((x) => Number(x.id) === Number(item.produk_tukar_id))
    if (p) {
        item.nama_barang_tukar = p.nama_barang
    }
}

// Format angka tanpa desimal
const formatAngka = (val) => Number(val || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 })

const addItem = () => {
    form.items.push(emptyItem())
}

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1)
    }
}

// Subtotal item: qty_retur * harga_beli
const itemSubtotal = (item) => {
    const qty = Number(item.qty_retur) || 0
    const harga = Number(item.harga_beli) || 0
    return Math.max(0, qty * harga)
}

const totalRetur = computed(() =>
    form.items.reduce((sum, item) => sum + itemSubtotal(item), 0),
)

const submit = () => {
    form.total_retur = totalRetur.value

    form.put(`/retur-pembelian/${props.retur.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            toast.success('Retur berhasil diperbarui.')
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
                            <ArrowLeftRight class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Edit Retur / Tukar</DialogTitle>
                            <DialogDescription>
                                <span class="bg-primary/10 text-primary inline-block rounded px-1.5 py-0.5 text-xs font-semibold">
                                    {{ form.no_retur || props.retur?.no_retur || '-' }}
                                </span>
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <!-- Body -->
                <div class="flex flex-col gap-6 p-6">
                    <!-- Informasi Retur -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Informasi Retur</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label>Tipe</Label>
                                <div class="grid grid-cols-2 gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        :class="form.tipe === 'retur' ? 'border-primary bg-primary/10 text-primary' : ''"
                                        :disabled="form.processing"
                                        @click="form.tipe = 'retur'"
                                    >
                                        <ArrowLeftRight class="size-4" />
                                        Retur
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        :class="form.tipe === 'tukar' ? 'border-primary bg-primary/10 text-primary' : ''"
                                        :disabled="form.processing"
                                        @click="form.tipe = 'tukar'"
                                    >
                                        <Repeat class="size-4" />
                                        Tukar
                                    </Button>
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="pembelian_id">Pembelian Asal</Label>
                                <SearchableSelect
                                    id="pembelian_id"
                                    v-model="form.pembelian_id"
                                    :options="pembelianOptions"
                                    placeholder="— Pilih Pembelian (opsional) —"
                                    :disabled="form.processing"
                                    @update:model-value="onPembelianChange"
                                />
                                <p v-if="form.errors.pembelian_id" class="text-destructive text-xs">{{ form.errors.pembelian_id }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="tanggal">Tanggal</Label>
                                <Input id="tanggal" v-model="form.tanggal" type="date" :disabled="form.processing" />
                                <p v-if="form.errors.tanggal" class="text-destructive text-xs">{{ form.errors.tanggal }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="supplier_id">Supplier</Label>
                                <SearchableSelect
                                    id="supplier_id"
                                    v-model="form.supplier_id"
                                    :options="supplierOptions"
                                    placeholder="— Cari & Pilih Supplier —"
                                    :disabled="form.processing"
                                    @update:model-value="onSupplierChange"
                                />
                                <p v-if="form.errors.supplier_id" class="text-destructive text-xs">{{ form.errors.supplier_id }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_pembelian_asal">No Pembelian Asal</Label>
                                <Input id="no_pembelian_asal" v-model="form.no_pembelian_asal" placeholder="Otomatis dari pembelian" :disabled="form.processing" />
                                <p v-if="form.errors.no_pembelian_asal" class="text-destructive text-xs">{{ form.errors.no_pembelian_asal }}</p>
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
                            <h3 class="text-sm font-semibold tracking-wide uppercase">
                                Item Barang Diretur
                                <span v-if="form.tipe === 'tukar'" class="text-primary normal-case">— dilengkapi barang pengganti</span>
                            </h3>
                            <div class="bg-border h-px flex-1" />
                        </div>

                        <div>
                            <table class="w-full border-separate border-spacing-0 text-sm">
                                <thead>
                                    <tr>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-left text-xs font-semibold uppercase">Produk Diretur</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-center text-xs font-semibold uppercase">Qty</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-right text-xs font-semibold uppercase">Harga Beli</th>
                                        <th v-if="form.tipe === 'tukar'" class="bg-muted text-muted-foreground border-b px-2 py-2 text-left text-xs font-semibold uppercase">Produk Pengganti</th>
                                        <th v-if="form.tipe === 'tukar'" class="bg-muted text-muted-foreground border-b px-2 py-2 text-center text-xs font-semibold uppercase">Qty Tukar</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-right text-xs font-semibold uppercase">Subtotal</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-muted/40">
                                        <td class="border-b px-2 py-2 align-top">
                                            <SearchableSelect
                                                v-model="item.produk_id"
                                                :options="produkOptions"
                                                placeholder="Cari produk diretur..."
                                                class="min-w-0 flex-1"
                                                @update:model-value="onProdukChange(item)"
                                            />
                                            <p v-if="form.errors[`items.${index}.produk_id`]" class="text-destructive mt-1 text-xs">
                                                {{ form.errors[`items.${index}.produk_id`] }}
                                            </p>
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.qty_retur" type="number" min="1" step="1" placeholder="1" class="h-10 w-16 text-center" />
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.harga_beli" type="number" min="0" step="1" placeholder="0" class="h-10 w-24 text-right" />
                                        </td>
                                        <td v-if="form.tipe === 'tukar'" class="border-b px-2 py-2 align-top">
                                            <SearchableSelect
                                                v-model="item.produk_tukar_id"
                                                :options="produkOptions"
                                                placeholder="Cari produk pengganti..."
                                                class="min-w-0 flex-1"
                                                @update:model-value="onProdukTukarChange(item)"
                                            />
                                            <p v-if="form.errors[`items.${index}.produk_tukar_id`]" class="text-destructive mt-1 text-xs">
                                                {{ form.errors[`items.${index}.produk_tukar_id`] }}
                                            </p>
                                        </td>
                                        <td v-if="form.tipe === 'tukar'" class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.qty_tukar" type="number" min="0" step="1" placeholder="0" class="h-10 w-16 text-center" />
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <div class="bg-muted/50 rounded-md border px-2 py-2 text-right text-sm font-semibold whitespace-nowrap">
                                                Rp {{ formatAngka(itemSubtotal(item)) }}
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

                    <!-- Alasan & Keterangan -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Keterangan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label htmlFor="alasan">Alasan Retur</Label>
                                <textarea
                                    id="alasan"
                                    v-model="form.alasan"
                                    rows="2"
                                    class="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                    placeholder="Cth: barang rusak, kadaluarsa, salah kirim..."
                                />
                                <p v-if="form.errors.alasan" class="text-destructive text-xs">{{ form.errors.alasan }}</p>
                            </div>
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
                        </div>
                    </section>

                    <!-- Ringkasan Total -->
                    <section class="flex flex-col gap-3">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Ringkasan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <span class="text-muted-foreground text-sm">Total Retur</span>
                            <span class="text-primary text-xl font-bold">Rp {{ formatAngka(totalRetur) }}</span>
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
                        <ArrowLeftRight v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
