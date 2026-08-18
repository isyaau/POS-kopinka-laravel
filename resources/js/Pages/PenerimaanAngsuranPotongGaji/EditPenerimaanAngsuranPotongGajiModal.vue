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
import { Loader2, Save, Calculator } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    item: { type: Object, default: null },
    registerTagihans: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const toDate = (v) => (v ? String(v).slice(0, 10) : '')

const form = useForm({
    tgl_transaksi: '',
    no_bukti: '',
    register_tagihan_id: '',
    no_register_tagihan: '',
    anggota_id: '',
    kode_anggota: '',
    nama_anggota: '',
    unit_kerja: '',
    jabatan: '',
    jumlah_potong: '',
    total_terbayar_sebelum: '',
    total_terbayar_sesudah: '',
    sisa_piutang: '',
    periode_gaji: '',
    keterangan: '',
})

const recalc = () => {
    const sebelum = Number(form.total_terbayar_sebelum || 0)
    const potong = Number(form.jumlah_potong || 0)
    form.total_terbayar_sesudah = sebelum + potong
}

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
        form.total_terbayar_sebelum = r.total_terbayar ?? 0
        form.jumlah_potong = r.jumlah_potong_per_bulan || 0
        form.total_terbayar_sesudah = (r.total_terbayar || 0) + (r.jumlah_potong_per_bulan || 0)
        form.sisa_piutang = Math.max(0, (r.total_harus_dibayar || 0) - form.total_terbayar_sesudah)
        form.periode_gaji = new Date().toISOString().slice(0, 7)
    }
}

watch(() => props.open, (val) => {
    if (val && props.item) {
        const b = props.item
        form.clearErrors()
        form.tgl_transaksi = toDate(b.tgl_transaksi) || new Date().toISOString().slice(0, 10)
        form.no_bukti = b.no_bukti || ''
        form.register_tagihan_id = b.register_tagihan_id ? String(b.register_tagihan_id) : ''
        form.no_register_tagihan = b.no_register_tagihan || ''
        form.anggota_id = b.anggota_id ? String(b.anggota_id) : ''
        form.kode_anggota = b.kode_anggota || ''
        form.nama_anggota = b.nama_anggota || ''
        form.unit_kerja = b.unit_kerja || ''
        form.jabatan = b.jabatan || ''
        form.jumlah_potong = b.jumlah_potong ?? ''
        form.total_terbayar_sebelum = b.total_terbayar_sebelum ?? ''
        form.total_terbayar_sesudah = b.total_terbayar_sesudah ?? ''
        form.sisa_piutang = b.sisa_piutang ?? ''
        form.periode_gaji = b.periode_gaji || ''
        form.keterangan = b.keterangan || ''
    }
})

const submit = () => {
    if (!props.item) return
    recalc()
    form.put(`/penerimaan-angsuran-potong-gaji/${props.item.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            setTimeout(() => toast.success('Data penerimaan angsuran potong gaji berhasil diperbarui.'), 50)
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
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <Save class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Edit Penerimaan Angsuran (Potong Gaji)</DialogTitle>
                            <DialogDescription>Perbarui data penerimaan angsuran piutang dagang (potong gaji).</DialogDescription>
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
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Data Transaksi</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="tgl_transaksi">Tgl Transaksi</Label>
                                <Input id="tgl_transaksi" v-model="form.tgl_transaksi" type="date" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_bukti">No. Bukti</Label>
                                <Input id="no_bukti" v-model="form.no_bukti" />
                                <p v-if="form.errors.no_bukti" class="text-destructive text-xs">{{ form.errors.no_bukti }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="periode_gaji">Periode Gaji</Label>
                                <Input id="periode_gaji" v-model="form.periode_gaji" type="month" />
                            </div>
                        </div>
                    </section>

                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Perhitungan Potong Gaji</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="total_terbayar_sebelum">Total Terbayar Sebelum (Rp)</Label>
                                <Input id="total_terbayar_sebelum" v-model="form.total_terbayar_sebelum" type="number" min="0" step="0.01" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="jumlah_potong">Jumlah Potong (Rp)</Label>
                                <Input id="jumlah_potong" v-model="form.jumlah_potong" type="number" min="0" step="0.01" @input="recalc" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="total_terbayar_sesudah">Total Terbayar Sesudah (Rp)</Label>
                                <Input id="total_terbayar_sesudah" v-model="form.total_terbayar_sesudah" type="number" min="0" step="0.01" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="sisa_piutang">Sisa Piutang (Rp)</Label>
                                <Input id="sisa_piutang" v-model="form.sisa_piutang" type="number" min="0" step="0.01" disabled />
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
                        <Calculator v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Perbarui Data' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
