<script setup>
import { Label } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
    htmlFor: { type: String, default: undefined },
})
</script>

<template>
    <Label
        :class="cn(
            'flex items-center gap-2 text-sm leading-none font-medium select-none group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50',
            props.class,
        )"
        :for="htmlFor"
    >
        <slot />
    </Label>
</template>
