<script setup>
import { SwitchRoot, SwitchThumb } from 'radix-vue'
import { cn } from '@/lib/utils'

const model = defineModel({ type: Boolean, default: false })

const props = defineProps({
    class: { type: [String, Array], default: '' },
    disabled: { type: Boolean, default: false },
})
</script>

<template>
    <SwitchRoot
        :checked="model"
        :disabled="disabled"
        @update:checked="model = $event"
        :class="cn(
            'focus-visible:ring-ring/50 focus-visible:ring-[3px] peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent shadow-xs transition-colors outline-none focus-visible:ring disabled:pointer-events-none disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input',
            props.class,
        )"
    >
        <SwitchThumb
            :class="cn(
                'pointer-events-none block h-4 w-4 rounded-full bg-background shadow-lg ring-0 transition-transform data-[state=checked]:translate-x-4 data-[state=unchecked]:translate-x-0',
            )"
        />
    </SwitchRoot>
</template>
