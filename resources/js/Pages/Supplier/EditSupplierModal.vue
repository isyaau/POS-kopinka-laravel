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
import { Loader2, Save } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    supplier: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const form = useForm({
    kode: '',
    nama: '',
    alamat: '',
    contact_person: '',
    no_telp: '',
    keterangan: '',
})

// Isi form dari data supplier saat dialog dibuka
watch(
    () => props.open,
    (val) => {
        if (val && props.supplier) {
            const s = props.supplier
            form.clearErrors()
            form.kode = s.kode || ''
            form.nama = s.nama || ''
            form.alamat = s.alamat || ''
            form.contact_person = s.contact_person || ''
            form.no_telp = s.no_telp || ''
            form.keterangan = s.keterangan || ''
        }
    },
)

const submit = () => {
    form.put(`/suppliers/${props.supplier.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            setTimeout(() => {
                toast.success('Data supplier berhasil diperbarui.')
            }, 50)
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90svh] max-w-2xl overflow-y-auto p-0">
            <form @submit.prevent="submit" class="flex flex-col">
                <!-- Header -->
                <DialogHeader class="border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <Save class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Edit Supplier</DialogTitle>
                            <DialogDescription>Perbarui data supplier.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <!-- Body -->
                <div class="flex flex-col gap-6 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label htmlFor="kode">Kode</Label>
                            <Input
                                id="kode"
                                v-model="form.kode"
                                placeholder="Kode supplier"
                                :disabled="form.processing"
                            />
                            <p v-if="form.errors.kode" class="text-destructive text-xs">{{ form.errors.kode }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label htmlFor="nama">Nama Supplier</Label>
                            <Input id="nama" v-model="form.nama" placeholder="Nama supplier" :disabled="form.processing" />
                            <p v-if="form.errors.nama" class="text-destructive text-xs">{{ form.errors.nama }}</p>
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label htmlFor="alamat">Alamat</Label>
                            <Textarea id="alamat" v-model="form.alamat" rows="2" placeholder="Alamat lengkap" />
                            <p v-if="form.errors.alamat" class="text-destructive text-xs">{{ form.errors.alamat }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label htmlFor="contact_person">Contact Person</Label>
                            <Input id="contact_person" v-model="form.contact_person" placeholder="Nama kontak" />
                            <p v-if="form.errors.contact_person" class="text-destructive text-xs">{{ form.errors.contact_person }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label htmlFor="no_telp">No Telp/HP</Label>
                            <Input id="no_telp" v-model="form.no_telp" type="tel" placeholder="08xxxxxxxxxx" />
                            <p v-if="form.errors.no_telp" class="text-destructive text-xs">{{ form.errors.no_telp }}</p>
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label htmlFor="keterangan">Keterangan</Label>
                            <Textarea id="keterangan" v-model="form.keterangan" rows="2" placeholder="Catatan tambahan (opsional)" />
                            <p v-if="form.errors.keterangan" class="text-destructive text-xs">{{ form.errors.keterangan }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <DialogFooter class="border-t p-6 pt-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <Save v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Perbarui Data' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
