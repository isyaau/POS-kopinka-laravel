<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Pagination } from '@/components/ui/pagination'
import {
    Users,
    Plus,
    Search,
    MoreHorizontal,
    Pencil,
    Trash2,
    Store,
    Mail,
    X,
    Shield,
    Eye,
} from 'lucide-vue-next'
import UserFormModal from './UserFormModal.vue'
import UserDeleteModal from './UserDeleteModal.vue'
import UserDetailModal from './UserDetailModal.vue'

const props = defineProps({
    users: { type: Object, default: () => ({ data: [] }) },
    roles: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const formOpen = ref(false)
const editingUser = ref(null)
const deleteOpen = ref(false)
const deletingUser = ref(null)
const detailOpen = ref(false)
const selectedUser = ref(null)
const search = ref(props.filters.search || '')

let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get('/pengguna', { search: search.value }, { preserveState: true, replace: true })
    }, 400)
}

const resetFilters = () => {
    search.value = ''
    router.get('/pengguna', {}, { preserveState: true, replace: true })
}

const openCreate = () => {
    editingUser.value = null
    formOpen.value = true
}

const openEdit = (user) => {
    editingUser.value = user
    formOpen.value = true
}

const openDelete = (user) => {
    deletingUser.value = user
    deleteOpen.value = true
}

const openDetail = (user) => {
    selectedUser.value = user
    detailOpen.value = true
}

const items = computed(() => props.users?.data || [])
const pagination = computed(() => {
    const s = props.users || {}
    return {
        current: s.current_page || 1,
        last: s.last_page || 1,
        total: s.total || 0,
        from: s.from || 0,
        to: s.to || 0,
    }
})

const roleLabel = (name) => {
    const map = {
        'super-admin': 'Super Admin',
        admin: 'Admin',
        kasir: 'Kasir',
        manager: 'Manager',
        'kepala-toko': 'Kepala Toko',
        akuntansi: 'Akuntansi',
    }
    return map[name] || name
}
</script>

<template>
    <AppLayout>
        <Head title="Pengguna - Kopinka" />

        <PageHeader title="Pengguna" description="Kelola pengguna, role, dan akses toko.">
            <template #actions>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    Tambah Pengguna
                </Button>
            </template>
        </PageHeader>

        <Card class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden py-0">
            <CardContent class="border-b py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="relative w-full lg:max-w-xs">
                        <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            v-model="search"
                            placeholder="Cari nama atau email..."
                            class="pl-9"
                            @input="onSearchInput"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <Button v-if="search" variant="ghost" size="sm" @click="resetFilters">
                            <X class="size-4" />
                            Reset
                        </Button>
                        <p class="text-muted-foreground text-sm">{{ pagination.total }} pengguna</p>
                    </div>
                </div>
            </CardContent>

            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <template v-if="items.length">
                    <div class="min-h-0 flex-1 overflow-auto overscroll-contain">
                        <table class="w-full min-w-[900px] border-separate border-spacing-0 text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Nama</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Role</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Toko Utama</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-left text-xs font-semibold uppercase">Akses Toko Lain</th>
                                    <th class="bg-muted text-muted-foreground border-b px-4 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in items" :key="user.id" class="hover:bg-muted/40 transition-colors">
                                    <td class="border-b px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="bg-primary/10 text-primary flex size-8 shrink-0 items-center justify-center rounded-full">
                                                <Users class="size-4" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium truncate">{{ user.name }}</p>
                                                <p class="text-muted-foreground flex items-center gap-1 text-xs">
                                                    <Mail class="size-3" />
                                                    {{ user.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            <Badge v-for="r in user.roles" :key="r" variant="secondary">
                                                <Shield class="size-3" />
                                                {{ roleLabel(r) }}
                                            </Badge>
                                            <span v-if="!user.roles.length" class="text-muted-foreground text-xs">-</span>
                                        </div>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <span v-if="user.store" class="flex items-center gap-1.5">
                                            <Store class="text-muted-foreground size-3.5" />
                                            <span class="font-medium">{{ user.store.nama }}</span>
                                            <span class="text-muted-foreground text-xs">{{ user.store.kode }}</span>
                                        </span>
                                        <span v-else class="text-muted-foreground text-xs">-</span>
                                    </td>
                                    <td class="border-b px-4 py-3">
                                        <div class="flex max-w-xs flex-wrap gap-1">
                                            <Badge v-for="s in user.stores" :key="s.id" variant="outline" class="text-[10px]">
                                                {{ s.kode }}
                                            </Badge>
                                            <span v-if="!user.stores.length" class="text-muted-foreground text-xs">-</span>
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
                                                    {{ user.email }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem @click="openDetail(user)">
                                                    <Eye class="size-4" />
                                                    Detail
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="openEdit(user)">
                                                    <Pencil class="size-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuItem variant="destructive" @click="openDelete(user)">
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
                        <Users class="text-muted-foreground size-7" />
                    </div>
                    <div>
                        <p class="font-semibold">Belum ada pengguna</p>
                        <p class="text-muted-foreground text-sm">Klik "Tambah Pengguna" untuk menambahkan.</p>
                    </div>
                </div>
            </div>

            <Pagination
                :current="pagination.current"
                :last="pagination.last"
                :total="pagination.total"
                :from="pagination.from"
                :to="pagination.to"
                :limit="Number(props.filters.limit || 10)"
                :base-url="'/pengguna'"
                :query="{ search: search }"
            />
        </Card>

        <UserFormModal
            :open="formOpen"
            :user="editingUser"
            :roles="roles"
            :stores="stores"
            @update:open="formOpen = $event"
        />
        <UserDeleteModal :open="deleteOpen" :user="deletingUser" @update:open="deleteOpen = $event" />
        <UserDetailModal :open="detailOpen" :user="selectedUser" @update:open="detailOpen = $event" />
    </AppLayout>
</template>
