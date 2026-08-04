<script setup>
import { DropdownMenuLabel } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
    inset: { type: Boolean, default: false },
})
</script>

<template>
    <DropdownMenuLabel
        :class="cn('px-2 py-1.5 text-sm font-medium', props.inset && 'pl-8', props.class)"
    >
        <slot />
    </DropdownMenuLabel>
</template>
