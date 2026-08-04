<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
</script>

<template>
    <AppLayout>
        <Head title="Produk - Kopinka" />

        <PageHeader
            title="Produk"
            description="Kelola produk dan stok per toko."
        />

        <Card>
            <CardHeader>
                <CardTitle>Modul Produk</CardTitle>
                <CardDescription>
                    Daftar produk, kategori, harga, dan stok akan dibangun di sini.
                </CardDescription>
            </CardHeader>
            <CardContent class="text-muted-foreground text-sm">
                Data akan di-scope sesuai toko aktif.
            </CardContent>
        </Card>
    </AppLayout>
</template>
