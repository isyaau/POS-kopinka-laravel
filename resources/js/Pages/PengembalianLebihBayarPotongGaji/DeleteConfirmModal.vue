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
    item: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const deleteForm = useForm({})

watch(() => props.open, (val) => { if (!val) deleteForm.clearErrors() })

const submit = () => {
    if (!props.item) return
    deleteForm.delete(`/pengembalian-lebih-bayar-potong-gaji/${props.item.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            toast.success('Data pengembalian lebih bayar potong gaji berhasil dihapus.')
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md gap-0 overflow-hidden p-0 sm:rounded-2xl">
            <DialogHeader class="gap-0 p-0">
                <div class="flex items-start gap-4 p-6 pb-4">
                    <div class="bg-destructive/10 text-destructive flex size-11 shrink-0 items-center justify-center rounded-full">
                        <AlertTriangle class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <DialogTitle class="text-lg font-bold">Hapus Pengembalian Lebih Bayar?</DialogTitle>
                        <DialogDescription class="text-muted-foreground mt-1 text-sm leading-relaxed">
                            Anda yakin ingin menghapus pengembalian
                            <span class="text-foreground font-semibold">{{ item?.no_transaksi || 'ini' }}</span>?
                            <br />
                            Tindakan ini <span class="text-destructive font-semibold">tidak dapat dibatalkan</span>.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <DialogFooter class="bg-muted/30 flex items-center justify-end gap-2 border-t px-6 py-4">
                <Button type="button" variant="outline" :disabled="deleteForm.processing" @click="emit('update:open', false)">
                    Batal
                </Button>
                <Button type="button" variant="destructive" :disabled="deleteForm.processing" @click="submit">
                    <Loader2 v-if="deleteForm.processing" class="size-4 animate-spin" />
                    <Trash2 v-else class="size-4" />
                    {{ deleteForm.processing ? 'Menghapus...' : 'Ya, Hapus' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
