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
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Select, SelectTrigger, SelectContent, SelectItem } from '@/components/ui/select'
import { Loader2, Users, X, Store, Shield, KeyRound } from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'

const props = defineProps({
    open: { type: Boolean, default: false },
    user: { type: Object, default: null },
    roles: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:open'])

const isEdit = computed(() => Boolean(props.user))

const form = useForm({
    name: '',
    email: '',
    password: '',
    store_id: '',
    role: '',
    stores: [],
})

watch(
    () => props.open,
    (val) => {
        if (!val) return
        const u = props.user
        form.clearErrors()
        form.name = u?.name || ''
        form.email = u?.email || ''
        form.password = ''
        form.store_id = u?.store_id ? String(u.store_id) : ''
        form.role = u?.roles?.[0] || ''
        form.stores = (u?.stores || []).map((s) => String(s.id))
    },
)

const toggleStore = (id) => {
    const i = String(id)
    if (form.stores.includes(i)) {
        form.stores = form.stores.filter((x) => x !== i)
    } else {
        form.stores = [...form.stores, i]
    }
}

const roleLabel = (name) => {
    const map = {
        'super-admin': 'Super Admin',
        admin: 'Admin',
        kasir: 'Kasir',
        manager: 'Manager',
        'kepala-toko': 'Kepala Toko',
        akuntansi: 'Akuntansi',
    }
    return map[name] || name
}

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false)
            setTimeout(() => {
                toast.success(isEdit.value ? 'Pengguna berhasil diperbarui.' : 'Pengguna berhasil ditambahkan.')
            }, 50)
        },
    }

    if (isEdit.value) {
        form.put(`/pengguna/${props.user.id}`, options)
    } else {
        form.post('/pengguna', options)
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
                        <Users class="size-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg">{{ isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' }}</DialogTitle>
                        <DialogDescription class="text-muted-foreground text-sm">
                            {{ isEdit ? 'Perbarui data pengguna dan akses toko.' : 'Buat pengguna baru dan atur aksesnya.' }}
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
                    <!-- Data akun -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-foreground text-sm font-semibold tracking-wide uppercase">Data Akun</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label htmlFor="user-name">Nama Lengkap</Label>
                                <Input id="user-name" v-model="form.name" placeholder="Nama pengguna" :disabled="form.processing" />
                                <p v-if="form.errors.name" class="text-destructive text-xs">{{ form.errors.name }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label htmlFor="user-email">Email</Label>
                                <Input id="user-email" v-model="form.email" type="email" placeholder="email@example.com" :disabled="form.processing" />
                                <p v-if="form.errors.email" class="text-destructive text-xs">{{ form.errors.email }}</p>
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label htmlFor="user-password">
                                    {{ isEdit ? 'Password (kosongkan jika tidak diubah)' : 'Password' }}
                                </Label>
                                <div class="relative">
                                    <KeyRound class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                    <Input
                                        id="user-password"
                                        v-model="form.password"
                                        type="password"
                                        placeholder="Minimal 6 karakter"
                                        class="pl-9"
                                        :disabled="form.processing"
                                    />
                                </div>
                                <p v-if="form.errors.password" class="text-destructive text-xs">{{ form.errors.password }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Role & toko -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-foreground text-sm font-semibold tracking-wide uppercase">Role & Toko</h3>
                            <div class="bg-border h-px flex-1" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label>Role</Label>
                                <Select v-model="form.role">
                                    <SelectTrigger />
                                    <SelectContent>
                                        <SelectItem v-for="r in roles" :key="r.id" :value="r.name">
                                            <span class="flex items-center gap-2">
                                                <Shield class="size-3.5" />
                                                {{ roleLabel(r.name) }}
                                            </span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.role" class="text-destructive text-xs">{{ form.errors.role }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label>Toko Utama</Label>
                                <Select v-model="form.store_id">
                                    <SelectTrigger />
                                    <SelectContent>
                                        <SelectItem v-for="s in stores" :key="s.id" :value="String(s.id)">
                                            <span class="flex items-center gap-2">
                                                <Store class="size-3.5" />
                                                {{ s.nama }} ({{ s.kode }})
                                            </span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.store_id" class="text-destructive text-xs">{{ form.errors.store_id }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Akses toko tambahan -->
                    <section class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <h3 class="text-foreground text-sm font-semibold tracking-wide uppercase">Akses Toko Tambahan</h3>
                                <div class="bg-border h-px flex-1" />
                            </div>
                            <Badge variant="secondary">{{ form.stores.length }} toko</Badge>
                        </div>
                        <p class="text-muted-foreground text-xs">
                            Berlaku untuk role per-toko (kasir / kepala toko). Centang toko lain yang boleh diakses — misalnya kasir yang berpindah shift.
                        </p>
                        <div class="grid grid-cols-1 gap-1 rounded-lg border bg-muted/20 p-2 sm:grid-cols-2">
                            <label
                                v-for="s in stores"
                                :key="s.id"
                                class="hover:bg-muted flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5"
                            >
                                <input
                                    type="checkbox"
                                    class="size-4 accent-primary"
                                    :checked="form.stores.includes(String(s.id))"
                                    :disabled="form.processing"
                                    @change="toggleStore(s.id)"
                                />
                                <span class="flex items-center gap-1.5 text-sm">
                                    <Store class="text-muted-foreground size-3.5" />
                                    <span class="font-medium">{{ s.nama }}</span>
                                    <span class="text-muted-foreground text-xs">{{ s.kode }}</span>
                                </span>
                            </label>
                        </div>
                    </section>
                </div>

                <DialogFooter class="flex shrink-0 items-center justify-end gap-2 border-t bg-muted/20 px-6 py-4">
                    <Button type="button" variant="outline" @click="emit('update:open', false)" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <Users v-else class="size-4" />
                        {{ form.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Simpan Pengguna' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
