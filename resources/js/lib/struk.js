/**
 * Cetak struk / nota penjualan (58mm thermal) via window.print().
 * Dipakai dari POS (setelah checkout) dan menu Transaksi (cetak ulang).
 */

const formatRupiah = (val) => {
    const n = Number(val || 0)
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(n)
}

const formatDate = (val) => {
    if (!val) return '-'
    const d = new Date(val)
    if (Number.isNaN(d.getTime())) return '-'
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    const hh = String(d.getHours()).padStart(2, '0')
    const mm = String(d.getMinutes()).padStart(2, '0')
    return `${day}-${month}-${year} ${hh}:${mm}`
}

const esc = (val) =>
    String(val ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')

/**
 * @param {Object} data  Data transaksi untuk struk:
 *   { no_nota, no_kasir, tanggal, nama_anggota, anggota_id,
 *     items: [{nama_barang, qty, harga, diskon_item, subtotal}],
 *     nilai, diskon, jual, cash, qris, edc, voucher, piutang }
 */
export function printStruk(data) {
    const d = data || {}
    const items = Array.isArray(d.items) ? d.items : []

    const itemRows = items
        .map((it) => {
            const nama = esc(it.nama_barang || '-')
            const qty = Number(it.qty || 0)
            const harga = Number(it.harga || 0)
            const diskonItem = Number(it.diskon_item || 0)
            const subtotal = Number(it.subtotal) > 0 ? Number(it.subtotal) : Math.max(0, qty * (harga - diskonItem))
            const hargaStr = formatRupiah(harga)
            const subStr = formatRupiah(subtotal)

            let html = `<div class="row nama">${nama}</div>`
            if (diskonItem > 0) {
                html += `<div class="row"><span>Diskon</span><span>${formatRupiah(diskonItem)}</span></div>`
            }
            html += `<div class="row"><span>${qty} x ${hargaStr}</span><span>${subStr}</span></div>`
            return html
        })
        .join('')

    // Hanya tampilkan metode bayar yang nilainya > 0
    const paymentRows = [
        { label: 'Cash', value: Number(d.cash || 0) },
        { label: 'QRIS', value: Number(d.qris || 0) },
        { label: 'EDC', value: Number(d.edc || 0) },
        { label: 'Voucher', value: Number(d.voucher || 0) },
        { label: 'Piutang', value: Number(d.piutang || 0) },
    ]
        .filter((p) => p.value > 0)
        .map((p) => `<div class="row"><span>${p.label}</span><span>${formatRupiah(p.value)}</span></div>`)
        .join('')

    const html = `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<title>Struk ${esc(d.no_nota || '')}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Courier New', Consolas, monospace;
        font-size: 12px;
        color: #000;
        width: 58mm;
        margin: 0 auto;
        padding: 4mm;
    }
    .center { text-align: center; }
    .bold { font-weight: 700; }
    .muted { color: #555; }
    .divider { border-top: 1px dashed #000; margin: 3mm 0; }
    .row { display: flex; justify-content: space-between; gap: 4px; margin: 1px 0; }
    .row.nama { justify-content: flex-start; font-weight: 700; margin-top: 2mm; }
    .header { text-align: center; margin-bottom: 2mm; }
    .header h1 { font-size: 15px; letter-spacing: 2px; }
    .header p { font-size: 10px; margin-top: 1px; }
    .total { display: flex; justify-content: space-between; font-weight: 700; font-size: 14px; margin-top: 1mm; }
    .footer { text-align: center; margin-top: 3mm; font-size: 10px; }
    @media print {
        body { width: 100%; }
    }
</style>
</head>
<body>
    <div class="header">
        <h1>KOPINKA</h1>
        <p>Koperasi Kopinka</p>
        <p>Jl. Koperasi No. 1</p>
    </div>
    <div class="divider"></div>
    <div class="row"><span>No Nota</span><span class="bold">${esc(d.no_nota || '-')}</span></div>
    <div class="row"><span>Tanggal</span><span>${formatDate(d.tanggal)}</span></div>
    <div class="row"><span>Kasir</span><span>${esc(d.no_kasir || '-')}</span></div>
    <div class="row"><span>Anggota</span><span>${esc(d.nama_anggota || 'Umum')}</span></div>
    <div class="divider"></div>
    ${itemRows}
    <div class="divider"></div>
    <div class="row"><span>Nilai</span><span>${formatRupiah(d.nilai)}</span></div>
    <div class="row"><span>Diskon</span><span>${formatRupiah(d.diskon)}</span></div>
    <div class="total"><span>Total Jual</span><span>${formatRupiah(d.jual)}</span></div>
    <div class="divider"></div>
    ${paymentRows}
    <div class="divider"></div>
    <div class="footer">
        <p>Terima kasih atas kunjungan Anda.</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar.</p>
    </div>
</body>
</html>`

    const win = window.open('', '_blank', 'width=420,height=600')
    if (!win) {
        alert('Browser memblokir pop-up. Izinkan pop-up untuk mencetak struk.')
        return
    }
    win.document.write(html)
    win.document.close()
    win.focus()
    win.print()
}
