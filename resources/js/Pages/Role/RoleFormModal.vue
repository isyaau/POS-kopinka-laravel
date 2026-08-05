<script setup>
import { computed, ref, watch } from 'vue'
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
import { Badge } from '@/components/ui/badge'
import { Loader2, Shield, X, Check } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    role: { type: Object, default: null },
    permissions: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const isEdit = computed(() => Boolean(props.role))

const form = useForm({
    name: '',
    permissions: [],
})

watch(
    () => props.open,
    (val) => {
        if (!val) return
        form.clearErrors()
        form.name = props.role?.name || ''
        form.permissions = [...(props.role?.permissions || [])]
    },
)

// Kelompokkan permission per modul
const grouped = computed(() => {
    const groups = {}
    for (const p of props.permissions) {
        const mod = p.split('.')[0]
        if (!groups[mod]) groups[mod] = []
        groups[mod].push(p)
    }
    return Object.entries(groups).map(([mod, perms]) => ({
        mod,
        label: mod.replace(/-/g, ' ').replace(/^\w/, (c) => c.toUpperCase()),
        perms,
    }))
})

const toggle = (p) => {
    if (form.permissions.includes(p)) {
        form.permissions = form.permissions.filter((x) => x !== p)
    } else {
        form.permissions = [...form.permissions, p]
    }
}

const toggleAll = (perms) => {
    const allChecked = perms.every((p) => form.permissions.includes(p))
    if (allChecked) {
        form.permissions = form.permissions.filter((x) => !perms.includes(x))
    } else {
        form.permissions = [...new Set([...form.permissions, ...perms])]
    }
}

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            setTimeout(() => {
                toast.success(isEdit.value ? 'Role berhasil diperbarui.' : 'Role berhasil dibuat.')
            }, 50)
        },
    }

    if (isEdit.value) {
        form.put(`/roles/${props.role.id}`, options)
    } else {
        form.post('/roles', options)
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            :show-close="false"
            class="flex max-h-[90svh] max-w-2xl flex-col gap-0 overflow-hidden p-0"
        >
            <DialogHeader class="flex shrink-0 flex-row items-center justify-between gap-3 border-b p-5">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <Shield class="size-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg">{{ isEdit ? 'Edit Role' : 'Tambah Role' }}</DialogTitle>
                        <DialogDescription class="text-muted-foreground text-sm">
                            {{ isEdit ? 'Ubah nama dan hak akses role.' : 'Buat role baru dan atur hak aksesnya.' }}
                        </DialogDescription>
                    </div>
                </div>
                <Button variant="ghost" size="icon" class="size-8 shrink-0" @click="emit('update:open', false)">
                    <X class="size-4" />
                    <span class="sr-only">Tutup</span>
                </Button>
            </DialogHeader>

            <form @submit.prevent="submit" class="flex min-h-0 flex-1 flex-col">
                <div class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto p-6">
                    <!-- Nama role -->
                    <div class="grid gap-2">
                        <Label htmlFor="role-name">Nama Role</Label>
                        <Input
                            id="role-name"
                            v-model="form.name"
                            placeholder="cth: supervisor"
                            :disabled="form.processing"
                            class="lowercase"
                        />
                        <p v-if="form.errors.name" class="text-destructive text-xs">{{ form.errors.name }}</p>
                    </div>

                    <!-- Hak akses -->
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <Label class="text-sm font-semibold">Hak Akses</Label>
                            <Badge variant="secondary">{{ form.permissions.length }} dipilih</Badge>
                        </div>

                        <div v-for="group in grouped" :key="group.mod" class="rounded-lg border">
                            <div class="bg-muted/50 flex items-center justify-between rounded-t-lg px-4 py-2.5">
                                <span class="text-sm font-semibold">{{ group.label }}</span>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 text-xs"
                                    @click="toggleAll(group.perms)"
                                >
                                    <Check class="size-3.5" />
                                    Pilih Semua
                                </Button>
                            </div>
                            <div class="grid grid-cols-1 gap-1 p-3 sm:grid-cols-2">
                                <label
                                    v-for="p in group.perms"
                                    :key="p"
                                    class="hover:bg-muted/40 flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5"
                                >
                                    <input
                                        type="checkbox"
                                        class="size-4 accent-primary"
                                        :checked="form.permissions.includes(p)"
                                        :disabled="form.processing"
                                        @change="toggle(p)"
                                    />
                                    <span class="font-mono text-xs">{{ p }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="flex shrink-0 items-center justify-end gap-2 border-t bg-muted/20 px-6 py-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <Shield v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Simpan Role' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
