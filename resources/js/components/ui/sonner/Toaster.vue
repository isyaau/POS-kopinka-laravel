<script setup>
import { Toaster as Sonner } from 'vue-sonner'

defineProps({
    position: { type: String, default: 'top-right' },
    theme: { type: String, default: 'system' },
})
</script>

<template>
    <Sonner :position="position" :theme="theme" rich-colors />
</template>
