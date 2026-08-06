<script setup>
import { ref, computed, provide, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import SidebarNav from './SidebarNav.vue'
import { Toaster, toast } from '@/components/ui/sonner'
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

const props = defineProps({
    contentFill: { type: Boolean, default: false },
})

const toggleCollapse = () => {
    collapsed.value = !collapsed.value
}

const toggleMobile = () => {
    mobileOpen.value = !mobileOpen.value
}

const mainClass = computed(() =>
    cn('flex min-w-0 flex-1 flex-col transition-all duration-300', collapsed.value ? 'lg:pl-16' : 'lg:pl-64'),
)

// Tampilkan toast global dari flash message (semua halaman).
// Dipicu saat URL berubah (navigasi Inertia) — membaca flash yang baru dirender.
const page = usePage()
watch(
    () => page.url,
    () => {
        const flash = page.props.flash
        if (flash?.success) toast.success(flash.success)
        if (flash?.error) toast.error(flash.error)
    },
)
</script>

<template>
    <div class="flex h-svh w-full overflow-hidden bg-background">
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
            <main
                class="flex min-h-0 flex-1 flex-col overflow-y-auto"
                :class="props.contentFill ? 'p-0' : 'px-4 py-4 md:px-6 md:py-5 lg:px-8 lg:py-6'"
            >
                <div class="flex min-h-0 flex-1 flex-col">
                    <slot />
                </div>
            </main>
            <footer
                v-if="!props.contentFill"
                class="text-muted-foreground flex shrink-0 flex-col gap-1 border-t bg-background px-4 py-3 text-[11px] sm:flex-row sm:items-center sm:justify-between md:px-6 lg:px-8"
            >
                <p class="flex items-center gap-1.5">
                    <Coffee class="size-3" />
                    © 2026 POS Kopinka — Sistem Kasir Multi-Toko
                </p>
                <p>v1.0.0</p>
            </footer>
        </div>

        <Toaster />
    </div>
</template>
