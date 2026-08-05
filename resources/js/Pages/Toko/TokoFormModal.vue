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
import { Switch } from '@/components/ui/switch'
import { Select, SelectTrigger, SelectContent, SelectItem } from '@/components/ui/select'
import { Loader2, Building2, X } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    store: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const isEdit = computed(() => Boolean(props.store))

const form = useForm({
    kode: '',
    nama: '',
    tipe: 'toko',
    alamat: '',
    telepon: '',
    is_active: true,
})

watch(
    () => props.open,
    (val) => {
        if (!val) return
        const s = props.store
        form.clearErrors()
        form.kode = s?.kode || ''
        form.nama = s?.nama || ''
        form.tipe = s?.tipe || 'toko'
        form.alamat = s?.alamat || ''
        form.telepon = s?.telepon || ''
        form.is_active = s?.is_active ?? true
    },
)

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            setTimeout(() => {
                toast.success(isEdit.value ? 'Data toko berhasil diperbarui.' : 'Toko berhasil ditambahkan.')
            }, 50)
        },
    }

    if (isEdit.value) {
        form.put(`/toko/${props.store.id}`, options)
    } else {
        form.post('/toko', options)
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            :show-close="false"
            class="flex max-h-[90svh] max-w-2xl flex-col gap-0 overflow-hidden p-0"
        >
            <!-- Header (sticky) -->
            <DialogHeader class="flex shrink-0 flex-row items-center justify-between gap-3 border-b p-5">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <Building2 class="size-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg">
                            {{ isEdit ? 'Edit Toko' : 'Tambah Toko' }}
                        </DialogTitle>
                        <DialogDescription class="text-muted-foreground text-sm">
                            {{ isEdit ? 'Perbarui data toko.' : 'Lengkapi data toko baru.' }}
                        </DialogDescription>
                    </div>
                </div>
                <Button variant="ghost" size="icon" class="size-8 shrink-0" @click="emit('update:open', false)">
                    <X class="size-4" />
                    <span class="sr-only">Tutup</span>
                </Button>
            </DialogHeader>

            <!-- Body (scrollable) -->
            <form @submit.prevent="submit" class="flex min-h-0 flex-1 flex-col">
                <div class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto p-6">
                    <!-- Identitas -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-foreground text-sm font-semibold tracking-wide uppercase">Identitas</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label htmlFor="kode">Kode</Label>
                                <Input
                                    id="kode"
                                    v-model="form.kode"
                                    placeholder="cth: K6"
                                    :disabled="form.processing"
                                    class="uppercase"
                                />
                                <p v-if="form.errors.kode" class="text-destructive text-xs">{{ form.errors.kode }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="nama">Nama Toko</Label>
                                <Input id="nama" v-model="form.nama" placeholder="Nama toko" :disabled="form.processing" />
                                <p v-if="form.errors.nama" class="text-destructive text-xs">{{ form.errors.nama }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label>Jenis</Label>
                                <Select v-model="form.tipe">
                                    <SelectTrigger />
                                    <SelectContent>
                                        <SelectItem value="toko">Toko</SelectItem>
                                        <SelectItem value="pusat">Kantor Pusat</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.tipe" class="text-destructive text-xs">{{ form.errors.tipe }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="telepon">Telepon</Label>
                                <Input id="telepon" v-model="form.telepon" type="tel" placeholder="Nomor telepon" />
                                <p v-if="form.errors.telepon" class="text-destructive text-xs">{{ form.errors.telepon }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Lokasi -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-foreground text-sm font-semibold tracking-wide uppercase">Lokasi</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <Label htmlFor="alamat">Alamat</Label>
                                <Textarea id="alamat" v-model="form.alamat" rows="2" placeholder="Alamat lengkap toko" />
                                <p v-if="form.errors.alamat" class="text-destructive text-xs">{{ form.errors.alamat }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Status -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-foreground text-sm font-semibold tracking-wide uppercase">Status</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="flex items-center justify-between rounded-lg border p-3">
                            <div class="grid gap-0.5">
                                <Label class="text-sm font-medium">Toko Aktif</Label>
                                <p class="text-muted-foreground text-xs">Toko aktif dapat digunakan untuk transaksi</p>
                            </div>
                            <Switch v-model="form.is_active" />
                        </div>
                    </section>
                </div>

                <!-- Footer (sticky) -->
                <DialogFooter class="flex shrink-0 items-center justify-end gap-2 border-t bg-muted/20 px-6 py-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <Building2 v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Simpan Toko' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
