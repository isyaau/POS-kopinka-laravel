<script setup>
import { AvatarFallback } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
    delayMs: { type: Number, default: 600 },
})
</script>

<template>
    <AvatarFallback
        :delay-ms="props.delayMs"
        :class="cn('bg-muted flex size-full items-center justify-center rounded-full text-xs font-medium', props.class)"
    >
        <slot />
    </AvatarFallback>
</template>
