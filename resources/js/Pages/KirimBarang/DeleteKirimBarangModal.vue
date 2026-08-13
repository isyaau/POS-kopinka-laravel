<script setup>
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
import { Loader2, Trash2 } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    kirim: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const form = useForm({})

const submit = () => {
    if (!props.kirim) return
    form.delete(`/kirim-barang/${props.kirim.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            toast.success('Kirim barang berhasil dihapus.')
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md p-0">
            <DialogHeader class="border-b p-6 pb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-destructive/10 text-destructive flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <Trash2 class="size-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-xl">Hapus Kirim Barang</DialogTitle>
                        <DialogDescription>Apakah Anda yakin?</DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="p-6 text-sm text-muted-foreground">
                Kiriman <span class="font-semibold text-foreground">{{ kirim?.no_kirim }}</span>
                dari <span class="font-semibold text-foreground">{{ kirim?.nama_store_asal }}</span>
                ke <span class="font-semibold text-foreground">{{ kirim?.nama_store_tujuan }}</span>
                akan dihapus.
                <span v-if="['dikirim', 'selesai'].includes(kirim?.status)" class="text-destructive mt-2 block">
                    Mutasi stok akan dibatalkan (stok asal kembali, stok tujuan berkurang).
                </span>
            </div>

            <DialogFooter class="border-t p-6 pt-4">
                <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                    Batal
                </Button>
                <Button type="button" variant="destructive" :disabled="form.processing" @click="submit">
                    <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                    <Trash2 v-else class="size-4" />
                    {{ form.processing ? 'Menghapus...' : 'Hapus' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
