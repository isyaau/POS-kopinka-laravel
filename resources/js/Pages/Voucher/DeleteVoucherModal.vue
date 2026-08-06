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
import { Loader2, AlertTriangle } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    voucher: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const form = useForm({})

const submit = () => {
    form.delete(`/voucher/${props.voucher.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            toast.success(`Voucher ${props.voucher.kode} berhasil dihapus.`)
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div class="bg-destructive/10 text-destructive flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <AlertTriangle class="size-5" />
                    </div>
                    <div>
                        <DialogTitle>Hapus Voucher</DialogTitle>
                        <DialogDescription>
                            Yakin ingin menghapus voucher <b>{{ voucher?.kode }}</b> —
                            <b>{{ voucher?.nama }}</b>?
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">
                    Batal
                </Button>
                <Button variant="destructive" :disabled="form.processing" @click="submit">
                    <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                    <AlertTriangle v-else class="size-4" />
                    {{ form.processing ? 'Menghapus...' : 'Ya, Hapus' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
