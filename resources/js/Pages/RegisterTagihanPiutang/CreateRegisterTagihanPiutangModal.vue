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
import { Loader2, Plus, Calculator } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    anggotas: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const today = new Date().toISOString().slice(0, 10)

const form = useForm({
    tgl_tagihan: today,
    no_faktur: '',
    anggota_id: '',
    kode_anggota: '',
    nama_anggota: '',
    alamat: '',
    no_telp: '',
    contact_person: '',
    unit_kerja: '',
    jabatan: '',
    no_bukti: '',
    nilai_piutang: '',
    retur_penjualan: '0',
    diskon_pembayaran: '0',
    total_harus_dibayar: '',
    total_terbayar: '0',
    total_diskon: '0',
    sisa_piutang: '',
    periode_potong: '',
    jumlah_potong_per_bulan: '',
    keterangan: '',
})

const resetForm = () => {
    form.reset()
    form.tgl_tagihan = today
    form.no_faktur = ''
    form.anggota_id = ''
    form.kode_anggota = ''
    form.nama_anggota = ''
    form.alamat = ''
    form.no_telp = ''
    form.contact_person = ''
    form.unit_kerja = ''
    form.jabatan = ''
    form.no_bukti = ''
    form.nilai_piutang = ''
    form.retur_penjualan = '0'
    form.diskon_pembayaran = '0'
    form.total_harus_dibayar = ''
    form.total_terbayar = '0'
    form.total_diskon = '0'
    form.sisa_piutang = ''
    form.periode_potong = ''
    form.jumlah_potong_per_bulan = ''
    form.keterangan = ''
    form.clearErrors()
}

watch(() => props.open, (val) => { if (val) resetForm() })

const onAnggotaChange = (val) => {
    form.anggota_id = val
    const a = props.anggotas.find((x) => String(x.id) === String(val))
    if (a) {
        form.kode_anggota = a.nip
        form.nama_anggota = a.nama
    }
}

const recalc = () => {
    const nilai = Number(form.nilai_piutang || 0)
    const retur = Number(form.retur_penjualan || 0)
    const diskon = Number(form.diskon_pembayaran || 0)
    const totalHarus = Math.max(0, nilai - retur - diskon)
    form.total_harus_dibayar = totalHarus
    form.total_diskon = diskon
    form.sisa_piutang = totalHarus
}

const submit = () => {
    recalc()
    form.post('/register-tagihan-piutang', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
            toast.success('Register tagihan piutang dagang (potong gaji) berhasil dicatat.')
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
                            <DialogTitle class="text-xl">Tambah Register Tagihan Piutang</DialogTitle>
                            <DialogDescription>Catat register tagihan piutang dagang (potong gaji) anggota/karyawan.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="flex flex-col gap-6 p-6">
                    <!-- Anggota / Karyawan -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Data Anggota / Karyawan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2 sm:col-span-2">
                                <Label>Anggota / Karyawan</Label>
                                <Select v-model="form.anggota_id" @update:model-value="onAnggotaChange">
                                    <SelectTrigger placeholder="Pilih anggota" />
                                    <SelectContent>
                                        <SelectItem v-for="a in anggotas" :key="a.id" :value="a.id">{{ a.nama }} ({{ a.nip }})</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.anggota_id" class="text-destructive text-xs">{{ form.errors.anggota_id }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="kode_anggota">Kode Anggota</Label>
                                <Input id="kode_anggota" v-model="form.kode_anggota" placeholder="Auto / manual" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="nama_anggota">Nama Anggota</Label>
                                <Input id="nama_anggota" v-model="form.nama_anggota" placeholder="Nama anggota" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label htmlFor="alamat">Alamat</Label>
                                <Textarea id="alamat" v-model="form.alamat" rows="2" placeholder="Alamat" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_telp">No. Telp</Label>
                                <Input id="no_telp" v-model="form.no_telp" placeholder="08..." />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="contact_person">Contact Person</Label>
                                <Input id="contact_person" v-model="form.contact_person" placeholder="Nama CP" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="unit_kerja">Unit Kerja</Label>
                                <Input id="unit_kerja" v-model="form.unit_kerja" placeholder="Contoh: Kopinka 1, Bagian Keuangan" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="jabatan">Jabatan</Label>
                                <Input id="jabatan" v-model="form.jabatan" placeholder="Contoh: Karyawan, Staff, Supervisor" />
                            </div>
                        </div>
                    </section>

                    <!-- Transaksi -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Data Tagihan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="tgl_tagihan">Tgl Tagihan</Label>
                                <Input id="tgl_tagihan" v-model="form.tgl_tagihan" type="date" @change="recalc" :disabled="form.processing" />
                                <p v-if="form.errors.tgl_tagihan" class="text-destructive text-xs">{{ form.errors.tgl_tagihan }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_faktur">No. Faktur</Label>
                                <Input id="no_faktur" v-model="form.no_faktur" placeholder="FAK-..." />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_bukti">No. Bukti</Label>
                                <Input id="no_bukti" v-model="form.no_bukti" placeholder="Kosongkan untuk otomatis" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="nilai_piutang">Nilai Piutang (Rp)</Label>
                                <Input id="nilai_piutang" v-model="form.nilai_piutang" type="number" min="0" step="0.01" @input="recalc" :disabled="form.processing" />
                                <p v-if="form.errors.nilai_piutang" class="text-destructive text-xs">{{ form.errors.nilai_piutang }}</p>
                            </div>
                            <div class="grid gap-2 sm:col-span-3">
                                <Label htmlFor="keterangan">Keterangan</Label>
                                <Textarea id="keterangan" v-model="form.keterangan" rows="2" />
                            </div>
                        </div>
                    </section>

                    <!-- Perhitungan -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Perhitungan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="retur_penjualan">Retur Penjualan (Rp)</Label>
                                <Input id="retur_penjualan" v-model="form.retur_penjualan" type="number" min="0" step="0.01" @input="recalc" :disabled="form.processing" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="diskon_pembayaran">Diskon Pembayaran (Rp)</Label>
                                <Input id="diskon_pembayaran" v-model="form.diskon_pembayaran" type="number" min="0" step="0.01" @input="recalc" :disabled="form.processing" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="total_harus_dibayar">Total Harus Dibayar (Rp)</Label>
                                <Input id="total_harus_dibayar" v-model="form.total_harus_dibayar" type="number" min="0" step="0.01" :disabled="form.processing" />
                                <p v-if="form.errors.total_harus_dibayar" class="text-destructive text-xs">{{ form.errors.total_harus_dibayar }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="sisa_piutang">Sisa Piutang (Rp)</Label>
                                <Input id="sisa_piutang" v-model="form.sisa_piutang" type="number" min="0" step="0.01" disabled />
                            </div>
                        </div>
                    </section>

                    <!-- Potong Gaji -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Skema Potong Gaji</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label htmlFor="periode_potong">Periode Potong</Label>
                                <Input id="periode_potong" v-model="form.periode_potong" placeholder="Contoh: 2024-01 - 2025-12" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="jumlah_potong_per_bulan">Jumlah Potong per Bulan (Rp)</Label>
                                <Input id="jumlah_potong_per_bulan" v-model="form.jumlah_potong_per_bulan" type="number" min="0" step="0.01" />
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
                        <Calculator v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Register' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
