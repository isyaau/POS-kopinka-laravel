<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStokOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['draft', 'selesai', 'batal'])],
            'petugas' => ['nullable', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['required', 'integer', 'exists:produk,id'],
            'items.*.nama_barang' => ['nullable', 'string', 'max:150'],
            'items.*.stok_sistem' => ['nullable', 'integer', 'min:0'],
            'items.*.stok_fisik' => ['required', 'integer', 'min:0'],
        ];
    }
}
