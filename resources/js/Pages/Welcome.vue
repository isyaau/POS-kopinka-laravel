<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Coffee, ArrowRight, ShieldCheck, Store as StoreIcon, Smartphone } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

defineProps({ message: String })
</script>

<template>
    <Head title="Selamat Datang - POS Kopinka" />

    <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-8 p-4 sm:p-8">
        <!-- Brand -->
        <div class="flex flex-col items-center gap-3 text-center">
            <div class="bg-primary text-primary-foreground flex size-14 items-center justify-center rounded-2xl shadow-lg">
                <Coffee class="size-7" />
            </div>
            <div>
                <h1 class="text-foreground text-3xl font-bold sm:text-4xl">{{ message }}</h1>
                <p class="text-muted-foreground mt-2 text-sm sm:text-base">
                    Sistem POS Multi-Toko Kopinka — Laravel, Inertia &amp; Vue.
                </p>
            </div>
            <div class="flex flex-wrap justify-center gap-2">
                <Badge>Multi-Toko</Badge>
                <Badge variant="secondary">5 Toko + 1 Pusat</Badge>
                <Badge variant="outline">RBAC Spatie</Badge>
            </div>
        </div>

        <!-- Feature cards -->
        <div class="grid w-full max-w-4xl grid-cols-1 gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Smartphone class="text-primary size-4" />
                        Responsive
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <CardDescription>
                        Tampilan nyaman di PC, tablet, dan HP kasir.
                    </CardDescription>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <StoreIcon class="text-primary size-4" />
                        Multi-Toko
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <CardDescription>
                        Pindah antar toko dari header, pusat lihat gabungan.
                    </CardDescription>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <ShieldCheck class="text-primary size-4" />
                        Aman
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <CardDescription>
                        Role admin &amp; kasir dengan 5 permission Spatie.
                    </CardDescription>
                </CardContent>
            </Card>
        </div>

        <!-- CTA -->
        <div class="flex flex-col gap-3 sm:flex-row">
            <Button as="a" href="/login" size="lg" class="h-11">
                Masuk ke Aplikasi
                <ArrowRight class="size-4" />
            </Button>
            <Button as="a" href="/dashboard" variant="outline" size="lg" class="h-11">
                Lihat Dashboard
            </Button>
        </div>
    </div>
</template>