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
import { Loader2, Plus, Tag } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    produks: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const today = new Date().toISOString().slice(0, 10)

const form = useForm({
    tgl_register: today,
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

const resetForm = () => {
    form.reset()
    form.tgl_register = today
    form.no_bukti = ''
    form.produk_id = ''
    form.kode_barang = ''
    form.nama_barang = ''
    form.kategori = ''
    form.satuan = ''
    form.no_rak = ''
    form.harga_jual = ''
    form.jumlah_label = ''
    form.ukuran_label = ''
    form.keterangan = ''
    form.status = 'draft'
    form.clearErrors()
}

watch(() => props.open, (val) => { if (val) resetForm() })

const onProdukChange = (val) => {
    form.produk_id = val
    const p = props.produks.find((x) => String(x.id) === String(val))
    if (p) {
        form.kode_barang = p.kode_barang || ''
        form.nama_barang = p.nama_barang || ''
        form.kategori = p.kategori || ''
        form.satuan = p.satuan || ''
        form.no_rak = p.no_rak || ''
        form.harga_jual = p.harga_jual ?? ''
        form.ukuran_label = 'sedang'
        form.jumlah_label = 1
    }
}

const submit = () => {
    form.post('/register-label-etalase-barang', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
            toast.success('Register label etalase barang berhasil dicatat.')
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
                            <Plus class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Tambah Register Label Etalase</DialogTitle>
                            <DialogDescription>Registrasi label produk untuk pajangan etalase/rak toko.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="flex flex-col gap-6 p-6">
                    <!-- Pilih Produk -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Pilih Produk</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2 sm:col-span-2">
                                <Label>Produk</Label>
                                <Select v-model="form.produk_id" @update:model-value="onProdukChange">
                                    <SelectTrigger placeholder="Pilih produk" />
                                    <SelectContent>
                                        <SelectItem v-for="p in produks" :key="p.id" :value="p.id">
                                            {{ p.kode_barang }} - {{ p.nama_barang }} ({{ p.satuan }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.produk_id" class="text-destructive text-xs">{{ form.errors.produk_id }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Data Produk Auto-fill -->
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
                                <Input id="harga_jual" v-model="form.harga_jual" type="number" min="0" step="0.01" :disabled="form.processing" />
                            </div>
                        </div>
                    </section>

                    <!-- Detail Label -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Detail Label</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="tgl_register">Tgl Register</Label>
                                <Input id="tgl_register" v-model="form.tgl_register" type="date" :disabled="form.processing" />
                                <p v-if="form.errors.tgl_register" class="text-destructive text-xs">{{ form.errors.tgl_register }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_bukti">No. Bukti</Label>
                                <Input id="no_bukti" v-model="form.no_bukti" placeholder="Kosongkan untuk otomatis" />
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
                                <Input id="jumlah_label" v-model="form.jumlah_label" type="number" min="1" :disabled="form.processing" />
                                <p v-if="form.errors.jumlah_label" class="text-destructive text-xs">{{ form.errors.jumlah_label }}</p>
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
                        <Tag v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Label' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
