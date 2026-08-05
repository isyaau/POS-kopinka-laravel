<script setup>
import { computed } from 'vue'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { X, Users, Mail, Store, Shield, CalendarDays } from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    user: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const u = computed(() => props.user || {})

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

const formatDate = (val) => {
    if (!val) return '-'
    const d = new Date(val)
    if (Number.isNaN(d.getTime())) return '-'
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}-${month}-${year}`
}

const primaryStore = computed(() => u.value.store || null)
const extraStores = computed(() => u.value.stores || [])
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            :show-close="false"
            class="flex max-h-[88svh] max-w-xl flex-col gap-0 overflow-hidden p-0"
        >
            <!-- Header -->
            <div class="bg-muted/40 flex shrink-0 items-center justify-between border-b px-6 py-3">
                <DialogHeader class="gap-0">
                    <DialogTitle class="text-sm font-semibold">Detail Pengguna</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-xs">
                        {{ u.email || 'Informasi pengguna' }}
                    </DialogDescription>
                </DialogHeader>
                <Button variant="ghost" size="icon" class="size-8 shrink-0" @click="emit('update:open', false)">
                    <X class="size-4" />
                    <span class="sr-only">Tutup</span>
                </Button>
            </div>

            <!-- Profil user -->
            <div class="flex shrink-0 items-center gap-4 border-b px-6 py-5">
                <div class="bg-primary/10 text-primary flex size-14 shrink-0 items-center justify-center rounded-full">
                    <Users class="size-7" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold">{{ u.name || '-' }}</p>
                    <p class="text-muted-foreground flex items-center gap-1.5 text-sm">
                        <Mail class="size-3.5" />
                        {{ u.email || '-' }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <Badge v-for="r in u.roles || []" :key="r" variant="secondary">
                            <Shield class="size-3" />
                            {{ roleLabel(r) }}
                        </Badge>
                        <span v-if="!(u.roles || []).length" class="text-muted-foreground text-xs">Tanpa role</span>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                <!-- Toko utama -->
                <div class="mb-3 flex items-center gap-2">
                    <Store class="text-primary size-4" />
                    <h3 class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">Toko Utama</h3>
                </div>
                <div v-if="primaryStore" class="flex items-center gap-2.5 rounded-lg border bg-muted/20 p-3">
                    <div class="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-lg">
                        <Store class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="font-medium">{{ primaryStore.nama }}</p>
                        <p class="text-muted-foreground text-xs">{{ primaryStore.kode }}</p>
                    </div>
                </div>
                <p v-else class="text-muted-foreground text-sm">Belum ada toko utama.</p>

                <!-- Akses toko tambahan -->
                <Separator class="my-5" />
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Store class="text-primary size-4" />
                        <h3 class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">Akses Toko Lain</h3>
                    </div>
                    <Badge variant="secondary" class="text-[10px]">{{ extraStores.length }}</Badge>
                </div>
                <div v-if="extraStores.length" class="flex flex-wrap gap-1.5">
                    <Badge v-for="s in extraStores" :key="s.id" variant="outline">
                        <Store class="size-3" />
                        {{ s.nama }}
                        <span class="text-muted-foreground">{{ s.kode }}</span>
                    </Badge>
                </div>
                <p v-else class="text-muted-foreground text-sm">Tidak ada akses toko tambahan.</p>

                <!-- Info dibuat -->
                <Separator class="my-5" />
                <div class="flex items-center gap-2">
                    <CalendarDays class="text-muted-foreground size-4" />
                    <h3 class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">Informasi Lain</h3>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-muted-foreground text-xs">Terdaftar</span>
                        <span class="text-sm font-medium">{{ formatDate(u.created_at) }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-muted-foreground text-xs">Terakhir Diperbarui</span>
                        <span class="text-sm font-medium">{{ formatDate(u.updated_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex shrink-0 items-center justify-end gap-2 border-t bg-muted/20 px-6 py-3">
                <Button variant="outline" size="sm" @click="emit('update:open', false)">
                    <X class="size-3.5" />
                    Tutup
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
