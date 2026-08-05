<script setup>
import { DialogDescription as DialogDescriptionPrimitive } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
})
</script>

<template>
    <DialogDescriptionPrimitive
        :class="cn('text-muted-foreground text-sm', props.class)"
    >
        <slot />
    </DialogDescriptionPrimitive>
</template>
