<script setup>
import { SelectTrigger, SelectValue, SelectIcon } from 'radix-vue'
import { ChevronDown } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
    placeholder: { type: String, default: 'Pilih...' },
})
</script>

<template>
    <SelectTrigger
        :class="cn(
            'border-input data-[placeholder]:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive dark:aria-invalid:ring-destructive/40 flex h-9 w-full items-center justify-between gap-2 rounded-md border bg-transparent px-3 py-2 text-sm whitespace-nowrap shadow-xs transition-[color,box-shadow] outline-none disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1',
            props.class,
        )"
    >
        <SelectValue :placeholder="placeholder" />
        <SelectIcon as-child>
            <ChevronDown class="text-muted-foreground size-4 opacity-50" />
        </SelectIcon>
    </SelectTrigger>
</template>
