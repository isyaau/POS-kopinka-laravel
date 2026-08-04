<script setup>
import { DropdownMenuItem } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
    inset: { type: Boolean, default: false },
    variant: { type: String, default: 'default' },
})

const variants = {
    default: 'focus:bg-accent focus:text-accent-foreground',
    destructive: 'focus:bg-destructive focus:text-destructive-foreground text-destructive',
}
</script>

<template>
    <DropdownMenuItem
        :class="cn(
            'focus:bg-accent focus:text-accent-foreground relative flex cursor-default items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-hidden transition-colors select-none data-[disabled=true]:pointer-events-none data-[disabled=true]:opacity-50 [&>svg]:size-4 [&>svg]:shrink-0',
            inset && 'pl-8',
            variants[variant],
            props.class,
        )"
    >
        <slot />
    </DropdownMenuItem>
</template>
