<script setup>
import { AvatarRoot } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
})
</script>

<template>
    <AvatarRoot :class="cn('relative flex size-8 shrink-0 overflow-hidden rounded-full', props.class)">
        <slot />
    </AvatarRoot>
</template>
