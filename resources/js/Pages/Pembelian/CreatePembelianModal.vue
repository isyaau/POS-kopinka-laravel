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
import { Switch } from '@/components/ui/switch'
import { Loader2, ShoppingBag, Plus, Trash2, List } from 'lucide-vue-next'
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
    qty: 1,
    harga_beli: '',
    diskon_item: 0,
    harga_jual: '',
    tanggal_expired: '',
    _manual: false,
})

const form = useForm({
    no_pembelian: '',
    no_faktur: '',
    supplier_id: '',
    tanggal: '',
    terlampir_bukti_ppn: false,
    harga_jual_termasuk_ppn: false,
    diskon: 0,
    ppn_masukan: 0,
    jenis_bayar: 'tunai',
    status: 'selesai',
    keterangan: '',
    items: [emptyItem()],
})

const resetForm = () => {
    form.reset()
    form.no_pembelian = ''
    form.no_faktur = ''
    form.supplier_id = ''
    form.tanggal = ''
    form.terlampir_bukti_ppn = false
    form.harga_jual_termasuk_ppn = false
    form.diskon = 0
    form.ppn_masukan = 0
    form.jenis_bayar = 'tunai'
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
        }
    },
)

// Pilih produk di baris item → isi otomatis nama, harga beli, harga jual
const onProdukChange = (item) => {
    const p = props.produk.find((x) => Number(x.id) === Number(item.produk_id))
    if (p) {
        item.nama_barang = p.nama_barang
        if (!item.harga_beli) item.harga_beli = Math.round(Number(p.harga_beli) || 0)
        if (!item.harga_jual) item.harga_jual = Math.round(Number(p.harga_jual) || 0)
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

// Toggle mode: pilih produk dari database ⇄ ketik produk baru
const toggleManual = (item) => {
    item._manual = !item._manual
    if (item._manual) {
        item.produk_id = ''
    } else if (!item.nama_barang) {
        item.nama_barang = ''
    }
}

// Subtotal item: qty * (harga_beli - diskon_item)
const itemSubtotal = (item) => {
    const qty = Number(item.qty) || 0
    const harga = Number(item.harga_beli) || 0
    const diskon = Number(item.diskon_item) || 0
    return Math.max(0, qty * (harga - diskon))
}

// Nilai total item (sebelum diskon header)
const nilai = computed(() =>
    form.items.reduce((sum, item) => sum + itemSubtotal(item), 0),
)

const subtotal = computed(() => Math.max(0, nilai.value - (Number(form.diskon) || 0)))

// Total = subtotal + PPN (kecuali harga jual sudah termasuk PPN)
const total = computed(() => {
    const ppn = Number(form.ppn_masukan) || 0
    return form.harga_jual_termasuk_ppn ? subtotal.value : subtotal.value + ppn
})

const sisaHutang = computed(() => {
    if (form.jenis_bayar === 'kredit') return total.value
    return 0
})

const submit = () => {
    // Pastikan perhitungan terkirim
    form.nilai = nilai.value
    form.subtotal = subtotal.value
    form.total = total.value
    form.sisa_hutang = sisaHutang.value

    form.post('/pembelian', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
            toast.success('Pembelian berhasil disimpan.')
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
                            <ShoppingBag class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Tambah Pembelian</DialogTitle>
                            <DialogDescription>Catat pembelian barang dari supplier.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <!-- Body -->
                <div class="flex flex-col gap-6 p-6">
                    <!-- Informasi Pembelian -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Informasi Pembelian</h3>
                            <div class="bg-border h-px flex-1" />
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
                                />
                                <p v-if="form.errors.supplier_id" class="text-destructive text-xs">{{ form.errors.supplier_id }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_faktur">No Faktur</Label>
                                <Input id="no_faktur" v-model="form.no_faktur" placeholder="No faktur supplier" :disabled="form.processing" />
                                <p v-if="form.errors.no_faktur" class="text-destructive text-xs">{{ form.errors.no_faktur }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="tanggal">Tanggal</Label>
                                <Input id="tanggal" v-model="form.tanggal" type="date" :disabled="form.processing" />
                                <p v-if="form.errors.tanggal" class="text-destructive text-xs">{{ form.errors.tanggal }}</p>
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
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-right text-xs font-semibold uppercase">Harga Beli</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-right text-xs font-semibold uppercase">Diskon</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-right text-xs font-semibold uppercase">Harga Jual</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-center text-xs font-semibold uppercase">Expired</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-right text-xs font-semibold uppercase">Subtotal</th>
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
                                            <Input v-model="item.harga_beli" type="number" min="0" step="1" placeholder="0" class="h-10 w-24 text-right" />
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.diskon_item" type="number" min="0" step="0.01" placeholder="0" class="h-10 w-20 text-right" />
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.harga_jual" type="number" min="0" step="1" placeholder="0" class="h-10 w-24 text-right" />
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.tanggal_expired" type="date" class="h-10 w-28" />
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

                    <!-- PPN & Toggle -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">PPN & Harga</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <Switch id="terlampir_bukti_ppn" v-model="form.terlampir_bukti_ppn" />
                                <Label htmlFor="terlampir_bukti_ppn">Terlampir Bukti PPN</Label>
                            </div>
                            <div class="flex items-center gap-3">
                                <Switch id="harga_jual_termasuk_ppn" v-model="form.harga_jual_termasuk_ppn" />
                                <Label htmlFor="harga_jual_termasuk_ppn">Harga Jual Termasuk PPN</Label>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="diskon">Diskon Pembelian (Rp)</Label>
                                <Input id="diskon" v-model="form.diskon" type="number" min="0" step="0.01" placeholder="0" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="ppn_masukan">PPN Masukan (Rp)</Label>
                                <Input id="ppn_masukan" v-model="form.ppn_masukan" type="number" min="0" step="0.01" placeholder="0" />
                                <p v-if="form.errors.ppn_masukan" class="text-destructive text-xs">{{ form.errors.ppn_masukan }}</p>
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

                    <!-- Pembayaran -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Pembayaran</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                            <div class="grid gap-2">
                                <Label htmlFor="jenis_bayar">Jenis Bayar</Label>
                                <select
                                    id="jenis_bayar"
                                    v-model="form.jenis_bayar"
                                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-10 w-full items-center justify-between rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                >
                                    <option value="tunai">Tunai</option>
                                    <option value="kredit">Kredit</option>
                                </select>
                            </div>
                            <div v-if="form.jenis_bayar === 'kredit'" class="grid gap-2">
                                <Label>Sisa Hutang</Label>
                                <div class="text-destructive bg-muted/50 rounded-md border px-3 py-2 text-sm font-semibold">
                                    Rp {{ formatAngka(sisaHutang) }}
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label>Subtotal</Label>
                                <div class="bg-muted/50 rounded-md border px-3 py-2 text-sm">Rp {{ formatAngka(subtotal) }}</div>
                            </div>
                            <div class="grid gap-2">
                                <Label>Total</Label>
                                <div class="bg-primary/10 text-primary rounded-md border px-3 py-2 text-sm font-semibold">
                                    Rp {{ formatAngka(total) }}
                                </div>
                            </div>
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
                    </section>
                </div>

                <!-- Footer -->
                <DialogFooter class="border-t p-6 pt-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <ShoppingBag v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Pembelian' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
