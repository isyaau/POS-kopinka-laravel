<?php

namespace App\Http\Requests;

use App\Models\PembayaranHutang;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePembayaranHutangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('pembayaran_hutang')->id;

        return [
            'tgl_pembelian' => ['required', 'date'],
            'tgl_jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tgl_pembelian'],
            'no_faktur' => ['nullable', 'string', 'max:50'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'kode_supplier' => ['nullable', 'string', 'max:50'],
            'nama_supplier' => ['nullable', 'string', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'no_telp' => ['nullable', 'string', 'max:30'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'no_bukti' => ['required', 'string', 'max:50', "unique:pembayaran_hutang,no_bukti,{$id}"],
            'tanggal_bayar' => ['required', 'date'],
            'nilai_pembelian' => ['required', 'numeric', 'min:0'],
            'retur_pembelian' => ['nullable', 'numeric', 'min:0'],
            'diskon_pembayaran' => ['nullable', 'numeric', 'min:0'],
            'total_harus_dibayar' => ['required', 'numeric', 'min:0'],
            'jumlah_bayar' => ['required', 'numeric', 'min:0'],
            'total_terbayar' => ['nullable', 'numeric', 'min:0'],
            'total_diskon' => ['nullable', 'numeric', 'min:0'],
            'kurang_bayar' => ['nullable', 'numeric', 'min:0'],
            'sisa_hutang' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
