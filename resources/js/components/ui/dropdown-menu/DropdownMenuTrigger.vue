<script setup>
import { DropdownMenuTrigger } from 'radix-vue'

defineProps({ asChild: { type: Boolean, default: true } })
</script>

<template>
    <DropdownMenuTrigger as-child>
        <slot />
    </DropdownMenuTrigger>
</template>
