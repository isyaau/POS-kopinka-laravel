<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKonsinyiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('konsinyi')->id;

        return [
            'jenis' => ['required', 'in:retur,pembayaran'],
            'tgl_transaksi' => ['required', 'date'],
            'no_bukti' => ['required', 'string', 'max:50', "unique:konsinyi,no_bukti,{$id}"],
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
}
