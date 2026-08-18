<script setup>
import { watch } from 'vue'
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
import { Loader2, Save, AlertTriangle } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    item: { type: Object, default: null },
    produks: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const toDate = (v) => (v ? String(v).slice(0, 10) : '')

const form = useForm({
    tgl_register: '',
    no_bukti: '',
    produk_id: '',
    kode_barang: '',
    nama_barang: '',
    kategori: '',
    satuan: '',
    no_rak: '',
    harga_jual: '',
    jumlah_label: '',
    ukuran_label: '',
    keterangan: '',
    status: 'draft',
})

const ukuranOptions = [
    { value: 'kecil', label: 'Kecil' },
    { value: 'sedang', label: 'Sedang' },
    { value: 'besar', label: 'Besar' },
]

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'tercetak', label: 'Tercetak' },
    { value: 'dipasang', label: 'Dipasang' },
    { value: 'batal', label: 'Batal' },
]

watch(() => props.open, (val) => {
    if (val && props.item) {
        const b = props.item
        form.clearErrors()
        form.tgl_register = toDate(b.tgl_register) || new Date().toISOString().slice(0, 10)
        form.no_bukti = b.no_bukti || ''
        form.produk_id = b.produk_id ? String(b.produk_id) : ''
        form.kode_barang = b.kode_barang || ''
        form.nama_barang = b.nama_barang || ''
        form.kategori = b.kategori || ''
        form.satuan = b.satuan || ''
        form.no_rak = b.no_rak || ''
        form.harga_jual = b.harga_jual ?? ''
        form.jumlah_label = b.jumlah_label ?? ''
        form.ukuran_label = b.ukuran_label || ''
        form.keterangan = b.keterangan || ''
        form.status = b.status || 'draft'
    }
})

const submit = () => {
    if (!props.item) return
    form.put(`/register-label-etalase-barang/${props.item.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            setTimeout(() => toast.success('Data register label etalase barang berhasil diperbarui.'), 50)
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90svh] max-w-3xl overflow-y-auto p-0">
            <form @submit.prevent="submit" class="flex flex-col">
                <DialogHeader class="border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <Save class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Edit Register Label Etalase</DialogTitle>
                            <DialogDescription>Perbarui data register label etalase barang.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="flex flex-col gap-6 p-6">
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Data Produk</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="kode_barang">Kode Barang</Label>
                                <Input id="kode_barang" v-model="form.kode_barang" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="nama_barang">Nama Barang</Label>
                                <Input id="nama_barang" v-model="form.nama_barang" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="kategori">Kategori</Label>
                                <Input id="kategori" v-model="form.kategori" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="satuan">Satuan</Label>
                                <Input id="satuan" v-model="form.satuan" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_rak">No. Rak</Label>
                                <Input id="no_rak" v-model="form.no_rak" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="harga_jual">Harga Jual (Rp)</Label>
                                <Input id="harga_jual" v-model="form.harga_jual" type="number" min="0" step="0.01" />
                            </div>
                        </div>
                    </section>

                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Detail Label</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="tgl_register">Tgl Register</Label>
                                <Input id="tgl_register" v-model="form.tgl_register" type="date" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_bukti">No. Bukti</Label>
                                <Input id="no_bukti" v-model="form.no_bukti" />
                                <p v-if="form.errors.no_bukti" class="text-destructive text-xs">{{ form.errors.no_bukti }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger />
                                    <SelectContent>
                                        <SelectItem v-for="s in statusOptions" :key="s.value" :value="s.value">
                                            {{ s.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="jumlah_label">Jumlah Label</Label>
                                <Input id="jumlah_label" v-model="form.jumlah_label" type="number" min="1" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="ukuran_label">Ukuran Label</Label>
                                <Select v-model="form.ukuran_label">
                                    <SelectTrigger placeholder="Pilih ukuran" />
                                    <SelectContent>
                                        <SelectItem v-for="u in ukuranOptions" :key="u.value" :value="u.value">
                                            {{ u.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </section>

                    <div class="grid gap-2">
                        <Label htmlFor="keterangan">Keterangan</Label>
                        <Textarea id="keterangan" v-model="form.keterangan" rows="2" />
                    </div>
                </div>

                <DialogFooter class="border-t p-6 pt-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <AlertTriangle v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Perbarui Data' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
