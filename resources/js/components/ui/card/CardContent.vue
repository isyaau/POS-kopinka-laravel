<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({ class: { type: [String, Array], default: '' } })
</script>

<template>
    <div :class="cn('px-6', props.class)" :data-slot="'card-content'">
        <slot />
    </div>
</template>
