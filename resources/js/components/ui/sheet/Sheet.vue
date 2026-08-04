<script setup>
import { DialogRoot } from 'radix-vue'
import { provideSheetContext } from './inject'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    defaultOpen: { type: Boolean, default: false },
    open: { type: Boolean, default: undefined },
    side: { type: String, default: 'left' },
})

const emit = defineEmits(['update:open'])

provideSheetContext({ side: () => props.side })
</script>

<template>
    <DialogRoot
        :default-open="defaultOpen"
        :open="open"
        @update:open="(v) => emit('update:open', v)"
    >
        <slot />
    </DialogRoot>
</template>
