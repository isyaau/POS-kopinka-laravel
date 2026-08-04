<script setup>
import { DialogContent, DialogClose } from 'radix-vue'
import { computed } from 'vue'
import { X } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { useSheetContext } from './inject'

defineOptions({ inheritAttrs: false })

const props = defineProps({ class: { type: [String, Array], default: '' } })

const ctx = useSheetContext()
const side = computed(() => ctx?.side() ?? 'left')

const sideClasses = {
    top: 'inset-x-0 top-0 border-b data-[state=closed]:slide-out-to-top data-[state=open]:slide-in-from-top',
    right: 'inset-y-0 right-0 h-full w-3/4 border-l data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right sm:max-w-sm',
    bottom: 'inset-x-0 bottom-0 border-t data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom',
    left: 'inset-y-0 left-0 h-full w-3/4 border-r data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left sm:max-w-sm',
}
</script>

<template>
    <DialogContent
        :class="cn(
            'bg-background data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:duration-300 data-[state=open]:duration-500 fixed z-50 flex flex-col gap-4 p-6 shadow-lg transition ease-in-out',
            sideClasses[side],
            props.class,
        )"
    >
        <slot />
        <DialogClose class="ring-offset-background focus:ring-ring absolute top-4 right-4 rounded-xs opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-offset-2 focus:outline-hidden disabled:pointer-events-none">
            <X class="size-4" />
            <span class="sr-only">Close</span>
        </DialogClose>
    </DialogContent>
</template>
