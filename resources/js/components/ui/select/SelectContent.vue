<script setup>
import { SelectContent, SelectViewport, SelectPortal } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
})
</script>

<template>
    <SelectPortal>
        <SelectContent
            :class="cn(
                'data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95 data-[state=closed]:zoom-out-95 relative z-50 max-h-96 min-w-[8rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md',
                props.class,
            )"
        >
            <SelectViewport class="p-1">
                <slot />
            </SelectViewport>
        </SelectContent>
    </SelectPortal>
</template>
