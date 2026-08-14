<?php

namespace App\Http\Requests;

use App\Models\Konsinyi;
use Illuminate\Foundation\Http\FormRequest;

class StoreKonsinyiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis' => ['required', 'in:retur,pembayaran'],
            'tgl_transaksi' => ['required', 'date'],
            'no_bukti' => ['nullable', 'string', 'max:50', 'unique:konsinyi,no_bukti'],
            'no_faktur' => ['nullable', 'string', 'max:50'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'kode_supplier' => ['nullable', 'string', 'max:50'],
            'nama_supplier' => ['nullable', 'string', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'no_telp' => ['nullable', 'string', 'max:30'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'diskon' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'jumlah_bayar' => ['required', 'numeric', 'min:0'],
            'kurang_bayar' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['nullable', 'exists:produk,id'],
            'items.*.kode_barang' => ['nullable', 'string', 'max:50'],
            'items.*.nama_barang' => ['nullable', 'string', 'max:150'],
            'items.*.satuan' => ['nullable', 'string', 'max:20'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.harga_beli' => ['required', 'numeric', 'min:0'],
            'items.*.harga_jual' => ['nullable', 'numeric', 'min:0'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Auto-generate no_transaksi & no_bukti bila kosong.
     */
    protected function prepareForValidation(): void
    {
        if (trim($this->input('no_transaksi') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_transaksi', now()->toDateString());
            $model = new Konsinyi();
            $this->merge(['no_transaksi' => $model->generateNoTransaksi($store?->kode, $tanggal)]);
        }

        if (trim($this->input('no_bukti') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_transaksi', now()->toDateString());
            $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($store?->kode ?? 'PUSAT')));
            $this->merge(['no_bukti' => 'BK' . $kode . str_replace('-', '', $tanggal) . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT)]);
        }
    }
}
