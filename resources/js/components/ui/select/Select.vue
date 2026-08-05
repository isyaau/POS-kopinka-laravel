<script setup>
import { SelectRoot } from 'radix-vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    defaultValue: { type: [String, Number], default: '' },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <SelectRoot
        :model-value="modelValue"
        :default-value="defaultValue"
        :disabled="disabled"
        @update:model-value="(v) => emit('update:modelValue', v)"
    >
        <slot />
    </SelectRoot>
</template>
