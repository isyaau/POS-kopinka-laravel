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
            { title: 'Kasir / POS', href: '/pos', icon: ShoppingCart, permission: 'transaksi.manage' },
            { title: 'Transaksi', href: '/transaksi', icon: ClipboardList, permission: 'transaksi.manage' },
            { title: 'Produk', href: '/produk', icon: Package, permission: 'produk.manage' },
            { title: 'Stok', href: '/stok', icon: Boxes, permission: 'produk.manage' },
        ],
    },
    {
        title: 'Kantor Pusat',
        items: [
            { title: 'Laporan', href: '/laporan', icon: BarChart3, permission: 'laporan.view' },
            { title: 'Toko', href: '/toko', icon: Store, permission: 'user.manage' },
        ],
    },
    {
        title: 'Manajemen',
        items: [
            { title: 'Anggota', href: '/anggota', icon: UserRound, permission: 'anggota.manage' },
            { title: 'Pengguna', href: '/pengguna', icon: Users, permission: 'user.manage' },
            { title: 'Pengaturan', href: '/pengaturan', icon: Settings, permission: 'user.manage' },
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
