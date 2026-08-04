<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
</script>

<template>
    <AppLayout>
        <Head title="Laporan - Kopinka" />

        <PageHeader
            title="Laporan"
            description="Laporan penjualan per toko atau gabungan semua toko."
        />

        <Card>
            <CardHeader>
                <CardTitle>Modul Laporan</CardTitle>
                <CardDescription>
                    Laporan penjualan, stok, dan laba akan dibangun di sini.
                </CardDescription>
            </CardHeader>
            <CardContent class="text-muted-foreground text-sm">
                Mode "Semua Toko" menampilkan kolom toko per baris.
            </CardContent>
        </Card>
    </AppLayout>
</template>
