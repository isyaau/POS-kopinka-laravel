<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Shield,
    Plus,
    MoreHorizontal,
    Pencil,
    Trash2,
    Users,
    Lock,
    Sparkles,
    Eye,
} from 'lucide-vue-next'
import RoleFormModal from './RoleFormModal.vue'
import RoleDeleteModal from './RoleDeleteModal.vue'
import RoleDetailModal from './RoleDetailModal.vue'

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
})

const formOpen = ref(false)
const editingRole = ref(null)
const deleteOpen = ref(false)
const deletingRole = ref(null)
const detailOpen = ref(false)
const selectedRole = ref(null)

const openCreate = () => {
    editingRole.value = null
    formOpen.value = true
}

const openEdit = (role) => {
    editingRole.value = role
    formOpen.value = true
}

const openDelete = (role) => {
    deletingRole.value = role
    deleteOpen.value = true
}

const openDetail = (role) => {
    selectedRole.value = role
    detailOpen.value = true
}

const roleIcon = (role) => {
    if (role.is_system) return Lock
    return Shield
}
</script>

<template>
    <AppLayout>
        <Head title="Role - Kopinka" />

        <PageHeader title="Role" description="Kelola peran pengguna dan hak aksesnya.">
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    Tambah Role
                </Button>
            </template>
        </PageHeader>

        <Card class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden py-0">
            <CardContent class="border-b py-4">
                <p class="text-muted-foreground text-sm">
                    {{ roles.length }} role terdaftar — role sistem tidak dapat diubah/dihapus.
                </p>
            </CardContent>

            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="roles.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[820px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Role</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Pengguna</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Hak Akses</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="role in roles" :key="role.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="flex size-8 shrink-0 items-center justify-center rounded-lg"
                                                :class="role.is_system ? 'bg-muted text-muted-foreground' : 'bg-primary/10 text-primary'"
                                            >
                                                <component :is="roleIcon(role)" class="size-4" />
                                            </div>
                                            <div>
                                                <p class="flex items-center gap-1.5 font-medium">
                                                    {{ role.label }}
                                                    <Badge v-if="role.is_system" variant="secondary" class="text-[10px]">
                                                        Sistem
                                                    </Badge>
                                                </p>
                                                <p class="text-muted-foreground text-xs">{{ role.name }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <span class="text-muted-foreground flex items-center gap-1.5">
                                            <Users class="size-3.5" />
                                            {{ role.users_count }} pengguna
                                        </span>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <div class="flex max-w-md flex-wrap gap-1">
                                            <template v-if="role.permissions.length">
                                                <Badge v-for="p in role.permissions.slice(0, 5)" :key="p" variant="outline" class="font-mono text-[10px]">
                                                    {{ p }}
                                                </Badge>
                                                <Badge v-if="role.permissions.length > 5" variant="secondary" class="text-[10px]">
                                                    +{{ role.permissions.length - 5 }}
                                                </Badge>
                                            </template>
                                            <span v-else class="text-muted-foreground text-xs">Tidak ada hak akses</span>
                                        </div>
                                    </td>
                                    <td class="border-b px-4 py-3 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" class="size-8">
                                                    <MoreHorizontal class="size-4" />
                                                    <span class="sr-only">Aksi</span>
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-44">
                                                <DropdownMenuLabel class="text-muted-foreground text-xs">
                                                    {{ role.label }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem @click="openDetail(role)">
                                                    <Eye class="size-4" />
                                                    Detail
                                                </DropdownMenuItem>
                                                <DropdownMenuItem :disabled="role.is_system" @click="openEdit(role)">
                                                    <Pencil class="size-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuItem variant="destructive" :disabled="role.is_system" @click="openDelete(role)">
                                                    <Trash2 class="size-4" />
                                                    Hapus
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <div v-else class="flex flex-1 flex-col items-center justify-center gap-3 py-16 text-center">
                    <div class="bg-muted flex size-14 items-center justify-center rounded-full">
                        <Shield class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada role</p>
                        <p class="text-muted-foreground text-sm">Klik "Tambah Role" untuk membuat role pertama.</p>
                    </div>
                </div>
            </div>
        </Card>

        <RoleFormModal :open="formOpen" :role="editingRole" :permissions="permissions" @update:open="formOpen = $event" />
        <RoleDeleteModal :open="deleteOpen" :role="deletingRole" @update:open="deleteOpen = $event" />
        <RoleDetailModal :open="detailOpen" :role="selectedRole" @update:open="detailOpen = $event" />
    </AppLayout>
</template>
