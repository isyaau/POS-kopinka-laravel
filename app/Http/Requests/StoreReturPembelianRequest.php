<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReturPembelianRequest extends FormRequest
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
            'no_retur' => ['nullable', 'string', 'max:50', 'unique:retur_pembelian,no_retur'],
            'pembelian_id' => ['nullable', 'integer', 'exists:pembelian,id'],
            'no_pembelian_asal' => ['nullable', 'string', 'max:50'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'nama_supplier' => ['nullable', 'string', 'max:150'],
            'tanggal' => ['nullable', 'date'],
            'tipe' => ['nullable', Rule::in(['retur', 'tukar'])],
            'alasan' => ['nullable', 'string'],
            'total_retur' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['draft', 'selesai', 'batal'])],
            'keterangan' => ['nullable', 'string'],

            // Items (detail retur)
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.produk_id' => ['nullable', 'integer', 'exists:produk,id'],
            'items.*.nama_barang' => ['nullable', 'string', 'max:150', 'required_without:items.*.produk_id'],
            'items.*.qty_retur' => ['required', 'integer', 'min:1'],
            'items.*.harga_beli' => ['nullable', 'numeric', 'min:0'],
            'items.*.subtotal' => ['nullable', 'numeric', 'min:0'],
            'items.*.produk_tukar_id' => ['nullable', 'integer', 'exists:produk,id'],
            'items.*.nama_barang_tukar' => ['nullable', 'string', 'max:150'],
            'items.*.qty_tukar' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Pastikan setiap item memiliki produk (id) ATAU nama barang.
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            foreach ($items as $index => $item) {
                $produkId = $item['produk_id'] ?? null;
                $nama = trim((string) ($item['nama_barang'] ?? ''));
                if (! $produkId && $nama === '') {
                    $validator->errors()->add("items.{$index}.produk_id", 'Pilih produk atau ketik nama barang.');
                }
            }
        });
    }

    /**
     * Normalkan field numerik & boolean.
     */
    protected function prepareForValidation(): void
    {
        foreach (['total_retur'] as $field) {
            $value = $this->input($field);
            if ($value === null || $value === '') {
                $this->merge([$field => 0]);
            }
        }
    }
}
