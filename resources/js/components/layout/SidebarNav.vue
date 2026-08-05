<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { getNavigation } from '@/config/navigation'
import SidebarNavItem from './SidebarNavItem.vue'

const props = defineProps({
    collapsed: { type: Boolean, default: false },
})

const page = usePage()
const auth = computed(() => page.props.auth || {})
const permissions = computed(() => auth.value.permissions || [])
const groups = computed(() => getNavigation(permissions.value))

const isActive = (href) => {
    if (href === '/dashboard') return page.url === '/dashboard' || page.url === '/'
    return page.url.startsWith(href)
}
</script>

<template>
    <nav class="sidebar-scroll flex flex-1 flex-col gap-6 overflow-y-auto px-3 py-4">
        <div v-for="group in groups" :key="group.title" class="flex flex-col gap-1">
            <p
                v-if="!collapsed"
                class="text-sidebar-foreground/50 px-3 pb-1 text-xs font-semibold tracking-wide uppercase"
            >
                {{ group.title }}
            </p>
            <SidebarNavItem
                v-for="item in group.items"
                :key="item.href"
                :item="item"
                :collapsed="collapsed"
                :active="isActive(item.href)"
            />
        </div>
    </nav>
</template>
