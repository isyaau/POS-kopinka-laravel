<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRegisterLabelEtalaseBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('register_label_etalase_barang')->id;

        return [
            'tgl_register' => ['required', 'date'],
            'no_bukti' => ['required', 'string', 'max:50', "unique:register_label_etalase_barang,no_bukti,{$id}"],
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
}
