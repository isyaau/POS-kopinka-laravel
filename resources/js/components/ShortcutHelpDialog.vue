<script setup>
import { computed } from 'vue'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'
import { hotkeyRegistry } from '@/composables/useHotkeys'
import Kbd from '@/components/Kbd.vue'
import { Keyboard } from 'lucide-vue-next'

defineProps({
    open: { type: Boolean, default: false },
})

const emit = defineEmits(['update:open'])

const groups = computed(() => {
    const map = new Map()
    for (const item of hotkeyRegistry.value) {
        if (!map.has(item.group)) map.set(item.group, [])
        map.get(item.group).push(item)
    }
    return [...map.entries()].map(([name, items]) => ({ name, items }))
})

const totalShortcuts = computed(() => hotkeyRegistry.value.length)
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-lg gap-0 overflow-hidden p-0">
            <DialogHeader class="border-b p-6 pr-12 pb-5">
                <div class="flex items-center gap-3.5">
                    <div class="bg-primary/10 text-primary flex size-11 shrink-0 items-center justify-center rounded-xl">
                        <Keyboard class="size-5" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <DialogTitle>Pintasan Papan Ketik</DialogTitle>
                        <DialogDescription class="flex items-center gap-1.5 leading-relaxed">
                            Tekan <Kbd keys="F1" /> kapan saja untuk membuka daftar ini.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="max-h-[60vh] overflow-y-auto px-6 py-5">
                <template v-if="groups.length">
                    <section
                        v-for="group in groups"
                        :key="group.name"
                        class="mb-5 last:mb-0"
                    >
                        <p
                            class="text-muted-foreground mb-2.5 text-[11px] font-semibold tracking-widest uppercase"
                        >
                            {{ group.name }}
                        </p>
                        <ul class="flex flex-col gap-1.5">
                            <li
                                v-for="item in group.items"
                                :key="item.id"
                                class="flex items-center justify-between gap-6 rounded-lg border border-border/60 px-3 py-2 transition-colors hover:bg-muted/60"
                            >
                                <span class="text-sm leading-snug">{{ item.description }}</span>
                                <span class="flex shrink-0 items-center gap-1.5">
                                    <Kbd :keys="item.keys" />
                                </span>
                            </li>
                        </ul>
                    </section>
                </template>
                <p v-else class="text-muted-foreground py-10 text-center text-sm">
                    Belum ada pintasan aktif di halaman ini.
                </p>
            </div>

            <div
                class="text-muted-foreground flex items-center justify-between gap-4 border-t bg-muted/30 px-6 py-3 text-xs"
            >
                <span>Total {{ totalShortcuts }} pintasan aktif di halaman ini</span>
                <span class="flex items-center gap-1.5">
                    <Kbd keys="Esc" /> untuk menutup
                </span>
            </div>
        </DialogContent>
    </Dialog>
</template>
