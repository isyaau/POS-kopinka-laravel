<script setup>
import { SelectItem, SelectItemIndicator, SelectItemText } from 'radix-vue'
import { Check } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps({
    value: { type: [String, Number], required: true },
    class: { type: [String, Array], default: '' },
    disabled: { type: Boolean, default: false },
})
</script>

<template>
    <SelectItem
        :value="String(value)"
        :disabled="disabled"
        :class="cn(
            'focus:bg-accent focus:text-accent-foreground relative flex w-full cursor-default items-center gap-2 rounded-sm py-1.5 pr-8 pl-2 text-sm outline-none select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:pointer-events-none',
            props.class,
        )"
    >
        <span class="absolute right-2 flex h-3.5 w-3.5 items-center justify-center">
            <SelectItemIndicator>
                <Check class="size-4" />
            </SelectItemIndicator>
        </span>
        <SelectItemText>
            <slot />
        </SelectItemText>
    </SelectItem>
</template>
