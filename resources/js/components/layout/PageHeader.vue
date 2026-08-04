<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
})
</script>

<template>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-foreground truncate text-2xl font-bold tracking-tight sm:text-3xl">
                {{ title }}
            </h1>
            <p v-if="description" class="text-muted-foreground mt-1 text-sm">
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
