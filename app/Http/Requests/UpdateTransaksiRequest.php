<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransaksiRequest extends FormRequest
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
        $id = $this->route('transaksi')->id;

        return [
            'no_nota' => ['required', 'string', 'max:50', "unique:transaksi,no_nota,{$id}"],
            'no_kasir' => ['nullable', 'string', 'max:50'],
            'anggota_id' => ['nullable', 'integer', 'exists:anggota,id'],
            'nama_anggota' => ['nullable', 'string', 'max:150'],
            'nilai' => ['nullable', 'numeric', 'min:0'],
            'diskon' => ['nullable', 'numeric', 'min:0'],
            'jual' => ['nullable', 'numeric', 'min:0'],
            'usaha' => ['nullable', 'numeric', 'min:0'],
            'jasa' => ['nullable', 'numeric', 'min:0'],
            'ppn' => ['nullable', 'numeric', 'min:0'],
            'cash' => ['nullable', 'numeric', 'min:0'],
            'qris' => ['nullable', 'numeric', 'min:0'],
            'edc' => ['nullable', 'numeric', 'min:0'],
            'voucher' => ['nullable', 'numeric', 'min:0'],
            'piutang' => ['nullable', 'numeric', 'min:0'],
            'tanggal' => ['nullable', 'date'],

            'items' => ['nullable', 'array', 'min:1'],
            'items.*.produk_id' => ['nullable', 'integer', 'exists:produk,id'],
            'items.*.nama_barang' => ['nullable', 'string', 'max:150'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.harga' => ['nullable', 'numeric', 'min:0'],
            'items.*.diskon_item' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Normalkan field numerik: kosong ('' / null) diubah menjadi 0.
     */
    protected function prepareForValidation(): void
    {
        foreach (['nilai', 'diskon', 'jual', 'usaha', 'jasa', 'ppn', 'cash', 'qris', 'edc', 'voucher', 'piutang'] as $field) {
            $value = $this->input($field);
            if ($value === null || $value === '') {
                $this->merge([$field => 0]);
            }
        }
    }
}
