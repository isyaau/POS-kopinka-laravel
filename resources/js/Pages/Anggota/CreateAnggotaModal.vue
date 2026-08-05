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
import { Loader2, UserPlus } from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
})

const emit = defineEmits(['update:open'])

const form = useForm({
    status: 'anggota',
    nip: '',
    nama: '',
    alamat: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    jenis_kelamin: '',
    pendidikan: '',
    no_hp: '',
    divisi_pekerjaan: '',
    status_purna: false,
    tgl_terdaftar: new Date().toISOString().slice(0, 10),
    tgl_pensiun: '',
    simpanan_pokok: 0,
    simpanan_wajib: 0,
    status_aktif: true,
    status_limit: false,
    limit_transaksi: '',
})

// Field limit aktif saat status_limit ON
const limitEnabled = computed(() => Boolean(form.status_limit))

const resetForm = () => {
    form.reset()
    form.status = 'anggota'
    form.status_aktif = true
    form.status_purna = false
    form.status_limit = false
    form.tgl_terdaftar = new Date().toISOString().slice(0, 10)
    form.clearErrors()
}

// Reset form saat dialog dibuka
watch(
    () => props.open,
    (val) => {
        if (val) {
            resetForm()
        }
    },
)

const submit = () => {
    form.post('/anggota', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm()
            emit('update:open', false)
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90svh] max-w-3xl overflow-y-auto p-0">
            <form @submit.prevent="submit" class="flex flex-col">
                <!-- Header -->
                <DialogHeader class="border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <UserPlus class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Tambah Anggota</DialogTitle>
                            <DialogDescription>Tambahkan data anggota atau karyawan baru.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <!-- Body -->
                <div class="flex flex-col gap-6 p-6">
                    <!-- Identitas -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Identitas</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label>Status di Kopinka</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger />
                                    <SelectContent>
                                        <SelectItem value="anggota">Anggota Koperasi</SelectItem>
                                        <SelectItem value="karyawan">Karyawan</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="nip">NIP / No. Anggota</Label>
                                <Input
                                    id="nip"
                                    v-model="form.nip"
                                    placeholder="Kosongkan untuk otomatis"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.nip" class="text-destructive text-xs">{{ form.errors.nip }}</p>
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label htmlFor="nama">Nama Lengkap</Label>
                                <Input id="nama" v-model="form.nama" placeholder="Nama lengkap" :disabled="form.processing" />
                                <p v-if="form.errors.nama" class="text-destructive text-xs">{{ form.errors.nama }}</p>
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label htmlFor="alamat">Alamat</Label>
                                <Textarea id="alamat" v-model="form.alamat" rows="2" placeholder="Alamat lengkap" />
                                <p v-if="form.errors.alamat" class="text-destructive text-xs">{{ form.errors.alamat }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Data Pribadi & Pekerjaan -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Data Pribadi & Pekerjaan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label htmlFor="tempat_lahir">Tempat Lahir</Label>
                                <Input id="tempat_lahir" v-model="form.tempat_lahir" placeholder="Tempat lahir" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="tanggal_lahir">Tanggal Lahir</Label>
                                <Input id="tanggal_lahir" v-model="form.tanggal_lahir" type="date" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Jenis Kelamin</Label>
                                <Select v-model="form.jenis_kelamin">
                                    <SelectTrigger placeholder="Pilih jenis kelamin" />
                                    <SelectContent>
                                        <SelectItem value="L">Laki-laki</SelectItem>
                                        <SelectItem value="P">Perempuan</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="pendidikan">Pendidikan</Label>
                                <Input id="pendidikan" v-model="form.pendidikan" placeholder="Pendidikan terakhir" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="no_hp">No. HP</Label>
                                <Input id="no_hp" v-model="form.no_hp" type="tel" placeholder="08xxxxxxxxxx" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="divisi">Divisi Pekerjaan</Label>
                                <Input id="divisi" v-model="form.divisi_pekerjaan" placeholder="Divisi / pekerjaan" />
                            </div>
                        </div>
                    </section>

                    <!-- Keanggotaan & Simpanan -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Keanggotaan & Simpanan</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label htmlFor="tgl_terdaftar">Tanggal Terdaftar</Label>
                                <Input id="tgl_terdaftar" v-model="form.tgl_terdaftar" type="date" required />
                                <p v-if="form.errors.tgl_terdaftar" class="text-destructive text-xs">{{ form.errors.tgl_terdaftar }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="tgl_pensiun">Tanggal Pensiun</Label>
                                <Input id="tgl_pensiun" v-model="form.tgl_pensiun" type="date" :disabled="!form.status_purna" />
                                <p v-if="form.errors.tgl_pensiun" class="text-destructive text-xs">{{ form.errors.tgl_pensiun }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="simpanan_pokok">Simpanan Pokok (Rp)</Label>
                                <Input id="simpanan_pokok" v-model="form.simpanan_pokok" type="number" min="0" step="0.01" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="simpanan_wajib">Simpanan Wajib (Rp)</Label>
                                <Input id="simpanan_wajib" v-model="form.simpanan_wajib" type="number" min="0" step="0.01" />
                            </div>
                        </div>
                    </section>

                    <!-- Status & Limit -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold tracking-wide text-foreground uppercase">Status & Limit Transaksi</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="flex items-center justify-between rounded-lg border p-3">
                                <div class="grid gap-0.5">
                                    <Label class="text-sm font-medium">Status Purna</Label>
                                    <p class="text-muted-foreground text-xs">Tandai jika sudah purna</p>
                                </div>
                                <Switch v-model="form.status_purna" />
                            </div>
                            <div class="flex items-center justify-between rounded-lg border p-3">
                                <div class="grid gap-0.5">
                                    <Label class="text-sm font-medium">Status Aktif</Label>
                                    <p class="text-muted-foreground text-xs">Anggota aktif / tidak aktif</p>
                                </div>
                                <Switch v-model="form.status_aktif" />
                            </div>
                            <div class="flex items-center justify-between rounded-lg border p-3">
                                <div class="grid gap-0.5">
                                    <Label class="text-sm font-medium">Status Limit Transaksi</Label>
                                    <p class="text-muted-foreground text-xs">Aktifkan batas nominal transaksi</p>
                                </div>
                                <Switch v-model="form.status_limit" />
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="limit_transaksi">Limit Transaksi (Rp)</Label>
                                <Input
                                    id="limit_transaksi"
                                    v-model="form.limit_transaksi"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="!limitEnabled"
                                    placeholder="Batas nominal transaksi"
                                />
                                <p v-if="form.errors.limit_transaksi" class="text-destructive text-xs">{{ form.errors.limit_transaksi }}</p>
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
                        <UserPlus v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Anggota' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
