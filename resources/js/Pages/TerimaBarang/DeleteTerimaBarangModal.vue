<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Loader2, Trash2, AlertTriangle } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    terima: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const deleteForm = useForm({})

// Reset error saat dialog ditutup/dibuka
watch(
    () => props.open,
    (val) => {
        if (!val) deleteForm.clearErrors()
    },
)

const submit = () => {
    if (!props.terima) return
    deleteForm.delete(`/terima-barang/${props.terima.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            toast.success('Penerimaan barang berhasil dihapus dan stok dikembalikan.')
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md gap-0 overflow-hidden p-0 sm:rounded-2xl">
            <DialogHeader class="gap-0 p-0">
                <!-- Ikon peringatan -->
                <div class="flex items-start gap-4 p-6 pb-4">
                    <div
                        class="bg-destructive/10 text-destructive flex size-11 shrink-0 items-center justify-center rounded-full"
                    >
                        <AlertTriangle class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <DialogTitle class="text-lg font-bold">Hapus Penerimaan Barang?</DialogTitle>
                        <DialogDescription class="text-muted-foreground mt-1 text-sm leading-relaxed">
                            Anda yakin ingin menghapus
                            <span class="text-foreground font-semibold">{{ terima?.no_terima || 'penerimaan ini' }}</span>?
                            <br />
                            Stok produk yang diterima akan <span class="text-destructive font-semibold">dikembalikan</span> (dikurangi)
                            dan data tidak dapat dipulihkan.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <DialogFooter class="bg-muted/30 flex items-center justify-end gap-2 border-t px-6 py-4">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="deleteForm.processing"
                    @click="emit('update:open', false)"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    :disabled="deleteForm.processing"
                    @click="submit"
                >
                    <Loader2 v-if="deleteForm.processing" class="size-4 animate-spin" />
                    <Trash2 v-else class="size-4" />
                    {{ deleteForm.processing ? 'Menghapus...' : 'Ya, Hapus' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
