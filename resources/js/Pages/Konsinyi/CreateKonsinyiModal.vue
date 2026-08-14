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
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectTrigger, SelectContent, SelectItem } from '@/components/ui/select'
import { Loader2, Plus, Trash2, Calculator, PackagePlus } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    suppliers: { type: Array, default: () => [] },
    produks: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const today = new Date().toISOString().slice(0, 10)

const emptyItem = () => ({
    produk_id: '',
    kode_barang: '',
    nama_barang: '',
    satuan: '',
    qty: 1,
    harga_beli: '',
    harga_jual: '',
    subtotal: 0,
})

const form = useForm({
    jenis: 'pembayaran',
    tgl_transaksi: today,
    no_bukti: '',
    no_faktur: '',
    supplier_id: '',
    kode_supplier: '',
    nama_supplier: '',
    alamat: '',
    no_telp: '',
    contact_person: '',
    diskon: '0',
    total: 0,
    jumlah_bayar: '',
    kurang_bayar: 0,
    keterangan: '',
    items: [emptyItem()],
})

const resetForm = () => {
    form.reset()
    form.jenis = 'pembayaran'
    form.tgl_transaksi = today
    form.no_bukti = ''
    form.no_faktur = ''
    form.supplier_id = ''
    form.kode_supplier = ''
    form.nama_supplier = ''
    form.alamat = ''
    form.no_telp = ''
    form.contact_person = ''
    form.diskon = '0'
    form.total = 0
    form.jumlah_bayar = ''
    form.kurang_bayar = 0
    form.keterangan = ''
    form.items = [emptyItem()]
    form.clearErrors()
}

watch(() => props.open, (val) => { if (val) resetForm() })

const onSupplierChange = (val) => {
    form.supplier_id = val
}

const onProdukChange = (item) => {
    const p = props.produks.find((x) => String(x.id) === String(item.produk_id))
    if (p) {
        item.kode_barang = p.kode_barang
        item.nama_barang = p.nama_barang
        item.satuan = p.satuan
        item.harga_beli = p.harga_beli
        item.harga_jual = p.harga_jual
        recalcItem(item)
        recalcTotals()
    }
}

const recalcItem = (item) => {
    const qty = Number(item.qty || 0)
    const harga = Number(item.jenis === 'retur' ? item.harga_beli : item.harga_jual || item.harga_beli) || 0
    item.subtotal = Math.max(0, qty * harga)
}

const recalcTotals = () => {
    const gross = form.items.reduce((sum, item) => sum + Number(item.subtotal || 0), 0)
    const diskon = Number(form.diskon || 0)
    const total = Math.max(0, gross - diskon)
    form.total = total
    const jumlahBayar = Number(form.jumlah_bayar || 0)
    form.kurang_bayar = Math.max(0, total - jumlahBayar)
}

const addItem = () => { form.items.push(emptyItem()) }
const removeItem = (index) => { if (form.items.length > 1) form.items.splice(index, 1); recalcTotals() }

const formatAngka = (val) => Number(val || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 })

const onJenisChange = () => { form.items.forEach(recalcItem); recalcTotals() }

const submit = () => {
    form.items.forEach(recalcItem)
    recalcTotals()
    form.post('/konsinyi', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
            toast.success('Transaksi konsinyi berhasil dicatat.')
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="!flex !flex-col max-h-[92svh] max-w-6xl overflow-hidden p-0">
            <form @submit.prevent="submit" class="flex min-h-0 flex-1 flex-col">
                <DialogHeader class="shrink-0 border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <PackagePlus class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Tambah Transaksi Konsinyi</DialogTitle>
                            <DialogDescription>Retur atau pembayaran barang titipan (konsinyi) — dapat multi item.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <!-- Jenis & Supplier -->
                    <section class="mb-6 flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Jenis & Supplier</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label>Jenis Transaksi</Label>
                                <Select v-model="form.jenis" @update:model-value="onJenisChange">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih jenis" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pembayaran">Pembayaran</SelectItem>
                                        <SelectItem value="retur">Retur</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label>Tanggal Transaksi</Label>
                                <Input v-model="form.tgl_transaksi" type="date" :disabled="form.processing" />
                                <p v-if="form.errors.tgl_transaksi" class="text-destructive text-xs">{{ form.errors.tgl_transaksi }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label>Supplier</Label>
                                <Select v-model="form.supplier_id" @update:model-value="onSupplierChange">
                                    <SelectTrigger placeholder="Pilih supplier" />
                                    <SelectContent>
                                        <SelectItem v-for="s in suppliers" :key="s.id" :value="String(s.id)">{{ s.nama }} ({{ s.kode }})</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.supplier_id" class="text-destructive text-xs">{{ form.errors.supplier_id }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="kode_supplier">Kode Supplier</Label>
                                <Input id="kode_supplier" v-model="form.kode_supplier" placeholder="Auto / manual" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="nama_supplier">Nama Supplier</Label>
                                <Input id="nama_supplier" v-model="form.nama_supplier" placeholder="Nama supplier" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_faktur">No. Faktur</Label>
                                <Input id="no_faktur" v-model="form.no_faktur" />
                            </div>
                            <div class="grid gap-2 sm:col-span-3">
                                <Label htmlFor="alamat">Alamat</Label>
                                <Textarea id="alamat" v-model="form.alamat" rows="2" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_telp">No. Telp</Label>
                                <Input id="no_telp" v-model="form.no_telp" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="contact_person">Contact Person</Label>
                                <Input id="contact_person" v-model="form.contact_person" />
                            </div>
                        </div>
                    </section>

                    <!-- Daftar Item Barang -->
                    <section class="mb-6 flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Daftar Barang Konsinyi</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="rounded-lg border">
                            <table class="w-full border-separate border-spacing-0 text-sm">
                                <thead>
                                    <tr>
                                        <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-left text-xs font-semibold uppercase">Produk</th>
                                        <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-center text-xs font-semibold uppercase">Qty</th>
                                        <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Harga Beli</th>
                                        <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Harga Jual</th>
                                        <th class="bg-muted text-muted-foreground border-b px-3 py-2 text-right text-xs font-semibold uppercase">Subtotal</th>
                                        <th class="bg-muted text-muted-foreground border-b px-3 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-muted/40">
                                        <td class="border-b px-3 py-2 align-top">
                                            <Select v-model="item.produk_id" @update:model-value="onProdukChange(item)">
                                                <SelectTrigger placeholder="Pilih produk" class="w-56" />
                                                <SelectContent>
                                                    <SelectItem v-for="p in produks" :key="p.id" :value="String(p.id)">{{ p.nama_barang }} ({{ p.kode_barang }})</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Input v-model="item.nama_barang" placeholder="Nama barang" class="mt-2 h-9" />
                                            <p v-if="form.errors[`items.${index}.nama_barang`]" class="text-destructive mt-1 text-xs">{{ form.errors[`items.${index}.nama_barang`] }}</p>
                                        </td>
                                        <td class="border-b px-3 py-2 align-top">
                                            <Input v-model="item.qty" type="number" min="1" class="h-9 w-20 text-center" @input="recalcItem(item); recalcTotals()" />
                                            <Input v-model="item.satuan" placeholder="satuan" class="mt-2 h-9 w-20" />
                                        </td>
                                        <td class="border-b px-3 py-2 align-top">
                                            <Input v-model="item.harga_beli" type="number" min="0" step="0.01" class="h-9 w-28 text-right" @input="recalcItem(item); recalcTotals()" />
                                        </td>
                                        <td class="border-b px-3 py-2 align-top">
                                            <Input v-model="item.harga_jual" type="number" min="0" step="0.01" class="h-9 w-28 text-right" @input="recalcItem(item); recalcTotals()" />
                                        </td>
                                        <td class="border-b px-3 py-2 align-top">
                                            <div class="bg-muted/50 text-foreground rounded-md border px-2 py-2 text-right text-sm font-semibold whitespace-nowrap">
                                                Rp {{ formatAngka(item.subtotal) }}
                                            </div>
                                        </td>
                                        <td class="border-b px-3 py-2 align-top">
                                            <Button type="button" variant="ghost" size="icon" class="text-destructive size-8" :disabled="form.items.length <= 1" @click="removeItem(index)">
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Button type="button" variant="outline" size="sm" class="w-fit" @click="addItem">
                            <Plus class="size-4" />
                            Tambah Barang
                        </Button>
                    </section>

                    <!-- Pembayaran -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Pembayaran & Ringkasan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="diskon">Diskon (Rp)</Label>
                                <Input id="diskon" v-model="form.diskon" type="number" min="0" step="0.01" @input="recalcTotals()" :disabled="form.processing" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="jumlah_bayar">Jumlah Bayar (Rp)</Label>
                                <Input id="jumlah_bayar" v-model="form.jumlah_bayar" type="number" min="0" step="0.01" @input="recalcTotals()" :disabled="form.processing" />
                                <p v-if="form.errors.jumlah_bayar" class="text-destructive text-xs">{{ form.errors.jumlah_bayar }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_bukti2">No. Bukti</Label>
                                <Input id="no_bukti2" v-model="form.no_bukti" placeholder="Kosongkan untuk otomatis" />
                            </div>
                            <div class="grid gap-2 sm:col-span-3">
                                <Label htmlFor="keterangan">Keterangan</Label>
                                <Textarea id="keterangan" v-model="form.keterangan" rows="2" />
                            </div>
                            <div class="sm:col-span-3">
                                <div class="flex items-center justify-between rounded-lg border p-4">
                                    <span class="text-muted-foreground text-sm">Total (setelah diskon)</span>
                                    <span class="text-primary text-xl font-bold">Rp {{ formatAngka(form.total) }}</span>
                                </div>
                                <p class="text-muted-foreground mt-1 text-sm">Kurang Bayar: <span class="text-destructive font-semibold">Rp {{ formatAngka(form.kurang_bayar) }}</span></p>
                            </div>
                        </div>
                    </section>
                </div>

                <DialogFooter class="shrink-0 border-t p-6 pt-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <Calculator v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Transaksi' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
