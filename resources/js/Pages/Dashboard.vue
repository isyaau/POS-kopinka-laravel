<script setup>
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import { Store as StoreIcon, Building2, TrendingUp, Package, ShoppingCart } from 'lucide-vue-next'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const page = usePage()
const auth = computed(() => page.props.auth || {})
const stats = computed(() => page.props.stats || {})
const currentStore = computed(() => stats.value.current_store || {})
const user = computed(() => auth.value.user || {})
const role = computed(() => (auth.value.roles || [])[0] || 'pengguna')
</script>

<template>
    <AppLayout>
        <Head title="Dashboard - POS Kopinka" />

        <PageHeader
            title="Dashboard"
            :description="`Selamat datang kembali, ${user.name || 'Pengguna'} — ${currentStore.nama || 'Pilih toko'} (${currentStore.kode || '-'})`"
        >
            <template #actions>
                <Badge variant="secondary" class="capitalize">{{ role }}</Badge>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Toko Aktif</CardTitle>
                    <Building2 class="text-primary size-4" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">{{ currentStore.nama || '-' }}</p>
                    <p class="text-muted-foreground text-xs">{{ currentStore.kode || '-' }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Total Toko</CardTitle>
                    <StoreIcon class="text-primary size-4" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">{{ stats.total_stores || 0 }}</p>
                    <p class="text-muted-foreground text-xs">1 pusat + 5 toko</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Produk</CardTitle>
                    <Package class="text-primary size-4" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">—</p>
                    <p class="text-muted-foreground text-xs">Modul segera hadir</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">Transaksi Hari Ini</CardTitle>
                    <ShoppingCart class="text-primary size-4" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold">—</p>
                    <p class="text-muted-foreground text-xs">Modul segera hadir</p>
                </CardContent>
            </Card>
        </div>

        <Card class="mt-6">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <TrendingUp class="text-primary size-5" />
                    Selamat Datang di POS Kopinka
                </CardTitle>
                <CardDescription>
                    Sistem kasir multi-toko untuk 1 kantor pusat dan 5 toko Kopinka.
                </CardDescription>
            </CardHeader>
            <CardContent class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-muted-foreground max-w-2xl text-sm">
                    Gunakan menu di samping untuk mengelola transaksi, produk, laporan, dan pengguna.
                    Sebagai admin, Anda dapat berpindah toko melalui dropdown di header.
                </p>
                <Button as="a" href="/pos" class="h-11 shrink-0" size="lg">
                    Buka Kasir
                </Button>
            </CardContent>
        </Card>
    </AppLayout>
</template>
