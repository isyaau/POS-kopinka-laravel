<script setup>
import { computed, watch } from 'vue'
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
import { Loader2, Plus } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    kategoriList: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const today = new Date().toISOString().slice(0, 10)

const form = useForm({
    tanggal: today,
    no_bukti: '',
    dari_unit: '',
    kategori: '',
    keterangan: '',
    jumlah: '',
})

const resetForm = () => {
    form.reset()
    form.tanggal = today
    form.no_bukti = ''
    form.dari_unit = ''
    form.kategori = ''
    form.keterangan = ''
    form.jumlah = ''
    form.clearErrors()
}

watch(
    () => props.open,
    (val) => {
        if (val) {
            resetForm()
        }
    },
)

const submit = () => {
    form.post('/biaya-operasional', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
            toast.success('Biaya operasional berhasil ditambahkan.')
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
                            <Plus class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Tambah Biaya Operasional</DialogTitle>
                            <DialogDescription>Catat pengeluaran operasional koperasi.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <!-- Body -->
                <div class="flex flex-col gap-6 p-6">
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Informasi Biaya</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label htmlFor="tanggal">Tanggal</Label>
                                <Input id="tanggal" v-model="form.tanggal" type="date" required :disabled="form.processing" />
                                <p v-if="form.errors.tanggal" class="text-destructive text-xs">{{ form.errors.tanggal }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_bukti">No. Bukti</Label>
                                <Input
                                    id="no_bukti"
                                    v-model="form.no_bukti"
                                    placeholder="Kosongkan untuk otomatis (OP-...)"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.no_bukti" class="text-destructive text-xs">{{ form.errors.no_bukti }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="dari_unit">Dari Unit</Label>
                                <Input id="dari_unit" v-model="form.dari_unit" placeholder="Mis. Kopinka Pusat" :disabled="form.processing" />
                                <p v-if="form.errors.dari_unit" class="text-destructive text-xs">{{ form.errors.dari_unit }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label>Kategori</Label>
                                <Select v-model="form.kategori">
                                    <SelectTrigger placeholder="Pilih kategori" />
                                    <SelectContent>
                                        <SelectItem v-for="kat in kategoriList" :key="kat" :value="kat">{{ kat }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.kategori" class="text-destructive text-xs">{{ form.errors.kategori }}</p>
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label htmlFor="jumlah">Jumlah (Rp)</Label>
                                <Input id="jumlah" v-model="form.jumlah" type="number" min="0" step="0.01" placeholder="0" :disabled="form.processing" />
                                <p v-if="form.errors.jumlah" class="text-destructive text-xs">{{ form.errors.jumlah }}</p>
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label htmlFor="keterangan">Keterangan</Label>
                                <Textarea id="keterangan" v-model="form.keterangan" rows="3" placeholder="Catatan tambahan" />
                                <p v-if="form.errors.keterangan" class="text-destructive text-xs">{{ form.errors.keterangan }}</p>
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
                        <Plus v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Biaya' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
