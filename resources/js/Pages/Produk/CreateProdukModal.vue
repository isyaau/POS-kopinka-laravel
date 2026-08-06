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
import { Loader2, Package } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
})

const emit = defineEmits(['update:open'])

const form = useForm({
    kode_barang: '',
    nama_barang: '',
    kategori: '',
    satuan: '',
    no_rak: '',
    harga_beli: '',
    harga_jual: '',
    diskon: '',
    stok: 0,
    stok_minimum: 0,
    tanggal_expired: '',
    ppn: 0,
})

const resetForm = () => {
    form.reset()
    form.kode_barang = ''
    form.stok = 0
    form.stok_minimum = 0
    form.ppn = 0
    form.clearErrors()
}

// Reset form saat dialog dibuka
watch(
    () => props.open,
    (val) => {
        if (val) {
            resetForm()
        }
    },
)

const submit = () => {
    form.post('/produk', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
            toast.success('Produk berhasil ditambahkan.')
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90svh] max-w-3xl overflow-y-auto p-0">
            <form @submit.prevent="submit" class="flex flex-col">
                <!-- Header -->
                <DialogHeader class="border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <Package class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Tambah Produk</DialogTitle>
                            <DialogDescription>Tambahkan data barang persediaan baru.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <!-- Body -->
                <div class="flex flex-col gap-6 p-6">
                    <!-- Identitas Barang -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Identitas Barang</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label>ID Barang</Label>
                                <div class="bg-muted/50 text-muted-foreground rounded-md border px-3 py-2 text-sm">
                                    Otomatis oleh sistem
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="kode_barang">Kode Barang</Label>
                                <Input
                                    id="kode_barang"
                                    v-model="form.kode_barang"
                                    placeholder="Kosongkan untuk otomatis (BRK-0001)"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.kode_barang" class="text-destructive text-xs">{{ form.errors.kode_barang }}</p>
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label htmlFor="nama_barang">Nama Barang</Label>
                                <Input id="nama_barang" v-model="form.nama_barang" placeholder="Nama barang" :disabled="form.processing" />
                                <p v-if="form.errors.nama_barang" class="text-destructive text-xs">{{ form.errors.nama_barang }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="kategori">Kategori</Label>
                                <Input id="kategori" v-model="form.kategori" placeholder="Kategori barang" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="satuan">Satuan</Label>
                                <Input id="satuan" v-model="form.satuan" placeholder="pcs, kg, ltr, dll" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label htmlFor="no_rak">No Rak</Label>
                                <Input id="no_rak" v-model="form.no_rak" placeholder="Lokasi rak / gudang" />
                            </div>
                        </div>
                    </section>

                    <!-- Harga & Diskon -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Harga & Diskon</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="harga_beli">Harga Beli (Rp)</Label>
                                <Input id="harga_beli" v-model="form.harga_beli" type="number" min="0" step="0.01" placeholder="0" />
                                <p v-if="form.errors.harga_beli" class="text-destructive text-xs">{{ form.errors.harga_beli }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="harga_jual">Harga Jual (Rp)</Label>
                                <Input id="harga_jual" v-model="form.harga_jual" type="number" min="0" step="0.01" placeholder="0" />
                                <p v-if="form.errors.harga_jual" class="text-destructive text-xs">{{ form.errors.harga_jual }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="diskon">Diskon (Rp)</Label>
                                <Input id="diskon" v-model="form.diskon" type="number" min="0" step="0.01" placeholder="0" />
                                <p v-if="form.errors.diskon" class="text-destructive text-xs">{{ form.errors.diskon }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Stok & Lainnya -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide uppercase">Stok & Lainnya</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label htmlFor="stok">Stok</Label>
                                <Input id="stok" v-model="form.stok" type="number" min="0" step="1" placeholder="0" />
                                <p v-if="form.errors.stok" class="text-destructive text-xs">{{ form.errors.stok }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="stok_minimum">Stok Minimum</Label>
                                <Input id="stok_minimum" v-model="form.stok_minimum" type="number" min="0" step="1" placeholder="0" />
                                <p v-if="form.errors.stok_minimum" class="text-destructive text-xs">{{ form.errors.stok_minimum }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="tanggal_expired">Tanggal Expired</Label>
                                <Input id="tanggal_expired" v-model="form.tanggal_expired" type="date" />
                                <p v-if="form.errors.tanggal_expired" class="text-destructive text-xs">{{ form.errors.tanggal_expired }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="ppn">PPN (%)</Label>
                                <Input id="ppn" v-model="form.ppn" type="number" min="0" max="100" step="0.01" placeholder="0" />
                                <p v-if="form.errors.ppn" class="text-destructive text-xs">{{ form.errors.ppn }}</p>
                            </div>
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
                        <Package v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Produk' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
