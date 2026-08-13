<script setup>
import { ref, computed, nextTick, onBeforeUnmount, watch } from 'vue'
import { Search, ChevronsUpDown, Check } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    // options: [{ value, label, sublabel }]
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Pilih...' },
    disabled: { type: Boolean, default: false },
    class: { type: [String, Array], default: '' },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const query = ref('')
const triggerRef = ref(null)
const dropdownRef = ref(null)

const selected = computed(
    () => props.options.find((o) => String(o.value) === String(props.modelValue)) || null,
)

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) return props.options
    return props.options.filter(
        (o) =>
            String(o.label || '').toLowerCase().includes(q) ||
            String(o.sublabel || '').toLowerCase().includes(q),
    )
})

const toggle = () => {
    if (props.disabled) return
    open.value = !open.value
    if (open.value) {
        query.value = ''
        nextTick(() => {
            // Fokus input + pastikan dropdown terlihat (auto-scroll)
            dropdownRef.value?.querySelector('input')?.focus()
            dropdownRef.value?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
        })
    }
}

const select = (value) => {
    emit('update:modelValue', value)
    open.value = false
    query.value = ''
}

// Tutup saat klik di luar komponen
const onClickOutside = (e) => {
    if (!open.value) return
    if (triggerRef.value?.contains(e.target)) return
    if (dropdownRef.value?.contains(e.target)) return
    open.value = false
    query.value = ''
}

watch(open, (val) => {
    if (val) {
        document.addEventListener('click', onClickOutside, true)
    } else {
        document.removeEventListener('click', onClickOutside, true)
    }
})

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside, true)
})
</script>

<template>
    <div class="relative" :class="props.class">
        <!-- Tombol tampilan pilihan -->
        <button
            ref="triggerRef"
            type="button"
            :disabled="disabled"
            @click.stop="toggle"
            class="border-input bg-background hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-10 w-full min-w-0 items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
        >
            <span v-if="selected" class="truncate text-left font-medium">{{ selected.label }}</span>
            <span v-else class="text-muted-foreground truncate text-left">{{ placeholder }}</span>
            <ChevronsUpDown class="size-4 shrink-0 opacity-50" />
        </button>

        <!-- Dropdown pencarian (inline absolute — aman dari focus trap dialog) -->
        <div
            v-if="open"
            ref="dropdownRef"
            class="bg-popover text-popover-foreground absolute top-full right-0 left-0 z-50 mt-1 rounded-md border shadow-lg"
        >
            <div class="p-2 pb-1">
                <div class="relative">
                    <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2" />
                    <Input
                        v-model="query"
                        placeholder="Cari..."
                        class="h-9 pl-8"
                    />
                </div>
            </div>
            <div class="max-h-56 overflow-y-auto p-1">
                <button
                    v-for="o in filtered"
                    :key="o.value"
                    type="button"
                    class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                    @mousedown.prevent="select(o.value)"
                >
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-medium">{{ o.label }}</span>
                        <span v-if="o.sublabel" class="text-muted-foreground block truncate text-xs">
                            {{ o.sublabel }}
                        </span>
                    </span>
                    <Check
                        v-if="String(o.value) === String(props.modelValue)"
                        class="text-primary size-4 shrink-0"
                    />
                </button>
                <p v-if="filtered.length === 0" class="text-muted-foreground px-2 py-3 text-center text-xs">
                    Tidak ada hasil ditemukan
                </p>
            </div>
        </div>
    </div>
</template>
