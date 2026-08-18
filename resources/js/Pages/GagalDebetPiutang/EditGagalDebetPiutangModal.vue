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
})

const emit = defineEmits(['update:open'])

const toDate = (v) => (v ? String(v).slice(0, 10) : '')

const form = useForm({
    tgl_gagal: '',
    no_bukti: '',
    register_tagihan_id: '',
    no_register_tagihan: '',
    anggota_id: '',
    kode_anggota: '',
    nama_anggota: '',
    unit_kerja: '',
    jabatan: '',
    keterangan: '',
    jumlah_gagal_debet: '',
    alasan_gagal: '',
    status: 'pending',
    tgl_followup: '',
    catatan_followup: '',
})

const alasanOptions = [
    'Saldo tidak cukup',
    'Rekening tutup',
    'Nomor rekening salah',
    'Batas transaksi harian terlampaui',
    'Sistem bank offline',
    'Data nasabah tidak cocok',
    'Rekening diblokir',
    'Kartu ATM rusak/expired',
]

const statusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'diproses', label: 'Diproses' },
    { value: 'selesai', label: 'Selesai' },
    { value: 'batal', label: 'Batal' },
]

const onRegisterTagihanChange = (val) => {
    form.register_tagihan_id = val
    const r = props.registerTagihans.find((x) => String(x.id) === String(val))
    if (r) {
        form.no_register_tagihan = r.no_transaksi
        form.anggota_id = r.anggota_id ? String(r.anggota_id) : ''
        form.kode_anggota = r.anggota?.nip || '-'
        form.nama_anggota = r.anggota?.nama || '-'
        form.unit_kerja = r.unit_kerja || ''
        form.jabatan = r.jabatan || ''
        form.jumlah_gagal_debet = r.sisa_piutang || 0
    }
}

watch(() => props.open, (val) => {
    if (val && props.item) {
        const b = props.item
        form.clearErrors()
        form.tgl_gagal = toDate(b.tgl_gagal) || new Date().toISOString().slice(0, 10)
        form.no_bukti = b.no_bukti || ''
        form.register_tagihan_id = b.register_tagihan_id ? String(b.register_tagihan_id) : ''
        form.no_register_tagihan = b.no_register_tagihan || ''
        form.anggota_id = b.anggota_id ? String(b.anggota_id) : ''
        form.kode_anggota = b.kode_anggota || ''
        form.nama_anggota = b.nama_anggota || ''
        form.unit_kerja = b.unit_kerja || ''
        form.jabatan = b.jabatan || ''
        form.keterangan = b.keterangan || ''
        form.jumlah_gagal_debet = b.jumlah_gagal_debet ?? ''
        form.alasan_gagal = b.alasan_gagal || ''
        form.status = b.status || 'pending'
        form.tgl_followup = toDate(b.tgl_followup) || ''
        form.catatan_followup = b.catatan_followup || ''
    }
})

const submit = () => {
    if (!props.item) return
    form.put(`/gagal-debet-piutang/${props.item.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            setTimeout(() => toast.success('Data gagal debet tagihan piutang berhasil diperbarui.'), 50)
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
                        <div class="bg-destructive/10 text-destructive flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <Save class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Edit Gagal Debet Tagihan Piutang</DialogTitle>
                            <DialogDescription>Perbarui data gagal debet tagihan piutang dagang.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto p-6">
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Pilih Register Tagihan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2 sm:col-span-2">
                                <Label>Register Tagihan</Label>
                                <Select v-model="form.register_tagihan_id" @update:model-value="onRegisterTagihanChange">
                                    <SelectTrigger placeholder="Pilih register tagihan" />
                                    <SelectContent>
                                        <SelectItem v-for="r in registerTagihans" :key="r.id" :value="String(r.id)">
                                            {{ r.no_transaksi }} - {{ r.anggota?.nama }} (Sisa: {{ r.sisa_piutang }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
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
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Data Transaksi Gagal</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="tgl_gagal">Tgl Gagal</Label>
                                <Input id="tgl_gagal" v-model="form.tgl_gagal" type="date" />
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
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Detail Gagal Debet</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="alasan_gagal">Alasan Gagal</Label>
                                <Select v-model="form.alasan_gagal">
                                    <SelectTrigger placeholder="Pilih alasan" />
                                    <SelectContent>
                                        <SelectItem v-for="a in alasanOptions" :key="a" :value="a">
                                            {{ a }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="jumlah_gagal_debet">Jumlah Gagal Debet (Rp)</Label>
                                <Input id="jumlah_gagal_debet" v-model="form.jumlah_gagal_debet" type="number" min="0" step="0.01" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="tgl_followup">Tgl Follow Up</Label>
                                <Input id="tgl_followup" v-model="form.tgl_followup" type="date" />
                            </div>
                        </div>
                    </section>

                    <div class="grid gap-2 sm:col-span-3">
                        <Label htmlFor="keterangan">Keterangan</Label>
                        <Textarea id="keterangan" v-model="form.keterangan" rows="2" />
                    </div>

                    <div class="grid gap-2 sm:col-span-3">
                        <Label htmlFor="catatan_followup">Catatan Follow Up</Label>
                        <Textarea id="catatan_followup" v-model="form.catatan_followup" rows="2" />
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
