<script setup>
import { DialogRoot } from 'radix-vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    defaultOpen: { type: Boolean, default: false },
    open: { type: Boolean, default: undefined },
    modal: { type: Boolean, default: true },
})

const emit = defineEmits(['update:open'])
</script>

<template>
    <DialogRoot
        :default-open="defaultOpen"
        :open="open"
        :modal="modal"
        @update:open="(v) => emit('update:open', v)"
    >
        <slot />
    </DialogRoot>
</template>
