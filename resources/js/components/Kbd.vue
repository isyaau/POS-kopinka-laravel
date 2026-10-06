<script setup>
import { computed } from 'vue'

const props = defineProps({
    keys: { type: [String, Array], default: '' },
})

const combos = computed(() => {
    const raw = Array.isArray(props.keys)
        ? props.keys
        : String(props.keys)
              .split('+')
              .map((s) => s.trim())

    if (raw.length && Array.isArray(raw[0])) return raw.map((group) => group.filter(Boolean))
    return [raw.filter(Boolean)]
})
</script>

<template>
    <span class="inline-flex items-center gap-1 align-middle">
        <template v-for="(combo, ci) in combos" :key="ci">
            <span
                v-if="ci > 0"
                class="text-muted-foreground/70 text-[10px] leading-none"
            >atau</span>
            <template v-for="(token, i) in combo" :key="`${token}-${i}`">
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
        </template>
    </span>
</template>