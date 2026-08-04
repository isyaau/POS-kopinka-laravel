<script setup>
import { ref, computed, provide } from 'vue'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import SidebarNav from './SidebarNav.vue'
import { Toaster } from '@/components/ui/sonner'
import { Coffee } from 'lucide-vue-next'
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet'
import { cn } from '@/lib/utils'

const collapsed = ref(false)
const mobileOpen = ref(false)

const toggleCollapse = () => {
    collapsed.value = !collapsed.value
}

const toggleMobile = () => {
    mobileOpen.value = !mobileOpen.value
}

const mainClass = computed(() =>
    cn('flex min-h-svh w-full flex-col transition-all duration-300', collapsed.value ? 'lg:pl-16' : 'lg:pl-64'),
)
</script>

<template>
    <div class="flex min-h-svh w-full bg-background">
        <!-- Desktop sidebar -->
        <div class="fixed inset-y-0 left-0 z-30 hidden lg:block">
            <AppSidebar :collapsed="collapsed" />
        </div>

        <!-- Mobile drawer (Sheet) -->
        <Sheet v-model:open="mobileOpen" side="left">
            <SheetContent class="bg-sidebar text-sidebar-foreground w-72 p-0">
                <SheetHeader class="flex h-16 flex-row items-center gap-2 px-4">
                    <div class="bg-primary text-primary-foreground flex size-9 items-center justify-center rounded-lg">
                        <Coffee class="size-5" />
                    </div>
                    <SheetTitle class="text-sidebar-foreground text-sm font-bold">
                        POS Kopinka
                    </SheetTitle>
                </SheetHeader>
                <SidebarNav :collapsed="false" />
            </SheetContent>
        </Sheet>

        <!-- Main column -->
        <div :class="mainClass">
            <AppHeader
                :on-menu-click="toggleMobile"
                :on-toggle-collapse="toggleCollapse"
                :collapsed="collapsed"
            />
            <main class="flex-1 p-4 md:p-6 lg:p-8">
                <slot />
            </main>
        </div>

        <Toaster />
    </div>
</template>
