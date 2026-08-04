<script setup>
import { DialogTitle } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({ class: { type: [String, Array], default: '' } })
</script>

<template>
    <DialogTitle :class="cn('text-lg font-semibold', props.class)">
        <slot />
    </DialogTitle>
</template>
