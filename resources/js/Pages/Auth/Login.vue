<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Coffee, LogIn } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Toaster } from '@/components/ui/sonner'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Masuk - POS Kopinka" />

    <Toaster />

    <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-4">
        <div class="flex items-center gap-3">
            <div class="bg-primary text-primary-foreground flex size-11 items-center justify-center rounded-xl shadow-lg">
                <Coffee class="size-6" />
            </div>
            <div>
                <h1 class="text-foreground text-xl font-bold">POS Kopinka</h1>
                <p class="text-muted-foreground text-sm">Sistem Kasir Multi-Toko</p>
            </div>
        </div>

        <Card class="w-full max-w-md">
            <CardHeader>
                <CardTitle class="text-2xl">Masuk</CardTitle>
                <CardDescription>
                    Gunakan akun kasir atau admin Anda.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="flex flex-col gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="nama@kopinka.com"
                            autocomplete="username"
                            :aria-invalid="!!form.errors.email"
                        />
                        <p v-if="form.errors.email" class="text-destructive text-sm">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                        />
                        <p v-if="form.errors.password" class="text-destructive text-sm">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.remember" type="checkbox" class="text-primary size-4 rounded" />
                        <span class="text-muted-foreground">Ingat saya</span>
                    </label>

                    <Button type="submit" class="h-11 w-full" size="lg" :disabled="form.processing">
                        <LogIn v-if="!form.processing" class="size-4" />
                        <span v-if="form.processing">Memproses...</span>
                        <span v-else>Masuk</span>
                    </Button>
                </form>

                <div class="bg-muted text-muted-foreground mt-6 rounded-md p-3 text-xs">
                    <p class="mb-1 font-semibold text-foreground">Akun demo:</p>
                    <p>admin@kopinka.test / admin123 (Admin)</p>
                    <p>kasir@kopinka.test / admin123 (Kasir)</p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
