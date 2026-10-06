<script setup>
import { computed } from 'vue'

const props = defineProps({
    keys: { type: [String, Array], default: '' },
})

const tokens = computed(() => {
    if (Array.isArray(props.keys)) return props.keys.filter(Boolean)
    if (!props.keys) return []
    return String(props.keys).split('+').map((s) => s.trim()).filter(Boolean)
})
</script>

<template>
    <span class="inline-flex items-center gap-1 align-middle">
        <template v-for="(token, i) in tokens" :key="`${token}-${i}`">
            <span
                v-if="i > 0"
                class="text-muted-foreground/60 text-[10px] leading-none"
            >+</span>
            <kbd
                class="border-border bg-muted text-foreground inline-flex h-5 min-w-5 items-center justify-center rounded-md border px-1.5 font-mono text-[11px] font-semibold leading-none shadow-[0_1px_0_rgba(0,0,0,0.08)]"
            >
                {{ token }}
            </kbd>
        </template>
    </span>
</template>
