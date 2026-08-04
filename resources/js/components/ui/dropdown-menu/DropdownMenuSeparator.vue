<script setup>
import { DropdownMenuSeparator } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({ class: { type: [String, Array], default: '' } })
</script>

<template>
    <DropdownMenuSeparator
        :class="cn('bg-muted -mx-1 my-1 h-px', props.class)"
    />
</template>
