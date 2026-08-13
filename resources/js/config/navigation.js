import {
    LayoutDashboard,
    Package,
    ShoppingCart,
    BarChart3,
    Users,
    Store,
    Settings,
    Boxes,
    ClipboardList,
    CircleUser,
    UserRound,
    Shield,
    Truck,
    Database,
    Ticket,
    ShoppingBag,
    ArrowLeftRight,
    PackageCheck,
    ClipboardCheck,
} from 'lucide-vue-next'

export const navigation = [
    {
        title: 'Utama',
        items: [
            { title: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, permission: 'dashboard.view' },
        ],
    },
    {
        title: 'Operasional',
        items: [
            { title: 'Kasir / POS', href: '/pos', icon: ShoppingCart, permission: 'transaksi.view' },
            { title: 'Transaksi', href: '/transaksi', icon: ClipboardList, permission: 'transaksi.view' },
            { title: 'Produk', href: '/produk', icon: Package, permission: 'produk.view' },
            { title: 'Stok', href: '/stok', icon: Boxes, permission: 'stok.view' },
            { title: 'Pembelian', href: '/pembelian', icon: ShoppingBag, permission: 'pembelian.view' },
            { title: 'Terima Barang', href: '/terima-barang', icon: PackageCheck, permission: 'terima-barang.view' },
            { title: 'Stok Opname', href: '/stok-opname', icon: ClipboardCheck, permission: 'stok-opname.view' },
            { title: 'Retur / Tukar', href: '/retur-pembelian', icon: ArrowLeftRight, permission: 'retur-pembelian.view' },
            { title: 'Kirim Barang', href: '/kirim-barang', icon: Truck, permission: 'kirim-barang.view' },
            { title: 'Mutasi Produk', href: '/mutasi', icon: ArrowLeftRight, permission: 'stok.view' },
            { title: 'Voucher', href: '/voucher', icon: Ticket, permission: 'voucher.view' },
        ],
    },
    {
        title: 'Kantor Pusat',
        items: [
            { title: 'Laporan', href: '/laporan', icon: BarChart3, permission: 'laporan.view' },
            { title: 'Toko', href: '/toko', icon: Store, permission: 'store.view' },
        ],
    },
    {
        title: 'Manajemen',
        items: [
            { title: 'Anggota', href: '/anggota', icon: UserRound, permission: 'anggota.view' },
            { title: 'Supplier', href: '/suppliers', icon: Truck, permission: 'supplier.view' },
            { title: 'Pengguna', href: '/pengguna', icon: Users, permission: 'user.view' },
            { title: 'Role', href: '/roles', icon: Shield, permission: 'role.view' },
            { title: 'Pengaturan', href: '/pengaturan', icon: Settings, permission: 'user.view' },
        ],
    },
    {
        title: 'Migrasi',
        items: [
            { title: 'Migrasi Data', href: '/migrasi', icon: Database, permission: 'supplier.import' },
        ],
    },
]

export function getNavigation(permissions = []) {
    if (!permissions || permissions.length === 0) return navigation

    return navigation
        .map((group) => ({
            ...group,
            items: group.items.filter(
                (item) => !item.permission || permissions.includes(item.permission),
            ),
        }))
        .filter((group) => group.items.length > 0)
}

export { CircleUser }
