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
    registerTagihans: { type: Array, default: () => [] },
    penerimaanAngsuranPotongGajis: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const toDate = (v) => (v ? String(v).slice(0, 10) : '')

const form = useForm({
    tgl_pengembalian: '',
    no_bukti: '',
    register_tagihan_id: '',
    no_register_tagihan: '',
    penerimaan_angsuran_potong_gaji_id: '',
    no_transaksi_potong_gaji: '',
    anggota_id: '',
    kode_anggota: '',
    nama_anggota: '',
    unit_kerja: '',
    jabatan: '',
    keterangan: '',
    jumlah_lebih_bayar: '',
    jumlah_pengembalian: '',
    metode_pengembalian: '',
    status: 'pending',
})

const metodeOptions = [
    { value: 'transfer', label: 'Transfer' },
    { value: 'tunai', label: 'Tunai' },
    { value: 'potong_gaji_berikutnya', label: 'Potong Gaji Berikutnya' },
]

const statusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'diproses', label: 'Diproses' },
    { value: 'selesai', label: 'Selesai' },
    { value: 'batal', label: 'Batal' },
]

watch(() => props.open, (val) => {
    if (val && props.item) {
        const b = props.item
        form.clearErrors()
        form.tgl_pengembalian = toDate(b.tgl_pengembalian) || new Date().toISOString().slice(0, 10)
        form.no_bukti = b.no_bukti || ''
        form.register_tagihan_id = b.register_tagihan_id ? String(b.register_tagihan_id) : ''
        form.no_register_tagihan = b.no_register_tagihan || ''
        form.penerimaan_angsuran_potong_gaji_id = b.penerimaan_angsuran_potong_gaji_id ? String(b.penerimaan_angsuran_potong_gaji_id) : ''
        form.no_transaksi_potong_gaji = b.no_transaksi_potong_gaji || ''
        form.anggota_id = b.anggota_id ? String(b.anggota_id) : ''
        form.kode_anggota = b.kode_anggota || ''
        form.nama_anggota = b.nama_anggota || ''
        form.unit_kerja = b.unit_kerja || ''
        form.jabatan = b.jabatan || ''
        form.keterangan = b.keterangan || ''
        form.jumlah_lebih_bayar = b.jumlah_lebih_bayar ?? ''
        form.jumlah_pengembalian = b.jumlah_pengembalian ?? ''
        form.metode_pengembalian = b.metode_pengembalian || ''
        form.status = b.status || 'pending'
    }
})

const submit = () => {
    if (!props.item) return
    form.put(`/pengembalian-lebih-bayar-potong-gaji/${props.item.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            setTimeout(() => toast.success('Data pengembalian lebih bayar potong gaji berhasil diperbarui.'), 50)
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="flex max-h-[90svh] max-w-3xl flex-col gap-0 overflow-hidden p-0">
            <form @submit.prevent="submit" class="flex min-h-0 flex-1 flex-col">
                <DialogHeader class="shrink-0 border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-500/10 text-emerald-600 flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <Save class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Edit Pengembalian Lebih Bayar</DialogTitle>
                            <DialogDescription>Perbarui data pengembalian lebih bayar potong gaji.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto p-6">
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Register Tagihan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2 sm:col-span-2">
                                <Label>Register Tagihan</Label>
                                <Input v-model="form.no_register_tagihan" disabled />
                            </div>
                        </div>
                    </section>

                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Data Anggota / Karyawan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="no_register_tagihan">No. Register Tagihan</Label>
                                <Input id="no_register_tagihan" v-model="form.no_register_tagihan" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="kode_anggota">Kode Anggota</Label>
                                <Input id="kode_anggota" v-model="form.kode_anggota" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="nama_anggota">Nama Anggota</Label>
                                <Input id="nama_anggota" v-model="form.nama_anggota" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="unit_kerja">Unit Kerja</Label>
                                <Input id="unit_kerja" v-model="form.unit_kerja" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="jabatan">Jabatan</Label>
                                <Input id="jabatan" v-model="form.jabatan" disabled />
                            </div>
                        </div>
                    </section>

                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Data Transaksi</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="tgl_pengembalian">Tgl Pengembalian</Label>
                                <Input id="tgl_pengembalian" v-model="form.tgl_pengembalian" type="date" />
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
                        </div>
                    </section>

                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Perhitungan Pengembalian</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="jumlah_lebih_bayar">Jumlah Lebih Bayar (Rp)</Label>
                                <Input id="jumlah_lebih_bayar" v-model="form.jumlah_lebih_bayar" type="number" min="0" step="0.01" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="jumlah_pengembalian">Jumlah Pengembalian (Rp)</Label>
                                <Input id="jumlah_pengembalian" v-model="form.jumlah_pengembalian" type="number" min="0" step="0.01" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="metode_pengembalian">Metode Pengembalian</Label>
                                <Select v-model="form.metode_pengembalian">
                                    <SelectTrigger placeholder="Pilih metode" />
                                    <SelectContent>
                                        <SelectItem v-for="m in metodeOptions" :key="m.value" :value="m.value">
                                            {{ m.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </section>

                    <div class="grid gap-2 sm:col-span-3">
                        <Label htmlFor="keterangan">Keterangan</Label>
                        <Textarea id="keterangan" v-model="form.keterangan" rows="2" />
                    </div>
                </div>

                <DialogFooter class="shrink-0 border-t p-6 pt-4">
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
