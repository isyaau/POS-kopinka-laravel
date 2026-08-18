<script setup>
import { ref, computed, watch } from 'vue'
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
import { Loader2, Plus, RotateCcw } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    registerTagihans: { type: Array, default: () => [] },
    penerimaanAngsuranPotongGajis: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const today = new Date().toISOString().slice(0, 10)

const form = useForm({
    tgl_pengembalian: today,
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

const filteredPotongGajis = computed(() => {
    if (!form.register_tagihan_id) return []
    return props.penerimaanAngsuranPotongGajis.filter(
        (pg) => String(pg.register_tagihan_id) === String(form.register_tagihan_id)
    )
})

const resetForm = () => {
    form.reset()
    form.tgl_pengembalian = today
    form.no_bukti = ''
    form.register_tagihan_id = ''
    form.no_register_tagihan = ''
    form.penerimaan_angsuran_potong_gaji_id = ''
    form.no_transaksi_potong_gaji = ''
    form.anggota_id = ''
    form.kode_anggota = ''
    form.nama_anggota = ''
    form.unit_kerja = ''
    form.jabatan = ''
    form.keterangan = ''
    form.jumlah_lebih_bayar = ''
    form.jumlah_pengembalian = ''
    form.metode_pengembalian = ''
    form.status = 'pending'
    form.clearErrors()
}

watch(() => props.open, (val) => { if (val) resetForm() })

const onRegisterTagihanChange = (val) => {
    form.register_tagihan_id = val
    form.penerimaan_angsuran_potong_gaji_id = ''
    form.no_transaksi_potong_gaji = ''
    form.jumlah_lebih_bayar = ''
    form.jumlah_pengembalian = ''
    const r = props.registerTagihans.find((x) => String(x.id) === String(val))
    if (r) {
        form.no_register_tagihan = r.no_transaksi
        form.anggota_id = r.anggota_id ? String(r.anggota_id) : ''
        form.kode_anggota = r.anggota?.nip || '-'
        form.nama_anggota = r.anggota?.nama || '-'
        form.unit_kerja = r.unit_kerja || ''
        form.jabatan = r.jabatan || ''
    }
}

const onPotongGajiChange = (val) => {
    form.penerimaan_angsuran_potong_gaji_id = val
    const pg = props.penerimaanAngsuranPotongGajis.find((x) => String(x.id) === String(val))
    if (pg) {
        form.no_transaksi_potong_gaji = pg.no_transaksi
        // Simulasi lebih bayar: jika total terbayar > total harus dibayar
        const lebih = Math.max(0, Number(pg.total_terbayar_sesudah || 0) - Number(pg.sisa_piutang || 0))
        form.jumlah_lebih_bayar = lebih || Math.round(rand(5000, 30000) / 1000) * 1000
        form.jumlah_pengembalian = form.jumlah_lebih_bayar
    }
}

const rand = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min

const submit = () => {
    form.post('/pengembalian-lebih-bayar-potong-gaji', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
            toast.success('Pengembalian lebih bayar potong gaji berhasil dicatat.')
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
                        <div class="bg-emerald-500/10 text-emerald-600 flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <Plus class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Tambah Pengembalian Lebih Bayar</DialogTitle>
                            <DialogDescription>Catat pengembalian dana ketika potong gaji melebihi sisa piutang.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="flex flex-col gap-6 p-6">
                    <!-- Register Tagihan -->
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
                                        <SelectItem v-for="r in registerTagihans" :key="r.id" :value="r.id">
                                            {{ r.no_transaksi }} - {{ r.anggota?.nama }} (Sisa: {{ r.sisa_piutang }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.register_tagihan_id" class="text-destructive text-xs">{{ form.errors.register_tagihan_id }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Data Anggota -->
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

                    <!-- Potong Gaji -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Data Potong Gaji</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label>Potong Gaji Terkait</Label>
                                <Select v-model="form.penerimaan_angsuran_potong_gaji_id" @update:model-value="onPotongGajiChange" :disabled="!form.register_tagihan_id">
                                    <SelectTrigger placeholder="Pilih potong gaji" />
                                    <SelectContent>
                                        <SelectItem v-for="pg in filteredPotongGajis" :key="pg.id" :value="String(pg.id)">
                                            {{ pg.no_transaksi }} (Potong: {{ pg.jumlah_potong }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_transaksi_potong_gaji">No. Transaksi Potong Gaji</Label>
                                <Input id="no_transaksi_potong_gaji" v-model="form.no_transaksi_potong_gaji" disabled />
                            </div>
                        </div>
                    </section>

                    <!-- Transaksi -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Data Transaksi</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="tgl_pengembalian">Tgl Pengembalian</Label>
                                <Input id="tgl_pengembalian" v-model="form.tgl_pengembalian" type="date" :disabled="form.processing" />
                                <p v-if="form.errors.tgl_pengembalian" class="text-destructive text-xs">{{ form.errors.tgl_pengembalian }}</p>
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
                        </div>
                    </section>

                    <!-- Perhitungan -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Perhitungan Pengembalian</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="jumlah_lebih_bayar">Jumlah Lebih Bayar (Rp)</Label>
                                <Input id="jumlah_lebih_bayar" v-model="form.jumlah_lebih_bayar" type="number" min="0" step="0.01" :disabled="form.processing" />
                                <p v-if="form.errors.jumlah_lebih_bayar" class="text-destructive text-xs">{{ form.errors.jumlah_lebih_bayar }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="jumlah_pengembalian">Jumlah Pengembalian (Rp)</Label>
                                <Input id="jumlah_pengembalian" v-model="form.jumlah_pengembalian" type="number" min="0" step="0.01" :disabled="form.processing" />
                                <p v-if="form.errors.jumlah_pengembalian" class="text-destructive text-xs">{{ form.errors.jumlah_pengembalian }}</p>
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

                <DialogFooter class="border-t p-6 pt-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <RotateCcw v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Pengembalian' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
