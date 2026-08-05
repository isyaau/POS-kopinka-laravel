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
            { title: 'Stok', href: '/stok', icon: Boxes, permission: 'produk.view' },
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
            { title: 'Pengguna', href: '/pengguna', icon: Users, permission: 'user.view' },
            { title: 'Role', href: '/roles', icon: Shield, permission: 'role.view' },
            { title: 'Pengaturan', href: '/pengaturan', icon: Settings, permission: 'user.view' },
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
