<?php

namespace App\Http\Requests;

use App\Models\RegisterLabelEtalaseBarang;
use Illuminate\Foundation\Http\FormRequest;

class StoreRegisterLabelEtalaseBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tgl_register' => ['required', 'date'],
            'no_bukti' => ['nullable', 'string', 'max:50', 'unique:register_label_etalase_barang,no_bukti'],
            'produk_id' => ['required', 'exists:produk,id'],
            'kode_barang' => ['nullable', 'string', 'max:50'],
            'nama_barang' => ['nullable', 'string', 'max:200'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'no_rak' => ['nullable', 'string', 'max:50'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'jumlah_label' => ['required', 'integer', 'min:1'],
            'ukuran_label' => ['nullable', 'string', 'in:kecil,sedang,besar'],
            'keterangan' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,tercetak,dipasang,batal'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (trim($this->input('no_transaksi') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_register', now()->toDateString());
            $model = new RegisterLabelEtalaseBarang();
            $this->merge(['no_transaksi' => $model->generateNoTransaksi($store?->kode, $tanggal)]);
        }

        if (trim($this->input('no_bukti') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_register', now()->toDateString());
            $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($store?->kode ?? 'PUSAT')));
            $this->merge(['no_bukti' => 'BLE' . $kode . str_replace('-', '', $tanggal) . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT)]);
        }
    }
}
