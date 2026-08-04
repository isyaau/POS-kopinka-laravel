<script setup>
import { DropdownMenuContent } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
    side: { type: String, default: 'bottom' },
    align: { type: String, default: 'start' },
})
</script>

<template>
    <DropdownMenuContent
        :class="cn(
            'bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=top]:slide-in-from-bottom-2 z-50 min-w-[8rem] overflow-hidden rounded-md border p-1 shadow-md',
            props.class,
        )"
        :side="props.side"
        :align="props.align"
    >
        <slot />
    </DropdownMenuContent>
</template>
