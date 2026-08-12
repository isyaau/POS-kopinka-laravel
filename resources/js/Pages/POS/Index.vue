<script setup>
import { computed, ref, watch } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/components/layout/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
    Search,
    ShoppingCart,
    Plus,
    Minus,
    Trash2,
    X,
    Banknote,
    CreditCard,
    QrCode,
    Ticket,
    HandCoins,
    Package,
    Loader2,
    CheckCircle2,
    User,
    Receipt,
    Maximize2,
    CheckCheck,
    Printer,
} from 'lucide-vue-next'
import { toast } from '@/components/ui/sonner'
import { printStruk } from '@/lib/struk'

const props = defineProps({
    produk: { type: Array, default: () => [] },
    kategori_list: { type: Array, default: () => [] },
    anggota: { type: Array, default: () => [] },
    vouchers: { type: Array, default: () => [] },
    no_nota: { type: String, default: '' },
    filters: { type: Object, default: () => ({}) },
})

const page = usePage()

// Form checkout (top-level agar useForm valid)
const form = useForm({
    no_kasir: '',
    tanggal: '',
    anggota_id: '',
    nama_anggota: '',
    diskon: '',
    usaha: '',
    jasa: '',
    ppn: '',
    cash: '',
    qris: '',
    edc: '',
    voucher: '',
    voucher_ids: [],
    piutang: '',
    items: [],
})

// ===== State =====
const search = ref(props.filters.search || '')
const kategori = ref(props.filters.kategori || '')
const cart = ref([])
const anggotaId = ref('')
const anggotaNama = ref('')
const anggotaSearch = ref('')
const diskonHeader = ref('')
const usaha = ref('')
const jasa = ref('')
const ppn = ref('')
const pembayaran = ref({ cash: '', qris: '', edc: '', voucher: '', piutang: '' })
const piutangMode = ref(false)
const selectedVouchers = ref([])
const voucherInput = ref('')
const checkoutOpen = ref(false)
const checkoutDone = ref(false)
const lastNota = ref('')
const produkModalOpen = ref(false)
const produkModalSearch = ref('')
const produkModalKategori = ref('')
const lastTransaksi = ref(null)

const filteredProdukModal = computed(() => {
    let list = props.produk
    if (produkModalSearch.value.trim()) {
        const s = produkModalSearch.value.trim().toLowerCase()
        list = list.filter(
            (p) =>
                p.nama_barang?.toLowerCase().includes(s) ||
                p.kode_barang?.toLowerCase().includes(s),
        )
    }
    if (produkModalKategori.value) {
        list = list.filter((p) => p.kategori === produkModalKategori.value)
    }
    return list
})

const formatRupiah = (val) => {
    const n = Number(val || 0)
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(n)
}

// ===== Produk grid =====
const filteredProduk = computed(() => {
    let list = props.produk
    if (search.value.trim()) {
        const s = search.value.trim().toLowerCase()
        list = list.filter(
            (p) =>
                p.nama_barang?.toLowerCase().includes(s) ||
                p.kode_barang?.toLowerCase().includes(s),
        )
    }
    if (kategori.value) {
        list = list.filter((p) => p.kategori === kategori.value)
    }
    return list
})

// Dropdown hasil cari produk (seperti pencarian anggota)
const produkDitemukan = computed(() => filteredProduk.value.slice(0, 40))

const pilihProduk = (p) => {
    addToCart(p)
    search.value = ''
}

const kategoriList = computed(() => {
    const set = new Set(props.kategori_list)
    props.produk.forEach((p) => {
        if (p.kategori) set.add(p.kategori)
    })
    return [...set]
})

// Scroll ke atas saat ganti kategori

const addToCart = (produk) => {
    if (Number(produk.stok) <= 0) {
        toast.error(`Stok ${produk.nama_barang} kosong.`)
        return
    }
    const existing = cart.value.find((c) => c.produk_id === produk.id)
    if (existing) {
        if (existing.qty >= Number(produk.stok)) {
            toast.error(`Stok ${produk.nama_barang} tidak cukup.`)
            return
        }
        existing.qty++
    } else {
        cart.value.push({
            produk_id: produk.id,
            nama_barang: produk.nama_barang,
            kode_barang: produk.kode_barang,
            qty: 1,
            harga: Number(produk.harga_jual || 0),
            diskon_item: Number(produk.diskon || 0),
            stok: Number(produk.stok || 0),
        })
    }
}

const incQty = (item) => {
    if (item.qty >= item.stok) {
        toast.error(`Stok ${item.nama_barang} tidak cukup.`)
        return
    }
    item.qty++
}

const decQty = (item) => {
    if (item.qty <= 1) {
        removeItem(item)
        return
    }
    item.qty--
}

const removeItem = (item) => {
    cart.value = cart.value.filter((c) => c.produk_id !== item.produk_id)
}

const clearCart = () => {
    cart.value = []
}

// ===== Perhitungan =====
const itemSubtotal = (item) => Math.max(0, item.qty * (item.harga - Number(item.diskon_item || 0)))

const totalQty = computed(() => cart.value.reduce((s, i) => s + i.qty, 0))
const totalNilai = computed(() => cart.value.reduce((sum, item) => sum + itemSubtotal(item), 0))
const totalDiskonHeader = computed(() => Number(diskonHeader.value || 0))
const totalJual = computed(() => Math.max(0, totalNilai.value - totalDiskonHeader.value))

const totalMethods = computed(() => {
    if (piutangMode.value) return 0
    const p = pembayaran.value
    return (
        Number(p.qris || 0) +
        Number(p.edc || 0) +
        totalVoucherNominal.value
    )
})

// Cash otomatis = sisa total jual setelah qris/edc/voucher
const cashOtomatis = computed(() =>
    piutangMode.value ? 0 : Math.max(0, totalJual.value - totalMethods.value),
)

const totalBayar = computed(() =>
    piutangMode.value ? totalJual.value : totalJual.value,
)

// ===== Voucher (bisa beberapa per transaksi) =====
const totalVoucherNominal = computed(() =>
    selectedVouchers.value.reduce((s, v) => s + Number(v.nominal || 0), 0),
)

const addVoucherByInput = () => {
    const key = voucherInput.value.trim()
    if (!key) return

    const keyLower = key.toLowerCase()
    const v = props.vouchers.find((x) => {
        const kode = (x.kode || '').toString().toLowerCase()
        const barcode = (x.barcode || '').toString().toLowerCase()
        return kode === keyLower || barcode === keyLower || barcode === key
    })

    if (!v) {
        toast.error('Voucher tidak ditemukan / tidak aktif.')
        voucherInput.value = ''
        return
    }

    if (selectedVouchers.value.some((s) => s.id === v.id)) {
        toast.error(`Voucher ${v.kode} sudah ditambahkan.`)
        voucherInput.value = ''
        return
    }

    selectedVouchers.value.push({ ...v })
    voucherInput.value = ''
    toast.success(`Voucher ${v.kode} (${formatRupiah(v.nominal)}) ditambahkan.`)
}

const removeVoucher = (id) => {
    selectedVouchers.value = selectedVouchers.value.filter((v) => v.id !== id)
}

const kembalian = computed(() => Math.max(0, totalBayar.value - totalJual.value))
const bayarKurang = computed(() => Math.max(0, totalJual.value - totalBayar.value))

// ===== Anggota =====
const onAnggotaChange = () => {
    const a = props.anggota.find((x) => String(x.id) === String(anggotaId.value))
    anggotaNama.value = a ? a.nama : ''
}

const anggotaDitemukan = computed(() => {
    const s = anggotaSearch.value.trim().toLowerCase()
    if (!s) return []
    return props.anggota
        .filter((a) => a.nama?.toLowerCase().includes(s) || a.nip?.toLowerCase().includes(s))
        .slice(0, 20)
})

const pilihAnggota = (a) => {
    anggotaId.value = a.id
    anggotaNama.value = a.nama
    anggotaSearch.value = `${a.nip} — ${a.nama}`
}

// ===== Checkout =====
const openCheckout = () => {
    if (cart.value.length === 0) {
        toast.error('Keranjang masih kosong.')
        return
    }
    if (bayarKurang.value > 0) {
        toast.error('Nominal pembayaran belum mencukupi.')
        return
    }
    checkoutOpen.value = true
    checkoutDone.value = false
}

const submitCheckout = () => {
    form.clearErrors()
    form.no_kasir = page.props.auth?.user?.name || ''
    form.tanggal = new Date().toISOString().slice(0, 10)
    form.anggota_id = anggotaId.value || ''
    form.nama_anggota = anggotaNama.value || 'Umum'
    form.diskon = diskonHeader.value
    form.usaha = usaha.value
    form.jasa = jasa.value
    form.ppn = ppn.value
    form.cash = cashOtomatis.value
    form.qris = pembayaran.value.qris
    form.edc = pembayaran.value.edc
    form.voucher = totalVoucherNominal.value
    form.voucher_ids = selectedVouchers.value.map((v) => v.id)
    form.piutang = piutangMode.value ? totalJual.value : 0
    form.items = cart.value.map((item) => ({
        produk_id: item.produk_id,
        nama_barang: item.nama_barang,
        qty: item.qty,
        harga: item.harga,
        diskon_item: item.diskon_item,
    }))

    form.post('/transaksi', {
        preserveScroll: true,
        onSuccess: () => {
            const flash = page.props.flash
            if (flash?.success) {
                const match = flash.success.match(/TRX[A-Z0-9]+\d{12}/)
                lastNota.value = match ? match[0] : ''
            }
            // Simpan data struk (setelah form ter-submit) untuk cetak
            lastTransaksi.value = {
                no_nota: lastNota.value || form.no_nota,
                no_kasir: form.no_kasir,
                tanggal: form.tanggal || new Date().toISOString(),
                nama_anggota: form.nama_anggota,
                anggota_id: form.anggota_id,
                items: cart.value.map((item) => ({
                    nama_barang: item.nama_barang,
                    qty: item.qty,
                    harga: item.harga,
                    diskon_item: item.diskon_item || 0,
                    subtotal: itemSubtotal(item),
                })),
                nilai: totalNilai.value,
                diskon: totalDiskonHeader.value,
                jual: totalJual.value,
                cash: Number(form.cash || 0),
                qris: Number(form.qris || 0),
                edc: Number(form.edc || 0),
                voucher: Number(form.voucher || 0),
                piutang: Number(form.piutang || 0),
            }
            checkoutDone.value = true
            resetTransaksi()
        },
        onError: (errors) => {
            const first = Object.values(errors)[0]
            if (first) toast.error(first)
        },
    })
}

const resetTransaksi = () => {
    cart.value = []
    anggotaId.value = ''
    anggotaNama.value = ''
    anggotaSearch.value = ''
    diskonHeader.value = ''
    usaha.value = ''
    jasa.value = ''
    ppn.value = ''
    pembayaran.value = { cash: '', qris: '', edc: '', voucher: '', piutang: '' }
    piutangMode.value = false
    selectedVouchers.value = []
    voucherInput.value = ''
}

const cetakStrukAkhir = () => {
    if (!lastTransaksi.value) return
    printStruk(lastTransaksi.value)
}

const newTransaction = () => {
    checkoutOpen.value = false
    checkoutDone.value = false
    lastNota.value = ''
    lastTransaksi.value = null
    resetTransaksi()
}

// ===== Pembayaran cepat (untuk metode QRIS/EDC) =====
const quickAmounts = [5000, 10000, 20000, 50000, 100000]

// Saat toggle piutang ON: kosongkan metode selain piutang
watch(piutangMode, (on) => {
    if (on) {
        pembayaran.value.qris = ''
        pembayaran.value.edc = ''
        selectedVouchers.value = []
    }
})
</script>

<template>
    <AppLayout content-fill>
        <Head title="Kasir / POS - Kopinka" />

        <!-- ===== Header Kasir ===== -->
        <header class="border-b bg-background shrink-0 px-4 py-3 md:px-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl">
                        <Receipt class="size-5" />
                    </div>
                    <div>
                        <h1 class="text-lg font-bold leading-tight">Kasir / POS</h1>
                        <p class="text-muted-foreground text-xs">
                            {{ page.props.auth?.user?.name || 'Kasir' }} •
                            {{ page.props.auth?.store?.nama || 'Semua Toko' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge variant="outline" class="gap-1.5 text-xs">
                        <Receipt class="size-3.5" />
                        <span class="text-muted-foreground">Nota:</span>
                        <span class="font-bold">{{ props.no_nota }}</span>
                    </Badge>
                    <Badge variant="secondary" class="gap-1.5">
                        <ShoppingCart class="size-3.5" />
                        {{ cart.length }} jenis • {{ totalQty }} pcs
                    </Badge>
                    <Badge variant="outline" class="gap-1.5 text-xs">
                        <span class="text-muted-foreground">Total:</span>
                        <span class="text-primary font-bold">{{ formatRupiah(totalJual) }}</span>
                    </Badge>
                </div>
            </div>
        </header>

        <!-- ===== Body ===== -->
        <div class="grid min-h-0 flex-1 grid-cols-1 overflow-hidden xl:grid-cols-[2fr_3fr] 2xl:grid-cols-[2fr_3fr]">
            <!-- ===== Kolom Kiri: Cari Produk + Keranjang + Pembayaran ===== -->
            <div class="flex min-h-0 min-w-0 flex-col xl:border-r">
                <!-- Cari Produk (dropdown) -->
                <div class="border-b bg-muted/30 flex shrink-0 flex-col gap-1.5 p-2">
                    <div class="flex gap-1.5">
                        <div class="relative min-w-0 flex-1">
                            <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2" />
                            <Input
                                v-model="search"
                                placeholder="Cari produk (ketik nama / kode)..."
                                class="h-8 bg-background pl-8 text-sm"
                            />
                            <!-- Dropdown hasil -->
                            <div
                                v-if="search.trim() && produkDitemukan.length"
                                class="bg-popover text-popover-foreground absolute top-full right-0 left-0 z-30 mt-1 flex max-h-56 flex-col gap-0.5 overflow-y-auto rounded-lg border p-1 shadow-lg"
                            >
                                <button
                                    v-for="p in produkDitemukan"
                                    :key="p.id"
                                    type="button"
                                    class="hover:bg-accent flex items-center gap-2 rounded-md px-2 py-1.5 text-left"
                                    :class="{ 'opacity-40 cursor-not-allowed': Number(p.stok) <= 0 }"
                                    :disabled="Number(p.stok) <= 0"
                                    @click="pilihProduk(p)"
                                >
                                    <Package class="text-primary size-3.5 shrink-0" />
                                    <span class="min-w-0 flex-1 truncate text-xs font-medium">{{ p.nama_barang }}</span>
                                    <Badge :variant="Number(p.stok) <= Number(p.stok_minimum) ? 'warning' : 'secondary'" class="shrink-0 text-[10px]">
                                        {{ p.stok }}
                                    </Badge>
                                    <span class="text-primary shrink-0 text-xs font-bold">{{ formatRupiah(p.harga_jual) }}</span>
                                </button>
                            </div>
                            <div
                                v-else-if="search.trim() && !produkDitemukan.length"
                                class="bg-popover text-popover-foreground absolute top-full right-0 left-0 z-30 mt-1 rounded-lg border p-2 text-xs shadow-lg"
                            >
                                Tidak ada produk yang cocok.
                            </div>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-8 shrink-0 gap-1 bg-background px-2 text-xs"
                            @click="produkModalSearch = search; produkModalKategori = kategori; produkModalOpen = true"
                        >
                            <Maximize2 class="size-3.5" />
                            Cari
                        </Button>
                    </div>
                </div>

                <!-- Keranjang -->
                <div class="flex min-h-0 flex-1 flex-col">
                    <div class="border-b bg-background flex items-center justify-between px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <ShoppingCart class="text-primary size-4" />
                            <span class="text-sm font-semibold">Keranjang</span>
                            <Badge variant="secondary" class="text-xs">{{ totalQty }} pcs</Badge>
                        </div>
                        <Button v-if="cart.length" variant="ghost" size="sm" class="text-destructive h-7 px-2 text-xs" @click="clearCart">
                            <Trash2 class="size-3.5" />
                            Kosongkan
                        </Button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto p-2.5">
                        <div v-if="cart.length" class="flex flex-col gap-2">
                            <div v-for="item in cart" :key="item.produk_id" class="border-input bg-card rounded-lg border p-2.5 shadow-xs">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-[13px] font-medium leading-tight">{{ item.nama_barang }}</p>
                                        <p class="text-muted-foreground text-[11px]">{{ item.kode_barang }}</p>
                                    </div>
                                    <button class="text-muted-foreground hover:text-destructive shrink-0" @click="removeItem(item)">
                                        <X class="size-4" />
                                    </button>
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center gap-1 rounded-lg border">
                                        <button class="hover:bg-muted flex size-7 items-center justify-center rounded-l-lg" @click="decQty(item)">
                                            <Minus class="size-3.5" />
                                        </button>
                                        <span class="w-8 text-center text-sm font-semibold">{{ item.qty }}</span>
                                        <button class="hover:bg-muted flex size-7 items-center justify-center rounded-r-lg" @click="incQty(item)">
                                            <Plus class="size-3.5" />
                                        </button>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold">{{ formatRupiah(itemSubtotal(item)) }}</p>
                                        <p v-if="item.diskon_item > 0" class="text-muted-foreground text-[11px]">
                                            {{ formatRupiah(item.harga) }} − {{ formatRupiah(item.diskon_item) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-muted-foreground flex h-full flex-col items-center justify-center gap-2 py-12 text-center">
                            <ShoppingCart class="size-8" />
                            <p class="text-sm">Keranjang kosong.<br />Ketik produk di atas untuk menambahkan.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Kolom Kanan: Anggota + Voucher (1 baris 2 kolom) ===== -->
            <div class="flex min-h-0 flex-col overflow-y-auto bg-muted/20 p-2">
                <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                    <!-- Anggota (search) -->
                    <div class="flex flex-col gap-1 rounded-lg border bg-background p-1.5">
                        <Label class="text-muted-foreground flex items-center gap-1 text-[10px] uppercase">
                            <User class="size-3" />
                            Cari Anggota
                        </Label>
                        <div class="relative">
                            <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2" />
                            <Input
                                v-model="anggotaSearch"
                                placeholder="Nama / NIP..."
                                class="h-8 bg-background pl-8 text-xs"
                            />
                            <div v-if="anggotaDitemukan.length" class="bg-popover text-popover-foreground absolute top-full right-0 left-0 z-30 mt-1 flex max-h-56 flex-col gap-0.5 overflow-y-auto rounded-lg border p-1 shadow-lg">
                                <button
                                    v-for="a in anggotaDitemukan"
                                    :key="a.id"
                                    type="button"
                                    class="hover:bg-accent flex items-center justify-between rounded-md px-2 py-1.5 text-left"
                                    :class="{ 'bg-primary/10 text-primary': String(a.id) === String(anggotaId) }"
                                    @click="pilihAnggota(a)"
                                >
                                    <span class="truncate text-xs">{{ a.nip }} — {{ a.nama }}</span>
                                    <CheckCheck v-if="String(a.id) === String(anggotaId)" class="size-3 shrink-0" />
                                </button>
                            </div>
                        </div>
                        <p v-if="anggotaId" class="bg-primary/10 text-primary truncate rounded-md px-2 py-1 text-[11px] font-medium">
                            Anggota: {{ anggotaNama }}
                        </p>
                    </div>

                    <!-- Voucher -->
                    <div class="flex flex-col gap-1 rounded-lg border bg-background p-1.5">
                        <div class="flex items-center justify-between">
                            <Label class="text-muted-foreground flex items-center gap-1 text-[10px] uppercase">
                                <Ticket class="size-3" />
                                Voucher ({{ selectedVouchers.length }})
                            </Label>
                            <span class="text-primary text-[11px] font-bold">{{ formatRupiah(totalVoucherNominal) }}</span>
                        </div>
                        <div class="flex gap-1">
                            <Input
                                v-model="voucherInput"
                                placeholder="Kode / barcode voucher..."
                                class="h-8 bg-background text-[11px]"
                                @keydown.enter.prevent="addVoucherByInput"
                            />
                            <Button type="button" variant="outline" size="sm" class="h-8 shrink-0 bg-background px-2 text-[11px]" @click="addVoucherByInput">
                                <Plus class="size-3" />
                                Tambah
                            </Button>
                        </div>
                        <div v-if="selectedVouchers.length" class="flex max-h-24 flex-col gap-0.5 overflow-y-auto">
                            <div v-for="v in selectedVouchers" :key="v.id" class="border-input bg-card flex items-center justify-between gap-2 rounded-md border px-1.5 py-0.5">
                                <div class="flex min-w-0 items-center gap-1">
                                    <Ticket class="text-primary size-2.5 shrink-0" />
                                    <span class="truncate text-[10px] font-medium">{{ v.kode }}</span>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <span class="text-primary text-[10px] font-bold">{{ formatRupiah(v.nominal) }}</span>
                                    <button type="button" class="text-muted-foreground hover:text-destructive" @click="removeVoucher(v.id)">
                                        <X class="size-3" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-muted-foreground text-[10px]">
                            Ketik kode/barcode lalu Enter.
                        </p>
                    </div>
                </div>

                <!-- Total & Pembayaran -->
                <div class="mt-1.5 flex flex-col gap-1.5 rounded-lg border bg-background p-2.5 shadow-sm">
                    <!-- Ringkasan -->
                    <div class="flex flex-col gap-0.5 text-[12px]">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Nilai ({{ totalQty }} item)</span>
                            <span class="font-medium">{{ formatRupiah(totalNilai) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-muted-foreground shrink-0">Diskon (Rp)</span>
                            <Input v-model="diskonHeader" type="number" min="0" step="100" placeholder="0" class="h-8 w-28 text-right text-sm" />
                        </div>
                        <div class="border-t mt-1 flex items-center justify-between pt-1.5">
                            <span class="font-semibold">Total Jual</span>
                            <span class="text-primary text-xl font-bold">{{ formatRupiah(totalJual) }}</span>
                        </div>
                    </div>

                    <!-- Metode bayar -->
                    <div class="flex flex-col gap-1">
                        <!-- Toggle Piutang -->
                        <div class="flex items-center justify-between rounded-lg border bg-muted/30 px-2 py-1">
                            <Label class="text-muted-foreground flex items-center gap-1.5 text-[11px] uppercase">
                                <HandCoins class="size-3.5" />
                                Bayar Piutang
                            </Label>
                            <button
                                type="button"
                                role="switch"
                                aria-checked="piutangMode"
                                class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors"
                                :class="piutangMode ? 'bg-primary' : 'bg-input'"
                                @click="piutangMode = !piutangMode"
                            >
                                <span
                                    class="bg-background inline-block size-4 rounded-full shadow transition-transform"
                                    :class="piutangMode ? 'translate-x-4.5' : 'translate-x-0.5'"
                                />
                            </button>
                        </div>

                        <div v-if="!piutangMode" class="grid grid-cols-2 gap-1.5">
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-muted-foreground flex items-center gap-1 text-[10px] uppercase">
                                    <QrCode class="size-3" /> QRIS
                                </span>
                                <Input v-model="pembayaran.qris" type="number" min="0" step="100" class="h-9 px-1 text-center text-xs" placeholder="0" />
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-muted-foreground flex items-center gap-1 text-[10px] uppercase">
                                    <CreditCard class="size-3" /> EDC
                                </span>
                                <Input v-model="pembayaran.edc" type="number" min="0" step="100" class="h-9 px-1 text-center text-xs" placeholder="0" />
                            </div>
                            <div class="border-input bg-muted/40 col-span-2 flex items-center justify-between rounded-lg border px-3 py-1.5">
                                <span class="text-muted-foreground flex items-center gap-1 text-[10px] uppercase">
                                    <Banknote class="size-3" /> Cash (otomatis)
                                </span>
                                <span class="text-primary text-sm font-bold">{{ formatRupiah(cashOtomatis) }}</span>
                            </div>
                        </div>

                        <!-- Mode Piutang: semua masuk piutang -->
                        <div v-else class="rounded-lg border bg-muted/30 px-3 py-2">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground flex items-center gap-1 text-[11px] uppercase">
                                    <HandCoins class="size-3.5" /> Total piutang
                                </span>
                                <span class="text-primary text-sm font-bold">{{ formatRupiah(totalJual) }}</span>
                            </div>
                        </div>

                        <div v-if="bayarKurang > 0" class="bg-destructive/10 text-destructive rounded-lg border px-3 py-1.5 text-xs font-medium">
                            Kurang: {{ formatRupiah(bayarKurang) }}
                        </div>
                        <div v-else-if="kembalian > 0" class="bg-emerald-500/10 text-emerald-600 rounded-lg border px-3 py-1.5 text-xs font-medium">
                            Kembalian: {{ formatRupiah(kembalian) }}
                        </div>
                    </div>

                    <!-- Checkout -->
                    <Button class="h-10 w-full text-sm" :disabled="cart.length === 0 || bayarKurang > 0" @click="openCheckout">
                        <CheckCircle2 class="size-4" />
                        Checkout • {{ formatRupiah(totalJual) }}
                    </Button>
                </div>
            </div>
        </div>

        <!-- ===== Modal Checkout ===== -->
        <div v-if="checkoutOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="checkoutDone ? newTransaction() : (checkoutOpen = false)">
            <div class="bg-background w-full max-w-md rounded-2xl p-6 shadow-xl">
                <template v-if="!checkoutDone">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                            <ShoppingCart class="size-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">Konfirmasi Checkout</h3>
                            <p class="text-muted-foreground text-sm">Pastikan data transaksi sudah benar.</p>
                        </div>
                    </div>

                    <div class="mb-4 flex flex-col gap-2 rounded-xl border p-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Item</span>
                            <span>{{ totalQty }} pcs ({{ cart.length }} jenis)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Nilai</span>
                            <span>{{ formatRupiah(totalNilai) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Diskon</span>
                            <span class="text-destructive">− {{ formatRupiah(totalDiskonHeader) }}</span>
                        </div>
                        <div v-if="selectedVouchers.length" class="flex justify-between">
                            <span class="text-muted-foreground">Voucher ({{ selectedVouchers.length }})</span>
                            <span class="text-primary">− {{ formatRupiah(totalVoucherNominal) }}</span>
                        </div>
                        <div class="border-t pt-2">
                            <div class="flex justify-between text-base">
                                <span class="font-semibold">Total Jual</span>
                                <span class="text-primary font-bold">{{ formatRupiah(totalJual) }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Dibayar</span>
                            <span>{{ formatRupiah(totalBayar) }}</span>
                        </div>
                        <div v-if="kembalian > 0" class="flex justify-between">
                            <span class="text-muted-foreground">Kembalian</span>
                            <span class="text-emerald-600 font-semibold">{{ formatRupiah(kembalian) }}</span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Button variant="outline" class="flex-1" @click="checkoutOpen = false">
                            Batal
                        </Button>
                        <Button class="flex-1" :disabled="form.processing" @click="submitCheckout">
                            <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                            <CheckCircle2 v-else class="size-4" />
                            {{ form.processing ? 'Menyimpan...' : 'Bayar & Simpan' }}
                        </Button>
                    </div>
                </template>

                <template v-else>
                    <div class="flex flex-col items-center gap-3 py-4 text-center">
                        <div class="bg-emerald-500/10 text-emerald-600 flex size-14 items-center justify-center rounded-full">
                            <CheckCircle2 class="size-7" />
                        </div>
                        <h3 class="text-lg font-semibold">Transaksi Berhasil!</h3>
                        <p class="text-muted-foreground text-sm">
                            Nota <b class="text-foreground">{{ lastNota }}</b> telah disimpan.
                        </p>
                        <Button class="mt-2 w-full" @click="newTransaction">
                            Transaksi Baru
                        </Button>
                        <Button variant="outline" class="mt-2 w-full" @click="cetakStrukAkhir">
                            <Printer class="size-4" />
                            Cetak Struk
                        </Button>
                    </div>
                </template>
            </div>
        </div>

        <!-- ===== Modal Cari Produk (pencarian lengkap) ===== -->
        <div v-if="produkModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="produkModalOpen = false">
            <div class="bg-background flex h-full max-h-[92svh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl shadow-xl">
                <!-- Header -->
                <div class="flex shrink-0 items-center gap-3 border-b px-5 py-4">
                    <div class="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-lg">
                        <Maximize2 class="size-4.5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base font-semibold">Cari Produk</h3>
                        <p class="text-muted-foreground text-xs">Pilih produk untuk ditambahkan ke keranjang</p>
                    </div>
                    <Button type="button" variant="ghost" size="icon" @click="produkModalOpen = false">
                        <X class="size-4.5" />
                    </Button>
                </div>

                <!-- Filter -->
                <div class="flex shrink-0 flex-col gap-2 border-b px-5 py-3">
                    <div class="relative">
                        <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            v-model="produkModalSearch"
                            placeholder="Ketik nama / kode produk..."
                            class="h-9 bg-background pl-9"
                            autofocus
                        />
                    </div>
                    <div class="flex gap-1.5 overflow-x-auto pb-0.5">
                        <Button
                            size="sm"
                            variant="outline"
                            class="h-7 shrink-0 bg-background text-xs"
                            :class="{ 'bg-primary text-primary-foreground border-primary': produkModalKategori === '' }"
                            @click="produkModalKategori = ''"
                        >
                            Semua
                        </Button>
                        <Button
                            v-for="k in kategoriList"
                            :key="k"
                            size="sm"
                            variant="outline"
                            class="h-7 shrink-0 bg-background text-xs"
                            :class="{ 'bg-primary text-primary-foreground border-primary': produkModalKategori === k }"
                            @click="produkModalKategori = produkModalKategori === k ? '' : k"
                        >
                            {{ k }}
                        </Button>
                    </div>
                </div>

                <!-- Daftar hasil -->
                <div class="min-h-0 flex-1 overflow-y-auto p-3">
                    <div v-if="filteredProdukModal.length" class="flex flex-col gap-1.5">
                        <button
                            v-for="p in filteredProdukModal"
                            :key="p.id"
                            type="button"
                            class="border-input hover:border-primary hover:shadow-primary/10 bg-card group flex items-center gap-3 rounded-xl border px-3 py-2.5 text-left shadow-xs transition-all hover:shadow-md"
                            :class="{ 'opacity-40 cursor-not-allowed': Number(p.stok) <= 0 }"
                            :disabled="Number(p.stok) <= 0"
                            @click="addToCart(p)"
                        >
                            <div class="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-lg">
                                <Package class="size-4.5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold leading-tight">{{ p.nama_barang }}</p>
                                <p class="text-muted-foreground truncate text-xs">{{ p.kode_barang }} • {{ p.kategori || '-' }}</p>
                            </div>
                            <Badge :variant="Number(p.stok) <= Number(p.stok_minimum) ? 'warning' : 'secondary'" class="shrink-0 text-[10px]">
                                {{ p.stok }}
                            </Badge>
                            <span class="text-primary shrink-0 text-sm font-bold">{{ formatRupiah(p.harga_jual) }}</span>
                            <span class="bg-primary/10 text-primary flex size-6 items-center justify-center rounded-full">
                                <Plus class="size-3.5" />
                            </span>
                        </button>
                    </div>
                    <div v-else class="text-muted-foreground flex h-full flex-col items-center justify-center gap-2 py-16 text-center">
                        <Maximize2 class="size-8" />
                        <p class="text-sm">Tidak ada produk yang cocok.</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="shrink-0 border-t px-5 py-3">
                    <Button type="button" variant="outline" class="w-full" @click="produkModalOpen = false">
                        Tutup
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
