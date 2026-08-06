<script setup>
import { computed, ref, watch } from 'vue'
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
import { Loader2, Plus, Trash2, ClipboardList } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    anggota: { type: Array, default: () => [] },
    produk: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const form = useForm({
    no_nota: '',
    tanggal: '',
    no_kasir: '',
    anggota_id: '',
    nama_anggota: '',
    diskon: '',
    usaha: '',
    jasa: '',
    ppn: '',
    cash: '',
    qris: '',
    edc: '',
    voucher: '',
    piutang: '',
    items: [],
})

const resetForm = () => {
    form.reset()
    form.no_nota = ''
    form.tanggal = ''
    form.no_kasir = ''
    form.anggota_id = ''
    form.nama_anggota = ''
    form.diskon = ''
    form.usaha = ''
    form.jasa = ''
    form.ppn = ''
    form.cash = ''
    form.qris = ''
    form.edc = ''
    form.voucher = ''
    form.piutang = ''
    form.items = []
    form.clearErrors()
}

watch(
    () => props.open,
    (val) => {
        if (val) {
            resetForm()
            // default tanggal hari ini
            const d = new Date()
            form.tanggal = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
        }
    },
)

const addItem = () => {
    form.items.push({ produk_id: '', nama_barang: '', qty: 1, harga: '', diskon_item: '' })
}

const removeItem = (index) => {
    form.items.splice(index, 1)
}

// Auto-fill nama & harga saat pilih produk
const onProdukChange = (index) => {
    const item = form.items[index]
    const produk = props.produk.find((p) => String(p.id) === String(item.produk_id))
    if (produk) {
        item.nama_barang = produk.nama_barang
        if (!item.harga || Number(item.harga) === 0) {
            item.harga = produk.harga_jual
        }
    }
}

const itemSubtotal = (item) => {
    const harga = Number(item.harga || 0)
    const diskon = Number(item.diskon_item || 0)
    const qty = Number(item.qty || 0)
    return Math.max(0, qty * (harga - diskon))
}

const totalNilai = computed(() => form.items.reduce((sum, item) => sum + itemSubtotal(item), 0))

const totalJual = computed(() => Math.max(0, totalNilai.value - Number(form.diskon || 0)))

const formatRupiah = (val) => {
    const n = Number(val || 0)
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(n)
}

// Saat pilih anggota, isi nama otomatis
const onAnggotaChange = () => {
    const anggota = props.anggota.find((a) => String(a.id) === String(form.anggota_id))
    form.nama_anggota = anggota ? anggota.nama : ''
}

const submit = () => {
    form.post('/transaksi', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
            toast.success('Transaksi berhasil disimpan.')
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[92svh] max-w-4xl overflow-y-auto p-0">
            <form @submit.prevent="submit" class="flex flex-col">
                <!-- Header -->
                <DialogHeader class="border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <ClipboardList class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Tambah Transaksi</DialogTitle>
                            <DialogDescription>Catat transaksi penjualan beserta item barang.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <!-- Body -->
                <div class="flex flex-col gap-6 p-6">
                    <!-- Info Nota -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Info Nota</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="no_nota">No Nota</Label>
                                <Input id="no_nota" v-model="form.no_nota" placeholder="Kosong = otomatis" />
                                <p v-if="form.errors.no_nota" class="text-destructive text-xs">{{ form.errors.no_nota }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_kasir">No Kasir</Label>
                                <Input id="no_kasir" v-model="form.no_kasir" placeholder="No kasir" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="tanggal">Tanggal</Label>
                                <Input id="tanggal" v-model="form.tanggal" type="date" />
                                <p v-if="form.errors.tanggal" class="text-destructive text-xs">{{ form.errors.tanggal }}</p>
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label htmlFor="anggota_id">Anggota</Label>
                                <select
                                    id="anggota_id"
                                    v-model="form.anggota_id"
                                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-10 w-full items-center justify-between rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    @change="onAnggotaChange"
                                >
                                    <option value="">— Pilih Anggota —</option>
                                    <option v-for="a in anggota" :key="a.id" :value="a.id">
                                        {{ a.nip }} — {{ a.nama }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="nama_anggota">Nama Anggota</Label>
                                <Input id="nama_anggota" v-model="form.nama_anggota" placeholder="Nama anggota (snapshot)" />
                            </div>
                        </div>
                    </section>

                    <!-- Item Barang -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold tracking-wide uppercase">Item Barang</h3>
                                <div class="bg-border h-px flex-1" />
                            </div>
                            <Button type="button" variant="outline" size="sm" @click="addItem">
                                <Plus class="size-4" />
                                Tambah Item
                            </Button>
                        </div>

                        <div v-if="form.items.length === 0" class="text-muted-foreground border border-dashed rounded-lg p-6 text-center text-sm">
                            Belum ada item. Klik "Tambah Item" untuk menambahkan barang ke nota.
                        </div>

                        <div v-else class="flex flex-col gap-3">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="border-input rounded-lg border p-3"
                            >
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-6">
                                    <div class="grid gap-1.5 sm:col-span-2">
                                        <Label class="text-xs">Produk</Label>
                                        <select
                                            v-model="item.produk_id"
                                            class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-9 w-full items-center justify-between rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                            @change="onProdukChange(index)"
                                        >
                                            <option value="">— Pilih Produk —</option>
                                            <option v-for="p in produk" :key="p.id" :value="p.id">
                                                {{ p.kode_barang }} — {{ p.nama_barang }} (stok {{ p.stok }})
                                            </option>
                                        </select>
                                    </div>
                                    <div class="grid gap-1.5 sm:col-span-1">
                                        <Label class="text-xs">Nama</Label>
                                        <Input v-model="item.nama_barang" placeholder="Nama barang" class="h-9" />
                                    </div>
                                    <div class="grid gap-1.5 sm:col-span-1">
                                        <Label class="text-xs">Qty</Label>
                                        <Input v-model.number="item.qty" type="number" min="1" step="1" class="h-9" />
                                    </div>
                                    <div class="grid gap-1.5 sm:col-span-1">
                                        <Label class="text-xs">Harga</Label>
                                        <Input v-model.number="item.harga" type="number" min="0" step="100" class="h-9" />
                                    </div>
                                    <div class="grid gap-1.5 sm:col-span-1">
                                        <Label class="text-xs">Diskon</Label>
                                        <Input v-model.number="item.diskon_item" type="number" min="0" step="100" class="h-9" />
                                    </div>
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <p class="text-primary text-sm font-semibold">
                                        Subtotal: {{ formatRupiah(itemSubtotal(item)) }}
                                    </p>
                                    <Button type="button" variant="ghost" size="sm" class="text-destructive" @click="removeItem(index)">
                                        <Trash2 class="size-4" />
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div class="bg-muted/50 flex items-center justify-between rounded-lg border px-4 py-3">
                            <span class="text-muted-foreground text-sm">Total Nilai (dari item)</span>
                            <span class="text-lg font-bold">{{ formatRupiah(totalNilai) }}</span>
                        </div>
                    </section>

                    <!-- Diskon & PPN -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Diskon, PPN & Kategori Nilai</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="diskon">Diskon (Rp)</Label>
                                <Input id="diskon" v-model="form.diskon" type="number" min="0" step="100" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="usaha">USAHA (Rp)</Label>
                                <Input id="usaha" v-model="form.usaha" type="number" min="0" step="100" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="jasa">JASA (Rp)</Label>
                                <Input id="jasa" v-model="form.jasa" type="number" min="0" step="100" />
                            </div>
                            <div class="grid gap-2 sm:col-span-3">
                                <Label htmlFor="ppn">PPN.K (Rp)</Label>
                                <Input id="ppn" v-model="form.ppn" type="number" min="0" step="100" />
                            </div>
                        </div>
                        <div class="bg-primary/10 text-primary rounded-lg border px-4 py-3 text-sm font-semibold">
                            JUAL = NILAI − DISKON = {{ formatRupiah(totalJual) }}
                        </div>
                    </section>

                    <!-- Metode Pembayaran -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Metode Pembayaran</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                            <div class="grid gap-2">
                                <Label htmlFor="cash">CASH</Label>
                                <Input id="cash" v-model="form.cash" type="number" min="0" step="100" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="qris">QRIS</Label>
                                <Input id="qris" v-model="form.qris" type="number" min="0" step="100" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="edc">EDC</Label>
                                <Input id="edc" v-model="form.edc" type="number" min="0" step="100" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="voucher">VOUCHER</Label>
                                <Input id="voucher" v-model="form.voucher" type="number" min="0" step="100" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="piutang">PIUTANG</Label>
                                <Input id="piutang" v-model="form.piutang" type="number" min="0" step="100" />
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Footer -->
                <DialogFooter class="border-t p-6 pt-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing || form.items.length === 0">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <Plus v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Transaksi' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
