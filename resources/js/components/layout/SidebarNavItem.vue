<script setup>
import { Link } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'

const props = defineProps({
    item: { type: Object, required: true },
    collapsed: { type: Boolean, default: false },
    active: { type: Boolean, default: false },
})
</script>

<template>
    <Link
        :href="item.href"
        :class="cn(
            'group/menu-button flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
            'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
            active
                ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                : 'text-sidebar-foreground/80',
            collapsed && 'justify-center px-2',
        )"
        :title="collapsed ? item.title : undefined"
    >
        <component :is="item.icon" class="size-4 shrink-0" />
        <span v-if="!collapsed" class="flex-1 truncate">{{ item.title }}</span>
        <Badge
            v-if="item.badge && !collapsed"
            variant="secondary"
            class="bg-sidebar-accent text-sidebar-accent-foreground"
        >
            {{ item.badge }}
        </Badge>
    </Link>
</template>
