<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
</script>

<template>
    <AppLayout>
        <Head title="Kasir / POS - Kopinka" />

        <PageHeader
            title="Kasir / POS"
            description="Halaman transaksi kasir akan dibangun di sini."
        />

        <Card>
            <CardHeader>
                <CardTitle>Modul Kasir</CardTitle>
                <CardDescription>
                    Keranjang, pembayaran, dan struk akan tersedia setelah modul POS dibangun.
                </CardDescription>
            </CardHeader>
            <CardContent class="text-muted-foreground text-sm">
                Komponen &amp; layout sudah siap — tinggal integrasi fitur.
            </CardContent>
        </Card>
    </AppLayout>
</template>
