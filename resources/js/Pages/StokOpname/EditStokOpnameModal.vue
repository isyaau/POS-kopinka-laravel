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
import { Loader2, ClipboardCheck, Plus, Trash2, Calculator } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'
import SearchableSelect from './SearchableSelect.vue'

const props = defineProps({
    open: { type: Boolean, default: false },
    opname: { type: Object, default: null },
    produk: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

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

const fillForm = () => {
    const p = props.opname
    if (!p) return

    form.tanggal = p.tanggal || ''
    form.status = p.status || 'draft'
    form.petugas = p.petugas || ''
    form.keterangan = p.keterangan || ''

    form.items = (p.details || []).map((d) => ({
        produk_id: d.produk_id || '',
        nama_barang: d.nama_barang || '',
        stok_sistem: d.stok_sistem || 0,
        stok_fisik: d.stok_fisik || 0,
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
    form.put(`/stok-opname/${props.opname.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            toast.success('Stok opname berhasil diperbarui.')
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
                            <DialogTitle class="text-xl">Edit Stok Opname</DialogTitle>
                            <DialogDescription>
                                <span class="bg-primary/10 text-primary inline-block rounded px-1.5 py-0.5 text-xs font-semibold">
                                    {{ props.opname?.no_opname || '-' }}
                                </span>
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
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
