<script setup>
import { DialogTitle as DialogTitlePrimitive } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
})
</script>

<template>
    <DialogTitlePrimitive
        :class="cn('text-lg leading-none font-semibold tracking-tight', props.class)"
    >
        <slot />
    </DialogTitlePrimitive>
</template>
