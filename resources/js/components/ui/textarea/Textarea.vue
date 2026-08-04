<script setup>
import { cn } from '@/lib/utils'

defineOptions({ inheritAttrs: false })

const model = defineModel({ type: [String, Number], default: '' })

const props = defineProps({
    class: { type: [String, Array], default: '' },
})
</script>

<template>
    <textarea
        v-model="model"
        :class="cn(
            'flex field-sizing-content min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
            'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
            'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
            props.class,
        )"
        v-bind="$attrs"
    />
</template>
