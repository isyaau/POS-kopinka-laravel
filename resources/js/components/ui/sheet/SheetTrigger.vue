<script setup>
import { DialogTrigger } from 'radix-vue'

defineProps({
    asChild: { type: Boolean, default: true },
})
</script>

<template>
    <DialogTrigger as-child>
        <slot />
    </DialogTrigger>
</template>
