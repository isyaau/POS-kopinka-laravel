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
import { X, Shield, Users, Lock, CalendarDays } from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    role: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const r = computed(() => props.role || {})

const grouped = computed(() => {
    const groups = {}
    for (const p of r.value.permissions || []) {
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

const formatDate = (val) => {
    if (!val) return '-'
    const d = new Date(val)
    if (Number.isNaN(d.getTime())) return '-'
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}-${month}-${year}`
}
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
                    <DialogTitle class="text-sm font-semibold">Detail Role</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-xs">
                        {{ r.label || 'Informasi role' }}
                    </DialogDescription>
                </DialogHeader>
                <Button variant="ghost" size="icon" class="size-8 shrink-0" @click="emit('update:open', false)">
                    <X class="size-4" />
                    <span class="sr-only">Tutup</span>
                </Button>
            </div>

            <!-- Profil role -->
            <div class="flex shrink-0 items-center gap-4 border-b px-6 py-5">
                <div
                    class="flex size-14 shrink-0 items-center justify-center rounded-2xl"
                    :class="r.is_system ? 'bg-muted text-muted-foreground' : 'bg-primary/10 text-primary'"
                >
                    <Shield class="size-7" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2 text-lg font-bold">
                        {{ r.label || '-' }}
                        <Badge v-if="r.is_system" variant="secondary" class="text-[10px]">Sistem</Badge>
                    </p>
                    <p class="text-muted-foreground text-sm">{{ r.name || '-' }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <Badge variant="outline" class="font-mono text-[10px]">
                            {{ r.permissions?.length || 0 }} permission
                        </Badge>
                        <Badge variant="secondary">
                            <Users class="size-3" />
                            {{ r.users_count || 0 }} pengguna
                        </Badge>
                    </div>
                </div>
            </div>

            <!-- Body: permissions per modul -->
            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                <div v-if="grouped.length">
                    <div v-for="(g, i) in grouped" :key="g.mod">
                        <Separator v-if="i > 0" class="my-4" />
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">
                                {{ g.label }}
                            </h3>
                            <Badge variant="secondary" class="text-[10px]">{{ g.perms.length }}</Badge>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <Badge v-for="p in g.perms" :key="p" variant="outline" class="font-mono text-[10px]">
                                {{ p }}
                            </Badge>
                        </div>
                    </div>
                </div>
                <p v-else class="text-muted-foreground text-sm">Role ini tidak memiliki hak akses.</p>

                <!-- Info dibuat -->
                <Separator class="my-5" />
                <div class="flex items-center gap-2">
                    <CalendarDays class="text-muted-foreground size-4" />
                    <h3 class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">Informasi Lain</h3>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-muted-foreground text-xs">Terdaftar</span>
                        <span class="text-sm font-medium">{{ formatDate(r.created_at) }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-muted-foreground text-xs">Terakhir Diperbarui</span>
                        <span class="text-sm font-medium">{{ formatDate(r.updated_at) }}</span>
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
