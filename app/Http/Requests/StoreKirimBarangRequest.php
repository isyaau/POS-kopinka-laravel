<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKirimBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'store_asal_id' => ['required', 'integer', 'exists:stores,id'],
            'store_tujuan_id' => ['required', 'integer', 'exists:stores,id', 'different:store_asal_id'],
            'status' => ['required', 'string', 'in:draft,dikirim,selesai'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.produk_id' => ['required', 'integer', 'exists:produk,id'],
            'details.*.qty' => ['required', 'integer', 'min:1'],
            'details.*.harga_beli' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_tujuan_id.different' => 'Toko tujuan harus berbeda dengan toko asal.',
            'details.min' => 'Minimal harus ada 1 produk yang dikirim.',
        ];
    }
}
