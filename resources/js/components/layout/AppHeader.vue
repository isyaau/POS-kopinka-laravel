<script setup>
import { computed } from 'vue'
import { usePage, useForm } from '@inertiajs/vue3'
import { Menu, PanelLeft, Sun, Moon, LogOut, CircleUser } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useTheme } from '@/composables/useTheme'
import StoreSwitcher from './StoreSwitcher.vue'

defineProps({
    onMenuClick: { type: Function, required: true },
    onToggleCollapse: { type: Function, required: true },
    collapsed: { type: Boolean, default: false },
})

const page = usePage()
const auth = computed(() => page.props.auth || {})
const user = computed(() => auth.value.user || {})
const { theme, toggleTheme } = useTheme()

const logout = useForm({})

const submitLogout = () => {
    logout.post('/logout')
}
</script>

<template>
    <header
        class="bg-background/80 border-border sticky top-0 z-20 flex h-16 shrink-0 items-center gap-2 border-b px-4 backdrop-blur-md lg:px-6"
    >
        <!-- Mobile: hamburger -->
        <Button variant="ghost" size="icon" class="lg:hidden" @click="onMenuClick">
            <Menu class="size-5" />
            <span class="sr-only">Buka menu</span>
        </Button>

        <!-- Desktop: collapse toggle -->
        <Button
            variant="ghost"
            size="icon"
            class="hidden lg:inline-flex"
            @click="onToggleCollapse"
        >
            <PanelLeft class="size-5" />
            <span class="sr-only">Ciutkan sidebar</span>
        </Button>

        <!-- Store switcher -->
        <div class="mx-1 lg:ml-2">
            <StoreSwitcher />
        </div>

        <div class="ml-auto flex items-center gap-1.5">
            <!-- Theme toggle -->
            <Button variant="ghost" size="icon" @click="toggleTheme">
                <Sun v-if="theme === 'light'" class="size-5" />
                <Moon v-else class="size-5" />
                <span class="sr-only">Ganti tema</span>
            </Button>

            <!-- Profile dropdown -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" class="h-9 gap-2 px-2">
                        <Avatar class="size-8">
                            <AvatarFallback class="bg-primary text-primary-foreground">
                                {{ (user.name || 'U').charAt(0).toUpperCase() }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="hidden text-left md:block">
                            <p class="max-w-36 truncate text-sm font-medium leading-tight">
                                {{ user.name || 'Pengguna' }}
                            </p>
                            <p class="text-muted-foreground text-xs capitalize leading-tight">
                                {{ (auth.roles || []).join(', ') || 'role' }}
                            </p>
                        </div>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <DropdownMenuLabel>
                        <p class="truncate text-sm font-medium">{{ user.name }}</p>
                        <p class="text-muted-foreground truncate text-xs">{{ user.email }}</p>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="submitLogout" variant="destructive">
                        <LogOut class="size-4" />
                        Keluar
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
