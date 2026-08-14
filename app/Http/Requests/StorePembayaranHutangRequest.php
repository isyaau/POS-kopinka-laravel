<?php

namespace App\Http\Requests;

use App\Models\PembayaranHutang;
use Illuminate\Foundation\Http\FormRequest;

class StorePembayaranHutangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
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
            'no_bukti' => ['nullable', 'string', 'max:50', 'unique:pembayaran_hutang,no_bukti'],
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

    /**
     * Auto-generate no_transaksi & no_bukti bila kosong.
     */
    protected function prepareForValidation(): void
    {
        if (trim($this->input('no_transaksi') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tanggal_bayar', now()->toDateString());
            $model = new PembayaranHutang();
            $this->merge(['no_transaksi' => $model->generateNoTransaksi($store?->kode, $tanggal)]);
        }

        if (trim($this->input('no_bukti') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tanggal_bayar', now()->toDateString());
            $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($store?->kode ?? 'PUSAT')));
            $this->merge(['no_bukti' => 'BK' . $kode . str_replace('-', '', $tanggal) . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT)]);
        }
    }
}
