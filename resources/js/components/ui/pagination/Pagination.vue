<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
} from '@/components/ui/select'
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next'

const props = defineProps({
    current: { type: Number, default: 1 },
    last: { type: Number, default: 1 },
    total: { type: Number, default: 0 },
    from: { type: Number, default: 0 },
    to: { type: Number, default: 0 },
    limit: { type: Number, default: 15 },
    // Base URL untuk navigasi (mis. '/anggota')
    baseUrl: { type: String, required: true },
    // Parameter query yang sudah aktif (search, tanggal) — dipertahankan
    query: { type: Object, default: () => ({}) },
})

const limitOptions = [10, 25, 50, 100]

// Nomor halaman yang ditampilkan: 1 2 3 4 ... 9 (dengan ellipsis)
const pages = computed(() => {
    const total = props.last
    const current = props.current
    const range = []
    const rangeWithDots = []
    let l

    for (let i = 1; i <= total; i++) {
        if (
            i === 1 ||
            i === total ||
            (i >= current - 2 && i <= current + 2)
        ) {
            range.push(i)
        }
    }

    range.forEach((i) => {
        if (l) {
            if (i - l === 2) {
                rangeWithDots.push(l + 1)
            } else if (i - l !== 1) {
                rangeWithDots.push('...')
            }
        }
        rangeWithDots.push(i)
        l = i
    })

    return rangeWithDots
})

const buildUrl = (params) => {
    const q = new URLSearchParams()
    for (const [key, value] of Object.entries({ ...props.query, ...params })) {
        if (value !== '' && value !== undefined && value !== null) {
            q.set(key, value)
        }
    }
    const qs = q.toString()
    return qs ? `${props.baseUrl}?${qs}` : props.baseUrl
}

const goTo = (page) => {
    if (page < 1 || page > props.last || page === props.current) return
    router.get(buildUrl({ page }), {}, { preserveState: true, replace: true })
}

const changeLimit = (limit) => {
    router.get(buildUrl({ limit, page: 1 }), {}, { preserveState: true, replace: true })
}
</script>

<template>
    <div class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between">
        <!-- Info & Limit -->
        <div class="flex flex-wrap items-center gap-3">
            <p class="text-muted-foreground text-xs">
                Menampilkan <b class="text-foreground">{{ from || 0 }}</b>–<b class="text-foreground">{{ to || 0 }}</b>
                dari <b class="text-foreground">{{ total }}</b> data
            </p>
            <div class="flex items-center gap-1.5">
                <span class="text-muted-foreground text-xs">Limit</span>
                <Select :model-value="String(limit)" @update:model-value="changeLimit(Number($event))">
                    <SelectTrigger class="h-8 w-[4.5rem] text-xs" :placeholder="String(limit)">
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="opt in limitOptions" :key="opt" :value="String(opt)">
                            {{ opt }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <span class="text-muted-foreground text-xs">/ halaman</span>
            </div>
        </div>

        <!-- Navigasi halaman -->
        <div class="flex items-center gap-1">
            <Button
                variant="outline"
                size="sm"
                class="size-8 p-0"
                :disabled="current <= 1"
                aria-label="Halaman pertama"
                @click="goTo(1)"
            >
                <ChevronsLeft class="size-4" />
            </Button>
            <Button
                variant="outline"
                size="sm"
                class="size-8 p-0"
                :disabled="current <= 1"
                aria-label="Halaman sebelumnya"
                @click="goTo(current - 1)"
            >
                <ChevronLeft class="size-4" />
            </Button>

            <div class="mx-1 flex items-center gap-1">
                <template v-for="(p, idx) in pages" :key="idx">
                    <span v-if="p === '...'" class="text-muted-foreground px-1 text-xs">…</span>
                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        class="size-8 p-0 text-xs"
                        :class="p === current ? 'bg-primary text-primary-foreground pointer-events-none border-primary' : ''"
                        @click="goTo(p)"
                    >
                        {{ p }}
                    </Button>
                </template>
            </div>

            <Button
                variant="outline"
                size="sm"
                class="size-8 p-0"
                :disabled="current >= last"
                aria-label="Halaman berikutnya"
                @click="goTo(current + 1)"
            >
                <ChevronRight class="size-4" />
            </Button>
            <Button
                variant="outline"
                size="sm"
                class="size-8 p-0"
                :disabled="current >= last"
                aria-label="Halaman terakhir"
                @click="goTo(last)"
            >
                <ChevronsRight class="size-4" />
            </Button>
        </div>
    </div>
</template>
