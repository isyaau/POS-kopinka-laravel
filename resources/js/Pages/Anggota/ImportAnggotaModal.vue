<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogFooter,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { toast } from '@/components/ui/sonner'
import { FileSpreadsheet, UploadCloud, Download, Loader2, X, ChevronDown, HelpCircle } from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
})

const emit = defineEmits(['update:open'])

const file = ref(null)
const fileError = ref('')
const importing = ref(false)
const showGuide = ref(false)

// Reset saat dialog dibuka
watch(
    () => props.open,
    (val) => {
        if (val) {
            file.value = null
            fileError.value = ''
        }
    },
)

const onFileChange = (e) => {
    const f = e.target.files?.[0]
    file.value = f || null
    fileError.value = ''

    if (f) {
        const ext = f.name.split('.').pop().toLowerCase()
        if (!['xlsx', 'xls', 'csv'].includes(ext)) {
            fileError.value = 'Format file harus .xlsx, .xls, atau .csv'
        } else if (f.size > 2 * 1024 * 1024) {
            fileError.value = 'Ukuran file maksimal 2MB'
        }
    }
}

const removeFile = () => {
    file.value = null
    fileError.value = ''
}

const downloadTemplate = () => {
    // Unduh template kosong (export dengan filter kosong)
    window.location.href = '/anggota/export?template=1'
}

const submit = () => {
    if (!file.value) {
        fileError.value = 'Pilih file terlebih dahulu.'
        return
    }
    if (fileError.value) return

    importing.value = true
    const formData = new FormData()
    formData.append('file', file.value)

    router.post('/anggota/import', formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            importing.value = false
            emit('update:open', false)
        },
        onError: (errors) => {
            importing.value = false
            const first = Object.values(errors)[0]
            if (first) toast.error(first)
        },
        onFinish: () => {
            importing.value = false
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md p-0">
            <form @submit.prevent="submit" class="flex flex-col">
                <DialogHeader class="border-b p-6 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <FileSpreadsheet class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Import Data Anggota</DialogTitle>
                            <DialogDescription>Upload file Excel/CSV untuk import massal.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="flex flex-col gap-4 p-6">
                    <!-- Info template -->
                    <div class="bg-muted/50 flex items-start gap-3 rounded-lg border p-3 text-sm">
                        <Download class="text-primary mt-0.5 size-4 shrink-0" />
                        <div class="text-muted-foreground text-xs">
                            Gunakan template yang sudah disediakan agar format kolom sesuai. Kolom wajib: <b>nama</b>. NIP kosong akan dibuat otomatis; NIP yang sudah ada akan dilewati.
                        </div>
                    </div>

                    <Button type="button" variant="outline" size="sm" class="w-fit" @click="downloadTemplate">
                        <Download class="size-4" />
                        Unduh Template
                    </Button>

                    <!-- Panduan pengisian (expandable) -->
                    <div class="rounded-lg border">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-2 px-3 py-2.5 text-sm font-medium transition-colors hover:bg-muted/50"
                            @click="showGuide = !showGuide"
                        >
                            <span class="flex items-center gap-2">
                                <HelpCircle class="text-primary size-4" />
                                Panduan Mengisi Template
                            </span>
                            <ChevronDown class="text-muted-foreground size-4 transition-transform" :class="{ 'rotate-180': showGuide }" />
                        </button>
                        <div v-if="showGuide" class="border-t px-3 py-3">
                            <ol class="text-muted-foreground flex list-decimal flex-col gap-2 pl-4 text-xs">
                                <li>
                                    Klik <b class="text-foreground">Unduh Template</b> untuk mendapatkan file Excel berisi kolom yang benar.
                                </li>
                                <li>
                                    Isi satu baris untuk satu anggota. <b class="text-foreground">Kolom wajib: <span class="text-destructive">nama</span></b>.
                                </li>
                                <li>
                                    <b class="text-foreground">Status</b>: tulis <b class="text-foreground">Anggota</b> atau <b class="text-foreground">Karyawan</b>.
                                </li>
                                <li>
                                    <b class="text-foreground">Jenis Kelamin</b>: <b class="text-foreground">L</b> (laki-laki) atau <b class="text-foreground">P</b> (perempuan).
                                </li>
                                <li>
                                    <b class="text-foreground">Tanggal</b> (Lahir, Terdaftar, Pensiun): format <b class="text-foreground">DD-MM-YYYY</b> (contoh: <code class="bg-muted rounded px-1">15-01-2024</code>).
                                </li>
                                <li>
                                    <b class="text-foreground">Ya/Tidak</b> untuk kolom Purna, Aktif, Limit: tulis <b class="text-foreground">Ya</b> atau <b class="text-foreground">Tidak</b> (kosongkan = Tidak).
                                </li>
                                <li>
                                    <b class="text-foreground">Simpanan & Limit</b>: angka biasa tanpa titik/koma ribuan (contoh: <code class="bg-muted rounded px-1">500000</code>).
                                </li>
                                <li>
                                    <b class="text-foreground">NIP</b> boleh dikosongkan — akan dibuat otomatis (ANG-0001 / KRY-0001).
                                </li>
                                <li>
                                    Baris dengan <b class="text-foreground">NIP yang sudah ada</b> akan <b class="text-foreground">dilewati</b> (tidak diimport ulang).
                                </li>
                            </ol>
                        </div>
                    </div>

                    <!-- Dropzone -->
                    <div class="grid gap-2">
                        <Label>File Excel/CSV</Label>
                        <label
                            class="border-input hover:bg-muted/50 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-8 text-center transition-colors"
                            :class="{ 'border-primary': file }"
                        >
                            <template v-if="!file">
                                <UploadCloud class="text-muted-foreground size-8" />
                                <span class="text-muted-foreground text-sm">
                                    Klik untuk memilih file<br />
                                    <span class="text-xs">.xlsx, .xls, .csv — maks 2MB</span>
                                </span>
                            </template>
                            <template v-else>
                                <FileSpreadsheet class="text-primary size-8" />
                                <span class="text-sm font-medium">{{ file.name }}</span>
                                <span class="text-muted-foreground text-xs">
                                    {{ (file.size / 1024).toFixed(1) }} KB
                                </span>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="mt-1"
                                    @click.stop="removeFile"
                                >
                                    <X class="size-4" />
                                    Hapus file
                                </Button>
                            </template>
                            <input type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="onFileChange" />
                        </label>
                        <p v-if="fileError" class="text-destructive text-xs">{{ fileError }}</p>
                    </div>
                </div>

                <DialogFooter class="border-t p-6 pt-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="importing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="importing || !file">
                        <Loader2 v-if="importing" class="size-4 animate-spin" />
                        <UploadCloud v-else class="size-4" />
                        {{ importing ? 'Mengimpor...' : 'Import Data' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
