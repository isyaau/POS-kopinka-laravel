<script setup>
import { Coffee } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import SidebarNav from './SidebarNav.vue'

defineProps({
    collapsed: { type: Boolean, default: false },
    mobileOpen: { type: Boolean, default: false },
})

const year = new Date().getFullYear()
</script>

<template>
    <!-- Desktop sidebar -->
    <aside
        :class="cn(
            'bg-sidebar text-sidebar-foreground border-sidebar-border sticky top-0 z-30 hidden h-svh flex-col border-r transition-all duration-300 lg:flex',
            collapsed ? 'w-16' : 'w-64',
        )"
    >
        <div class="flex h-16 shrink-0 items-center gap-2 px-4">
            <div class="bg-primary text-primary-foreground flex size-9 shrink-0 items-center justify-center rounded-lg">
                <Coffee class="size-5" />
            </div>
            <div v-if="!collapsed" class="min-w-0">
                <p class="truncate text-sm font-bold">POS Kopinka</p>
                <p class="text-sidebar-foreground/50 text-xs">Multi-Toko</p>
            </div>
        </div>
        <SidebarNav :collapsed="collapsed" />
        <div class="p-4">
            <p v-if="!collapsed" class="text-sidebar-foreground/40 px-3 text-[10px]">
                © {{ year }} Kopinka
            </p>
        </div>
    </aside>
</template>
