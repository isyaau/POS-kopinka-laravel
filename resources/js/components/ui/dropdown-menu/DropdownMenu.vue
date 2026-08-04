<script setup>
import { DropdownMenuRoot } from 'radix-vue'

defineProps({ open: { type: Boolean, default: undefined } })
</script>

<template>
    <DropdownMenuRoot :open="open">
        <slot />
    </DropdownMenuRoot>
</template>
