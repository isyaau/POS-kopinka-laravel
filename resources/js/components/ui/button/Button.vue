<script setup>
import { Primitive } from 'radix-vue'
import { cn } from '@/lib/utils'
import { buttonVariants } from './buttonVariants'

const props = defineProps({
    variant: { type: String, default: 'default' },
    size: { type: String, default: 'default' },
    as: { type: [String, Object], default: 'button' },
    asChild: { type: Boolean, default: false },
    class: { type: [String, Array], default: '' },
})

const computedClass = () =>
    cn(buttonVariants({ variant: props.variant, size: props.size }), props.class)
</script>

<template>
    <Primitive :as="as" :as-child="asChild" :class="computedClass()">
        <slot />
    </Primitive>
</template>
