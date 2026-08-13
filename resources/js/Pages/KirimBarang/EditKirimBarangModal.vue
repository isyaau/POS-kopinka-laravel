<script setup>
import { ref, watch, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
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
import { Loader2, Truck, Plus, Trash2 } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'
import SearchableSelect from '@/Pages/Pembelian/SearchableSelect.vue'

const props = defineProps({
    open: { type: Boolean, default: false },
    kirim: { type: Object, default: null },
    stores: { type: Array, default: () => [] },
    produk: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const page = usePage()
const currentStoreId = computed(() => {
    const id = page.props.auth?.current_store?.id
    return id && id !== 'all' ? Number(id) : null
})

const storeOptions = computed(() =>
    props.stores.map((s) => ({ value: s.id, label: s.nama, sublabel: s.kode })),
)

const produkOptions = computed(() =>
    props.produk.map((p) => {
        const row = (p.stoks || []).find((x) => x.store_id === currentStoreId.value)
        const stok = row ? Number(row.stok) : 0
        return {
            value: p.id,
            label: p.nama_barang,
            sublabel: `${p.kode_barang} • Stok: ${stok}`,
        }
    }),
)

const emptyItem = () => ({ produk_id: '', nama_barang: '', qty: 1, harga_beli: '' })

const form = useForm({
    _method: 'PUT',
    tanggal: '',
    store_asal_id: '',
    store_tujuan_id: '',
    status: 'selesai',
    keterangan: '',
    details: [emptyItem()],
})

const fillFrom = (k) => {
    if (!k) return
    form.tanggal = k.tanggal ? String(k.tanggal).slice(0, 10) : ''
    form.store_asal_id = k.store_asal_id ?? ''
    form.store_tujuan_id = k.store_tujuan_id ?? ''
    form.status = k.status ?? 'selesai'
    form.keterangan = k.keterangan ?? ''
    form.details = (k.details && k.details.length)
        ? k.details.map((d) => ({
            produk_id: d.produk_id,
            nama_barang: d.nama_barang,
            qty: d.qty,
            harga_beli: d.harga_beli,
        }))
        : [emptyItem()]
    form.clearErrors()
}

watch(
    () => props.open,
    (val) => { if (val && props.kirim) fillFrom(props.kirim) },
)

const onProdukChange = (item) => {
    const p = props.produk.find((x) => Number(x.id) === Number(item.produk_id))
    if (p) {
        item.nama_barang = p.nama_barang
        if (!item.harga_beli) item.harga_beli = Math.round(Number(p.harga_beli) || 0)
    }
}

const addItem = () => { form.details.push(emptyItem()) }
const removeItem = (index) => { if (form.details.length > 1) form.details.splice(index, 1) }

const formatAngka = (val) => Number(val || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 })

const totalNilai = computed(() =>
    form.details.reduce((sum, item) => sum + (Number(item.qty) || 0) * (Number(item.harga_beli) || 0), 0),
)

const submit = () => {
    if (!props.kirim) return
    form.post(`/kirim-barang/${props.kirim.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            toast.success('Kirim barang berhasil diperbarui.')
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90svh] max-w-4xl overflow-y-auto p-0">
            <form @submit.prevent="submit" class="flex flex-col">
                <DialogHeader class="border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <Truck class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Edit Kirim Barang</DialogTitle>
                            <DialogDescription>{{ kirim?.no_kirim }}</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="flex flex-col gap-6 p-6">
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Informasi Pengiriman</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label>Toko Asal</Label>
                                <SearchableSelect v-model="form.store_asal_id" :options="storeOptions" placeholder="— Pilih Toko Asal —" :disabled="form.processing" />
                                <p v-if="form.errors.store_asal_id" class="text-destructive text-xs">{{ form.errors.store_asal_id }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label>Toko Tujuan</Label>
                                <SearchableSelect v-model="form.store_tujuan_id" :options="storeOptions" placeholder="— Pilih Toko Tujuan —" :disabled="form.processing" />
                                <p v-if="form.errors.store_tujuan_id" class="text-destructive text-xs">{{ form.errors.store_tujuan_id }}</p>
                            </div>
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
                                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-10 w-full items-center justify-between rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                >
                                    <option value="selesai">Selesai (stok langsung mutasi)</option>
                                    <option value="dikirim">Dikirim (stok langsung mutasi)</option>
                                    <option value="draft">Draft (belum mutasi)</option>
                                </select>
                                <p v-if="form.errors.status" class="text-destructive text-xs">{{ form.errors.status }}</p>
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label htmlFor="keterangan">Keterangan</Label>
                            <textarea
                                id="keterangan"
                                v-model="form.keterangan"
                                rows="2"
                                class="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                placeholder="Catatan (opsional)"
                            />
                        </div>
                    </section>

                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Daftar Produk</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div>
                            <table class="w-full border-separate border-spacing-0 text-sm">
                                <thead>
                                    <tr>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-left text-xs font-semibold uppercase">Produk</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-center text-xs font-semibold uppercase">Qty</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-right text-xs font-semibold uppercase">Harga Beli</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2 text-right text-xs font-semibold uppercase">Subtotal</th>
                                        <th class="bg-muted text-muted-foreground border-b px-2 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.details" :key="index" class="hover:bg-muted/40">
                                        <td class="border-b px-2 py-2 align-top">
                                            <SearchableSelect
                                                v-model="item.produk_id"
                                                :options="produkOptions"
                                                placeholder="Cari produk..."
                                                @update:model-value="onProdukChange(item)"
                                            />
                                            <p v-if="form.errors[`details.${index}.produk_id`]" class="text-destructive mt-1 text-xs">
                                                {{ form.errors[`details.${index}.produk_id`] }}
                                            </p>
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.qty" type="number" min="1" step="1" placeholder="1" class="h-10 w-16 text-center" />
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Input v-model="item.harga_beli" type="number" min="0" step="1" placeholder="0" class="h-10 w-24 text-right" />
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <div class="bg-muted/50 rounded-md border px-2 py-2 text-right text-sm font-semibold whitespace-nowrap">
                                                Rp {{ formatAngka((Number(item.qty) || 0) * (Number(item.harga_beli) || 0)) }}
                                            </div>
                                        </td>
                                        <td class="border-b px-2 py-2 align-top">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="size-8"
                                                :disabled="form.details.length <= 1"
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

                    <section class="flex justify-end">
                        <div class="grid gap-2">
                            <Label>Total Nilai</Label>
                            <div class="bg-primary/10 text-primary rounded-md border px-3 py-2 text-sm font-semibold">
                                Rp {{ formatAngka(totalNilai) }}
                            </div>
                        </div>
                    </section>
                </div>

                <DialogFooter class="border-t p-6 pt-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <Truck v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Perbarui' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
