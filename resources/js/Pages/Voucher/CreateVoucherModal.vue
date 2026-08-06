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
import { Loader2, Ticket, Barcode } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
})

const emit = defineEmits(['update:open'])

const form = useForm({
    kode: '',
    nama: '',
    nominal: '',
    barcode: '',
    status: 'aktif',
    tanggal_expired: '',
    keterangan: '',
})

const resetForm = () => {
    form.reset()
    form.kode = ''
    form.nama = ''
    form.nominal = ''
    form.barcode = ''
    form.status = 'aktif'
    form.tanggal_expired = ''
    form.keterangan = ''
    form.clearErrors()
}

watch(
    () => props.open,
    (val) => {
        if (val) resetForm()
    },
)

const submit = () => {
    form.post('/voucher', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
            toast.success('Voucher berhasil disimpan.')
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90svh] max-w-lg overflow-y-auto p-0">
            <form @submit.prevent="submit" class="flex flex-col">
                <!-- Header -->
                <DialogHeader class="border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <Ticket class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Tambah Voucher</DialogTitle>
                            <DialogDescription>Buat kupon dengan barcode & nominal.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <!-- Body -->
                <div class="flex flex-col gap-4 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label htmlFor="kode">Kode Voucher</Label>
                            <Input id="kode" v-model="form.kode" placeholder="Kosong = otomatis (VCH-...)" />
                            <p v-if="form.errors.kode" class="text-destructive text-xs">{{ form.errors.kode }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label htmlFor="nominal">Nominal (Rp)</Label>
                            <Input id="nominal" v-model="form.nominal" type="number" min="0" step="100" placeholder="10000" />
                            <p v-if="form.errors.nominal" class="text-destructive text-xs">{{ form.errors.nominal }}</p>
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label htmlFor="nama">Nama Kupon</Label>
                            <Input id="nama" v-model="form.nama" placeholder="Kupon 10.000" />
                            <p v-if="form.errors.nama" class="text-destructive text-xs">{{ form.errors.nama }}</p>
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label htmlFor="barcode" class="flex items-center gap-1.5">
                                <Barcode class="size-3.5" />
                                Barcode
                            </Label>
                            <Input id="barcode" v-model="form.barcode" placeholder="Kosong = otomatis (13 digit)" class="font-mono" />
                            <p v-if="form.errors.barcode" class="text-destructive text-xs">{{ form.errors.barcode }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-10 w-full items-center justify-between rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                            >
                                <option value="aktif">Aktif</option>
                                <option value="terpakai">Terpakai</option>
                                <option value="kedaluwarsa">Kedaluwarsa</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label htmlFor="tanggal_expired">Tanggal Expired</Label>
                            <Input id="tanggal_expired" v-model="form.tanggal_expired" type="date" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label htmlFor="keterangan">Keterangan</Label>
                            <Input id="keterangan" v-model="form.keterangan" placeholder="Opsional" />
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <DialogFooter class="border-t p-6 pt-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <Ticket v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Voucher' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
