<script setup>
import { Separator } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
    orientation: { type: String, default: 'horizontal' },
    decorative: { type: Boolean, default: false },
})
</script>

<template>
    <Separator
        :class="cn(
            'bg-border relative shrink-0 data-[orientation=horizontal]:h-px data-[orientation=horizontal]:w-full data-[orientation=vertical]:h-full data-[orientation=vertical]:w-px',
            props.class,
        )"
        v-bind="props"
    />
</template>
