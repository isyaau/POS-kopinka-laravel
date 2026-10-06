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
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-lg gap-0">
            <DialogHeader class="mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <Keyboard class="size-5" />
                    </div>
                    <div>
                        <DialogTitle>Pintasan Papan Ketik</DialogTitle>
                        <DialogDescription>
                            Tekan <Kbd keys="?" class="mx-1" /> kapan saja untuk membuka daftar ini.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="max-h-[60vh] overflow-y-auto pr-1">
                <template v-if="groups.length">
                    <section v-for="group in groups" :key="group.name" class="mb-4 last:mb-0">
                        <p class="text-muted-foreground mb-2 text-[11px] font-semibold uppercase tracking-wide">
                            {{ group.name }}
                        </p>
                        <ul class="flex flex-col gap-1">
                            <li
                                v-for="item in group.items"
                                :key="item.id"
                                class="hover:bg-muted/60 flex items-center justify-between gap-4 rounded-lg px-2 py-1.5"
                            >
                                <span class="text-sm">{{ item.description }}</span>
                                <span class="flex shrink-0 items-center gap-1.5">
                                    <Kbd :keys="item.keys" />
                                </span>
                            </li>
                        </ul>
                    </section>
                </template>
                <p v-else class="text-muted-foreground py-8 text-center text-sm">
                    Belum ada pintasan aktif di halaman ini.
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
