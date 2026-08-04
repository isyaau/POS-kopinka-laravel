<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
</script>

<template>
    <AppLayout>
        <Head title="Pengguna - Kopinka" />

        <PageHeader
            title="Pengguna"
            description="Kelola pengguna dan hak akses (role & permission)."
        />

        <Card>
            <CardHeader>
                <CardTitle>Modul Pengguna</CardTitle>
                <CardDescription>
                    Manajemen user, role (admin/kasir), dan store assignment akan dibangun di sini.
                </CardDescription>
            </CardHeader>
            <CardContent class="text-muted-foreground text-sm">
                Terintegrasi dengan Spatie Laravel Permission.
            </CardContent>
        </Card>
    </AppLayout>
</template>
