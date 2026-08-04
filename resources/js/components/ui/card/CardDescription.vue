<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({ class: { type: [String, Array], default: '' } })
</script>

<template>
    <div :class="cn('text-muted-foreground text-sm', props.class)" :data-slot="'card-description'">
        <slot />
    </div>
</template>
