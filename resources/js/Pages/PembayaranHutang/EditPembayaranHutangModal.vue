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
    suppliers: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const toDate = (v) => (v ? String(v).slice(0, 10) : '')

const form = useForm({
    tgl_pembelian: '',
    tgl_jatuh_tempo: '',
    no_faktur: '',
    supplier_id: '',
    kode_supplier: '',
    nama_supplier: '',
    alamat: '',
    no_telp: '',
    contact_person: '',
    no_bukti: '',
    tanggal_bayar: '',
    nilai_pembelian: '',
    retur_pembelian: '0',
    diskon_pembayaran: '0',
    total_harus_dibayar: '',
    jumlah_bayar: '',
    total_terbayar: '0',
    total_diskon: '0',
    kurang_bayar: '',
    sisa_hutang: '',
})

const recalc = () => {
    const nilai = Number(form.nilai_pembelian || 0)
    const retur = Number(form.retur_pembelian || 0)
    const diskon = Number(form.diskon_pembayaran || 0)
    const totalHarus = Math.max(0, nilai - retur - diskon)
    form.total_harus_dibayar = totalHarus
    form.total_diskon = diskon

    const jumlahBayar = Number(form.jumlah_bayar || 0)
    const totalTerbayar = Math.min(totalHarus, Number(form.total_terbayar || 0) + jumlahBayar)
    form.total_terbayar = totalTerbayar
    const kurang = Math.max(0, totalHarus - jumlahBayar)
    form.kurang_bayar = kurang
    form.sisa_hutang = kurang
}

const onSupplierChange = (val) => {
    form.supplier_id = val
    const s = props.suppliers.find((x) => String(x.id) === String(val))
    if (s) {
        form.kode_supplier = s.kode
        form.nama_supplier = s.nama
    }
}

watch(() => props.open, (val) => {
    if (val && props.item) {
        const b = props.item
        form.clearErrors()
        form.tgl_pembelian = toDate(b.tgl_pembelian) || new Date().toISOString().slice(0, 10)
        form.tgl_jatuh_tempo = toDate(b.tgl_jatuh_tempo)
        form.no_faktur = b.no_faktur || ''
        form.supplier_id = b.supplier_id ? String(b.supplier_id) : ''
        form.kode_supplier = b.kode_supplier || ''
        form.nama_supplier = b.nama_supplier || ''
        form.alamat = b.alamat || ''
        form.no_telp = b.no_telp || ''
        form.contact_person = b.contact_person || ''
        form.no_bukti = b.no_bukti || ''
        form.tanggal_bayar = toDate(b.tanggal_bayar) || new Date().toISOString().slice(0, 10)
        form.nilai_pembelian = b.nilai_pembelian ?? ''
        form.retur_pembelian = b.retur_pembelian ?? '0'
        form.diskon_pembayaran = b.diskon_pembayaran ?? '0'
        form.total_harus_dibayar = b.total_harus_dibayar ?? ''
        form.jumlah_bayar = b.jumlah_bayar ?? ''
        form.total_terbayar = b.total_terbayar ?? '0'
        form.total_diskon = b.total_diskon ?? '0'
        form.kurang_bayar = b.kurang_bayar ?? ''
        form.sisa_hutang = b.sisa_hutang ?? ''
    }
})

const submit = () => {
    if (!props.item) return
    recalc()
    form.put(`/pembayaran-hutang/${props.item.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            setTimeout(() => toast.success('Data pembayaran hutang berhasil diperbarui.'), 50)
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
                            <DialogTitle class="text-xl">Edit Pembayaran Hutang</DialogTitle>
                            <DialogDescription>Perbarui data pembayaran hutang supplier.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto p-6">
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Data Supplier</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2 sm:col-span-2">
                                <Label>Supplier</Label>
                                <Select v-model="form.supplier_id" @update:model-value="onSupplierChange">
                                    <SelectTrigger placeholder="Pilih supplier" />
                                    <SelectContent>
                                        <SelectItem v-for="s in suppliers" :key="s.id" :value="String(s.id)">{{ s.nama }} ({{ s.kode }})</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="kode_supplier">Kode Supplier</Label>
                                <Input id="kode_supplier" v-model="form.kode_supplier" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="nama_supplier">Nama Supplier</Label>
                                <Input id="nama_supplier" v-model="form.nama_supplier" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label htmlFor="alamat">Alamat</Label>
                                <Textarea id="alamat" v-model="form.alamat" rows="2" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_telp">No. Telp</Label>
                                <Input id="no_telp" v-model="form.no_telp" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="contact_person">Contact Person</Label>
                                <Input id="contact_person" v-model="form.contact_person" />
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
                                <Label htmlFor="tgl_pembelian">Tgl Pembelian</Label>
                                <Input id="tgl_pembelian" v-model="form.tgl_pembelian" type="date" @change="recalc" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="tgl_jatuh_tempo">Tgl Jatuh Tempo</Label>
                                <Input id="tgl_jatuh_tempo" v-model="form.tgl_jatuh_tempo" type="date" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="tanggal_bayar">Tanggal Bayar</Label>
                                <Input id="tanggal_bayar" v-model="form.tanggal_bayar" type="date" @change="recalc" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_faktur">No. Faktur</Label>
                                <Input id="no_faktur" v-model="form.no_faktur" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_bukti">No. Bukti</Label>
                                <Input id="no_bukti" v-model="form.no_bukti" />
                                <p v-if="form.errors.no_bukti" class="text-destructive text-xs">{{ form.errors.no_bukti }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="nilai_pembelian">Nilai Pembelian (Rp)</Label>
                                <Input id="nilai_pembelian" v-model="form.nilai_pembelian" type="number" min="0" step="0.01" @input="recalc" />
                                <p v-if="form.errors.nilai_pembelian" class="text-destructive text-xs">{{ form.errors.nilai_pembelian }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Perhitungan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label htmlFor="retur_pembelian">Retur Pembelian (Rp)</Label>
                                <Input id="retur_pembelian" v-model="form.retur_pembelian" type="number" min="0" step="0.01" @input="recalc" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="diskon_pembayaran">Diskon Pembayaran (Rp)</Label>
                                <Input id="diskon_pembayaran" v-model="form.diskon_pembayaran" type="number" min="0" step="0.01" @input="recalc" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="total_harus_dibayar">Total Harus Dibayar (Rp)</Label>
                                <Input id="total_harus_dibayar" v-model="form.total_harus_dibayar" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.total_harus_dibayar" class="text-destructive text-xs">{{ form.errors.total_harus_dibayar }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="jumlah_bayar">Jumlah Bayar (Rp)</Label>
                                <Input id="jumlah_bayar" v-model="form.jumlah_bayar" type="number" min="0" step="0.01" @input="recalc" />
                                <p v-if="form.errors.jumlah_bayar" class="text-destructive text-xs">{{ form.errors.jumlah_bayar }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="kurang_bayar">Kurang Bayar (Rp)</Label>
                                <Input id="kurang_bayar" v-model="form.kurang_bayar" type="number" min="0" step="0.01" disabled />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="sisa_hutang">Sisa Hutang (Rp)</Label>
                                <Input id="sisa_hutang" v-model="form.sisa_hutang" type="number" min="0" step="0.01" disabled />
                            </div>
                        </div>
                    </section>
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
