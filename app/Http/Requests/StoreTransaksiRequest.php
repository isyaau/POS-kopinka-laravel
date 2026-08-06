<?php

namespace App\Http\Requests;

use App\Models\Transaksi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransaksiRequest extends FormRequest
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
            'no_nota' => ['nullable', 'string', 'max:50', 'unique:transaksi,no_nota'],
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
            'voucher_ids' => ['nullable', 'array'],
            'voucher_ids.*' => ['integer', 'exists:vouchers,id'],
            'piutang' => ['nullable', 'numeric', 'min:0'],
            'tanggal' => ['nullable', 'date'],

            // Items (detail transaksi)
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.produk_id' => ['nullable', 'integer', 'exists:produk,id'],
            'items.*.nama_barang' => ['nullable', 'string', 'max:150'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.harga' => ['nullable', 'numeric', 'min:0'],
            'items.*.diskon_item' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Auto-generate no_nota jika kosong & normalkan field numerik.
     */
    protected function prepareForValidation(): void
    {
        $noNota = trim($this->input('no_nota') ?? '');

        if ($noNota === '') {
            $this->merge(['no_nota' => (new Transaksi())->generateNoNota()]);
        }

        foreach (['nilai', 'diskon', 'jual', 'usaha', 'jasa', 'ppn', 'cash', 'qris', 'edc', 'voucher', 'piutang'] as $field) {
            $value = $this->input($field);
            if ($value === null || $value === '') {
                $this->merge([$field => 0]);
            }
        }
    }
}
