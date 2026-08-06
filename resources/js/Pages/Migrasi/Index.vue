<script setup>
import { ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { toast } from '@/components/ui/sonner'
import {
    Database,
    FileSpreadsheet,
    UploadCloud,
    Download,
    Loader2,
    X,
    ChevronDown,
    HelpCircle,
    CheckCircle2,
} from 'lucide-vue-next'

const file = ref(null)
const fileError = ref('')
const importing = ref(false)
const showGuide = ref(false)

const page = usePage()

// Reset saat halaman dibuka ulang
watch(
    () => page.url,
    () => {
        file.value = null
        fileError.value = ''
        showGuide.value = false
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
    window.location.href = '/suppliers/export?template=1'
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

    router.post('/suppliers/import', formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            importing.value = false
            const flash = page.props.flash
            if (flash?.success) toast.success(flash.success)
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
    <AppLayout>
        <Head title="Migrasi Data - Kopinka" />

        <PageHeader
            title="Migrasi Data"
            description="Import data dari file Excel/CSV ke sistem Kopinka."
        />

        <div class="flex flex-col gap-4">
            <!-- Card Import Supplier -->
            <Card class="max-w-3xl">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileSpreadsheet class="text-primary size-5" />
                        Import Data Supplier
                    </CardTitle>
                    <CardDescription>
                        Upload file Excel/CSV berisi data supplier untuk migrasi massal.
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-4">
                    <!-- Info template -->
                    <div class="bg-muted/50 flex items-start gap-3 rounded-lg border p-3 text-sm">
                        <Download class="text-primary mt-0.5 size-4 shrink-0" />
                        <div class="text-muted-foreground text-xs">
                            Gunakan template yang sudah disediakan agar format kolom sesuai. Kolom wajib:
                            <b>nama_supplier</b>. Kode kosong akan dibuat otomatis (SPL-0001, dst.); kode yang
                            sudah ada akan dilewati.
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
                                    Isi satu baris untuk satu supplier. <b class="text-foreground">Kolom wajib: <span class="text-destructive">nama_supplier</span></b>.
                                </li>
                                <li>
                                    <b class="text-foreground">Kode</b> boleh dikosongkan — akan dibuat otomatis (SPL-0001, SPL-0002, dst).
                                </li>
                                <li>
                                    <b class="text-foreground">Alamat, Contact Person, No Telp/HP, Keterangan</b> bersifat opsional.
                                </li>
                                <li>
                                    Baris dengan <b class="text-foreground">Kode yang sudah ada</b> akan <b class="text-foreground">dilewati</b> (tidak diimport ulang).
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

                    <!-- Submit -->
                    <div class="flex items-center justify-end gap-2 border-t pt-4">
                        <Button type="button" :disabled="importing || !file" @click="submit">
                            <Loader2 v-if="importing" class="size-4 animate-spin" />
                            <UploadCloud v-else class="size-4" />
                            {{ importing ? 'Mengimpor...' : 'Import Data Supplier' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Info modul migrasi -->
            <Card class="max-w-3xl">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Database class="text-primary size-5" />
                        Modul Migrasi
                    </CardTitle>
                    <CardDescription>
                        Pusat migrasi data dari sistem lama ke POS Kopinka.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between rounded-lg border p-3">
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="text-primary size-4" />
                                <span class="text-sm font-medium">Supplier</span>
                            </div>
                            <Badge variant="secondary">Tersedia</Badge>
                        </div>
                        <div class="text-muted-foreground flex items-center justify-between rounded-lg border p-3 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="size-4" />
                                <span>Anggota, Produk, Transaksi</span>
                            </div>
                            <Badge variant="outline">Segera</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
