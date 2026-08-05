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
    store: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const deleteForm = useForm({})

watch(
    () => props.open,
    (val) => {
        if (!val) deleteForm.clearErrors()
    },
)

const isPusat = () => props.store?.tipe === 'pusat'

const submit = () => {
    if (!props.store || isPusat()) return
    deleteForm.delete(`/toko/${props.store.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            setTimeout(() => toast.success('Toko berhasil dihapus.'), 50)
        },
    })
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md gap-0 overflow-hidden p-0 sm:rounded-2xl">
            <DialogHeader class="gap-0 p-0">
                <div class="flex items-start gap-4 p-6 pb-4">
                    <div
                        class="bg-destructive/10 text-destructive flex size-11 shrink-0 items-center justify-center rounded-full"
                    >
                        <AlertTriangle class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <DialogTitle class="text-lg font-bold">
                            {{ isPusat() ? 'Tidak Dapat Dihapus' : 'Hapus Toko?' }}
                        </DialogTitle>
                        <DialogDescription class="text-muted-foreground mt-1 text-sm leading-relaxed">
                            <template v-if="isPusat()">
                                Toko <span class="text-foreground font-semibold">{{ store?.nama }}</span> adalah
                                <span class="text-foreground font-semibold">Kantor Pusat</span>, sehingga tidak dapat
                                dihapus.
                            </template>
                            <template v-else>
                                Anda yakin ingin menghapus
                                <span class="text-foreground font-semibold">{{ store?.nama }}</span>?
                                <br />
                                Tindakan ini <span class="text-destructive font-semibold">tidak dapat dibatalkan</span>.
                            </template>
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <DialogFooter class="bg-muted/30 flex items-center justify-end gap-2 border-t px-6 py-4">
                <template v-if="isPusat()">
                    <Button type="button" @click="emit('update:open', false)">Tutup</Button>
                </template>
                <template v-else>
                    <Button type="button" variant="outline" :disabled="deleteForm.processing" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="button" variant="destructive" :disabled="deleteForm.processing" @click="submit">
                        <Loader2 v-if="deleteForm.processing" class="size-4 animate-spin" />
                        <Trash2 v-else class="size-4" />
                        {{ deleteForm.processing ? 'Menghapus...' : 'Ya, Hapus' }}
                    </Button>
                </template>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
