<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({ class: { type: [String, Array], default: '' } })
</script>

<template>
    <div :class="cn('leading-none font-semibold', props.class)" :data-slot="'card-title'">
        <slot />
    </div>
</template>
