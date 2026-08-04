<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({ class: { type: [String, Array], default: '' } })
</script>

<template>
    <div :class="cn('flex items-center px-6', props.class)" :data-slot="'card-footer'">
        <slot />
    </div>
</template>
