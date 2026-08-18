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
    ReceiptText,
    Wallet,
    PackageX,
    Landmark,
    FileText,
    CreditCard,
    AlertTriangle,
    MailOpen,
    RotateCcw,
    Tag,
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
            { title: 'Laporan Penjualan', href: '/laporan', icon: BarChart3, permission: 'laporan.view' },
            { title: 'Laporan Pembelian', href: '/laporan-pembelian', icon: ShoppingCart, permission: 'pembelian.view' },
            { title: 'Laporan Hutang Dagang', href: '/laporan-hutang-dagang', icon: Wallet, permission: 'pembayaran-hutang.view' },
            { title: 'Laporan Piutang Anggota', href: '/laporan-piutang-anggota', icon: Users, permission: 'penerimaan-angsuran.view' },
            { title: 'Laporan Mutasi & Stok', href: '/laporan-mutasi-stok', icon: Boxes, permission: 'stok.view' },
            { title: 'Laporan Mutasi Barang Masuk', href: '/laporan-mutasi-barang-masuk', icon: PackageCheck, permission: 'stok.view' },
            { title: 'Laporan Mutasi Barang Keluar', href: '/laporan-mutasi-barang-keluar', icon: PackageX, permission: 'stok.view' },
            { title: 'Biaya Operasional', href: '/biaya-operasional', icon: ReceiptText, permission: 'biaya-operasional.view' },
            { title: 'Hutang Supplier', href: '/pembayaran-hutang', icon: Wallet, permission: 'pembayaran-hutang.view' },
            { title: 'Konsinyi', href: '/konsinyi', icon: PackageX, permission: 'konsinyi.view' },
            { title: 'Penerimaan Angsuran', href: '/penerimaan-angsuran', icon: Landmark, permission: 'penerimaan-angsuran.view' },
            { title: 'Register Tagihan Piutang', href: '/register-tagihan-piutang', icon: FileText, permission: 'register-tagihan-piutang.view' },
            { title: 'Penerimaan Angsuran Potong Gaji', href: '/penerimaan-angsuran-potong-gaji', icon: CreditCard, permission: 'penerimaan-angsuran-potong-gaji.view' },
            { title: 'Gagal Debet Piutang', href: '/gagal-debet-piutang', icon: AlertTriangle, permission: 'gagal-debet-piutang.view' },
            { title: 'Pengembalian Lebih Bayar Potong Gaji', href: '/pengembalian-lebih-bayar-potong-gaji', icon: RotateCcw, permission: 'pengembalian-lebih-bayar-potong-gaji.view' },
            { title: 'Register Label Etalase Barang', href: '/register-label-etalase-barang', icon: Tag, permission: 'register-label-etalase-barang.view' },
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
