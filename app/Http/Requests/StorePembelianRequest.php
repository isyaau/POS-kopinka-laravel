<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePembelianRequest extends FormRequest
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
            'no_pembelian' => ['nullable', 'string', 'max:50', 'unique:pembelian,no_pembelian'],
            'no_faktur' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'nama_supplier' => ['nullable', 'string', 'max:150'],
            'tanggal' => ['nullable', 'date'],
            'terlampir_bukti_ppn' => ['nullable', 'boolean'],
            'harga_jual_termasuk_ppn' => ['nullable', 'boolean'],
            'nilai' => ['nullable', 'numeric', 'min:0'],
            'diskon' => ['nullable', 'numeric', 'min:0'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'ppn_masukan' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'jenis_bayar' => ['nullable', Rule::in(['tunai', 'kredit'])],
            'sisa_hutang' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['draft', 'selesai', 'batal'])],
            'keterangan' => ['nullable', 'string'],

            // Items (detail pembelian)
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.produk_id' => ['nullable', 'integer', 'exists:produk,id'],
            'items.*.nama_barang' => ['nullable', 'string', 'max:150', 'required_without:items.*.produk_id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.harga_beli' => ['nullable', 'numeric', 'min:0'],
            'items.*.diskon_item' => ['nullable', 'numeric', 'min:0'],
            'items.*.harga_jual' => ['nullable', 'numeric', 'min:0'],
            'items.*.tanggal_expired' => ['nullable', 'date'],
        ];
    }

    /**
     * Pastikan setiap item memiliki produk (id) ATAU nama barang (produk baru).
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            foreach ($items as $index => $item) {
                $produkId = $item['produk_id'] ?? null;
                $nama = trim((string) ($item['nama_barang'] ?? ''));
                if (! $produkId && $nama === '') {
                    $validator->errors()->add("items.{$index}.produk_id", 'Pilih produk atau ketik nama produk baru.');
                }
            }
        });
    }

    /**
     * Normalkan field numerik & boolean.
     */
    protected function prepareForValidation(): void
    {
        foreach ([
            'nilai', 'diskon', 'subtotal', 'ppn_masukan', 'total', 'sisa_hutang',
        ] as $field) {
            $value = $this->input($field);
            if ($value === null || $value === '') {
                $this->merge([$field => 0]);
            }
        }

        foreach (['terlampir_bukti_ppn', 'harga_jual_termasuk_ppn'] as $field) {
            $value = $this->input($field);
            if ($value === null || $value === '') {
                $this->merge([$field => false]);
            }
        }
    }
}
