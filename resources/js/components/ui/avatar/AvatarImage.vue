<script setup>
import { AvatarImage } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: [String, Array], default: '' },
    src: { type: String, default: '' },
    alt: { type: String, default: '' },
})
</script>

<template>
    <AvatarImage
        :src="props.src"
        :alt="props.alt"
        :class="cn('aspect-square size-full', props.class)"
    />
</template>
