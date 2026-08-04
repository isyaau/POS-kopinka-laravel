<script setup>
import { computed } from 'vue'
import { usePage, useForm } from '@inertiajs/vue3'
import { Store as StoreIcon, Building2, ChevronsUpDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

const page = usePage()
const auth = computed(() => page.props.auth || {})
const current = computed(() => auth.value.current_store || {})
const stores = computed(() => auth.value.stores || [])

const form = useForm({ store_id: current.value.id })

const isKasir = computed(() => {
    const roles = auth.value.roles || []
    return roles.includes('kasir') && !roles.includes('admin') && !roles.includes('super-admin')
})

const switchStore = (storeId) => {
    form.transform((data) => ({ ...data, store_id: storeId })).post('/store/switch', {
        preserveScroll: true,
    })
}
</script>

<template>
    <DropdownMenu v-if="!isKasir && stores.length > 0">
        <DropdownMenuTrigger as-child>
            <Button variant="outline" class="h-9 w-auto gap-2 px-3">
                <component
                    :is="current.tipe === 'pusat' || current.id === 'all' ? Building2 : StoreIcon"
                    class="text-primary size-4 shrink-0"
                />
                <span class="hidden max-w-40 truncate sm:inline">{{ current.nama || 'Semua Toko' }}</span>
                <span class="sm:hidden">{{ current.kode || 'ALL' }}</span>
                <ChevronsUpDown class="text-muted-foreground size-3.5 shrink-0" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel class="text-muted-foreground text-xs font-normal">
                Pilih Toko
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <button
                v-for="store in stores"
                :key="store.id"
                class="hover:bg-accent focus:bg-accent flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm"
                @click="switchStore(store.id)"
            >
                <component
                    :is="store.tipe === 'pusat' || store.id === 'all' ? Building2 : StoreIcon"
                    class="text-primary size-4 shrink-0"
                />
                <span class="flex-1 truncate">{{ store.nama }}</span>
                <span v-if="store.id === current.id" class="bg-primary text-primary-foreground rounded px-1.5 text-[10px]">AKTIF</span>
            </button>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
